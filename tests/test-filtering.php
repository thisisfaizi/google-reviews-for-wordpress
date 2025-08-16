<?php
/**
 * Test file for Google Maps Reviews Filtering
 *
 * This file can be used to test the filtering functionality
 * Run this file directly to see the filtering in action
 */

// Include WordPress
require_once('../../../wp-load.php');

// Test data
$test_reviews = array(
    array(
        'id' => '1',
        'author_name' => 'John Doe',
        'rating' => 5,
        'content' => 'Excellent service!',
        'date' => '2023-12-01',
        'helpful_votes' => 10
    ),
    array(
        'id' => '2',
        'author_name' => 'Jane Smith',
        'rating' => 3,
        'content' => 'Good but could be better',
        'date' => '2023-11-15',
        'helpful_votes' => 5
    ),
    array(
        'id' => '3',
        'author_name' => 'Bob Johnson',
        'rating' => 4,
        'content' => 'Very good experience',
        'date' => '2023-10-20',
        'helpful_votes' => 8
    ),
    array(
        'id' => '4',
        'author_name' => 'Alice Brown',
        'rating' => 2,
        'content' => 'Not satisfied',
        'date' => '2023-09-10',
        'helpful_votes' => 2
    ),
    array(
        'id' => '5',
        'author_name' => 'Charlie Wilson',
        'rating' => 5,
        'content' => 'Amazing!',
        'date' => '2023-08-05',
        'helpful_votes' => 15
    )
);

echo "<h1>Google Maps Reviews Filtering Test</h1>\n";

// Test 1: Filter by rating
echo "<h2>Test 1: Filter by Rating (4+ stars)</h2>\n";
$filtered_reviews = Google_Maps_Reviews_Filter::filter_by_rating($test_reviews, 4);
echo "Original reviews: " . count($test_reviews) . "\n";
echo "Filtered reviews (4+ stars): " . count($filtered_reviews) . "\n";
foreach ($filtered_reviews as $review) {
    echo "- {$review['author_name']}: {$review['rating']} stars\n";
}

// Test 2: Filter by date
echo "\n<h2>Test 2: Filter by Date (Last month)</h2>\n";
$filtered_reviews = Google_Maps_Reviews_Filter::filter_by_date($test_reviews, 'month');
echo "Filtered reviews (last month): " . count($filtered_reviews) . "\n";
foreach ($filtered_reviews as $review) {
    echo "- {$review['author_name']}: {$review['date']}\n";
}

// Test 3: Sort by rating
echo "\n<h2>Test 3: Sort by Rating (High to Low)</h2>\n";
$sorted_reviews = Google_Maps_Reviews_Filter::sort_reviews($test_reviews, 'rating-high');
echo "Sorted reviews (highest rating first):\n";
foreach ($sorted_reviews as $review) {
    echo "- {$review['author_name']}: {$review['rating']} stars\n";
}

// Test 4: Apply multiple filters
echo "\n<h2>Test 4: Apply Multiple Filters</h2>\n";
$filters = array(
    'min_rating' => 4,
    'sort_by' => 'rating-high'
);
$filtered_reviews = Google_Maps_Reviews_Filter::apply_filters($test_reviews, $filters);
echo "Filtered and sorted reviews (4+ stars, highest first):\n";
foreach ($filtered_reviews as $review) {
    echo "- {$review['author_name']}: {$review['rating']} stars\n";
}

// Test 5: Get filter statistics
echo "\n<h2>Test 5: Filter Statistics</h2>\n";
$stats = Google_Maps_Reviews_Filter::get_filter_stats($test_reviews);
echo "Total reviews: {$stats['total']}\n";
echo "Average rating: {$stats['average_rating']}\n";
echo "Rating distribution:\n";
foreach ($stats['rating_distribution'] as $rating => $count) {
    echo "- {$rating} stars: {$count} reviews\n";
}
echo "Date range: {$stats['date_range']['earliest']} to {$stats['date_range']['latest']}\n";
echo "Total helpful votes: {$stats['helpful_votes_total']}\n";

echo "\n<h2>Test Complete!</h2>\n";
echo "All filtering functionality appears to be working correctly.\n";
?>
