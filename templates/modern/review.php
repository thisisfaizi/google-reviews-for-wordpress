<?php
/**
 * Modern Review Template
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
$index = Google_Maps_Reviews_Templates::get_var('index');
$total = Google_Maps_Reviews_Templates::get_var('total');

if (!$review) {
    return;
}
?>

<div class="gmrw-review gmrw-theme-modern" data-rating="<?php echo esc_attr($review['rating']); ?>" data-date="<?php echo esc_attr($review['date']); ?>">
    
    <!-- Review Header with Gradient Background -->
    <div class="gmrw-review-header-modern">
        <div class="gmrw-author-section">
            <?php if (!empty($review['author_image'])) : ?>
                <div class="gmrw-author-image-container">
                    <img src="<?php echo esc_url($review['author_image']); ?>" 
                         alt="<?php echo esc_attr($review['author_name']); ?>" 
                         class="gmrw-author-image-modern"
                         loading="lazy">
                </div>
            <?php else : ?>
                <div class="gmrw-author-image-container">
                    <div class="gmrw-author-image-modern gmrw-default-avatar-modern">
                        <?php echo esc_html(substr($review['author_name'], 0, 1)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="gmrw-author-info-modern">
                <h4 class="gmrw-author-name-modern"><?php echo esc_html($review['author_name']); ?></h4>
                <span class="gmrw-review-date-modern"><?php echo esc_html($review['date']); ?></span>
            </div>
        </div>
        
        <div class="gmrw-rating-badge">
            <div class="gmrw-rating-number-modern"><?php echo esc_html($review['rating']); ?></div>
            <div class="gmrw-rating-stars-modern">
                <?php echo Google_Maps_Reviews_Templates::render_stars($review['rating']); ?>
            </div>
        </div>
    </div>
    
    <!-- Review Content -->
    <div class="gmrw-review-content-modern">
        <div class="gmrw-review-text-modern">
            <?php 
            $content = esc_html($review['content']);
            $max_length = isset($options['max_content_length']) ? $options['max_content_length'] : 300;
            
            if (strlen($content) > $max_length) {
                $truncated = substr($content, 0, $max_length);
                $full = $content;
                ?>
                <div class="gmrw-truncated-modern"><?php echo esc_html($truncated); ?>...</div>
                <div class="gmrw-full-modern" style="display: none;"><?php echo esc_html($full); ?></div>
                <button class="gmrw-read-more-modern" type="button">
                    <span class="gmrw-read-more-text"><?php _e('Read more', GMRW_TEXT_DOMAIN); ?></span>
                    <i class="gmrw-icon-chevron-down"></i>
                </button>
                <?php
            } else {
                echo '<p>' . $content . '</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Review Footer -->
    <div class="gmrw-review-footer-modern">
        <?php if (!empty($review['helpful_votes'])) : ?>
            <div class="gmrw-helpful-votes-modern">
                <div class="gmrw-votes-icon">
                    <i class="gmrw-icon-thumbs-up-modern"></i>
                </div>
                <span class="gmrw-votes-count">
                    <?php printf(_n('%d helpful vote', '%d helpful votes', $review['helpful_votes'], GMRW_TEXT_DOMAIN), $review['helpful_votes']); ?>
                </span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($review['owner_response'])) : ?>
            <div class="gmrw-owner-response-modern">
                <div class="gmrw-owner-response-header-modern">
                    <div class="gmrw-owner-badge">
                        <i class="gmrw-icon-verified"></i>
                        <span class="gmrw-owner-label-modern"><?php _e('Owner Response', GMRW_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="gmrw-owner-response-content">
                    <?php 
                    $response = esc_html($review['owner_response']);
                    $response_max_length = isset($options['max_response_length']) ? $options['max_response_length'] : 200;
                    
                    if (strlen($response) > $response_max_length) {
                        $response_truncated = substr($response, 0, $response_max_length);
                        $response_full = $response;
                        ?>
                        <div class="gmrw-truncated-modern"><?php echo esc_html($response_truncated); ?>...</div>
                        <div class="gmrw-full-modern" style="display: none;"><?php echo esc_html($response_full); ?></div>
                        <button class="gmrw-read-more-response-modern" type="button">
                            <span class="gmrw-read-more-text"><?php _e('Read more', GMRW_TEXT_DOMAIN); ?></span>
                            <i class="gmrw-icon-chevron-down"></i>
                        </button>
                        <?php
                    } else {
                        echo '<p>' . $response . '</p>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
</div>
