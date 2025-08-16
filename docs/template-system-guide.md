# Google Maps Reviews Template System Guide

## Overview

The Google Maps Reviews plugin includes a flexible template system that allows you to customize how reviews and business information are displayed. This system supports multiple themes, custom templates, and theme overrides.

## Template Structure

### Directory Structure
```
templates/
├── default/
│   ├── review.php
│   ├── business.php
│   └── style.css
├── modern/
│   ├── review.php
│   ├── business.php
│   └── style.css
├── minimal/
│   ├── review.php
│   ├── business.php
│   └── style.css
├── card/
│   ├── review.php
│   ├── business.php
│   └── style.css
└── compact/
    ├── review.php
    ├── business.php
    └── style.css
```

### Available Themes

1. **Default** - Clean and professional design
2. **Modern** - Contemporary design with gradients and modern styling
3. **Minimal** - Simple and clean minimal design
4. **Card** - Card-based layout with shadows
5. **Compact** - Space-efficient compact layout

## Template Variables

### Review Template Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `$review` | Complete review data array | `array('author_name' => 'John Doe', 'rating' => 5, ...)` |
| `$business` | Business information array | `array('name' => 'Business Name', 'rating' => 4.5, ...)` |
| `$options` | Display options array | `array('theme' => 'default', 'show_rating' => true, ...)` |
| `$index` | Current review index (0-based) | `0, 1, 2, ...` |
| `$total` | Total number of reviews being displayed | `10` |

### Review Data Structure

```php
$review = array(
    'id' => 'review_id',
    'author_name' => 'Reviewer Name',
    'author_image' => 'https://example.com/avatar.jpg',
    'rating' => 5,
    'content' => 'Review text content...',
    'date' => '2023-12-01',
    'helpful_votes' => 15,
    'owner_response' => 'Owner response text...'
);
```

### Business Data Structure

```php
$business = array(
    'name' => 'Business Name',
    'rating' => 4.5,
    'total_reviews' => 150,
    'address' => '123 Main St, City, State',
    'phone' => '+1-555-123-4567',
    'website' => 'https://example.com',
    'maps_url' => 'https://maps.google.com/...',
    'image' => 'https://example.com/logo.jpg',
    'categories' => array('Restaurant', 'Italian')
);
```

## Creating Custom Templates

### 1. Theme Override (Recommended)

Create templates in your theme directory:

```
your-theme/
└── google-maps-reviews/
    ├── default/
    │   ├── review.php
    │   └── business.php
    └── modern/
        ├── review.php
        └── business.php
```

### 2. Plugin Template Override

Create templates in the plugin's template directory:

```
wp-content/plugins/google-maps-reviews-for-wordpress/templates/
└── your-custom-theme/
    ├── review.php
    ├── business.php
    └── style.css
```

### 3. Basic Review Template Example

```php
<?php
/**
 * Custom Review Template
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$review = Google_Maps_Reviews_Templates::get_var('review');
$business = Google_Maps_Reviews_Templates::get_var('business');
$options = Google_Maps_Reviews_Templates::get_var('options');

if (!$review) {
    return;
}
?>

<div class="gmrw-review gmrw-theme-custom">
    <!-- Author Information -->
    <div class="gmrw-author-section">
        <?php if (!empty($review['author_image'])) : ?>
            <img src="<?php echo esc_url($review['author_image']); ?>" 
                 alt="<?php echo esc_attr($review['author_name']); ?>" 
                 class="gmrw-author-image">
        <?php endif; ?>
        
        <div class="gmrw-author-details">
            <h4 class="gmrw-author-name"><?php echo esc_html($review['author_name']); ?></h4>
            <span class="gmrw-review-date"><?php echo esc_html($review['date']); ?></span>
        </div>
    </div>
    
    <!-- Rating -->
    <div class="gmrw-rating-section">
        <?php echo Google_Maps_Reviews_Templates::render_stars($review['rating']); ?>
        <span class="gmrw-rating-text"><?php echo esc_html($review['rating']); ?>/5</span>
    </div>
    
    <!-- Review Content -->
    <div class="gmrw-content-section">
        <p class="gmrw-review-text"><?php echo esc_html($review['content']); ?></p>
    </div>
    
    <!-- Helpful Votes -->
    <?php if (!empty($review['helpful_votes'])) : ?>
        <div class="gmrw-votes-section">
            <span class="gmrw-helpful-votes">
                <?php printf(_n('%d helpful vote', '%d helpful votes', $review['helpful_votes'], GMRW_TEXT_DOMAIN), $review['helpful_votes']); ?>
            </span>
        </div>
    <?php endif; ?>
    
    <!-- Owner Response -->
    <?php if (!empty($review['owner_response'])) : ?>
        <div class="gmrw-owner-response">
            <div class="gmrw-owner-header">
                <strong><?php _e('Owner Response:', GMRW_TEXT_DOMAIN); ?></strong>
            </div>
            <p class="gmrw-owner-text"><?php echo esc_html($review['owner_response']); ?></p>
        </div>
    <?php endif; ?>
</div>
```

