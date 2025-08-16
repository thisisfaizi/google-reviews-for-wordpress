# Developer Quick Reference Guide

## Plugin Architecture

### Core Classes
- `Google_Maps_Reviews_Init` - Main plugin initialization
- `Google_Maps_Reviews_Scraper` - Web scraping functionality
- `Google_Maps_Reviews_Widget` - WordPress widget implementation
- `Google_Maps_Reviews_Shortcode` - Shortcode handler
- `Google_Maps_Reviews_Config` - Configuration management
- `Google_Maps_Reviews_Validator` - Data validation
- `Google_Maps_Reviews_Sanitizer` - Input/output sanitization

### File Structure
```
google-maps-reviews-widget/
├── google-maps-reviews-widget.php          # Main plugin file
├── includes/
│   ├── class-google-maps-reviews-init.php
│   ├── class-google-maps-reviews-scraper.php
│   ├── class-google-maps-reviews-widget.php
│   ├── class-google-maps-reviews-shortcode.php
│   ├── class-google-maps-reviews-config.php
│   ├── class-google-maps-reviews-validator.php
│   ├── class-google-maps-reviews-sanitizer.php
│   ├── class-google-maps-reviews-autoloader.php
│   ├── class-google-maps-reviews-activator.php
│   ├── class-google-maps-reviews-deactivator.php
│   ├── class-google-maps-reviews-uninstall.php
│   └── exceptions/
│       ├── class-google-maps-reviews-exception.php
│       ├── class-google-maps-reviews-scraping-exception.php
│       └── class-google-maps-reviews-cache-exception.php
├── admin/
├── assets/
├── languages/
└── docs/
```

## API Reference

### Widget Class Methods

#### `Google_Maps_Reviews_Widget::get_documentation()`
Returns comprehensive documentation array for the widget.

#### `Google_Maps_Reviews_Widget::get_usage_examples()`
Returns usage examples with configuration settings.

#### `Google_Maps_Reviews_Widget::get_configuration_tips()`
Returns configuration tips organized by category.

#### `Google_Maps_Reviews_Widget::get_css_classes()`
Returns CSS class names for customization.

### Shortcode Class Methods

#### `Google_Maps_Reviews_Shortcode::get_documentation()`
Returns comprehensive documentation array for shortcodes.

#### `Google_Maps_Reviews_Shortcode::get_usage_examples()`
Returns usage examples with shortcode syntax.

#### `Google_Maps_Reviews_Shortcode::get_configuration_tips()`
Returns configuration tips organized by category.

#### `Google_Maps_Reviews_Shortcode::get_css_classes()`
Returns CSS class names for customization.

#### `Google_Maps_Reviews_Shortcode::get_attribute_schema()`
Returns attribute schema for WordPress editor integration.

### Scraper Class Methods

#### `Google_Maps_Reviews_Scraper::get_reviews($business_url, $options)`
Fetches reviews from Google Maps.

**Parameters:**
- `$business_url` (string) - Google Maps business URL
- `$options` (array) - Additional options

**Returns:** Array with reviews and business info, or WP_Error on failure

#### `Google_Maps_Reviews_Scraper::get_business_info($business_url)`
Fetches business information from Google Maps.

**Parameters:**
- `$business_url` (string) - Google Maps business URL

**Returns:** Array with business information, or WP_Error on failure

### Configuration Class Methods

#### `Google_Maps_Reviews_Config::get($key, $default = null)`
Gets a configuration value.

#### `Google_Maps_Reviews_Config::set($key, $value)`
Sets a configuration value.

#### `Google_Maps_Reviews_Config::parse_business_url($url)`
Parses and validates a Google Maps business URL.

#### `Google_Maps_Reviews_Config::get_url_validation_errors($url)`
Returns validation errors for a business URL.

## Hooks and Filters

### Actions
```php
// Plugin initialization
do_action('gmrw_plugin_loaded');
do_action('gmrw_shortcodes_registered');

// Scraping events
do_action('gmrw_before_scrape', $business_url);
do_action('gmrw_after_scrape', $reviews, $business_url);
do_action('gmrw_scrape_error', $error, $business_url);

// Widget events
do_action('gmrw_widget_before_display', $instance);
do_action('gmrw_widget_after_display', $instance);

// Shortcode events
do_action('gmrw_shortcode_before_render', $attributes);
do_action('gmrw_shortcode_after_render', $attributes, $output);
```

