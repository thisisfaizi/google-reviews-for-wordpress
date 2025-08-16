<?php
/**
 * Helper Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides utility functions for the plugin
 */
class Google_Maps_Reviews_Helper {
    
    /**
     * Format a rating as stars
     *
     * @param float $rating The rating value
     * @param int $max_rating Maximum rating value
     * @return string HTML for star rating
     */
    public static function format_stars($rating, $max_rating = 5) {
        if (!is_numeric($rating) || $rating < 0) {
            return '';
        }
        
        $rating = min($rating, $max_rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = $max_rating - $full_stars - ($half_star ? 1 : 0);
        
        $stars_html = '<span class="gmrw-stars" aria-label="' . sprintf(__('%1$s out of %2$s stars', GMRW_TEXT_DOMAIN), $rating, $max_rating) . '">';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $stars_html .= '<span class="gmrw-star gmrw-star-full" aria-hidden="true">★</span>';
        }
        
        // Half star
        if ($half_star) {
            $stars_html .= '<span class="gmrw-star gmrw-star-half" aria-hidden="true">☆</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $stars_html .= '<span class="gmrw-star gmrw-star-empty" aria-hidden="true">☆</span>';
        }
        
        $stars_html .= '</span>';
        
        return $stars_html;
    }
    
    /**
     * Format a date
     *
     * @param string $date_string The date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function format_date($date_string, $format = '') {
        if (empty($date_string)) {
            return '';
        }
        
        if (empty($format)) {
            $format = get_option('date_format');
        }
        
        $timestamp = strtotime($date_string);
        
        if ($timestamp === false) {
            return $date_string;
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Truncate text to a specified length
     *
     * @param string $text The text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to add if truncated
     * @return string Truncated text
     */
    public static function truncate_text($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . $suffix;
    }
    
