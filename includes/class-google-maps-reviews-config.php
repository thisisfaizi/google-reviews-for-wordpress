<?php
/**
 * Plugin Configuration Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Google_Maps_Reviews_Config {
    
    /**
     * Default plugin settings
     *
     * @var array
     */
    private static $default_settings = array(
        'business_url' => '',
        'cache_duration' => GMRW_CACHE_DURATION,
        'max_reviews' => GMRW_DEFAULT_MAX_REVIEWS,
        'default_layout' => GMRW_DEFAULT_LAYOUT,
        'auto_refresh' => true,
        'refresh_interval' => 86400, // 24 hours
        'show_avatar' => GMRW_DEFAULT_SHOW_AVATAR,
        'show_date' => GMRW_DEFAULT_SHOW_DATE,
        'show_rating' => GMRW_DEFAULT_SHOW_RATING,
        'rate_limiting' => true,
        'request_timeout' => GMRW_REQUEST_TIMEOUT,
        'rate_limit_delay' => GMRW_RATE_LIMIT_DELAY,
        'enable_logging' => false,
        'log_level' => GMRW_LOG_LEVEL_ERROR,
        'custom_css' => '',
        'custom_js' => '',
        'enable_ajax' => true,
        'enable_shortcode' => true,
        'enable_widget' => true,
        'display_options' => array(
            'show_business_info' => true,
            'show_review_count' => true,
            'show_average_rating' => true,
            'show_review_date' => true,
            'show_reviewer_name' => true,
            'show_reviewer_avatar' => true,
            'show_review_content' => true,
            'show_review_rating' => true,
            'show_helpful_votes' => false,
            'show_owner_response' => false,
        ),
        'filter_options' => array(
            'min_rating' => 1,
            'max_rating' => 5,
            'sort_by' => 'date', // date, rating, helpfulness
            'sort_order' => 'desc', // asc, desc
            'exclude_owner_responses' => false,
        ),
        'layout_options' => array(
            'list' => array(
                'enabled' => true,
                'show_avatar' => true,
                'show_date' => true,
                'show_rating' => true,
                'truncate_content' => true,
                'max_content_length' => 200,
            ),
            'cards' => array(
                'enabled' => true,
                'show_avatar' => true,
                'show_date' => true,
                'show_rating' => true,
                'truncate_content' => true,
                'max_content_length' => 150,
                'columns' => 3,
            ),
            'carousel' => array(
                'enabled' => true,
                'show_avatar' => true,
                'show_date' => true,
                'show_rating' => true,
                'truncate_content' => true,
                'max_content_length' => 100,
                'autoplay' => true,
                'autoplay_speed' => 5000,
                'show_navigation' => true,
                'show_dots' => true,
            ),
            'grid' => array(
                'enabled' => true,
                'show_avatar' => true,
                'show_date' => true,
                'show_rating' => true,
                'truncate_content' => true,
                'max_content_length' => 120,
                'columns' => 4,
            ),
        ),
    );
    
    /**
     * Get plugin settings
     *
     * @param string $key Optional specific setting key
     * @return mixed
     */
    public static function get_settings($key = null) {
        $settings = get_option(GMRW_OPTION_SETTINGS, array());
        $settings = wp_parse_args($settings, self::$default_settings);
        
        if ($key !== null) {
            return isset($settings[$key]) ? $settings[$key] : null;
        }
        
        return $settings;
    }
    
    /**
     * Update plugin settings
     *
     * @param array $settings New settings
     * @return bool
     */
    public static function update_settings($settings) {
        $current_settings = self::get_settings();
        $new_settings = wp_parse_args($settings, $current_settings);
        
        return update_option(GMRW_OPTION_SETTINGS, $new_settings);
    }
    
    /**
     * Get a specific setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public static function get($key, $default = null) {
        $settings = self::get_settings();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default;
    }
    
    /**
     * Set a specific setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public static function set($key, $value) {
        $settings = self::get_settings();
        $settings[$key] = $value;
        
        return self::update_settings($settings);
    }
    
    /**
     * Get default settings
     *
     * @return array
     */
    public static function get_default_settings() {
        return self::$default_settings;
    }
    
    /**
     * Reset settings to defaults
     *
     * @return bool
     */
    public static function reset_settings() {
        return update_option(GMRW_OPTION_SETTINGS, self::$default_settings);
    }
    
    /**
     * Get database table name with prefix
     *
     * @param string $table_name Table name without prefix
     * @return string
     */
    public static function get_table_name($table_name) {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }
    
    /**
     * Get reviews table name
     *
     * @return string
     */
    public static function get_reviews_table() {
        return self::get_table_name(GMRW_TABLE_REVIEWS_NAME);
    }
    
    /**
     * Get logs table name
     *
     * @return string
     */
    public static function get_logs_table() {
        return self::get_table_name(GMRW_TABLE_LOGS_NAME);
    }
    
    /**
     * Get available layouts
     *
     * @return array
     */
    public static function get_available_layouts() {
        return array(
            'list' => __('List', GMRW_TEXT_DOMAIN),
            'cards' => __('Cards', GMRW_TEXT_DOMAIN),
            'carousel' => __('Carousel', GMRW_TEXT_DOMAIN),
            'grid' => __('Grid', GMRW_TEXT_DOMAIN),
        );
    }
    
    /**
     * Get available sort options
     *
     * @return array
     */
    public static function get_sort_options() {
        return array(
            'date' => __('Date', GMRW_TEXT_DOMAIN),
            'rating' => __('Rating', GMRW_TEXT_DOMAIN),
            'helpfulness' => __('Helpfulness', GMRW_TEXT_DOMAIN),
        );
    }
    
    /**
     * Get available sort orders
     *
     * @return array
     */
    public static function get_sort_orders() {
        return array(
            'asc' => __('Ascending', GMRW_TEXT_DOMAIN),
            'desc' => __('Descending', GMRW_TEXT_DOMAIN),
        );
    }
    
    /**
     * Get available log levels
     *
     * @return array
     */
    public static function get_log_levels() {
        return array(
            GMRW_LOG_LEVEL_ERROR => __('Error', GMRW_TEXT_DOMAIN),
            GMRW_LOG_LEVEL_WARNING => __('Warning', GMRW_TEXT_DOMAIN),
            GMRW_LOG_LEVEL_INFO => __('Info', GMRW_TEXT_DOMAIN),
            GMRW_LOG_LEVEL_DEBUG => __('Debug', GMRW_TEXT_DOMAIN),
        );
    }
    
    /**
     * Validate business URL
     *
     * @param string $url Business URL
     * @return bool
     */
    public static function validate_business_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's a Google Maps URL
        $google_maps_patterns = array(
            '/maps\.google\./',
            '/google\.com\/maps/',
            '/goo\.gl\/maps/',
        );
        
        foreach ($google_maps_patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse and validate Google Maps business URL
     *
     * @param string $url Business URL
     * @return array|false Parsed URL data or false on failure
     */
    public static function parse_business_url($url) {
        if (!self::validate_business_url($url)) {
            return false;
        }
        
        $parsed = array(
            'original_url' => $url,
            'place_id' => '',
            'business_name' => '',
            'url_type' => '',
            'is_valid' => false,
            'errors' => array(),
        );
        
        // Extract place ID using various patterns
        $place_id = self::extract_place_id_from_url($url);
        if ($place_id) {
            $parsed['place_id'] = $place_id;
            $parsed['is_valid'] = true;
        } else {
            $parsed['errors'][] = __('Could not extract place ID from URL', GMRW_TEXT_DOMAIN);
        }
        
        // Determine URL type
        $parsed['url_type'] = self::get_url_type($url);
        
        // Extract business name if possible
        $parsed['business_name'] = self::extract_business_name_from_url($url);
        
        return $parsed;
    }
    
    /**
     * Extract place ID from Google Maps URL
     *
     * @param string $url Google Maps URL
     * @return string|false Place ID or false on failure
     */
    public static function extract_place_id_from_url($url) {
        // Common patterns for Google Maps URLs
        $patterns = array(
            // maps.google.com/maps/place/Business+Name/place_id
            '/maps\.google\.com\/maps\/place\/[^\/]+\/([^\/\?]+)/',
            // maps.google.com/maps?cid=place_id
            '/maps\.google\.com\/maps\?.*cid=([^&\s]+)/',
            // maps.google.com/maps?q=place_id:place_id
            '/maps\.google\.com\/maps\?.*q=place_id:([^&\s]+)/',
            // goo.gl/maps/place_id
            '/goo\.gl\/maps\/([^\/\?]+)/',
            // google.com/maps/place/Business+Name/place_id
            '/google\.com\/maps\/place\/[^\/]+\/([^\/\?]+)/',
            // maps.google.com/maps/place/place_id
            '/maps\.google\.com\/maps\/place\/([^\/\?]+)/',
            // google.com/maps/place/place_id
            '/google\.com\/maps\/place\/([^\/\?]+)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $place_id = $matches[1];
                
                // Validate place ID format
                if (self::is_valid_place_id($place_id)) {
                    return $place_id;
                }
            }
        }
        
        // Try to extract from any URL with place_id parameter
        if (preg_match('/place_id=([^&\s]+)/', $url, $matches)) {
            $place_id = $matches[1];
            if (self::is_valid_place_id($place_id)) {
                return $place_id;
            }
        }
        
        return false;
    }
    
    /**
     * Validate place ID format
     *
     * @param string $place_id Place ID to validate
     * @return bool
     */
    public static function is_valid_place_id($place_id) {
        if (empty($place_id)) {
            return false;
        }
        
        // Google Place IDs are typically alphanumeric and can contain special characters
        // They usually start with a letter and are 27 characters long
        if (preg_match('/^[A-Za-z0-9_-]{20,}$/', $place_id)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Extract business name from URL
     *
     * @param string $url Google Maps URL
     * @return string Business name or empty string
     */
    public static function extract_business_name_from_url($url) {
        // Try to extract business name from URL path
        $patterns = array(
            // maps.google.com/maps/place/Business+Name/place_id
            '/maps\.google\.com\/maps\/place\/([^\/]+)\/[^\/\?]+/',
            // google.com/maps/place/Business+Name/place_id
            '/google\.com\/maps\/place\/([^\/]+)\/[^\/\?]+/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $business_name = urldecode($matches[1]);
                $business_name = str_replace('+', ' ', $business_name);
                return trim($business_name);
            }
        }
        
        return '';
    }
    
    /**
     * Get URL type
     *
     * @param string $url Google Maps URL
     * @return string URL type
     */
    public static function get_url_type($url) {
        if (strpos($url, 'maps.google.com') !== false) {
            if (strpos($url, '/maps/place/') !== false) {
                return 'place_page';
            } elseif (strpos($url, '?cid=') !== false) {
                return 'cid_parameter';
            } elseif (strpos($url, '?q=place_id:') !== false) {
                return 'place_id_query';
            } else {
                return 'maps_general';
            }
        } elseif (strpos($url, 'google.com/maps') !== false) {
            return 'google_maps';
        } elseif (strpos($url, 'goo.gl/maps') !== false) {
            return 'shortened';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Normalize Google Maps URL
     *
     * @param string $url Google Maps URL
     * @return string|false Normalized URL or false on failure
     */
    public static function normalize_business_url($url) {
        $parsed = self::parse_business_url($url);
        
        if (!$parsed || !$parsed['is_valid']) {
            return false;
        }
        
        // Return a standardized URL format
        return 'https://www.google.com/maps/place/' . $parsed['place_id'];
    }
    
    /**
     * Get URL validation errors
     *
     * @param string $url Business URL
     * @return array Array of validation errors
     */
    public static function get_url_validation_errors($url) {
        $errors = array();
        
        if (empty($url)) {
            $errors[] = __('Business URL is required', GMRW_TEXT_DOMAIN);
            return $errors;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Invalid URL format', GMRW_TEXT_DOMAIN);
            return $errors;
        }
        
        if (!self::validate_business_url($url)) {
            $errors[] = __('URL must be a valid Google Maps business URL', GMRW_TEXT_DOMAIN);
            return $errors;
        }
        
        $place_id = self::extract_place_id_from_url($url);
        if (!$place_id) {
            $errors[] = __('Could not extract place ID from URL', GMRW_TEXT_DOMAIN);
        } elseif (!self::is_valid_place_id($place_id)) {
            $errors[] = __('Invalid place ID format', GMRW_TEXT_DOMAIN);
        }
        
        return $errors;
    }
    
    /**
     * Get cache key for business URL
     *
     * @param string $business_url Business URL
     * @param string $suffix Optional suffix
     * @return string
     */
    public static function get_cache_key($business_url, $suffix = '') {
        $key = 'gmrw_' . md5($business_url);
        
        if (!empty($suffix)) {
            $key .= '_' . $suffix;
        }
        
        return $key;
    }
    
    /**
     * Get plugin capabilities
     *
     * @return array
     */
    public static function get_capabilities() {
        return array(
            'manage_settings' => GMRW_CAPABILITY,
            'view_reviews' => 'read',
            'refresh_reviews' => GMRW_CAPABILITY,
            'clear_cache' => GMRW_CAPABILITY,
        );
    }
    
    /**
     * Check if user has capability
     *
     * @param string $capability Capability to check
     * @return bool
     */
    public static function user_can($capability) {
        $capabilities = self::get_capabilities();
        
        if (!isset($capabilities[$capability])) {
            return false;
        }
        
        return current_user_can($capabilities[$capability]);
    }
}
