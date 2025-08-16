<?php
/**
 * Google Maps Reviews Templates Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Template System
 *
 * Handles customizable review display templates with theme support
 */
class Google_Maps_Reviews_Templates {

    /**
     * Available template themes
     */
    const THEME_DEFAULT = 'default';
    const THEME_MODERN = 'modern';
    const THEME_MINIMAL = 'minimal';
    const THEME_CARD = 'card';
    const THEME_COMPACT = 'compact';

    /**
     * Template variables
     */
    private static $template_vars = array();

    /**
     * Initialize template system
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_template_hooks'));
        add_filter('gmrw_template_path', array(__CLASS__, 'get_template_path'), 10, 2);
        add_filter('gmrw_template_content', array(__CLASS__, 'process_template_content'), 10, 2);
    }

    /**
     * Register template hooks
     */
    public static function register_template_hooks() {
        // Allow theme overrides
        add_filter('gmrw_template_override', array(__CLASS__, 'check_theme_override'), 10, 2);
        
        // Register template variables
        add_action('gmrw_before_template_render', array(__CLASS__, 'setup_template_variables'));
        add_action('gmrw_after_template_render', array(__CLASS__, 'cleanup_template_variables'));
    }

    /**
     * Get available themes
     *
     * @return array
     */
    public static function get_available_themes() {
        return array(
            self::THEME_DEFAULT => array(
                'name' => __('Default', GMRW_TEXT_DOMAIN),
                'description' => __('Clean and professional default theme', GMRW_TEXT_DOMAIN),
                'preview' => 'default-preview.png'
            ),
            self::THEME_MODERN => array(
                'name' => __('Modern', GMRW_TEXT_DOMAIN),
                'description' => __('Contemporary design with rounded corners', GMRW_TEXT_DOMAIN),
                'preview' => 'modern-preview.png'
            ),
            self::THEME_MINIMAL => array(
                'name' => __('Minimal', GMRW_TEXT_DOMAIN),
                'description' => __('Simple and clean minimal design', GMRW_TEXT_DOMAIN),
                'preview' => 'minimal-preview.png'
            ),
            self::THEME_CARD => array(
                'name' => __('Card', GMRW_TEXT_DOMAIN),
                'description' => __('Card-based layout with shadows', GMRW_TEXT_DOMAIN),
                'preview' => 'card-preview.png'
            ),
            self::THEME_COMPACT => array(
                'name' => __('Compact', GMRW_TEXT_DOMAIN),
                'description' => __('Space-efficient compact layout', GMRW_TEXT_DOMAIN),
                'preview' => 'compact-preview.png'
            )
        );
    }

    /**
     * Get template path
     *
     * @param string $template_name
     * @param string $theme
     * @return string
     */
    public static function get_template_path($template_name, $theme = 'default') {
        // Check for theme override first
        $theme_override = apply_filters('gmrw_template_override', false, array(
            'template' => $template_name,
            'theme' => $theme
        ));

        if ($theme_override) {
            return $theme_override;
        }

        // Check for custom template in theme directory
        $custom_template = locate_template(array(
            'google-maps-reviews/' . $theme . '/' . $template_name . '.php',
            'google-maps-reviews/' . $template_name . '.php'
        ));

        if ($custom_template) {
            return $custom_template;
        }

        // Return default plugin template
        $template_file = GMRW_PLUGIN_DIR . 'templates/' . $theme . '/' . $template_name . '.php';
        
        if (!file_exists($template_file)) {
            // Fallback to default theme
            $template_file = GMRW_PLUGIN_DIR . 'templates/' . self::THEME_DEFAULT . '/' . $template_name . '.php';
        }

        return $template_file;
    }

    /**
     * Check for theme override
     *
     * @param bool $override
     * @param array $args
     * @return string|false
     */
    public static function check_theme_override($override, $args) {
        $template_name = $args['template'];
        $theme = $args['theme'];

        // Check if theme has custom template
        $theme_template = get_template_directory() . '/google-maps-reviews/' . $theme . '/' . $template_name . '.php';
        
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        return false;
    }

    /**
     * Render template
     *
     * @param string $template_name
     * @param array $data
     * @param string $theme
     * @return string
     */
    public static function render($template_name, $data = array(), $theme = 'default') {
        // Setup template variables
        self::setup_template_variables($data);

        // Get template path
        $template_path = self::get_template_path($template_name, $theme);

        // Start output buffering
        ob_start();

        // Include template file
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback template
            self::render_fallback_template($template_name, $data);
        }

        // Get rendered content
        $content = ob_get_clean();

        // Process template content
        $content = apply_filters('gmrw_template_content', $content, array(
            'template' => $template_name,
            'theme' => $theme,
            'data' => $data
        ));

        // Cleanup template variables
        self::cleanup_template_variables();

