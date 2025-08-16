<?php
/**
 * Minification Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles CSS and JavaScript minification
 */
class Google_Maps_Reviews_Minifier {
    
    /**
     * Minify CSS content
     *
     * @param string $css CSS content to minify
     * @return string Minified CSS
     */
    public static function minify_css($css) {
        if (empty($css)) {
            return '';
        }
        
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/:\s*/', ':', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        
        // Remove leading/trailing whitespace
        $css = trim($css);
        
        return $css;
    }
    
    /**
     * Minify JavaScript content
     *
     * @param string $js JavaScript content to minify
     * @return string Minified JavaScript
     */
    public static function minify_js($js) {
        if (empty($js)) {
            return '';
        }
        
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/;\s*/', ';', $js);
        $js = preg_replace('/{\s*/', '{', $js);
        $js = preg_replace('/\s*}/', '}', $js);
        $js = preg_replace('/\(\s*/', '(', $js);
        $js = preg_replace('/\s*\)/', ')', $js);
        $js = preg_replace('/\[\s*/', '[', $js);
        $js = preg_replace('/\s*\]/', ']', $js);
        $js = preg_replace('/,\s*/', ',', $js);
        $js = preg_replace('/:\s*/', ':', $js);
        $js = preg_replace('/;\s*}/', '}', $js);
        
        // Remove leading/trailing whitespace
        $js = trim($js);
        
        return $js;
    }
    
    /**
     * Minify a CSS file
     *
     * @param string $file_path Path to CSS file
     * @param string $output_path Output path for minified file (optional)
     * @return string|false Minified content or false on failure
     */
    public static function minify_css_file($file_path, $output_path = null) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $css = file_get_contents($file_path);
        if ($css === false) {
            return false;
        }
        
        $minified = self::minify_css($css);
        
        if ($output_path) {
            $result = file_put_contents($output_path, $minified);
            return $result !== false ? $minified : false;
        }
        
