<?php
/**
 * Google Maps Reviews Display Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Display Handler
 *
 * Handles different layout formats for displaying reviews
 */
class Google_Maps_Reviews_Display {

    /**
     * Available layout types
     */
    const LAYOUT_LIST = 'list';
    const LAYOUT_CARDS = 'cards';
    const LAYOUT_CAROUSEL = 'carousel';
    const LAYOUT_GRID = 'grid';

    /**
     * Display reviews with specified layout
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render_reviews($reviews, $options = array()) {
        if (empty($reviews)) {
            return self::render_no_reviews_message($options);
        }

        $layout = isset($options['layout']) ? $options['layout'] : self::LAYOUT_LIST;
        $container_class = isset($options['container_class']) ? $options['container_class'] : '';
        $reviews_per_page = isset($options['reviews_per_page']) ? intval($options['reviews_per_page']) : 0;
        $show_pagination = isset($options['show_pagination']) ? (bool) $options['show_pagination'] : false;
        $show_filters = isset($options['show_filters']) ? (bool) $options['show_filters'] : false;

        // Sanitize reviews data
        $sanitized_reviews = array();
        foreach ($reviews as $review) {
            $sanitized_reviews[] = Google_Maps_Reviews_Sanitizer::sanitize_review_data($review);
        }

        // Apply server-side filters if enabled
        if ($show_filters && !empty($options['filters'])) {
            $filters = $options['filters'];
            
            // Validate filters
            $validation = Google_Maps_Reviews_Filter::validate_filters($filters);
            if (!$validation['valid']) {
                // Log validation errors but continue with unfiltered reviews
                error_log('Google Maps Reviews: Filter validation failed: ' . implode(', ', $validation['errors']));
            } else {
                $sanitized_reviews = Google_Maps_Reviews_Filter::apply_filters($sanitized_reviews, $filters);
            }
        }

        // Apply pagination if needed
        if ($reviews_per_page > 0 && count($sanitized_reviews) > $reviews_per_page) {
            $current_page = isset($_GET['gmrw_page']) ? max(1, intval($_GET['gmrw_page'])) : 1;
            $offset = ($current_page - 1) * $reviews_per_page;
            $paginated_reviews = array_slice($sanitized_reviews, $offset, $reviews_per_page);
            $total_pages = ceil(count($sanitized_reviews) / $reviews_per_page);
        } else {
            $paginated_reviews = $sanitized_reviews;
            $current_page = 1;
            $total_pages = 1;
        }

        ob_start();

        // Container classes
        $classes = array(
            'gmrw-reviews',
            'gmrw-layout-' . esc_attr($layout),
            $container_class
        );
        $classes = array_filter($classes);
        $container_classes = implode(' ', $classes);

        echo '<div class="' . esc_attr($container_classes) . '" data-layout="' . esc_attr($layout) . '">';

        // Render filter UI if enabled
        if ($show_filters) {
            self::render_filter_ui($sanitized_reviews, $options);
        }

        // Render review count if enabled
        $show_review_count = isset($options['show_review_count']) ? (bool) $options['show_review_count'] : true;
        if ($show_review_count) {
            self::render_review_count($sanitized_reviews, $options);
        }

        // Render reviews based on layout
        switch ($layout) {
            case self::LAYOUT_CARDS:
                self::render_cards_layout($paginated_reviews, $options);
                break;
            case self::LAYOUT_CAROUSEL:
                self::render_carousel_layout($paginated_reviews, $options);
                break;
            case self::LAYOUT_GRID:
                self::render_grid_layout($paginated_reviews, $options);
                break;
            case self::LAYOUT_LIST:
            default:
                self::render_list_layout($paginated_reviews, $options);
                break;
        }

        // Render pagination if needed
        if ($show_pagination && $total_pages > 1) {
            self::render_pagination($current_page, $total_pages, $options);
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Render list layout
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_list_layout($reviews, $options) {
        echo '<div class="gmrw-reviews-list">';
        
        foreach ($reviews as $review) {
            self::render_single_review($review, $options, 'list');
        }
        
        echo '</div>';
    }

    /**
     * Render cards layout
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_cards_layout($reviews, $options) {
        $columns = isset($options['columns']) ? intval($options['columns']) : 3;
        $columns = max(1, min(6, $columns)); // Limit between 1 and 6 columns

        echo '<div class="gmrw-reviews-cards" data-columns="' . esc_attr($columns) . '">';
        
        foreach ($reviews as $review) {
            echo '<div class="gmrw-review-card">';
            self::render_single_review($review, $options, 'card');
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render carousel layout
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_carousel_layout($reviews, $options) {
        $slides_to_show = isset($options['slides_to_show']) ? intval($options['slides_to_show']) : 3;
        $slides_to_show = max(1, min(6, $slides_to_show));
        $autoplay = isset($options['autoplay']) ? (bool) $options['autoplay'] : true;
        $autoplay_speed = isset($options['autoplay_speed']) ? intval($options['autoplay_speed']) : 5000;

        echo '<div class="gmrw-reviews-carousel" 
                  data-slides="' . esc_attr($slides_to_show) . '"
                  data-autoplay="' . esc_attr($autoplay ? 'true' : 'false') . '"
                  data-autoplay-speed="' . esc_attr($autoplay_speed) . '">';
        
        echo '<div class="gmrw-carousel-track">';
        
        foreach ($reviews as $review) {
            echo '<div class="gmrw-carousel-slide">';
            self::render_single_review($review, $options, 'carousel');
            echo '</div>';
        }
        
        echo '</div>';
        
        // Carousel navigation
        if (count($reviews) > $slides_to_show) {
            echo '<button class="gmrw-carousel-prev" aria-label="' . esc_attr__('Previous reviews', GMRW_TEXT_DOMAIN) . '">‹</button>';
            echo '<button class="gmrw-carousel-next" aria-label="' . esc_attr__('Next reviews', GMRW_TEXT_DOMAIN) . '">›</button>';
            
            // Carousel dots
            echo '<div class="gmrw-carousel-dots">';
            $total_slides = count($reviews);
            $dots_count = ceil($total_slides / $slides_to_show);
            for ($i = 0; $i < $dots_count; $i++) {
                $active_class = $i === 0 ? ' active' : '';
                echo '<button class="gmrw-carousel-dot' . $active_class . '" data-slide="' . esc_attr($i) . '" aria-label="' . esc_attr__('Go to slide', GMRW_TEXT_DOMAIN) . ' ' . esc_attr($i + 1) . '"></button>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render grid layout
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_grid_layout($reviews, $options) {
        $columns = isset($options['columns']) ? intval($options['columns']) : 4;
        $columns = max(1, min(6, $columns)); // Limit between 1 and 6 columns

        echo '<div class="gmrw-reviews-grid" data-columns="' . esc_attr($columns) . '">';
        
        foreach ($reviews as $review) {
            echo '<div class="gmrw-review-grid-item">';
            self::render_single_review($review, $options, 'grid');
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render a single review using template system
     *
     * @param array $review Review data
     * @param array $options Display options
     * @param string $layout_type Layout type (list, card, carousel, grid)
     */
    private static function render_single_review($review, $options, $layout_type) {
        // Get theme from options or use default
        $theme = isset($options['theme']) ? $options['theme'] : 'default';
        
        // Prepare template data
        $template_data = array(
            'review' => $review,
            'business' => isset($options['business']) ? $options['business'] : null,
            'options' => $options,
            'index' => isset($options['review_index']) ? $options['review_index'] : 0,
            'total' => isset($options['total_reviews']) ? $options['total_reviews'] : 0
        );
        
        // Render using template system
        echo Google_Maps_Reviews_Templates::render('review', $template_data, $theme);
    }