### Filters
```php
// Modify scraped reviews
apply_filters('gmrw_reviews_data', $reviews, $business_url);
apply_filters('gmrw_business_info', $business_info, $business_url);

// Modify widget output
apply_filters('gmrw_widget_output', $output, $instance);
apply_filters('gmrw_widget_css_classes', $classes, $instance);

// Modify shortcode output
apply_filters('gmrw_shortcode_output', $output, $attributes);
apply_filters('gmrw_shortcode_css_classes', $classes, $attributes);

// Modify configuration
apply_filters('gmrw_config_settings', $settings);
apply_filters('gmrw_cache_duration', $duration, $business_url);
```

## Data Structures

### Review Data Structure
```php
$review = array(
    'id' => 'string',              // Unique review ID
    'author_name' => 'string',     // Reviewer name
    'author_image' => 'string',    // Profile image URL
    'rating' => 1-5,               // Star rating
    'content' => 'string',         // Review text
    'date' => 'string',            // Review date
    'helpful_votes' => 0,          // Helpful votes count
    'owner_response' => 'string',  // Business owner response
    'language' => 'string',        // Review language
);
```

### Business Info Structure
```php
$business_info = array(
    'name' => 'string',            // Business name
    'address' => 'string',         // Business address
    'phone' => 'string',           // Phone number
    'website' => 'string',         // Website URL
    'rating' => 0.0-5.0,           // Overall rating
    'review_count' => 0,           // Total review count
    'category' => 'string',        // Business category
    'hours' => array(),            // Operating hours
);
```

### Widget Instance Structure
```php
$instance = array(
    'title' => 'string',           // Widget title
    'business_url' => 'string',    // Google Maps URL
    'max_reviews' => 1-50,         // Number of reviews
    'layout' => 'list|cards|carousel|grid',
    'show_rating' => true|false,
    'show_date' => true|false,
    'show_author_image' => true|false,
    'show_helpful_votes' => true|false,
    'show_owner_response' => true|false,
    'sort_by' => 'relevance|date|rating',
    'sort_order' => 'asc|desc',
    'min_rating' => 0-5,
    'cache_duration' => 300-86400,
);
```

## Error Handling

### Exception Classes
```php
// Base exception
Google_Maps_Reviews_Exception

// Specific exceptions
Google_Maps_Reviews_Scraping_Exception
Google_Maps_Reviews_Cache_Exception
```

### Error Codes
```php
// Scraping errors
'SCRAPING_ERROR'           // General scraping error
'INVALID_URL'              // Invalid business URL
'NO_REVIEWS_FOUND'         // No reviews available
'RATE_LIMIT_EXCEEDED'      // Rate limiting hit
'NETWORK_ERROR'            // Network/HTTP error

// Cache errors
'CACHE_ERROR'              // General cache error
'CACHE_MISS'               // Cache miss
'CACHE_EXPIRED'            // Cache expired

// Validation errors
'VALIDATION_ERROR'         // Data validation failed
'REQUIRED_FIELD_MISSING'   // Required field missing
'INVALID_FORMAT'           // Invalid data format
```

## Caching

### Cache Keys
```php
// Review cache
'gmrw_reviews_' . md5($business_url)
'gmrw_reviews_' . md5($business_url) . '_' . $options_hash

// Business info cache
'gmrw_business_' . md5($business_url)

// Error cache
'gmrw_error_' . md5($business_url)
```

### Cache Management
```php
// Clear specific cache
delete_transient('gmrw_reviews_' . md5($business_url));

// Clear all plugin cache
Google_Maps_Reviews_Cache::clear_all();

// Get cache status
Google_Maps_Reviews_Cache::get_status($business_url);
```

## Security

