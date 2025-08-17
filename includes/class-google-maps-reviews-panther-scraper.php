<?php
/**
 * Panther-based Google Maps Reviews Scraper
 *
 * Uses Symfony Panther for browser automation to scrape Google Maps reviews
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Panther-based scraper for Google Maps reviews
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Panther_Scraper {

    /**
     * Panther client instance
     *
     * @since 1.0.0
     * @var \Symfony\Component\Panther\PantherTestCase
     */
    private $client;

    /**
     * Logger instance
     *
     * @since 1.0.0
     * @var Google_Maps_Reviews_Logger
     */
    private $logger;

    /**
     * Configuration settings
     *
     * @since 1.0.0
     * @var array
     */
    private $settings;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Google_Maps_Reviews_Logger();
        $this->settings = Google_Maps_Reviews_Config::get_settings();
        $this->init_panther();
    }

    /**
     * Initialize Panther client
     *
     * @since 1.0.0
     */
    private function init_panther() {
        try {
            // Check if Panther is available
            if (!class_exists('\Symfony\Component\Panther\PantherTestCase')) {
                $this->logger->log_error('panther_not_available', 'Symfony Panther library not available');
                return false;
            }

            // Initialize Panther with Chrome options
            $options = [
                'chrome' => [
                    'args' => [
                        '--no-sandbox',
                        '--disable-dev-shm-usage',
                        '--disable-gpu',
                        '--disable-web-security',
                        '--disable-features=VizDisplayCompositor',
                        '--disable-extensions',
                        '--disable-plugins',
                        '--disable-images',
                        '--disable-javascript',
                        '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ]
                ]
            ];

            $this->client = \Symfony\Component\Panther\PantherTestCase::createPantherClient($options);
            return true;

        } catch (Exception $e) {
            $this->logger->log_error('panther_init_failed', 'Failed to initialize Panther client: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reviews using Panther browser automation
     *
     * @since 1.0.0
     * @param string $business_url Google Maps business URL
     * @param array $options Additional options
     * @return array|WP_Error Array of reviews or WP_Error on failure
     */
    public function get_reviews($business_url, $options = array()) {
        if (!$this->client) {
            return new WP_Error('panther_not_available', 'Panther client not available');
        }

        try {
            $this->logger->log_info('panther_scraping_started', 'Starting Panther-based scraping', array(
                'business_url' => $business_url,
                'options' => $options
            ));

            // Navigate to the business page
            $this->client->request('GET', $business_url);
            
            // Wait for page to load
            $this->client->wait(3000);

            // Check if we need to handle any popups or overlays
            $this->handle_popups();

            // Try to find and click on reviews section
            $reviews_found = $this->navigate_to_reviews();

            if (!$reviews_found) {
                return new WP_Error('no_reviews_section', 'Could not find reviews section on the page');
            }

            // Extract reviews from the page
            $reviews = $this->extract_reviews_from_page();

            if (empty($reviews)) {
                return new WP_Error('no_reviews_found', 'No reviews found on the page');
            }

            $this->logger->log_info('panther_scraping_success', 'Successfully scraped reviews using Panther', array(
                'review_count' => count($reviews)
            ));

            return $reviews;

        } catch (Exception $e) {
            $this->logger->log_error('panther_scraping_failed', 'Panther scraping failed: ' . $e->getMessage());
            return new WP_Error('panther_error', $e->getMessage());
        } finally {
            // Always close the client
            if ($this->client) {
                $this->client->quit();
            }
        }
    }

    /**
     * Handle any popups or overlays that might appear
     *
     * @since 1.0.0
     */
    private function handle_popups() {
        try {
            // Common popup selectors
            $popup_selectors = [
                'button[aria-label="Close"]',
                '.modal-close',
                '.popup-close',
                '[data-dismiss="modal"]',
                '.close-button',
                'button[class*="close"]'
            ];

            foreach ($popup_selectors as $selector) {
                try {
                    $element = $this->client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($selector));
                    if ($element && $element->isDisplayed()) {
                        $element->click();
                        $this->client->wait(1000);
                    }
                } catch (Exception $e) {
                    // Element not found or not clickable, continue
                    continue;
                }
            }
        } catch (Exception $e) {
            // Ignore popup handling errors
        }
    }

    /**
     * Navigate to the reviews section
     *
     * @since 1.0.0
     * @return bool True if reviews section found and clicked
     */
    private function navigate_to_reviews() {
        try {
            // Try different selectors for reviews section
            $review_selectors = [
                'a[href*="reviews"]',
                'button[aria-label*="reviews"]',
                '[data-tab-index="1"]', // Often reviews tab
                '.section-reviews',
                '.reviews-tab',
                'a:contains("Reviews")',
                'button:contains("Reviews")'
            ];

            foreach ($review_selectors as $selector) {
                try {
                    $element = $this->client->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($selector));
                    if ($element && $element->isDisplayed()) {
                        $element->click();
                        $this->client->wait(3000); // Wait for reviews to load
                        return true;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // If no reviews button found, try to scroll to reviews section
            return $this->scroll_to_reviews_section();

        } catch (Exception $e) {
            $this->logger->log_error('navigate_reviews_failed', 'Failed to navigate to reviews: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Scroll to reviews section if no button found
     *
     * @since 1.0.0
     * @return bool True if reviews section found
     */
    private function scroll_to_reviews_section() {
        try {
            // Scroll down to find reviews
            $this->client->executeScript('window.scrollTo(0, document.body.scrollHeight);');
            $this->client->wait(2000);

            // Look for review elements
            $review_elements = $this->client->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector('.jftiEf, .review-item, [data-review-id]'));
            
            return !empty($review_elements);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extract reviews from the current page
     *
     * @since 1.0.0
     * @return array Array of review data
     */
    private function extract_reviews_from_page() {
        $reviews = array();

        try {
            // Multiple selectors for review containers
            $review_selectors = [
                '.jftiEf',
                '.g88MCb',
                '[data-review-id]',
                '.review-item',
                '.review-container'
            ];

            $review_elements = array();
            foreach ($review_selectors as $selector) {
                $elements = $this->client->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector($selector));
                if (!empty($elements)) {
                    $review_elements = $elements;
                    break;
                }
            }

            if (empty($review_elements)) {
                $this->logger->log_error('no_review_elements', 'No review elements found on page');
                return $reviews;
            }

            foreach ($review_elements as $element) {
                try {
                    $review = $this->extract_single_review($element);
                    if ($review) {
                        $reviews[] = $review;
                    }
                } catch (Exception $e) {
                    $this->logger->log_error('extract_review_failed', 'Failed to extract single review: ' . $e->getMessage());
                    continue;
                }
            }

        } catch (Exception $e) {
            $this->logger->log_error('extract_reviews_failed', 'Failed to extract reviews: ' . $e->getMessage());
        }

        return $reviews;
    }

    /**
     * Extract data from a single review element
     *
     * @since 1.0.0
     * @param \Facebook\WebDriver\Remote\RemoteWebElement $element Review element
     * @return array|false Review data or false on failure
     */
    private function extract_single_review($element) {
        try {
            $review = array();

            // Extract review ID
            $review_id = $element->getAttribute('data-review-id');
            if (!$review_id) {
                $review_id = uniqid('review_');
            }
            $review['id'] = $review_id;

            // Extract author name
            try {
                $author_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.d4r55, .reviewer-name, [aria-label]'));
                $review['author_name'] = $author_element->getText() ?: $author_element->getAttribute('aria-label') ?: 'Anonymous';
            } catch (Exception $e) {
                $review['author_name'] = 'Anonymous';
            }

            // Extract author image
            try {
                $image_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('img.NBa7we, .reviewer-image img, img[alt*="Photo"]'));
                $review['author_image'] = $image_element->getAttribute('src') ?: '';
            } catch (Exception $e) {
                $review['author_image'] = '';
            }

            // Extract rating
            try {
                $rating_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.kvMYJc, .rating, [aria-label*="stars"]'));
                $rating_text = $rating_element->getAttribute('aria-label') ?: $rating_element->getText();
                $review['rating'] = $this->extract_rating_from_text($rating_text);
            } catch (Exception $e) {
                $review['rating'] = 0;
            }

            // Extract review content
            try {
                $content_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.MyEned, .review-text, .wiI7pd'));
                $review['content'] = $content_element->getText() ?: '';
            } catch (Exception $e) {
                $review['content'] = '';
            }

            // Extract date
            try {
                $date_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.rsqaWe, .review-date, .timestamp'));
                $review['date'] = $date_element->getText() ?: '';
            } catch (Exception $e) {
                $review['date'] = '';
            }

            // Extract helpful votes
            try {
                $votes_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.NlVald, .helpful-votes'));
                $votes_text = $votes_element->getText();
                $review['helpful_votes'] = $this->extract_number_from_text($votes_text);
            } catch (Exception $e) {
                $review['helpful_votes'] = 0;
            }

            // Extract owner response
            try {
                $response_element = $element->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.owner-response, .business-response'));
                $review['owner_response'] = $response_element->getText() ?: '';
            } catch (Exception $e) {
                $review['owner_response'] = '';
            }

            // Set default values
            $review['language'] = 'en';
            $review['source'] = 'google_maps';

            return $review;

        } catch (Exception $e) {
            $this->logger->log_error('extract_single_review_failed', 'Failed to extract review data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract rating from text
     *
     * @since 1.0.0
     * @param string $text Rating text
     * @return int Rating value
     */
    private function extract_rating_from_text($text) {
        if (preg_match('/(\d+)\s*stars?/i', $text, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    /**
     * Extract number from text
     *
     * @since 1.0.0
     * @param string $text Text containing number
     * @return int Number value
     */
    private function extract_number_from_text($text) {
        if (preg_match('/(\d+)/', $text, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    /**
     * Check if Panther is available
     *
     * @since 1.0.0
     * @return bool True if Panther is available
     */
    public function is_available() {
        return class_exists('\Symfony\Component\Panther\PantherTestCase') && $this->client !== null;
    }

    /**
     * Get Panther client status
     *
     * @since 1.0.0
     * @return array Status information
     */
    public function get_status() {
        return array(
            'panther_available' => class_exists('\Symfony\Component\Panther\PantherTestCase'),
            'client_initialized' => $this->client !== null,
            'chrome_driver_available' => $this->check_chrome_driver(),
        );
    }

    /**
     * Check if Chrome driver is available
     *
     * @since 1.0.0
     * @return bool True if Chrome driver is available
     */
    private function check_chrome_driver() {
        try {
            $chrome_driver_path = getenv('CHROME_DRIVER_PATH') ?: '/usr/local/bin/chromedriver';
            return file_exists($chrome_driver_path) || shell_exec('which chromedriver') !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}
