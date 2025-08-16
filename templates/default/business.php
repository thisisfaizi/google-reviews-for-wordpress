<?php
/**
 * Default Business Template
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

<div class="gmrw-business-info gmrw-theme-default">
    
    <!-- Business Header -->
    <div class="gmrw-business-header">
        <?php if (!empty($business['image'])) : ?>
            <div class="gmrw-business-image">
                <img src="<?php echo esc_url($business['image']); ?>" 
                     alt="<?php echo esc_attr($business['name']); ?>" 
                     class="gmrw-business-logo"
                     loading="lazy">
            </div>
        <?php endif; ?>
        
        <div class="gmrw-business-details">
            <h3 class="gmrw-business-name"><?php echo esc_html($business['name']); ?></h3>
            
            <?php if (!empty($business['address'])) : ?>
                <div class="gmrw-business-address">
                    <i class="gmrw-icon-location"></i>
                    <span><?php echo esc_html($business['address']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($business['phone'])) : ?>
                <div class="gmrw-business-phone">
                    <i class="gmrw-icon-phone"></i>
                    <a href="tel:<?php echo esc_attr($business['phone']); ?>"><?php echo esc_html($business['phone']); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Business Rating -->
    <div class="gmrw-business-rating">
        <div class="gmrw-rating-display">
            <?php echo Google_Maps_Reviews_Templates::render_stars($business['rating']); ?>
            <div class="gmrw-rating-details">
                <span class="gmrw-rating-number"><?php echo esc_html($business['rating']); ?></span>
                <span class="gmrw-rating-max">/5</span>
                <span class="gmrw-total-reviews">
                    <?php printf(_n('(%d review)', '(%d reviews)', $business['total_reviews'], GMRW_TEXT_DOMAIN), $business['total_reviews']); ?>
                </span>
            </div>
        </div>
        
        <?php if (!empty($business['categories'])) : ?>
            <div class="gmrw-business-categories">
                <?php foreach ($business['categories'] as $category) : ?>
                    <span class="gmrw-category"><?php echo esc_html($category); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Business Actions -->
    <div class="gmrw-business-actions">
        <?php if (!empty($business['website'])) : ?>
            <a href="<?php echo esc_url($business['website']); ?>" 
               class="gmrw-business-website" 
               target="_blank" 
               rel="noopener noreferrer">
                <i class="gmrw-icon-globe"></i>
                <?php _e('Visit Website', GMRW_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
        
        <?php if (!empty($business['maps_url'])) : ?>
            <a href="<?php echo esc_url($business['maps_url']); ?>" 
               class="gmrw-business-maps" 
               target="_blank" 
               rel="noopener noreferrer">
                <i class="gmrw-icon-map"></i>
                <?php _e('View on Google Maps', GMRW_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>
    
</div>
