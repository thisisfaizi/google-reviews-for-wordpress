<?php
/**
 * Plugin Activation Handler
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin activation tasks
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Activator {

    /**
     * Plugin activation hook
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(GMRW_PLUGIN_FILE));
            wp_die(__('This plugin requires WordPress 5.0 or higher.', 'google-maps-reviews-widget'));
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(GMRW_PLUGIN_FILE));
            wp_die(__('This plugin requires PHP 7.4 or higher.', 'google-maps-reviews-widget'));
        }

        // Create default options
        self::create_default_options();

        // Create database tables if needed
        self::create_database_tables();

        // Set activation flag
        add_option('gmrw_activated', true);
        add_option('gmrw_activation_time', current_time('timestamp'));

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log activation
        self::log_activation();
    }

    /**
     * Create default plugin options
     *
     * @since 1.0.0
     */
    private static function create_default_options() {
        $default_settings = array(
            'cache_duration' => 3600, // 1 hour
            'max_reviews' => 10,
            'default_layout' => 'list',
            'auto_refresh' => true,
            'refresh_interval' => 86400, // 24 hours
            'rate_limit_delay' => 2, // seconds between requests
            'show_reviewer_image' => true,
            'show_review_date' => true,
            'show_rating' => true,
            'show_helpful_votes' => true,
            'show_business_response' => true,
            'default_avatar' => GMRW_PLUGIN_URL . 'assets/images/default-avatar.png',
            'text_truncate_length' => 200,
            'enable_pagination' => true,
            'reviews_per_page' => 5,
            'enable_filtering' => true,
            'min_rating_filter' => 1,
            'max_rating_filter' => 5,
            'sort_by' => 'date', // date, rating, helpfulness
            'sort_order' => 'desc', // asc, desc
            'enable_carousel' => true,
            'carousel_autoplay' => true,
            'carousel_interval' => 5000, // milliseconds
            'enable_lazy_loading' => true,
            'custom_css' => '',
            'debug_mode' => false,
            'log_errors' => true,
            'max_retry_attempts' => 3,
            'retry_delay' => 5, // seconds
        );

        // Only add if not already exists
        if (!get_option('gmrw_settings')) {
            add_option('gmrw_settings', $default_settings);
        }

        // Add version
        add_option('gmrw_version', GMRW_VERSION);
    }

    /**
     * Create database tables if needed
     *
     * @since 1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Reviews cache table
        $table_name = $wpdb->prefix . 'gmrw_reviews_cache';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_url varchar(500) NOT NULL,
            business_name varchar(255) NOT NULL,
            reviews_data longtext NOT NULL,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            cache_expiry timestamp NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY business_url (business_url(191)),
            KEY cache_expiry (cache_expiry)
        ) $charset_collate;";

        // Error log table
        $error_table = $wpdb->prefix . 'gmrw_logs';
        $error_sql = "CREATE TABLE $error_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL DEFAULT 'error',
            message text NOT NULL,
            context longtext,
            error_type varchar(50),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY level (level),
            KEY error_type (error_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($error_sql);
    }

    /**
     * Log activation event
     *
     * @since 1.0.0
     */
    private static function log_activation() {
        $log_data = array(
            'event' => 'plugin_activated',
            'version' => GMRW_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url(),
        );

        // Store in options for debugging
        add_option('gmrw_activation_log', $log_data);

        // Also log to error log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Google Maps Reviews Widget activated: ' . json_encode($log_data));
        }
    }

    /**
     * Check if plugin was just activated
     *
     * @since 1.0.0
     * @return bool
     */
    public static function was_just_activated() {
        $activated = get_option('gmrw_activated', false);
        if ($activated) {
            delete_option('gmrw_activated');
            return true;
        }
        return false;
    }
}
