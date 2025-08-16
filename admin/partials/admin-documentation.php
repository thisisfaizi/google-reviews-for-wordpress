<?php
/**
 * Admin Documentation Page Template
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="gmrw-documentation">
        <div class="gmrw-doc-content">
            <section id="overview">
                <h2><?php esc_html_e('Overview', GMRW_TEXT_DOMAIN); ?></h2>
                <p><?php esc_html_e('The Google Maps Reviews plugin allows you to display Google Maps business reviews on your WordPress website without requiring an API key.', GMRW_TEXT_DOMAIN); ?></p>
                
                <h3><?php esc_html_e('Key Features', GMRW_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li><?php esc_html_e('No API key required', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Multiple display layouts: List, Cards, Carousel, Grid', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('WordPress widget and shortcode support', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Review filtering and sorting options', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Responsive design with accessibility features', GMRW_TEXT_DOMAIN); ?></li>
                </ul>
            </section>
            
            <section id="shortcode-usage">
                <h2><?php esc_html_e('Shortcode Usage', GMRW_TEXT_DOMAIN); ?></h2>
                
                <h3><?php esc_html_e('Basic Shortcode', GMRW_TEXT_DOMAIN); ?></h3>
                <pre><code>[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]</code></pre>
                
                <h3><?php esc_html_e('Advanced Shortcode', GMRW_TEXT_DOMAIN); ?></h3>
                <pre><code>[google_maps_reviews 
    business_url="https://www.google.com/maps/place/Your+Business+Name"
    layout="cards"
    max_reviews="10"
    show_rating="true"
    show_date="true"
    show_filters="true"
    min_rating="4"
]</code></pre>
                
                <h3><?php esc_html_e('Common Parameters', GMRW_TEXT_DOMAIN); ?></h3>
                <table class="gmrw-doc-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Parameter', GMRW_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Description', GMRW_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Default', GMRW_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>business_url</code></td>
                            <td><?php esc_html_e('Google Maps business URL (required)', GMRW_TEXT_DOMAIN); ?></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><?php esc_html_e('Display layout: list, cards, carousel, grid', GMRW_TEXT_DOMAIN); ?></td>
                            <td>list</td>
                        </tr>
                        <tr>
                            <td><code>max_reviews</code></td>
                            <td><?php esc_html_e('Maximum number of reviews (1-50)', GMRW_TEXT_DOMAIN); ?></td>
                            <td>5</td>
                        </tr>
                        <tr>
                            <td><code>show_filters</code></td>
                            <td><?php esc_html_e('Show filtering controls', GMRW_TEXT_DOMAIN); ?></td>
                            <td>false</td>
                        </tr>
                        <tr>
                            <td><code>min_rating</code></td>
                            <td><?php esc_html_e('Minimum rating filter (1-5)', GMRW_TEXT_DOMAIN); ?></td>
                            <td>0</td>
                        </tr>
                    </tbody>
                </table>
            </section>
            
            <section id="troubleshooting">
                <h2><?php esc_html_e('Troubleshooting', GMRW_TEXT_DOMAIN); ?></h2>
                
                <h3><?php esc_html_e('No Reviews Displayed', GMRW_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li><?php esc_html_e('Check that the business URL is correct and accessible', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Verify that the business has public reviews on Google Maps', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Try refreshing the cache in the settings', GMRW_TEXT_DOMAIN); ?></li>
                </ul>
                
                <h3><?php esc_html_e('Styling Issues', GMRW_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li><?php esc_html_e('Check for CSS conflicts with your theme', GMRW_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Add custom CSS to override conflicting styles', GMRW_TEXT_DOMAIN); ?></li>
                </ul>
            </section>
        </div>
    </div>
</div>

<style>
.gmrw-documentation {
    margin-top: 20px;
}

.gmrw-doc-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 30px;
}

.gmrw-doc-content section {
    margin-bottom: 40px;
}

.gmrw-doc-content h2 {
    color: #23282d;
    font-size: 24px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
}

.gmrw-doc-content h3 {
    color: #23282d;
    font-size: 20px;
    margin: 30px 0 15px 0;
}

.gmrw-doc-content p {
    line-height: 1.6;
    margin-bottom: 15px;
}

.gmrw-doc-content ul {
    margin-bottom: 15px;
    padding-left: 20px;
}

.gmrw-doc-content li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.gmrw-doc-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: #fff;
}

.gmrw-doc-table th,
.gmrw-doc-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
    vertical-align: top;
}

.gmrw-doc-table th {
    background: #f9f9f9;
    font-weight: 600;
    color: #23282d;
}

.gmrw-doc-table code {
    background: #f6f7f7;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.gmrw-doc-content pre {
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    overflow-x: auto;
    margin: 15px 0;
}

.gmrw-doc-content pre code {
    background: none;
    padding: 0;
    border-radius: 0;
    font-size: 13px;
    line-height: 1.4;
}
</style>
