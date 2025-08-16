# Google Maps Reviews Widget for WordPress

A WordPress plugin that displays Google Maps business reviews on your website using a widget or shortcode. No API key required - simply enter your Google Maps business URL.

## Directory Structure

```
google-maps-reviews-widget/
├── google-maps-reviews-widget.php    # Main plugin file
├── includes/                         # Core plugin classes
│   ├── class-google-maps-reviews-scraper.php
│   ├── class-google-maps-reviews-widget.php
│   ├── class-google-maps-reviews-shortcode.php
│   ├── class-google-maps-reviews-cache.php
│   └── class-google-maps-reviews-display.php
├── admin/                           # Admin interface
│   ├── class-google-maps-reviews-admin.php
│   └── partials/
│       └── admin-settings.php
├── assets/                          # Frontend assets
│   ├── css/
│   │   └── google-maps-reviews.css
│   └── js/
│       └── google-maps-reviews.js
├── languages/                       # Translation files
│   └── google-maps-reviews.pot
├── tasks/                          # Development tasks
│   ├── prd-google-maps-reviews-widget.md
│   └── tasks-prd-google-maps-reviews-widget.md
└── README.md                       # This file
```

## Features

- Display Google Maps reviews without requiring an API key
- WordPress widget and shortcode support
- Multiple display layouts (list, cards, carousel, grid)
- Review filtering and sorting options
- Caching system for performance
- Responsive design
- Translation ready

## Installation

1. Upload the plugin files to `/wp-content/plugins/google-maps-reviews-widget/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin in the admin settings
4. Add the widget to your sidebar or use the shortcode `[google_maps_reviews]`

## Usage

### Widget
Add the "Google Maps Reviews" widget to any widget area in your WordPress admin.

### Shortcode
Use the shortcode in any post or page:
```
[google_maps_reviews business_url="https://maps.google.com/..." layout="cards" max_reviews="5"]
```

## Development

This plugin follows WordPress coding standards and best practices. See the tasks directory for detailed development information.

## License

GPL v2 or later
