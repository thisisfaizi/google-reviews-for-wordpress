<?php
/**
 * Plugin Uninstall Handler
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin uninstall tasks
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Uninstall {

    /**
     * Plugin uninstall hook
     *
     * @since 1.0.0
     */
    public static function uninstall() {
        // Check if user has permission
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Check if we should remove data
        $settings = get_option('gmrw_settings', array());
        $remove_data = isset($settings['remove_data_on_uninstall']) ? $settings['remove_data_on_uninstall'] : false;

        if ($remove_data) {
            self::remove_all_data();
        } else {
            self::remove_plugin_files_only();
        }

        // Log uninstall
        self::log_uninstall();
    }

    /**
     * Remove all plugin data
     *
     * @since 1.0.0
     */
    private static function remove_all_data() {
        global $wpdb;

        // Remove all plugin options
        self::remove_plugin_options();

        // Remove database tables
        self::remove_database_tables();

        // Remove transients
        self::remove_transients();

        // Remove scheduled events
        self::remove_scheduled_events();

        // Remove uploaded files
        self::remove_uploaded_files();

        // Remove widget instances
        self::remove_widget_instances();
    }

    /**
     * Remove only plugin files (keep data)
     *
     * @since 1.0.0
     */
    private static function remove_plugin_files_only() {
        // Only remove scheduled events and transients
        self::remove_scheduled_events();
        self::remove_transients();
    }

    /**
     * Remove all plugin options
     *
     * @since 1.0.0
     */
    private static function remove_plugin_options() {
        $options_to_remove = array(
            'gmrw_version',
            'gmrw_settings',
            'gmrw_cache',
            'gmrw_activated',
            'gmrw_activation_time',
            'gmrw_deactivated',
            'gmrw_deactivation_time',
            'gmrw_activation_log',
            'gmrw_deactivation_log',
            'gmrw_uninstall_log',
            'widget_google_maps_reviews_widget',
            'sidebars_widgets', // Will be updated to remove our widget
        );

        foreach ($options_to_remove as $option) {
            delete_option($option);
        }

        // Remove site options if multisite
        if (is_multisite()) {
            foreach ($options_to_remove as $option) {
                delete_site_option($option);
            }
        }
    }

    /**
     * Remove database tables
     *
     * @since 1.0.0
     */
    private static function remove_database_tables() {
        global $wpdb;

        $tables_to_remove = array(
            $wpdb->prefix . 'gmrw_reviews_cache',
            $wpdb->prefix . 'gmrw_error_log',
        );

        foreach ($tables_to_remove as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Remove all transients
     *
     * @since 1.0.0
     */
    private static function remove_transients() {
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
     * Remove scheduled events
     *
     * @since 1.0.0
     */
    private static function remove_scheduled_events() {
        wp_clear_scheduled_hook('gmrw_refresh_reviews');
        wp_clear_scheduled_hook('gmrw_cache_cleanup');
        wp_clear_scheduled_hook('gmrw_error_log_cleanup');
    }

    /**
     * Remove uploaded files
     *
     * @since 1.0.0
     */
    private static function remove_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/google-maps-reviews-widget/';

        if (is_dir($plugin_upload_dir)) {
            self::delete_directory($plugin_upload_dir);
        }
    }

    /**
     * Remove widget instances
     *
     * @since 1.0.0
     */
    private static function remove_widget_instances() {
        $sidebars_widgets = get_option('sidebars_widgets', array());

        foreach ($sidebars_widgets as $sidebar_id => $widgets) {
            if (is_array($widgets)) {
                foreach ($widgets as $key => $widget_id) {
                    if (strpos($widget_id, 'google_maps_reviews_widget') === 0) {
                        unset($sidebars_widgets[$sidebar_id][$key]);
                    }
                }
            }
        }

        update_option('sidebars_widgets', $sidebars_widgets);
    }

    /**
     * Recursively delete directory
     *
     * @since 1.0.0
     * @param string $dir Directory to delete
     * @return bool Success status
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Log uninstall event
     *
     * @since 1.0.0
     */
    private static function log_uninstall() {
        $log_data = array(
            'event' => 'plugin_uninstalled',
            'version' => GMRW_VERSION,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url(),
            'data_removed' => true,
        );

        // Store in options for debugging (will be removed if data removal is enabled)
        add_option('gmrw_uninstall_log', $log_data);

        // Also log to error log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Google Maps Reviews Widget uninstalled: ' . json_encode($log_data));
        }
    }

    /**
     * Check if plugin was just uninstalled
     *
     * @since 1.0.0
     * @return bool
     */
    public static function was_just_uninstalled() {
        $uninstalled = get_option('gmrw_uninstalled', false);
        if ($uninstalled) {
            delete_option('gmrw_uninstalled');
            return true;
        }
        return false;
    }

    /**
     * Set uninstall flag
     *
     * @since 1.0.0
     */
    public static function set_uninstall_flag() {
        add_option('gmrw_uninstalled', true);
        add_option('gmrw_uninstall_time', current_time('timestamp'));
    }
}
