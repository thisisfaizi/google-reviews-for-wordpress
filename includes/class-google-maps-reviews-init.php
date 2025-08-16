<?php
/**
 * Plugin Initialization Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Google_Maps_Reviews_Init {
    
    /**
     * Plugin instance
     *
     * @var Google_Maps_Reviews_Init
     */
    private static $instance = null;
    
    /**
     * Plugin components
     *
     * @var array
     */
    private $components = array();
    
    /**
     * Get plugin instance
     *
     * @return Google_Maps_Reviews_Init
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Core WordPress hooks
        add_action('plugins_loaded', array($this, 'init_plugin'), 0);
        add_action('init', array($this, 'init_components'), 10);
        add_action('wp_loaded', array($this, 'late_init'), 20);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_footer', array($this, 'add_footer_scripts'));
        
        // Widget and shortcode hooks
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('init', array($this, 'register_shortcodes'));
        
        // AJAX hooks
        add_action('wp_ajax_' . GMRW_AJAX_REFRESH_REVIEWS, array($this, 'ajax_refresh_reviews'));
        add_action('wp_ajax_nopriv_' . GMRW_AJAX_REFRESH_REVIEWS, array($this, 'ajax_refresh_reviews'));
        add_action('wp_ajax_' . GMRW_AJAX_GET_REVIEWS, array($this, 'ajax_get_reviews'));
        add_action('wp_ajax_nopriv_' . GMRW_AJAX_GET_REVIEWS, array($this, 'ajax_get_reviews'));
        
        // Cron hooks
        add_action(GMRW_CRON_HOOK, array($this, 'cron_refresh_reviews'));
        add_action('gmrw_refresh_cache', array($this, 'handle_background_cache_refresh'));
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        // Activation/deactivation hooks
        register_activation_hook(GMRW_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(GMRW_PLUGIN_FILE, array($this, 'deactivate'));
        register_uninstall_hook(GMRW_PLUGIN_FILE, array($this, 'uninstall'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . GMRW_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Plugin row meta
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
        
        // Content filters
        add_filter('the_content', array($this, 'content_filter'));
        add_filter('widget_text', array($this, 'widget_text_filter'));
        
        // Security hooks
        add_action('wp_loaded', array($this, 'security_checks'));
        
        // Performance hooks
        add_action('wp_head', array($this, 'add_preload_hints'));
        add_action('wp_footer', array($this, 'add_performance_scripts'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init_plugin() {
        // Load text domain
        $this->load_textdomain();
        
        // Initialize autoloader
        $this->init_autoloader();
        
        // Check requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Initialize configuration
        $this->init_configuration();
        
        // Set up error handling
        $this->setup_error_handling();
        
        // Initialize components
        $this->init_core_components();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Fire init action
        do_action('gmrw_init');
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize admin if in admin area
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize frontend components
        $this->init_frontend();
        
        // Initialize API endpoints
        $this->init_api();
        
        // Fire components init action
        do_action('gmrw_components_init');
    }
    
    /**
     * Late initialization
     */
    public function late_init() {
        // Initialize any components that need to run after everything is loaded
        $this->init_late_components();
        
        // Fire late init action
        do_action('gmrw_late_init');
    }
    
    /**
     * Load text domain
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            GMRW_TEXT_DOMAIN,
            false,
            dirname(GMRW_PLUGIN_BASENAME) . GMRW_DOMAIN_PATH
        );
    }
    
    /**
     * Initialize autoloader
     */
    private function init_autoloader() {
        require_once GMRW_PLUGIN_DIR . 'includes/class-google-maps-reviews-autoloader.php';
        Google_Maps_Reviews_Autoloader::register();
    }
    
    /**
     * Check plugin requirements
     */
    private function check_requirements() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wordpress_version_notice'));
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check for required PHP extensions
        $required_extensions = array('curl', 'json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                add_action('admin_notices', function() use ($extension) {
                    $this->extension_notice($extension);
                });
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Initialize configuration
     */
    private function init_configuration() {
        // Ensure default settings are set
        $settings = Google_Maps_Reviews_Config::get_settings();
        if (empty($settings)) {
            Google_Maps_Reviews_Config::reset_settings();
        }
    }
    
    /**
     * Set up error handling
     */
    private function setup_error_handling() {
        // Set error reporting based on configuration
        $log_level = Google_Maps_Reviews_Config::get('log_level', GMRW_LOG_LEVEL_ERROR);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_reporting(E_ALL);
        }
        
        // Set up custom error handler if logging is enabled
        if (Google_Maps_Reviews_Config::get('enable_logging', false)) {
            set_error_handler(array($this, 'custom_error_handler'));
        }
    }
    
    /**
     * Initialize core components
     */
    private function init_core_components() {
        // Initialize cache system
        $this->components['cache'] = new Google_Maps_Reviews_Cache();
        
        // Initialize scraper
        $this->components['scraper'] = new Google_Maps_Reviews_Scraper();
        
        // Initialize display system
        $this->components['display'] = new Google_Maps_Reviews_Display();
        
        // Initialize widget
        $this->components['widget'] = new Google_Maps_Reviews_Widget();
        
        // Initialize shortcode
        $this->components['shortcode'] = new Google_Maps_Reviews_Shortcode();
    }
    
    /**
     * Initialize admin components
     */
    private function init_admin() {
        $this->components['admin'] = new Google_Maps_Reviews_Admin();
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin initialization is handled by Google_Maps_Reviews_Admin class
    }
    
    /**
     * Admin menu setup
     */
    public function admin_menu() {
        // Admin menu is handled by Google_Maps_Reviews_Admin class
    }
    
    /**
     * Initialize frontend components
     */
    private function init_frontend() {
        // Frontend-specific initialization
        add_action('wp_head', array($this, 'add_frontend_meta'));
    }
    
    /**
     * Initialize API
     */
    private function init_api() {
        // Initialize REST API endpoints if needed
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Initialize late components
     */
    private function init_late_components() {
        // Components that need to run after everything is loaded
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        if (!empty($settings['auto_refresh']) && !wp_next_scheduled(GMRW_CRON_HOOK)) {
            wp_schedule_event(time(), GMRW_CRON_INTERVAL, GMRW_CRON_HOOK);
        }
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Google_Maps_Reviews_Widget');
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Shortcodes are registered in the Google_Maps_Reviews_Shortcode constructor
        // This method is kept for potential future enhancements
        do_action('gmrw_shortcodes_registered');
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_scripts() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        // Enqueue main CSS file
        // Enqueue main CSS
        $css_url = Google_Maps_Reviews_Minifier::get_asset_url('assets/css/google-maps-reviews.css');
        wp_enqueue_style(
            'google-maps-reviews-widget',
            $css_url,
            array(),
            GMRW_VERSION
        );
        
        // Enqueue layout-specific CSS files
        $layouts = array('list', 'cards', 'carousel', 'grid');
        foreach ($layouts as $layout) {
            wp_enqueue_style(
                'google-maps-reviews-' . $layout,
                GMRW_PLUGIN_URL . 'assets/css/layouts/' . $layout . '.css',
                array('google-maps-reviews-widget'),
                GMRW_VERSION
            );
        }
        
        // Enqueue utility functions first
        wp_enqueue_script(
            'google-maps-reviews-utils',
            GMRW_PLUGIN_URL . 'assets/js/utils.js',
            array('jquery'),
            GMRW_VERSION,
            true
        );
        
        // Enqueue carousel functionality
        wp_enqueue_script(
            'google-maps-reviews-carousel',
            GMRW_PLUGIN_URL . 'assets/js/carousel.js',
            array('jquery', 'google-maps-reviews-utils'),
            GMRW_VERSION,
            true
        );
        
        // Enqueue filtering functionality
        wp_enqueue_script(
            'google-maps-reviews-filters',
            GMRW_PLUGIN_URL . 'assets/js/filters.js',
            array('jquery', 'google-maps-reviews-utils'),
            GMRW_VERSION,
            true
        );
        
        // Enqueue pagination functionality
        wp_enqueue_script(
            'google-maps-reviews-pagination',
            GMRW_PLUGIN_URL . 'assets/js/pagination.js',
            array('jquery', 'google-maps-reviews-utils'),
            GMRW_VERSION,
            true
        );
        
        // Enqueue main JavaScript file last
        // Enqueue main JavaScript
        $js_url = Google_Maps_Reviews_Minifier::get_asset_url('assets/js/google-maps-reviews.js');
        wp_enqueue_script(
            'google-maps-reviews-widget',
            $js_url,
            array('jquery', 'google-maps-reviews-utils', 'google-maps-reviews-carousel', 'google-maps-reviews-filters', 'google-maps-reviews-pagination'),
            GMRW_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('google-maps-reviews-widget', 'gmrw_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(GMRW_NONCE_ACTION),
            'strings' => array(
                'loading' => __('Loading reviews...', GMRW_TEXT_DOMAIN),
                'error' => __('Error loading reviews', GMRW_TEXT_DOMAIN),
                'no_reviews' => __('No reviews available', GMRW_TEXT_DOMAIN),
                'read_more' => __('Read more', GMRW_TEXT_DOMAIN),
                'read_less' => __('Read less', GMRW_TEXT_DOMAIN),
                'previous' => __('Previous', GMRW_TEXT_DOMAIN),
                'next' => __('Next', GMRW_TEXT_DOMAIN),
                'first' => __('First', GMRW_TEXT_DOMAIN),
                'last' => __('Last', GMRW_TEXT_DOMAIN),
                'page' => __('Page', GMRW_TEXT_DOMAIN),
                'of' => __('of', GMRW_TEXT_DOMAIN),
                'reviews' => __('reviews', GMRW_TEXT_DOMAIN),
                'filter_by_rating' => __('Filter by rating', GMRW_TEXT_DOMAIN),
                'filter_by_date' => __('Filter by date', GMRW_TEXT_DOMAIN),
                'sort_by' => __('Sort by', GMRW_TEXT_DOMAIN),
                'clear_filters' => __('Clear filters', GMRW_TEXT_DOMAIN)
            ),
            'settings' => array(
                'auto_refresh' => !empty($settings['auto_refresh']),
                'refresh_interval' => $settings['refresh_interval'] ?? 86400,
                'carousel_autoplay' => !empty($settings['carousel_autoplay']),
                'carousel_speed' => $settings['carousel_speed'] ?? 5000,
                'pagination_enabled' => !empty($settings['pagination_enabled']),
                'filters_enabled' => !empty($settings['filters_enabled']),
                'lazy_loading' => !empty($settings['lazy_loading']),
                'accessibility' => !empty($settings['accessibility'])
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook) {
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
    }
    
    /**
     * AJAX handler for refreshing reviews
     */
    public function ajax_refresh_reviews() {
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
    
    /**
     * AJAX handler for getting reviews
     */
    public function ajax_get_reviews() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], GMRW_NONCE_ACTION)) {
            wp_die(__('Security check failed', GMRW_TEXT_DOMAIN));
        }
        
        $business_url = sanitize_url($_POST['business_url']);
        if (empty($business_url)) {
            wp_send_json_error(__('Business URL is required', GMRW_TEXT_DOMAIN));
        }
        
        // Get cached reviews
        $cache = new Google_Maps_Reviews_Cache();
        $reviews = $cache->get_reviews($business_url);
        
        if (empty($reviews)) {
            wp_send_json_error(__('No reviews found', GMRW_TEXT_DOMAIN));
        }
        
        wp_send_json_success($reviews);
    }
    
    /**
     * Cron job for refreshing reviews
     */
    public function cron_refresh_reviews() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $business_url = $settings['business_url'] ?? '';
        
        if (empty($business_url)) {
            return;
        }
        
        $scraper = new Google_Maps_Reviews_Scraper();
        $scraper->get_reviews($business_url);
    }
    
    /**
     * Handle background cache refresh
     *
     * @param string $business_url The business URL to refresh
     */
    public function handle_background_cache_refresh($business_url) {
        if (empty($business_url)) {
            return;
        }
        
        // Initialize cache and handle refresh
        $cache = new Google_Maps_Reviews_Cache();
        $cache->handle_background_refresh($business_url);
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['gmrw_hourly'] = array(
            'interval' => 3600,
            'display' => __('Every Hour', GMRW_TEXT_DOMAIN)
        );
        
        $schedules['gmrw_twice_daily'] = array(
            'interval' => 43200,
            'display' => __('Twice Daily', GMRW_TEXT_DOMAIN)
        );
        
        return $schedules;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        Google_Maps_Reviews_Activator::activate();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        Google_Maps_Reviews_Deactivator::deactivate();
    }
    
    /**
     * Plugin uninstall
     */
    public function uninstall() {
        Google_Maps_Reviews_Uninstall::uninstall();
    }
    
    /**
     * Add plugin action links
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=' . GMRW_PLUGIN_SLUG) . '">' . __('Settings', GMRW_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function plugin_row_meta($links, $file) {
        if (GMRW_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="' . esc_url('https://github.com/thisisfaizi/google-reviews-for-wordpress') . '" target="_blank">' . __('Documentation', GMRW_TEXT_DOMAIN) . '</a>',
                'support' => '<a href="' . esc_url('https://github.com/thisisfaizi/google-reviews-for-wordpress/issues') . '" target="_blank">' . __('Support', GMRW_TEXT_DOMAIN) . '</a>',
            );
            
            return array_merge($links, $row_meta);
        }
        
        return $links;
    }
    
    /**
     * Content filter
     */
    public function content_filter($content) {
        // Process shortcodes in content
        return $content;
    }
    
    /**
     * Widget text filter
     */
    public function widget_text_filter($text) {
        // Process shortcodes in widget text
        return $text;
    }
    
    /**
     * Security checks
     */
    public function security_checks() {
        // Perform security checks
        if (!is_admin() && !wp_doing_ajax()) {
            // Frontend security checks
        }
    }
    
    /**
     * Add preload hints
     */
    public function add_preload_hints() {
        // Add resource hints for performance
        echo '<link rel="preload" href="' . GMRW_PLUGIN_URL . 'assets/css/google-maps-reviews.css" as="style">';
    }
    
    /**
     * Add performance scripts
     */
    public function add_performance_scripts() {
        // Add performance monitoring scripts
    }
    
    /**
     * Add meta tags
     */
    public function add_meta_tags() {
        // Add meta tags for SEO
    }
    
    /**
     * Add footer scripts
     */
    public function add_footer_scripts() {
        // Add any footer scripts
    }
    
    /**
     * Add frontend meta
     */
    public function add_frontend_meta() {
        // Add frontend-specific meta tags
    }
    
    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        // Register REST API routes if needed
    }
    
    /**
     * Custom error handler
     */
    public function custom_error_handler($errno, $errstr, $errfile, $errline) {
        // Custom error handling for logging
        if (Google_Maps_Reviews_Config::get('enable_logging', false)) {
            error_log("Google Maps Reviews Widget Error: [$errno] $errstr in $errfile on line $errline");
        }
        
        return false; // Let PHP handle the error normally
    }
    
    /**
     * WordPress version notice
     */
    public function wordpress_version_notice() {
        echo '<div class="notice notice-error"><p>' . 
             sprintf(__('Google Maps Reviews Widget requires WordPress 5.0 or higher. You are running version %s.', GMRW_TEXT_DOMAIN), get_bloginfo('version')) . 
             '</p></div>';
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>' . 
             sprintf(__('Google Maps Reviews Widget requires PHP 7.4 or higher. You are running version %s.', GMRW_TEXT_DOMAIN), PHP_VERSION) . 
             '</p></div>';
    }
    
    /**
     * Extension notice
     */
    public function extension_notice($extension) {
        echo '<div class="notice notice-error"><p>' . 
             sprintf(__('Google Maps Reviews Widget requires the %s PHP extension to be installed.', GMRW_TEXT_DOMAIN), $extension) . 
             '</p></div>';
    }
    
    /**
     * Get component
     */
    public function get_component($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
    
    /**
     * Get all components
     */
    public function get_components() {
        return $this->components;
    }
}