        return $content;
    }

    /**
     * Setup template variables
     *
     * @param array $data
     */
    public static function setup_template_variables($data = array()) {
        self::$template_vars = array_merge(array(
            'review' => null,
            'business' => null,
            'options' => array(),
            'index' => 0,
            'total' => 0
        ), $data);

        // Make variables available in template scope
        extract(self::$template_vars);
    }

    /**
     * Cleanup template variables
     */
    public static function cleanup_template_variables() {
        self::$template_vars = array();
    }

    /**
     * Get template variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get_var($key, $default = null) {
        return isset(self::$template_vars[$key]) ? self::$template_vars[$key] : $default;
    }

    /**
     * Process template content
     *
     * @param string $content
     * @param array $args
     * @return string
     */
    public static function process_template_content($content, $args) {
        // Replace template variables
        $content = self::replace_template_variables($content);

        // Process shortcodes
        $content = do_shortcode($content);

        // Apply content filters
        $content = apply_filters('the_content', $content);

        return $content;
    }

    /**
     * Replace template variables
     *
     * @param string $content
     * @return string
     */
    public static function replace_template_variables($content) {
        $variables = array(
            '{{review_author}}' => self::get_var('review.author_name', ''),
            '{{review_content}}' => self::get_var('review.content', ''),
            '{{review_rating}}' => self::get_var('review.rating', 0),
            '{{review_date}}' => self::get_var('review.date', ''),
            '{{review_helpful_votes}}' => self::get_var('review.helpful_votes', 0),
            '{{review_author_image}}' => self::get_var('review.author_image', ''),
            '{{business_name}}' => self::get_var('business.name', ''),
            '{{business_rating}}' => self::get_var('business.rating', 0),
            '{{business_total_reviews}}' => self::get_var('business.total_reviews', 0),
            '{{review_index}}' => self::get_var('index', 0),
            '{{total_reviews}}' => self::get_var('total', 0),
            '{{theme_url}}' => GMRW_PLUGIN_URL . 'templates/',
            '{{plugin_url}}' => GMRW_PLUGIN_URL
        );

        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    /**
     * Render fallback template
     *
     * @param string $template_name
     * @param array $data
     */
    public static function render_fallback_template($template_name, $data) {
        $review = $data['review'] ?? null;
        $business = $data['business'] ?? null;
        $options = $data['options'] ?? array();

        if (!$review) {
            echo '<p>' . __('No review data available', GMRW_TEXT_DOMAIN) . '</p>';
            return;
        }

        echo '<div class="gmrw-review gmrw-fallback-template">';
        echo '<div class="gmrw-review-header">';
        echo '<div class="gmrw-author-info">';
        if (!empty($review['author_image'])) {
            echo '<img src="' . esc_url($review['author_image']) . '" alt="' . esc_attr($review['author_name']) . '" class="gmrw-author-image">';
        }
        echo '<span class="gmrw-author-name">' . esc_html($review['author_name']) . '</span>';
        echo '</div>';
        echo '<div class="gmrw-rating">';
        echo self::render_stars($review['rating']);
        echo '</div>';
        echo '</div>';
        echo '<div class="gmrw-review-content">';
        echo '<p>' . esc_html($review['content']) . '</p>';
        echo '</div>';
        echo '<div class="gmrw-review-footer">';
        echo '<span class="gmrw-review-date">' . esc_html($review['date']) . '</span>';
        if (!empty($review['helpful_votes'])) {
            echo '<span class="gmrw-helpful-votes">' . sprintf(__('%d helpful votes', GMRW_TEXT_DOMAIN), $review['helpful_votes']) . '</span>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render star rating
     *
     * @param int $rating
     * @return string
     */
    public static function render_stars($rating) {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '<span class="gmrw-star gmrw-star-filled">★</span>';
            } else {
                $stars .= '<span class="gmrw-star gmrw-star-empty">☆</span>';
            }
        }
        return '<div class="gmrw-stars" role="img" aria-label="' . $rating . ' out of 5 stars">' . $stars . '</div>';
    }

    /**
     * Get template CSS
     *
     * @param string $theme
     * @return string
     */
    public static function get_template_css($theme = 'default') {
        $css_file = GMRW_PLUGIN_DIR . 'templates/' . $theme . '/style.css';
        
        if (file_exists($css_file)) {
            return file_get_contents($css_file);
        }

        return '';
    }

    /**
     * Create custom template
     *
     * @param string $template_name
     * @param string $content
     * @param string $theme
     * @return bool
     */
    public static function create_custom_template($template_name, $content, $theme = 'default') {
        $template_dir = GMRW_PLUGIN_DIR . 'templates/' . $theme;
        
        if (!is_dir($template_dir)) {
            wp_mkdir_p($template_dir);
        }

        $template_file = $template_dir . '/' . $template_name . '.php';
        
        $template_content = "<?php\n";
        $template_content .= "/**\n";
        $template_content .= " * Custom Template: {$template_name}\n";
        $template_content .= " * Theme: {$theme}\n";
        $template_content .= " */\n\n";
        $template_content .= "if (!defined('ABSPATH')) {\n";
        $template_content .= "    exit;\n";
        $template_content .= "}\n\n";
        $template_content .= $content;

        return file_put_contents($template_file, $template_content) !== false;
    }

    /**
     * Get template preview
     *
     * @param string $theme
     * @return string
     */
    public static function get_template_preview($theme) {
        $preview_file = GMRW_PLUGIN_URL . 'templates/' . $theme . '/preview.png';
        $fallback_preview = GMRW_PLUGIN_URL . 'assets/images/template-previews/' . $theme . '.png';
        
        return file_exists(str_replace(GMRW_PLUGIN_URL, GMRW_PLUGIN_DIR, $preview_file)) ? $preview_file : $fallback_preview;
    }

    /**
     * Validate template
     *
     * @param string $content
     * @return array
     */
    public static function validate_template($content) {
        $errors = array();
        $warnings = array();

        // Check for required variables
        $required_vars = array('{{review_author}}', '{{review_content}}', '{{review_rating}}');
        foreach ($required_vars as $var) {
            if (strpos($content, $var) === false) {
                $warnings[] = sprintf(__('Template does not include required variable: %s', GMRW_TEXT_DOMAIN), $var);
            }
        }

        // Check for potential security issues
        if (strpos($content, '<?php') !== false) {
            $errors[] = __('Template contains PHP code which is not allowed for security reasons', GMRW_TEXT_DOMAIN);
        }

        // Check for proper escaping
        if (strpos($content, 'esc_html') === false && strpos($content, 'esc_attr') === false) {
            $warnings[] = __('Template may not properly escape output. Consider using esc_html() and esc_attr()', GMRW_TEXT_DOMAIN);
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    /**
     * Get template documentation
     *
     * @return array
     */
    public static function get_template_documentation() {
        return array(
            'variables' => array(
                '{{review_author}}' => __('Reviewer name', GMRW_TEXT_DOMAIN),
                '{{review_content}}' => __('Review text content', GMRW_TEXT_DOMAIN),
                '{{review_rating}}' => __('Review rating (1-5)', GMRW_TEXT_DOMAIN),
                '{{review_date}}' => __('Review date', GMRW_TEXT_DOMAIN),
                '{{review_helpful_votes}}' => __('Number of helpful votes', GMRW_TEXT_DOMAIN),
                '{{review_author_image}}' => __('Reviewer profile image URL', GMRW_TEXT_DOMAIN),
                '{{business_name}}' => __('Business name', GMRW_TEXT_DOMAIN),
                '{{business_rating}}' => __('Business average rating', GMRW_TEXT_DOMAIN),
                '{{business_total_reviews}}' => __('Total number of business reviews', GMRW_TEXT_DOMAIN),
                '{{review_index}}' => __('Current review index (0-based)', GMRW_TEXT_DOMAIN),
                '{{total_reviews}}' => __('Total number of reviews being displayed', GMRW_TEXT_DOMAIN),
                '{{theme_url}}' => __('URL to theme assets', GMRW_TEXT_DOMAIN),
                '{{plugin_url}}' => __('URL to plugin assets', GMRW_TEXT_DOMAIN)
            ),
            'functions' => array(
                'self::render_stars($rating)' => __('Render star rating HTML', GMRW_TEXT_DOMAIN),
                'self::get_var($key, $default)' => __('Get template variable value', GMRW_TEXT_DOMAIN),
                'esc_html($text)' => __('Escape HTML output', GMRW_TEXT_DOMAIN),
                'esc_attr($text)' => __('Escape HTML attribute', GMRW_TEXT_DOMAIN),
                'esc_url($url)' => __('Escape URL', GMRW_TEXT_DOMAIN)
            ),
            'examples' => array(
                'basic' => "<?php\n// Basic review template\n?>\n<div class=\"review\">\n    <h3><?php echo esc_html(self::get_var('review_author')); ?></h3>\n    <div class=\"rating\"><?php echo self::render_stars(self::get_var('review_rating')); ?></div>\n    <p><?php echo esc_html(self::get_var('review_content')); ?></p>\n    <small><?php echo esc_html(self::get_var('review_date')); ?></small>\n</div>",
                'card' => "<?php\n// Card-style review template\n?>\n<div class=\"review-card\">\n    <div class=\"card-header\">\n        <img src=\"<?php echo esc_url(self::get_var('review_author_image')); ?>\" alt=\"<?php echo esc_attr(self::get_var('review_author')); ?>\">\n        <div class=\"author-info\">\n            <h4><?php echo esc_html(self::get_var('review_author')); ?></h4>\n            <div class=\"stars\"><?php echo self::render_stars(self::get_var('review_rating')); ?></div>\n        </div>\n    </div>\n    <div class=\"card-body\">\n        <p><?php echo esc_html(self::get_var('review_content')); ?></p>\n    </div>\n    <div class=\"card-footer\">\n        <span class=\"date\"><?php echo esc_html(self::get_var('review_date')); ?></span>\n    </div>\n</div>"
            )
        );
    }
}

// Initialize template system
Google_Maps_Reviews_Templates::init();
