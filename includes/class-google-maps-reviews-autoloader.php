<?php
/**
 * Plugin Autoloader
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles automatic class loading for the plugin
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Autoloader {

    /**
     * Plugin namespace
     *
     * @since 1.0.0
     * @var string
     */
    private $namespace = 'Google_Maps_Reviews';

    /**
     * Plugin directory path
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_dir;

    /**
     * Class file mapping
     *
     * @since 1.0.0
     * @var array
     */
    private $class_map = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->plugin_dir = GMRW_PLUGIN_DIR;
        $this->build_class_map();
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Build the class file mapping
     *
     * @since 1.0.0
     */
    private function build_class_map() {
        $this->class_map = array(
            // Core classes
            'Google_Maps_Reviews_Activator' => 'includes/class-google-maps-reviews-activator.php',
            'Google_Maps_Reviews_Deactivator' => 'includes/class-google-maps-reviews-deactivator.php',
            'Google_Maps_Reviews_Uninstall' => 'includes/class-google-maps-reviews-uninstall.php',
            'Google_Maps_Reviews_Config' => 'includes/class-google-maps-reviews-config.php',
            'Google_Maps_Reviews_Init' => 'includes/class-google-maps-reviews-init.php',
            'Google_Maps_Reviews_Scraper' => 'includes/class-google-maps-reviews-scraper.php',
            'Google_Maps_Reviews_Panther_Scraper' => 'includes/class-google-maps-reviews-panther-scraper.php',
            'Google_Maps_Reviews_Widget' => 'includes/class-google-maps-reviews-widget.php',
            'Google_Maps_Reviews_Shortcode' => 'includes/class-google-maps-reviews-shortcode.php',
            'Google_Maps_Reviews_Cache' => 'includes/class-google-maps-reviews-cache.php',
            'Google_Maps_Reviews_Display' => 'includes/class-google-maps-reviews-display.php',
            'Google_Maps_Reviews_Templates' => 'includes/class-google-maps-reviews-templates.php',
            'Google_Maps_Reviews_Filter' => 'includes/class-google-maps-reviews-filter.php',
            'Google_Maps_Reviews_Autoloader' => 'includes/class-google-maps-reviews-autoloader.php',
            
            // Admin classes
            'Google_Maps_Reviews_Admin' => 'admin/class-google-maps-reviews-admin.php',
            
            // Utility classes
            'Google_Maps_Reviews_Logger' => 'includes/class-google-maps-reviews-logger.php',
            'Google_Maps_Reviews_Validator' => 'includes/class-google-maps-reviews-validator.php',
            'Google_Maps_Reviews_Sanitizer' => 'includes/class-google-maps-reviews-sanitizer.php',
            'Google_Maps_Reviews_Helper' => 'includes/class-google-maps-reviews-helper.php',
            'Google_Maps_Reviews_Minifier' => 'includes/class-google-maps-reviews-minifier.php',
            
            // Exception classes
            'Google_Maps_Reviews_Exception' => 'includes/exceptions/class-google-maps-reviews-exception.php',
            'Google_Maps_Reviews_Scraping_Exception' => 'includes/exceptions/class-google-maps-reviews-scraping-exception.php',
            'Google_Maps_Reviews_Cache_Exception' => 'includes/exceptions/class-google-maps-reviews-cache-exception.php',
        );
    }

    /**
     * Autoload callback
     *
     * @since 1.0.0
     * @param string $class Class name
     */
    public function autoload($class) {
        // Check if this is one of our classes
        if (!isset($this->class_map[$class])) {
            return;
        }

        $file = $this->plugin_dir . $this->class_map[$class];

        // Check if file exists
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Log missing file for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Google Maps Reviews Widget: Class file not found: {$file} for class: {$class}");
            }
        }
    }

    /**
     * Register the autoloader
     *
     * @since 1.0.0
     */
    public static function register() {
        new self();
    }

    /**
     * Get class map for debugging
     *
     * @since 1.0.0
     * @return array
     */
    public function get_class_map() {
        return $this->class_map;
    }

    /**
     * Check if a class exists and is loadable
     *
     * @since 1.0.0
     * @param string $class Class name
     * @return bool
     */
    public function class_exists($class) {
        if (!isset($this->class_map[$class])) {
            return false;
        }

        $file = $this->plugin_dir . $this->class_map[$class];
        return file_exists($file);
    }

    /**
     * Load a specific class manually
     *
     * @since 1.0.0
     * @param string $class Class name
     * @return bool Success status
     */
    public function load_class($class) {
        if (!isset($this->class_map[$class])) {
            return false;
        }

        $file = $this->plugin_dir . $this->class_map[$class];
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    /**
     * Get all available classes
     *
     * @since 1.0.0
     * @return array
     */
    public function get_available_classes() {
        $available = array();
        
        foreach ($this->class_map as $class => $file) {
            if (file_exists($this->plugin_dir . $file)) {
                $available[] = $class;
            }
        }
        
        return $available;
    }

    /**
     * Get missing class files
     *
     * @since 1.0.0
     * @return array
     */
    public function get_missing_files() {
        $missing = array();
        
        foreach ($this->class_map as $class => $file) {
            if (!file_exists($this->plugin_dir . $file)) {
                $missing[$class] = $file;
            }
        }
        
        return $missing;
    }
}
