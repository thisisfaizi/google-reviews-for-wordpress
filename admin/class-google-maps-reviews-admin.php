<?php
/**
 * Admin Interface Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the WordPress admin interface for the plugin
 */
class Google_Maps_Reviews_Admin {
    
    /**
     * Settings page hook
     *
     * @var string
     */
    private $settings_page_hook;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . GMRW_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_gmrw_test_business_url', array($this, 'ajax_test_business_url'));
        add_action('wp_ajax_gmrw_refresh_cache', array($this, 'ajax_refresh_cache'));
        add_action('wp_ajax_gmrw_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_gmrw_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_gmrw_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_gmrw_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_gmrw_minify_assets', array($this, 'ajax_minify_assets'));
        add_action('wp_ajax_gmrw_get_performance_stats', array($this, 'ajax_get_performance_stats'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $this->settings_page_hook = add_options_page(
            __('Google Maps Reviews', GMRW_TEXT_DOMAIN),
            __('Google Maps Reviews', GMRW_TEXT_DOMAIN),
            'manage_options',
            GMRW_PLUGIN_SLUG,
            array($this, 'render_settings_page')
        );
        
        // Add submenu pages
        add_submenu_page(
            GMRW_PLUGIN_SLUG,
            __('Settings', GMRW_TEXT_DOMAIN),
            __('Settings', GMRW_TEXT_DOMAIN),
            'manage_options',
            GMRW_PLUGIN_SLUG,
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            GMRW_PLUGIN_SLUG,
            __('Documentation', GMRW_TEXT_DOMAIN),
            __('Documentation', GMRW_TEXT_DOMAIN),
            'manage_options',
            GMRW_PLUGIN_SLUG . '-docs',
            array($this, 'render_documentation_page')
        );
        
        add_submenu_page(
            GMRW_PLUGIN_SLUG,
            __('System Status', GMRW_TEXT_DOMAIN),
            __('System Status', GMRW_TEXT_DOMAIN),
            'manage_options',
            GMRW_PLUGIN_SLUG . '-status',
            array($this, 'render_status_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings
        register_setting(
            GMRW_PLUGIN_SLUG,
            GMRW_OPTION_SETTINGS,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => Google_Maps_Reviews_Config::get_default_settings()
            )
        );
        
        // General Settings Section
        add_settings_section(
            'general_settings',
            __('General Settings', GMRW_TEXT_DOMAIN),
            array($this, 'render_general_section'),
            GMRW_PLUGIN_SLUG
        );
        
        // Business URL field
        add_settings_field(
            'business_url',
            __('Default Business URL', GMRW_TEXT_DOMAIN),
            array($this, 'render_business_url_field'),
            GMRW_PLUGIN_SLUG,
            'general_settings'
        );
        
        // Cache Settings Section
        add_settings_section(
            'cache_settings',
            __('Cache Settings', GMRW_TEXT_DOMAIN),
            array($this, 'render_cache_section'),
            GMRW_PLUGIN_SLUG
        );
        
        // Cache duration field
        add_settings_field(
            'cache_duration',
            __('Cache Duration', GMRW_TEXT_DOMAIN),
            array($this, 'render_cache_duration_field'),
            GMRW_PLUGIN_SLUG,
            'cache_settings'
        );
        
        // Auto refresh field
        add_settings_field(
            'auto_refresh',
            __('Auto Refresh', GMRW_TEXT_DOMAIN),
            array($this, 'render_auto_refresh_field'),
            GMRW_PLUGIN_SLUG,
            'cache_settings'
        );
        
        // Display Settings Section
        add_settings_section(
            'display_settings',
            __('Display Settings', GMRW_TEXT_DOMAIN),
            array($this, 'render_display_section'),
            GMRW_PLUGIN_SLUG
        );
        
        // Default layout field
        add_settings_field(
            'default_layout',
            __('Default Layout', GMRW_TEXT_DOMAIN),
            array($this, 'render_default_layout_field'),
            GMRW_PLUGIN_SLUG,
            'display_settings'
        );
        
        // Max reviews field
        add_settings_field(
            'max_reviews',
            __('Default Max Reviews', GMRW_TEXT_DOMAIN),
            array($this, 'render_max_reviews_field'),
            GMRW_PLUGIN_SLUG,
            'display_settings'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'advanced_settings',
            __('Advanced Settings', GMRW_TEXT_DOMAIN),
            array($this, 'render_advanced_section'),
            GMRW_PLUGIN_SLUG
        );
        
        // Rate limiting field
        add_settings_field(
            'rate_limiting',
            __('Rate Limiting', GMRW_TEXT_DOMAIN),
            array($this, 'render_rate_limiting_field'),
            GMRW_PLUGIN_SLUG,
            'advanced_settings'
        );
        
        // Logging field
        add_settings_field(
            'enable_logging',
            __('Enable Logging', GMRW_TEXT_DOMAIN),
            array($this, 'render_logging_field'),
            GMRW_PLUGIN_SLUG,
            'advanced_settings'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, GMRW_PLUGIN_SLUG) === false) {
            return;
        }
        
        wp_enqueue_style(
            'google-maps-reviews-admin',
            GMRW_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            GMRW_VERSION
        );
        
        wp_enqueue_script(
            'google-maps-reviews-admin',
            GMRW_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            GMRW_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('google-maps-reviews-admin', 'gmrwAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(GMRW_NONCE_ACTION),
            'strings' => array(
                'testing' => __('Testing...', GMRW_TEXT_DOMAIN),
                'success' => __('Success!', GMRW_TEXT_DOMAIN),
                'error' => __('Error!', GMRW_TEXT_DOMAIN),
                'confirmClear' => __('Are you sure you want to clear all cached data?', GMRW_TEXT_DOMAIN),
                'confirmRefresh' => __('Are you sure you want to refresh all cached data?', GMRW_TEXT_DOMAIN),
            )
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', GMRW_TEXT_DOMAIN));
        }
        
        include GMRW_PLUGIN_DIR . 'admin/partials/admin-settings.php';
    }
    
    /**
     * Render documentation page
     */
    public function render_documentation_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', GMRW_TEXT_DOMAIN));
        }
        
        include GMRW_PLUGIN_DIR . 'admin/partials/admin-documentation.php';
    }
    
