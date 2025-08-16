<?php
/**
 * Google Maps Reviews Sanitizer Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Sanitizer
 *
 * Handles all sanitization and escaping for widget and shortcode output
 */
class Google_Maps_Reviews_Sanitizer {

    /**
     * Sanitize widget instance data
     *
     * @param array $instance Widget instance data
     * @return array Sanitized widget instance data
     */
    public static function sanitize_widget_instance($instance) {
        $sanitized = array();

        // Sanitize text fields
        $sanitized['title'] = sanitize_text_field($instance['title'] ?? '');
        $sanitized['business_url'] = esc_url_raw($instance['business_url'] ?? '');

        // Sanitize numeric fields with validation
        $sanitized['max_reviews'] = self::sanitize_int_range($instance['max_reviews'] ?? 5, 1, 50);
        $sanitized['min_rating'] = self::sanitize_int_range($instance['min_rating'] ?? 0, 0, 5);
        $sanitized['cache_duration'] = self::sanitize_int_range($instance['cache_duration'] ?? 3600, 300, 86400);

        // Sanitize select fields
        $sanitized['layout'] = self::sanitize_select($instance['layout'] ?? 'list', array('list', 'cards', 'carousel', 'grid'));
        $sanitized['sort_by'] = self::sanitize_select($instance['sort_by'] ?? 'relevance', array('relevance', 'date', 'rating'));
        $sanitized['sort_order'] = self::sanitize_select($instance['sort_order'] ?? 'desc', array('asc', 'desc'));

        // Sanitize boolean fields
        $sanitized['show_rating'] = self::sanitize_boolean($instance['show_rating'] ?? true);
        $sanitized['show_date'] = self::sanitize_boolean($instance['show_date'] ?? true);
        $sanitized['show_author_image'] = self::sanitize_boolean($instance['show_author_image'] ?? true);
        $sanitized['show_helpful_votes'] = self::sanitize_boolean($instance['show_helpful_votes'] ?? false);
        $sanitized['show_owner_response'] = self::sanitize_boolean($instance['show_owner_response'] ?? false);
        $sanitized['show_pagination'] = self::sanitize_boolean($instance['show_pagination'] ?? false);
        $sanitized['show_review_count'] = self::sanitize_boolean($instance['show_review_count'] ?? true);

        // Sanitize numeric fields
        $sanitized['reviews_per_page'] = self::sanitize_int_range($instance['reviews_per_page'] ?? 5, 1, 20);

        return $sanitized;
    }

    /**
     * Sanitize shortcode attributes
     *
     * @param array $attributes Shortcode attributes
     * @return array Sanitized shortcode attributes
     */
    public static function sanitize_shortcode_attributes($attributes) {
        $sanitized = array();

        // Sanitize text fields
        $sanitized['business_url'] = esc_url_raw($attributes['business_url'] ?? '');
        $sanitized['container_class'] = sanitize_html_class($attributes['container_class'] ?? '');
        $sanitized['title'] = sanitize_text_field($attributes['title'] ?? '');

        // Sanitize numeric fields with validation
        $sanitized['max_reviews'] = self::sanitize_int_range($attributes['max_reviews'] ?? 5, 1, 50);
        $sanitized['min_rating'] = self::sanitize_int_range($attributes['min_rating'] ?? 0, 0, 5);
        $sanitized['cache_duration'] = self::sanitize_int_range($attributes['cache_duration'] ?? 3600, 300, 86400);

        // Sanitize select fields
        $sanitized['layout'] = self::sanitize_select($attributes['layout'] ?? 'list', array('list', 'cards', 'carousel', 'grid'));
        $sanitized['sort_by'] = self::sanitize_select($attributes['sort_by'] ?? 'relevance', array('relevance', 'date', 'rating'));
        $sanitized['sort_order'] = self::sanitize_select($attributes['sort_order'] ?? 'desc', array('asc', 'desc'));

        // Sanitize boolean fields
        $sanitized['show_rating'] = self::sanitize_boolean($attributes['show_rating'] ?? true);
        $sanitized['show_date'] = self::sanitize_boolean($attributes['show_date'] ?? true);
        $sanitized['show_author_image'] = self::sanitize_boolean($attributes['show_author_image'] ?? true);
        $sanitized['show_helpful_votes'] = self::sanitize_boolean($attributes['show_helpful_votes'] ?? false);
        $sanitized['show_owner_response'] = self::sanitize_boolean($attributes['show_owner_response'] ?? false);
        $sanitized['show_business_info'] = self::sanitize_boolean($attributes['show_business_info'] ?? true);
        $sanitized['show_pagination'] = self::sanitize_boolean($attributes['show_pagination'] ?? false);
        $sanitized['show_review_count'] = self::sanitize_boolean($attributes['show_review_count'] ?? true);

        // Sanitize numeric fields
        $sanitized['reviews_per_page'] = self::sanitize_int_range($attributes['reviews_per_page'] ?? 5, 1, 20);

        return $sanitized;
    }

