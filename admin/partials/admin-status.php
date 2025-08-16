<?php
/**
 * Admin Status Page Template
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
    
    <div class="gmrw-status-content">
        <div class="gmrw-status-section">
            <h2><?php esc_html_e('System Information', GMRW_TEXT_DOMAIN); ?></h2>
            <table class="gmrw-status-table">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(GMRW_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('MySQL Version:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(mysqli_get_server_info(mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME))); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Web Server:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="gmrw-status-section">
            <h2><?php esc_html_e('Plugin Health Check', GMRW_TEXT_DOMAIN); ?></h2>
            <table class="gmrw-status-table">
                <tbody>
                    <?php
                    // Check if required PHP extensions are available
                    $required_extensions = array('curl', 'dom', 'simplexml');
                    foreach ($required_extensions as $ext) {
                        $status = extension_loaded($ext) ? 'success' : 'error';
                        $message = extension_loaded($ext) ? __('Available', GMRW_TEXT_DOMAIN) : __('Missing', GMRW_TEXT_DOMAIN);
                        ?>
                        <tr>
                            <td><strong><?php printf(esc_html__('PHP %s Extension:', GMRW_TEXT_DOMAIN), strtoupper($ext)); ?></strong></td>
                            <td class="gmrw-status-<?php echo $status; ?>"><?php echo esc_html($message); ?></td>
                        </tr>
                        <?php
                    }
                    
                    // Check if cURL functions are available
                    $curl_status = function_exists('curl_init') ? 'success' : 'error';
                    $curl_message = function_exists('curl_init') ? __('Available', GMRW_TEXT_DOMAIN) : __('Missing', GMRW_TEXT_DOMAIN);
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('cURL Functions:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td class="gmrw-status-<?php echo $curl_status; ?>"><?php echo esc_html($curl_message); ?></td>
                    </tr>
                    
                    <?php
                    // Check if DOM functions are available
                    $dom_status = class_exists('DOMDocument') ? 'success' : 'error';
                    $dom_message = class_exists('DOMDocument') ? __('Available', GMRW_TEXT_DOMAIN) : __('Missing', GMRW_TEXT_DOMAIN);
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('DOM Functions:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td class="gmrw-status-<?php echo $dom_status; ?>"><?php echo esc_html($dom_message); ?></td>
                    </tr>
                    
                    <?php
                    // Check if SimpleXML functions are available
                    $simplexml_status = function_exists('simplexml_load_string') ? 'success' : 'error';
                    $simplexml_message = function_exists('simplexml_load_string') ? __('Available', GMRW_TEXT_DOMAIN) : __('Missing', GMRW_TEXT_DOMAIN);
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('SimpleXML Functions:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td class="gmrw-status-<?php echo $simplexml_status; ?>"><?php echo esc_html($simplexml_message); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="gmrw-status-section">
            <h2><?php esc_html_e('Cache Status', GMRW_TEXT_DOMAIN); ?></h2>
            <table class="gmrw-status-table">
                <tbody>
                    <?php
                    // Check cache directory permissions
                    $cache_dir = WP_CONTENT_DIR . '/cache/gmrw/';
                    $cache_dir_status = is_dir($cache_dir) && is_writable($cache_dir) ? 'success' : 'error';
                    $cache_dir_message = is_dir($cache_dir) && is_writable($cache_dir) ? __('Writable', GMRW_TEXT_DOMAIN) : __('Not writable', GMRW_TEXT_DOMAIN);
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('Cache Directory:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td class="gmrw-status-<?php echo $cache_dir_status; ?>"><?php echo esc_html($cache_dir_message); ?></td>
                    </tr>
                    
                    <?php
                    // Check WordPress transients
                    $transients_status = 'success';
                    $transients_message = __('Available', GMRW_TEXT_DOMAIN);
                    if (!function_exists('set_transient')) {
                        $transients_status = 'error';
                        $transients_message = __('Not available', GMRW_TEXT_DOMAIN);
                    }
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Transients:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td class="gmrw-status-<?php echo $transients_status; ?>"><?php echo esc_html($transients_message); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="gmrw-status-section">
            <h2><?php esc_html_e('Plugin Settings', GMRW_TEXT_DOMAIN); ?></h2>
            <table class="gmrw-status-table">
                <tbody>
                    <?php
                    $settings = Google_Maps_Reviews_Config::get_settings();
                    ?>
                    <tr>
                        <td><strong><?php esc_html_e('Default Business URL:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo !empty($settings['business_url']) ? esc_url($settings['business_url']) : __('Not set', GMRW_TEXT_DOMAIN); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Cache Duration:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html($settings['cache_duration'] ?? GMRW_CACHE_DURATION); ?> <?php esc_html_e('seconds', GMRW_TEXT_DOMAIN); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Default Layout:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($settings['default_layout'] ?? GMRW_DEFAULT_LAYOUT)); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Max Reviews:', GMRW_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html($settings['max_reviews'] ?? GMRW_DEFAULT_MAX_REVIEWS); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="gmrw-status-section">
            <h2><?php esc_html_e('Quick Actions', GMRW_TEXT_DOMAIN); ?></h2>
            <div class="gmrw-status-actions">
                <button type="button" class="button button-secondary" id="refresh-status">
                    <?php esc_html_e('Refresh Status', GMRW_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button button-secondary" id="test-connection">
                    <?php esc_html_e('Test Connection', GMRW_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button button-secondary" id="clear-all-cache">
                    <?php esc_html_e('Clear All Cache', GMRW_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.gmrw-status-content {
    margin-top: 20px;
}

.gmrw-status-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.gmrw-status-section h2 {
    color: #23282d;
    font-size: 18px;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.gmrw-status-table {
    width: 100%;
    border-collapse: collapse;
}

.gmrw-status-table td {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}

.gmrw-status-table td:first-child {
    width: 200px;
    font-weight: 500;
}

.gmrw-status-table tr:last-child td {
    border-bottom: none;
}

.gmrw-status-success {
    color: #46b450;
    font-weight: 500;
}

.gmrw-status-error {
    color: #dc3232;
    font-weight: 500;
}

.gmrw-status-warning {
    color: #ffb900;
    font-weight: 500;
}

.gmrw-status-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gmrw-status-actions .button {
    margin: 0;
}

@media screen and (max-width: 782px) {
    .gmrw-status-table td:first-child {
        width: auto;
        display: block;
        padding-bottom: 5px;
    }
    
    .gmrw-status-table td:last-child {
        display: block;
        padding-top: 0;
    }
    
    .gmrw-status-actions {
        flex-direction: column;
    }
    
    .gmrw-status-actions .button {
        width: 100%;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Refresh status
    $('#refresh-status').on('click', function() {
        location.reload();
    });
    
    // Test connection
    $('#test-connection').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_html_e('Testing...', GMRW_TEXT_DOMAIN); ?>');
        
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_test_connection',
                nonce: gmrwAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e('Connection test successful!', GMRW_TEXT_DOMAIN); ?>');
                } else {
                    alert('<?php esc_html_e('Connection test failed:', GMRW_TEXT_DOMAIN); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred during the connection test.', GMRW_TEXT_DOMAIN); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Test Connection', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Clear all cache
    $('#clear-all-cache').on('click', function() {
        if (!confirm('<?php esc_html_e('Are you sure you want to clear all cached data?', GMRW_TEXT_DOMAIN); ?>')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_html_e('Clearing...', GMRW_TEXT_DOMAIN); ?>');
        
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_clear_cache',
                nonce: gmrwAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('<?php esc_html_e('Error:', GMRW_TEXT_DOMAIN); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred while clearing the cache.', GMRW_TEXT_DOMAIN); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Clear All Cache', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
});
</script>