    /**
     * Get time ago string
     *
     * @param string $date_string The date string
     * @return string Time ago string
     */
    public static function time_ago($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        $timestamp = strtotime($date_string);
        
        if ($timestamp === false) {
            return $date_string;
        }
        
        $time_diff = time() - $timestamp;
        
        if ($time_diff < 60) {
            return __('just now', GMRW_TEXT_DOMAIN);
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, GMRW_TEXT_DOMAIN), $minutes);
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            return sprintf(_n('%d hour ago', '%d hours ago', $hours, GMRW_TEXT_DOMAIN), $hours);
        } elseif ($time_diff < 2592000) {
            $days = floor($time_diff / 86400);
            return sprintf(_n('%d day ago', '%d days ago', $days, GMRW_TEXT_DOMAIN), $days);
        } elseif ($time_diff < 31536000) {
            $months = floor($time_diff / 2592000);
            return sprintf(_n('%d month ago', '%d months ago', $months, GMRW_TEXT_DOMAIN), $months);
        } else {
            $years = floor($time_diff / 31536000);
            return sprintf(_n('%d year ago', '%d years ago', $years, GMRW_TEXT_DOMAIN), $years);
        }
    }
    
    /**
     * Sanitize HTML content
     *
     * @param string $content The content to sanitize
     * @param array $allowed_tags Allowed HTML tags
     * @return string Sanitized content
     */
    public static function sanitize_html($content, $allowed_tags = array()) {
        if (empty($allowed_tags)) {
            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'target' => array(),
                    'rel' => array()
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                'b' => array(),
                'i' => array(),
                'u' => array(),
                'p' => array(),
                'div' => array(),
                'span' => array(),
                'ul' => array(),
                'ol' => array(),
                'li' => array()
            );
        }
        
        return wp_kses($content, $allowed_tags);
    }
    
    /**
     * Get plugin asset URL
     *
     * @param string $path Asset path
     * @return string Asset URL
     */
    public static function get_asset_url($path) {
        return GMRW_PLUGIN_URL . 'assets/' . ltrim($path, '/');
    }
    
    /**
     * Get plugin asset path
     *
     * @param string $path Asset path
     * @return string Asset file path
     */
    public static function get_asset_path($path) {
        return GMRW_PLUGIN_DIR . 'assets/' . ltrim($path, '/');
    }
    
    /**
     * Check if a file exists in assets
     *
     * @param string $path Asset path
     * @return bool True if file exists, false otherwise
     */
    public static function asset_exists($path) {
        return file_exists(self::get_asset_path($path));
    }
    
    /**
     * Get default avatar URL
     *
     * @return string Default avatar URL
     */
    public static function get_default_avatar_url() {
        $avatar_path = 'images/default-avatar.svg';
        
        if (self::asset_exists($avatar_path)) {
            return self::get_asset_url($avatar_path);
        }
        
        // Fallback to WordPress default avatar
        return get_avatar_url(0);
    }
    
    /**
     * Get template path
     *
     * @param string $template Template name
     * @param string $template_type Template type (default, modern, etc.)
     * @return string Template file path
     */
    public static function get_template_path($template, $template_type = 'default') {
        $template_file = $template_type . '/' . $template . '.php';
        $template_path = GMRW_PLUGIN_DIR . 'templates/' . $template_file;
        
        // Check if template exists
        if (file_exists($template_path)) {
            return $template_path;
        }
        
        // Fallback to default template
        $default_path = GMRW_PLUGIN_DIR . 'templates/default/' . $template . '.php';
        
        if (file_exists($default_path)) {
            return $default_path;
        }
        
        return false;
    }
    
    /**
     * Load a template
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @param string $template_type Template type
     * @return string Template HTML
     */
    public static function load_template($template, $args = array(), $template_type = 'default') {
        $template_path = self::get_template_path($template, $template_type);
        
        if (!$template_path) {
            return '';
        }
        
        // Extract arguments to make them available in template
        if (!empty($args)) {
            extract($args);
        }
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Get CSS class names
     *
     * @param array $classes Base classes
     * @param array $additional_classes Additional classes
     * @return string CSS class string
     */
    public static function get_css_classes($classes = array(), $additional_classes = array()) {
        $all_classes = array_merge($classes, $additional_classes);
        $all_classes = array_filter($all_classes); // Remove empty values
        $all_classes = array_unique($all_classes); // Remove duplicates
        
        return implode(' ', $all_classes);
    }
    
    /**
     * Get inline styles
     *
     * @param array $styles Style properties
     * @return string Inline style string
     */
    public static function get_inline_styles($styles = array()) {
        if (empty($styles)) {
            return '';
        }
        
        $style_strings = array();
        
        foreach ($styles as $property => $value) {
            if (!empty($value)) {
                $style_strings[] = $property . ': ' . $value;
            }
        }
        
        return implode('; ', $style_strings);
    }
    
    /**
     * Get responsive breakpoints
     *
     * @return array Responsive breakpoints
     */
    public static function get_responsive_breakpoints() {
        return array(
            'mobile' => 480,
            'tablet' => 768,
            'desktop' => 1024,
            'large' => 1200
        );
    }
    
    /**
     * Check if current request is AJAX
     *
     * @return bool True if AJAX request, false otherwise
     */
    public static function is_ajax_request() {
        return wp_doing_ajax() || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
    
    /**
     * Check if current request is REST API
     *
     * @return bool True if REST API request, false otherwise
     */
    public static function is_rest_request() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
    
    /**
     * Get current page URL
     *
     * @return string Current page URL
     */
    public static function get_current_url() {
        global $wp;
        return home_url($wp->request);
    }
    
    /**
     * Get user agent string
     *
     * @return string User agent string
     */
    public static function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    
    /**
     * Check if user agent is a bot
     *
     * @return bool True if bot, false otherwise
     */
    public static function is_bot() {
        $user_agent = self::get_user_agent();
        $bot_patterns = array(
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot'
        );
        
        foreach ($bot_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get memory usage
     *
     * @return array Memory usage information
     */
    public static function get_memory_usage() {
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        return array(
            'limit' => $memory_limit,
            'usage' => $memory_usage,
            'usage_formatted' => size_format($memory_usage),
            'peak' => $memory_peak,
            'peak_formatted' => size_format($memory_peak),
            'percentage' => function_exists('memory_get_usage') ? round(($memory_usage / wp_convert_hr_to_bytes($memory_limit)) * 100, 2) : 0
        );
    }
    
    /**
     * Get execution time
     *
     * @return float Execution time in seconds
     */
    public static function get_execution_time() {
        if (function_exists('microtime')) {
            return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        }
        
        return 0;
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted bytes
     */
    public static function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Generate a unique ID
     *
     * @param string $prefix ID prefix
     * @return string Unique ID
     */
    public static function generate_id($prefix = 'gmrw') {
        return $prefix . '_' . uniqid() . '_' . wp_rand(1000, 9999);
    }
    
    /**
     * Check if debug mode is enabled
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public static function is_debug_mode() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
    
    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public static function get_plugin_version() {
        return GMRW_VERSION;
    }
    
    /**
     * Check if plugin is active
     *
     * @return bool True if plugin is active, false otherwise
     */
    public static function is_plugin_active() {
        return is_plugin_active(GMRW_PLUGIN_BASENAME);
    }
}
