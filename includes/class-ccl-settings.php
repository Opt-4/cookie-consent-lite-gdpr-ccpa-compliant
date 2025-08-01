<?php
/**
 * Settings functionality for Cookie Consent Lite - Production Ready
 *
 * @package CookieConsentLite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CCL_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize any settings-related hooks
        add_action('wp_ajax_ccl_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_ccl_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_ccl_reset_settings', array($this, 'ajax_reset_settings'));
    }
    
    /**
     * Get plugin settings with defaults and validation
     */
    public static function get_settings() {
        $defaults = array(
            'enable_banner' => 1,
            'banner_text' => __('This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.', 'cookie-consent-lite'),
            'accept_button_label' => __('Accept All', 'cookie-consent-lite'),
            'reject_button_label' => __('Reject', 'cookie-consent-lite'),
            'settings_button_label' => __('Cookie Settings', 'cookie-consent-lite'),
            'learn_more_text' => __('Learn more', 'cookie-consent-lite'),
            'learn_more_url' => '/cookie-policy',
            'banner_position' => 'bottom',
            'show_reject_button' => 1,
            'show_settings_button' => 0,
            'consent_expiration' => 180,
            'enable_analytics' => 1,
            'enable_marketing' => 1,
            'enable_preferences' => 1,
            'primary_color' => '#4CAF50',
            'secondary_color' => '#f44336',
            'banner_bg_color' => '#ffffff',
            'text_color' => '#333333'
        );
        
        $settings = get_option('ccl_settings', array());
        $merged_settings = wp_parse_args($settings, $defaults);
        
        // Validate and sanitize settings
        return self::validate_settings($merged_settings);
    }
    
    /**
     * Validate and sanitize settings array
     */
    public static function validate_settings($settings) {
        if (!is_array($settings)) {
            return self::get_default_settings();
        }
        
        $validated = array();
        
        // Boolean fields
        $boolean_fields = array('enable_banner', 'show_reject_button', 'show_settings_button', 'enable_analytics', 'enable_marketing', 'enable_preferences');
        foreach ($boolean_fields as $field) {
            $validated[$field] = !empty($settings[$field]) ? 1 : 0;
        }
        
        // Text fields with length limits
        $text_fields = array(
            'accept_button_label' => 50,
            'reject_button_label' => 50,
            'settings_button_label' => 50,
            'learn_more_text' => 50
        );
        
        foreach ($text_fields as $field => $max_length) {
            $value = isset($settings[$field]) ? sanitize_text_field($settings[$field]) : '';
            $value = trim($value);
            
            if (strlen($value) > $max_length) {
                $value = substr($value, 0, $max_length);
            }
            
            $validated[$field] = $value;
        }
        
        // Banner text with special handling
        $banner_text = isset($settings['banner_text']) ? wp_kses_post($settings['banner_text']) : '';
        $banner_text = trim($banner_text);
        
        if (strlen($banner_text) < 10) {
            $banner_text = __('This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.', 'cookie-consent-lite');
        }
        
        if (strlen($banner_text) > 1000) {
            $banner_text = substr($banner_text, 0, 1000);
        }
        
        $validated['banner_text'] = $banner_text;
        
        // URL validation
        $learn_more_url = isset($settings['learn_more_url']) ? esc_url_raw($settings['learn_more_url']) : '';
        if (!empty($learn_more_url)) {
            if (!filter_var($learn_more_url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $learn_more_url)) {
                $learn_more_url = '/cookie-policy';
            }
        }
        $validated['learn_more_url'] = $learn_more_url;
        
        // Select validation
        $banner_position = isset($settings['banner_position']) ? sanitize_text_field($settings['banner_position']) : 'bottom';
        $validated['banner_position'] = in_array($banner_position, array('top', 'bottom')) ? $banner_position : 'bottom';
        
        // Number validation
        $consent_expiration = isset($settings['consent_expiration']) ? intval($settings['consent_expiration']) : 180;
        $validated['consent_expiration'] = max(1, min(365, $consent_expiration));
        
        // Color validation
        $color_fields = array(
            'primary_color' => '#4CAF50',
            'secondary_color' => '#f44336',
            'banner_bg_color' => '#ffffff',
            'text_color' => '#333333'
        );
        
        foreach ($color_fields as $field => $default) {
            $color = isset($settings[$field]) ? sanitize_text_field($settings[$field]) : '';
            $color = sanitize_hex_color($color);
            $validated[$field] = !empty($color) ? $color : $default;
        }
        
        return $validated;
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'enable_banner' => 1,
            'banner_text' => __('This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.', 'cookie-consent-lite'),
            'accept_button_label' => __('Accept All', 'cookie-consent-lite'),
            'reject_button_label' => __('Reject', 'cookie-consent-lite'),
            'settings_button_label' => __('Cookie Settings', 'cookie-consent-lite'),
            'learn_more_text' => __('Learn more', 'cookie-consent-lite'),
            'learn_more_url' => '/cookie-policy',
            'banner_position' => 'bottom',
            'show_reject_button' => 1,
            'show_settings_button' => 0,
            'consent_expiration' => 180,
            'enable_analytics' => 1,
            'enable_marketing' => 1,
            'enable_preferences' => 1,
            'primary_color' => '#4CAF50',
            'secondary_color' => '#f44336',
            'banner_bg_color' => '#ffffff',
            'text_color' => '#333333'
        );
    }
    
    /**
     * Get a specific setting value with validation
     */
    public static function get_setting($key, $default = null) {
        if (!is_string($key) || empty($key)) {
            return $default;
        }
        
        $settings = self::get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update a specific setting with validation
     */
    public static function update_setting($key, $value) {
        if (!is_string($key) || empty($key)) {
            return false;
        }
        
        $settings = self::get_settings();
        $settings[$key] = $value;
        
        // Validate the entire settings array
        $validated_settings = self::validate_settings($settings);
        
        $result = update_option('ccl_settings', $validated_settings);
        
        if ($result) {
            update_option('ccl_settings_version', time());
        }
        
        return $result;
    }
    
    /**
     * Get enabled categories with proper validation
     */
    public static function get_enabled_categories() {
        $settings = self::get_settings();
        $categories = array();
        
        // Essential is always enabled
        $categories['essential'] = array(
            'name' => __('Essential', 'cookie-consent-lite'),
            'description' => __('Required for the website to function properly. Cannot be disabled.', 'cookie-consent-lite'),
            'required' => true,
            'enabled' => true
        );
        
        if (!empty($settings['enable_analytics'])) {
            $categories['analytics'] = array(
                'name' => __('Analytics', 'cookie-consent-lite'),
                'description' => __('Help us understand how visitors interact with our website.', 'cookie-consent-lite'),
                'required' => false,
                'enabled' => true
            );
        }
        
        if (!empty($settings['enable_marketing'])) {
            $categories['marketing'] = array(
                'name' => __('Marketing', 'cookie-consent-lite'),
                'description' => __('Used to track visitors across websites for advertising purposes.', 'cookie-consent-lite'),
                'required' => false,
                'enabled' => true
            );
        }
        
        if (!empty($settings['enable_preferences'])) {
            $categories['preferences'] = array(
                'name' => __('Preferences', 'cookie-consent-lite'),
                'description' => __('Remember your settings and preferences for future visits.', 'cookie-consent-lite'),
                'required' => false,
                'enabled' => true
            );
        }
        
        return $categories;
    }
    
    /**
     * Check if any optional categories are enabled
     */
    public static function has_optional_categories() {
        $settings = self::get_settings();
        return !empty($settings['enable_analytics']) || 
               !empty($settings['enable_marketing']) || 
               !empty($settings['enable_preferences']);
    }
    
    /**
     * Export settings for backup with enhanced security
     */
    public static function export_settings() {
        $settings = self::get_settings();
        
        // Add metadata
        $export_data = array(
            'version' => CCL_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => home_url(),
            'settings' => $settings
        );
        
        return wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Import settings from backup with enhanced validation
     */
    public static function import_settings($json_data) {
        if (empty($json_data) || !is_string($json_data)) {
            return new WP_Error('invalid_data', __('No data provided for import.', 'cookie-consent-lite'));
        }
        
        // Validate JSON
        $imported_data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data provided.', 'cookie-consent-lite'));
        }
        
        if (!is_array($imported_data)) {
            return new WP_Error('invalid_format', __('Invalid data format.', 'cookie-consent-lite'));
        }
        
        // Extract settings
        $imported_settings = array();
        
        if (isset($imported_data['settings']) && is_array($imported_data['settings'])) {
            $imported_settings = $imported_data['settings'];
        } elseif (isset($imported_data['enable_banner'])) {
            // Direct settings format (legacy support)
            $imported_settings = $imported_data;
        } else {
            return new WP_Error('no_settings', __('No settings found in import data.', 'cookie-consent-lite'));
        }
        
        // Validate and sanitize imported settings
        $validated_settings = self::validate_settings($imported_settings);
        
        // Save settings
        $result = update_option('ccl_settings', $validated_settings);
        
        if ($result !== false) {
            update_option('ccl_settings_version', time());
            return true;
        } else {
            return new WP_Error('import_failed', __('Failed to save imported settings.', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Reset settings to defaults with confirmation
     */
    public static function reset_settings() {
        $default_settings = self::get_default_settings();
        
        $result = update_option('ccl_settings', $default_settings);
        
        if ($result !== false) {
            update_option('ccl_settings_version', time());
            return true;
        }
        
        return false;
    }
    
    /**
     * Get category-specific scripts that should be blocked
     */
    public static function get_category_scripts() {
        return array(
            'analytics' => array(
                'google-analytics',
                'gtag',
                'ga',
                'googletagmanager',
                'google-tag-manager',
                'plausible',
                'matomo',
                'hotjar',
                'clarity',
                'mixpanel',
                'segment',
                'amplitude'
            ),
            'marketing' => array(
                'facebook',
                'fbq',
                'fbevents',
                'google-ads',
                'googleads',
                'linkedin',
                'tiktok',
                'ttq',
                'pinterest',
                'twitter',
                'snapchat',
                'doubleclick',
                'adsystem',
                'criteo',
                'outbrain',
                'taboola'
            ),
            'preferences' => array(
                'wpml',
                'polylang',
                'theme-settings',
                'user-preferences',
                'customizer',
                'personalization'
            )
        );
    }
    
    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        // Check nonce and permissions
        if (!check_ajax_referer('ccl_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cookie-consent-lite'));
            return;
        }
        
        try {
            $export_data = self::export_settings();
            wp_send_json_success($export_data);
        } catch (Exception $e) {
            error_log('CCL export error: ' . $e->getMessage());
            wp_send_json_error(__('Export failed', 'cookie-consent-lite'));
        }
    }
    
    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        // Check nonce and permissions
        if (!check_ajax_referer('ccl_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cookie-consent-lite'));
            return;
        }
        
        $settings_data = isset($_POST['settings_data']) ? stripslashes($_POST['settings_data']) : '';
        
        if (empty($settings_data)) {
            wp_send_json_error(__('No settings data provided', 'cookie-consent-lite'));
            return;
        }
        
        try {
            $result = self::import_settings($settings_data);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(__('Settings imported successfully', 'cookie-consent-lite'));
            }
        } catch (Exception $e) {
            error_log('CCL import error: ' . $e->getMessage());
            wp_send_json_error(__('Import failed', 'cookie-consent-lite'));
        }
    }
    
    /**
     * AJAX handler for resetting settings
     */
    public function ajax_reset_settings() {
        // Check nonce and permissions
        if (!check_ajax_referer('ccl_admin_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'cookie-consent-lite'));
            return;
        }
        
        try {
            $result = self::reset_settings();
            
            if ($result) {
                wp_send_json_success(__('Settings reset successfully', 'cookie-consent-lite'));
            } else {
                wp_send_json_error(__('Failed to reset settings', 'cookie-consent-lite'));
            }
        } catch (Exception $e) {
            error_log('CCL reset error: ' . $e->getMessage());
            wp_send_json_error(__('Reset failed', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Validate consent preferences from frontend
     */
    public static function validate_consent_preferences($consent_data, $categories) {
        $validated = array();
        
        // Validate consent type
        $allowed_consent_types = array('accepted', 'rejected', 'customized');
        if (!in_array($consent_data, $allowed_consent_types)) {
            return false;
        }
        
        $validated['consent'] = sanitize_text_field($consent_data);
        
        // Validate categories
        $allowed_categories = array('essential', 'analytics', 'marketing', 'preferences');
        $validated['categories'] = array();
        
        if (is_array($categories)) {
            foreach ($categories as $category => $value) {
                if (in_array($category, $allowed_categories)) {
                    $validated['categories'][$category] = ($value === 'true' || $value === true) ? 'true' : 'false';
                }
            }
        }
        
        // Essential is always true
        $validated['categories']['essential'] = 'true';
        
        return $validated;
    }
    
    /**
     * Get consent cookie configuration
     */
    public static function get_consent_cookie_config() {
        $settings = self::get_settings();
        
        return array(
            'expiration_days' => max(1, min(365, intval($settings['consent_expiration']))),
            'secure' => is_ssl(),
            'httponly' => false, // Must be accessible via JavaScript
            'samesite' => 'Lax',
            'path' => '/',
            'domain' => ''
        );
    }
}