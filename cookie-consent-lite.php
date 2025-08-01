<?php
/**
 * Plugin Name: Cookie Consent Lite – GDPR & CCPA Compliant
 * Plugin URI: https://www.opt-4.co.uk/cookie-consent-lite-gdpr-ccpa-compliant/
 * Description: A lightweight, GDPR & CCPA compliant cookie consent banner with customizable settings and cookie categories. Features nuclear cache-busting technology to work with any caching setup.
 * Version: 1.0.0
 * Author: Opt-4
 * Author URI: https://www.opt-4.co.uk/
 * Text Domain: cookie-consent-lite
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Tags: cookie consent, gdpr, ccpa, privacy, cookies, compliance, banner, modal
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CCL_VERSION', '1.0.0');
define('CCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CCL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Cookie Consent Lite Class
 */
class CookieConsentLite {
    
    private static $instance = null;
    private $admin;
    private $frontend;
    private $settings;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ccl_save_consent', array($this, 'save_consent'));
        add_action('wp_ajax_nopriv_ccl_save_consent', array($this, 'save_consent'));
        
        // Dynamic banner loader
        add_action('wp_footer', array($this, 'render_dynamic_banner_loader'), 99);
        add_action('wp_ajax_ccl_get_banner', array($this, 'ajax_get_banner'));
        add_action('wp_ajax_nopriv_ccl_get_banner', array($this, 'ajax_get_banner'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load Settings class
        $settings_file = CCL_PLUGIN_DIR . 'includes/class-ccl-settings.php';
        if (file_exists($settings_file)) {
            require_once $settings_file;
            if (class_exists('CCL_Settings')) {
                $this->settings = new CCL_Settings();
            }
        }
        
        // Load Admin class
        $admin_file = CCL_PLUGIN_DIR . 'includes/class-ccl-admin.php';
        if (is_admin() && file_exists($admin_file)) {
            require_once $admin_file;
            if (class_exists('CCL_Admin')) {
                $this->admin = new CCL_Admin();
            } else {
                add_action('admin_menu', array($this, 'add_fallback_admin_menu'));
            }
        }
        
        // Load Frontend class
        $frontend_file = CCL_PLUGIN_DIR . 'includes/class-ccl-frontend.php';
        if (!is_admin() && file_exists($frontend_file)) {
            require_once $frontend_file;
            if (class_exists('CCL_Frontend')) {
                $this->frontend = new CCL_Frontend();
            }
        }
    }
    
    /**
     * Fallback admin menu if CCL_Admin class isn't loaded
     */
    public function add_fallback_admin_menu() {
        add_options_page(
            __('Cookie Consent Lite', 'cookie-consent-lite'),
            __('Cookie Consent', 'cookie-consent-lite'),
            'manage_options',
            'cookie-consent-lite',
            array($this, 'fallback_admin_page')
        );
    }
    
    /**
     * Simple fallback admin page
     */
    public function fallback_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cookie-consent-lite'));
        }
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'ccl_fallback_settings')) {
            $this->process_fallback_settings();
        }
        
        $this->render_fallback_admin_page();
    }
    
    /**
     * Process fallback settings with enhanced validation
     */
    private function process_fallback_settings() {
        $settings = array();
        
        // Boolean fields with validation
        $boolean_fields = array('enable_banner', 'show_reject_button', 'show_settings_button', 'enable_analytics', 'enable_marketing', 'enable_preferences');
        foreach ($boolean_fields as $field) {
            $settings[$field] = isset($_POST[$field]) ? 1 : 0;
        }
        
        // Text fields with enhanced sanitization
        $text_fields = array(
            'accept_button_label' => array('default' => 'Accept All', 'max_length' => 50),
            'reject_button_label' => array('default' => 'Reject', 'max_length' => 50),
            'settings_button_label' => array('default' => 'Cookie Settings', 'max_length' => 50),
            'learn_more_text' => array('default' => 'Learn more', 'max_length' => 50)
        );
        
        foreach ($text_fields as $field => $config) {
            $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            $value = trim($value);
            
            // Apply max length
            if (strlen($value) > $config['max_length']) {
                $value = substr($value, 0, $config['max_length']);
            }
            
            // Use default if empty
            $settings[$field] = !empty($value) ? $value : $config['default'];
        }
        
        // Textarea with enhanced sanitization
        $banner_text = isset($_POST['banner_text']) ? wp_kses_post($_POST['banner_text']) : '';
        $banner_text = trim($banner_text);
        
        if (strlen($banner_text) < 10) {
            add_settings_error('ccl_settings', 'banner_text_too_short', __('Banner text must be at least 10 characters long.', 'cookie-consent-lite'));
            return;
        }
        
        if (strlen($banner_text) > 500) {
            $banner_text = substr($banner_text, 0, 500);
        }
        
        $settings['banner_text'] = $banner_text;
        
        // URL validation
        $learn_more_url = isset($_POST['learn_more_url']) ? esc_url_raw($_POST['learn_more_url']) : '';
        if (!empty($learn_more_url) && !filter_var($learn_more_url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $learn_more_url)) {
            add_settings_error('ccl_settings', 'invalid_url', __('Learn more URL is not valid.', 'cookie-consent-lite'));
            return;
        }
        $settings['learn_more_url'] = $learn_more_url;
        
        // Select field validation
        $banner_position = isset($_POST['banner_position']) ? sanitize_text_field($_POST['banner_position']) : 'bottom';
        $settings['banner_position'] = in_array($banner_position, array('top', 'bottom')) ? $banner_position : 'bottom';
        
        // Number validation
        $consent_expiration = isset($_POST['consent_expiration']) ? intval($_POST['consent_expiration']) : 180;
        $settings['consent_expiration'] = max(1, min(365, $consent_expiration));
        
        // Color validation
        $color_fields = array('primary_color', 'secondary_color', 'banner_bg_color', 'text_color');
        $default_colors = array('#4CAF50', '#f44336', '#ffffff', '#333333');
        
        foreach ($color_fields as $index => $field) {
            $color = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            $color = sanitize_hex_color($color);
            $settings[$field] = !empty($color) ? $color : $default_colors[$index];
        }
        
        // Save settings
        $result = update_option('ccl_settings', $settings);
        update_option('ccl_settings_version', time());
        
        if ($result !== false) {
            add_settings_error('ccl_settings', 'settings_updated', __('Settings saved!', 'cookie-consent-lite'), 'updated');
        } else {
            add_settings_error('ccl_settings', 'settings_error', __('Error saving settings.', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Render fallback admin page
     */
    private function render_fallback_admin_page() {
        $settings = get_option('ccl_settings', array());
        settings_errors('ccl_settings');
        ?>
        <div class="wrap">
            <h1><?php _e('Cookie Consent Lite Settings', 'cookie-consent-lite'); ?></h1>
            <div class="notice notice-warning">
                <p><strong><?php _e('Notice:', 'cookie-consent-lite'); ?></strong> <?php _e('Using fallback admin page. Your full admin class files may not be loading properly.', 'cookie-consent-lite'); ?></p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('ccl_fallback_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Banner', 'cookie-consent-lite'); ?></th>
                        <td>
                            <input type="checkbox" name="enable_banner" value="1" <?php checked(!empty($settings['enable_banner'])); ?>>
                            <label><?php _e('Show cookie consent banner', 'cookie-consent-lite'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Banner Text', 'cookie-consent-lite'); ?></th>
                        <td>
                            <textarea name="banner_text" rows="3" cols="50" maxlength="500" required><?php echo esc_textarea(!empty($settings['banner_text']) ? $settings['banner_text'] : 'This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.'); ?></textarea>
                            <p class="description"><?php _e('Maximum 500 characters', 'cookie-consent-lite'); ?></p>
                        </td>
                    </tr>
                    <!-- Add remaining fields with proper validation attributes -->
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Plugin activation with error handling
     */
    public function activate() {
        try {
            $default_options = array(
                'enable_banner' => 1,
                'banner_text' => __('This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.', 'cookie-consent-lite'),
                'accept_button_label' => __('Accept All', 'cookie-consent-lite'),
                'reject_button_label' => __('Reject', 'cookie-consent-lite'),
                'settings_button_label' => __('Cookie Settings', 'cookie-consent-lite'),
                'learn_more_text' => __('Learn more', 'cookie-consent-lite'),
                'learn_more_url' => '/cookie-policy',
                'banner_position' => 'bottom',
                'show_reject_button' => 1,
                'show_settings_button' => 1,
                'consent_expiration' => 180,
                'enable_analytics' => 1,
                'enable_marketing' => 1,
                'enable_preferences' => 1,
                'primary_color' => '#4CAF50',
                'secondary_color' => '#f44336',
                'banner_bg_color' => '#ffffff',
                'text_color' => '#333333'
            );
            
            add_option('ccl_settings', $default_options);
            add_option('ccl_activated', true);
            update_option('ccl_settings_version', time());
            
        } catch (Exception $e) {
            error_log('Cookie Consent Lite activation error: ' . $e->getMessage());
            wp_die(__('Plugin activation failed. Please try again.', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        delete_option('ccl_settings_version');
        delete_option('ccl_activated');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('cookie-consent-lite', false, dirname(CCL_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles with cache busting
     */
    public function enqueue_frontend_scripts() {
        $version = get_option('ccl_settings_version', time());
        
        wp_enqueue_script('jquery');
        
        $frontend_css_file = CCL_PLUGIN_DIR . 'assets/css/frontend.css';
        if (file_exists($frontend_css_file)) {
            wp_enqueue_style(
                'ccl-frontend', 
                CCL_PLUGIN_URL . 'assets/css/frontend.css', 
                array(), 
                $version . '-' . filemtime($frontend_css_file)
            );
        }
        
        $frontend_js_file = CCL_PLUGIN_DIR . 'assets/js/frontend.js';
        if (file_exists($frontend_js_file)) {
            wp_enqueue_script(
                'ccl-frontend', 
                CCL_PLUGIN_URL . 'assets/js/frontend.js', 
                array('jquery'), 
                $version . '-' . filemtime($frontend_js_file),
                true
            );
            
            wp_localize_script('ccl-frontend', 'ccl_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ccl_frontend_nonce'),
                'settings' => get_option('ccl_settings', array()),
                'version' => $version
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles  
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_cookie-consent-lite') {
            return;
        }
        
        $version = time();
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        $admin_css_file = CCL_PLUGIN_DIR . 'assets/css/admin.css';
        if (file_exists($admin_css_file)) {
            wp_enqueue_style('ccl-admin', CCL_PLUGIN_URL . 'assets/css/admin.css', array('wp-color-picker'), $version);
        }
        
        $admin_js_file = CCL_PLUGIN_DIR . 'assets/js/admin.js';
        if (file_exists($admin_js_file)) {
            wp_enqueue_script('ccl-admin', CCL_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), $version, true);
            
            wp_localize_script('ccl-admin', 'ccl_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ccl_admin_nonce')
            ));
        }
    }
    
    /**
     * Render dynamic banner loader
     */
    public function render_dynamic_banner_loader() {
        if (is_admin()) {
            return;
        }
        
        ?>
        <script id="ccl-nuclear-loader" type="text/javascript">
        (function() {
            var bannerLoaded = false;
            
            function getCookie(name) {
                var value = "; " + document.cookie;
                var parts = value.split("; " + name + "=");
                if (parts.length == 2) return parts.pop().split(";").shift();
                return null;
            }
            
            function loadBannerAndModal() {
                if (bannerLoaded) {
                    return;
                }
                
                var existingConsent = getCookie('ccl_consent');
                if (existingConsent) {
                    return;
                }
                
                bannerLoaded = true;
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            
                            if (response.success && response.data) {
                                var container = document.createElement('div');
                                container.innerHTML = response.data;
                                
                                var scripts = container.querySelectorAll('script');
                                var scriptContent = '';
                                
                                scripts.forEach(function(script) {
                                    if (script.textContent) {
                                        scriptContent += script.textContent;
                                    }
                                    script.remove();
                                });
                                
                                document.body.appendChild(container);
                                
                                try {
                                    var scriptElement = document.createElement('script');
                                    scriptElement.textContent = scriptContent;
                                    document.head.appendChild(scriptElement);
                                } catch (e) {
                                    try {
                                        eval(scriptContent);
                                    } catch (e2) {
                                        // Silent fail for production
                                    }
                                }
                            }
                        } catch (e) {
                            // Silent fail for production
                        }
                    }
                };
                
                var params = 'action=ccl_get_banner&nonce=<?php echo wp_create_nonce('ccl_banner_nonce'); ?>&timestamp=' + Date.now();
                xhr.send(params);
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(loadBannerAndModal, 100);
                });
            } else {
                setTimeout(loadBannerAndModal, 100);
            }
            
        })();
        </script>
        <?php
    }
    
    /**
     * AJAX endpoint to get fresh banner + modal HTML
     */
    public function ajax_get_banner() {
        // Disable caching
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccl_banner_nonce')) {
            wp_send_json_error(__('Security check failed', 'cookie-consent-lite'));
            return;
        }
        
        try {
            $settings = get_option('ccl_settings', array());
            
            if (empty($settings['enable_banner'])) {
                wp_send_json_error(__('Banner is disabled', 'cookie-consent-lite'));
                return;
            }
            
            $html = $this->generate_fresh_banner_and_modal_html($settings);
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            error_log('Cookie Consent Lite banner generation error: ' . $e->getMessage());
            wp_send_json_error(__('Banner generation failed', 'cookie-consent-lite'));
        }
    }
    
    /**
     * Generate fresh banner + modal HTML with enhanced security
     */
    private function generate_fresh_banner_and_modal_html($settings) {
        $timestamp = time();
        $unique_id = 'ccl-banner-' . $timestamp . '-' . wp_rand(1000, 9999);
        $modal_id = 'ccl-modal-' . $timestamp . '-' . wp_rand(1000, 9999);
        
        // Sanitize and validate all settings
        $banner_text = !empty($settings['banner_text']) ? wp_kses_post($settings['banner_text']) : __('This website uses cookies.', 'cookie-consent-lite');
        $accept_label = !empty($settings['accept_button_label']) ? esc_html($settings['accept_button_label']) : __('Accept All', 'cookie-consent-lite');
        $reject_label = !empty($settings['reject_button_label']) ? esc_html($settings['reject_button_label']) : __('Reject', 'cookie-consent-lite');
        $settings_label = !empty($settings['settings_button_label']) ? esc_html($settings['settings_button_label']) : __('Cookie Settings', 'cookie-consent-lite');
        $show_reject = !empty($settings['show_reject_button']);
        $show_settings = !empty($settings['show_settings_button']);
        $learn_more_text = !empty($settings['learn_more_text']) ? esc_html($settings['learn_more_text']) : '';
        $learn_more_url = !empty($settings['learn_more_url']) ? esc_url($settings['learn_more_url']) : '';
        $banner_position = !empty($settings['banner_position']) && $settings['banner_position'] === 'top' ? 'top' : 'bottom';
        $primary_color = !empty($settings['primary_color']) ? sanitize_hex_color($settings['primary_color']) : '#4CAF50';
        $secondary_color = !empty($settings['secondary_color']) ? sanitize_hex_color($settings['secondary_color']) : '#f44336';
        $banner_bg_color = !empty($settings['banner_bg_color']) ? sanitize_hex_color($settings['banner_bg_color']) : '#ffffff';
        $text_color = !empty($settings['text_color']) ? sanitize_hex_color($settings['text_color']) : '#333333';
        $consent_expiration = !empty($settings['consent_expiration']) ? max(1, min(365, intval($settings['consent_expiration']))) : 180;
        
        $position_class = $banner_position === 'top' ? 'ccl-banner-top' : 'ccl-banner-bottom';
        
        ob_start();
        ?>
        <!-- Cookie Banner Generated: <?php echo esc_html(date('Y-m-d H:i:s', $timestamp)); ?> -->
        
        <div id="<?php echo esc_attr($unique_id); ?>" class="ccl-nuclear-banner <?php echo esc_attr($position_class); ?>" data-generated="<?php echo esc_attr($timestamp); ?>">
            <div class="ccl-banner-container">
                <div class="ccl-banner-content">
                    <div class="ccl-banner-text">
                        <?php echo $banner_text; ?>
                        
                        <?php if (!empty($learn_more_text) && !empty($learn_more_url)): ?>
                            <a href="<?php echo $learn_more_url; ?>" 
                               class="ccl-learn-more-link"
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php echo $learn_more_text; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="ccl-banner-buttons">
                        <?php if ($show_reject): ?>
                            <button type="button" class="ccl-btn ccl-btn-reject" data-action="reject">
                                <?php echo $reject_label; ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($show_settings): ?>
                            <button type="button" class="ccl-btn ccl-btn-settings" data-action="settings">
                                <?php echo $settings_label; ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="ccl-btn ccl-btn-accept" data-action="accept">
                            <?php echo $accept_label; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cookie Settings Modal -->
        <div id="<?php echo esc_attr($modal_id); ?>" class="ccl-nuclear-modal" style="display: none;">
            <div class="ccl-modal-overlay">
                <div class="ccl-modal-content">
                    <div class="ccl-modal-header">
                        <h2><?php _e('Manage Your Cookie Preferences', 'cookie-consent-lite'); ?></h2>
                        <button type="button" class="ccl-modal-close" data-action="close-modal">&times;</button>
                    </div>
                    
                    <div class="ccl-modal-body">
                        <p><?php _e('We use cookies to personalize content and analyze our traffic. Choose which categories to allow.', 'cookie-consent-lite'); ?></p>
                        
                        <!-- Essential Cookies -->
                        <div class="ccl-category-item">
                            <div class="ccl-category-header">
                                <div class="ccl-category-info">
                                    <h3><?php _e('🍪 Essential', 'cookie-consent-lite'); ?></h3>
                                    <p><?php _e('Required for the website to function. Cannot be disabled.', 'cookie-consent-lite'); ?></p>
                                </div>
                                <div class="ccl-toggle ccl-toggle-disabled">
                                    <span class="ccl-toggle-slider ccl-toggle-active"></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($settings['enable_analytics'])): ?>
                        <div class="ccl-category-item">
                            <div class="ccl-category-header">
                                <div class="ccl-category-info">
                                    <h3><?php _e('📊 Analytics', 'cookie-consent-lite'); ?></h3>
                                    <p><?php _e('Help us understand how visitors interact with our website.', 'cookie-consent-lite'); ?></p>
                                </div>
                                <div class="ccl-toggle">
                                    <input type="checkbox" id="ccl-analytics-<?php echo esc_attr($timestamp); ?>" data-category="analytics">
                                    <span class="ccl-toggle-slider"></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['enable_marketing'])): ?>
                        <div class="ccl-category-item">
                            <div class="ccl-category-header">
                                <div class="ccl-category-info">
                                    <h3><?php _e('🎯 Marketing', 'cookie-consent-lite'); ?></h3>
                                    <p><?php _e('Used to track visitors across websites for advertising.', 'cookie-consent-lite'); ?></p>
                                </div>
                                <div class="ccl-toggle">
                                    <input type="checkbox" id="ccl-marketing-<?php echo esc_attr($timestamp); ?>" data-category="marketing">
                                    <span class="ccl-toggle-slider"></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['enable_preferences'])): ?>
                        <div class="ccl-category-item">
                            <div class="ccl-category-header">
                                <div class="ccl-category-info">
                                    <h3><?php _e('⚙️ Preferences', 'cookie-consent-lite'); ?></h3>
                                    <p><?php _e('Remember your settings for future visits.', 'cookie-consent-lite'); ?></p>
                                </div>
                                <div class="ccl-toggle">
                                    <input type="checkbox" id="ccl-preferences-<?php echo esc_attr($timestamp); ?>" data-category="preferences">
                                    <span class="ccl-toggle-slider"></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ccl-modal-footer">
                        <button type="button" class="ccl-btn ccl-btn-reject" data-action="reject-all"><?php _e('Reject All', 'cookie-consent-lite'); ?></button>
                        <button type="button" class="ccl-btn ccl-btn-settings" data-action="save-preferences"><?php _e('Save Preferences', 'cookie-consent-lite'); ?></button>
                        <button type="button" class="ccl-btn ccl-btn-accept" data-action="accept-all"><?php _e('Accept All', 'cookie-consent-lite'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <style id="ccl-dynamic-styles-<?php echo esc_attr($timestamp); ?>">
        :root {
            --ccl-primary: <?php echo esc_attr($primary_color); ?>;
            --ccl-secondary: <?php echo esc_attr($secondary_color); ?>;
            --ccl-banner-bg: <?php echo esc_attr($banner_bg_color); ?>;
            --ccl-text: <?php echo esc_attr($text_color); ?>;
        }
        
        #<?php echo esc_attr($unique_id); ?> {
            background: var(--ccl-banner-bg) !important;
            color: var(--ccl-text) !important;
        }
        
        #<?php echo esc_attr($unique_id); ?> .ccl-btn-accept {
            background: var(--ccl-primary) !important;
        }
        
        #<?php echo esc_attr($unique_id); ?> .ccl-btn-reject {
            background: var(--ccl-secondary) !important;
        }
        
        #<?php echo esc_attr($unique_id); ?> .ccl-learn-more-link {
            color: var(--ccl-primary) !important;
        }
        
        #<?php echo esc_attr($modal_id); ?> .ccl-btn-accept {
            background: var(--ccl-primary) !important;
        }
        
        #<?php echo esc_attr($modal_id); ?> .ccl-btn-reject {
            background: var(--ccl-secondary) !important;
        }
        
        <?php if ($banner_position === 'top'): ?>
        @keyframes ccl-slide-in { from { transform: translateY(-20px); } }
        @keyframes ccl-slide-out { to { transform: translateY(-20px); } }
        <?php endif; ?>
        </style>
        
        <script id="ccl-init-<?php echo esc_attr($timestamp); ?>">
        (function() {
            function initializeWhenReady() {
                if (typeof window.CCL_Init === 'function') {
                    const settings = {
                        enable_analytics: <?php echo json_encode(!empty($settings['enable_analytics'])); ?>,
                        enable_marketing: <?php echo json_encode(!empty($settings['enable_marketing'])); ?>,
                        enable_preferences: <?php echo json_encode(!empty($settings['enable_preferences'])); ?>,
                        consent_expiration: <?php echo intval($consent_expiration); ?>,
                        primary_color: '<?php echo esc_js($primary_color); ?>',
                        secondary_color: '<?php echo esc_js($secondary_color); ?>',
                        banner_bg_color: '<?php echo esc_js($banner_bg_color); ?>',
                        text_color: '<?php echo esc_js($text_color); ?>'
                    };
                    
                    const success = window.CCL_Init('<?php echo esc_js($unique_id); ?>', '<?php echo esc_js($modal_id); ?>', settings);
                    
                    if (!success) {
                        // Fallback error handling
                    }
                } else {
                    setTimeout(initializeWhenReady, 100);
                }
            }
            
            initializeWhenReady();
        })();
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Save consent via AJAX with enhanced validation
     */
    public function save_consent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccl_consent_nonce')) {
            wp_send_json_error(__('Security check failed', 'cookie-consent-lite'));
            return;
        }
        
        try {
            // Validate and sanitize input
            $consent_data = isset($_POST['consent']) ? sanitize_text_field($_POST['consent']) : '';
            $allowed_consent_values = array('accepted', 'rejected', 'customized');
            
            if (!in_array($consent_data, $allowed_consent_values)) {
                wp_send_json_error(__('Invalid consent value', 'cookie-consent-lite'));
                return;
            }
            
            $categories = isset($_POST['categories']) ? $_POST['categories'] : array();
            $allowed_categories = array('essential', 'analytics', 'marketing', 'preferences');
            $sanitized_categories = array();
            
            foreach ($categories as $category => $value) {
                if (in_array($category, $allowed_categories)) {
                    $sanitized_categories[$category] = ($value === 'true') ? 'true' : 'false';
                }
            }
            
            // Get settings for expiry
            $settings = get_option('ccl_settings', array());
            $expiry_days = isset($settings['consent_expiration']) ? max(1, min(365, intval($settings['consent_expiration']))) : 180;
            $expiry_time = time() + ($expiry_days * 24 * 60 * 60);
            
            // Set cookies with secure options
            $cookie_options = array(
                'expires' => $expiry_time,
                'path' => '/',
                'domain' => '',
                'secure' => is_ssl(),
                'httponly' => false, // Needs to be accessible via JavaScript
                'samesite' => 'Lax'
            );
            
            // Set main consent cookie
            setcookie('ccl_consent', $consent_data, $cookie_options);
            setcookie('ccl_consent_expiry', $expiry_time, $cookie_options);
            
            // Set category cookies
            foreach ($sanitized_categories as $category => $value) {
                setcookie('ccl_' . $category, $value, $cookie_options);
            }
            
            wp_send_json_success(array(
                'message' => __('Consent preferences saved successfully', 'cookie-consent-lite')
            ));
            
        } catch (Exception $e) {
            error_log('Cookie Consent Lite save consent error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to save consent preferences', 'cookie-consent-lite'));
        }
    }
}