### 4. Basic Business Template Example

```php
<?php
/**
 * Custom Business Template
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$business = Google_Maps_Reviews_Templates::get_var('business');
$options = Google_Maps_Reviews_Templates::get_var('options');

if (!$business) {
    return;
}
?>

<div class="gmrw-business-info gmrw-theme-custom">
    <!-- Business Header -->
    <div class="gmrw-business-header">
        <?php if (!empty($business['image'])) : ?>
            <img src="<?php echo esc_url($business['image']); ?>" 
                 alt="<?php echo esc_attr($business['name']); ?>" 
                 class="gmrw-business-logo">
        <?php endif; ?>
        
        <div class="gmrw-business-details">
            <h3 class="gmrw-business-name"><?php echo esc_html($business['name']); ?></h3>
            
            <?php if (!empty($business['address'])) : ?>
                <p class="gmrw-business-address"><?php echo esc_html($business['address']); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Business Rating -->
    <div class="gmrw-business-rating">
        <?php echo Google_Maps_Reviews_Templates::render_stars($business['rating']); ?>
        <div class="gmrw-rating-details">
            <span class="gmrw-rating-number"><?php echo esc_html($business['rating']); ?></span>
            <span class="gmrw-rating-max">/5</span>
            <span class="gmrw-total-reviews">
                <?php printf(_n('(%d review)', '(%d reviews)', $business['total_reviews'], GMRW_TEXT_DOMAIN), $business['total_reviews']); ?>
            </span>
        </div>
    </div>
    
    <!-- Business Actions -->
    <div class="gmrw-business-actions">
        <?php if (!empty($business['website'])) : ?>
            <a href="<?php echo esc_url($business['website']); ?>" 
               class="gmrw-business-website" 
               target="_blank" 
               rel="noopener noreferrer">
                <?php _e('Visit Website', GMRW_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
        
        <?php if (!empty($business['maps_url'])) : ?>
            <a href="<?php echo esc_url($business['maps_url']); ?>" 
               class="gmrw-business-maps" 
               target="_blank" 
               rel="noopener noreferrer">
                <?php _e('View on Google Maps', GMRW_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
```

## Template Functions

### Available Functions

| Function | Description | Parameters |
|----------|-------------|------------|
| `Google_Maps_Reviews_Templates::get_var($key, $default)` | Get template variable | `$key` - Variable name, `$default` - Default value |
| `Google_Maps_Reviews_Templates::render_stars($rating)` | Render star rating HTML | `$rating` - Rating value (1-5) |
| `Google_Maps_Reviews_Templates::render($template, $data, $theme)` | Render template | `$template` - Template name, `$data` - Template data, `$theme` - Theme name |

### Example Usage

```php
// Get template variable
$author_name = Google_Maps_Reviews_Templates::get_var('review.author_name', 'Anonymous');

// Render stars
$stars_html = Google_Maps_Reviews_Templates::render_stars(4);

// Render custom template
$html = Google_Maps_Reviews_Templates::render('custom-template', $data, 'default');
```

