<?php
/**
 * Plugin Name: Google Maps Reviews Widget
 * Plugin URI: https://github.com/thisisfaizi/google-reviews-for-wordpress
 * Description: Display Google Maps business reviews on your WordPress website using a widget or shortcode. No API key required - simply enter your Google Maps business URL.
 * Version: 1.0.0
 * Author: nowdigiverse
 * Author URI: https://nowdigiverse.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: google-maps-reviews-widget
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package GoogleMapsReviewsWidget
 * @version 1.0.0
 * @author nowdigiverse
 * @license GPL v2 or later
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GMRW_VERSION', '1.0.0');
define('GMRW_PLUGIN_FILE', __FILE__);
define('GMRW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GMRW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GMRW_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('GMRW_PLUGIN_NAME', 'Google Maps Reviews Widget');
define('GMRW_PLUGIN_SLUG', 'google-maps-reviews-widget');

// Database and option constants
define('GMRW_OPTION_SETTINGS', 'gmrw_settings');
define('GMRW_OPTION_VERSION', 'gmrw_version');
define('GMRW_OPTION_CACHE', 'gmrw_cache');
define('GMRW_OPTION_LOGS', 'gmrw_logs');

// Database table constants (will be set dynamically)
define('GMRW_TABLE_REVIEWS_NAME', 'gmrw_reviews');
define('GMRW_TABLE_LOGS_NAME', 'gmrw_logs');

// Cache and performance constants
define('GMRW_CACHE_DURATION', 3600); // 1 hour default
define('GMRW_MAX_REVIEWS', 50); // Maximum reviews to fetch
define('GMRW_REQUEST_TIMEOUT', 30); // HTTP request timeout in seconds
define('GMRW_RATE_LIMIT_DELAY', 2); // Delay between requests in seconds

// Display and layout constants
define('GMRW_DEFAULT_LAYOUT', 'list');
define('GMRW_DEFAULT_MAX_REVIEWS', 10);
define('GMRW_DEFAULT_SHOW_AVATAR', true);
define('GMRW_DEFAULT_SHOW_DATE', true);
define('GMRW_DEFAULT_SHOW_RATING', true);

// Security constants
define('GMRW_NONCE_ACTION', 'gmrw_nonce');
define('GMRW_CAPABILITY', 'manage_options');

// AJAX action constants
define('GMRW_AJAX_REFRESH_REVIEWS', 'gmrw_refresh_reviews');
define('GMRW_AJAX_GET_REVIEWS', 'gmrw_get_reviews');

// Cron job constants
define('GMRW_CRON_HOOK', 'gmrw_refresh_reviews');
define('GMRW_CRON_INTERVAL', 'daily');

// Error and logging constants
define('GMRW_LOG_LEVEL_ERROR', 'error');
define('GMRW_LOG_LEVEL_WARNING', 'warning');
define('GMRW_LOG_LEVEL_INFO', 'info');
define('GMRW_LOG_LEVEL_DEBUG', 'debug');

// Text domain constants
define('GMRW_TEXT_DOMAIN', 'google-maps-reviews-widget');
define('GMRW_DOMAIN_PATH', '/languages');

// Plugin activation hook
register_activation_hook(__FILE__, array('Google_Maps_Reviews_Activator', 'activate'));

// Plugin deactivation hook
register_deactivation_hook(__FILE__, array('Google_Maps_Reviews_Deactivator', 'deactivate'));

// Plugin uninstall hook
register_uninstall_hook(__FILE__, array('Google_Maps_Reviews_Uninstall', 'uninstall'));



/**
 * Initialize the plugin
 */
function gmrw_init() {
    // Load text domain for translations
    load_plugin_textdomain(GMRW_TEXT_DOMAIN, false, dirname(GMRW_PLUGIN_BASENAME) . GMRW_DOMAIN_PATH);
    
    // Initialize autoloader
    require_once GMRW_PLUGIN_DIR . 'includes/class-google-maps-reviews-autoloader.php';
    Google_Maps_Reviews_Autoloader::register();
    
    // Initialize admin functionality
    if (is_admin()) {
        require_once GMRW_PLUGIN_DIR . 'admin/class-google-maps-reviews-admin.php';
        new Google_Maps_Reviews_Admin();
    }
    
    // Register widget
    add_action('widgets_init', 'gmrw_register_widget');
    
    // Register shortcode
    add_action('init', 'gmrw_register_shortcode');
    
    // Enqueue frontend assets
    add_action('wp_enqueue_scripts', 'gmrw_enqueue_scripts');
    
    // Schedule review refresh if enabled
    if (!wp_next_scheduled(GMRW_CRON_HOOK)) {
        $settings = Google_Maps_Reviews_Config::get_settings();
        if (!empty($settings['auto_refresh'])) {
            wp_schedule_event(time(), GMRW_CRON_INTERVAL, GMRW_CRON_HOOK);
        }
    }
}

/**
 * Register the widget
 */
function gmrw_register_widget() {
    register_widget('Google_Maps_Reviews_Widget');
}

/**
 * Register the shortcode
 */
function gmrw_register_shortcode() {
    add_shortcode('google_maps_reviews', array('Google_Maps_Reviews_Shortcode', 'render'));
}

/**
 * Enqueue frontend scripts and styles
 */
function gmrw_enqueue_scripts() {
    wp_enqueue_style(
        'google-maps-reviews-widget',
        GMRW_PLUGIN_URL . 'assets/css/google-maps-reviews.css',
        array(),
        GMRW_VERSION
    );
    
    wp_enqueue_script(
        'google-maps-reviews-widget',
        GMRW_PLUGIN_URL . 'assets/js/google-maps-reviews.js',
        array('jquery'),
        GMRW_VERSION,
        true
    );
    
    // Localize script for AJAX
    wp_localize_script('google-maps-reviews-widget', 'gmrw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce(GMRW_NONCE_ACTION),
        'strings' => array(
            'loading' => __('Loading reviews...', GMRW_TEXT_DOMAIN),
            'error' => __('Error loading reviews', GMRW_TEXT_DOMAIN),
            'no_reviews' => __('No reviews available', GMRW_TEXT_DOMAIN)
        )
    ));
}

// Initialize plugin after WordPress is loaded
add_action('plugins_loaded', 'gmrw_init');

// Handle AJAX requests
add_action('wp_ajax_' . GMRW_AJAX_REFRESH_REVIEWS, 'gmrw_ajax_refresh_reviews');
add_action('wp_ajax_nopriv_' . GMRW_AJAX_REFRESH_REVIEWS, 'gmrw_ajax_refresh_reviews');

/**
 * AJAX handler for refreshing reviews
 */
function gmrw_ajax_refresh_reviews() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
        wp_die(__('Security check failed', GMRW_TEXT_DOMAIN));
    }
    
    // Check user capabilities
    if (!Google_Maps_Reviews_Config::user_can('refresh_reviews')) {
        wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
    }
    
    $business_url = sanitize_url($_POST['business_url']);
    if (empty($business_url)) {
        wp_send_json_error(__('Business URL is required', GMRW_TEXT_DOMAIN));
    }
    
    // Validate business URL
    if (!Google_Maps_Reviews_Config::validate_business_url($business_url)) {
        wp_send_json_error(__('Invalid Google Maps business URL', GMRW_TEXT_DOMAIN));
    }
    
    // Initialize scraper and get reviews
    $scraper = new Google_Maps_Reviews_Scraper();
    $reviews = $scraper->get_reviews($business_url);
    
    if (is_wp_error($reviews)) {
        wp_send_json_error($reviews->get_error_message());
    }
    
    wp_send_json_success($reviews);
}
