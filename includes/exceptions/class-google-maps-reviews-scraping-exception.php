<?php
/**
 * Scraping Exception Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown when scraping fails
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Scraping_Exception extends Google_Maps_Reviews_Exception {

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $message Error message
     * @param string $code Error code
     * @param array $data Additional data
     * @param Exception $previous Previous exception
     */
    public function __construct($message = '', $code = 'SCRAPING_ERROR', $data = array(), $previous = null) {
        parent::__construct($message, $code, $data, $previous);
    }
}
