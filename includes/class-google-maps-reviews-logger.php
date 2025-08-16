<?php
/**
 * Logger Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles logging operations for the plugin
 */
class Google_Maps_Reviews_Logger {
    
    /**
     * Log levels
     */
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';
    
    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;
    
    /**
     * Maximum log file size in bytes
     *
     * @var int
     */
    private $max_file_size = 5242880; // 5MB
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/logs/gmrw-debug.log';
        $this->ensure_log_directory();
    }
    
    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param string $level The log level
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function log($message, $level = self::LOG_LEVEL_INFO, $context = array()) {
        if (!$this->is_logging_enabled()) {
            return false;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        
        $log_entry = sprintf(
            '[%s] [%s] %s',
            $timestamp,
            $level_upper,
            $message
        );
        
        // Add context if provided
        if (!empty($context)) {
            $log_entry .= ' Context: ' . json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        // Check file size before writing
        if ($this->should_rotate_log()) {
            $this->rotate_log();
        }
        
        return file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Log an error message
     *
     * @param string $message The error message
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function error($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_ERROR, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message The warning message
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function warning($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_WARNING, $context);
    }
    
    /**
     * Log an info message
     *
     * @param string $message The info message
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function info($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_INFO, $context);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message The debug message
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function debug($message, $context = array()) {
        return $this->log($message, self::LOG_LEVEL_DEBUG, $context);
    }
    
    /**
     * Log an exception
     *
     * @param Exception $exception The exception to log
     * @param array $context Additional context data
     * @return bool True on success, false on failure
     */
    public function exception($exception, $context = array()) {
        $message = sprintf(
            'Exception: %s in %s on line %d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        $context['trace'] = $exception->getTraceAsString();
        
        return $this->error($message, $context);
    }
    
    /**
     * Get log file contents
     *
     * @param int $lines Number of lines to return (0 for all)
     * @return string|false Log contents or false on failure
     */
    public function get_log_contents($lines = 0) {
        if (!file_exists($this->log_file)) {
            return false;
        }
        
        $contents = file_get_contents($this->log_file);
        
        if ($contents === false) {
            return false;
        }
        
        if ($lines > 0) {
            $lines_array = explode(PHP_EOL, $contents);
            $lines_array = array_filter($lines_array); // Remove empty lines
            $lines_array = array_slice($lines_array, -$lines);
            $contents = implode(PHP_EOL, $lines_array);
        }
        
        return $contents;
    }
    
    /**
     * Clear log file
     *
     * @return bool True on success, false on failure
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            return file_put_contents($this->log_file, '') !== false;
        }
        
        return true;
    }
    
    /**
     * Get log file size
     *
     * @return int|false File size in bytes or false on failure
     */
    public function get_log_size() {
        if (!file_exists($this->log_file)) {
            return 0;
        }
        
        return filesize($this->log_file);
    }
    
    /**
     * Get log file size formatted
     *
     * @return string Formatted file size
     */
    public function get_log_size_formatted() {
        $size = $this->get_log_size();
        
        if ($size === false) {
            return '0 B';
        }
        
        return size_format($size);
    }
    
    /**
     * Check if logging is enabled
     *
     * @return bool True if logging is enabled, false otherwise
     */
    private function is_logging_enabled() {
        $settings = Google_Maps_Reviews_Config::get_settings();
        return !empty($settings['enable_logging']);
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensure_log_directory() {
        $log_dir = dirname($this->log_file);
        
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Create .htaccess to protect logs
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
    
    /**
     * Check if log file should be rotated
     *
     * @return bool True if log should be rotated, false otherwise
     */
    private function should_rotate_log() {
        if (!file_exists($this->log_file)) {
            return false;
        }
        
        return filesize($this->log_file) > $this->max_file_size;
    }
    
    /**
     * Rotate log file
     */
    private function rotate_log() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        
        if (rename($this->log_file, $backup_file)) {
            // Keep only the last 5 backup files
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $pattern = $log_dir . '/gmrw-debug.log.*.bak';
        
        $backup_files = glob($pattern);
        
        if (count($backup_files) > 5) {
            // Sort by modification time (oldest first)
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($backup_files, 0, count($backup_files) - 5);
            
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get log statistics
     *
     * @return array Log statistics
     */
    public function get_log_stats() {
        $contents = $this->get_log_contents();
        
        if ($contents === false) {
            return array(
                'total_lines' => 0,
                'error_count' => 0,
                'warning_count' => 0,
                'info_count' => 0,
                'debug_count' => 0,
                'file_size' => 0,
                'file_size_formatted' => '0 B'
            );
        }
        
        $lines = explode(PHP_EOL, $contents);
        $lines = array_filter($lines); // Remove empty lines
        
        $error_count = 0;
        $warning_count = 0;
        $info_count = 0;
        $debug_count = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, '[ERROR]') !== false) {
                $error_count++;
            } elseif (strpos($line, '[WARNING]') !== false) {
                $warning_count++;
            } elseif (strpos($line, '[INFO]') !== false) {
                $info_count++;
            } elseif (strpos($line, '[DEBUG]') !== false) {
                $debug_count++;
            }
        }
        
        return array(
            'total_lines' => count($lines),
            'error_count' => $error_count,
            'warning_count' => $warning_count,
            'info_count' => $info_count,
            'debug_count' => $debug_count,
            'file_size' => $this->get_log_size(),
            'file_size_formatted' => $this->get_log_size_formatted()
        );
    }
}
