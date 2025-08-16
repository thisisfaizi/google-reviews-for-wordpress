<?php
/**
 * Google Maps Reviews Filter Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Maps Reviews Filter Handler
 *
 * Handles server-side filtering of reviews by rating and date
 */
class Google_Maps_Reviews_Filter {

    /**
     * Filter reviews by rating
     *
     * @param array $reviews Array of reviews
     * @param int $min_rating Minimum rating (1-5)
     * @return array Filtered reviews
     */
    public static function filter_by_rating($reviews, $min_rating) {
        if (empty($reviews) || !is_numeric($min_rating) || $min_rating < 1 || $min_rating > 5) {
            return $reviews;
        }

        return array_filter($reviews, function($review) use ($min_rating) {
            $rating = isset($review['rating']) ? intval($review['rating']) : 0;
            return $rating >= $min_rating;
        });
    }

    /**
     * Filter reviews by date range
     *
     * @param array $reviews Array of reviews
     * @param string $date_range Date range ('week', 'month', 'year', 'custom')
     * @param string $custom_start_date Custom start date (Y-m-d format)
     * @param string $custom_end_date Custom end date (Y-m-d format)
     * @return array Filtered reviews
     */
    public static function filter_by_date($reviews, $date_range, $custom_start_date = '', $custom_end_date = '') {
        if (empty($reviews)) {
            return $reviews;
        }

        $current_time = current_time('timestamp');
        $start_timestamp = 0;
        $end_timestamp = $current_time;

        switch ($date_range) {
            case 'week':
                $start_timestamp = strtotime('-1 week', $current_time);
                break;
            case 'month':
                $start_timestamp = strtotime('-1 month', $current_time);
                break;
            case 'year':
                $start_timestamp = strtotime('-1 year', $current_time);
                break;
            case 'custom':
                if (!empty($custom_start_date)) {
                    $start_timestamp = strtotime($custom_start_date);
                }
                if (!empty($custom_end_date)) {
                    $end_timestamp = strtotime($custom_end_date . ' 23:59:59');
                }
                break;
            default:
                return $reviews; // No filtering
        }

        return array_filter($reviews, function($review) use ($start_timestamp, $end_timestamp) {
            if (empty($review['date'])) {
                return false;
            }

            $review_timestamp = strtotime($review['date']);
            return $review_timestamp >= $start_timestamp && $review_timestamp <= $end_timestamp;
        });
    }

    /**
     * Sort reviews by various criteria
     *
     * @param array $reviews Array of reviews
     * @param string $sort_by Sort criteria ('date-new', 'date-old', 'rating-high', 'rating-low', 'helpful')
     * @return array Sorted reviews
     */
    public static function sort_reviews($reviews, $sort_by) {
        if (empty($reviews)) {
            return $reviews;
        }

        usort($reviews, function($a, $b) use ($sort_by) {
            switch ($sort_by) {
                case 'date-new':
                    $a_date = !empty($a['date']) ? strtotime($a['date']) : 0;
                    $b_date = !empty($b['date']) ? strtotime($b['date']) : 0;
                    return $b_date - $a_date;
                
                case 'date-old':
                    $a_date = !empty($a['date']) ? strtotime($a['date']) : 0;
                    $b_date = !empty($b['date']) ? strtotime($b['date']) : 0;
                    return $a_date - $b_date;
                
                case 'rating-high':
                    $a_rating = isset($a['rating']) ? intval($a['rating']) : 0;
                    $b_rating = isset($b['rating']) ? intval($b['rating']) : 0;
                    return $b_rating - $a_rating;
                
                case 'rating-low':
                    $a_rating = isset($a['rating']) ? intval($a['rating']) : 0;
                    $b_rating = isset($b['rating']) ? intval($b['rating']) : 0;
                    return $a_rating - $b_rating;
                
                case 'helpful':
                    $a_votes = isset($a['helpful_votes']) ? intval($a['helpful_votes']) : 0;
                    $b_votes = isset($b['helpful_votes']) ? intval($b['helpful_votes']) : 0;
                    return $b_votes - $a_votes;
                
                default:
                    return 0;
            }
        });

        return $reviews;
    }

