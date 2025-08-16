<?php
/**
 * Google Maps Reviews Widget Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Widget
 *
 * Displays Google Maps reviews in a WordPress widget
 */
class Google_Maps_Reviews_Widget extends WP_Widget {

    /**
     * Widget constructor
     */
    public function __construct() {
        parent::__construct(
            'google_maps_reviews_widget',
            __('Google Maps Reviews', GMRW_TEXT_DOMAIN),
            array(
                'description' => __('Display Google Maps reviews for a business', GMRW_TEXT_DOMAIN),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Widget display method
     *
     * @param array $args     Widget arguments
     * @param array $instance Widget instance settings
     */
    public function widget($args, $instance) {
        // Extract widget arguments
        $before_widget = isset($args['before_widget']) ? $args['before_widget'] : '';
        $after_widget = isset($args['after_widget']) ? $args['after_widget'] : '';
        $before_title = isset($args['before_title']) ? $args['before_title'] : '';
        $after_title = isset($args['after_title']) ? $args['after_title'] : '';

        // Sanitize widget instance data
        $sanitized_instance = Google_Maps_Reviews_Sanitizer::sanitize_widget_instance($instance);
        
        // Get sanitized widget settings
        $title = $sanitized_instance['title'];
        $business_url = $sanitized_instance['business_url'];
        $max_reviews = $sanitized_instance['max_reviews'];
        $layout = $sanitized_instance['layout'];
        $show_rating = $sanitized_instance['show_rating'];
        $show_date = $sanitized_instance['show_date'];
        $show_author_image = $sanitized_instance['show_author_image'];
        $show_helpful_votes = $sanitized_instance['show_helpful_votes'];
        $show_owner_response = $sanitized_instance['show_owner_response'];
        $sort_by = $sanitized_instance['sort_by'];
        $sort_order = $sanitized_instance['sort_order'];
        $min_rating = $sanitized_instance['min_rating'];
        $cache_duration = $sanitized_instance['cache_duration'];
        $reviews_per_page = isset($sanitized_instance['reviews_per_page']) ? $sanitized_instance['reviews_per_page'] : 5;
        $show_pagination = isset($sanitized_instance['show_pagination']) ? $sanitized_instance['show_pagination'] : false;
        $show_review_count = isset($sanitized_instance['show_review_count']) ? $sanitized_instance['show_review_count'] : true;

        // Validate business URL
        if (empty($business_url)) {
            echo $before_widget;
            if (!empty($title)) {
                echo $before_title . esc_html($title) . $after_title;
            }
            echo '<p>' . esc_html__('Please configure a valid Google Maps business URL.', GMRW_TEXT_DOMAIN) . '</p>';
            echo $after_widget;
            return;
        }

        // Validate URL format
        $url_errors = Google_Maps_Reviews_Config::get_url_validation_errors($business_url);
        if (!empty($url_errors)) {
            echo $before_widget;
            if (!empty($title)) {
                echo $before_title . esc_html($title) . $after_title;
            }
            echo '<p>' . esc_html__('Invalid Google Maps business URL. Please check the URL format.', GMRW_TEXT_DOMAIN) . '</p>';
            echo $after_widget;
            return;
        }

        // Get reviews
        $scraper = new Google_Maps_Reviews_Scraper();
        $options = array(
            'max_reviews' => $max_reviews,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'cache_duration' => $cache_duration,
        );

        $reviews_result = $scraper->get_reviews($business_url, $options);

        // Handle errors
        if (is_wp_error($reviews_result)) {
            echo $before_widget;
            if (!empty($title)) {
                echo $before_title . esc_html($title) . $after_title;
            }
            echo '<p>' . esc_html__('Unable to fetch reviews at this time. Please try again later.', GMRW_TEXT_DOMAIN) . '</p>';
            echo $after_widget;
            return;
        }

        $reviews = $reviews_result['reviews'] ?? array();
        $business_info = $reviews_result['business_info'] ?? array();

        // Filter reviews by minimum rating
        if ($min_rating > 0) {
            $reviews = array_filter($reviews, function($review) use ($min_rating) {
                return isset($review['rating']) && $review['rating'] >= $min_rating;
            });
        }

        // Display widget
        echo $before_widget;

        // Widget title
        if (!empty($title)) {
            echo $before_title . esc_html($title) . $after_title;
        }

        // Business information
        if (!empty($business_info)) {
            $this->display_business_info($business_info);
        }

        // Reviews
        if (!empty($reviews)) {
            // Prepare filter options
            $filter_options = array();
            if ($show_filters) {
                $filter_options = array(
                    'show_filters' => $show_filters,
                    'enable_client_side_filtering' => $enable_client_side_filtering,
                    'preserve_filter_state' => $preserve_filter_state,
                    'filters' => array(
                        'min_rating' => $min_rating > 0 ? $min_rating : null,
                        'sort_by' => $sort_by === 'relevance' ? 'date-new' : $sort_by,
                    )
                );
            }

            // Use the display class for rendering
            echo Google_Maps_Reviews_Display::render_reviews($reviews, array_merge(array(
                'layout' => $layout,
                'show_rating' => $show_rating,
                'show_date' => $show_date,
                'show_author_image' => $show_author_image,
                'show_helpful_votes' => $show_helpful_votes,
                'show_owner_response' => $show_owner_response,
                'reviews_per_page' => $reviews_per_page,
                'show_pagination' => $show_pagination,
                'show_review_count' => $show_review_count,
            ), $filter_options));
        } else {
            echo '<p>' . esc_html__('No reviews found for this business.', GMRW_TEXT_DOMAIN) . '</p>';
        }

        echo $after_widget;
    }

    /**
     * Widget form method
     *
     * @param array $instance Widget instance settings
     */
    public function form($instance) {
        // Get current settings
        $title = isset($instance['title']) ? $instance['title'] : '';
        $business_url = isset($instance['business_url']) ? $instance['business_url'] : '';
        $max_reviews = isset($instance['max_reviews']) ? intval($instance['max_reviews']) : 5;
        $layout = isset($instance['layout']) ? $instance['layout'] : 'list';
        $show_rating = isset($instance['show_rating']) ? (bool) $instance['show_rating'] : true;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : true;
        $show_author_image = isset($instance['show_author_image']) ? (bool) $instance['show_author_image'] : true;
        $show_helpful_votes = isset($instance['show_helpful_votes']) ? (bool) $instance['show_helpful_votes'] : false;
        $show_owner_response = isset($instance['show_owner_response']) ? (bool) $instance['show_owner_response'] : false;
        $sort_by = isset($instance['sort_by']) ? $instance['sort_by'] : 'relevance';
        $sort_order = isset($instance['sort_order']) ? $instance['sort_order'] : 'desc';
        $min_rating = isset($instance['min_rating']) ? intval($instance['min_rating']) : 0;
        $cache_duration = isset($instance['cache_duration']) ? intval($instance['cache_duration']) : 3600;
        $show_filters = isset($instance['show_filters']) ? (bool) $instance['show_filters'] : false;
        $enable_client_side_filtering = isset($instance['enable_client_side_filtering']) ? (bool) $instance['enable_client_side_filtering'] : true;
        $preserve_filter_state = isset($instance['preserve_filter_state']) ? (bool) $instance['preserve_filter_state'] : true;

        // Get available options
        $layouts = Google_Maps_Reviews_Config::get_available_layouts();
        $sort_options = Google_Maps_Reviews_Config::get_sort_options();
        $sort_orders = Google_Maps_Reviews_Config::get_sort_orders();

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('business_url')); ?>">
                <?php esc_html_e('Google Maps Business URL:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('business_url')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('business_url')); ?>" 
                   type="url" 
                   value="<?php echo esc_url($business_url); ?>"
                   placeholder="https://www.google.com/maps/place/...">
            <small><?php esc_html_e('Enter the full Google Maps URL for the business', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('max_reviews')); ?>">
                <?php esc_html_e('Maximum Reviews:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('max_reviews')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('max_reviews')); ?>" 
                   type="number" 
                   min="1" 
                   max="50" 
                   value="<?php echo esc_attr($max_reviews); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>">
                <?php esc_html_e('Layout:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('layout')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('layout')); ?>">
                <?php foreach ($layouts as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($layout, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('sort_by')); ?>">
                <?php esc_html_e('Sort By:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('sort_by')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('sort_by')); ?>">
                <?php foreach ($sort_options as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($sort_by, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('sort_order')); ?>">
                <?php esc_html_e('Sort Order:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('sort_order')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('sort_order')); ?>">
                <?php foreach ($sort_orders as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($sort_order, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('min_rating')); ?>">
                <?php esc_html_e('Minimum Rating:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('min_rating')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('min_rating')); ?>">
                <option value="0" <?php selected($min_rating, 0); ?>><?php esc_html_e('All Ratings', GMRW_TEXT_DOMAIN); ?></option>
                <option value="1" <?php selected($min_rating, 1); ?>><?php esc_html_e('1+ Stars', GMRW_TEXT_DOMAIN); ?></option>
                <option value="2" <?php selected($min_rating, 2); ?>><?php esc_html_e('2+ Stars', GMRW_TEXT_DOMAIN); ?></option>
                <option value="3" <?php selected($min_rating, 3); ?>><?php esc_html_e('3+ Stars', GMRW_TEXT_DOMAIN); ?></option>
                <option value="4" <?php selected($min_rating, 4); ?>><?php esc_html_e('4+ Stars', GMRW_TEXT_DOMAIN); ?></option>
                <option value="5" <?php selected($min_rating, 5); ?>><?php esc_html_e('5 Stars Only', GMRW_TEXT_DOMAIN); ?></option>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('cache_duration')); ?>">
                <?php esc_html_e('Cache Duration (seconds):', GMRW_TEXT_DOMAIN); ?>
            </label>
            <input class="small-text" 
                   id="<?php echo esc_attr($this->get_field_id('cache_duration')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('cache_duration')); ?>" 
                   type="number" 
                   min="300" 
                   max="86400" 
                   value="<?php echo esc_attr($cache_duration); ?>">
            <small><?php esc_html_e('Minimum 5 minutes (300s), Maximum 24 hours (86400s)', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('reviews_per_page')); ?>">
                <?php esc_html_e('Reviews Per Page:', GMRW_TEXT_DOMAIN); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('reviews_per_page')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('reviews_per_page')); ?>" 
                   type="number" 
                   min="1" 
                   max="20" 
                   value="<?php echo esc_attr(isset($instance['reviews_per_page']) ? $instance['reviews_per_page'] : 5); ?>">
            <small><?php esc_html_e('Number of reviews to show per page (1-20)', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <h4><?php esc_html_e('Display Options:', GMRW_TEXT_DOMAIN); ?></h4>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_rating')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_rating')); ?>" 
                   value="1" 
                   <?php checked($show_rating); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_rating')); ?>">
                <?php esc_html_e('Show Star Rating', GMRW_TEXT_DOMAIN); ?>
            </label>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>" 
                   value="1" 
                   <?php checked($show_date); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>">
                <?php esc_html_e('Show Review Date', GMRW_TEXT_DOMAIN); ?>
            </label>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_author_image')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_author_image')); ?>" 
                   value="1" 
                   <?php checked($show_author_image); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_author_image')); ?>">
                <?php esc_html_e('Show Author Image', GMRW_TEXT_DOMAIN); ?>
            </label>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_helpful_votes')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_helpful_votes')); ?>" 
                   value="1" 
                   <?php checked($show_helpful_votes); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_helpful_votes')); ?>">
                <?php esc_html_e('Show Helpful Votes', GMRW_TEXT_DOMAIN); ?>
            </label>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_owner_response')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_owner_response')); ?>" 
                   value="1" 
                   <?php checked($show_owner_response); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_owner_response')); ?>">
                <?php esc_html_e('Show Owner Responses', GMRW_TEXT_DOMAIN); ?>
            </label>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_pagination')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_pagination')); ?>" 
                   value="1" 
                   <?php checked(isset($instance['show_pagination']) ? $instance['show_pagination'] : false); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_pagination')); ?>">
                <?php esc_html_e('Show Pagination', GMRW_TEXT_DOMAIN); ?>
            </label>
            <br>
            <small><?php esc_html_e('Display pagination controls when reviews exceed per-page limit', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_review_count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_review_count')); ?>" 
                   value="1" 
                   <?php checked(isset($instance['show_review_count']) ? $instance['show_review_count'] : true); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_review_count')); ?>">
                <?php esc_html_e('Show Review Count', GMRW_TEXT_DOMAIN); ?>
            </label>
            <br>
            <small><?php esc_html_e('Display total number of reviews available', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <h4><?php esc_html_e('Filter Options:', GMRW_TEXT_DOMAIN); ?></h4>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_filters')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_filters')); ?>" 
                   value="1" 
                   <?php checked(isset($instance['show_filters']) ? $instance['show_filters'] : false); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_filters')); ?>">
                <?php esc_html_e('Show Filter Controls', GMRW_TEXT_DOMAIN); ?>
            </label>
            <br>
            <small><?php esc_html_e('Allow users to filter reviews by rating and date', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('enable_client_side_filtering')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('enable_client_side_filtering')); ?>" 
                   value="1" 
                   <?php checked(isset($instance['enable_client_side_filtering']) ? $instance['enable_client_side_filtering'] : true); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('enable_client_side_filtering')); ?>">
                <?php esc_html_e('Enable Client-Side Filtering', GMRW_TEXT_DOMAIN); ?>
            </label>
            <br>
            <small><?php esc_html_e('Use JavaScript for instant filtering (recommended)', GMRW_TEXT_DOMAIN); ?></small>
        </p>

        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('preserve_filter_state')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('preserve_filter_state')); ?>" 
                   value="1" 
                   <?php checked(isset($instance['preserve_filter_state']) ? $instance['preserve_filter_state'] : true); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('preserve_filter_state')); ?>">
                <?php esc_html_e('Preserve Filter State', GMRW_TEXT_DOMAIN); ?>
            </label>
            <br>
            <small><?php esc_html_e('Remember user filter preferences', GMRW_TEXT_DOMAIN); ?></small>
        </p>
        <?php
    }

    /**
     * Widget update method
     *
     * @param array $new_instance New widget instance settings
     * @param array $old_instance Old widget instance settings
     * @return array Updated widget instance settings
     */
    public function update($new_instance, $old_instance) {
        // Use the sanitizer to handle all sanitization and validation
        $instance = Google_Maps_Reviews_Sanitizer::sanitize_widget_form_input($new_instance);
        
        // Additional validation for business URL
        if (!empty($instance['business_url']) && !Google_Maps_Reviews_Sanitizer::validate_business_url($instance['business_url'])) {
            $instance['business_url'] = ''; // Clear invalid URL
        }

        return $instance;
    }

    /**
     * Display business information
     *
     * @param array $business_info Business information
     */
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

    /**
     * Display reviews
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private function display_reviews($reviews, $options) {
        if (empty($reviews)) {
            return;
        }

        $layout = $options['layout'] ?? 'list';
        $show_rating = $options['show_rating'] ?? true;
        $show_date = $options['show_date'] ?? true;
        $show_author_image = $options['show_author_image'] ?? true;
        $show_helpful_votes = $options['show_helpful_votes'] ?? false;
        $show_owner_response = $options['show_owner_response'] ?? false;

        echo '<div class="gmrw-reviews gmrw-layout-' . esc_attr($layout) . '">';

        foreach ($reviews as $review) {
            $this->display_single_review($review, array(
                'show_rating' => $show_rating,
                'show_date' => $show_date,
                'show_author_image' => $show_author_image,
                'show_helpful_votes' => $show_helpful_votes,
                'show_owner_response' => $show_owner_response,
            ));
        }

        echo '</div>';
    }

    /**
     * Display a single review
     *
     * @param array $review Review data
     * @param array $options Display options
     */
    private function display_single_review($review, $options) {
        $show_rating = $options['show_rating'] ?? true;
        $show_date = $options['show_date'] ?? true;
        $show_author_image = $options['show_author_image'] ?? true;
        $show_helpful_votes = $options['show_helpful_votes'] ?? false;
        $show_owner_response = $options['show_owner_response'] ?? false;

        // Sanitize review data
        $sanitized_review = Google_Maps_Reviews_Sanitizer::sanitize_review_data($review);

        echo '<div class="gmrw-review" data-review-id="' . esc_attr($sanitized_review['id']) . '">';

        // Author information
        echo '<div class="gmrw-review-author">';
        
        if ($show_author_image && !empty($sanitized_review['author_image'])) {
            echo '<img class="gmrw-author-image" src="' . esc_url($sanitized_review['author_image']) . '" alt="' . esc_attr($sanitized_review['author_name']) . '" loading="lazy">';
        } elseif ($show_author_image) {
            echo '<div class="gmrw-author-image gmrw-default-avatar">' . esc_html(substr($sanitized_review['author_name'], 0, 1)) . '</div>';
        }

        echo '<div class="gmrw-author-info">';
        echo '<span class="gmrw-author-name">' . esc_html($sanitized_review['author_name']) . '</span>';
        
        if ($show_date && !empty($sanitized_review['date'])) {
            echo '<span class="gmrw-review-date">' . esc_html($sanitized_review['date']) . '</span>';
        }
        echo '</div>';
        echo '</div>';

        // Rating
        if ($show_rating && !empty($sanitized_review['rating'])) {
            echo '<div class="gmrw-review-rating">';
            echo '<span class="gmrw-stars">';
            for ($i = 1; $i <= 5; $i++) {
                $class = $i <= $sanitized_review['rating'] ? 'gmrw-star-filled' : 'gmrw-star-empty';
                echo '<span class="gmrw-star ' . esc_attr($class) . '">★</span>';
            }
            echo '</span>';
            echo '</div>';
        }

        // Review content
        if (!empty($sanitized_review['content'])) {
            echo '<div class="gmrw-review-content">';
            echo '<p>' . Google_Maps_Reviews_Sanitizer::escape_review_content($sanitized_review['content']) . '</p>';
            echo '</div>';
        }

        // Helpful votes
        if ($show_helpful_votes && !empty($sanitized_review['helpful_votes'])) {
            echo '<div class="gmrw-helpful-votes">';
            printf(
                esc_html(_n('%d person found this helpful', '%d people found this helpful', $sanitized_review['helpful_votes'], GMRW_TEXT_DOMAIN)),
                intval($sanitized_review['helpful_votes'])
            );
            echo '</div>';
        }

        // Owner response
        if ($show_owner_response && !empty($sanitized_review['owner_response'])) {
            echo '<div class="gmrw-owner-response">';
            echo '<strong>' . esc_html__('Owner Response:', GMRW_TEXT_DOMAIN) . '</strong>';
            echo '<p>' . Google_Maps_Reviews_Sanitizer::escape_owner_response($sanitized_review['owner_response']) . '</p>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Get widget documentation and usage examples
     *
     * @return array Documentation array
     */
    public static function get_documentation() {
        return array(
            'title' => __('Google Maps Reviews Widget Documentation', GMRW_TEXT_DOMAIN),
            'description' => __('A WordPress widget that displays Google Maps reviews for any business.', GMRW_TEXT_DOMAIN),
            'features' => array(
                __('Display Google Maps reviews without API key', GMRW_TEXT_DOMAIN),
                __('Configurable number of reviews (1-50)', GMRW_TEXT_DOMAIN),
                __('Multiple layout options (list, cards, carousel, grid)', GMRW_TEXT_DOMAIN),
                __('Filter reviews by minimum rating', GMRW_TEXT_DOMAIN),
                __('Sort reviews by relevance, date, or rating', GMRW_TEXT_DOMAIN),
                __('Show/hide review elements (rating, date, author image, etc.)', GMRW_TEXT_DOMAIN),
                __('Automatic caching for performance', GMRW_TEXT_DOMAIN),
                __('Responsive design', GMRW_TEXT_DOMAIN),
            ),
            'installation' => array(
                'step1' => __('Go to Appearance > Widgets in your WordPress admin', GMRW_TEXT_DOMAIN),
                'step2' => __('Find "Google Maps Reviews" in the available widgets', GMRW_TEXT_DOMAIN),
                'step3' => __('Drag and drop it to your desired widget area', GMRW_TEXT_DOMAIN),
                'step4' => __('Configure the widget settings and save', GMRW_TEXT_DOMAIN),
            ),
            'configuration' => array(
                'title' => __('Widget Title (optional)', GMRW_TEXT_DOMAIN),
                'business_url' => __('Google Maps business URL (required)', GMRW_TEXT_DOMAIN),
                'max_reviews' => __('Maximum number of reviews to display (1-50)', GMRW_TEXT_DOMAIN),
                'layout' => __('Display layout: list, cards, carousel, or grid', GMRW_TEXT_DOMAIN),
                'show_rating' => __('Show star ratings for each review', GMRW_TEXT_DOMAIN),
                'show_date' => __('Show review dates', GMRW_TEXT_DOMAIN),
                'show_author_image' => __('Show reviewer profile images', GMRW_TEXT_DOMAIN),
                'show_helpful_votes' => __('Show helpful vote counts', GMRW_TEXT_DOMAIN),
                'show_owner_response' => __('Show business owner responses', GMRW_TEXT_DOMAIN),
                'sort_by' => __('Sort reviews by: relevance, date, or rating', GMRW_TEXT_DOMAIN),
                'sort_order' => __('Sort order: ascending or descending', GMRW_TEXT_DOMAIN),
                'min_rating' => __('Minimum rating filter (0-5, 0 = show all)', GMRW_TEXT_DOMAIN),
                'cache_duration' => __('Cache duration in seconds (300-86400)', GMRW_TEXT_DOMAIN),
            ),
            'url_examples' => array(
                __('Standard Google Maps URL: https://www.google.com/maps/place/Business+Name/@latitude,longitude,zoom/data=...', GMRW_TEXT_DOMAIN),
                __('Short Google Maps URL: https://goo.gl/maps/...', GMRW_TEXT_DOMAIN),
                __('Google My Business URL: https://business.google.com/...', GMRW_TEXT_DOMAIN),
            ),
            'troubleshooting' => array(
                'no_reviews' => __('If no reviews appear, check that the business URL is correct and the business has reviews on Google Maps.', GMRW_TEXT_DOMAIN),
                'invalid_url' => __('Ensure the URL is a valid Google Maps business page URL.', GMRW_TEXT_DOMAIN),
                'caching' => __('Reviews are cached for performance. Clear cache if reviews are outdated.', GMRW_TEXT_DOMAIN),
                'rate_limiting' => __('The plugin respects Google\'s terms of service with built-in rate limiting.', GMRW_TEXT_DOMAIN),
            ),
        );
    }

    /**
     * Get widget usage examples
     *
     * @return array Usage examples array
     */
    public static function get_usage_examples() {
        return array(
            'basic' => array(
                'title' => __('Basic Widget Setup', GMRW_TEXT_DOMAIN),
                'description' => __('Simple widget with default settings', GMRW_TEXT_DOMAIN),
                'settings' => array(
                    'title' => 'Customer Reviews',
                    'business_url' => 'https://www.google.com/maps/place/Your+Business+Name',
                    'max_reviews' => 5,
                    'layout' => 'list',
                ),
            ),
            'featured' => array(
                'title' => __('Featured Reviews Widget', GMRW_TEXT_DOMAIN),
                'description' => __('Show only 5-star reviews in card layout', GMRW_TEXT_DOMAIN),
                'settings' => array(
                    'title' => '5-Star Reviews',
                    'business_url' => 'https://www.google.com/maps/place/Your+Business+Name',
                    'max_reviews' => 10,
                    'layout' => 'cards',
                    'min_rating' => 5,
                    'sort_by' => 'rating',
                    'sort_order' => 'desc',
                    'show_rating' => true,
                    'show_date' => true,
                    'show_author_image' => true,
                ),
            ),
            'sidebar' => array(
                'title' => __('Sidebar Reviews Widget', GMRW_TEXT_DOMAIN),
                'description' => __('Compact widget for sidebar display', GMRW_TEXT_DOMAIN),
                'settings' => array(
                    'title' => 'What Our Customers Say',
                    'business_url' => 'https://www.google.com/maps/place/Your+Business+Name',
                    'max_reviews' => 3,
                    'layout' => 'list',
                    'show_rating' => true,
                    'show_date' => false,
                    'show_author_image' => false,
                    'show_helpful_votes' => false,
                    'show_owner_response' => false,
                ),
            ),
            'carousel' => array(
                'title' => __('Reviews Carousel', GMRW_TEXT_DOMAIN),
                'description' => __('Interactive carousel display', GMRW_TEXT_DOMAIN),
                'settings' => array(
                    'title' => 'Customer Testimonials',
                    'business_url' => 'https://www.google.com/maps/place/Your+Business+Name',
                    'max_reviews' => 15,
                    'layout' => 'carousel',
                    'show_rating' => true,
                    'show_date' => true,
                    'show_author_image' => true,
                    'sort_by' => 'date',
                    'sort_order' => 'desc',
                ),
            ),
            'grid' => array(
                'title' => __('Reviews Grid', GMRW_TEXT_DOMAIN),
                'description' => __('Grid layout for visual appeal', GMRW_TEXT_DOMAIN),
                'settings' => array(
                    'title' => 'Customer Reviews Grid',
                    'business_url' => 'https://www.google.com/maps/place/Your+Business+Name',
                    'max_reviews' => 8,
                    'layout' => 'grid',
                    'show_rating' => true,
                    'show_date' => true,
                    'show_author_image' => true,
                    'min_rating' => 4,
                ),
            ),
        );
    }

    /**
     * Get widget configuration tips
     *
     * @return array Configuration tips array
     */
    public static function get_configuration_tips() {
        return array(
            'performance' => array(
                __('Use appropriate cache duration based on how often reviews change', GMRW_TEXT_DOMAIN),
                __('Limit max_reviews to reduce loading time', GMRW_TEXT_DOMAIN),
                __('Consider using min_rating filter to show only positive reviews', GMRW_TEXT_DOMAIN),
            ),
            'display' => array(
                __('Choose layout based on your theme and available space', GMRW_TEXT_DOMAIN),
                __('Use cards or grid layout for visual impact', GMRW_TEXT_DOMAIN),
                __('List layout works well in sidebars and narrow spaces', GMRW_TEXT_DOMAIN),
                __('Carousel layout is great for featured content areas', GMRW_TEXT_DOMAIN),
            ),
            'content' => array(
                __('Show author images for personal touch', GMRW_TEXT_DOMAIN),
                __('Include dates to show review freshness', GMRW_TEXT_DOMAIN),
                __('Display helpful votes to show review quality', GMRW_TEXT_DOMAIN),
                __('Show owner responses to demonstrate customer service', GMRW_TEXT_DOMAIN),
            ),
            'seo' => array(
                __('Use descriptive widget titles for better SEO', GMRW_TEXT_DOMAIN),
                __('Reviews provide fresh, user-generated content', GMRW_TEXT_DOMAIN),
                __('Star ratings can improve click-through rates', GMRW_TEXT_DOMAIN),
            ),
        );
    }

    /**
     * Get widget CSS classes for customization
     *
     * @return array CSS classes array
     */
    public static function get_css_classes() {
        return array(
            'container' => 'gmrw-widget',
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
}
