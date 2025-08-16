# Google Maps Reviews Widget & Shortcode Documentation

## Overview

The Google Maps Reviews Widget and Shortcode plugin allows you to display Google Maps reviews for any business on your WordPress website. The plugin fetches reviews directly from Google Maps without requiring an API key, making it easy to showcase customer testimonials and build trust with your audience.

## Features

### Core Features
- **No API Key Required**: Fetches reviews directly from Google Maps
- **Multiple Display Options**: List, cards, carousel, and grid layouts
- **Configurable Reviews**: Display 1-50 reviews with custom settings
- **Rating Filtering**: Show only reviews above a certain rating
- **Sorting Options**: Sort by relevance, date, or rating
- **Responsive Design**: Works on all devices and screen sizes
- **Automatic Caching**: Built-in caching for optimal performance
- **Rate Limiting**: Respects Google's terms of service

### Widget Features
- **WordPress Widget Integration**: Easy drag-and-drop setup
- **Customizable Settings**: Full control over display options
- **Theme Integration**: Seamlessly integrates with any WordPress theme
- **Live Preview**: See changes in real-time with WordPress Customizer

### Shortcode Features
- **Multiple Shortcodes**: `[google_maps_reviews]`, `[gmrw]`, `[gmrw_reviews]`
- **TinyMCE Integration**: Visual editor button for easy insertion
- **WordPress Editor Support**: Attribute hints and validation
- **Custom Styling**: CSS classes for theme customization
- **Flexible Placement**: Use anywhere in posts, pages, or widgets

## Installation

### Widget Installation
1. Go to **Appearance > Widgets** in your WordPress admin
2. Find "Google Maps Reviews" in the available widgets
3. Drag and drop it to your desired widget area
4. Configure the widget settings and save

### Shortcode Installation
1. Simply add the shortcode to any post or page content
2. Use the TinyMCE button in the visual editor
3. Or manually type the shortcode with desired attributes

## Widget Configuration

### Basic Settings
- **Widget Title**: Optional title displayed above the reviews
- **Business URL**: Google Maps business URL (required)
- **Maximum Reviews**: Number of reviews to display (1-50)

### Display Options
- **Layout**: Choose from list, cards, carousel, or grid
- **Show Rating**: Display star ratings for each review
- **Show Date**: Display review dates
- **Show Author Image**: Display reviewer profile images
- **Show Helpful Votes**: Display helpful vote counts
- **Show Owner Response**: Display business owner responses

### Filtering & Sorting
- **Sort By**: Relevance, date, or rating
- **Sort Order**: Ascending or descending
- **Minimum Rating**: Filter reviews by minimum rating (0-5)

### Performance
- **Cache Duration**: How long to cache reviews (300-86400 seconds)

## Shortcode Usage

### Basic Shortcode
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]
```

### Advanced Shortcode
```
[google_maps_reviews 
    business_url="https://www.google.com/maps/place/Your+Business+Name"
    max_reviews="10"
    layout="cards"
    show_rating="true"
    show_date="true"
    min_rating="4"
    sort_by="date"
    sort_order="desc"
    container_class="my-custom-reviews"
    title="Customer Testimonials"
]
```

### Shortcode Aliases
```
[gmrw business_url="https://www.google.com/maps/place/Your+Business+Name"]
[gmrw_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]
```

## Usage Examples

### Widget Examples

#### Basic Widget Setup
- **Title**: Customer Reviews
- **Business URL**: Your Google Maps business URL
- **Max Reviews**: 5
- **Layout**: List

#### Featured Reviews Widget
- **Title**: 5-Star Reviews
- **Business URL**: Your Google Maps business URL
- **Max Reviews**: 10
- **Layout**: Cards
- **Min Rating**: 5
- **Sort By**: Rating
- **Sort Order**: Descending

#### Sidebar Reviews Widget
- **Title**: What Our Customers Say
- **Business URL**: Your Google Maps business URL
- **Max Reviews**: 3
- **Layout**: List
- **Show Rating**: Yes
- **Show Date**: No
- **Show Author Image**: No

### Shortcode Examples

#### Basic Shortcode
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]
```
*Displays 5 reviews in list layout with default settings.*

#### Custom Number of Reviews
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" max_reviews="10"]
```
*Displays 10 reviews instead of the default 5.*

#### Card Layout
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" layout="cards" max_reviews="6"]
```
*Displays 6 reviews in an attractive card layout.*

#### Featured 5-Star Reviews
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" min_rating="5" max_reviews="8" layout="cards"]
```
*Displays only 5-star reviews in card layout.*

#### Recent Reviews
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" sort_by="date" sort_order="desc" max_reviews="5"]
```
*Displays the 5 most recent reviews.*