    /**
     * Apply multiple filters to reviews
     *
     * @param array $reviews Array of reviews
     * @param array $filters Filter options
     * @return array Filtered and sorted reviews
     */
    public static function apply_filters($reviews, $filters = array()) {
        if (empty($reviews)) {
            return $reviews;
        }

        $filtered_reviews = $reviews;

        // Apply rating filter
        if (!empty($filters['min_rating'])) {
            $filtered_reviews = self::filter_by_rating($filtered_reviews, $filters['min_rating']);
        }

        // Apply date filter
        if (!empty($filters['date_range'])) {
            $custom_start = isset($filters['custom_start_date']) ? $filters['custom_start_date'] : '';
            $custom_end = isset($filters['custom_end_date']) ? $filters['custom_end_date'] : '';
            
            $filtered_reviews = self::filter_by_date(
                $filtered_reviews, 
                $filters['date_range'], 
                $custom_start, 
                $custom_end
            );
        }

        // Apply sorting
        if (!empty($filters['sort_by'])) {
            $filtered_reviews = self::sort_reviews($filtered_reviews, $filters['sort_by']);
        }

        return $filtered_reviews;
    }

    /**
     * Get filter statistics
     *
     * @param array $reviews Array of reviews
     * @return array Statistics about the reviews
     */
    public static function get_filter_stats($reviews) {
        if (empty($reviews)) {
            return array(
                'total' => 0,
                'average_rating' => 0,
                'rating_distribution' => array(),
                'date_range' => array(),
                'helpful_votes_total' => 0
            );
        }

        $stats = array(
            'total' => count($reviews),
            'average_rating' => 0,
            'rating_distribution' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0),
            'date_range' => array('earliest' => null, 'latest' => null),
            'helpful_votes_total' => 0
        );

        $total_rating = 0;
        $rated_count = 0;
        $earliest_date = null;
        $latest_date = null;

        foreach ($reviews as $review) {
            // Rating statistics
            if (isset($review['rating']) && is_numeric($review['rating'])) {
                $rating = intval($review['rating']);
                if ($rating >= 1 && $rating <= 5) {
                    $total_rating += $rating;
                    $rated_count++;
                    $stats['rating_distribution'][$rating]++;
                }
            }

            // Date range
            if (!empty($review['date'])) {
                $review_date = strtotime($review['date']);
                if ($review_date) {
                    if ($earliest_date === null || $review_date < $earliest_date) {
                        $earliest_date = $review_date;
                    }
                    if ($latest_date === null || $review_date > $latest_date) {
                        $latest_date = $review_date;
                    }
                }
            }

            // Helpful votes
            if (isset($review['helpful_votes']) && is_numeric($review['helpful_votes'])) {
                $stats['helpful_votes_total'] += intval($review['helpful_votes']);
            }
        }

        // Calculate average rating
        if ($rated_count > 0) {
            $stats['average_rating'] = round($total_rating / $rated_count, 1);
        }

        // Set date range
        if ($earliest_date) {
            $stats['date_range']['earliest'] = date('Y-m-d', $earliest_date);
        }
        if ($latest_date) {
            $stats['date_range']['latest'] = date('Y-m-d', $latest_date);
        }

