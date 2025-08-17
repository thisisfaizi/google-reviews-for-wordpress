/**
 * Admin JavaScript for Google Maps Reviews Plugin
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Admin functionality
     */
    var GMRWAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initFormValidation();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Settings form validation
            $('#business_url').on('blur', this.validateBusinessUrl);
            
            // Cache management
            $('#refresh-cache').on('click', this.refreshCache);
            $('#clear-cache').on('click', this.clearCache);
            
            // Settings import/export
            $('#export-settings').on('click', this.exportSettings);
            $('#import-settings').on('click', this.showImportModal);
            
            // Modal functionality
            $('.gmrw-modal-close, #cancel-import').on('click', this.closeImportModal);
            $('#confirm-import').on('click', this.importSettings);
            $(window).on('click', this.closeModalOnOutsideClick);
            
            // Form field dependencies
            this.setupFieldDependencies();
            
            // Auto-save functionality
            this.setupAutoSave();
            
            // Panther status check
            $('#check-panther-status').on('click', this.checkPantherStatus);
            
            // Check Panther status on page load
            this.checkPantherStatus();
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.gmrw-tooltip').each(function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                if (tooltipText) {
                    $element.attr('title', tooltipText);
                }
            });
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            var $form = $('form[action="options.php"]');
            
            $form.on('submit', function(e) {
                var isValid = GMRWAdmin.validateForm($(this));
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        /**
         * Validate business URL
         */
        validateBusinessUrl: function() {
            var $field = $(this);
            var url = $field.val();
            var $errorContainer = $field.siblings('.field-error');
            
            // Remove existing error
            $errorContainer.remove();
            
            if (url && !GMRWAdmin.isValidGoogleMapsUrl(url)) {
                var errorHtml = '<div class="field-error"><span class="error">' + 
                               gmrwAdmin.strings.invalidUrl + '</span></div>';
                $field.after(errorHtml);
                return false;
            }
            
            return true;
        },
        
        /**
         * Check if URL is a valid Google Maps URL
         */
        isValidGoogleMapsUrl: function(url) {
            var googleMapsPatterns = [
                /^https?:\/\/(www\.)?google\.com\/maps\/place\//,
                /^https?:\/\/(www\.)?google\.com\/maps\/search\//,
                /^https?:\/\/(www\.)?google\.com\/maps\/@/
            ];
            
            return googleMapsPatterns.some(function(pattern) {
                return pattern.test(url);
            });
        },
        
        /**
         * Validate entire form
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Validate business URL
            var $businessUrl = $form.find('#business_url');
            if ($businessUrl.length && !GMRWAdmin.validateBusinessUrl.call($businessUrl[0])) {
                isValid = false;
            }
            
            // Validate numeric fields
            $form.find('input[type="number"]').each(function() {
                var $field = $(this);
                var value = parseInt($field.val());
                var min = parseInt($field.attr('min'));
                var max = parseInt($field.attr('max'));
                
                if (isNaN(value) || (min && value < min) || (max && value > max)) {
                    GMRWAdmin.showFieldError($field, gmrwAdmin.strings.invalidNumber);
                    isValid = false;
                } else {
                    GMRWAdmin.clearFieldError($field);
                }
            });
            
            return isValid;
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            GMRWAdmin.clearFieldError($field);
            
            var errorHtml = '<div class="field-error"><span class="error">' + message + '</span></div>';
            $field.after(errorHtml);
            $field.addClass('error');
        },
        
        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            $field.siblings('.field-error').remove();
            $field.removeClass('error');
        },
        
        /**
         * Setup field dependencies
         */
        setupFieldDependencies: function() {
            // Auto refresh dependency
            $('#auto_refresh').on('change', function() {
                var isChecked = $(this).is(':checked');
                var $cacheDuration = $('#cache_duration');
                
                if (isChecked) {
                    $cacheDuration.prop('disabled', false);
                } else {
                    $cacheDuration.prop('disabled', true);
                }
            }).trigger('change');
            
            // Rate limiting dependency
            $('#rate_limiting').on('change', function() {
                var isChecked = $(this).is(':checked');
                var $requestTimeout = $('#request_timeout');
                var $rateLimitDelay = $('#rate_limit_delay');
                
                if (isChecked) {
                    $requestTimeout.prop('disabled', false);
                    $rateLimitDelay.prop('disabled', false);
                } else {
                    $requestTimeout.prop('disabled', true);
                    $rateLimitDelay.prop('disabled', true);
                }
            }).trigger('change');
        },
        
        /**
         * Setup auto-save functionality
         */
        setupAutoSave: function() {
            var autoSaveTimer;
            var $form = $('form[action="options.php"]');
            
            $form.on('change', 'input, select, textarea', function() {
                clearTimeout(autoSaveTimer);
                
                autoSaveTimer = setTimeout(function() {
                    GMRWAdmin.autoSaveSettings($form);
                }, 2000); // Auto-save after 2 seconds of inactivity
            });
        },
        
        /**
         * Check Panther browser automation status
         */
        checkPantherStatus: function() {
            var $statusContainer = $('#panther-status');
            var $button = $('#check-panther-status');
            
            $statusContainer.html('<p>Checking Panther status...</p>');
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gmrw_check_panther_status',
                    nonce: gmrwAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var status = response.data;
                        var statusHtml = '<ul>';
                        
                        if (status.panther_available) {
                            statusHtml += '<li><span class="status-ok">✓</span> Symfony Panther library available</li>';
                        } else {
                            statusHtml += '<li><span class="status-error">✗</span> Symfony Panther library not available</li>';
                        }
                        
                        if (status.client_initialized) {
                            statusHtml += '<li><span class="status-ok">✓</span> Panther client initialized</li>';
                        } else {
                            statusHtml += '<li><span class="status-error">✗</span> Panther client not initialized</li>';
                        }
                        
                        if (status.chrome_driver_available) {
                            statusHtml += '<li><span class="status-ok">✓</span> Chrome driver available</li>';
                        } else {
                            statusHtml += '<li><span class="status-error">✗</span> Chrome driver not available</li>';
                        }
                        
                        if (status.error) {
                            statusHtml += '<li><span class="status-error">✗</span> Error: ' + status.error + '</li>';
                        }
                        
                        statusHtml += '</ul>';
                        
                        $statusContainer.html(statusHtml);
                    } else {
                        $statusContainer.html('<p class="error">Error checking Panther status: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $statusContainer.html('<p class="error">Failed to check Panther status</p>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Auto-save settings
         */
        autoSaveSettings: function($form) {
            var formData = $form.serialize();
            
            $.ajax({
                url: gmrwAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=gmrw_auto_save_settings&nonce=' + gmrwAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        GMRWAdmin.showNotification(gmrwAdmin.strings.autoSaved, 'success');
                    }
                }
            });
        },
        
        /**
         * Refresh cache
         */
        refreshCache: function(e) {
            e.preventDefault();
            
            if (!confirm(gmrwAdmin.strings.confirmRefresh)) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text(gmrwAdmin.strings.refreshing);
            
            $.ajax({
                url: gmrwAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gmrw_refresh_cache',
                    nonce: gmrwAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        GMRWAdmin.showNotification(response.data, 'success');
                    } else {
                        GMRWAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.refreshError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(gmrwAdmin.strings.refreshCache);
                }
            });
        },
        
        /**
         * Clear cache
         */
        clearCache: function(e) {
            e.preventDefault();
            
            if (!confirm(gmrwAdmin.strings.confirmClear)) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text(gmrwAdmin.strings.clearing);
            
            $.ajax({
                url: gmrwAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gmrw_clear_cache',
                    nonce: gmrwAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        GMRWAdmin.showNotification(response.data, 'success');
                    } else {
                        GMRWAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.clearError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(gmrwAdmin.strings.clearCache);
                }
            });
        },
        
        /**
         * Export settings
         */
        exportSettings: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text(gmrwAdmin.strings.exporting);
            
            $.ajax({
                url: gmrwAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gmrw_export_settings',
                    nonce: gmrwAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        GMRWAdmin.downloadFile(response.data, 'gmrw-settings-' + 
                            new Date().toISOString().slice(0, 10) + '.json');
                        GMRWAdmin.showNotification(gmrwAdmin.strings.exportSuccess, 'success');
                    } else {
                        GMRWAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.exportError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(gmrwAdmin.strings.exportSettings);
                }
            });
        },
        
        /**
         * Show import modal
         */
        showImportModal: function(e) {
            e.preventDefault();
            $('#import-modal').show();
        },
        
        /**
         * Close import modal
         */
        closeImportModal: function(e) {
            e.preventDefault();
            $('#import-modal').hide();
            $('#import-data').val('');
        },
        
        /**
         * Close modal when clicking outside
         */
        closeModalOnOutsideClick: function(e) {
            if (e.target.id === 'import-modal') {
                GMRWAdmin.closeImportModal(e);
            }
        },
        
        /**
         * Import settings
         */
        importSettings: function(e) {
            e.preventDefault();
            
            var importData = $('#import-data').val();
            if (!importData) {
                GMRWAdmin.showNotification(gmrwAdmin.strings.importDataRequired, 'error');
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text(gmrwAdmin.strings.importing);
            
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
                        GMRWAdmin.showNotification(response.data, 'success');
                        $('#import-modal').hide();
                        $('#import-data').val('');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        GMRWAdmin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.importError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(gmrwAdmin.strings.importSettings);
                }
            });
        },
        
        /**
         * Download file
         */
        downloadFile: function(data, filename) {
            var dataStr = JSON.stringify(data, null, 2);
            var dataBlob = new Blob([dataStr], {type: 'application/json'});
            var url = window.URL.createObjectURL(dataBlob);
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.click();
            window.URL.revokeObjectURL(url);
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="gmrw-notification gmrw-notification-' + type + '">' +
                                '<span class="gmrw-notification-message">' + message + '</span>' +
                                '<span class="gmrw-notification-close">&times;</span>' +
                                '</div>');
            
            $('body').append($notification);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.find('.gmrw-notification-close').on('click', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        /**
         * Initialize health checks
         */
        initHealthChecks: function() {
            // Check PHP version
            if (typeof gmrwAdmin.phpVersion !== 'undefined') {
                var requiredVersion = '7.4';
                if (GMRWAdmin.compareVersions(gmrwAdmin.phpVersion, requiredVersion) < 0) {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.phpVersionWarning, 'warning');
                }
            }
            
            // Check WordPress version
            if (typeof gmrwAdmin.wpVersion !== 'undefined') {
                var requiredWPVersion = '5.0';
                if (GMRWAdmin.compareVersions(gmrwAdmin.wpVersion, requiredWPVersion) < 0) {
                    GMRWAdmin.showNotification(gmrwAdmin.strings.wpVersionWarning, 'warning');
                }
            }
        },
        
        /**
         * Compare version strings
         */
        compareVersions: function(v1, v2) {
            var v1Parts = v1.split('.').map(Number);
            var v2Parts = v2.split('.').map(Number);
            
            for (var i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
                var v1Part = v1Parts[i] || 0;
                var v2Part = v2Parts[i] || 0;
                
                if (v1Part > v2Part) return 1;
                if (v1Part < v2Part) return -1;
            }
            
            return 0;
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        GMRWAdmin.init();
        GMRWAdmin.initHealthChecks();
    });
    
    /**
     * Make GMRWAdmin available globally
     */
    window.GMRWAdmin = GMRWAdmin;
    
})(jQuery);
