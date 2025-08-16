<?php
/**
 * Google Maps Reviews Shortcode Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Shortcode Handler
 */
class Google_Maps_Reviews_Shortcode {

    const SHORTCODE_TAG = 'google_maps_reviews';

    public function __construct() {
        add_shortcode(self::SHORTCODE_TAG, array($this, 'render_shortcode'));
        add_shortcode('gmrw', array($this, 'render_shortcode')); // Alias
        add_shortcode('gmrw_reviews', array($this, 'render_shortcode')); // Alternative alias
        
        // Register shortcode with WordPress
        add_action('init', array($this, 'register_shortcode_attributes'));
        
        // Add shortcode button to editor if enabled
        if (Google_Maps_Reviews_Config::get('enable_shortcode_button', true)) {
            add_action('admin_footer', array($this, 'add_shortcode_button'));
        }
    }

    public function render_shortcode($atts, $content = '') {
        $attributes = $this->parse_attributes($atts);
        
        // Additional sanitization using the sanitizer class
        $sanitized_attributes = Google_Maps_Reviews_Sanitizer::sanitize_shortcode_attributes($attributes);
        
        if (empty($sanitized_attributes['business_url'])) {
            return $this->get_error_message(__('Business URL is required.', GMRW_TEXT_DOMAIN));
        }

        $url_errors = Google_Maps_Reviews_Config::get_url_validation_errors($sanitized_attributes['business_url']);
        if (!empty($url_errors)) {
            return $this->get_error_message(__('Invalid Google Maps business URL.', GMRW_TEXT_DOMAIN));
        }

        $scraper = new Google_Maps_Reviews_Scraper();
        $options = array(
            'max_reviews' => $sanitized_attributes['max_reviews'],
            'sort_by' => $sanitized_attributes['sort_by'],
            'sort_order' => $sanitized_attributes['sort_order'],
            'cache_duration' => $sanitized_attributes['cache_duration'],
        );

        $reviews_result = $scraper->get_reviews($sanitized_attributes['business_url'], $options);

        if (is_wp_error($reviews_result)) {
            return $this->get_error_message(__('Unable to fetch reviews at this time.', GMRW_TEXT_DOMAIN));
        }

        $reviews = $reviews_result['reviews'] ?? array();
        $business_info = $reviews_result['business_info'] ?? array();

        if ($sanitized_attributes['min_rating'] > 0) {
            $reviews = array_filter($reviews, function($review) use ($sanitized_attributes) {
                return isset($review['rating']) && $review['rating'] >= $sanitized_attributes['min_rating'];
            });
        }

        ob_start();

        if (!empty($business_info) && $sanitized_attributes['show_business_info']) {
            $this->display_business_info($business_info);
        }

        if (!empty($reviews)) {
            $this->display_reviews($reviews, $sanitized_attributes);
        } else {
            echo '<p class="gmrw-no-reviews">' . esc_html__('No reviews found for this business.', GMRW_TEXT_DOMAIN) . '</p>';
        }

        $output = ob_get_clean();

        if (!empty($sanitized_attributes['container_class'])) {
            $output = '<div class="' . esc_attr($sanitized_attributes['container_class']) . '">' . $output . '</div>';
        }

        return $output;
    }