        return $stats;
    }

    /**
     * Validate filter parameters
     *
     * @param array $filters Filter parameters
     * @return array Validation results
     */
    public static function validate_filters($filters) {
        $errors = array();
        $warnings = array();

        // Validate rating filter
        if (isset($filters['min_rating'])) {
            if (!is_numeric($filters['min_rating']) || $filters['min_rating'] < 1 || $filters['min_rating'] > 5) {
                $errors[] = __('Invalid minimum rating value. Must be between 1 and 5.', GMRW_TEXT_DOMAIN);
            }
        }

        // Validate date range
        if (isset($filters['date_range'])) {
            $valid_ranges = array('week', 'month', 'year', 'custom');
            if (!in_array($filters['date_range'], $valid_ranges)) {
                $errors[] = __('Invalid date range value.', GMRW_TEXT_DOMAIN);
            }

            // Validate custom dates
            if ($filters['date_range'] === 'custom') {
                if (!empty($filters['custom_start_date']) && !self::is_valid_date($filters['custom_start_date'])) {
                    $errors[] = __('Invalid custom start date format. Use YYYY-MM-DD.', GMRW_TEXT_DOMAIN);
                }
                if (!empty($filters['custom_end_date']) && !self::is_valid_date($filters['custom_end_date'])) {
                    $errors[] = __('Invalid custom end date format. Use YYYY-MM-DD.', GMRW_TEXT_DOMAIN);
                }

                // Check if start date is before end date
                if (!empty($filters['custom_start_date']) && !empty($filters['custom_end_date'])) {
                    $start = strtotime($filters['custom_start_date']);
                    $end = strtotime($filters['custom_end_date']);
                    if ($start > $end) {
                        $errors[] = __('Start date must be before end date.', GMRW_TEXT_DOMAIN);
                    }
                }
            }
        }

        // Validate sort criteria
        if (isset($filters['sort_by'])) {
            $valid_sorts = array('date-new', 'date-old', 'rating-high', 'rating-low', 'helpful');
            if (!in_array($filters['sort_by'], $valid_sorts)) {
                $errors[] = __('Invalid sort criteria.', GMRW_TEXT_DOMAIN);
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    /**
     * Check if a date string is valid
     *
     * @param string $date Date string
     * @return bool True if valid date
     */
    private static function is_valid_date($date) {
        $timestamp = strtotime($date);
        return $timestamp !== false && date('Y-m-d', $timestamp) === $date;
    }

    /**
     * Get available filter options
     *
     * @return array Available filter options
     */
    public static function get_filter_options() {
        return array(
            'rating_filters' => array(
                '' => __('All ratings', GMRW_TEXT_DOMAIN),
                '5' => __('5 stars', GMRW_TEXT_DOMAIN),
                '4' => __('4+ stars', GMRW_TEXT_DOMAIN),
                '3' => __('3+ stars', GMRW_TEXT_DOMAIN),
                '2' => __('2+ stars', GMRW_TEXT_DOMAIN),
                '1' => __('1+ stars', GMRW_TEXT_DOMAIN)
            ),
            'date_filters' => array(
                '' => __('All time', GMRW_TEXT_DOMAIN),
                'week' => __('Last week', GMRW_TEXT_DOMAIN),
                'month' => __('Last month', GMRW_TEXT_DOMAIN),
                'year' => __('Last year', GMRW_TEXT_DOMAIN),
                'custom' => __('Custom range', GMRW_TEXT_DOMAIN)
            ),
            'sort_options' => array(
                'date-new' => __('Newest first', GMRW_TEXT_DOMAIN),
                'date-old' => __('Oldest first', GMRW_TEXT_DOMAIN),
                'rating-high' => __('Highest rating', GMRW_TEXT_DOMAIN),
                'rating-low' => __('Lowest rating', GMRW_TEXT_DOMAIN),
                'helpful' => __('Most helpful', GMRW_TEXT_DOMAIN)
            )
        );
    }

    /**
     * Parse filter parameters from request
     *
     * @param array $request Request parameters
     * @return array Parsed filters
     */
    public static function parse_request_filters($request) {
        $filters = array();

        // Parse rating filter
        if (isset($request['rating']) && !empty($request['rating'])) {
            $filters['min_rating'] = intval($request['rating']);
        }

        // Parse date filter
        if (isset($request['date']) && !empty($request['date'])) {
            $filters['date_range'] = sanitize_text_field($request['date']);
            
            // Parse custom dates
            if ($filters['date_range'] === 'custom') {
                if (isset($request['date_start']) && !empty($request['date_start'])) {
                    $filters['custom_start_date'] = sanitize_text_field($request['date_start']);
                }
                if (isset($request['date_end']) && !empty($request['date_end'])) {
                    $filters['custom_end_date'] = sanitize_text_field($request['date_end']);
                }
            }
        }

        // Parse sort filter
        if (isset($request['sort']) && !empty($request['sort'])) {
            $filters['sort_by'] = sanitize_text_field($request['sort']);
        }

        return $filters;
    }
}