        return $minified;
    }
    
    /**
     * Minify a JavaScript file
     *
     * @param string $file_path Path to JavaScript file
     * @param string $output_path Output path for minified file (optional)
     * @return string|false Minified content or false on failure
     */
    public static function minify_js_file($file_path, $output_path = null) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $js = file_get_contents($file_path);
        if ($js === false) {
            return false;
        }
        
        $minified = self::minify_js($js);
        
        if ($output_path) {
            $result = file_put_contents($output_path, $minified);
            return $result !== false ? $minified : false;
        }
        
        return $minified;
    }
    
    /**
     * Minify all plugin assets
     *
     * @return array Results of minification
     */
    public static function minify_all_assets() {
        $results = array(
            'css' => array(),
            'js' => array(),
            'errors' => array()
        );
        
        // CSS files to minify
        $css_files = array(
            'assets/css/google-maps-reviews.css',
            'assets/css/layouts/list.css',
            'assets/css/layouts/cards.css',
            'assets/css/layouts/carousel.css',
            'assets/css/layouts/grid.css',
            'admin/css/admin.css'
        );
        
        // JavaScript files to minify
        $js_files = array(
            'assets/js/google-maps-reviews.js',
            'assets/js/utils.js',
            'assets/js/carousel.js',
            'assets/js/filters.js',
            'assets/js/pagination.js',
            'admin/js/admin.js'
        );
        
        // Minify CSS files
        foreach ($css_files as $css_file) {
            $file_path = GMRW_PLUGIN_DIR . $css_file;
            $output_path = GMRW_PLUGIN_DIR . str_replace('.css', '.min.css', $css_file);
            
            if (file_exists($file_path)) {
                $result = self::minify_css_file($file_path, $output_path);
                if ($result !== false) {
                    $results['css'][$css_file] = array(
                        'original_size' => filesize($file_path),
                        'minified_size' => filesize($output_path),
                        'savings' => filesize($file_path) - filesize($output_path),
                        'savings_percent' => round(((filesize($file_path) - filesize($output_path)) / filesize($file_path)) * 100, 2)
                    );
                } else {
                    $results['errors'][] = 'Failed to minify CSS file: ' . $css_file;
                }
            }
        }
        
        // Minify JavaScript files
        foreach ($js_files as $js_file) {
            $file_path = GMRW_PLUGIN_DIR . $js_file;
            $output_path = GMRW_PLUGIN_DIR . str_replace('.js', '.min.js', $js_file);
            
            if (file_exists($file_path)) {
                $result = self::minify_js_file($file_path, $output_path);
                if ($result !== false) {
                    $results['js'][$js_file] = array(
                        'original_size' => filesize($file_path),
                        'minified_size' => filesize($output_path),
                        'savings' => filesize($file_path) - filesize($output_path),
                        'savings_percent' => round(((filesize($file_path) - filesize($output_path)) / filesize($file_path)) * 100, 2)
                    );
                } else {
                    $results['errors'][] = 'Failed to minify JavaScript file: ' . $js_file;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get minified asset URL
     *
     * @param string $asset_path Original asset path
     * @param bool $use_minified Whether to use minified version
     * @return string Asset URL
     */
    public static function get_asset_url($asset_path, $use_minified = true) {
        if (!$use_minified) {
            return GMRW_PLUGIN_URL . $asset_path;
        }
        
        // Check if minified version exists
        $minified_path = '';
        if (strpos($asset_path, '.css') !== false) {
            $minified_path = str_replace('.css', '.min.css', $asset_path);
        } elseif (strpos($asset_path, '.js') !== false) {
            $minified_path = str_replace('.js', '.min.js', $asset_path);
        }
        
        if ($minified_path && file_exists(GMRW_PLUGIN_DIR . $minified_path)) {
            return GMRW_PLUGIN_URL . $minified_path;
        }
        
        return GMRW_PLUGIN_URL . $asset_path;
    }
    
    /**
     * Check if minified assets exist
     *
     * @return bool True if minified assets exist, false otherwise
     */
    public static function minified_assets_exist() {
        $minified_files = array(
            'assets/css/google-maps-reviews.min.css',
            'assets/js/google-maps-reviews.min.js'
        );
        
        foreach ($minified_files as $file) {
            if (!file_exists(GMRW_PLUGIN_DIR . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Clean up minified assets
     *
     * @return array Results of cleanup
     */
    public static function cleanup_minified_assets() {
        $results = array(
            'deleted' => array(),
            'errors' => array()
        );
        
        $minified_files = array(
            'assets/css/google-maps-reviews.min.css',
            'assets/css/layouts/list.min.css',
            'assets/css/layouts/cards.min.css',
            'assets/css/layouts/carousel.min.css',
            'assets/css/layouts/grid.min.css',
            'admin/css/admin.min.css',
            'assets/js/google-maps-reviews.min.js',
            'assets/js/utils.min.js',
            'assets/js/carousel.min.js',
            'assets/js/filters.min.js',
            'assets/js/pagination.min.js',
            'admin/js/admin.min.js'
        );
        
        foreach ($minified_files as $file) {
            $file_path = GMRW_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $results['deleted'][] = $file;
                } else {
                    $results['errors'][] = 'Failed to delete: ' . $file;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get asset optimization statistics
     *
     * @return array Optimization statistics
     */
    public static function get_optimization_stats() {
        $stats = array(
            'total_original_size' => 0,
            'total_minified_size' => 0,
            'total_savings' => 0,
            'total_savings_percent' => 0,
            'files_optimized' => 0
        );
        
        $minification_results = self::minify_all_assets();
        
        foreach ($minification_results['css'] as $file => $file_stats) {
            $stats['total_original_size'] += $file_stats['original_size'];
            $stats['total_minified_size'] += $file_stats['minified_size'];
            $stats['total_savings'] += $file_stats['savings'];
            $stats['files_optimized']++;
        }
        
        foreach ($minification_results['js'] as $file => $file_stats) {
            $stats['total_original_size'] += $file_stats['original_size'];
            $stats['total_minified_size'] += $file_stats['minified_size'];
            $stats['total_savings'] += $file_stats['savings'];
            $stats['files_optimized']++;
        }
        
        if ($stats['total_original_size'] > 0) {
            $stats['total_savings_percent'] = round(($stats['total_savings'] / $stats['total_original_size']) * 100, 2);
        }
        
        $stats['total_original_size_formatted'] = size_format($stats['total_original_size']);
        $stats['total_minified_size_formatted'] = size_format($stats['total_minified_size']);
        $stats['total_savings_formatted'] = size_format($stats['total_savings']);
        
        return $stats;
    }
}
