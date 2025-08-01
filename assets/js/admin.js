/**
 * Cookie Consent Lite - Admin JavaScript - Production Ready
 * 
 * @package CookieConsentLite
 */

(function($) {
    'use strict';

    // Admin object
    var CCL_Admin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.initColorPickers();
            this.initToggleSwitches();
            this.initCacheClearButton();
            this.initFormValidation();
            this.initPreview();
            this.initCharacterCounters();
            this.initConditionalFields();
            this.initKeyboardShortcuts();
        },
        
        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.ccl-color-picker, input[type="color"]').wpColorPicker({
                    change: function(event, ui) {
                        // Trigger change event for any listeners
                        $(this).trigger('ccl:color-changed', ui.color.toString());
                    }
                });
            }
        },
        
        /**
         * Initialize toggle switches
         */
        initToggleSwitches: function() {
            $('.ccl-toggle input[type="checkbox"]').on('change', function() {
                var $toggle = $(this).closest('.ccl-toggle');
                var $slider = $toggle.find('.ccl-toggle-slider');
                
                if ($(this).is(':checked')) {
                    $slider.addClass('ccl-toggle-active');
                    $toggle.addClass('ccl-toggle-on');
                } else {
                    $slider.removeClass('ccl-toggle-active');
                    $toggle.removeClass('ccl-toggle-on');
                }
            });
            
            // Initialize current states
            $('.ccl-toggle input[type="checkbox"]:checked').each(function() {
                var $toggle = $(this).closest('.ccl-toggle');
                var $slider = $toggle.find('.ccl-toggle-slider');
                $slider.addClass('ccl-toggle-active');
                $toggle.addClass('ccl-toggle-on');
            });
        },
        
        /**
         * Initialize cache clear button with enhanced error handling
         */
        initCacheClearButton: function() {
            $('#ccl-clear-cache').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $status = $('#ccl-cache-status');
                var originalText = $button.text();
                
                // Prevent double-clicks
                if ($button.prop('disabled')) {
                    return false;
                }
                
                // Show loading state
                $button.prop('disabled', true)
                       .text(ccl_admin.strings.clearing || 'Clearing...')
                       .addClass('ccl-loading');
                
                $status.html('<span style="color: #666;">⏳ ' + (ccl_admin.strings.clearing_caches || 'Clearing caches...') + '</span>');
                
                $.ajax({
                    url: ccl_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ccl_clear_cache',
                        nonce: ccl_admin.nonce
                    },
                    timeout: 30000, // 30 second timeout
                    success: function(response) {
                        if (response && response.success) {
                            $status.html('<span style="color: #46b450;">✅ ' + (response.data || 'Cache cleared successfully') + '</span>');
                            
                            // Show success animation
                            $button.addClass('ccl-success');
                            setTimeout(function() {
                                $button.removeClass('ccl-success');
                            }, 2000);
                        } else {
                            $status.html('<span style="color: #dc3232;">❌ ' + (response.data || 'Cache clearing failed') + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Cache clearing failed';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Cache may have been cleared.';
                        } else if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMessage = xhr.responseJSON.data;
                        } else if (error) {
                            errorMessage = 'Error: ' + error;
                        }
                        
                        $status.html('<span style="color: #dc3232;">❌ ' + errorMessage + '</span>');
                    },
                    complete: function() {
                        // Reset button state
                        $button.prop('disabled', false)
                               .text(originalText)
                               .removeClass('ccl-loading');
                        
                        // Auto-hide status after 5 seconds
                        setTimeout(function() {
                            $status.fadeOut(function() {
                                $(this).html('').show();
                            });
                        }, 5000);
                    }
                });
                
                return false;
            });
        },
        
        /**
         * Initialize form validation with enhanced checks
         */
        initFormValidation: function() {
            $('#ccl-settings-form').on('submit', function(e) {
                var isValid = true;
                var errors = [];
                
                try {
                    // Validate banner text
                    var bannerText = $('textarea[name="ccl_settings[banner_text]"]').val();
                    if (bannerText.length < 10) {
                        errors.push('Banner text must be at least 10 characters long.');
                        isValid = false;
                    }
                    if (bannerText.length > 1000) {
                        errors.push('Banner text must be less than 1000 characters.');
                        isValid = false;
                    }
                    
                    // Validate button labels
                    var requiredFields = [
                        {field: 'accept_button_label', name: 'Accept button label', min: 1, max: 50},
                        {field: 'reject_button_label', name: 'Reject button label', min: 1, max: 50},
                        {field: 'settings_button_label', name: 'Settings button label', min: 0, max: 50}
                    ];
                    
                    requiredFields.forEach(function(fieldConfig) {
                        var value = $('input[name="ccl_settings[' + fieldConfig.field + ']"]').val();
                        if (fieldConfig.min > 0 && value.length < fieldConfig.min) {
                            errors.push(fieldConfig.name + ' is required.');
                            isValid = false;
                        }
                        if (value.length > fieldConfig.max) {
                            errors.push(fieldConfig.name + ' must be less than ' + fieldConfig.max + ' characters.');
                            isValid = false;
                        }
                    });
                    
                    // Validate consent expiration
                    var expiration = parseInt($('input[name="ccl_settings[consent_expiration]"]').val());
                    if (isNaN(expiration) || expiration < 1 || expiration > 365) {
                        errors.push('Consent expiration must be between 1 and 365 days.');
                        isValid = false;
                    }
                    
                    // Validate learn more URL if provided
                    var learnMoreUrl = $('input[name="ccl_settings[learn_more_url]"]').val();
                    if (learnMoreUrl && !CCL_Admin.isValidUrl(learnMoreUrl)) {
                        errors.push('Learn more URL is not valid.');
                        isValid = false;
                    }
                    
                    // Validate colors
                    $('input[type="color"]').each(function() {
                        var color = $(this).val();
                        if (color && !CCL_Admin.isValidColor(color)) {
                            errors.push('Invalid color value: ' + color);
                            isValid = false;
                        }
                    });
                    
                } catch (error) {
                    errors.push('Form validation error occurred.');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    CCL_Admin.showValidationErrors(errors);
                    return false;
                }
                
                // Show saving state
                var $submitButton = $(this).find('.ccl-save-button');
                var originalText = $submitButton.val();
                $submitButton.prop('disabled', true)
                            .val(ccl_admin.strings.saving || 'Saving & Clearing Cache...')
                            .addClass('ccl-loading');
                
                // Re-enable after timeout as fallback
                setTimeout(function() {
                    $submitButton.prop('disabled', false)
                                .val(originalText)
                                .removeClass('ccl-loading');
                }, 10000);
                
                return true;
            });
        },
        
        /**
         * Show validation errors with enhanced styling
         */
        showValidationErrors: function(errors) {
            if (!errors || errors.length === 0) return;
            
            // Remove existing error notices
            $('.ccl-validation-errors').remove();
            
            var errorHtml = '<div class="notice notice-error is-dismissible ccl-validation-errors">';
            errorHtml += '<p><strong>' + (ccl_admin.strings.fix_errors || 'Please fix the following errors:') + '</strong></p>';
            errorHtml += '<ul>';
            errors.forEach(function(error) {
                errorHtml += '<li>' + CCL_Admin.escapeHtml(error) + '</li>';
            });
            errorHtml += '</ul>';
            errorHtml += '</div>';
            
            $('.wrap .ccl-admin-header').after(errorHtml);
            
            // Make dismissible
            $('.ccl-validation-errors .notice-dismiss').on('click', function() {
                $(this).closest('.notice').fadeOut();
            });
            
            // Scroll to top to show errors
            $('html, body').animate({
                scrollTop: $('.wrap').offset().top - 50
            }, 500);
        },
        
        /**
         * Initialize preview functionality
         */
        initPreview: function() {
            // Real-time preview updates (debounced)
            var previewTimeout;
            
            $('input, textarea, select').on('input change', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(function() {
                    CCL_Admin.updatePreview();
                }, 1000);
            });
            
            // Preview button
            $('.ccl-preview-btn').on('click', function(e) {
                e.preventDefault();
                var previewUrl = $(this).attr('href');
                window.open(previewUrl, 'ccl_preview', 'width=1200,height=800,scrollbars=yes,resizable=yes');
                return false;
            });
        },
        
        /**
         * Update live preview (placeholder for future enhancement)
         */
        updatePreview: function() {
            // Future enhancement: Real-time preview updates
        },
        
        /**
         * Character counter for textareas
         */
        initCharacterCounters: function() {
            $('textarea[maxlength], input[maxlength]').each(function() {
                var $field = $(this);
                var maxLength = parseInt($field.attr('maxlength'));
                
                if (!maxLength) return;
                
                var $counter = $('<div class="character-counter"></div>');
                $field.after($counter);
                
                $field.on('input keyup', function() {
                    var currentLength = $(this).val().length;
                    var remaining = maxLength - currentLength;
                    
                    $counter.text(currentLength + ' / ' + maxLength + ' characters');
                    
                    // Color coding
                    $counter.removeClass('warning error');
                    if (remaining < maxLength * 0.1) { // Less than 10% remaining
                        $counter.addClass('warning');
                    }
                    if (remaining < 0) {
                        $counter.addClass('error');
                    }
                });
                
                // Trigger initial count
                $field.trigger('input');
            });
        },
        
        /**
         * Initialize conditional fields
         */
        initConditionalFields: function() {
            // Show/hide fields based on other field values
            $('input[name="ccl_settings[show_settings_button]"]').on('change', function() {
                var $settingsLabelField = $('input[name="ccl_settings[settings_button_label]"]').closest('tr');
                if ($(this).is(':checked')) {
                    $settingsLabelField.fadeIn();
                } else {
                    $settingsLabelField.fadeOut();
                }
            }).trigger('change');
            
            $('input[name="ccl_settings[show_reject_button]"]').on('change', function() {
                var $rejectLabelField = $('input[name="ccl_settings[reject_button_label]"]').closest('tr');
                if ($(this).is(':checked')) {
                    $rejectLabelField.fadeIn();
                } else {
                    $rejectLabelField.fadeOut();
                }
            }).trigger('change');
            
            // Learn more URL field based on text field
            $('input[name="ccl_settings[learn_more_text]"]').on('input', function() {
                var $urlField = $('input[name="ccl_settings[learn_more_url]"]').closest('tr');
                if ($(this).val().trim()) {
                    $urlField.fadeIn();
                } else {
                    $urlField.fadeOut();
                }
            }).trigger('input');
        },
        
        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl+S or Cmd+S to save
                if ((e.ctrlKey || e.metaKey) && e.which === 83) {
                    e.preventDefault();
                    $('#ccl-settings-form').submit();
                    return false;
                }
                
                // Ctrl+Shift+C to clear cache
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.which === 67) {
                    e.preventDefault();
                    $('#ccl-clear-cache').click();
                    return false;
                }
            });
        },
        
        /**
         * Utility function to validate URL
         */
        isValidUrl: function(string) {
            try {
                // Try absolute URL first
                new URL(string);
                return true;
            } catch (_) {
                // Check for relative URLs
                return /^\/[^\/]/.test(string);
            }
        },
        
        /**
         * Utility function to validate color
         */
        isValidColor: function(color) {
            if (!color) return false;
            
            // Check hex color format
            return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'success';
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + CCL_Admin.escapeHtml(message) + '</p></div>');
            $('.wrap .ccl-admin-header').after($notification);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.fadeOut();
            }, 5000);
        },
        
        /**
         * Initialize tooltips (if needed)
         */
        initTooltips: function() {
            $('.ccl-tooltip').hover(
                function() {
                    var tooltip = $(this).data('tooltip');
                    if (tooltip) {
                        $('<div class="ccl-tooltip-content">' + CCL_Admin.escapeHtml(tooltip) + '</div>')
                            .appendTo('body')
                            .fadeIn('fast');
                    }
                },
                function() {
                    $('.ccl-tooltip-content').remove();
                }
            ).mousemove(function(e) {
                $('.ccl-tooltip-content').css({
                    top: e.pageY + 10,
                    left: e.pageX + 10
                });
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Set up localized strings fallback
        if (typeof ccl_admin === 'undefined') {
            window.ccl_admin = {
                ajax_url: ajaxurl || '',
                nonce: '',
                strings: {}
            };
        }
        
        // Initialize admin functionality
        try {
            CCL_Admin.init();
        } catch (error) {
            // Silent fail but log error
            if (console && console.error) {
                console.error('CCL Admin initialization failed:', error);
            }
        }
    });
    
    // Expose for debugging (only in development)
    if (window.location.hostname === 'localhost' || 
        window.location.hostname.includes('dev') ||
        window.location.hostname.includes('staging')) {
        window.CCL_Admin_Debug = CCL_Admin;
    }

})(jQuery);