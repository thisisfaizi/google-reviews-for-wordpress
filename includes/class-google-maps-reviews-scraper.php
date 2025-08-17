<?php
/**
 * Google Maps Reviews Scraper Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Google_Maps_Reviews_Scraper {
    
    /**
     * User agent for requests
     *
     * @var string
     */
    private $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    /**
     * Request timeout in seconds
     *
     * @var int
     */
    private $timeout;
    
    /**
     * Rate limiting delay in seconds
     *
     * @var int
     */
    private $rate_limit_delay;
    
    /**
     * Maximum number of reviews to fetch
     *
     * @var int
     */
    private $max_reviews;
    
    /**
     * Last request time for rate limiting
     *
     * @var int
     */
    private $last_request_time = 0;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        $this->timeout = $settings['request_timeout'] ?? GMRW_REQUEST_TIMEOUT;
        $this->rate_limit_delay = $settings['rate_limit_delay'] ?? GMRW_RATE_LIMIT_DELAY;
        $this->max_reviews = $settings['max_reviews'] ?? GMRW_MAX_REVIEWS;
    }
    
    /**
     * Get reviews from Google Maps business URL
     *
     * @param string $business_url Google Maps business URL
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    public function get_reviews($business_url, $options = array()) {
        $retry_count = 0;
        $max_retries = isset($options['max_retries']) ? $options['max_retries'] : 3;
        $retry_delay = isset($options['retry_delay']) ? $options['retry_delay'] : 5;
        
        while ($retry_count <= $max_retries) {
            try {
                // Validate business URL
                if (!Google_Maps_Reviews_Config::validate_business_url($business_url)) {
                    throw new Google_Maps_Reviews_Scraping_Exception(
                        __('Invalid Google Maps business URL', GMRW_TEXT_DOMAIN),
                        'INVALID_URL'
                    );
                }
                
                // Check cache first
                $cache = new Google_Maps_Reviews_Cache();
                $cached_reviews = $cache->get_reviews($business_url);
                
                if ($cached_reviews !== false) {
                    return $cached_reviews;
                }
                
                // Parse business URL to get place ID
                $place_id = $this->extract_place_id($business_url);
                
                if ($place_id) {
                    // Use place ID method if available
                    $reviews = $this->fetch_reviews($place_id, $options);
                } else {
                    // Use business name method if no place ID
                    $business_name = Google_Maps_Reviews_Config::extract_business_name_from_url($business_url);
                    if (!$business_name) {
                        throw new Google_Maps_Reviews_Scraping_Exception(
                            __('Could not extract business name from URL', GMRW_TEXT_DOMAIN),
                            'BUSINESS_NAME_EXTRACTION_FAILED'
                        );
                    }
                    $reviews = $this->fetch_reviews_by_business_name($business_name, $options);
                }
                
                // Cache the results
                if (!empty($reviews)) {
                    $cache->set_reviews($business_url, $reviews);
                }
                
                return $reviews;
                
            } catch (Google_Maps_Reviews_Scraping_Exception $e) {
                $e->log();
                
                // Check if this is a retryable error
                if ($this->is_retryable_error($e) && $retry_count < $max_retries) {
                    $retry_count++;
                    $this->log_retry_attempt($business_url, $retry_count, $e->getMessage());
                    
                    // Exponential backoff
                    $delay = $retry_delay * pow(2, $retry_count - 1);
                    sleep($delay);
                    
                    continue;
                }
                
                return new WP_Error('scraping_error', $e->getMessage(), array(
                    'error_code' => $e->get_error_code(),
                    'retry_count' => $retry_count,
                    'business_url' => $business_url
                ));
                
            } catch (Exception $e) {
                $this->log_error('unexpected_error', $e->getMessage(), array(
                    'business_url' => $business_url,
                    'retry_count' => $retry_count,
                    'trace' => $e->getTraceAsString()
                ));
                
                if ($retry_count < $max_retries) {
                    $retry_count++;
                    $this->log_retry_attempt($business_url, $retry_count, $e->getMessage());
                    
                    // Exponential backoff
                    $delay = $retry_delay * pow(2, $retry_count - 1);
                    sleep($delay);
                    
                    continue;
                }
                
                return new WP_Error('unexpected_error', __('An unexpected error occurred', GMRW_TEXT_DOMAIN), array(
                    'retry_count' => $retry_count,
                    'business_url' => $business_url
                ));
            }
        }
        
        return new WP_Error('max_retries_exceeded', __('Maximum retry attempts exceeded', GMRW_TEXT_DOMAIN));
    }
    
    /**
     * Extract place ID from Google Maps URL
     *
     * @param string $url Google Maps URL
     * @return string|false Place ID or false on failure
     */
    private function extract_place_id($url) {
        // Common patterns for Google Maps URLs
        $patterns = array(
            // maps.google.com/maps/place/...
            '/maps\.google\.com\/maps\/place\/[^\/]+\/([^\/\?]+)/',
            // maps.google.com/maps?cid=...
            '/maps\.google\.com\/maps\?.*cid=([^&\s]+)/',
            // maps.google.com/maps?q=place_id:...
            '/maps\.google\.com\/maps\?.*q=place_id:([^&\s]+)/',
            // goo.gl/maps/...
            '/goo\.gl\/maps\/([^\/\?]+)/',
            // google.com/maps/place/...
            '/google\.com\/maps\/place\/[^\/]+\/([^\/\?]+)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        // Try to extract from any URL with place_id parameter
        if (preg_match('/place_id=([^&\s]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return false;
    }
    
    /**
     * Fetch reviews from Google Maps
     *
     * @param string $place_id Google Maps place ID
     * @param array $options Additional options
     * @return array Array of reviews
     */
    private function fetch_reviews($place_id, $options = array()) {
        $reviews = array();
        $page = 0;
        $max_pages = isset($options['max_pages']) ? $options['max_pages'] : 5;
        $reviews_per_page = isset($options['reviews_per_page']) ? $options['reviews_per_page'] : 20;
        $consecutive_empty_pages = 0;
        $max_consecutive_empty = isset($options['max_consecutive_empty']) ? $options['max_consecutive_empty'] : 2;
        
        $pagination_stats = array(
            'total_pages_fetched' => 0,
            'total_reviews_fetched' => 0,
            'empty_pages' => 0,
            'failed_pages' => 0,
        );
        
        while (count($reviews) < $this->max_reviews && $page < $max_pages) {
            // Rate limiting
            $this->rate_limit();
            
            // Fetch reviews for current page
            $page_reviews = $this->fetch_reviews_page($place_id, $page, $options);
            
            if (is_wp_error($page_reviews)) {
                $pagination_stats['failed_pages']++;
                $this->log_error('page_fetch_failed', 'Failed to fetch page ' . $page, array(
                    'place_id' => $place_id,
                    'page' => $page,
                    'error' => $page_reviews->get_error_message()
                ));
                
                // If too many consecutive failures, stop
                if ($pagination_stats['failed_pages'] > 3) {
                    break;
                }
                
                $page++;
                continue;
            }
            
            $pagination_stats['total_pages_fetched']++;
            
            if (empty($page_reviews)) {
                $consecutive_empty_pages++;
                $pagination_stats['empty_pages']++;
                
                // If too many consecutive empty pages, stop
                if ($consecutive_empty_pages >= $max_consecutive_empty) {
                    $this->log_error('pagination_info', 'Stopping pagination due to consecutive empty pages', array(
                        'place_id' => $place_id,
                        'consecutive_empty_pages' => $consecutive_empty_pages,
                        'total_reviews_fetched' => count($reviews)
                    ));
                    break;
                }
            } else {
                $consecutive_empty_pages = 0; // Reset counter
                $reviews = array_merge($reviews, $page_reviews);
                $pagination_stats['total_reviews_fetched'] = count($reviews);
                
                // Log pagination progress
                $this->log_error('pagination_progress', 'Fetched page ' . $page, array(
                    'place_id' => $place_id,
                    'page' => $page,
                    'reviews_on_page' => count($page_reviews),
                    'total_reviews' => count($reviews),
                    'target_reviews' => $this->max_reviews
                ));
            }
            
            $page++;
        }
        
        // Limit to max reviews
        if (count($reviews) > $this->max_reviews) {
            $reviews = array_slice($reviews, 0, $this->max_reviews);
        }
        
        // Log final pagination statistics
        $this->log_error('pagination_complete', 'Pagination completed', array(
            'place_id' => $place_id,
            'final_stats' => $pagination_stats,
            'final_review_count' => count($reviews),
            'target_review_count' => $this->max_reviews
        ));
        
        return $reviews;
    }
    
    /**
     * Fetch reviews for a specific page
     *
     * @param string $place_id Google Maps place ID
     * @param int $page Page number
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    private function fetch_reviews_page($place_id, $page = 0, $options = array()) {
        try {
            // Construct the reviews URL
            $url = $this->build_reviews_url($place_id, $page, $options);
            
            // Make HTTP request
            $response = $this->make_request($url);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Parse the response
            $reviews = $this->parse_reviews_response($response['body']);
            
            // Check for pagination indicators
            $has_more_pages = $this->check_for_more_pages($response['body']);
            
            // Log page fetch results
            $this->log_error('page_fetch_success', 'Successfully fetched page ' . $page, array(
                'place_id' => $place_id,
                'page' => $page,
                'reviews_count' => count($reviews),
                'has_more_pages' => $has_more_pages,
                'url' => $url
            ));
            
            return $reviews;
            
        } catch (Exception $e) {
            return new WP_Error(
                'page_fetch_exception',
                $e->getMessage(),
                array('place_id' => $place_id, 'page' => $page)
            );
        }
    }
    
    /**
     * Build reviews URL for Google Maps
     *
     * @param string $place_id Google Maps place ID
     * @param int $page Page number
     * @param array $options Additional options
     * @return string Reviews URL
     */
    private function build_reviews_url($place_id, $page = 0, $options = array()) {
        $base_url = 'https://www.google.com/maps/place/';
        $reviews_path = '/data=!4m8!14m7!1m6!2m5!1s!2m1!1s!3m1!1s2!4e1!5m1!1e1!6m1!1e2';
        
        $url = $base_url . $place_id . $reviews_path;
        
        // Add pagination if needed
        if ($page > 0) {
            $reviews_per_page = isset($options['reviews_per_page']) ? $options['reviews_per_page'] : 20;
            $start_index = $page * $reviews_per_page;
            $url .= '&start=' . $start_index;
        }
        
        // Add sorting options if specified
        if (isset($options['sort_by'])) {
            $url .= '&sort=' . urlencode($options['sort_by']);
        }
        
        // Add language options if specified
        if (isset($options['language'])) {
            $url .= '&hl=' . urlencode($options['language']);
        }
        
        return $url;
    }
    
    /**
     * Check if there are more pages available
     *
     * @param string $html HTML content
     * @return bool Whether more pages are available
     */
    private function check_for_more_pages($html) {
        // Look for pagination indicators in the HTML
        $pagination_indicators = array(
            'next',
            'more',
            'load more',
            'show more',
            'pagination',
            'page',
        );
        
        $html_lower = strtolower($html);
        
        foreach ($pagination_indicators as $indicator) {
            if (strpos($html_lower, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for specific Google Maps pagination elements
        if (preg_match('/data-next-page|next-page|more-reviews/i', $html)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Make HTTP request using WordPress HTTP API
     *
     * @param string $url URL to request
     * @return array|WP_Error Response array or WP_Error on failure
     */
    private function make_request($url) {
        // Check robots.txt before making request
        if (!$this->check_robots_txt($url)) {
            return new WP_Error(
                'robots_txt_disallowed',
                __('URL is disallowed by robots.txt', GMRW_TEXT_DOMAIN),
                array('url' => $url)
            );
        }
        
        // Apply rate limiting
        $this->rate_limit();
        
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        $args = array(
            'timeout' => $this->timeout,
            'user-agent' => $this->user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Referer' => 'https://www.google.com/',
            ),
            'sslverify' => false, // Some servers have SSL issues
            'redirection' => 5,
            'blocking' => true,
            'cookies' => array(),
        );
        
        // Add custom headers if configured
        if (!empty($settings['custom_headers'])) {
            $args['headers'] = array_merge($args['headers'], $settings['custom_headers']);
        }
        
        $response = wp_remote_get($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Handle different response codes
        switch ($response_code) {
            case 200:
                // Success
                break;
            case 429:
                // Too Many Requests - implement exponential backoff
                $this->handle_rate_limit_exceeded();
                return new WP_Error(
                    'rate_limit_exceeded',
                    __('Rate limit exceeded. Please try again later.', GMRW_TEXT_DOMAIN),
                    array('status_code' => $response_code, 'url' => $url)
                );
            case 403:
                // Forbidden - might be blocked
                return new WP_Error(
                    'access_forbidden',
                    __('Access forbidden. The request may have been blocked.', GMRW_TEXT_DOMAIN),
                    array('status_code' => $response_code, 'url' => $url)
                );
            case 404:
                // Not Found
                return new WP_Error(
                    'not_found',
                    __('The requested resource was not found.', GMRW_TEXT_DOMAIN),
                    array('status_code' => $response_code, 'url' => $url)
                );
            default:
                return new WP_Error(
                    'http_error',
                    sprintf(__('HTTP request failed with status code %d', GMRW_TEXT_DOMAIN), $response_code),
                    array('status_code' => $response_code, 'url' => $url)
                );
        }
        
        return $response;
    }
    
    /**
     * Handle rate limit exceeded response
     */
    private function handle_rate_limit_exceeded() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        // Increase rate limit delay temporarily
        $this->rate_limit_delay = min($this->rate_limit_delay * 2, 30);
        
        // Store rate limit event
        $rate_limit_key = 'gmrw_rate_limit_' . md5(site_url());
        $rate_limit_count = get_transient($rate_limit_key) ?: 0;
        $rate_limit_count++;
        set_transient($rate_limit_key, $rate_limit_count, 3600);
        
        // Log rate limit event
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Google Maps Reviews Widget: Rate limit exceeded. Count: {$rate_limit_count}");
        }
        
        // If rate limit exceeded too many times, increase delay permanently
        if ($rate_limit_count > 5) {
            $settings['rate_limit_delay'] = min($settings['rate_limit_delay'] * 1.5, 10);
            Google_Maps_Reviews_Config::update_settings($settings);
        }
    }
    
    /**
     * Parse reviews from Google Maps response
     *
     * @param string $html HTML response from Google Maps
     * @return array Array of parsed reviews
     */
    private function parse_reviews_response($html) {
        $reviews = array();
        
        // Validate HTML content before parsing
        if (!$this->validate_html_content($html)) {
            return $this->handle_parsing_error($html, 'HTML validation failed');
        }
        
        try {
            // Use DOMDocument for parsing
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // Suppress XML parsing warnings
            $dom->loadHTML($html);
            libxml_clear_errors();
            
            $xpath = new DOMXPath($dom);
            
            // Try multiple selectors for review containers - updated for current Google Maps structure
            $review_selectors = array(
                // Current Google Maps review selectors (2024)
                '//div[contains(@class, "jftiEf")]',
                '//div[contains(@class, "g88MCb")]',
                '//div[contains(@class, "review-dialog-list")]//div[contains(@class, "jftiEf")]',
                '//div[contains(@class, "review-dialog-list")]//div[contains(@class, "g88MCb")]',
                
                // Alternative modern selectors
                '//div[contains(@class, "review") and contains(@class, "jftiEf")]',
                '//div[contains(@class, "review") and contains(@class, "g88MCb")]',
                '//div[contains(@class, "review-item")]',
                '//div[contains(@class, "review-container")]',
                
                // Generic review-like containers
                '//div[contains(@class, "review")]',
                '//div[contains(@class, "review-dialog")]//div[contains(@class, "review")]',
                
                // Fallback selectors for different Google Maps versions
                '//div[contains(@class, "review-dialog-list")]//div[contains(@class, "review")]',
                '//div[contains(@class, "review-dialog-list")]//div[contains(@class, "review-item")]',
            );
            
            $review_nodes = null;
            foreach ($review_selectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    $review_nodes = $nodes;
                    break;
                }
            }
            
                         if (!$review_nodes || $review_nodes->length === 0) {
                 // Enhanced debugging - look for any review-related content
                 $debug_info = array(
                     'html_length' => strlen($html),
                     'selectors_tried' => $review_selectors,
                     'html_preview' => substr($html, 0, 2000), // First 2000 chars for debugging
                     'contains_review_text' => strpos(strtolower($html), 'review') !== false,
                     'contains_rating_text' => strpos(strtolower($html), 'rating') !== false,
                     'contains_star_text' => strpos(strtolower($html), 'star') !== false,
                 );
                 
                 // Try to find any div with review-related classes
                 $all_divs = $xpath->query('//div[contains(@class, "review") or contains(@class, "rating") or contains(@class, "star")]');
                 $debug_info['divs_with_review_classes'] = $all_divs->length;
                 
                 $this->log_error('parsing_error', 'No review containers found in HTML', $debug_info);
                 
                 // If we found some divs with review classes, try to parse them anyway
                 if ($all_divs->length > 0) {
                     $this->log_error('debug_info', 'Found ' . $all_divs->length . ' divs with review-related classes, attempting to parse', array());
                     $review_nodes = $all_divs;
                 } else {
                     return $reviews;
                 }
             }
            
            $successful_parses = 0;
            $failed_parses = 0;
            
            foreach ($review_nodes as $review_node) {
                try {
                    $review = $this->parse_single_review($review_node, $xpath);
                    if ($review) {
                        $reviews[] = $review;
                        $successful_parses++;
                    } else {
                        $failed_parses++;
                    }
                } catch (Exception $e) {
                    $failed_parses++;
                    $this->log_error('review_parsing_error', $e->getMessage(), array(
                        'node_index' => $successful_parses + $failed_parses,
                        'total_nodes' => $review_nodes->length
                    ));
                }
            }
            
            // Log parsing statistics
            if ($successful_parses > 0 || $failed_parses > 0) {
                $this->log_error('parsing_stats', "Parsed {$successful_parses} reviews successfully, {$failed_parses} failed", array(
                    'successful_parses' => $successful_parses,
                    'failed_parses' => $failed_parses,
                    'total_nodes' => $review_nodes->length
                ));
            }
            
        } catch (Exception $e) {
            return $this->handle_parsing_error($html, 'DOM parsing failed: ' . $e->getMessage());
        }
        
        return $reviews;
    }
    
    /**
     * Parse a single review from DOM node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return array|false Review data or false on failure
     */
    private function parse_single_review($node, $xpath) {
        try {
            $review = array(
                'id' => '',
                'author_name' => '',
                'author_image' => '',
                'rating' => 0,
                'content' => '',
                'date' => '',
                'helpful_votes' => 0,
                'owner_response' => '',
                'language' => 'en',
            );
            
            // Extract review ID
            $review['id'] = $this->extract_review_id($node, $xpath);
            
            // Extract author name using multiple selectors
            $author_name = $this->extract_author_name($node, $xpath);
            if ($author_name) {
                $review['author_name'] = $author_name;
            }
            
            // Extract author image using multiple selectors
            $author_image = $this->extract_author_image($node, $xpath);
            if ($author_image) {
                $review['author_image'] = $author_image;
            }
            
            // Extract rating using multiple selectors
            $rating = $this->extract_rating($node, $xpath);
            if ($rating > 0) {
                $review['rating'] = $rating;
            }
            
            // Extract review content using multiple selectors
            $content = $this->extract_review_content($node, $xpath);
            if ($content) {
                $review['content'] = $content;
            }
            
            // Extract date using multiple selectors
            $date = $this->extract_review_date($node, $xpath);
            if ($date) {
                $review['date'] = $date;
            }
            
            // Extract helpful votes using multiple selectors
            $votes = $this->extract_helpful_votes($node, $xpath);
            if ($votes > 0) {
                $review['helpful_votes'] = $votes;
            }
            
            // Extract owner response using multiple selectors
            $response = $this->extract_owner_response($node, $xpath);
            if ($response) {
                $review['owner_response'] = $response;
            }
            
            // Validate review data using the validator
            $validation_result = Google_Maps_Reviews_Validator::validate_review($review);
            
            if (!$validation_result['valid']) {
                $this->log_error('review_validation_failed', 'Review validation failed', array(
                    'errors' => $validation_result['errors'],
                    'warnings' => $validation_result['warnings'],
                    'review_id' => $review['id'] ?? 'unknown'
                ));
                return false;
            }
            
            // Use cleaned data from validation
            $review = $validation_result['cleaned_data'];
            
            // Clean and sanitize data
            $review = $this->sanitize_review_data($review);
            
            return $review;
            
        } catch (Exception $e) {
            error_log("Google Maps Reviews Widget: Error parsing review: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract review ID from DOM node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string Review ID
     */
    private function extract_review_id($node, $xpath) {
        // Try to find review ID in data attributes
        $id_nodes = $xpath->query('.//@data-review-id', $node);
        if ($id_nodes->length > 0) {
            return $id_nodes->item(0)->value;
        }
        
        // Try to extract from class names
        $class_attr = $node->getAttribute('class');
        if (preg_match('/review-(\d+)/', $class_attr, $matches)) {
            return $matches[1];
        }
        
        // Generate a unique ID based on content hash
        $content = $node->textContent;
        return 'review_' . md5($content);
    }
    
    /**
     * Extract author name from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string|false Author name or false on failure
     */
    private function extract_author_name($node, $xpath) {
        $selectors = array(
            './/div[contains(@class, "d4r55")]',
            './/div[contains(@class, "TSUbDb")]',
            './/div[contains(@class, "reviewer-name")]',
            './/span[contains(@class, "reviewer-name")]',
            './/div[contains(@class, "author-name")]',
            './/span[contains(@class, "author-name")]',
            './/div[contains(@class, "review-author")]',
            './/span[contains(@class, "review-author")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $name = trim($nodes->item(0)->textContent);
                if (!empty($name)) {
                    return $name;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Extract author image from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string|false Author image URL or false on failure
     */
    private function extract_author_image($node, $xpath) {
        $selectors = array(
            './/img[contains(@class, "lDY1rd")]',
            './/img[contains(@class, "reviewer-avatar")]',
            './/img[contains(@class, "author-avatar")]',
            './/img[contains(@class, "profile-image")]',
            './/img[contains(@class, "user-avatar")]',
            './/img[contains(@alt, "profile")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $src = $nodes->item(0)->getAttribute('src');
                if (!empty($src)) {
                    return $src;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Extract rating from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return int Rating (1-5) or 0 on failure
     */
    private function extract_rating($node, $xpath) {
        $selectors = array(
            './/span[contains(@class, "kvMYJc")]',
            './/span[contains(@class, "review-rating")]',
            './/span[contains(@class, "rating")]',
            './/div[contains(@class, "review-rating")]',
            './/div[contains(@class, "rating")]',
            './/span[contains(@aria-label, "stars")]',
            './/span[contains(@aria-label, "rating")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $node_item = $nodes->item(0);
                
                // Try aria-label first
                $aria_label = $node_item->getAttribute('aria-label');
                if (!empty($aria_label)) {
                    $rating = $this->extract_rating_from_text($aria_label);
                    if ($rating > 0) {
                        return $rating;
                    }
                }
                
                // Try text content
                $text_content = $node_item->textContent;
                if (!empty($text_content)) {
                    $rating = $this->extract_rating_from_text($text_content);
                    if ($rating > 0) {
                        return $rating;
                    }
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Extract review content from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string|false Review content or false on failure
     */
    private function extract_review_content($node, $xpath) {
        $selectors = array(
            './/span[contains(@class, "wiI7pd")]',
            './/div[contains(@class, "review-content")]',
            './/span[contains(@class, "review-content")]',
            './/div[contains(@class, "review-text")]',
            './/span[contains(@class, "review-text")]',
            './/div[contains(@class, "review-body")]',
            './/span[contains(@class, "review-body")]',
            './/div[contains(@class, "review-comment")]',
            './/span[contains(@class, "review-comment")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $content = trim($nodes->item(0)->textContent);
                if (!empty($content)) {
                    return $content;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Extract review date from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string|false Review date or false on failure
     */
    private function extract_review_date($node, $xpath) {
        $selectors = array(
            './/span[contains(@class, "rsqaWe")]',
            './/span[contains(@class, "review-date")]',
            './/div[contains(@class, "review-date")]',
            './/span[contains(@class, "date")]',
            './/div[contains(@class, "date")]',
            './/span[contains(@class, "timestamp")]',
            './/div[contains(@class, "timestamp")]',
            './/time',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $date = trim($nodes->item(0)->textContent);
                if (!empty($date)) {
                    return $date;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Extract helpful votes from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return int Number of helpful votes or 0 on failure
     */
    private function extract_helpful_votes($node, $xpath) {
        $selectors = array(
            './/span[contains(@class, "RDApEe")]',
            './/span[contains(@class, "helpful-votes")]',
            './/div[contains(@class, "helpful-votes")]',
            './/span[contains(@class, "votes")]',
            './/div[contains(@class, "votes")]',
            './/span[contains(@class, "likes")]',
            './/div[contains(@class, "likes")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $votes_text = $nodes->item(0)->textContent;
                $votes = $this->extract_number_from_text($votes_text);
                if ($votes > 0) {
                    return $votes;
                }
            }
        }
        
        return 0;
    }
    
    /**
     * Extract owner response from review node
     *
     * @param DOMNode $node Review DOM node
     * @param DOMXPath $xpath XPath object
     * @return string|false Owner response or false on failure
     */
    private function extract_owner_response($node, $xpath) {
        $selectors = array(
            './/div[contains(@class, "owner-response")]',
            './/div[contains(@class, "business-response")]',
            './/div[contains(@class, "response")]',
            './/div[contains(@class, "reply")]',
            './/div[contains(@class, "owner-reply")]',
            './/div[contains(@class, "business-reply")]',
            './/div[contains(@class, "management-response")]',
        );
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $node);
            if ($nodes->length > 0) {
                $response = trim($nodes->item(0)->textContent);
                if (!empty($response)) {
                    return $response;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Extract rating from text
     *
     * @param string $text Rating text
     * @return int Rating (1-5)
     */
    private function extract_rating_from_text($text) {
        if (preg_match('/(\d+)/', $text, $matches)) {
            $rating = intval($matches[1]);
            return max(1, min(5, $rating));
        }
        
        // Try to extract from star emoji or text
        if (strpos($text, '5') !== false || strpos($text, '★★★★★') !== false) {
            return 5;
        } elseif (strpos($text, '4') !== false || strpos($text, '★★★★☆') !== false) {
            return 4;
        } elseif (strpos($text, '3') !== false || strpos($text, '★★★☆☆') !== false) {
            return 3;
        } elseif (strpos($text, '2') !== false || strpos($text, '★★☆☆☆') !== false) {
            return 2;
        } elseif (strpos($text, '1') !== false || strpos($text, '★☆☆☆☆') !== false) {
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Extract number from text
     *
     * @param string $text Text containing number
     * @return int Extracted number
     */
    private function extract_number_from_text($text) {
        if (preg_match('/(\d+)/', $text, $matches)) {
            return intval($matches[1]);
        }
        return 0;
    }
    
    /**
     * Sanitize review data
     *
     * @param array $review Review data
     * @return array Sanitized review data
     */
    private function sanitize_review_data($review) {
        // Use the validator class for sanitization
        $sanitized = Google_Maps_Reviews_Validator::sanitize_review($review);
        
        // Additional sanitization for specific fields
        if (isset($sanitized['author_image'])) {
            $sanitized['author_image'] = esc_url_raw($sanitized['author_image']);
        }
        
        if (isset($sanitized['content'])) {
            $sanitized['content'] = sanitize_textarea_field($sanitized['content']);
        }
        
        if (isset($sanitized['owner_response'])) {
            $sanitized['owner_response'] = sanitize_textarea_field($sanitized['owner_response']);
        }
        
        return $sanitized;
    }
    
    /**
     * Apply rate limiting
     */
    private function rate_limit() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        if (empty($settings['rate_limiting'])) {
            return;
        }
        
        $current_time = time();
        $time_since_last_request = $current_time - $this->last_request_time;
        
        // Calculate dynamic delay based on request frequency
        $dynamic_delay = $this->calculate_dynamic_delay();
        
        if ($time_since_last_request < $dynamic_delay) {
            $sleep_time = $dynamic_delay - $time_since_last_request;
            
            // Log rate limiting if debugging is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Google Maps Reviews Widget: Rate limiting - sleeping for {$sleep_time} seconds");
            }
            
            sleep($sleep_time);
        }
        
        $this->last_request_time = time();
        
        // Store request history for adaptive rate limiting
        $this->store_request_history();
    }
    
    /**
     * Calculate dynamic delay based on request history
     *
     * @return int Delay in seconds
     */
    private function calculate_dynamic_delay() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        $base_delay = $this->rate_limit_delay;
        
        // Get recent request history
        $recent_requests = $this->get_recent_requests();
        $request_count = count($recent_requests);
        
        // Increase delay if too many requests in short time
        if ($request_count > 10) {
            $base_delay *= 2; // Double the delay
        } elseif ($request_count > 5) {
            $base_delay *= 1.5; // Increase by 50%
        }
        
        // Add random jitter to avoid synchronized requests
        $jitter = rand(1, 3);
        $base_delay += $jitter;
        
        // Respect maximum delay limit
        $max_delay = $settings['max_rate_limit_delay'] ?? 10;
        return min($base_delay, $max_delay);
    }
    
    /**
     * Store request history for adaptive rate limiting
     */
    private function store_request_history() {
        $history_key = 'gmrw_request_history_' . md5(site_url());
        $history = get_transient($history_key);
        
        if (!$history) {
            $history = array();
        }
        
        // Add current request timestamp
        $history[] = time();
        
        // Keep only last 20 requests
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        
        // Store for 1 hour
        set_transient($history_key, $history, 3600);
    }
    
    /**
     * Get recent request history
     *
     * @return array Array of request timestamps
     */
    private function get_recent_requests() {
        $history_key = 'gmrw_request_history_' . md5(site_url());
        $history = get_transient($history_key);
        
        if (!$history) {
            return array();
        }
        
        // Filter requests from last 5 minutes
        $cutoff_time = time() - 300;
        return array_filter($history, function($timestamp) use ($cutoff_time) {
            return $timestamp > $cutoff_time;
        });
    }
    
    /**
     * Check if we should respect robots.txt
     *
     * @param string $url URL to check
     * @return bool Whether to respect robots.txt
     */
    private function should_respect_robots_txt($url) {
        $settings = Google_Maps_Reviews_Config::get_settings();
        return !empty($settings['respect_robots_txt']);
    }
    
    /**
     * Check robots.txt for allowed/disallowed paths
     *
     * @param string $url URL to check
     * @return bool Whether the URL is allowed
     */
    private function check_robots_txt($url) {
        if (!$this->should_respect_robots_txt($url)) {
            return true; // Skip robots.txt check
        }
        
        $parsed_url = parse_url($url);
        $robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';
        
        $response = wp_remote_get($robots_url, array(
            'timeout' => 10,
            'user-agent' => $this->user_agent,
        ));
        
        if (is_wp_error($response)) {
            return true; // Allow if robots.txt is not accessible
        }
        
        $robots_content = wp_remote_retrieve_body($response);
        $path = $parsed_url['path'] ?? '/';
        
        // Simple robots.txt parsing
        $lines = explode("\n", $robots_content);
        $user_agent = 'Google Maps Reviews Widget';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $current_user_agent = trim($matches[1]);
                if ($current_user_agent === '*' || $current_user_agent === $user_agent) {
                    // Check next lines for Disallow rules
                    continue;
                }
            }
            
            if (preg_match('/^Disallow:\s*(.+)$/i', $line, $matches)) {
                $disallow_path = trim($matches[1]);
                if (strpos($path, $disallow_path) === 0) {
                    return false; // URL is disallowed
                }
            }
        }
        
        return true; // URL is allowed
    }
    
    /**
     * Get business information from Google Maps
     *
     * @param string $business_url Google Maps business URL
     * @return array|WP_Error Business information or WP_Error on failure
     */
    public function get_business_info($business_url) {
        try {
            $place_id = $this->extract_place_id($business_url);
            if (!$place_id) {
                throw new Google_Maps_Reviews_Scraping_Exception(
                    __('Could not extract place ID from URL', GMRW_TEXT_DOMAIN),
                    'PLACE_ID_EXTRACTION_FAILED'
                );
            }
            
            $url = 'https://www.google.com/maps/place/' . $place_id;
            $response = $this->make_request($url);
            
            if (is_wp_error($response)) {
                throw new Google_Maps_Reviews_Scraping_Exception(
                    $response->get_error_message(),
                    'HTTP_REQUEST_FAILED'
                );
            }
            
            return $this->parse_business_info($response['body']);
            
        } catch (Google_Maps_Reviews_Scraping_Exception $e) {
            $e->log();
            return new WP_Error('scraping_error', $e->getMessage());
        }
    }
    
    /**
     * Parse business information from HTML
     *
     * @param string $html HTML response
     * @return array Business information
     */
    private function parse_business_info($html) {
        $business_info = array(
            'name' => '',
            'address' => '',
            'phone' => '',
            'website' => '',
            'rating' => 0,
            'review_count' => 0,
            'category' => '',
            'hours' => array(),
        );
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Extract business name
        $name_nodes = $xpath->query('//h1[contains(@class, "DUwDvf")]');
        if ($name_nodes->length > 0) {
            $business_info['name'] = trim($name_nodes->item(0)->textContent);
        }
        
        // Extract address
        $address_nodes = $xpath->query('//button[contains(@data-item-id, "address")]');
        if ($address_nodes->length > 0) {
            $business_info['address'] = trim($address_nodes->item(0)->textContent);
        }
        
        // Extract phone
        $phone_nodes = $xpath->query('//button[contains(@data-item-id, "phone")]');
        if ($phone_nodes->length > 0) {
            $business_info['phone'] = trim($phone_nodes->item(0)->textContent);
        }
        
        // Extract website
        $website_nodes = $xpath->query('//a[contains(@data-item-id, "authority")]');
        if ($website_nodes->length > 0) {
            $business_info['website'] = $website_nodes->item(0)->getAttribute('href');
        }
        
        // Extract rating
        $rating_nodes = $xpath->query('//span[contains(@class, "ceNzKf")]');
        if ($rating_nodes->length > 0) {
            $rating_text = $rating_nodes->item(0)->textContent;
            $business_info['rating'] = floatval($rating_text);
        }
        
        // Extract review count
        $review_count_nodes = $xpath->query('//span[contains(@class, "F7nice")]');
        if ($review_count_nodes->length > 0) {
            $review_count_text = $review_count_nodes->item(0)->textContent;
            $business_info['review_count'] = $this->extract_number_from_text($review_count_text);
        }
        
        return $business_info;
    }
    
    /**
     * Check if an error is retryable
     *
     * @param Google_Maps_Reviews_Scraping_Exception $exception Exception to check
     * @return bool Whether the error is retryable
     */
    private function is_retryable_error($exception) {
        $retryable_codes = array(
            'HTTP_REQUEST_FAILED',
            'RATE_LIMIT_EXCEEDED',
            'TIMEOUT_ERROR',
            'CONNECTION_ERROR',
            'SERVER_ERROR',
        );
        
        return in_array($exception->get_error_code(), $retryable_codes);
    }
    
    /**
     * Log retry attempt
     *
     * @param string $business_url Business URL being retried
     * @param int $retry_count Current retry count
     * @param string $error_message Error message
     */
    private function log_retry_attempt($business_url, $retry_count, $error_message) {
        $this->log_error('retry_attempt', sprintf(
            'Retry attempt %d for URL: %s. Error: %s',
            $retry_count,
            $business_url,
            $error_message
        ), array(
            'business_url' => $business_url,
            'retry_count' => $retry_count,
            'error_message' => $error_message
        ));
    }
    
    /**
     * Log error with context
     *
     * @param string $error_type Type of error
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function log_error($error_type, $message, $context = array()) {
        $settings = Google_Maps_Reviews_Config::get_settings();
        
        if (empty($settings['enable_logging'])) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'error_type' => $error_type,
            'message' => $message,
            'context' => $context,
            'user_agent' => $this->user_agent,
            'site_url' => site_url(),
        );
        
        // Log to WordPress error log if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Google Maps Reviews Widget [{$error_type}]: {$message}");
        }
        
        // Store in database if logging is enabled
        if (!empty($settings['enable_logging'])) {
            $this->store_error_log($log_entry);
        }
    }
    
    /**
     * Store error log in database
     *
     * @param array $log_entry Log entry to store
     */
    private function store_error_log($log_entry) {
        global $wpdb;
        
        $table_name = Google_Maps_Reviews_Config::get_logs_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'level' => 'error',
                'message' => $log_entry['message'],
                'context' => json_encode($log_entry['context']),
                'error_type' => $log_entry['error_type'],
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Handle parsing errors gracefully
     *
     * @param string $html HTML content that failed to parse
     * @param string $context Context of the parsing error
     * @return array Empty array to continue processing
     */
    private function handle_parsing_error($html, $context = '') {
        $this->log_error('parsing_error', "Failed to parse HTML content: {$context}", array(
            'html_length' => strlen($html),
            'context' => $context,
            'html_preview' => substr($html, 0, 500) // First 500 characters for debugging
        ));
        
        return array(); // Return empty array to continue processing
    }
    
    /**
     * Validate HTML content before parsing
     *
     * @param string $html HTML content to validate
     * @return bool Whether the HTML is valid for parsing
     */
    private function validate_html_content($html) {
        if (empty($html)) {
            $this->log_error('validation_error', 'Empty HTML content received');
            return false;
        }
        
        if (strlen($html) < 100) {
            $this->log_error('validation_error', 'HTML content too short, likely an error page');
            return false;
        }
        
        // Check for common error indicators
        $error_indicators = array(
            'error',
            'not found',
            'access denied',
            'forbidden',
            'blocked',
            'captcha',
            'robot',
            'automated',
        );
        
        $html_lower = strtolower($html);
        foreach ($error_indicators as $indicator) {
            if (strpos($html_lower, $indicator) !== false) {
                $this->log_error('validation_error', "HTML contains error indicator: {$indicator}");
                return false;
            }
        }
        
        return true;
    }
    
         /**
      * Test if scraping is working
      *
      * @param string $business_url Test business URL
      * @return array|WP_Error Test results
      */
     public function test_scraping($business_url) {
         $results = array(
             'success' => false,
             'message' => '',
             'business_info' => null,
             'reviews_count' => 0,
             'error' => null,
             'debug_info' => array(),
         );
         
         try {
             // Test business info extraction
             $business_info = $this->get_business_info($business_url);
             if (is_wp_error($business_info)) {
                 throw new Exception($business_info->get_error_message());
             }
             
             $results['business_info'] = $business_info;
             
             // Test reviews extraction
             $reviews = $this->get_reviews($business_url, array('max_pages' => 1));
             if (is_wp_error($reviews)) {
                 throw new Exception($reviews->get_error_message());
             }
             
             $results['reviews_count'] = count($reviews);
             $results['success'] = true;
             $results['message'] = sprintf(
                 __('Successfully extracted %d reviews for %s', GMRW_TEXT_DOMAIN),
                 $results['reviews_count'],
                 $business_info['name']
             );
             
             // Add debug information
             $results['debug_info'] = array(
                 'place_id' => $this->extract_place_id($business_url),
                 'url_validation' => Google_Maps_Reviews_Config::validate_business_url($business_url),
                 'cache_status' => 'working',
                 'rate_limiting' => 'enabled',
             );
             
         } catch (Exception $e) {
             $results['error'] = $e->getMessage();
             $results['message'] = __('Scraping test failed', GMRW_TEXT_DOMAIN);
             
             // Add debug information for failed tests
             $results['debug_info'] = array(
                 'place_id' => $this->extract_place_id($business_url),
                 'url_validation' => Google_Maps_Reviews_Config::validate_business_url($business_url),
                 'error_type' => get_class($e),
                 'error_code' => method_exists($e, 'get_error_code') ? $e->get_error_code() : 'unknown',
             );
         }
         
         return $results;
     }
     
     /**
      * Simple test method to debug HTML content
      *
      * @param string $business_name Business name to test
      * @return array Debug information
      */
     public function debug_html_content($business_name) {
         $debug_info = array(
             'business_name' => $business_name,
             'direct_url' => '',
             'html_length' => 0,
             'contains_review_text' => false,
             'contains_rating_text' => false,
             'div_count' => 0,
             'review_div_count' => 0,
         );
         
         try {
             // Try direct business URL
             $direct_url = 'https://www.google.com/maps/place/' . urlencode($business_name);
             $debug_info['direct_url'] = $direct_url;
             
             $html = $this->fetch_url($direct_url);
             if ($html) {
                 $debug_info['html_length'] = strlen($html);
                 $debug_info['contains_review_text'] = strpos(strtolower($html), 'review') !== false;
                 $debug_info['contains_rating_text'] = strpos(strtolower($html), 'rating') !== false;
                 
                 // Parse HTML to count divs
                 $dom = new DOMDocument();
                 @$dom->loadHTML($html);
                 $xpath = new DOMXPath($dom);
                 
                 $all_divs = $xpath->query('//div');
                 $debug_info['div_count'] = $all_divs->length;
                 
                 $review_divs = $xpath->query('//div[contains(@class, "review") or contains(@class, "rating") or contains(@class, "star")]');
                 $debug_info['review_div_count'] = $review_divs->length;
                 
                 // Add sample of review-related divs
                 $debug_info['sample_divs'] = array();
                 for ($i = 0; $i < min(5, $review_divs->length); $i++) {
                     $div = $review_divs->item($i);
                     $debug_info['sample_divs'][] = array(
                         'class' => $div->getAttribute('class'),
                         'text_preview' => substr(trim($div->textContent), 0, 100)
                     );
                 }
             }
             
         } catch (Exception $e) {
             $debug_info['error'] = $e->getMessage();
         }
         
         return $debug_info;
     }
    
    /**
     * Fetch reviews by business name instead of place ID
     *
     * @param string $business_name Business name
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
              private function fetch_reviews_by_business_name($business_name, $options = array()) {
         try {
             // Try multiple approaches to get reviews
             
             // Approach 1: Direct business URL with reviews parameter
             $direct_url = 'https://www.google.com/maps/place/' . urlencode($business_name) . '/@0,0,15z/data=!4m8!14m7!1m6!2m5!1s!2m1!1s!3m1!1s2!4e1!5m1!1e1!6m1!1e2';
             
             $this->log_error('debug_info', 'Trying direct URL approach: ' . $direct_url, array());
             
             // Fetch the direct business page
             $html = $this->fetch_url($direct_url);
             if ($html) {
                 // Try to parse reviews directly from this page
                 $reviews = $this->parse_reviews_response($html);
                 if (!empty($reviews)) {
                     $this->log_error('debug_info', 'Successfully found ' . count($reviews) . ' reviews using direct URL approach', array());
                     return $reviews;
                 }
             }
             
             // Approach 2: Search and find business
             $this->log_error('debug_info', 'Direct approach failed, trying search approach', array());
             $search_url = 'https://www.google.com/maps/search/' . urlencode($business_name);
             
             // Fetch the search results page
             $html = $this->fetch_url($search_url);
             if (!$html) {
                 throw new Google_Maps_Reviews_Scraping_Exception(
                     __('Failed to fetch search results page', GMRW_TEXT_DOMAIN),
                     'FETCH_FAILED'
                 );
             }
             
             // Extract the first business URL from search results
             $business_url = $this->extract_first_business_url($html, $business_name);
             if (!$business_url) {
                 throw new Google_Maps_Reviews_Scraping_Exception(
                     __('Could not find business in search results', GMRW_TEXT_DOMAIN),
                     'BUSINESS_NOT_FOUND'
                 );
             }
             
             // Now fetch reviews from the found business URL
             return $this->fetch_reviews_from_business_page($business_url, $options);
             
         } catch (Exception $e) {
             $this->log_error('business_name_fetch_error', $e->getMessage(), array(
                 'business_name' => $business_name,
                 'options' => $options
             ));
             
             return new WP_Error('business_name_fetch_error', $e->getMessage());
         }
     }
    
    /**
     * Extract the first business URL from search results
     *
     * @param string $html Search results HTML
     * @param string $business_name Business name to match
     * @return string|false Business URL or false on failure
     */
    private function extract_first_business_url($html, $business_name) {
        // Use DOMDocument to parse the HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Look for business links in search results
        $business_links = $xpath->query('//a[contains(@href, "/maps/place/")]');
        
        foreach ($business_links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);
            
            // Check if this link matches our business name
            if (stripos($text, $business_name) !== false || 
                stripos($href, urlencode($business_name)) !== false) {
                return 'https://www.google.com' . $href;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch reviews from a business page URL
     *
     * @param string $business_url Business page URL
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    private function fetch_reviews_from_business_page($business_url, $options = array()) {
        // Add reviews parameter to URL
        $reviews_url = $business_url . '?hl=en#lrd=0x0:0x0,0';
        
        // Fetch the reviews page
        $html = $this->fetch_url($reviews_url);
        if (!$html) {
            throw new Google_Maps_Reviews_Scraping_Exception(
                __('Failed to fetch reviews page', GMRW_TEXT_DOMAIN),
                'FETCH_FAILED'
            );
        }
        
                 // Parse reviews from the HTML
         return $this->parse_reviews_response($html);
     }
     
     /**
      * Fetch URL content
      *
      * @param string $url URL to fetch
      * @return string|false HTML content or false on failure
      */
     private function fetch_url($url) {
         $response = $this->make_request($url);
         
         if (is_wp_error($response)) {
             return false;
         }
         
         return wp_remote_retrieve_body($response);
     }
 }