    private function parse_attributes($atts) {
        $defaults = array(
            'business_url' => '',
            'max_reviews' => 5,
            'layout' => 'list',
            'show_rating' => 'true',
            'show_date' => 'true',
            'show_author_image' => 'true',
            'show_helpful_votes' => 'false',
            'show_owner_response' => 'false',
            'show_business_info' => 'true',
            'sort_by' => 'relevance',
            'sort_order' => 'desc',
            'min_rating' => 0,
            'cache_duration' => 3600,
            'container_class' => '',
            'title' => '',
        );

        $attributes = shortcode_atts($defaults, $atts, self::SHORTCODE_TAG);

        // Convert boolean attributes
        $boolean_attrs = array('show_rating', 'show_date', 'show_author_image', 'show_helpful_votes', 'show_owner_response', 'show_business_info');
        foreach ($boolean_attrs as $attr) {
            $attributes[$attr] = $this->string_to_bool($attributes[$attr]);
        }

        // Sanitize and validate numeric attributes
        $attributes['max_reviews'] = max(1, min(50, intval($attributes['max_reviews'])));
        $attributes['min_rating'] = max(0, min(5, intval($attributes['min_rating'])));
        $attributes['cache_duration'] = max(300, min(86400, intval($attributes['cache_duration'])));

        // Validate layout
        $layouts = Google_Maps_Reviews_Config::get_available_layouts();
        if (!array_key_exists($attributes['layout'], $layouts)) {
            $attributes['layout'] = 'list';
        }

        // Validate sort options
        $sort_options = Google_Maps_Reviews_Config::get_sort_options();
        if (!array_key_exists($attributes['sort_by'], $sort_options)) {
            $attributes['sort_by'] = 'relevance';
        }

        $sort_orders = Google_Maps_Reviews_Config::get_sort_orders();
        if (!array_key_exists($attributes['sort_order'], $sort_orders)) {
            $attributes['sort_order'] = 'desc';
        }

        // Sanitize text attributes
        $attributes['business_url'] = esc_url_raw($attributes['business_url']);
        $attributes['container_class'] = sanitize_html_class($attributes['container_class']);
        $attributes['title'] = sanitize_text_field($attributes['title']);

        // Enhanced validation
        $validation_errors = $this->validate_parameters($attributes);
        if (!empty($validation_errors)) {
            // Log validation errors for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Google Maps Reviews Shortcode Validation Errors: ' . implode(', ', $validation_errors));
            }
        }

