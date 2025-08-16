# Google Maps Reviews Filtering Guide

## Overview

The Google Maps Reviews plugin now includes comprehensive filtering capabilities that allow users to filter reviews by rating, date, and sort them by various criteria. This functionality works both on the server-side and client-side for optimal performance and user experience.

## Features

### Server-Side Filtering
- **Rating Filter**: Filter reviews by minimum star rating (1-5 stars)
- **Date Filter**: Filter reviews by time periods (week, month, year, custom range)
- **Sorting**: Sort reviews by date (newest/oldest), rating (highest/lowest), or helpful votes
- **Statistics**: Get comprehensive statistics about filtered reviews

### Client-Side Filtering
- **Instant Filtering**: Real-time filtering using JavaScript
- **State Preservation**: Remember user filter preferences
- **Responsive Design**: Mobile-friendly filter interface
- **Accessibility**: Full keyboard navigation and screen reader support

## Usage

### Widget Configuration

In the WordPress widget settings, you can enable filtering with these options:

1. **Show Filter Controls**: Enable the filter UI
2. **Enable Client-Side Filtering**: Use JavaScript for instant filtering
3. **Preserve Filter State**: Remember user preferences

### Shortcode Parameters

```php
[google_maps_reviews 
    business_url="https://maps.google.com/..." 
    show_filters="true"
    enable_client_side_filtering="true"
    preserve_filter_state="true"
    min_rating="4"
    date_range="month"
    custom_start_date="2023-01-01"
    custom_end_date="2023-12-31"
]
```

### Available Filter Parameters

#### Rating Filter
- `min_rating`: Minimum star rating (1-5)
- Values: `1`, `2`, `3`, `4`, `5`

#### Date Filter
- `date_range`: Time period filter
- Values: `week`, `month`, `year`, `custom`
- `custom_start_date`: Start date for custom range (YYYY-MM-DD)
- `custom_end_date`: End date for custom range (YYYY-MM-DD)

#### Sort Options
- `sort_by`: Sort criteria
- Values: `date-new`, `date-old`, `rating-high`, `rating-low`, `helpful`

## Technical Implementation

### Server-Side Filtering Class

The `Google_Maps_Reviews_Filter` class provides the core filtering functionality:

```php
// Filter by rating
$filtered_reviews = Google_Maps_Reviews_Filter::filter_by_rating($reviews, 4);

// Filter by date
$filtered_reviews = Google_Maps_Reviews_Filter::filter_by_date($reviews, 'month');

// Sort reviews
$sorted_reviews = Google_Maps_Reviews_Filter::sort_reviews($reviews, 'rating-high');

// Apply multiple filters
$filtered_reviews = Google_Maps_Reviews_Filter::apply_filters($reviews, $filters);

// Get statistics
$stats = Google_Maps_Reviews_Filter::get_filter_stats($reviews);
```

### Client-Side JavaScript

The filtering system includes comprehensive JavaScript functionality:

- **GMRWFilters Class**: Main filtering controller
- **Debounced Updates**: Performance-optimized filtering
- **State Management**: Local storage for preferences
- **Accessibility**: ARIA attributes and keyboard navigation

### CSS Styling

The filter UI is fully styled with:

- **Responsive Design**: Mobile-first approach
- **Dark Mode Support**: Automatic theme detection
- **High Contrast**: Accessibility compliance
- **Reduced Motion**: Respects user preferences

## Filter Options

### Rating Filters
- **All ratings**: Show all reviews regardless of rating
- **5 stars**: Only 5-star reviews
- **4+ stars**: Reviews with 4 or 5 stars
- **3+ stars**: Reviews with 3, 4, or 5 stars
- **2+ stars**: Reviews with 2, 3, 4, or 5 stars
- **1+ stars**: All reviews (same as "All ratings")

### Date Filters
- **All time**: Show all reviews regardless of date
- **Last week**: Reviews from the past 7 days
- **Last month**: Reviews from the past 30 days
- **Last year**: Reviews from the past 365 days
- **Custom range**: User-defined date range

### Sort Options
- **Newest first**: Most recent reviews first
- **Oldest first**: Oldest reviews first
- **Highest rating**: Highest-rated reviews first
- **Lowest rating**: Lowest-rated reviews first
- **Most helpful**: Reviews with most helpful votes first

## Integration with Display System