#### Minimal Display
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" show_author_image="false" show_date="false" show_helpful_votes="false" show_owner_response="false"]
```
*Displays reviews with minimal information for a clean look.*

#### Custom Styled
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" container_class="my-custom-reviews" title="Customer Testimonials" layout="grid"]
```
*Displays reviews with a custom title and CSS class for styling.*

#### Carousel Display
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" layout="carousel" max_reviews="12" show_rating="true" show_date="true"]
```
*Displays reviews in an interactive carousel format.*

#### Sidebar Widget Alternative
```
[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" max_reviews="3" show_author_image="false" show_date="false"]
```
*Perfect for use in a text widget in your sidebar.*

## Configuration Tips

### Performance Optimization
- Use appropriate cache duration based on how often reviews change
- Limit max_reviews to reduce loading time
- Consider using min_rating filter to show only positive reviews

### Display Optimization
- Choose layout based on your theme and available space
- Use cards or grid layout for visual impact
- List layout works well in sidebars and narrow spaces
- Carousel layout is great for featured content areas

### Content Strategy
- Show author images for personal touch
- Include dates to show review freshness
- Display helpful votes to show review quality
- Show owner responses to demonstrate customer service

### SEO Benefits
- Use descriptive widget titles for better SEO
- Reviews provide fresh, user-generated content
- Star ratings can improve click-through rates

## CSS Classes for Customization

### Widget CSS Classes
```css
.gmrw-widget              /* Main widget container */
.gmrw-business-info       /* Business information section */
.gmrw-business-name       /* Business name */
.gmrw-business-rating     /* Business rating display */
.gmrw-business-address    /* Business address */
.gmrw-business-phone      /* Business phone */
.gmrw-reviews             /* Reviews container */
.gmrw-review              /* Individual review */
.gmrw-review-author       /* Review author section */
.gmrw-author-image        /* Author profile image */
.gmrw-author-name         /* Author name */
.gmrw-review-date         /* Review date */
.gmrw-review-rating       /* Review rating */
.gmrw-review-content      /* Review content */
.gmrw-helpful-votes       /* Helpful votes count */
.gmrw-owner-response      /* Owner response */
.gmrw-stars               /* Star rating container */
.gmrw-star                /* Individual star */
.gmrw-star-filled         /* Filled star */
.gmrw-star-empty          /* Empty star */
.gmrw-no-reviews          /* No reviews message */
.gmrw-error               /* Error message */
```

### Shortcode CSS Classes
```css
.gmrw-shortcode           /* Main shortcode container */
/* All other classes are the same as widget classes */
```

## Troubleshooting

### Common Issues

#### No Reviews Appearing
- Check that the business URL is correct
- Ensure the business has reviews on Google Maps
- Verify the URL is a valid Google Maps business page

#### Invalid URL Error
- Ensure the URL is a valid Google Maps business page URL
- Check for typos in the URL
- Make sure the business exists on Google Maps

#### Shortcode Not Working
- Make sure the shortcode is properly formatted
- Use quotes around attribute values
- Check for syntax errors in the shortcode

#### Caching Issues
- Reviews are cached for performance
- Clear cache if reviews are outdated
- Adjust cache duration in settings

#### Rate Limiting
- The plugin respects Google's terms of service
- Built-in rate limiting prevents overloading
- Wait a few minutes if you encounter rate limiting

## URL Examples

### Supported URL Formats
- **Standard Google Maps URL**: `https://www.google.com/maps/place/Business+Name/@latitude,longitude,zoom/data=...`
- **Short Google Maps URL**: `https://goo.gl/maps/...`
- **Google My Business URL**: `https://business.google.com/...`

### Finding Your Business URL
1. Go to Google Maps
2. Search for your business name
3. Click on your business listing
4. Copy the URL from the address bar
5. Use this URL in the widget or shortcode

## Advanced Features

### TinyMCE Editor Integration
The plugin includes a TinyMCE editor button for easy shortcode insertion:
1. Click the Google Maps Reviews button in the visual editor
2. Fill in the required fields
3. Click "Insert Shortcode"
4. The shortcode will be added to your content

### WordPress Editor Integration
The plugin registers shortcode attributes with WordPress for better editor support:
- Attribute hints and validation
- IntelliSense support in code editors
- Better integration with WordPress core

### Custom Styling
Use the provided CSS classes to customize the appearance:
```css
/* Custom styling example */
.gmrw-widget {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.gmrw-review {
    border-bottom: 1px solid #eee;
    padding: 15px 0;
}

.gmrw-stars {
    color: #ffd700;
}
```

## Support

For additional support and documentation:
- Check the plugin's built-in help sections
- Review the configuration tips in the admin area
- Use the troubleshooting guide for common issues
- Contact the plugin developer for technical support

---

*This documentation covers all major features and usage scenarios for the Google Maps Reviews Widget and Shortcode plugin. For the most up-to-date information, always refer to the plugin's built-in documentation and help sections.*
