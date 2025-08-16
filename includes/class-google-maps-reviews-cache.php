<?php
/**
 * Cache Management Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles caching operations for the plugin
 */
class Google_Maps_Reviews_Cache {
    
    /**
     * Cache prefix
     *
     * @var string
     */
    private $cache_prefix = 'gmrw_';
    
    /**
     * Default cache duration
     *
     * @var int
     */
    private $default_duration = 3600; // 1 hour
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->default_duration = GMRW_CACHE_DURATION;
    }
    
    /**
     * Get cached reviews for a business URL
     *
     * @param string $business_url The business URL
     * @return array|false Cached reviews or false if not found
     */
    public function get_reviews($business_url) {
        $cache_key = $this->get_cache_key($business_url);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            return false;
        }
        
        // Validate cached data
        if (!is_array($cached_data) || !isset($cached_data['reviews']) || !isset($cached_data['timestamp'])) {
            return false;
        }
        
        // Check if cache has expired
        if (time() - $cached_data['timestamp'] > $this->default_duration) {
            return false;
        }
        
        return $cached_data['reviews'];
    }
    
    /**
     * Set cached reviews for a business URL
     *
     * @param string $business_url The business URL
     * @param array $reviews The reviews data
     * @param int $duration Cache duration in seconds (optional)
     * @return bool True on success, false on failure
     */
    public function set_reviews($business_url, $reviews, $duration = null) {
        if (empty($business_url) || !is_array($reviews)) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($business_url);
        $cache_duration = $duration ?: $this->default_duration;
        
        $cache_data = array(
            'reviews' => $reviews,
            'timestamp' => time(),
            'business_url' => $business_url
        );
        
        return set_transient($cache_key, $cache_data, $cache_duration);
    }
    
    /**
     * Get cached business info
     *
     * @param string $business_url The business URL
     * @return array|false Cached business info or false if not found
     */
    public function get_business_info($business_url) {
        $cache_key = $this->get_cache_key($business_url, 'business_info');
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            return false;
        }
        
        // Validate cached data
        if (!is_array($cached_data) || !isset($cached_data['data']) || !isset($cached_data['timestamp'])) {
            return false;
        }
        
        // Check if cache has expired
        if (time() - $cached_data['timestamp'] > $this->default_duration) {
            return false;
        }
        
        return $cached_data['data'];
    }
    
    /**
     * Set cached business info
     *
     * @param string $business_url The business URL
     * @param array $business_info The business info data
     * @param int $duration Cache duration in seconds (optional)
     * @return bool True on success, false on failure
     */
    public function set_business_info($business_url, $business_info, $duration = null) {
        if (empty($business_url) || !is_array($business_info)) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($business_url, 'business_info');
        $cache_duration = $duration ?: $this->default_duration;
        
        $cache_data = array(
            'data' => $business_info,
            'timestamp' => time(),
            'business_url' => $business_url
        );
        
        return set_transient($cache_key, $cache_data, $cache_duration);
    }
    
    /**
     * Delete cached data for a business URL
     *
     * @param string $business_url The business URL
     * @return bool True on success, false on failure
     */
    public function delete_cache($business_url) {
        if (empty($business_url)) {
            return false;
        }
        
        $reviews_key = $this->get_cache_key($business_url);
        $business_info_key = $this->get_cache_key($business_url, 'business_info');
        
        $reviews_deleted = delete_transient($reviews_key);
        $business_info_deleted = delete_transient($business_info_key);
        
        return $reviews_deleted || $business_info_deleted;
    }
    
    /**
     * Clear all plugin cache
     *
     * @return bool True on success, false on failure
     */
    public function clear_all_cache() {
        global $wpdb;
        
        // Get all transients with our prefix
        $transients = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        if (empty($transients)) {
            return true;
        }
        
        $deleted_count = 0;
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient);
            if (delete_transient($transient_name)) {
                $deleted_count++;
            }
        }
        
        return $deleted_count > 0;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        $total_items = count($transients);
        $total_size = 0;
        $expired_items = 0;
        $valid_items = 0;
        
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient->option_name);
            $cached_data = get_transient($transient_name);
            
            if ($cached_data === false) {
                $expired_items++;
            } else {
                $valid_items++;
                $total_size += strlen(serialize($cached_data));
            }
        }
        
        return array(
            'total_items' => $total_items,
            'valid_items' => $valid_items,
            'expired_items' => $expired_items,
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size)
        );
    }
    
    /**
     * Check if cache is working
     *
     * @return bool True if cache is working, false otherwise
     */
    public function is_cache_working() {
        $test_key = $this->cache_prefix . 'test_' . time();
        $test_data = array('test' => true, 'timestamp' => time());
        
        $set_result = set_transient($test_key, $test_data, 60);
        $get_result = get_transient($test_key);
        $delete_result = delete_transient($test_key);
        
        return $set_result && $get_result && $delete_result;
    }
    
    /**
     * Generate cache key for a business URL
     *
     * @param string $business_url The business URL
     * @param string $type Cache type (optional)
     * @return string Cache key
     */
    private function get_cache_key($business_url, $type = 'reviews') {
        $url_hash = md5($business_url);
        return $this->cache_prefix . $type . '_' . $url_hash;
    }
    
    /**
     * Get cache key prefix
     *
     * @return string Cache prefix
     */
    public function get_cache_prefix() {
        return $this->cache_prefix;
    }
    
    /**
     * Set cache duration
     *
     * @param int $duration Cache duration in seconds
     */
    public function set_cache_duration($duration) {
        if (is_numeric($duration) && $duration > 0) {
            $this->default_duration = (int) $duration;
        }
    }
    
    /**
     * Get cache duration
     *
     * @return int Cache duration in seconds
     */
    public function get_cache_duration() {
        return $this->default_duration;
    }
}
