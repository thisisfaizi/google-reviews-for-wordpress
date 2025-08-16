<?php
/**
 * Review Data Validator Class
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Google_Maps_Reviews_Validator {
    
    /**
     * Review data structure schema
     *
     * @var array
     */
    private static $review_schema = array(
        'id' => array(
            'type' => 'string',
            'required' => true,
            'max_length' => 255,
            'pattern' => '/^[a-zA-Z0-9_-]+$/',
        ),
        'author_name' => array(
            'type' => 'string',
            'required' => true,
            'max_length' => 100,
            'min_length' => 1,
        ),
        'author_image' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 500,
            'pattern' => '/^https?:\/\/.+/',
        ),
        'rating' => array(
            'type' => 'integer',
            'required' => true,
            'min' => 1,
            'max' => 5,
        ),
        'content' => array(
            'type' => 'string',
            'required' => true,
            'max_length' => 5000,
            'min_length' => 1,
        ),
        'date' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 100,
        ),
        'helpful_votes' => array(
            'type' => 'integer',
            'required' => false,
            'min' => 0,
        ),
        'owner_response' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 2000,
        ),
        'language' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 10,
            'default' => 'en',
        ),
    );
    
    /**
     * Business info data structure schema
     *
     * @var array
     */
    private static $business_schema = array(
        'name' => array(
            'type' => 'string',
            'required' => true,
            'max_length' => 200,
            'min_length' => 1,
        ),
        'address' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 500,
        ),
        'phone' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 50,
        ),
        'website' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 500,
            'pattern' => '/^https?:\/\/.+/',
        ),
        'rating' => array(
            'type' => 'float',
            'required' => false,
            'min' => 0,
            'max' => 5,
        ),
        'review_count' => array(
            'type' => 'integer',
            'required' => false,
            'min' => 0,
        ),
        'category' => array(
            'type' => 'string',
            'required' => false,
            'max_length' => 100,
        ),
        'hours' => array(
            'type' => 'array',
            'required' => false,
        ),
    );
    
    /**
     * Validate a single review
     *
     * @param array $review Review data to validate
     * @return array Validation result with errors and warnings
     */
    public static function validate_review($review) {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'cleaned_data' => array(),
        );
        
        if (!is_array($review)) {
            $result['valid'] = false;
            $result['errors'][] = __('Review data must be an array', GMRW_TEXT_DOMAIN);
            return $result;
        }
        
        foreach (self::$review_schema as $field => $rules) {
            $value = isset($review[$field]) ? $review[$field] : null;
            $field_result = self::validate_field($field, $value, $rules);
            
            if (!empty($field_result['errors'])) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $field_result['errors']);
            }
            
            if (!empty($field_result['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $field_result['warnings']);
            }
            
            if (isset($field_result['cleaned_value'])) {
                $result['cleaned_data'][$field] = $field_result['cleaned_value'];
            }
        }
        
        // Additional business logic validation
        $business_errors = self::validate_review_business_logic($review);
        if (!empty($business_errors)) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $business_errors);
        }
        
        return $result;
    }
    
    /**
     * Validate business information
     *
     * @param array $business_info Business info data to validate
     * @return array Validation result with errors and warnings
     */
    public static function validate_business_info($business_info) {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'cleaned_data' => array(),
        );
        
        if (!is_array($business_info)) {
            $result['valid'] = false;
            $result['errors'][] = __('Business info data must be an array', GMRW_TEXT_DOMAIN);
            return $result;
        }
        
        foreach (self::$business_schema as $field => $rules) {
            $value = isset($business_info[$field]) ? $business_info[$field] : null;
            $field_result = self::validate_field($field, $value, $rules);
            
            if (!empty($field_result['errors'])) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $field_result['errors']);
            }
            
            if (!empty($field_result['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $field_result['warnings']);
            }
            
            if (isset($field_result['cleaned_value'])) {
                $result['cleaned_data'][$field] = $field_result['cleaned_value'];
            }
        }
        
        return $result;
    }
    
    /**
     * Validate a single field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rules Validation rules
     * @return array Validation result
     */
    private static function validate_field($field, $value, $rules) {
        $result = array(
            'errors' => array(),
            'warnings' => array(),
            'cleaned_value' => null,
        );
        
        // Check if field is required
        if (!empty($rules['required']) && (is_null($value) || $value === '')) {
            $result['errors'][] = sprintf(__('Field "%s" is required', GMRW_TEXT_DOMAIN), $field);
            return $result;
        }
        
        // Skip validation for empty optional fields
        if (empty($rules['required']) && (is_null($value) || $value === '')) {
            if (isset($rules['default'])) {
                $result['cleaned_value'] = $rules['default'];
            }
            return $result;
        }
        
        // Type validation
        $type_result = self::validate_field_type($field, $value, $rules['type']);
        if (!empty($type_result['errors'])) {
            $result['errors'] = array_merge($result['errors'], $type_result['errors']);
            return $result;
        }
        
        $cleaned_value = $type_result['cleaned_value'];
        
        // Length validation for strings
        if ($rules['type'] === 'string') {
            $length_result = self::validate_string_length($field, $cleaned_value, $rules);
            if (!empty($length_result['errors'])) {
                $result['errors'] = array_merge($result['errors'], $length_result['errors']);
            }
            if (!empty($length_result['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $length_result['warnings']);
            }
        }
        
        // Range validation for numbers
        if (in_array($rules['type'], array('integer', 'float'))) {
            $range_result = self::validate_number_range($field, $cleaned_value, $rules);
            if (!empty($range_result['errors'])) {
                $result['errors'] = array_merge($result['errors'], $range_result['errors']);
            }
        }
        
        // Pattern validation
        if (isset($rules['pattern'])) {
            $pattern_result = self::validate_pattern($field, $cleaned_value, $rules['pattern']);
            if (!empty($pattern_result['errors'])) {
                $result['errors'] = array_merge($result['errors'], $pattern_result['errors']);
            }
        }
        
        $result['cleaned_value'] = $cleaned_value;
        return $result;
    }
    
    /**
     * Validate field type
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $type Expected type
     * @return array Validation result
     */
    private static function validate_field_type($field, $value, $type) {
        $result = array(
            'errors' => array(),
            'cleaned_value' => $value,
        );
        
        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    $result['errors'][] = sprintf(__('Field "%s" must be a string', GMRW_TEXT_DOMAIN), $field);
                }
                break;
                
            case 'integer':
                if (!is_numeric($value) || (string)(int)$value !== (string)$value) {
                    $result['errors'][] = sprintf(__('Field "%s" must be an integer', GMRW_TEXT_DOMAIN), $field);
                } else {
                    $result['cleaned_value'] = (int)$value;
                }
                break;
                
            case 'float':
                if (!is_numeric($value)) {
                    $result['errors'][] = sprintf(__('Field "%s" must be a number', GMRW_TEXT_DOMAIN), $field);
                } else {
                    $result['cleaned_value'] = (float)$value;
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    $result['errors'][] = sprintf(__('Field "%s" must be an array', GMRW_TEXT_DOMAIN), $field);
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * Validate string length
     *
     * @param string $field Field name
     * @param string $value Field value
     * @param array $rules Validation rules
     * @return array Validation result
     */
    private static function validate_string_length($field, $value, $rules) {
        $result = array(
            'errors' => array(),
            'warnings' => array(),
        );
        
        $length = strlen($value);
        
        if (isset($rules['min_length']) && $length < $rules['min_length']) {
            $result['errors'][] = sprintf(
                __('Field "%s" must be at least %d characters long', GMRW_TEXT_DOMAIN),
                $field,
                $rules['min_length']
            );
        }
        
        if (isset($rules['max_length']) && $length > $rules['max_length']) {
            $result['warnings'][] = sprintf(
                __('Field "%s" is longer than recommended (%d characters)', GMRW_TEXT_DOMAIN),
                $field,
                $rules['max_length']
            );
        }
        
        return $result;
    }
    
    /**
     * Validate number range
     *
     * @param string $field Field name
     * @param numeric $value Field value
     * @param array $rules Validation rules
     * @return array Validation result
     */
    private static function validate_number_range($field, $value, $rules) {
        $result = array(
            'errors' => array(),
        );
        
        if (isset($rules['min']) && $value < $rules['min']) {
            $result['errors'][] = sprintf(
                __('Field "%s" must be at least %s', GMRW_TEXT_DOMAIN),
                $field,
                $rules['min']
            );
        }
        
        if (isset($rules['max']) && $value > $rules['max']) {
            $result['errors'][] = sprintf(
                __('Field "%s" must be at most %s', GMRW_TEXT_DOMAIN),
                $field,
                $rules['max']
            );
        }
        
        return $result;
    }
    
    /**
     * Validate pattern
     *
     * @param string $field Field name
     * @param string $value Field value
     * @param string $pattern Regex pattern
     * @return array Validation result
     */
    private static function validate_pattern($field, $value, $pattern) {
        $result = array(
            'errors' => array(),
        );
        
        if (!preg_match($pattern, $value)) {
            $result['errors'][] = sprintf(
                __('Field "%s" does not match the required format', GMRW_TEXT_DOMAIN),
                $field
            );
        }
        
        return $result;
    }
    
    /**
     * Validate review business logic
     *
     * @param array $review Review data
     * @return array Business logic errors
     */
    private static function validate_review_business_logic($review) {
        $errors = array();
        
        // Check if review has minimum required content
        if (isset($review['content']) && strlen(trim($review['content'])) < 10) {
            $errors[] = __('Review content is too short', GMRW_TEXT_DOMAIN);
        }
        
        // Check if rating is reasonable for content length
        if (isset($review['rating']) && isset($review['content'])) {
            $content_length = strlen(trim($review['content']));
            if ($review['rating'] <= 2 && $content_length < 20) {
                $errors[] = __('Low rating reviews should have more detailed content', GMRW_TEXT_DOMAIN);
            }
        }
        
        // Check for suspicious patterns
        if (isset($review['author_name']) && isset($review['content'])) {
            if (strlen($review['author_name']) < 2) {
                $errors[] = __('Author name is too short', GMRW_TEXT_DOMAIN);
            }
            
            // Check for repetitive content
            $words = explode(' ', strtolower($review['content']));
            $word_count = array_count_values($words);
            $max_repetition = max($word_count);
            if ($max_repetition > count($words) * 0.3) {
                $errors[] = __('Review content appears to be repetitive', GMRW_TEXT_DOMAIN);
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize review data
     *
     * @param array $review Raw review data
     * @return array Sanitized review data
     */
    public static function sanitize_review($review) {
        $sanitized = array();
        
        foreach (self::$review_schema as $field => $rules) {
            if (isset($review[$field])) {
                $value = $review[$field];
                
                switch ($rules['type']) {
                    case 'string':
                        $sanitized[$field] = sanitize_text_field($value);
                        break;
                    case 'integer':
                        $sanitized[$field] = intval($value);
                        break;
                    case 'float':
                        $sanitized[$field] = floatval($value);
                        break;
                    case 'array':
                        $sanitized[$field] = is_array($value) ? $value : array();
                        break;
                }
            } elseif (isset($rules['default'])) {
                $sanitized[$field] = $rules['default'];
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize business info data
     *
     * @param array $business_info Raw business info data
     * @return array Sanitized business info data
     */
    public static function sanitize_business_info($business_info) {
        $sanitized = array();
        
        foreach (self::$business_schema as $field => $rules) {
            if (isset($business_info[$field])) {
                $value = $business_info[$field];
                
                switch ($rules['type']) {
                    case 'string':
                        $sanitized[$field] = sanitize_text_field($value);
                        break;
                    case 'integer':
                        $sanitized[$field] = intval($value);
                        break;
                    case 'float':
                        $sanitized[$field] = floatval($value);
                        break;
                    case 'array':
                        $sanitized[$field] = is_array($value) ? $value : array();
                        break;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get review schema
     *
     * @return array Review schema
     */
    public static function get_review_schema() {
        return self::$review_schema;
    }
    
    /**
     * Get business schema
     *
     * @return array Business schema
     */
    public static function get_business_schema() {
        return self::$business_schema;
    }
    
    /**
     * Check if review data is valid
     *
     * @param array $review Review data
     * @return bool Whether the review is valid
     */
    public static function is_valid_review($review) {
        $result = self::validate_review($review);
        return $result['valid'];
    }
    
    /**
     * Check if business info is valid
     *
     * @param array $business_info Business info data
     * @return bool Whether the business info is valid
     */
    public static function is_valid_business_info($business_info) {
        $result = self::validate_business_info($business_info);
        return $result['valid'];
    }
}