    /**
     * Render status page
     */
    public function render_status_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', GMRW_TEXT_DOMAIN));
        }
        
        include GMRW_PLUGIN_DIR . 'admin/partials/admin-status.php';
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=' . GMRW_PLUGIN_SLUG) . '">' . __('Settings', GMRW_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        
        $docs_link = '<a href="' . admin_url('options-general.php?page=' . GMRW_PLUGIN_SLUG . '-docs') . '">' . __('Documentation', GMRW_TEXT_DOMAIN) . '</a>';
        array_push($links, $docs_link);
        
        return $links;
    }
    
    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $screen = get_current_screen();
        
        // Only show on plugin pages
        if (strpos($screen->id, GMRW_PLUGIN_SLUG) === false) {
            return;
        }
        
        // Check for errors
        $errors = get_option(GMRW_PLUGIN_SLUG . '_errors', array());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
            delete_option(GMRW_PLUGIN_SLUG . '_errors');
        }
        
        // Check for success messages
        $success = get_option(GMRW_PLUGIN_SLUG . '_success', array());
        if (!empty($success)) {
            foreach ($success as $message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            delete_option(GMRW_PLUGIN_SLUG . '_success');
        }
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Business URL
        if (isset($input['business_url'])) {
            $sanitized['business_url'] = esc_url_raw($input['business_url']);
        }
        
        // Cache duration
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
            $sanitized['cache_duration'] = max(300, min(86400, $sanitized['cache_duration']));
        }
        
        // Auto refresh
        $sanitized['auto_refresh'] = isset($input['auto_refresh']);
        
        // Default layout
        if (isset($input['default_layout'])) {
            $allowed_layouts = array('list', 'cards', 'carousel', 'grid');
            $sanitized['default_layout'] = in_array($input['default_layout'], $allowed_layouts) ? $input['default_layout'] : 'list';
        }
        
        // Max reviews
        if (isset($input['max_reviews'])) {
            $sanitized['max_reviews'] = absint($input['max_reviews']);
            $sanitized['max_reviews'] = max(1, min(50, $sanitized['max_reviews']));
        }
        
        // Rate limiting
        $sanitized['rate_limiting'] = isset($input['rate_limiting']);
        
        // Logging
        $sanitized['enable_logging'] = isset($input['enable_logging']);
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for testing business URL
     */
    public function ajax_test_business_url() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
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
        
        // Test scraping
        try {
            $scraper = new Google_Maps_Reviews_Scraper();
            $reviews = $scraper->get_reviews($business_url, 1);
            
            if (is_wp_error($reviews)) {
                wp_send_json_error($reviews->get_error_message());
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully retrieved %d reviews', GMRW_TEXT_DOMAIN), count($reviews)),
                'review_count' => count($reviews)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for refreshing cache
     */
    public function ajax_refresh_cache() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        // Clear all transients
        $cache = new Google_Maps_Reviews_Cache();
        $result = $cache->clear_all_cache();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Cache refreshed successfully', GMRW_TEXT_DOMAIN));
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        // Clear all transients
        $cache = new Google_Maps_Reviews_Cache();
        $result = $cache->clear_all_cache();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Cache cleared successfully', GMRW_TEXT_DOMAIN));
    }
    
    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        $settings = Google_Maps_Reviews_Config::get_settings();
        $export_data = array(
            'version' => GMRW_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        );
        
        wp_send_json_success($export_data);
    }
    
    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        $import_data = json_decode(stripslashes($_POST['settings']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid import data', GMRW_TEXT_DOMAIN));
        }
        
        if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
            wp_send_json_error(__('Invalid settings data', GMRW_TEXT_DOMAIN));
        }
        
        // Update settings
        $result = update_option(GMRW_OPTION_SETTINGS, $import_data['settings']);
        
        if ($result) {
            wp_send_json_success(__('Settings imported successfully', GMRW_TEXT_DOMAIN));
        } else {
            wp_send_json_error(__('Failed to import settings', GMRW_TEXT_DOMAIN));
        }
    }
    
    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        // Test basic connectivity
        $test_url = 'https://www.google.com/maps';
        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(__('Connection test failed: ', GMRW_TEXT_DOMAIN) . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            wp_send_json_error(sprintf(__('Connection test failed: HTTP %d', GMRW_TEXT_DOMAIN), $status_code));
        }
        
        wp_send_json_success(__('Connection test successful', GMRW_TEXT_DOMAIN));
    }
    
    /**
     * AJAX handler for minifying assets
     */
    public function ajax_minify_assets() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        try {
            $results = Google_Maps_Reviews_Minifier::minify_all_assets();
            $stats = Google_Maps_Reviews_Minifier::get_optimization_stats();
            
            wp_send_json_success(array(
                'message' => __('Assets minified successfully', GMRW_TEXT_DOMAIN),
                'results' => $results,
                'stats' => $stats
            ));
        } catch (Exception $e) {
            wp_send_json_error(__('Minification failed: ', GMRW_TEXT_DOMAIN) . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for getting performance stats
     */
    public function ajax_get_performance_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_send_json_error(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', GMRW_TEXT_DOMAIN));
        }
        
        try {
            $cache = new Google_Maps_Reviews_Cache();
            $cache_stats = $cache->get_cache_stats();
            $cache_metrics = $cache->get_performance_metrics();
            $optimization_stats = Google_Maps_Reviews_Minifier::get_optimization_stats();
            
            wp_send_json_success(array(
                'cache_stats' => $cache_stats,
                'cache_metrics' => $cache_metrics,
                'optimization_stats' => $optimization_stats
            ));
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to get performance stats: ', GMRW_TEXT_DOMAIN) . $e->getMessage());
        }
    }
    
    // Settings field renderers
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure the general settings for the Google Maps Reviews plugin.', GMRW_TEXT_DOMAIN) . '</p>';
    }
    
    public function render_cache_section() {
        echo '<p>' . esc_html__('Configure caching settings to optimize performance.', GMRW_TEXT_DOMAIN) . '</p>';
    }
    
    public function render_display_section() {
        echo '<p>' . esc_html__('Configure default display settings for reviews.', GMRW_TEXT_DOMAIN) . '</p>';
    }
    
    public function render_advanced_section() {
        echo '<p>' . esc_html__('Configure advanced settings for the plugin.', GMRW_TEXT_DOMAIN) . '</p>';
    }
    
    public function render_business_url_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $business_url = $settings['business_url'] ?? '';
        ?>
        <input type="url" 
               id="business_url" 
               name="<?php echo GMRW_OPTION_SETTINGS; ?>[business_url]" 
               value="<?php echo esc_attr($business_url); ?>" 
               class="regular-text"
               placeholder="https://www.google.com/maps/place/Your+Business+Name">
        <p class="description">
            <?php esc_html_e('Default Google Maps business URL for widgets and shortcodes.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <button type="button" class="button button-secondary" id="test-business-url">
            <?php esc_html_e('Test URL', GMRW_TEXT_DOMAIN); ?>
        </button>
        <span id="test-result"></span>
        <?php
    }
    
    public function render_cache_duration_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $cache_duration = $settings['cache_duration'] ?? GMRW_CACHE_DURATION;
        ?>
        <input type="number" 
               id="cache_duration" 
               name="<?php echo GMRW_OPTION_SETTINGS; ?>[cache_duration]" 
               value="<?php echo esc_attr($cache_duration); ?>" 
               min="300" 
               max="86400" 
               step="300"
               class="small-text">
        <span><?php esc_html_e('seconds (5 minutes to 24 hours)', GMRW_TEXT_DOMAIN); ?></span>
        <p class="description">
            <?php esc_html_e('How long to cache reviews before refreshing.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
    
    public function render_auto_refresh_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $auto_refresh = $settings['auto_refresh'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   id="auto_refresh" 
                   name="<?php echo GMRW_OPTION_SETTINGS; ?>[auto_refresh]" 
                   value="1" 
                   <?php checked($auto_refresh); ?>>
            <?php esc_html_e('Automatically refresh cached reviews', GMRW_TEXT_DOMAIN); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Enable automatic background refresh of cached reviews.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
    
    public function render_default_layout_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $default_layout = $settings['default_layout'] ?? GMRW_DEFAULT_LAYOUT;
        $layouts = array(
            'list' => __('List', GMRW_TEXT_DOMAIN),
            'cards' => __('Cards', GMRW_TEXT_DOMAIN),
            'carousel' => __('Carousel', GMRW_TEXT_DOMAIN),
            'grid' => __('Grid', GMRW_TEXT_DOMAIN)
        );
        ?>
        <select id="default_layout" name="<?php echo GMRW_OPTION_SETTINGS; ?>[default_layout]">
            <?php foreach ($layouts as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($default_layout, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Default layout for displaying reviews.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
    
    public function render_max_reviews_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $max_reviews = $settings['max_reviews'] ?? GMRW_DEFAULT_MAX_REVIEWS;
        ?>
        <input type="number" 
               id="max_reviews" 
               name="<?php echo GMRW_OPTION_SETTINGS; ?>[max_reviews]" 
               value="<?php echo esc_attr($max_reviews); ?>" 
               min="1" 
               max="50" 
               class="small-text">
        <p class="description">
            <?php esc_html_e('Default maximum number of reviews to display.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
    
    public function render_rate_limiting_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $rate_limiting = $settings['rate_limiting'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   id="rate_limiting" 
                   name="<?php echo GMRW_OPTION_SETTINGS; ?>[rate_limiting]" 
                   value="1" 
                   <?php checked($rate_limiting); ?>>
            <?php esc_html_e('Enable rate limiting', GMRW_TEXT_DOMAIN); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Respect Google\'s rate limits to avoid being blocked.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
    
    public function render_logging_field() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $enable_logging = $settings['enable_logging'] ?? false;
        ?>
        <label>
            <input type="checkbox" 
                   id="enable_logging" 
                   name="<?php echo GMRW_OPTION_SETTINGS; ?>[enable_logging]" 
                   value="1" 
                   <?php checked($enable_logging); ?>>
            <?php esc_html_e('Enable error logging', GMRW_TEXT_DOMAIN); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Log errors and debugging information to WordPress error log.', GMRW_TEXT_DOMAIN); ?>
        </p>
        <?php
    }
}