### Sanitization
```php
// Sanitize widget data
$sanitized = Google_Maps_Reviews_Sanitizer::sanitize_widget_instance($instance);

// Sanitize shortcode attributes
$sanitized = Google_Maps_Reviews_Sanitizer::sanitize_shortcode_attributes($attributes);

// Sanitize review data
$sanitized = Google_Maps_Reviews_Sanitizer::sanitize_review_data($review);

// Escape HTML output
$escaped = Google_Maps_Reviews_Sanitizer::escape_review_content($content);
```

### Validation
```php
// Validate business URL
$is_valid = Google_Maps_Reviews_Config::validate_business_url($url);

// Validate review data
$result = Google_Maps_Reviews_Validator::validate_review($review);

// Get validation errors
$errors = Google_Maps_Reviews_Config::get_url_validation_errors($url);
```

## Performance Optimization

### Caching Strategies
```php
// Set appropriate cache duration
$cache_duration = apply_filters('gmrw_cache_duration', 3600, $business_url);

// Use transient API
set_transient($cache_key, $data, $cache_duration);

// Implement cache warming
wp_schedule_single_event(time() + 300, 'gmrw_cache_warm', array($business_url));
```

### Rate Limiting
```php
// Respect rate limits
$delay = Google_Maps_Reviews_Scraper::calculate_dynamic_delay();

// Store request history
Google_Maps_Reviews_Scraper::store_request_history();

// Check robots.txt
$allowed = Google_Maps_Reviews_Scraper::check_robots_txt($url);
```

## Customization Examples

### Custom Widget Styling
```php
// Add custom CSS
add_action('wp_head', function() {
    echo '<style>
        .gmrw-widget {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
        }
        .gmrw-review {
            background: rgba(255,255,255,0.1);
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
        }
    </style>';
});
```

### Custom Shortcode Attributes
```php
// Add custom attribute
add_filter('gmrw_shortcode_attributes', function($attributes) {
    $attributes['custom_theme'] = 'dark';
    return $attributes;
});

// Handle custom attribute
add_action('gmrw_shortcode_before_render', function($attributes) {
    if (!empty($attributes['custom_theme'])) {
        wp_enqueue_style('gmrw-dark-theme');
    }
});
```

### Custom Review Filtering
```php
// Filter reviews by custom criteria
add_filter('gmrw_reviews_data', function($reviews, $business_url) {
    return array_filter($reviews, function($review) {
        return strlen($review['content']) > 50; // Only long reviews
    });
}, 10, 2);
```

### Custom Error Handling
```php
// Log scraping errors
add_action('gmrw_scrape_error', function($error, $business_url) {
    error_log("GMRW Scraping Error: " . $error->getMessage() . " for URL: " . $business_url);
});

// Custom error display
add_filter('gmrw_widget_output', function($output, $instance) {
    if (strpos($output, 'gmrw-error') !== false) {
        $output = '<div class="custom-error">Custom error message</div>';
    }
    return $output;
}, 10, 2);
```

## Testing

### Unit Testing
```php
// Test scraper functionality
$scraper = new Google_Maps_Reviews_Scraper();
$result = $scraper->get_reviews($test_url);

// Test validation
$is_valid = Google_Maps_Reviews_Validator::validate_review($test_review);

// Test sanitization
$sanitized = Google_Maps_Reviews_Sanitizer::sanitize_widget_instance($test_instance);
```

### Integration Testing
```php
// Test widget rendering
$widget = new Google_Maps_Reviews_Widget();
$output = $widget->widget($args, $instance);

// Test shortcode rendering
$shortcode = new Google_Maps_Reviews_Shortcode();
$output = $shortcode->render_shortcode($attributes);
```

## Debugging

### Debug Mode
```php
// Enable debug logging
define('GMRW_DEBUG', true);

// Debug scraper
$scraper = new Google_Maps_Reviews_Scraper();
$debug_info = $scraper->test_scraping($business_url);
```

### Logging
```php
// Log to WordPress error log
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('GMRW Debug: ' . $message);
}

// Log to custom table
Google_Maps_Reviews_Logger::log($level, $message, $context);
```

---

*This quick reference guide provides essential information for developers working with the Google Maps Reviews Widget and Shortcode plugin. For detailed implementation examples and advanced usage, refer to the main documentation.*
