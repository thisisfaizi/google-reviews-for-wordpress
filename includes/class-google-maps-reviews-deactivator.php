<?php
/**
 * Plugin Deactivation Handler
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin deactivation tasks
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Deactivator {

    /**
     * Plugin deactivation hook
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();

        // Clear transients
        self::clear_transients();

        // Log deactivation
        self::log_deactivation();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear all scheduled events
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear review refresh cron job
        wp_clear_scheduled_hook('gmrw_refresh_reviews');

        // Clear any other scheduled events
        wp_clear_scheduled_hook('gmrw_cache_cleanup');
        wp_clear_scheduled_hook('gmrw_error_log_cleanup');

        // Remove cron schedules
        remove_filter('cron_schedules', array('Google_Maps_Reviews_Deactivator', 'add_cron_schedules'));
    }

    /**
     * Clear all plugin transients
     *
     * @since 1.0.0
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all transients with our prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gmrw_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_gmrw_%'");

        // Also clear site transients if multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_gmrw_%'");
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_gmrw_%'");
        }
    }

    /**
     * Log deactivation event
     *
     * @since 1.0.0
     */
    private static function log_deactivation() {
        $log_data = array(
            'event' => 'plugin_deactivated',
            'version' => GMRW_VERSION,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url(),
            'deactivation_reason' => 'user_deactivated',
        );

        // Store in options for debugging
        add_option('gmrw_deactivation_log', $log_data);

        // Also log to error log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Google Maps Reviews Widget deactivated: ' . json_encode($log_data));
        }
    }

    /**
     * Add custom cron schedules
     *
     * @since 1.0.0
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function add_cron_schedules($schedules) {
        $schedules['gmrw_hourly'] = array(
            'interval' => 3600,
            'display' => __('Every Hour (Google Maps Reviews)', 'google-maps-reviews-widget')
        );

        $schedules['gmrw_daily'] = array(
            'interval' => 86400,
            'display' => __('Daily (Google Maps Reviews)', 'google-maps-reviews-widget')
        );

        $schedules['gmrw_weekly'] = array(
            'interval' => 604800,
            'display' => __('Weekly (Google Maps Reviews)', 'google-maps-reviews-widget')
        );

        return $schedules;
    }

    /**
     * Check if plugin was just deactivated
     *
     * @since 1.0.0
     * @return bool
     */
    public static function was_just_deactivated() {
        $deactivated = get_option('gmrw_deactivated', false);
        if ($deactivated) {
            delete_option('gmrw_deactivated');
            return true;
        }
        return false;
    }

    /**
     * Set deactivation flag
     *
     * @since 1.0.0
     */
    public static function set_deactivation_flag() {
        add_option('gmrw_deactivated', true);
        add_option('gmrw_deactivation_time', current_time('timestamp'));
    }
}