## CSS Customization

### Theme CSS Structure

Each theme can have its own CSS file (`style.css`) that will be automatically loaded when the theme is used.

### CSS Variables

The default theme uses CSS custom properties for easy customization:

```css
:root {
    --gmrw-default-primary-color: #4285f4;
    --gmrw-default-secondary-color: #34a853;
    --gmrw-default-text-color: #333333;
    --gmrw-default-light-text: #666666;
    --gmrw-default-border-color: #e0e0e0;
    --gmrw-default-background: #ffffff;
    --gmrw-default-hover-bg: #f8f9fa;
    --gmrw-default-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    --gmrw-default-border-radius: 8px;
    --gmrw-default-spacing: 16px;
    --gmrw-default-font-size: 14px;
    --gmrw-default-line-height: 1.5;
}
```

### Custom CSS Example

```css
/* Custom theme styles */
.gmrw-review.gmrw-theme-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.gmrw-theme-custom .gmrw-author-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.gmrw-theme-custom .gmrw-stars {
    color: #ffd700;
}

.gmrw-theme-custom .gmrw-review-text {
    font-size: 16px;
    line-height: 1.6;
    margin: 15px 0;
}
```

## Using Templates in Code

### Widget Usage

```php
// In widget or shortcode
$options = array(
    'theme' => 'modern',
    'layout' => 'cards',
    'show_rating' => true,
    'show_date' => true,
    'max_content_length' => 300
);

$html = Google_Maps_Reviews_Display::render_reviews($reviews, $options);
```

### Shortcode Usage

```
[google_maps_reviews theme="modern" layout="cards" show_rating="true" show_date="true"]
```

### PHP Usage

```php
// Render reviews with custom theme
$reviews_html = Google_Maps_Reviews_Display::render_reviews($reviews, array(
    'theme' => 'custom-theme',
    'layout' => 'list'
));

// Render business info with custom theme
$business_html = Google_Maps_Reviews_Display::render_business_info($business_info, array(
    'theme' => 'custom-theme'
));
```

## Template Validation

The template system includes validation to ensure templates are secure and properly formatted:

```php
// Validate template content
$validation = Google_Maps_Reviews_Templates::validate_template($template_content);

if ($validation['valid']) {
    // Template is valid
    echo "Template is valid";
} else {
    // Template has errors
    foreach ($validation['errors'] as $error) {
        echo "Error: " . $error;
    }
}

// Check warnings
foreach ($validation['warnings'] as $warning) {
    echo "Warning: " . $warning;
}
```

## Best Practices

### 1. Security
- Always use `esc_html()`, `esc_attr()`, and `esc_url()` for output escaping
- Never include PHP code in template content
- Validate and sanitize all user input

### 2. Performance
- Use lazy loading for images
- Minimize DOM manipulation
- Use CSS for animations when possible

### 3. Accessibility
- Include proper ARIA labels
- Ensure keyboard navigation
- Provide alt text for images
- Use semantic HTML elements

### 4. Responsive Design
- Use CSS Grid or Flexbox for layouts
- Include media queries for mobile devices
- Test on various screen sizes

### 5. Theme Compatibility
- Use theme-agnostic CSS classes
- Avoid hardcoded colors and fonts
- Test with different WordPress themes

## Troubleshooting

### Common Issues

1. **Template not loading**: Check file permissions and path
2. **CSS not applying**: Ensure CSS file is properly enqueued
3. **Variables not available**: Verify template data structure
4. **Theme override not working**: Check theme directory structure

### Debug Mode

Enable debug mode to see template loading information:

```php
// Add to wp-config.php
define('GMRW_DEBUG', true);
```

### Template Documentation

For complete template documentation and examples, see:
- `docs/template-system-guide.md` - This guide
- `includes/class-google-maps-reviews-templates.php` - Template system source code
- `templates/` - Example templates

## Support

For template system support:
1. Check the documentation
2. Review example templates
3. Use the validation system
4. Contact plugin support

---

*This guide covers the basic template system. For advanced customization, refer to the plugin source code and WordPress development documentation.*