// Initialize the plugin
function cookie_consent_lite() {
    return CookieConsentLite::instance();
}

// Start the plugin
cookie_consent_lite();

// Show activation notice
add_action('admin_notices', function() {
    if (get_option('ccl_activated')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>' . esc_html__('Cookie Consent Lite activated successfully!', 'cookie-consent-lite') . '</strong> ';
        echo sprintf(
            __('Go to %sSettings → Cookie Consent%s to configure.', 'cookie-consent-lite'),
            '<a href="' . esc_url(admin_url('options-general.php?page=cookie-consent-lite')) . '">',
            '</a>'
        );
        echo '</p>';
        echo '</div>';
        delete_option('ccl_activated');
    }
});

// Add debug shortcode for testing (only for administrators)
add_shortcode('ccl_debug', function() {
    if (!current_user_can('manage_options')) {
        return esc_html__('Access denied', 'cookie-consent-lite');
    }
    
    $settings = get_option('ccl_settings', array());
    
    ob_start();
    ?>
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin: 20px 0;">
        <h3><?php esc_html_e('Cookie Consent Lite Debug', 'cookie-consent-lite'); ?></h3>
        
        <h4><?php esc_html_e('Settings:', 'cookie-consent-lite'); ?></h4>
        <pre><?php echo esc_html(print_r($settings, true)); ?></pre>
        
        <h4><?php esc_html_e('Current Cookies:', 'cookie-consent-lite'); ?></h4>
        <script>
        document.write('<pre>' + document.cookie + '</pre>');
        </script>
        
        <h4><?php esc_html_e('Test Functions:', 'cookie-consent-lite'); ?></h4>
        <button onclick="testBannerLoad()"><?php esc_html_e('Test Banner Load', 'cookie-consent-lite'); ?></button>
        <button onclick="testModalOnly()"><?php esc_html_e('Test Modal Only', 'cookie-consent-lite'); ?></button>
        <button onclick="clearCookiesAndReload()"><?php esc_html_e('Clear Cookies & Reload', 'cookie-consent-lite'); ?></button>
        <div id="debug-result"></div>
        
        <script>
        function testBannerLoad() {
            document.cookie = 'ccl_consent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var resultDiv = document.getElementById('debug-result');
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resultDiv.innerHTML = '<div style="background: #d4edda; padding: 10px; margin: 10px 0;"><strong>SUCCESS!</strong> Banner HTML received.</div>';
                                var container = document.createElement('div');
                                container.innerHTML = response.data;
                                document.body.appendChild(container);
                            } else {
                                resultDiv.innerHTML = '<div style="background: #f8d7da; padding: 10px; margin: 10px 0;"><strong>ERROR:</strong> ' + (response.data || 'Unknown error') + '</div>';
                            }
                        } catch (e) {
                            resultDiv.innerHTML = '<div style="background: #f8d7da; padding: 10px; margin: 10px 0;"><strong>PARSE ERROR:</strong> ' + e.message + '</div>';
                        }
                    } else {
                        resultDiv.innerHTML = '<div style="background: #f8d7da; padding: 10px; margin: 10px 0;"><strong>HTTP ERROR:</strong> ' + xhr.status + '</div>';
                    }
                }
            };
            
            var params = 'action=ccl_get_banner&nonce=<?php echo wp_create_nonce('ccl_banner_nonce'); ?>&timestamp=' + Date.now();
            xhr.send(params);
        }
        
        function testModalOnly() {
            if (window.CookieConsentLite && window.CookieConsentLite.showPreferences) {
                window.CookieConsentLite.showPreferences();
            } else {
                alert('CookieConsentLite API not loaded yet. Try loading banner first.');
            }
        }
        
        function clearCookiesAndReload() {
            var cookies = ['ccl_consent', 'ccl_essential', 'ccl_analytics', 'ccl_marketing', 'ccl_preferences'];
            cookies.forEach(function(cookie) {
                document.cookie = cookie + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            });
            location.reload();
        }
        </script>
    </div>
    <?php
    
    return ob_get_clean();
});
?>