    /**
     * Sanitize review data
     *
     * @param array $review Review data
     * @return array Sanitized review data
     */
    public static function sanitize_review_data($review) {
        $sanitized = array();

        // Sanitize text fields
        $sanitized['id'] = sanitize_text_field($review['id'] ?? '');
        $sanitized['author_name'] = sanitize_text_field($review['author_name'] ?? '');
        $sanitized['content'] = sanitize_textarea_field($review['content'] ?? '');
        $sanitized['date'] = sanitize_text_field($review['date'] ?? '');
        $sanitized['owner_response'] = sanitize_textarea_field($review['owner_response'] ?? '');
        $sanitized['language'] = sanitize_text_field($review['language'] ?? 'en');

        // Sanitize URL fields
        $sanitized['author_image'] = esc_url_raw($review['author_image'] ?? '');

        // Sanitize numeric fields
        $sanitized['rating'] = self::sanitize_int_range($review['rating'] ?? 0, 1, 5);
        $sanitized['helpful_votes'] = self::sanitize_int_range($review['helpful_votes'] ?? 0, 0, 999999);

        return $sanitized;
    }

    /**
     * Sanitize business info data
     *
     * @param array $business_info Business info data
     * @return array Sanitized business info data
     */
    public static function sanitize_business_info($business_info) {
        $sanitized = array();

        // Sanitize text fields
        $sanitized['name'] = sanitize_text_field($business_info['name'] ?? '');
        $sanitized['address'] = sanitize_text_field($business_info['address'] ?? '');
        $sanitized['phone'] = sanitize_text_field($business_info['phone'] ?? '');
        $sanitized['category'] = sanitize_text_field($business_info['category'] ?? '');

        // Sanitize URL fields
        $sanitized['website'] = esc_url_raw($business_info['website'] ?? '');

        // Sanitize numeric fields
        $sanitized['rating'] = self::sanitize_float_range($business_info['rating'] ?? 0, 0, 5);
        $sanitized['review_count'] = self::sanitize_int_range($business_info['review_count'] ?? 0, 0, 999999);

        // Sanitize array fields
        $sanitized['hours'] = is_array($business_info['hours'] ?? array()) ? $business_info['hours'] : array();

        return $sanitized;
    }

    /**
     * Escape HTML output for widget and shortcode
     *
     * @param string $html HTML content
     * @param array $allowed_tags Allowed HTML tags
     * @return string Escaped HTML content
     */
    public static function escape_html_output($html, $allowed_tags = array()) {
        if (empty($allowed_tags)) {
            // Default allowed tags for review content
            $allowed_tags = array(
                'p' => array(),
                'br' => array(),
                'strong' => array(),
                'em' => array(),
                'u' => array(),
                'span' => array('class' => array()),
                'div' => array('class' => array()),
            );
        }

        return wp_kses($html, $allowed_tags);
    }

    /**
     * Escape review content with allowed HTML
     *
     * @param string $content Review content
     * @return string Escaped review content
     */
    public static function escape_review_content($content) {
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'u' => array(),
            'span' => array('class' => array()),
        );