    /**
     * Render business information using template system
     *
     * @param array $business_info Business information
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render_business_info($business_info, $options = array()) {
        if (empty($business_info)) {
            return '';
        }

        // Sanitize business info
        $sanitized_business_info = Google_Maps_Reviews_Sanitizer::sanitize_business_info($business_info);
        
        // Get theme from options or use default
        $theme = isset($options['theme']) ? $options['theme'] : 'default';
        
        // Prepare template data
        $template_data = array(
            'business' => $sanitized_business_info,
            'options' => $options
        );
        
        // Render using template system
        return Google_Maps_Reviews_Templates::render('business', $template_data, $theme);
    }

    /**
     * Render pagination
     *
     * @param int $current_page Current page number
     * @param int $total_pages Total number of pages
     * @param array $options Display options
     */
    private static function render_pagination($current_page, $total_pages, $options) {
        if ($total_pages <= 1) {
            return;
        }

        $max_visible_pages = isset($options['max_visible_pages']) ? intval($options['max_visible_pages']) : 5;
        $show_first_last = isset($options['show_first_last']) ? (bool) $options['show_first_last'] : true;
        $show_prev_next = isset($options['show_prev_next']) ? (bool) $options['show_prev_next'] : true;

        echo '<div class="gmrw-pagination">';

        // Previous button
        if ($show_prev_next && $current_page > 1) {
            $prev_url = add_query_arg('gmrw_page', $current_page - 1);
            echo '<a href="' . esc_url($prev_url) . '" class="gmrw-pagination-prev">' . esc_html__('‹ Previous', GMRW_TEXT_DOMAIN) . '</a>';
        }

        // First page
        if ($show_first_last && $current_page > 2) {
            $first_url = remove_query_arg('gmrw_page');
            echo '<a href="' . esc_url($first_url) . '" class="gmrw-pagination-first">1</a>';
            if ($current_page > 3) {
                echo '<span class="gmrw-pagination-ellipsis">...</span>';
            }
        }

        // Page numbers
        $start_page = max(1, $current_page - floor($max_visible_pages / 2));
        $end_page = min($total_pages, $start_page + $max_visible_pages - 1);

        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                echo '<span class="gmrw-pagination-current">' . esc_html($i) . '</span>';
            } else {
                $page_url = $i == 1 ? remove_query_arg('gmrw_page') : add_query_arg('gmrw_page', $i);
                echo '<a href="' . esc_url($page_url) . '" class="gmrw-pagination-link">' . esc_html($i) . '</a>';
            }
        }

        // Last page
        if ($show_first_last && $current_page < $total_pages - 1) {
            if ($current_page < $total_pages - 2) {
                echo '<span class="gmrw-pagination-ellipsis">...</span>';
            }
            $last_url = add_query_arg('gmrw_page', $total_pages);
            echo '<a href="' . esc_url($last_url) . '" class="gmrw-pagination-last">' . esc_html($total_pages) . '</a>';
        }

        // Next button
        if ($show_prev_next && $current_page < $total_pages) {
            $next_url = add_query_arg('gmrw_page', $current_page + 1);
            echo '<a href="' . esc_url($next_url) . '" class="gmrw-pagination-next">' . esc_html__('Next ›', GMRW_TEXT_DOMAIN) . '</a>';
        }

        echo '</div>';
    }

    /**
     * Render no reviews message
     *
     * @param array $options Display options
     * @return string HTML output
     */
    private static function render_no_reviews_message($options) {
        $custom_message = isset($options['no_reviews_message']) ? $options['no_reviews_message'] : '';
        $default_message = __('No reviews found for this business.', GMRW_TEXT_DOMAIN);
        $message = !empty($custom_message) ? $custom_message : $default_message;

        return '<div class="gmrw-no-reviews">' . esc_html($message) . '</div>';
    }

    /**
     * Get available layout options
     *
     * @return array Layout options
     */
    public static function get_available_layouts() {
        return array(
            self::LAYOUT_LIST => __('List', GMRW_TEXT_DOMAIN),
            self::LAYOUT_CARDS => __('Cards', GMRW_TEXT_DOMAIN),
            self::LAYOUT_CAROUSEL => __('Carousel', GMRW_TEXT_DOMAIN),
            self::LAYOUT_GRID => __('Grid', GMRW_TEXT_DOMAIN),
        );
    }

    /**
     * Get layout configuration
     *
     * @param string $layout Layout type
     * @return array Layout configuration
     */
    public static function get_layout_config($layout) {
        $configs = array(
            self::LAYOUT_LIST => array(
                'supports_pagination' => true,
                'supports_filtering' => true,
                'supports_sorting' => true,
                'responsive' => true,
                'columns' => 1,
            ),
            self::LAYOUT_CARDS => array(
                'supports_pagination' => true,
                'supports_filtering' => true,
                'supports_sorting' => true,
                'responsive' => true,
                'columns' => 3,
                'max_columns' => 6,
            ),
            self::LAYOUT_CAROUSEL => array(
                'supports_pagination' => false,
                'supports_filtering' => true,
                'supports_sorting' => true,
                'responsive' => true,
                'slides_to_show' => 3,
                'max_slides' => 6,
                'autoplay' => true,
                'autoplay_speed' => 5000,
            ),
            self::LAYOUT_GRID => array(
                'supports_pagination' => true,
                'supports_filtering' => true,
                'supports_sorting' => true,
                'responsive' => true,
                'columns' => 4,
                'max_columns' => 6,
            ),
        );

        return isset($configs[$layout]) ? $configs[$layout] : $configs[self::LAYOUT_LIST];
    }

    /**
     * Enqueue layout-specific assets
     *
     * @param string $layout Layout type
     */
    public static function enqueue_layout_assets($layout) {
        $config = self::get_layout_config($layout);

        // Enqueue CSS
        wp_enqueue_style(
            'gmrw-display-' . $layout,
            GMRW_PLUGIN_URL . 'assets/css/layouts/' . $layout . '.css',
            array(),
            GMRW_VERSION
        );

        // Enqueue JavaScript for interactive layouts
        if ($layout === self::LAYOUT_CAROUSEL) {
            wp_enqueue_script(
                'gmrw-carousel',
                GMRW_PLUGIN_URL . 'assets/js/carousel.js',
                array('jquery'),
                GMRW_VERSION,
                true
            );

            wp_localize_script('gmrw-carousel', 'gmrwCarousel', array(
                'autoplay' => $config['autoplay'],
                'autoplaySpeed' => $config['autoplay_speed'],
                'slidesToShow' => $config['slides_to_show'],
            ));
        }

        // Enqueue JavaScript for read more functionality
        if (isset($options['truncate_content']) && $options['truncate_content']) {
            wp_enqueue_script(
                'gmrw-read-more',
                GMRW_PLUGIN_URL . 'assets/js/read-more.js',
                array('jquery'),
                GMRW_VERSION,
                true
            );
        }
    }

    /**
     * Get CSS classes for a layout
     *
     * @param string $layout Layout type
     * @param array $options Display options
     * @return array CSS classes
     */
    public static function get_layout_classes($layout, $options = array()) {
        $classes = array(
            'gmrw-reviews',
            'gmrw-layout-' . $layout,
        );

        // Add responsive classes
        if (isset($options['responsive']) && $options['responsive']) {
            $classes[] = 'gmrw-responsive';
        }

        // Add column classes for grid and cards
        if (in_array($layout, array(self::LAYOUT_GRID, self::LAYOUT_CARDS))) {
            $columns = isset($options['columns']) ? intval($options['columns']) : 3;
            $classes[] = 'gmrw-columns-' . $columns;
        }

        // Add carousel-specific classes
        if ($layout === self::LAYOUT_CAROUSEL) {
            $slides_to_show = isset($options['slides_to_show']) ? intval($options['slides_to_show']) : 3;
            $classes[] = 'gmrw-slides-' . $slides_to_show;
            
            if (isset($options['autoplay']) && $options['autoplay']) {
                $classes[] = 'gmrw-autoplay';
            }
        }

        return array_filter($classes);
    }

    /**
     * Render filter UI
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_filter_ui($reviews, $options) {
        $filter_options = Google_Maps_Reviews_Filter::get_filter_options();
        $stats = Google_Maps_Reviews_Filter::get_filter_stats($reviews);
        
        // Get current filter values from options or request
        $current_filters = isset($options['filters']) ? $options['filters'] : array();
        $current_rating = isset($current_filters['min_rating']) ? $current_filters['min_rating'] : '';
        $current_date = isset($current_filters['date_range']) ? $current_filters['date_range'] : '';
        $current_sort = isset($current_filters['sort_by']) ? $current_filters['sort_by'] : 'date-new';

        echo '<div class="gmrw-filters" 
                  data-rating-filter="true" 
                  data-date-filter="true" 
                  data-sort-filter="true" 
                  data-show-count="true" 
                  data-preserve-state="true">';
        
        echo '<div class="gmrw-filter-row">';
        
        // Rating filter
        echo '<div class="gmrw-filter-group">';
        echo '<label for="gmrw-rating-filter-' . esc_attr(uniqid()) . '">' . esc_html__('Rating:', GMRW_TEXT_DOMAIN) . '</label>';
        echo '<select class="gmrw-filter-rating" id="gmrw-rating-filter-' . esc_attr(uniqid()) . '">';
        foreach ($filter_options['rating_filters'] as $value => $label) {
            $selected = ($value == $current_rating) ? ' selected' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Date filter
        echo '<div class="gmrw-filter-group">';
        echo '<label for="gmrw-date-filter-' . esc_attr(uniqid()) . '">' . esc_html__('Date:', GMRW_TEXT_DOMAIN) . '</label>';
        echo '<select class="gmrw-filter-date" id="gmrw-date-filter-' . esc_attr(uniqid()) . '">';
        foreach ($filter_options['date_filters'] as $value => $label) {
            $selected = ($value == $current_date) ? ' selected' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Sort filter
        echo '<div class="gmrw-filter-group">';
        echo '<label for="gmrw-sort-filter-' . esc_attr(uniqid()) . '">' . esc_html__('Sort by:', GMRW_TEXT_DOMAIN) . '</label>';
        echo '<select class="gmrw-filter-sort" id="gmrw-sort-filter-' . esc_attr(uniqid()) . '">';
        foreach ($filter_options['sort_options'] as $value => $label) {
            $selected = ($value == $current_sort) ? ' selected' : '';
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Review count
        echo '<div class="gmrw-filter-group gmrw-count-group">';
        echo '<span class="gmrw-review-count">' . sprintf(esc_html__('%d reviews', GMRW_TEXT_DOMAIN), $stats['total']) . '</span>';
        echo '</div>';
        
        // Clear filters button
        echo '<div class="gmrw-filter-group">';
        echo '<button type="button" class="gmrw-clear-filters" aria-label="' . esc_attr__('Clear all filters', GMRW_TEXT_DOMAIN) . '">';
        echo esc_html__('Clear Filters', GMRW_TEXT_DOMAIN);
        echo '</button>';
        echo '</div>';
        
        echo '</div>'; // .gmrw-filter-row
        
        // Custom date range inputs (hidden by default)
        echo '<div class="gmrw-custom-date-range" style="display: none;">';
        echo '<div class="gmrw-filter-row">';
        echo '<div class="gmrw-filter-group">';
        echo '<label for="gmrw-date-start-' . esc_attr(uniqid()) . '">' . esc_html__('Start Date:', GMRW_TEXT_DOMAIN) . '</label>';
        echo '<input type="date" class="gmrw-date-start" id="gmrw-date-start-' . esc_attr(uniqid()) . '" value="' . esc_attr(isset($current_filters['custom_start_date']) ? $current_filters['custom_start_date'] : '') . '">';
        echo '</div>';
        echo '<div class="gmrw-filter-group">';
        echo '<label for="gmrw-date-end-' . esc_attr(uniqid()) . '">' . esc_html__('End Date:', GMRW_TEXT_DOMAIN) . '</label>';
        echo '<input type="date" class="gmrw-date-end" id="gmrw-date-end-' . esc_attr(uniqid()) . '" value="' . esc_attr(isset($current_filters['custom_end_date']) ? $current_filters['custom_end_date'] : '') . '">';
        echo '</div>';
        echo '</div>'; // .gmrw-filter-row
        echo '</div>'; // .gmrw-custom-date-range
        
        echo '</div>'; // .gmrw-filters
    }

    /**
     * Render review count display
     *
     * @param array $reviews Array of reviews
     * @param array $options Display options
     */
    private static function render_review_count($reviews, $options) {
        $total_reviews = count($reviews);
        $reviews_per_page = isset($options['reviews_per_page']) ? intval($options['reviews_per_page']) : 0;
        $current_page = isset($_GET['gmrw_page']) ? max(1, intval($_GET['gmrw_page'])) : 1;
        
        if ($reviews_per_page > 0 && $total_reviews > $reviews_per_page) {
            $total_pages = ceil($total_reviews / $reviews_per_page);
            $start_review = (($current_page - 1) * $reviews_per_page) + 1;
            $end_review = min($current_page * $reviews_per_page, $total_reviews);
            
            echo '<div class="gmrw-review-count-display">';
            echo '<p class="gmrw-review-count-text">';
            printf(
                esc_html__('Showing %1$d-%2$d of %3$d reviews', GMRW_TEXT_DOMAIN),
                $start_review,
                $end_review,
                $total_reviews
            );
            echo '</p>';
            echo '</div>';
        } else {
            echo '<div class="gmrw-review-count-display">';
            echo '<p class="gmrw-review-count-text">';
            printf(
                esc_html(_n('%d review', '%d reviews', $total_reviews, GMRW_TEXT_DOMAIN)),
                $total_reviews
            );
            echo '</p>';
            echo '</div>';
        }
    }
}
