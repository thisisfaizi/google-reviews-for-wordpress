<?php
/**
 * Default Review Template
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

<div class="gmrw-review gmrw-theme-default" data-rating="<?php echo esc_attr($review['rating']); ?>" data-date="<?php echo esc_attr($review['date']); ?>">
    
    <!-- Review Header -->
    <div class="gmrw-review-header">
        <div class="gmrw-author-info">
            <?php if (!empty($review['author_image'])) : ?>
                <img src="<?php echo esc_url($review['author_image']); ?>" 
                     alt="<?php echo esc_attr($review['author_name']); ?>" 
                     class="gmrw-author-image"
                     loading="lazy">
            <?php else : ?>
                <div class="gmrw-author-image gmrw-default-avatar">
                    <?php echo esc_html(substr($review['author_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            
            <div class="gmrw-author-details">
                <h4 class="gmrw-author-name"><?php echo esc_html($review['author_name']); ?></h4>
                <span class="gmrw-review-date"><?php echo esc_html($review['date']); ?></span>
            </div>
        </div>
        
        <div class="gmrw-rating-section">
            <?php echo Google_Maps_Reviews_Templates::render_stars($review['rating']); ?>
            <span class="gmrw-rating-text"><?php echo esc_html($review['rating']); ?>/5</span>
        </div>
    </div>
    
    <!-- Review Content -->
    <div class="gmrw-review-content">
        <div class="gmrw-review-text">
            <?php 
            $content = esc_html($review['content']);
            $max_length = isset($options['max_content_length']) ? $options['max_content_length'] : 300;
            
            if (strlen($content) > $max_length) {
                $truncated = substr($content, 0, $max_length);
                $full = $content;
                ?>
                <div class="gmrw-truncated"><?php echo esc_html($truncated); ?>...</div>
                <div class="gmrw-full" style="display: none;"><?php echo esc_html($full); ?></div>
                <button class="gmrw-read-more" type="button"><?php _e('Read more', GMRW_TEXT_DOMAIN); ?></button>
                <?php
            } else {
                echo '<p>' . $content . '</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Review Footer -->
    <div class="gmrw-review-footer">
        <?php if (!empty($review['helpful_votes'])) : ?>
            <span class="gmrw-helpful-votes">
                <i class="gmrw-icon-thumbs-up"></i>
                <?php printf(_n('%d helpful vote', '%d helpful votes', $review['helpful_votes'], GMRW_TEXT_DOMAIN), $review['helpful_votes']); ?>
            </span>
        <?php endif; ?>
        
        <?php if (!empty($review['owner_response'])) : ?>
            <div class="gmrw-owner-response">
                <div class="gmrw-owner-response-header">
                    <i class="gmrw-icon-reply"></i>
                    <span class="gmrw-owner-label"><?php _e('Owner Response', GMRW_TEXT_DOMAIN); ?></span>
                </div>
                <div class="gmrw-owner-response-text">
                    <?php 
                    $response = esc_html($review['owner_response']);
                    $response_max_length = isset($options['max_response_length']) ? $options['max_response_length'] : 200;
                    
                    if (strlen($response) > $response_max_length) {
                        $response_truncated = substr($response, 0, $response_max_length);
                        $response_full = $response;
                        ?>
                        <div class="gmrw-truncated"><?php echo esc_html($response_truncated); ?>...</div>
                        <div class="gmrw-full" style="display: none;"><?php echo esc_html($response_full); ?></div>
                        <button class="gmrw-read-more-response" type="button"><?php _e('Read more', GMRW_TEXT_DOMAIN); ?></button>
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
