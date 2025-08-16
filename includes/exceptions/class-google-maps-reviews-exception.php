<?php
/**
 * Base Exception Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base exception class for Google Maps Reviews Widget
 *
 * @since 1.0.0
 */
class Google_Maps_Reviews_Exception extends Exception {

    /**
     * Error code
     *
     * @since 1.0.0
     * @var string
     */
    protected $error_code;

    /**
     * Additional data
     *
     * @since 1.0.0
     * @var array
     */
    protected $data;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $message Error message
     * @param string $code Error code
     * @param array $data Additional data
     * @param Exception $previous Previous exception
     */
    public function __construct($message = '', $code = '', $data = array(), $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->error_code = $code;
        $this->data = $data;
    }

    /**
     * Get error code
     *
     * @since 1.0.0
     * @return string
     */
    public function get_error_code() {
        return $this->error_code;
    }

    /**
     * Get additional data
     *
     * @since 1.0.0
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get formatted error message
     *
     * @since 1.0.0
     * @return string
     */
    public function get_formatted_message() {
        $message = $this->getMessage();
        
        if (!empty($this->error_code)) {
            $message = sprintf('[%s] %s', $this->error_code, $message);
        }
        
        return $message;
    }

    /**
     * Log the exception
     *
     * @since 1.0.0
     */
    public function log() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Google Maps Reviews Widget Exception: %s in %s:%d',
                $this->get_formatted_message(),
                $this->getFile(),
                $this->getLine()
            ));
        }
    }
}
