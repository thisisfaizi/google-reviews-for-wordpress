<?php
/**
 * Plugin Name: Google Maps Reviews Widget
 * Plugin URI: https://github.com/your-username/google-maps-reviews-widget
 * Description: Display Google Maps business reviews on your WordPress website using a widget or shortcode. No API key required - simply enter your Google Maps business URL.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
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
 * @author Your Name
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
    load_plugin_textdomain('google-maps-reviews-widget', false, dirname(GMRW_PLUGIN_BASENAME) . '/languages');
    
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
    if (!wp_next_scheduled('gmrw_refresh_reviews')) {
        $settings = get_option('gmrw_settings', array());
        if (!empty($settings['auto_refresh'])) {
            wp_schedule_event(time(), 'daily', 'gmrw_refresh_reviews');
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
        'nonce' => wp_create_nonce('gmrw_nonce'),
        'strings' => array(
            'loading' => __('Loading reviews...', 'google-maps-reviews-widget'),
            'error' => __('Error loading reviews', 'google-maps-reviews-widget'),
            'no_reviews' => __('No reviews available', 'google-maps-reviews-widget')
        )
    ));
}

// Initialize plugin after WordPress is loaded
add_action('plugins_loaded', 'gmrw_init');

// Handle AJAX requests
add_action('wp_ajax_gmrw_refresh_reviews', 'gmrw_ajax_refresh_reviews');
add_action('wp_ajax_nopriv_gmrw_refresh_reviews', 'gmrw_ajax_refresh_reviews');

/**
 * AJAX handler for refreshing reviews
 */
function gmrw_ajax_refresh_reviews() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'gmrw_nonce')) {
        wp_die(__('Security check failed', 'google-maps-reviews-widget'));
    }
    
    $business_url = sanitize_url($_POST['business_url']);
    if (empty($business_url)) {
        wp_send_json_error(__('Business URL is required', 'google-maps-reviews-widget'));
    }
    
    // Initialize scraper and get reviews
    $scraper = new Google_Maps_Reviews_Scraper();
    $reviews = $scraper->get_reviews($business_url);
    
    if (is_wp_error($reviews)) {
        wp_send_json_error($reviews->get_error_message());
    }
    
    wp_send_json_success($reviews);
}
