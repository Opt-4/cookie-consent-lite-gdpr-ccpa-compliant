<?php
/**
 * Admin functionality for Cookie Consent Lite - Production Ready
 *
 * @package CookieConsentLite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CCL_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('plugin_action_links_' . CCL_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        add_action('wp_ajax_ccl_clear_cache', array($this, 'ajax_clear_cache'));
        
        // Cache clearing hooks
        add_action('update_option_ccl_settings', array($this, 'clear_cache_on_settings_update'), 10, 2);
        add_action('updated_option', array($this, 'check_ccl_settings_update'), 10, 3);
        add_action('admin_notices', array($this, 'show_cache_cleared_notice'));
        
        // Add cache busting
        add_action('wp_enqueue_scripts', array($this, 'add_cache_busting'), 999);
    }
    
    /**
     * Add cache busting parameter
     */
    public function add_cache_busting() {
        $settings_version = get_option('ccl_settings_version', time());
        
        wp_add_inline_script('jquery', "
            window.ccl_version = '{$settings_version}';
        ", 'before');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Cookie Consent Lite Settings', 'cookie-consent-lite'),
            __('Cookie Consent', 'cookie-consent-lite'),
            'manage_options',
            'cookie-consent-lite',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'ccl_settings_group',
            'ccl_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Enhanced settings sanitization with error handling
     */
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            add_settings_error('ccl_settings', 'invalid_input', __('Invalid settings data provided.', 'cookie-consent-lite'));
            return get_option('ccl_settings', array());
        }
        
        $sanitized = array();
        $errors = array();
        
        try {
            // Boolean fields
            $boolean_fields = array('enable_banner', 'show_reject_button', 'show_settings_button', 'enable_analytics', 'enable_marketing', 'enable_preferences');
            foreach ($boolean_fields as $field) {
                $sanitized[$field] = isset($input[$field]) ? 1 : 0;
            }
            
            // Text fields with length validation
            $text_fields = array(
                'accept_button_label' => array('max' => 50, 'min' => 1, 'default' => __('Accept All', 'cookie-consent-lite')),
                'reject_button_label' => array('max' => 50, 'min' => 1, 'default' => __('Reject', 'cookie-consent-lite')),
                'settings_button_label' => array('max' => 50, 'min' => 1, 'default' => __('Cookie Settings', 'cookie-consent-lite')),
                'learn_more_text' => array('max' => 50, 'min' => 0, 'default' => __('Learn more', 'cookie-consent-lite'))
            );
            
            foreach ($text_fields as $field => $config) {
                $value = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
                $value = trim($value);
                
                if (strlen($value) > $config['max']) {
                    $value = substr($value, 0, $config['max']);
                    $errors[] = sprintf(__('%s was truncated to %d characters.', 'cookie-consent-lite'), $field, $config['max']);
                }
                
                if (strlen($value) < $config['min'] && $config['min'] > 0) {
                    $value = $config['default'];
                    $errors[] = sprintf(__('%s was too short and has been reset to default.', 'cookie-consent-lite'), $field);
                }
                
                $sanitized[$field] = $value;
            }
            
            // Banner text with enhanced validation
            $banner_text = isset($input['banner_text']) ? wp_kses_post($input['banner_text']) : '';
            $banner_text = trim($banner_text);
            
            if (strlen($banner_text) < 10) {
                $errors[] = __('Banner text must be at least 10 characters long.', 'cookie-consent-lite');
                $banner_text = __('This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.', 'cookie-consent-lite');
            }
            
            if (strlen($banner_text) > 1000) {
                $banner_text = substr($banner_text, 0, 1000);
                $errors[] = __('Banner text was truncated to 1000 characters.', 'cookie-consent-lite');
            }
            
            $sanitized['banner_text'] = $banner_text;
            
            // URL validation
            $learn_more_url = isset($input['learn_more_url']) ? esc_url_raw($input['learn_more_url']) : '';
            if (!empty($learn_more_url)) {
                if (!filter_var($learn_more_url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $learn_more_url)) {
                    $errors[] = __('Learn more URL is not valid and has been cleared.', 'cookie-consent-lite');
                    $learn_more_url = '';
                }
            }
            $sanitized['learn_more_url'] = $learn_more_url;
            
            // Select field validation
            $banner_position = isset($input['banner_position']) ? sanitize_text_field($input['banner_position']) : 'bottom';
            $sanitized['banner_position'] = in_array($banner_position, array('top', 'bottom')) ? $banner_position : 'bottom';
            
            // Number validation with range checking
            $consent_expiration = isset($input['consent_expiration']) ? intval($input['consent_expiration']) : 180;
            if ($consent_expiration < 1 || $consent_expiration > 365) {
                $errors[] = __('Consent expiration must be between 1 and 365 days. Reset to default (180).', 'cookie-consent-lite');
                $consent_expiration = 180;
            }
            $sanitized['consent_expiration'] = $consent_expiration;
            
            // Color validation with fallbacks
            $color_fields = array(
                'primary_color' => '#4CAF50',
                'secondary_color' => '#f44336',
                'banner_bg_color' => '#ffffff',
                'text_color' => '#333333'
            );
            
            foreach ($color_fields as $field => $default) {
                $color = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
                $color = sanitize_hex_color($color);
                
                if (empty($color)) {
                    $color = $default;
                    $errors[] = sprintf(__('%s was invalid and reset to default.', 'cookie-consent-lite'), $field);
                }
                
                $sanitized[$field] = $color;
            }
            
            // Show any validation errors
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    add_settings_error('ccl_settings', 'validation_error', $error, 'warning');
                }
            }
            
            // Aggressive cache clearing
            $this->aggressive_cache_clear();
            
            // Update version timestamp
            update_option('ccl_settings_version', time());
            
            // Set success notice
            set_transient('ccl_settings_updated', true, 30);
            set_transient('ccl_cache_cleared', $this->get_cleared_caches_list(), 30);
            
            return $sanitized;
            
        } catch (Exception $e) {
            error_log('Cookie Consent Lite settings sanitization error: ' . $e->getMessage());
            add_settings_error('ccl_settings', 'sanitization_error', __('An error occurred while saving settings. Please try again.', 'cookie-consent-lite'));
            return get_option('ccl_settings', array());
        }
    }
    
    /**
     * Check if CCL settings were updated (backup method)
     */
    public function check_ccl_settings_update($option, $old_value, $value) {
        if ($option === 'ccl_settings') {
            wp_schedule_single_event(time() + 1, 'ccl_delayed_cache_clear');
            add_action('ccl_delayed_cache_clear', array($this, 'aggressive_cache_clear'));
        }
    }
    
    /**
     * Clear cache when settings are updated
     */
    public function clear_cache_on_settings_update($old_value, $new_value) {
        $this->aggressive_cache_clear();
    }
    
    /**
     * Show cache cleared notice
     */
    public function show_cache_cleared_notice() {
        if (get_transient('ccl_settings_updated')) {
            $cleared_caches = get_transient('ccl_cache_cleared');
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Cookie Consent settings updated!', 'cookie-consent-lite') . '</strong></p>';
            if ($cleared_caches) {
                echo '<p>' . sprintf(__('Cleared caches: %s', 'cookie-consent-lite'), esc_html($cleared_caches)) . '</p>';
            }
            echo '<p><em>' . __('If changes don\'t appear immediately, wait 30 seconds or try a hard refresh (Ctrl+F5).', 'cookie-consent-lite') . '</em></p>';
            echo '</div>';
            delete_transient('ccl_settings_updated');
            delete_transient('ccl_cache_cleared');
        }
    }
    
    /**
     * Get list of cleared caches
     */
    private function get_cleared_caches_list() {
        $cleared_caches = $this->aggressive_cache_clear();
        return empty($cleared_caches) ? 'WordPress Core Cache' : implode(', ', $cleared_caches);
    }
    
    /**
     * Admin page content with clean white header and logo
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cookie-consent-lite'));
        }
        
        ?>
        <div class="wrap">
            <!-- Clean White Header with Logo -->
            <div class="ccl-admin-header-clean">
                <div class="ccl-header-logo-container">
                    <img src="<?php echo esc_url(CCL_PLUGIN_URL . 'assets/images/logo-admin.png'); ?>" 
                         alt="<?php esc_attr_e('Cookie Consent Lite', 'cookie-consent-lite'); ?>" 
                         class="ccl-header-logo">
                </div>
                <div class="ccl-header-content">
                    <h1><?php _e('Cookie Consent Lite Settings', 'cookie-consent-lite'); ?></h1>
                    <p><?php _e('Configure your cookie consent banner for GDPR & CCPA compliance.', 'cookie-consent-lite'); ?></p>
                </div>
            </div>
            
            <div class="ccl-admin-layout">
                <div class="ccl-admin-main">
                    <form method="post" action="options.php" id="ccl-settings-form">
                        <?php settings_fields('ccl_settings_group'); ?>
                        
                        <div class="ccl-settings-section">
                            <h2><?php _e('General Settings', 'cookie-consent-lite'); ?></h2>
                            <p class="ccl-section-description"><?php _e('Configure the basic settings for your cookie consent banner.', 'cookie-consent-lite'); ?></p>
                            
                            <table class="form-table ccl-form-table">
                                <?php $this->render_banner_settings(); ?>
                            </table>
                        </div>
                        
                        <div class="ccl-settings-section">
                            <h2><?php _e('Button Text & Visibility', 'cookie-consent-lite'); ?></h2>
                            <p class="ccl-section-description"><?php _e('Customize button labels and visibility options.', 'cookie-consent-lite'); ?></p>
                            
                            <table class="form-table ccl-form-table">
                                <?php $this->render_button_settings(); ?>
                            </table>
                        </div>
                        
                        <div class="ccl-settings-section">
                            <h2><?php _e('Cookie Categories', 'cookie-consent-lite'); ?></h2>
                            <p class="ccl-section-description"><?php _e('Enable or disable specific cookie categories.', 'cookie-consent-lite'); ?></p>
                            
                            <table class="form-table ccl-form-table">
                                <?php $this->render_category_settings(); ?>
                            </table>
                        </div>
                        
                        <div class="ccl-settings-section">
                            <h2><?php _e('Advanced Settings', 'cookie-consent-lite'); ?></h2>
                            <p class="ccl-section-description"><?php _e('Advanced configuration options.', 'cookie-consent-lite'); ?></p>
                            
                            <table class="form-table ccl-form-table">
                                <?php $this->render_advanced_settings(); ?>
                            </table>
                        </div>
                        
                        <?php submit_button(__('Save Changes & Clear Cache', 'cookie-consent-lite'), 'primary ccl-save-button'); ?>
                    </form>
                </div>
                
                <div class="ccl-admin-sidebar">
                    <?php $this->render_sidebar(); ?>
                </div>
            </div>
        </div>
        
        <!-- Clean White Header Styles -->
        <style>
        .ccl-admin-header-clean {
            background: #ffffff !important;
            border: 1px solid #e1e5e9 !important;
            border-radius: 12px !important;
            padding: 24px 32px !important;
            margin: 20px 0 32px 0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            display: flex !important;
            align-items: center !important;
            gap: 24px !important;
            transition: box-shadow 0.2s ease !important;
        }
        
        .ccl-admin-header-clean:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12) !important;
        }
        
        .ccl-header-logo-container {
            flex-shrink: 0 !important;
            transition: transform 0.2s ease !important;
        }
        
        .ccl-header-logo-container:hover {
            transform: scale(1.02) !important;
        }
        
        .ccl-header-logo {
            height: 85px !important;
            width: auto !important;
            max-width: none !important;
            display: block !important;
            transition: filter 0.2s ease !important;
        }
        
        .ccl-header-content {
            flex: 1 !important;
        }
        
        .ccl-header-content h1 {
            margin: 0 0 8px 0 !important;
            font-size: 28px !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
            line-height: 1.2 !important;
            letter-spacing: -0.025em !important;
        }
        
        .ccl-header-content p {
            margin: 0 !important;
            font-size: 16px !important;
            color: #6b7280 !important;
            line-height: 1.5 !important;
            font-weight: 400 !important;
        }
        
        /* Responsive adjustments for logo header */
        @media (max-width: 768px) {
            .ccl-admin-header-clean {
                flex-direction: column !important;
                text-align: center !important;
                padding: 20px !important;
                gap: 16px !important;
            }
            
            .ccl-header-logo {
                height: 70px !important;
            }
            
            .ccl-header-content h1 {
                font-size: 24px !important;
            }
            
            .ccl-header-content p {
                font-size: 15px !important;
            }
        }
        
        @media (max-width: 480px) {
            .ccl-admin-header-clean {
                padding: 16px !important;
            }
            
            .ccl-header-logo {
                height: 60px !important;
            }
            
            .ccl-header-content h1 {
                font-size: 20px !important;
            }
            
            .ccl-header-content p {
                font-size: 14px !important;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Enhanced form submission with loading state
            $('#ccl-settings-form').on('submit', function() {
                var submitButton = $('.ccl-save-button');
                var originalText = submitButton.val();
                submitButton.prop('disabled', true).val('<?php _e('Saving & Clearing Cache...', 'cookie-consent-lite'); ?>');
                
                // Re-enable after timeout as fallback
                setTimeout(function() {
                    submitButton.prop('disabled', false).val(originalText);
                }, 8000);
            });
            
            // Manual cache clear button
            $('#ccl-clear-cache').on('click', function() {
                var button = $(this);
                var status = $('#ccl-cache-status');
                var originalText = button.text();
                
                button.prop('disabled', true).text('<?php _e('Clearing...', 'cookie-consent-lite'); ?>');
                status.html('<span style="color: #666;">⏳ <?php _e('Clearing caches...', 'cookie-consent-lite'); ?></span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ccl_clear_cache',
                        nonce: '<?php echo wp_create_nonce('ccl_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            status.html('<span style="color: green;">✅ ' + response.data + '</span>');
                        } else {
                            status.html('<span style="color: red;">❌ ' + response.data + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        status.html('<span style="color: red;">❌ <?php _e('Error clearing cache', 'cookie-consent-lite'); ?></span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                        setTimeout(function() {
                            $('#ccl-cache-status').fadeOut();
                        }, 5000);
                    }
                });
            });
            
            // Initialize admin toggles
            $('.ccl-admin-toggle').on('click', function(e) {
                // Don't prevent default if clicking directly on the checkbox
                if (e.target.type === 'checkbox') {
                    return;
                }
                
                e.preventDefault();
                
                var $toggle = $(this);
                var $checkbox = $toggle.find('input[type="checkbox"]');
                var $slider = $toggle.find('.ccl-admin-toggle-slider');
                
                // Toggle the checkbox
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                
                // Update visual state
                if ($checkbox.prop('checked')) {
                    $toggle.addClass('ccl-admin-toggle-active');
                    $slider.addClass('ccl-admin-toggle-slider-active');
                } else {
                    $toggle.removeClass('ccl-admin-toggle-active');
                    $slider.removeClass('ccl-admin-toggle-slider-active');
                }
                
                // Trigger change event for any listeners
                $checkbox.trigger('change');
            });
            
            // Also handle direct checkbox changes
            $('.ccl-admin-toggle input[type="checkbox"]').on('change', function() {
                var $checkbox = $(this);
                var $toggle = $checkbox.closest('.ccl-admin-toggle');
                var $slider = $toggle.find('.ccl-admin-toggle-slider');
                
                if ($checkbox.prop('checked')) {
                    $toggle.addClass('ccl-admin-toggle-active');
                    $slider.addClass('ccl-admin-toggle-slider-active');
                } else {
                    $toggle.removeClass('ccl-admin-toggle-active');
                    $slider.removeClass('ccl-admin-toggle-slider-active');
                }
            });
            
            // Initialize toggle states on page load
            $('.ccl-admin-toggle input[type="checkbox"]:checked').each(function() {
                var $checkbox = $(this);
                var $toggle = $checkbox.closest('.ccl-admin-toggle');
                var $slider = $toggle.find('.ccl-admin-toggle-slider');
                
                $toggle.addClass('ccl-admin-toggle-active');
                $slider.addClass('ccl-admin-toggle-slider-active');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Show detected cache plugins/systems
     */
    private function show_detected_cache_plugins() {
        $cache_systems = array();
        
        // Check for popular cache plugins
        if (function_exists('rocket_clean_domain')) $cache_systems[] = 'WP Rocket';
        if (function_exists('w3tc_flush_all')) $cache_systems[] = 'W3 Total Cache';
        if (function_exists('wp_cache_clear_cache')) $cache_systems[] = 'WP Super Cache';
        if (class_exists('LiteSpeed_Cache_API')) $cache_systems[] = 'LiteSpeed Cache';
        if (class_exists('WpFastestCache')) $cache_systems[] = 'WP Fastest Cache';
        if (class_exists('autoptimizeCache')) $cache_systems[] = 'Autoptimize';
        if (function_exists('sg_cachepress_purge_cache')) $cache_systems[] = 'SG Optimizer';
        if (class_exists('Breeze_Admin')) $cache_systems[] = 'Breeze';
        
        // Check for server-level caching
        if (function_exists('opcache_get_status')) $cache_systems[] = 'OPcache';
        if (class_exists('Redis')) $cache_systems[] = 'Redis';
        if (class_exists('Memcached')) $cache_systems[] = 'Memcached';
        
        // Check for CDN
        if (defined('CLOUDFLARE_PLUGIN_DIR')) $cache_systems[] = 'Cloudflare CDN';
        
        if (empty($cache_systems)) {
            echo '<span style="color: #666;">' . esc_html__('WordPress Core Cache Only', 'cookie-consent-lite') . '</span>';
        } else {
            echo esc_html(implode(', ', $cache_systems));
        }
    }
    
    /**
     * Render banner settings with toggle switches
     */
    private function render_banner_settings() {
        $settings = get_option('ccl_settings', array());
        ?>
        <tr>
            <th scope="row"><?php _e('Enable Banner', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_enable_banner">
                    <input type="checkbox" id="ccl_enable_banner" name="ccl_settings[enable_banner]" value="1" <?php checked(1, isset($settings['enable_banner']) ? $settings['enable_banner'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Show the cookie consent banner', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Banner Text', 'cookie-consent-lite'); ?></th>
            <td>
                <textarea name="ccl_settings[banner_text]" rows="4" class="large-text" maxlength="1000" required><?php echo esc_textarea(isset($settings['banner_text']) ? $settings['banner_text'] : ''); ?></textarea>
                <p class="description"><?php _e('Maximum 1000 characters. Minimum 10 characters required.', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Banner Position', 'cookie-consent-lite'); ?></th>
            <td>
                <select name="ccl_settings[banner_position]" class="regular-text">
                    <option value="bottom" <?php selected('bottom', isset($settings['banner_position']) ? $settings['banner_position'] : 'bottom'); ?>><?php _e('Bottom', 'cookie-consent-lite'); ?></option>
                    <option value="top" <?php selected('top', isset($settings['banner_position']) ? $settings['banner_position'] : 'bottom'); ?>><?php _e('Top', 'cookie-consent-lite'); ?></option>
                </select>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render button settings with toggle switches
     */
    private function render_button_settings() {
        $settings = get_option('ccl_settings', array());
        ?>
        <tr>
            <th scope="row"><?php _e('Accept Button Label', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="text" name="ccl_settings[accept_button_label]" value="<?php echo esc_attr(isset($settings['accept_button_label']) ? $settings['accept_button_label'] : ''); ?>" class="regular-text" maxlength="50" required />
                <p class="description"><?php _e('Maximum 50 characters', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Reject Button Label', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="text" name="ccl_settings[reject_button_label]" value="<?php echo esc_attr(isset($settings['reject_button_label']) ? $settings['reject_button_label'] : ''); ?>" class="regular-text" maxlength="50" required />
                <p class="description"><?php _e('Maximum 50 characters', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Show "Reject" Button', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_show_reject_button">
                    <input type="checkbox" id="ccl_show_reject_button" name="ccl_settings[show_reject_button]" value="1" <?php checked(1, isset($settings['show_reject_button']) ? $settings['show_reject_button'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Display reject button', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Show "Cookie Settings" Button', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_show_settings_button">
                    <input type="checkbox" id="ccl_show_settings_button" name="ccl_settings[show_settings_button]" value="1" <?php checked(1, isset($settings['show_settings_button']) ? $settings['show_settings_button'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Display settings button', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Cookie Settings Button Label', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="text" name="ccl_settings[settings_button_label]" value="<?php echo esc_attr(isset($settings['settings_button_label']) ? $settings['settings_button_label'] : ''); ?>" class="regular-text" maxlength="50" />
                <p class="description"><?php _e('Maximum 50 characters', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Learn More Link Text', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="text" name="ccl_settings[learn_more_text]" value="<?php echo esc_attr(isset($settings['learn_more_text']) ? $settings['learn_more_text'] : ''); ?>" class="regular-text" maxlength="50" />
                <p class="description"><?php _e('Maximum 50 characters. Leave empty to hide link.', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Learn More URL', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="url" name="ccl_settings[learn_more_url]" value="<?php echo esc_attr(isset($settings['learn_more_url']) ? $settings['learn_more_url'] : ''); ?>" class="regular-text" />
                <p class="description"><?php _e('Full URL or relative path (e.g., /privacy-policy)', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render category settings with toggle switches
     */
    private function render_category_settings() {
        $settings = get_option('ccl_settings', array());
        ?>
        <tr>
            <th scope="row"><?php _e('Enable Analytics Category', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_enable_analytics">
                    <input type="checkbox" id="ccl_enable_analytics" name="ccl_settings[enable_analytics]" value="1" <?php checked(1, isset($settings['enable_analytics']) ? $settings['enable_analytics'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Allow users to control analytics cookies', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Enable Marketing Category', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_enable_marketing">
                    <input type="checkbox" id="ccl_enable_marketing" name="ccl_settings[enable_marketing]" value="1" <?php checked(1, isset($settings['enable_marketing']) ? $settings['enable_marketing'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Allow users to control marketing cookies', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Enable Preferences Category', 'cookie-consent-lite'); ?></th>
            <td>
                <label class="ccl-admin-toggle" for="ccl_enable_preferences">
                    <input type="checkbox" id="ccl_enable_preferences" name="ccl_settings[enable_preferences]" value="1" <?php checked(1, isset($settings['enable_preferences']) ? $settings['enable_preferences'] : 1); ?> />
                    <span class="ccl-admin-toggle-slider"></span>
                </label>
                <span class="ccl-admin-toggle-label"><?php _e('Allow users to control preference cookies', 'cookie-consent-lite'); ?></span>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render advanced settings
     */
    private function render_advanced_settings() {
        $settings = get_option('ccl_settings', array());
        ?>
        <tr>
            <th scope="row"><?php _e('Consent Expiration (days)', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="number" name="ccl_settings[consent_expiration]" value="<?php echo esc_attr(isset($settings['consent_expiration']) ? $settings['consent_expiration'] : 180); ?>" min="1" max="365" class="small-text" required />
                <p class="description"><?php _e('How long to remember user\'s consent choice (1-365 days)', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Primary Color', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="color" name="ccl_settings[primary_color]" value="<?php echo esc_attr(isset($settings['primary_color']) ? $settings['primary_color'] : '#4CAF50'); ?>" class="ccl-color-picker" />
                <p class="description"><?php _e('Color for accept buttons and links', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Secondary Color', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="color" name="ccl_settings[secondary_color]" value="<?php echo esc_attr(isset($settings['secondary_color']) ? $settings['secondary_color'] : '#f44336'); ?>" class="ccl-color-picker" />
                <p class="description"><?php _e('Color for reject buttons', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Banner Background Color', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="color" name="ccl_settings[banner_bg_color]" value="<?php echo esc_attr(isset($settings['banner_bg_color']) ? $settings['banner_bg_color'] : '#ffffff'); ?>" class="ccl-color-picker" />
                <p class="description"><?php _e('Background color of the banner', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Text Color', 'cookie-consent-lite'); ?></th>
            <td>
                <input type="color" name="ccl_settings[text_color]" value="<?php echo esc_attr(isset($settings['text_color']) ? $settings['text_color'] : '#333333'); ?>" class="ccl-color-picker" />
                <p class="description"><?php _e('Color of the banner text', 'cookie-consent-lite'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Render admin sidebar
     */
    private function render_sidebar() {
        ?>
        <div class="ccl-admin-box">
            <h3><?php _e('Cache Status', 'cookie-consent-lite'); ?></h3>
            <p><?php _e('Cache is automatically cleared when you save settings.', 'cookie-consent-lite'); ?></p>
            
            <div style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 12px;">
                <strong><?php _e('Detected Cache Systems:', 'cookie-consent-lite'); ?></strong><br>
                <?php $this->show_detected_cache_plugins(); ?>
            </div>
            
            <button type="button" 
                    class="button button-secondary ccl-clear-cache-btn" 
                    id="ccl-clear-cache">
                <?php _e('Force Clear All Cache', 'cookie-consent-lite'); ?>
            </button>
            <div id="ccl-cache-status" style="margin-top: 10px;"></div>
            
            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; font-size: 12px;">
                <strong><?php _e('Still not working?', 'cookie-consent-lite'); ?></strong><br>
                • <?php _e('Try hard refresh (Ctrl+F5)', 'cookie-consent-lite'); ?><br>
                • <?php _e('Clear browser cache', 'cookie-consent-lite'); ?><br>
                • <?php _e('Check CDN settings', 'cookie-consent-lite'); ?><br>
                • <?php _e('Wait 30-60 seconds', 'cookie-consent-lite'); ?>
            </div>
        </div>
        
        <div class="ccl-admin-box">
            <h3><?php _e('Preview', 'cookie-consent-lite'); ?></h3>
            <p><?php _e('Visit your website to see the cookie banner in action.', 'cookie-consent-lite'); ?></p>
            <a href="<?php echo esc_url(home_url('/?ccl_preview=' . time())); ?>" target="_blank" class="button button-secondary">
                <?php _e('View Website', 'cookie-consent-lite'); ?>
            </a>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                <?php _e('Tip: Use incognito/private mode to see the banner if you\'ve already accepted cookies.', 'cookie-consent-lite'); ?>
            </p>
        </div>
        
        <div class="ccl-admin-box">
            <h3><?php _e('Debug Info', 'cookie-consent-lite'); ?></h3>
            <div style="font-size: 12px; font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 3px;">
                <strong><?php _e('Settings Version:', 'cookie-consent-lite'); ?></strong> <?php echo esc_html(get_option('ccl_settings_version', 'Not set')); ?><br>
                <strong><?php _e('Last Updated:', 'cookie-consent-lite'); ?></strong> <?php echo esc_html(date('Y-m-d H:i:s', get_option('ccl_settings_version', time()))); ?><br>
                <strong><?php _e('PHP Memory:', 'cookie-consent-lite'); ?></strong> <?php echo esc_html(ini_get('memory_limit')); ?><br>
                <strong><?php _e('WordPress Version:', 'cookie-consent-lite'); ?></strong> <?php echo esc_html(get_bloginfo('version')); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Aggressive cache clearing with error handling
     */
    private function aggressive_cache_clear() {
        $cleared_caches = array();
        
        try {
            // WordPress Core
            wp_cache_flush();
            $cleared_caches[] = 'WordPress Core';
            
            // Popular Cache Plugins with error handling
            if (function_exists('rocket_clean_domain')) {
                try {
                    rocket_clean_domain();
                    $cleared_caches[] = 'WP Rocket';
                } catch (Exception $e) {
                    error_log('CCL: WP Rocket cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (function_exists('w3tc_flush_all')) {
                try {
                    w3tc_flush_all();
                    $cleared_caches[] = 'W3 Total Cache';
                } catch (Exception $e) {
                    error_log('CCL: W3TC cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (function_exists('wp_cache_clear_cache')) {
                try {
                    wp_cache_clear_cache();
                    $cleared_caches[] = 'WP Super Cache';
                } catch (Exception $e) {
                    error_log('CCL: WP Super Cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
                try {
                    LiteSpeed_Cache_API::purge_all();
                    $cleared_caches[] = 'LiteSpeed Cache';
                } catch (Exception $e) {
                    error_log('CCL: LiteSpeed cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (class_exists('WpFastestCache')) {
                try {
                    $wp_fastest_cache = new WpFastestCache();
                    if (method_exists($wp_fastest_cache, 'deleteCache')) {
                        $wp_fastest_cache->deleteCache(true);
                        $cleared_caches[] = 'WP Fastest Cache';
                    }
                } catch (Exception $e) {
                    error_log('CCL: WP Fastest Cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
                try {
                    autoptimizeCache::clearall();
                    $cleared_caches[] = 'Autoptimize';
                } catch (Exception $e) {
                    error_log('CCL: Autoptimize cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (function_exists('sg_cachepress_purge_cache')) {
                try {
                    sg_cachepress_purge_cache();
                    $cleared_caches[] = 'SG Optimizer';
                } catch (Exception $e) {
                    error_log('CCL: SG Optimizer cache clear failed: ' . $e->getMessage());
                }
            }
            
            if (class_exists('Breeze_Admin')) {
                try {
                    do_action('breeze_clear_all_cache');
                    $cleared_caches[] = 'Breeze';
                } catch (Exception $e) {
                    error_log('CCL: Breeze cache clear failed: ' . $e->getMessage());
                }
            }
            
            // Clear all transients related to our plugin
            global $wpdb;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_ccl_%', '_transient_timeout_ccl_%'));
            
            // Clear object cache
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group('');
            }
            
            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                try {
                    opcache_reset();
                    $cleared_caches[] = 'OPcache';
                } catch (Exception $e) {
                    error_log('CCL: OPcache clear failed: ' . $e->getMessage());
                }
            }
            
            // Trigger additional clearing actions
            do_action('wp_cache_cleared');
            do_action('ccl_cache_cleared');
            
        } catch (Exception $e) {
            error_log('CCL: Cache clearing error: ' . $e->getMessage());
        }
        
        return $cleared_caches;
    }
    
    /**
     * AJAX handler for manual cache clearing with enhanced security
     */
    public function ajax_clear_cache() {
        // Check nonce
        if (!check_ajax_referer('ccl_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'cookie-consent-lite'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cookie-consent-lite'));
            return;
        }
        
        try {
            // Aggressive cache clearing
            $cleared_caches = $this->aggressive_cache_clear();
            
            // Update version for cache busting
            update_option('ccl_settings_version', time());
            
            if (!empty($cleared_caches)) {
                $message = sprintf(__('Cache cleared successfully! Systems: %s', 'cookie-consent-lite'), implode(', ', $cleared_caches));
            } else {
                $message = __('Cache clearing attempted. WordPress core cache was flushed.', 'cookie-consent-lite');
            }
            
            wp_send_json_success($message);
            
        } catch (Exception $e) {
            error_log('CCL: AJAX cache clear error: ' . $e->getMessage());
            wp_send_json_error(__('Cache clearing failed. Please try again.', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Add settings link to plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=cookie-consent-lite')) . '">' . __('Settings', 'cookie-consent-lite') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// End of CCL_Admin class