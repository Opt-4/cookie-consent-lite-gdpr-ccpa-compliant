<?php
/**
 * Frontend functionality for Cookie Consent Lite - Production Ready
 *
 * @package CookieConsentLite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CCL_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_head', array($this, 'add_dynamic_styles'));
        add_action('wp_footer', array($this, 'add_script_blocker'));
        add_action('wp_ajax_ccl_save_consent', array($this, 'save_consent'));
        add_action('wp_ajax_nopriv_ccl_save_consent', array($this, 'save_consent'));
    }
    
    /**
     * Add dynamic styles based on settings
     */
    public function add_dynamic_styles() {
        $settings = CCL_Settings::get_settings();
        
        // Get colors with validation
        $primary_color = !empty($settings['primary_color']) ? sanitize_hex_color($settings['primary_color']) : '#4CAF50';
        $secondary_color = !empty($settings['secondary_color']) ? sanitize_hex_color($settings['secondary_color']) : '#f44336';
        $banner_bg_color = !empty($settings['banner_bg_color']) ? sanitize_hex_color($settings['banner_bg_color']) : '#ffffff';
        $text_color = !empty($settings['text_color']) ? sanitize_hex_color($settings['text_color']) : '#333333';
        
        // Only output if colors are valid
        if (!$primary_color || !$secondary_color || !$banner_bg_color || !$text_color) {
            return;
        }
        
        ?>
        <style id="ccl-dynamic-styles">
            :root {
                --ccl-primary-color: <?php echo esc_attr($primary_color); ?>;
                --ccl-secondary-color: <?php echo esc_attr($secondary_color); ?>;
                --ccl-banner-bg: <?php echo esc_attr($banner_bg_color); ?>;
                --ccl-text-color: <?php echo esc_attr($text_color); ?>;
            }
            
            .ccl-banner, .ccl-nuclear-banner {
                background-color: var(--ccl-banner-bg) !important;
                color: var(--ccl-text-color) !important;
            }
            
            .ccl-btn-accept {
                background-color: var(--ccl-primary-color) !important;
                border-color: var(--ccl-primary-color) !important;
            }
            
            .ccl-btn-accept:hover {
                background-color: <?php echo esc_attr($this->darken_color($primary_color, 10)); ?> !important;
                border-color: <?php echo esc_attr($this->darken_color($primary_color, 10)); ?> !important;
            }
            
            .ccl-btn-reject {
                background-color: var(--ccl-secondary-color) !important;
                border-color: var(--ccl-secondary-color) !important;
            }
            
            .ccl-btn-reject:hover {
                background-color: <?php echo esc_attr($this->darken_color($secondary_color, 10)); ?> !important;
                border-color: <?php echo esc_attr($this->darken_color($secondary_color, 10)); ?> !important;
            }
            
            .ccl-learn-more-link {
                color: var(--ccl-primary-color) !important;
            }
            
            .ccl-modal-content, .ccl-nuclear-modal .ccl-modal-content {
                background-color: var(--ccl-banner-bg) !important;
                color: var(--ccl-text-color) !important;
            }
        </style>
        <?php
    }
    
    /**
     * Darken a hex color by a percentage with validation
     */
    private function darken_color($hex, $percent) {
        // Validate input
        if (!$hex || !is_string($hex)) {
            return '#000000';
        }
        
        $hex = str_replace('#', '', $hex);
        
        // Validate hex format
        if (!preg_match('/^[a-f0-9]{3,6}$/i', $hex)) {
            return '#000000';
        }
        
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        if (strlen($hex) != 6) {
            return '#000000';
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $percent = max(0, min(100, $percent)); // Clamp between 0-100
        
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        return '#' . str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT) . 
                    str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT) . 
                    str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Add script blocker functionality with enhanced security
     */
    public function add_script_blocker() {
        // Only add if categories are enabled
        $settings = CCL_Settings::get_settings();
        
        if (empty($settings['enable_analytics']) && empty($settings['enable_marketing']) && empty($settings['enable_preferences'])) {
            return;
        }
        ?>
        <script id="ccl-script-blocker">
        (function() {
            'use strict';
            
            // Function to check consent for a category
            function hasConsent(category) {
                try {
                    var cookie = getCookie('ccl_' + category);
                    return cookie === 'true';
                } catch (e) {
                    return false;
                }
            }
            
            // Function to get cookie value safely
            function getCookie(name) {
                try {
                    if (!name || typeof name !== 'string') return null;
                    
                    var value = "; " + document.cookie;
                    var parts = value.split("; " + encodeURIComponent(name) + "=");
                    if (parts.length === 2) {
                        var cookieValue = parts.pop().split(";").shift();
                        return decodeURIComponent(cookieValue);
                    }
                    return null;
                } catch (e) {
                    return null;
                }
            }
            
            // Function to enable scripts for a category
            function enableScripts(category) {
                try {
                    var scripts = document.querySelectorAll('script[data-category="' + category + '"]');
                    scripts.forEach(function(script) {
                        if (script.getAttribute('data-src')) {
                            // External script
                            var newScript = document.createElement('script');
                            newScript.src = script.getAttribute('data-src');
                            
                            // Copy attributes safely
                            Array.from(script.attributes).forEach(function(attr) {
                                if (attr.name !== 'data-category' && attr.name !== 'data-src') {
                                    newScript.setAttribute(attr.name, attr.value);
                                }
                            });
                            
                            document.head.appendChild(newScript);
                            script.remove();
                            
                        } else if (script.innerHTML && script.type === 'text/plain') {
                            // Inline script
                            var newScript = document.createElement('script');
                            newScript.type = 'text/javascript';
                            newScript.innerHTML = script.innerHTML;
                            document.head.appendChild(newScript);
                            script.remove();
                        }
                    });
                } catch (e) {
                    // Silent fail in production
                }
            }
            
            // Check and enable scripts based on consent
            var categories = ['analytics', 'marketing', 'preferences'];
            categories.forEach(function(category) {
                if (hasConsent(category)) {
                    enableScripts(category);
                }
            });
            
            // Listen for consent changes
            document.addEventListener('cclConsentChanged', function(e) {
                if (e.detail && e.detail.cookies) {
                    categories.forEach(function(category) {
                        if (e.detail.cookies['ccl_' + category] === 'true') {
                            enableScripts(category);
                        }
                    });
                }
            });
            
        })();
        </script>
        <?php
    }
    
    /**
     * Save consent via AJAX with enhanced validation and security
     */
    public function save_consent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccl_consent_nonce')) {
            wp_send_json_error(__('Security check failed', 'cookie-consent-lite'));
            return;
        }
        
        // Rate limiting check (basic)
        $user_ip = $this->get_user_ip();
        $rate_limit_key = 'ccl_rate_limit_' . md5($user_ip);
        $attempts = get_transient($rate_limit_key);
        
        if ($attempts && $attempts > 10) {
            wp_send_json_error(__('Too many requests. Please try again later.', 'cookie-consent-lite'));
            return;
        }
        
        try {
            // Validate and sanitize input
            $consent_data = isset($_POST['consent']) ? sanitize_text_field($_POST['consent']) : '';
            $categories = isset($_POST['categories']) ? $_POST['categories'] : array();
            
            // Validate using settings class
            $validated = CCL_Settings::validate_consent_preferences($consent_data, $categories);
            
            if (!$validated) {
                wp_send_json_error(__('Invalid consent data', 'cookie-consent-lite'));
                return;
            }
            
            // Get cookie configuration
            $cookie_config = CCL_Settings::get_consent_cookie_config();
            $expiry_time = time() + ($cookie_config['expiration_days'] * 24 * 60 * 60);
            
            // Set cookies with secure configuration
            $this->set_secure_cookie('ccl_consent', $validated['consent'], $expiry_time, $cookie_config);
            $this->set_secure_cookie('ccl_consent_expiry', $expiry_time, $expiry_time, $cookie_config);
            
            // Set category cookies
            foreach ($validated['categories'] as $category => $value) {
                $this->set_secure_cookie('ccl_' . $category, $value, $expiry_time, $cookie_config);
            }
            
            // Update rate limiting
            set_transient($rate_limit_key, ($attempts ? $attempts + 1 : 1), HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'message' => __('Consent preferences saved successfully', 'cookie-consent-lite'),
                'consent' => $validated['consent'],
                'categories' => $validated['categories']
            ));
            
        } catch (Exception $e) {
            error_log('Cookie Consent Lite save consent error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to save consent preferences', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Set secure cookie with proper configuration
     */
    private function set_secure_cookie($name, $value, $expiry, $config) {
        if (!is_string($name) || !is_string($value)) {
            return false;
        }
        
        // Use setcookie with all security options
        return setcookie(
            $name,
            $value,
            array(
                'expires' => $expiry,
                'path' => $config['path'],
                'domain' => $config['domain'],
                'secure' => $config['secure'],
                'httponly' => $config['httponly'],
                'samesite' => $config['samesite']
            )
        );
    }
    
    /**
     * Get user IP address safely
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get consent status for a category with validation
     */
    public static function has_category_consent($category) {
        if (!is_string($category) || empty($category)) {
            return false;
        }
        
        $allowed_categories = array('essential', 'analytics', 'marketing', 'preferences');
        if (!in_array($category, $allowed_categories)) {
            return false;
        }
        
        // Essential is always true
        if ($category === 'essential') {
            return true;
        }
        
        $cookie_name = 'ccl_' . $category;
        $cookie_value = isset($_COOKIE[$cookie_name]) ? sanitize_text_field($_COOKIE[$cookie_name]) : '';
        
        return $cookie_value === 'true';
    }
    
    /**
     * Check if consent exists and is not expired
     */
    public static function has_valid_consent() {
        // Check if consent cookie exists
        if (!isset($_COOKIE['ccl_consent'])) {
            return false;
        }
        
        // Check if consent has not expired
        if (isset($_COOKIE['ccl_consent_expiry'])) {
            $expiry_time = intval($_COOKIE['ccl_consent_expiry']);
            if ($expiry_time < time()) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get all consent preferences
     */
    public static function get_consent_preferences() {
        if (!self::has_valid_consent()) {
            return array();
        }
        
        $preferences = array(
            'consent_type' => isset($_COOKIE['ccl_consent']) ? sanitize_text_field($_COOKIE['ccl_consent']) : '',
            'essential' => true, // Always true
            'analytics' => self::has_category_consent('analytics'),
            'marketing' => self::has_category_consent('marketing'),
            'preferences' => self::has_category_consent('preferences')
        );
        
        if (isset($_COOKIE['ccl_consent_expiry'])) {
            $preferences['expiry'] = intval($_COOKIE['ccl_consent_expiry']);
        }
        
        return $preferences;
    }
    
    /**
     * Helper function for developers to conditionally load scripts
     */
    public static function load_script_if_consent($category, $script_url, $attributes = array()) {
        if (!is_string($category) || !is_string($script_url)) {
            return;
        }
        
        // Validate category
        $allowed_categories = array('analytics', 'marketing', 'preferences');
        if (!in_array($category, $allowed_categories)) {
            return;
        }
        
        // Sanitize URL
        $script_url = esc_url($script_url);
        if (empty($script_url)) {
            return;
        }
        
        // Sanitize attributes
        $sanitized_attrs = array();
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $sanitized_attrs[sanitize_key($key)] = esc_attr($value);
            }
        }
        
        $attrs = '';
        foreach ($sanitized_attrs as $key => $value) {
            $attrs .= ' ' . $key . '="' . $value . '"';
        }
        
        if (self::has_category_consent($category)) {
            echo '<script src="' . $script_url . '"' . $attrs . '></script>';
        } else {
            echo '<script type="text/plain" data-category="' . esc_attr($category) . '" data-src="' . $script_url . '"' . $attrs . '></script>';
        }
    }
    
    /**
     * Helper function for inline scripts
     */
    public static function load_inline_script_if_consent($category, $script_content) {
        if (!is_string($category) || !is_string($script_content)) {
            return;
        }
        
        // Validate category
        $allowed_categories = array('analytics', 'marketing', 'preferences');
        if (!in_array($category, $allowed_categories)) {
            return;
        }
        
        // Basic XSS protection for script content
        $script_content = wp_kses($script_content, array());
        
        if (self::has_category_consent($category)) {
            echo '<script>' . $script_content . '</script>';
        } else {
            echo '<script type="text/plain" data-category="' . esc_attr($category) . '">' . $script_content . '</script>';
        }
    }
    
    /**
     * Helper function to check if Google Analytics consent mode should be enabled
     */
    public static function should_enable_ga_consent_mode() {
        $settings = CCL_Settings::get_settings();
        return !empty($settings['enable_analytics']) || !empty($settings['enable_marketing']);
    }
    
    /**
     * Add Google Analytics consent mode integration
     */
    public static function add_ga_consent_mode() {
        if (!self::should_enable_ga_consent_mode()) {
            return;
        }
        
        $analytics_consent = self::has_category_consent('analytics') ? 'granted' : 'denied';
        $marketing_consent = self::has_category_consent('marketing') ? 'granted' : 'denied';
        
        ?>
        <script>
        // Google Analytics Consent Mode
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        
        gtag('consent', 'default', {
            'analytics_storage': '<?php echo esc_js($analytics_consent); ?>',
            'ad_storage': '<?php echo esc_js($marketing_consent); ?>',
            'wait_for_update': 500
        });
        
        // Listen for consent changes
        document.addEventListener('cclConsentChanged', function(e) {
            if (e.detail && e.detail.cookies && typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': e.detail.cookies.ccl_analytics === 'true' ? 'granted' : 'denied',
                    'ad_storage': e.detail.cookies.ccl_marketing === 'true' ? 'granted' : 'denied'
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add Facebook Pixel consent integration
     */
    public static function add_facebook_consent_mode() {
        if (!self::has_category_consent('marketing')) {
            return;
        }
        
        ?>
        <script>
        // Facebook Pixel consent integration
        if (typeof fbq !== 'undefined') {
            document.addEventListener('cclConsentChanged', function(e) {
                if (e.detail && e.detail.cookies) {
                    if (e.detail.cookies.ccl_marketing === 'true') {
                        fbq('consent', 'grant');
                    } else {
                        fbq('consent', 'revoke');
                    }
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Add developer-friendly consent API
     */
    public static function add_consent_api() {
        ?>
        <script>
        // Cookie Consent Lite Developer API
        window.CookieConsentLite = window.CookieConsentLite || {};
        
        // Extend existing API with additional methods
        Object.assign(window.CookieConsentLite, {
            // Check if consent is valid (not expired)
            isConsentValid: function() {
                try {
                    var expiry = this.getCookie('ccl_consent_expiry');
                    if (!expiry) return false;
                    return parseInt(expiry) > Date.now();
                } catch (e) {
                    return false;
                }
            },
            
            // Get cookie safely
            getCookie: function(name) {
                try {
                    var value = "; " + document.cookie;
                    var parts = value.split("; " + name + "=");
                    if (parts.length === 2) return parts.pop().split(";").shift();
                    return null;
                } catch (e) {
                    return null;
                }
            },
            
            // Trigger custom event when scripts should be loaded
            triggerScriptLoad: function(category) {
                if (typeof category !== 'string') return false;
                
                var event = new CustomEvent('cclLoadScripts', {
                    detail: { category: category }
                });
                document.dispatchEvent(event);
                return true;
            }
        });
        </script>
        <?php
    }
}