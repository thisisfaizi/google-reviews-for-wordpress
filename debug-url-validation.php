<?php
// Debug script to test URL validation
require_once 'includes/class-google-maps-reviews-config.php';

$test_url = 'https://www.google.com/maps/place/NowDigiVerse/';

echo "Testing URL: " . $test_url . "\n";
echo "URL validation result: " . (Google_Maps_Reviews_Config::validate_business_url($test_url) ? 'VALID' : 'INVALID') . "\n";

$parsed = Google_Maps_Reviews_Config::parse_business_url($test_url);
echo "Parse result: " . print_r($parsed, true) . "\n";

$normalized = Google_Maps_Reviews_Config::normalize_business_url($test_url);
echo "Normalized URL: " . ($normalized ?: 'FAILED') . "\n";

$business_name = Google_Maps_Reviews_Config::extract_business_name_from_url($test_url);
echo "Business name: " . $business_name . "\n";
?>
