<?php
/**
 * Cache Exception Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown when cache operations fail
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Cache_Exception extends Google_Maps_Reviews_Exception {

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $message Error message
     * @param string $code Error code
     * @param array $data Additional data
     * @param Exception $previous Previous exception
     */
    public function __construct($message = '', $code = 'CACHE_ERROR', $data = array(), $previous = null) {
        parent::__construct($message, $code, $data, $previous);
    }
}