The filtering system integrates seamlessly with the existing display system:

### Display Class Integration
```php
// In Google_Maps_Reviews_Display::render_reviews()
if ($show_filters && !empty($options['filters'])) {
    $filters = $options['filters'];
    $validation = Google_Maps_Reviews_Filter::validate_filters($filters);
    if ($validation['valid']) {
        $sanitized_reviews = Google_Maps_Reviews_Filter::apply_filters($sanitized_reviews, $filters);
    }
}
```

### Template System Integration
The filtering system works with all layout types:
- **List Layout**: Filtered reviews in vertical list
- **Cards Layout**: Filtered reviews in card grid
- **Carousel Layout**: Filtered reviews in carousel
- **Grid Layout**: Filtered reviews in responsive grid

## Performance Considerations

### Server-Side Optimization
- **Efficient Filtering**: Uses PHP array functions for fast filtering
- **Validation**: Input validation prevents invalid filter requests
- **Caching**: Filtered results can be cached for performance

### Client-Side Optimization
- **Debounced Updates**: Prevents excessive filtering operations
- **Lazy Loading**: Reviews load as needed
- **State Management**: Efficient local storage usage

## Accessibility Features

### Keyboard Navigation
- **Tab Navigation**: All filter controls are keyboard accessible
- **Enter Key**: Apply filters with Enter key
- **Escape Key**: Clear filters with Escape key

### Screen Reader Support
- **ARIA Labels**: Descriptive labels for all controls
- **Live Regions**: Dynamic updates announced to screen readers
- **Semantic HTML**: Proper heading structure and landmarks

### Visual Accessibility
- **High Contrast**: Enhanced contrast for visibility
- **Reduced Motion**: Respects user motion preferences
- **Focus Indicators**: Clear focus states for all interactive elements

## Error Handling

### Validation
```php
$validation = Google_Maps_Reviews_Filter::validate_filters($filters);
if (!$validation['valid']) {
    // Handle validation errors
    error_log('Filter validation failed: ' . implode(', ', $validation['errors']));
}
```

### Fallbacks
- **Invalid Filters**: System falls back to unfiltered reviews
- **Missing Data**: Graceful handling of missing review data
- **Network Issues**: Client-side filtering continues to work

## Customization

### Custom Filter Options
You can extend the filtering system by adding custom filter options:

```php
// Add custom filter to the filter options
add_filter('gmrw_filter_options', function($options) {
    $options['custom_filters']['verified'] = 'Verified Reviews Only';
    return $options;
});
```

### Custom Filter Logic
```php
// Add custom filter logic
add_filter('gmrw_apply_filters', function($reviews, $filters) {
    if (!empty($filters['verified_only'])) {
        $reviews = array_filter($reviews, function($review) {
            return !empty($review['verified']);
        });
    }
    return $reviews;
}, 10, 2);
```

## Testing

### Test File
A test file is included at `tests/test-filtering.php` that demonstrates all filtering functionality:

```bash
# Run the test file
php tests/test-filtering.php
```

### Test Coverage
The test file covers:
- Rating filtering
- Date filtering
- Sorting functionality
- Multiple filter application
- Statistics generation

## Troubleshooting

### Common Issues

1. **Filters Not Working**
   - Check if JavaScript is enabled
   - Verify filter parameters are valid
   - Check browser console for errors

2. **Performance Issues**
   - Reduce number of reviews being filtered
   - Enable caching
   - Use client-side filtering for better performance

3. **Accessibility Issues**
   - Ensure ARIA labels are present
   - Test with screen readers
   - Verify keyboard navigation works

### Debug Mode
Enable debug mode to see detailed filter information:

```php
// Add to wp-config.php
define('GMRW_DEBUG', true);
```

## Future Enhancements

### Planned Features
- **Advanced Search**: Text-based review search
- **Filter Combinations**: Save and share filter combinations
- **Analytics**: Filter usage statistics
- **Export**: Export filtered reviews
- **API**: REST API for filtering

### Contributing
To contribute to the filtering system:
1. Follow WordPress coding standards
2. Add tests for new functionality
3. Update documentation
4. Test accessibility features

## Conclusion

The filtering system provides a comprehensive solution for managing and displaying Google Maps reviews. It combines server-side efficiency with client-side responsiveness, ensuring a great user experience while maintaining performance and accessibility standards.
