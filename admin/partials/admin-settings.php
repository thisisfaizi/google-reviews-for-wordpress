<?php
/**
 * Admin Settings Page Template
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
    
    <div class="gmrw-admin-header">
        <div class="gmrw-admin-header-content">
            <h2><?php esc_html_e('Google Maps Reviews Settings', GMRW_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Configure your Google Maps Reviews plugin settings below.', GMRW_TEXT_DOMAIN); ?></p>
        </div>
        
        <div class="gmrw-admin-header-actions">
            <button type="button" class="button button-secondary" id="refresh-cache">
                <?php esc_html_e('Refresh Cache', GMRW_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button button-secondary" id="clear-cache">
                <?php esc_html_e('Clear Cache', GMRW_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button button-secondary" id="export-settings">
                <?php esc_html_e('Export Settings', GMRW_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button button-secondary" id="import-settings">
                <?php esc_html_e('Import Settings', GMRW_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>
    
    <div class="gmrw-admin-content">
        <div class="gmrw-admin-main">
            <form method="post" action="options.php">
                <?php
                settings_fields(GMRW_PLUGIN_SLUG);
                do_settings_sections(GMRW_PLUGIN_SLUG);
                submit_button();
                ?>
            </form>
        </div>
        
        <div class="gmrw-admin-sidebar">
            <div class="gmrw-admin-widget">
                <h3><?php esc_html_e('Quick Actions', GMRW_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li>
                        <a href="<?php echo admin_url('widgets.php'); ?>">
                            <?php esc_html_e('Manage Widgets', GMRW_TEXT_DOMAIN); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('options-general.php?page=' . GMRW_PLUGIN_SLUG . '-docs'); ?>">
                            <?php esc_html_e('View Documentation', GMRW_TEXT_DOMAIN); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('options-general.php?page=' . GMRW_PLUGIN_SLUG . '-status'); ?>">
                            <?php esc_html_e('System Status', GMRW_TEXT_DOMAIN); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="gmrw-admin-widget">
                <h3><?php esc_html_e('Shortcode Examples', GMRW_TEXT_DOMAIN); ?></h3>
                <div class="gmrw-shortcode-examples">
                    <p><strong><?php esc_html_e('Basic Usage:', GMRW_TEXT_DOMAIN); ?></strong></p>
                    <code>[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name"]</code>
                    
                    <p><strong><?php esc_html_e('With Options:', GMRW_TEXT_DOMAIN); ?></strong></p>
                    <code>[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" layout="cards" max_reviews="10"]</code>
                    
                    <p><strong><?php esc_html_e('With Filtering:', GMRW_TEXT_DOMAIN); ?></strong></p>
                    <code>[google_maps_reviews business_url="https://www.google.com/maps/place/Your+Business+Name" show_filters="true" min_rating="4"]</code>
                </div>
            </div>
            
            <div class="gmrw-admin-widget">
                <h3><?php esc_html_e('Plugin Information', GMRW_TEXT_DOMAIN); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Version:', GMRW_TEXT_DOMAIN); ?></strong> <?php echo esc_html(GMRW_VERSION); ?></li>
                    <li><strong><?php esc_html_e('Author:', GMRW_TEXT_DOMAIN); ?></strong> <?php echo esc_html(GMRW_AUTHOR); ?></li>
                    <li><strong><?php esc_html_e('Support:', GMRW_TEXT_DOMAIN); ?></strong> <a href="<?php echo esc_url(GMRW_AUTHOR_URI); ?>" target="_blank"><?php esc_html_e('Visit Website', GMRW_TEXT_DOMAIN); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Import Settings Modal -->
<div id="import-modal" class="gmrw-modal" style="display: none;">
    <div class="gmrw-modal-content">
        <div class="gmrw-modal-header">
            <h3><?php esc_html_e('Import Settings', GMRW_TEXT_DOMAIN); ?></h3>
            <span class="gmrw-modal-close">&times;</span>
        </div>
        <div class="gmrw-modal-body">
            <p><?php esc_html_e('Paste your exported settings JSON data below:', GMRW_TEXT_DOMAIN); ?></p>
            <textarea id="import-data" rows="10" cols="50" placeholder='{"version":"1.0.0","settings":{...}}'></textarea>
        </div>
        <div class="gmrw-modal-footer">
            <button type="button" class="button button-primary" id="confirm-import">
                <?php esc_html_e('Import Settings', GMRW_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="button button-secondary" id="cancel-import">
                <?php esc_html_e('Cancel', GMRW_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Test business URL
    $('#test-business-url').on('click', function() {
        var button = $(this);
        var resultSpan = $('#test-result');
        var businessUrl = $('#business_url').val();
        
        if (!businessUrl) {
            resultSpan.html('<span class="error"><?php esc_html_e('Please enter a business URL first.', GMRW_TEXT_DOMAIN); ?></span>');
            return;
        }
        
        button.prop('disabled', true).text(gmrwAdmin.strings.testing);
        resultSpan.html('<span class="loading"><?php esc_html_e('Testing...', GMRW_TEXT_DOMAIN); ?></span>');
        
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_test_business_url',
                business_url: businessUrl,
                nonce: gmrwAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultSpan.html('<span class="success">' + response.data.message + '</span>');
                } else {
                    resultSpan.html('<span class="error">' + response.data + '</span>');
                }
            },
            error: function() {
                resultSpan.html('<span class="error"><?php esc_html_e('An error occurred while testing the URL.', GMRW_TEXT_DOMAIN); ?></span>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Test URL', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Refresh cache
    $('#refresh-cache').on('click', function() {
        if (!confirm(gmrwAdmin.strings.confirmRefresh)) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_html_e('Refreshing...', GMRW_TEXT_DOMAIN); ?>');
        
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_refresh_cache',
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
                alert('<?php esc_html_e('An error occurred while refreshing the cache.', GMRW_TEXT_DOMAIN); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Refresh Cache', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Clear cache
    $('#clear-cache').on('click', function() {
        if (!confirm(gmrwAdmin.strings.confirmClear)) {
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
                button.prop('disabled', false).text('<?php esc_html_e('Clear Cache', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Export settings
    $('#export-settings').on('click', function() {
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_export_settings',
                nonce: gmrwAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var dataStr = JSON.stringify(response.data, null, 2);
                    var dataBlob = new Blob([dataStr], {type: 'application/json'});
                    var url = window.URL.createObjectURL(dataBlob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'gmrw-settings-' + new Date().toISOString().slice(0, 10) + '.json';
                    link.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('<?php esc_html_e('Error:', GMRW_TEXT_DOMAIN); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred while exporting settings.', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Import settings
    $('#import-settings').on('click', function() {
        $('#import-modal').show();
    });
    
    // Modal close
    $('.gmrw-modal-close, #cancel-import').on('click', function() {
        $('#import-modal').hide();
        $('#import-data').val('');
    });
    
    // Confirm import
    $('#confirm-import').on('click', function() {
        var importData = $('#import-data').val();
        if (!importData) {
            alert('<?php esc_html_e('Please enter settings data to import.', GMRW_TEXT_DOMAIN); ?>');
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_html_e('Importing...', GMRW_TEXT_DOMAIN); ?>');
        
        $.ajax({
            url: gmrwAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmrw_import_settings',
                settings: importData,
                nonce: gmrwAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#import-modal').hide();
                    $('#import-data').val('');
                    location.reload();
                } else {
                    alert('<?php esc_html_e('Error:', GMRW_TEXT_DOMAIN); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred while importing settings.', GMRW_TEXT_DOMAIN); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Import Settings', GMRW_TEXT_DOMAIN); ?>');
            }
        });
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if (event.target.id === 'import-modal') {
            $('#import-modal').hide();
            $('#import-data').val('');
        }
    });
});
</script>