        return self::escape_html_output($content, $allowed_tags);
    }

    /**
     * Escape owner response with allowed HTML
     *
     * @param string $response Owner response
     * @return string Escaped owner response
     */
    public static function escape_owner_response($response) {
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'u' => array(),
            'span' => array('class' => array()),
        );

        return self::escape_html_output($response, $allowed_tags);
    }

    /**
     * Sanitize integer with range validation
     *
     * @param mixed $value Value to sanitize
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Sanitized integer
     */
    private static function sanitize_int_range($value, $min, $max) {
        $int_value = intval($value);
        return max($min, min($max, $int_value));
    }

    /**
     * Sanitize float with range validation
     *
     * @param mixed $value Value to sanitize
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return float Sanitized float
     */
    private static function sanitize_float_range($value, $min, $max) {
        $float_value = floatval($value);
        return max($min, min($max, $float_value));
    }

    /**
     * Sanitize select field
     *
     * @param mixed $value Value to sanitize
     * @param array $allowed_values Allowed values
     * @param string $default Default value
     * @return string Sanitized value
     */
    private static function sanitize_select($value, $allowed_values, $default = '') {
        $sanitized_value = sanitize_text_field($value);
        return in_array($sanitized_value, $allowed_values) ? $sanitized_value : $default;
    }

    /**
     * Sanitize boolean value
     *
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    private static function sanitize_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, array('true', '1', 'yes', 'on', 'enabled'));
        }

        return (bool) $value;
    }

    /**
     * Validate and sanitize CSS class names
     *
     * @param string $classes CSS class names
     * @return string Sanitized CSS class names
     */
    public static function sanitize_css_classes($classes) {
        if (empty($classes)) {
            return '';
        }

        $class_array = explode(' ', $classes);
        $sanitized_classes = array();

        foreach ($class_array as $class) {
            $sanitized_class = sanitize_html_class(trim($class));
            if (!empty($sanitized_class)) {
                $sanitized_classes[] = $sanitized_class;
            }
        }

        return implode(' ', $sanitized_classes);
    }

    /**
     * Sanitize HTML attributes
     *
     * @param array $attributes HTML attributes
     * @return array Sanitized HTML attributes
     */
    public static function sanitize_html_attributes($attributes) {
        $sanitized = array();

        foreach ($attributes as $key => $value) {
            $sanitized_key = sanitize_key($key);
            
            switch ($sanitized_key) {
                case 'class':
                    $sanitized[$sanitized_key] = self::sanitize_css_classes($value);
                    break;
                case 'id':
                    $sanitized[$sanitized_key] = sanitize_html_class($value);
                    break;
                case 'src':
                case 'href':
                    $sanitized[$sanitized_key] = esc_url_raw($value);
                    break;
                case 'alt':
                case 'title':
                    $sanitized[$sanitized_key] = sanitize_text_field($value);
                    break;
                default:
                    $sanitized[$sanitized_key] = esc_attr($value);
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Build HTML attributes string
     *
     * @param array $attributes HTML attributes
     * @return string HTML attributes string
     */
    public static function build_html_attributes($attributes) {
        $sanitized_attributes = self::sanitize_html_attributes($attributes);
        $html_attributes = array();

        foreach ($sanitized_attributes as $key => $value) {
            if (!empty($value)) {
                $html_attributes[] = $key . '="' . $value . '"';
            }
        }

        return implode(' ', $html_attributes);
    }

    /**
     * Sanitize widget form input
     *
     * @param array $input Form input data
     * @return array Sanitized form input
     */
    public static function sanitize_widget_form_input($input) {
        $sanitized = array();

        // Sanitize text inputs
        $sanitized['title'] = sanitize_text_field($input['title'] ?? '');
        $sanitized['business_url'] = esc_url_raw($input['business_url'] ?? '');

        // Sanitize numeric inputs
        $sanitized['max_reviews'] = self::sanitize_int_range($input['max_reviews'] ?? 5, 1, 50);
        $sanitized['min_rating'] = self::sanitize_int_range($input['min_rating'] ?? 0, 0, 5);
        $sanitized['cache_duration'] = self::sanitize_int_range($input['cache_duration'] ?? 3600, 300, 86400);

        // Sanitize select inputs
        $sanitized['layout'] = self::sanitize_select($input['layout'] ?? 'list', array('list', 'cards', 'carousel', 'grid'));
        $sanitized['sort_by'] = self::sanitize_select($input['sort_by'] ?? 'relevance', array('relevance', 'date', 'rating'));
        $sanitized['sort_order'] = self::sanitize_select($input['sort_order'] ?? 'desc', array('asc', 'desc'));

        // Sanitize checkbox inputs
        $sanitized['show_rating'] = isset($input['show_rating']);
        $sanitized['show_date'] = isset($input['show_date']);
        $sanitized['show_author_image'] = isset($input['show_author_image']);
        $sanitized['show_helpful_votes'] = isset($input['show_helpful_votes']);
        $sanitized['show_owner_response'] = isset($input['show_owner_response']);

        return $sanitized;
    }

    /**
     * Validate business URL
     *
     * @param string $url Business URL
     * @return bool True if valid, false otherwise
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
        if (!preg_match('/google\.com\/maps/', $url)) {
            return false;
        }

        return true;
    }

    /**
     * Get sanitization errors
     *
     * @param array $data Data to validate
     * @param string $type Type of data (widget, shortcode, review, business)
     * @return array Array of validation errors
     */
    public static function get_sanitization_errors($data, $type = 'widget') {
        $errors = array();

        switch ($type) {
            case 'widget':
                $errors = self::validate_widget_data($data);
                break;
            case 'shortcode':
                $errors = self::validate_shortcode_data($data);
                break;
            case 'review':
                $errors = self::validate_review_data($data);
                break;
            case 'business':
                $errors = self::validate_business_data($data);
                break;
        }

        return $errors;
    }

    /**
     * Validate widget data
     *
     * @param array $data Widget data
     * @return array Validation errors
     */
    private static function validate_widget_data($data) {
        $errors = array();

        if (!empty($data['business_url']) && !self::validate_business_url($data['business_url'])) {
            $errors[] = __('Invalid Google Maps business URL.', GMRW_TEXT_DOMAIN);
        }

        if (isset($data['max_reviews']) && ($data['max_reviews'] < 1 || $data['max_reviews'] > 50)) {
            $errors[] = __('Maximum reviews must be between 1 and 50.', GMRW_TEXT_DOMAIN);
        }

        if (isset($data['min_rating']) && ($data['min_rating'] < 0 || $data['min_rating'] > 5)) {
            $errors[] = __('Minimum rating must be between 0 and 5.', GMRW_TEXT_DOMAIN);
        }

        return $errors;
    }

    /**
     * Validate shortcode data
     *
     * @param array $data Shortcode data
     * @return array Validation errors
     */
    private static function validate_shortcode_data($data) {
        $errors = self::validate_widget_data($data);

        if (!empty($data['cache_duration']) && ($data['cache_duration'] < 300 || $data['cache_duration'] > 86400)) {
            $errors[] = __('Cache duration must be between 300 and 86400 seconds.', GMRW_TEXT_DOMAIN);
        }

        return $errors;
    }

    /**
     * Validate review data
     *
     * @param array $data Review data
     * @return array Validation errors
     */
    private static function validate_review_data($data) {
        $errors = array();

        if (empty($data['author_name'])) {
            $errors[] = __('Review author name is required.', GMRW_TEXT_DOMAIN);
        }

        if (empty($data['content'])) {
            $errors[] = __('Review content is required.', GMRW_TEXT_DOMAIN);
        }

        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            $errors[] = __('Review rating must be between 1 and 5.', GMRW_TEXT_DOMAIN);
        }

        return $errors;
    }

    /**
     * Validate business data
     *
     * @param array $data Business data
     * @return array Validation errors
     */
    private static function validate_business_data($data) {
        $errors = array();

        if (empty($data['name'])) {
            $errors[] = __('Business name is required.', GMRW_TEXT_DOMAIN);
        }

        if (isset($data['rating']) && ($data['rating'] < 0 || $data['rating'] > 5)) {
            $errors[] = __('Business rating must be between 0 and 5.', GMRW_TEXT_DOMAIN);
        }

        return $errors;
    }
}
