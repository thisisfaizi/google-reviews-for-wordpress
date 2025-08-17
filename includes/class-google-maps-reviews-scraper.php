<?php
/**
 * Google Maps Reviews Scraper Class
 *
 * Uses Symfony Panther for browser automation to scrape Google Maps reviews
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Google_Maps_Reviews_Scraper {
    
    /**
     * Logger instance
     *
     * @var Google_Maps_Reviews_Logger
     */
    private $logger;
    
    /**
     * Configuration settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new Google_Maps_Reviews_Logger();
        $this->settings = Google_Maps_Reviews_Config::get_settings();
    }
    
    /**
     * Get reviews from Google Maps business URL using Panther browser automation
     *
     * @param string $business_url Google Maps business URL
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    public function get_reviews($business_url, $options = array()) {
        try {
            // Validate business URL
            if (!Google_Maps_Reviews_Config::validate_business_url($business_url)) {
                return new WP_Error('invalid_url', __('Invalid Google Maps business URL', GMRW_TEXT_DOMAIN));
            }
            
            // Check cache first
            $cache = new Google_Maps_Reviews_Cache();
            $cached_reviews = $cache->get_reviews($business_url);
            
            if ($cached_reviews !== false) {
                return $cached_reviews;
            }
            
            // Parse business URL to get business name
            $business_name = Google_Maps_Reviews_Config::extract_business_name_from_url($business_url);
            if (!$business_name) {
                return new WP_Error('business_name_extraction_failed', __('Could not extract business name from URL', GMRW_TEXT_DOMAIN));
            }
            
            $this->logger->log_info('panther_scraping_started', 'Starting Panther-based review scraping', array(
                'business_url' => $business_url,
                'business_name' => $business_name,
                'options' => $options
            ));
            
            // Use Panther scraper as the only method
            $panther_reviews = $this->try_panther_scraper($business_name, $options);
            
            if (!is_wp_error($panther_reviews) && !empty($panther_reviews)) {
                // Cache the results
                $cache->set_reviews($business_url, $panther_reviews);
                
                $this->logger->log_info('panther_scraping_success', 'Successfully found reviews using Panther', array(
                    'review_count' => count($panther_reviews)
                ));
                return $panther_reviews;
            }
            
            return new WP_Error('no_reviews_found', 'No reviews found for this business using Panther browser automation.');
            
        } catch (Exception $e) {
            $this->logger->log_error('scraping_exception', 'Exception during Panther scraping: ' . $e->getMessage(), array(
                'business_url' => $business_url,
                'exception' => $e->getMessage()
            ));
            return new WP_Error('scraping_exception', $e->getMessage());
        }
    }
    
    /**
     * Try using Panther browser automation to scrape reviews
     *
     * @param string $business_name Business name
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    private function try_panther_scraper($business_name, $options = array()) {
        try {
            // Check if Panther scraper is available
            if (!class_exists('Google_Maps_Reviews_Panther_Scraper')) {
                $this->logger->log_error('panther_not_available', 'Panther scraper class not available');
                return new WP_Error('panther_not_available', 'Panther scraper not available');
            }
            
            $panther_scraper = new Google_Maps_Reviews_Panther_Scraper();
            
            if (!$panther_scraper->is_available()) {
                $this->logger->log_error('panther_not_available', 'Panther scraper not available');
                return new WP_Error('panther_not_available', 'Panther scraper not available');
            }
            
            $this->logger->log_info('panther_scraping_attempt', 'Attempting to scrape reviews using Panther', array(
                'business_name' => $business_name,
                'options' => $options
            ));
            
            // Construct a business URL for the Panther scraper
            $business_url = 'https://www.google.com/maps/place/' . urlencode($business_name);
            
            // Get reviews using Panther
            $reviews = $panther_scraper->get_reviews($business_url, $options);
            
            if (is_wp_error($reviews)) {
                $this->logger->log_error('panther_error', $reviews->get_error_message(), array(
                    'business_name' => $business_name,
                    'options' => $options
                ));
                return $reviews;
            }
            
            if (!empty($reviews)) {
                $this->logger->log_info('panther_success', 'Successfully found ' . count($reviews) . ' reviews using Panther scraper', array());
                return $reviews;
            }
            
            return new WP_Error('no_reviews_found', 'No reviews found using Panther scraper');
            
        } catch (Exception $e) {
            $this->logger->log_error('panther_exception', 'Panther scraper exception: ' . $e->getMessage(), array(
                'business_name' => $business_name,
                'exception' => $e->getMessage()
            ));
            return new WP_Error('panther_exception', $e->getMessage());
        }
    }
    
    /**
     * Check if Panther is available
     *
     * @return bool True if Panther is available
     */
    public function is_panther_available() {
        if (!class_exists('Google_Maps_Reviews_Panther_Scraper')) {
            return false;
        }
        
        $panther_scraper = new Google_Maps_Reviews_Panther_Scraper();
        return $panther_scraper->is_available();
    }
    
    /**
     * Get Panther status information
     *
     * @return array Status information
     */
    public function get_panther_status() {
        if (!class_exists('Google_Maps_Reviews_Panther_Scraper')) {
            return array(
                'panther_available' => false,
                'client_initialized' => false,
                'chrome_driver_available' => false,
                'error' => 'Panther scraper class not found'
            );
        }
        
        $panther_scraper = new Google_Maps_Reviews_Panther_Scraper();
        return $panther_scraper->get_status();
    }
}