        return $attributes;
    }

    private function string_to_bool($value) {
        if (is_bool($value)) {
            return $value;
        }
        $value = strtolower(trim($value));
        return in_array($value, array('true', '1', 'yes', 'on', 'enabled'));
    }

    private function display_business_info($business_info) {
        if (empty($business_info)) {
            return;
        }

        // Sanitize business info data
        $sanitized_business_info = Google_Maps_Reviews_Sanitizer::sanitize_business_info($business_info);

        echo '<div class="gmrw-business-info">';
        
        if (!empty($sanitized_business_info['name'])) {
            echo '<h3 class="gmrw-business-name">' . esc_html($sanitized_business_info['name']) . '</h3>';
        }

        if (!empty($sanitized_business_info['rating']) || !empty($sanitized_business_info['review_count'])) {
            echo '<div class="gmrw-business-rating">';
            
            if (!empty($sanitized_business_info['rating'])) {
                echo '<span class="gmrw-rating">';
                echo '<span class="gmrw-stars">';
                for ($i = 1; $i <= 5; $i++) {
                    $class = $i <= $sanitized_business_info['rating'] ? 'gmrw-star-filled' : 'gmrw-star-empty';
                    echo '<span class="gmrw-star ' . esc_attr($class) . '">★</span>';
                }
                echo '</span>';
                echo '<span class="gmrw-rating-value">' . esc_html(number_format($sanitized_business_info['rating'], 1)) . '</span>';
                echo '</span>';
            }

            if (!empty($sanitized_business_info['review_count'])) {
                echo '<span class="gmrw-review-count">';
                printf(
                    esc_html(_n('(%d review)', '(%d reviews)', $sanitized_business_info['review_count'], GMRW_TEXT_DOMAIN)),
                    intval($sanitized_business_info['review_count'])
                );
                echo '</span>';
            }

            echo '</div>';
        }

        if (!empty($sanitized_business_info['address'])) {
            echo '<p class="gmrw-business-address">' . esc_html($sanitized_business_info['address']) . '</p>';
        }

        if (!empty($sanitized_business_info['phone'])) {
            echo '<p class="gmrw-business-phone">' . esc_html($sanitized_business_info['phone']) . '</p>';
        }

        echo '</div>';
    }

    private function display_reviews($reviews, $attributes) {
        if (empty($reviews)) {
            return;
        }

        echo '<div class="gmrw-reviews gmrw-layout-' . esc_attr($attributes['layout']) . '">';

        foreach ($reviews as $review) {
            $this->display_single_review($review, $attributes);
        }

        echo '</div>';
    }

    private function display_single_review($review, $attributes) {
        // Sanitize review data
        $sanitized_review = Google_Maps_Reviews_Sanitizer::sanitize_review_data($review);

        echo '<div class="gmrw-review" data-review-id="' . esc_attr($sanitized_review['id']) . '">';

        echo '<div class="gmrw-review-author">';
        
        if ($attributes['show_author_image'] && !empty($sanitized_review['author_image'])) {
            echo '<img class="gmrw-author-image" src="' . esc_url($sanitized_review['author_image']) . '" alt="' . esc_attr($sanitized_review['author_name']) . '" loading="lazy">';
        } elseif ($attributes['show_author_image']) {
            echo '<div class="gmrw-author-image gmrw-default-avatar">' . esc_html(substr($sanitized_review['author_name'], 0, 1)) . '</div>';
        }

        echo '<div class="gmrw-author-info">';
        echo '<span class="gmrw-author-name">' . esc_html($sanitized_review['author_name']) . '</span>';
        
        if ($attributes['show_date'] && !empty($sanitized_review['date'])) {
            echo '<span class="gmrw-review-date">' . esc_html($sanitized_review['date']) . '</span>';
        }
        echo '</div>';
        echo '</div>';

        if ($attributes['show_rating'] && !empty($sanitized_review['rating'])) {
            echo '<div class="gmrw-review-rating">';
            echo '<span class="gmrw-stars">';
            for ($i = 1; $i <= 5; $i++) {
                $class = $i <= $sanitized_review['rating'] ? 'gmrw-star-filled' : 'gmrw-star-empty';
                echo '<span class="gmrw-star ' . esc_attr($class) . '">★</span>';
            }
            echo '</span>';
            echo '</div>';
        }

        if (!empty($sanitized_review['content'])) {
            echo '<div class="gmrw-review-content">';
            echo '<p>' . Google_Maps_Reviews_Sanitizer::escape_review_content($sanitized_review['content']) . '</p>';
            echo '</div>';
        }

        if ($attributes['show_helpful_votes'] && !empty($sanitized_review['helpful_votes'])) {
            echo '<div class="gmrw-helpful-votes">';
            printf(
                esc_html(_n('%d person found this helpful', '%d people found this helpful', $sanitized_review['helpful_votes'], GMRW_TEXT_DOMAIN)),
                intval($sanitized_review['helpful_votes'])
            );
            echo '</div>';
        }

        if ($attributes['show_owner_response'] && !empty($sanitized_review['owner_response'])) {
            echo '<div class="gmrw-owner-response">';
            echo '<strong>' . esc_html__('Owner Response:', GMRW_TEXT_DOMAIN) . '</strong>';
            echo '<p>' . Google_Maps_Reviews_Sanitizer::escape_owner_response($sanitized_review['owner_response']) . '</p>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function get_error_message($message) {
        return '<div class="gmrw-error">' . esc_html($message) . '</div>';
    }

    /**
     * Register shortcode attributes with WordPress
     */
    public function register_shortcode_attributes() {
        // Register shortcode attributes for better editor support
        if (function_exists('wp_register_shortcode_attributes')) {
            wp_register_shortcode_attributes(self::SHORTCODE_TAG, array(
                'business_url' => array(
                    'type' => 'string',
                    'required' => true,
                    'description' => __('Google Maps business URL', GMRW_TEXT_DOMAIN),
                ),
                'max_reviews' => array(
                    'type' => 'integer',
                    'default' => 5,
                    'min' => 1,
                    'max' => 50,
                    'description' => __('Maximum number of reviews to display', GMRW_TEXT_DOMAIN),
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'list',
                    'enum' => array('list', 'cards', 'carousel', 'grid'),
                    'description' => __('Display layout for reviews', GMRW_TEXT_DOMAIN),
                ),
                'show_rating' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'description' => __('Show star ratings for each review', GMRW_TEXT_DOMAIN),
                ),
                'show_date' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'description' => __('Show review dates', GMRW_TEXT_DOMAIN),
                ),
                'show_author_image' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'description' => __('Show author profile images', GMRW_TEXT_DOMAIN),
                ),
                'show_helpful_votes' => array(
                    'type' => 'boolean',
                    'default' => false,
                    'description' => __('Show helpful vote counts', GMRW_TEXT_DOMAIN),
                ),
                'show_owner_response' => array(
                    'type' => 'boolean',
                    'default' => false,
                    'description' => __('Show owner responses to reviews', GMRW_TEXT_DOMAIN),
                ),
                'show_business_info' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'description' => __('Show business information header', GMRW_TEXT_DOMAIN),
                ),
                'sort_by' => array(
                    'type' => 'string',
                    'default' => 'relevance',
                    'enum' => array('relevance', 'date', 'rating'),
                    'description' => __('Sort reviews by this criteria', GMRW_TEXT_DOMAIN),
                ),
                'sort_order' => array(
                    'type' => 'string',
                    'default' => 'desc',
                    'enum' => array('asc', 'desc'),
                    'description' => __('Sort order (ascending or descending)', GMRW_TEXT_DOMAIN),
                ),
                'min_rating' => array(
                    'type' => 'integer',
                    'default' => 0,
                    'min' => 0,
                    'max' => 5,
                    'description' => __('Minimum rating filter (0 = all ratings)', GMRW_TEXT_DOMAIN),
                ),
                'cache_duration' => array(
                    'type' => 'integer',
                    'default' => 3600,
                    'min' => 300,
                    'max' => 86400,
                    'description' => __('Cache duration in seconds (5 minutes to 24 hours)', GMRW_TEXT_DOMAIN),
                ),
                'container_class' => array(
                    'type' => 'string',
                    'description' => __('Custom CSS class for the container', GMRW_TEXT_DOMAIN),
                ),
                'title' => array(
                    'type' => 'string',
                    'description' => __('Custom title for the reviews section', GMRW_TEXT_DOMAIN),
                ),
            ));
        }
    }

    /**
     * Add shortcode button to editor
     */
    public function add_shortcode_button() {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof tinymce !== 'undefined') {
                tinymce.PluginManager.add('gmrw_shortcode', function(editor, url) {
                    editor.addButton('gmrw_shortcode', {
                        text: '<?php echo esc_js(__('Google Reviews', GMRW_TEXT_DOMAIN)); ?>',
                        icon: false,
                        onclick: function() {
                            editor.windowManager.open({
                                title: '<?php echo esc_js(__('Insert Google Maps Reviews', GMRW_TEXT_DOMAIN)); ?>',
                                body: [
                                    {
                                        type: 'textbox',
                                        name: 'business_url',
                                        label: '<?php echo esc_js(__('Business URL', GMRW_TEXT_DOMAIN)); ?>',
                                        placeholder: 'https://www.google.com/maps/place/...'
                                    },
                                    {
                                        type: 'listbox',
                                        name: 'max_reviews',
                                        label: '<?php echo esc_js(__('Max Reviews', GMRW_TEXT_DOMAIN)); ?>',
                                        values: [
                                            {text: '5', value: '5'},
                                            {text: '10', value: '10'},
                                            {text: '15', value: '15'},
                                            {text: '20', value: '20'}
                                        ]
                                    },
                                    {
                                        type: 'listbox',
                                        name: 'layout',
                                        label: '<?php echo esc_js(__('Layout', GMRW_TEXT_DOMAIN)); ?>',
                                        values: [
                                            {text: '<?php echo esc_js(__('List', GMRW_TEXT_DOMAIN)); ?>', value: 'list'},
                                            {text: '<?php echo esc_js(__('Cards', GMRW_TEXT_DOMAIN)); ?>', value: 'cards'},
                                            {text: '<?php echo esc_js(__('Carousel', GMRW_TEXT_DOMAIN)); ?>', value: 'carousel'},
                                            {text: '<?php echo esc_js(__('Grid', GMRW_TEXT_DOMAIN)); ?>', value: 'grid'}
                                        ]
                                    }
                                ],
                                onsubmit: function(e) {
                                    var shortcode = '[google_maps_reviews business_url="' + e.data.business_url + '" max_reviews="' + e.data.max_reviews + '" layout="' + e.data.layout + '"]';
                                    editor.insertContent(shortcode);
                                }
                            });
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Enhanced parameter validation
     */
    private function validate_parameters($attributes) {
        $errors = array();

        // Validate business URL
        if (empty($attributes['business_url'])) {
            $errors[] = __('Business URL is required.', GMRW_TEXT_DOMAIN);
        } elseif (!filter_var($attributes['business_url'], FILTER_VALIDATE_URL)) {
            $errors[] = __('Business URL must be a valid URL.', GMRW_TEXT_DOMAIN);
        } elseif (!preg_match('/google\.com\/maps/', $attributes['business_url'])) {
            $errors[] = __('Business URL must be a Google Maps URL.', GMRW_TEXT_DOMAIN);
        }

        // Validate numeric parameters
        if (!is_numeric($attributes['max_reviews']) || $attributes['max_reviews'] < 1 || $attributes['max_reviews'] > 50) {
            $errors[] = __('Max reviews must be between 1 and 50.', GMRW_TEXT_DOMAIN);
        }

        if (!is_numeric($attributes['min_rating']) || $attributes['min_rating'] < 0 || $attributes['min_rating'] > 5) {
            $errors[] = __('Minimum rating must be between 0 and 5.', GMRW_TEXT_DOMAIN);
        }

        if (!is_numeric($attributes['cache_duration']) || $attributes['cache_duration'] < 300 || $attributes['cache_duration'] > 86400) {
            $errors[] = __('Cache duration must be between 300 and 86400 seconds.', GMRW_TEXT_DOMAIN);
        }

        // Validate layout
        $valid_layouts = array('list', 'cards', 'carousel', 'grid');
        if (!in_array($attributes['layout'], $valid_layouts)) {
            $errors[] = __('Invalid layout specified.', GMRW_TEXT_DOMAIN);
        }

        // Validate sort options
        $valid_sort_by = array('relevance', 'date', 'rating');
        if (!in_array($attributes['sort_by'], $valid_sort_by)) {
            $errors[] = __('Invalid sort option specified.', GMRW_TEXT_DOMAIN);
        }

        $valid_sort_orders = array('asc', 'desc');
        if (!in_array($attributes['sort_order'], $valid_sort_orders)) {
            $errors[] = __('Invalid sort order specified.', GMRW_TEXT_DOMAIN);
        }

        return $errors;
    }

    /**
     * Get shortcode documentation and usage examples
     *
     * @return array Documentation array
     */
    public static function get_documentation() {
        return array(
            'title' => __('Google Maps Reviews Shortcode Documentation', GMRW_TEXT_DOMAIN),
            'description' => __('Embed Google Maps reviews anywhere in your WordPress content using shortcodes.', GMRW_TEXT_DOMAIN),
            'shortcodes' => array(
                'primary' => '[google_maps_reviews]',
                'aliases' => array('[gmrw]', '[gmrw_reviews]'),
            ),
            'features' => array(
                __('Embed reviews in posts, pages, and widgets', GMRW_TEXT_DOMAIN),
                __('Multiple shortcode aliases for convenience', GMRW_TEXT_DOMAIN),
                __('All widget features available via shortcode', GMRW_TEXT_DOMAIN),
                __('Custom CSS classes for styling', GMRW_TEXT_DOMAIN),
                __('TinyMCE editor button for easy insertion', GMRW_TEXT_DOMAIN),
                __('WordPress editor integration with attribute hints', GMRW_TEXT_DOMAIN),
            ),
            'installation' => array(
                'step1' => __('Simply add the shortcode to any post or page content', GMRW_TEXT_DOMAIN),
                'step2' => __('Use the TinyMCE button in the visual editor', GMRW_TEXT_DOMAIN),
                'step3' => __('Or manually type the shortcode with desired attributes', GMRW_TEXT_DOMAIN),
            ),
            'attributes' => array(
                'business_url' => __('Google Maps business URL (required)', GMRW_TEXT_DOMAIN),
                'max_reviews' => __('Maximum number of reviews (1-50, default: 5)', GMRW_TEXT_DOMAIN),
                'layout' => __('Display layout: list, cards, carousel, grid (default: list)', GMRW_TEXT_DOMAIN),
                'show_rating' => __('Show star ratings (true/false, default: true)', GMRW_TEXT_DOMAIN),
                'show_date' => __('Show review dates (true/false, default: true)', GMRW_TEXT_DOMAIN),
                'show_author_image' => __('Show reviewer images (true/false, default: true)', GMRW_TEXT_DOMAIN),
                'show_helpful_votes' => __('Show helpful votes (true/false, default: false)', GMRW_TEXT_DOMAIN),
                'show_owner_response' => __('Show owner responses (true/false, default: false)', GMRW_TEXT_DOMAIN),
                'show_business_info' => __('Show business information (true/false, default: true)', GMRW_TEXT_DOMAIN),
                'sort_by' => __('Sort by: relevance, date, rating (default: relevance)', GMRW_TEXT_DOMAIN),
                'sort_order' => __('Sort order: asc, desc (default: desc)', GMRW_TEXT_DOMAIN),
                'min_rating' => __('Minimum rating filter (0-5, default: 0)', GMRW_TEXT_DOMAIN),
                'cache_duration' => __('Cache duration in seconds (300-86400, default: 3600)', GMRW_TEXT_DOMAIN),
                'container_class' => __('Custom CSS class for container (optional)', GMRW_TEXT_DOMAIN),
                'title' => __('Custom title above reviews (optional)', GMRW_TEXT_DOMAIN),
            ),
            'url_examples' => array(
                __('Standard Google Maps URL: https://www.google.com/maps/place/Business+Name/@latitude,longitude,zoom/data=...', GMRW_TEXT_DOMAIN),
                __('Short Google Maps URL: https://goo.gl/maps/...', GMRW_TEXT_DOMAIN),
                __('Google My Business URL: https://business.google.com/...', GMRW_TEXT_DOMAIN),
            ),
            'troubleshooting' => array(
                'no_reviews' => __('If no reviews appear, check that the business URL is correct and the business has reviews on Google Maps.', GMRW_TEXT_DOMAIN),
                'invalid_url' => __('Ensure the URL is a valid Google Maps business page URL.', GMRW_TEXT_DOMAIN),
                'shortcode_not_working' => __('Make sure the shortcode is properly formatted with quotes around attribute values.', GMRW_TEXT_DOMAIN),
                'caching' => __('Reviews are cached for performance. Clear cache if reviews are outdated.', GMRW_TEXT_DOMAIN),
                'rate_limiting' => __('The plugin respects Google\'s terms of service with built-in rate limiting.', GMRW_TEXT_DOMAIN),
            ),
        );
    }

    public static function get_usage_examples() {
        return array(
            'basic' => array(
                'title' => __('Basic Shortcode', GMRW_TEXT_DOMAIN),
                'description' => __('Simple shortcode with default settings', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]',
                'explanation' => __('This will display 5 reviews in list layout with default settings.', GMRW_TEXT_DOMAIN),
            ),
            'custom_reviews' => array(
                'title' => __('Custom Number of Reviews', GMRW_TEXT_DOMAIN),
                'description' => __('Show 10 reviews instead of the default 5', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" max_reviews="10"]',
                'explanation' => __('This will display 10 reviews instead of the default 5.', GMRW_TEXT_DOMAIN),
            ),
            'card_layout' => array(
                'title' => __('Card Layout', GMRW_TEXT_DOMAIN),
                'description' => __('Display reviews in card format', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" layout="cards" max_reviews="6"]',
                'explanation' => __('This will display 6 reviews in an attractive card layout.', GMRW_TEXT_DOMAIN),
            ),
            'featured_reviews' => array(
                'title' => __('Featured 5-Star Reviews', GMRW_TEXT_DOMAIN),
                'description' => __('Show only 5-star reviews', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" min_rating="5" max_reviews="8" layout="cards"]',
                'explanation' => __('This will display only 5-star reviews in card layout.', GMRW_TEXT_DOMAIN),
            ),
            'recent_reviews' => array(
                'title' => __('Recent Reviews', GMRW_TEXT_DOMAIN),
                'description' => __('Show most recent reviews first', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" sort_by="date" sort_order="desc" max_reviews="5"]',
                'explanation' => __('This will display the 5 most recent reviews.', GMRW_TEXT_DOMAIN),
            ),
            'minimal_display' => array(
                'title' => __('Minimal Display', GMRW_TEXT_DOMAIN),
                'description' => __('Show only essential review information', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" show_author_image="false" show_date="false" show_helpful_votes="false" show_owner_response="false"]',
                'explanation' => __('This will display reviews with minimal information for a clean look.', GMRW_TEXT_DOMAIN),
            ),
            'custom_styled' => array(
                'title' => __('Custom Styled', GMRW_TEXT_DOMAIN),
                'description' => __('Add custom CSS class and title', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" container_class="my-custom-reviews" title="Customer Testimonials" layout="grid"]',
                'explanation' => __('This will display reviews with a custom title and CSS class for styling.', GMRW_TEXT_DOMAIN),
            ),
            'carousel_display' => array(
                'title' => __('Carousel Display', GMRW_TEXT_DOMAIN),
                'description' => __('Interactive carousel layout', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" layout="carousel" max_reviews="12" show_rating="true" show_date="true"]',
                'explanation' => __('This will display reviews in an interactive carousel format.', GMRW_TEXT_DOMAIN),
            ),
            'sidebar_widget' => array(
                'title' => __('Sidebar Widget Alternative', GMRW_TEXT_DOMAIN),
                'description' => __('Use shortcode in text widget for sidebar', GMRW_TEXT_DOMAIN),
                'shortcode' => '[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" max_reviews="3" show_author_image="false" show_date="false"]',
                'explanation' => __('Perfect for use in a text widget in your sidebar.', GMRW_TEXT_DOMAIN),
            ),
            'alias_usage' => array(
                'title' => __('Using Shortcode Aliases', GMRW_TEXT_DOMAIN),
                'description' => __('Use shorter alias for convenience', GMRW_TEXT_DOMAIN),
                'shortcode' => '[gmrw business_url="https://www.google.com/maps/place/Your+Business+Name"]',
                'explanation' => __('The [gmrw] alias works exactly the same as the full shortcode.', GMRW_TEXT_DOMAIN),
            ),
        );
    }

    /**
     * Get shortcode configuration tips
     *
     * @return array Configuration tips array
     */
    public static function get_configuration_tips() {
        return array(
            'performance' => array(
                __('Use appropriate cache_duration based on how often reviews change', GMRW_TEXT_DOMAIN),
                __('Limit max_reviews to reduce loading time', GMRW_TEXT_DOMAIN),
                __('Consider using min_rating filter to show only positive reviews', GMRW_TEXT_DOMAIN),
            ),
            'display' => array(
                __('Choose layout based on your content and available space', GMRW_TEXT_DOMAIN),
                __('Use cards or grid layout for visual impact in content areas', GMRW_TEXT_DOMAIN),
                __('List layout works well in sidebars and narrow spaces', GMRW_TEXT_DOMAIN),
                __('Carousel layout is great for featured content areas', GMRW_TEXT_DOMAIN),
            ),
            'content' => array(
                __('Show author images for personal touch', GMRW_TEXT_DOMAIN),
                __('Include dates to show review freshness', GMRW_TEXT_DOMAIN),
                __('Display helpful votes to show review quality', GMRW_TEXT_DOMAIN),
                __('Show owner responses to demonstrate customer service', GMRW_TEXT_DOMAIN),
            ),
            'styling' => array(
                __('Use container_class for custom styling', GMRW_TEXT_DOMAIN),
                __('Add custom title for better context', GMRW_TEXT_DOMAIN),
                __('Combine with theme CSS for seamless integration', GMRW_TEXT_DOMAIN),
            ),
            'seo' => array(
                __('Use descriptive titles for better SEO', GMRW_TEXT_DOMAIN),
                __('Reviews provide fresh, user-generated content', GMRW_TEXT_DOMAIN),
                __('Star ratings can improve click-through rates', GMRW_TEXT_DOMAIN),
            ),
        );
    }

    /**
     * Get shortcode CSS classes for customization
     *
     * @return array CSS classes array
     */
    public static function get_css_classes() {
        return array(
            'container' => 'gmrw-shortcode',
            'business_info' => 'gmrw-business-info',
            'business_name' => 'gmrw-business-name',
            'business_rating' => 'gmrw-business-rating',
            'business_address' => 'gmrw-business-address',
            'business_phone' => 'gmrw-business-phone',
            'reviews_container' => 'gmrw-reviews',
            'review_item' => 'gmrw-review',
            'review_author' => 'gmrw-review-author',
            'author_image' => 'gmrw-author-image',
            'author_name' => 'gmrw-author-name',
            'review_date' => 'gmrw-review-date',
            'review_rating' => 'gmrw-review-rating',
            'review_content' => 'gmrw-review-content',
            'helpful_votes' => 'gmrw-helpful-votes',
            'owner_response' => 'gmrw-owner-response',
            'stars' => 'gmrw-stars',
            'star' => 'gmrw-star',
            'star_filled' => 'gmrw-star-filled',
            'star_empty' => 'gmrw-star-empty',
            'no_reviews' => 'gmrw-no-reviews',
            'error' => 'gmrw-error',
        );
    }

    /**
     * Get shortcode attribute schema for WordPress editor
     *
     * @return array Attribute schema array
     */
    public static function get_attribute_schema() {
        return array(
            'business_url' => array(
                'type' => 'string',
                'required' => true,
                'description' => __('Google Maps business URL', GMRW_TEXT_DOMAIN),
            ),
            'max_reviews' => array(
                'type' => 'number',
                'default' => 5,
                'min' => 1,
                'max' => 50,
                'description' => __('Maximum number of reviews to display', GMRW_TEXT_DOMAIN),
            ),
            'layout' => array(
                'type' => 'string',
                'default' => 'list',
                'enum' => array('list', 'cards', 'carousel', 'grid'),
                'description' => __('Display layout', GMRW_TEXT_DOMAIN),
            ),
            'show_rating' => array(
                'type' => 'boolean',
                'default' => true,
                'description' => __('Show star ratings', GMRW_TEXT_DOMAIN),
            ),
            'show_date' => array(
                'type' => 'boolean',
                'default' => true,
                'description' => __('Show review dates', GMRW_TEXT_DOMAIN),
            ),
            'show_author_image' => array(
                'type' => 'boolean',
                'default' => true,
                'description' => __('Show reviewer images', GMRW_TEXT_DOMAIN),
            ),
            'show_helpful_votes' => array(
                'type' => 'boolean',
                'default' => false,
                'description' => __('Show helpful vote counts', GMRW_TEXT_DOMAIN),
            ),
            'show_owner_response' => array(
                'type' => 'boolean',
                'default' => false,
                'description' => __('Show business owner responses', GMRW_TEXT_DOMAIN),
            ),
            'show_business_info' => array(
                'type' => 'boolean',
                'default' => true,
                'description' => __('Show business information', GMRW_TEXT_DOMAIN),
            ),
            'sort_by' => array(
                'type' => 'string',
                'default' => 'relevance',
                'enum' => array('relevance', 'date', 'rating'),
                'description' => __('Sort reviews by', GMRW_TEXT_DOMAIN),
            ),
            'sort_order' => array(
                'type' => 'string',
                'default' => 'desc',
                'enum' => array('asc', 'desc'),
                'description' => __('Sort order', GMRW_TEXT_DOMAIN),
            ),
            'min_rating' => array(
                'type' => 'number',
                'default' => 0,
                'min' => 0,
                'max' => 5,
                'description' => __('Minimum rating filter', GMRW_TEXT_DOMAIN),
            ),
            'cache_duration' => array(
                'type' => 'number',
                'default' => 3600,
                'min' => 300,
                'max' => 86400,
                'description' => __('Cache duration in seconds', GMRW_TEXT_DOMAIN),
            ),
            'container_class' => array(
                'type' => 'string',
                'description' => __('Custom CSS class for container', GMRW_TEXT_DOMAIN),
            ),
            'title' => array(
                'type' => 'string',
                'description' => __('Custom title above reviews', GMRW_TEXT_DOMAIN),
            ),
        );
    }
}
