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

/**
 * Initialize the plugin
 */
function gmrw_init() {
    // Initialize the plugin using the comprehensive initialization class
    Google_Maps_Reviews_Init::get_instance();
}

// Initialize plugin after WordPress is loaded
add_action('plugins_loaded', 'gmrw_init', 0);
