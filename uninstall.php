<?php
/**
 * Uninstall Cookie Consent Lite – GDPR & CCPA Compliant
 * 
 * This file is executed when the plugin is deleted via the WordPress admin.
 * It removes all plugin data from the database to ensure clean uninstallation.
 * 
 * @package CookieConsentLite
 * @version 1.0.0
 * @author Opt-4
 */

// Security check - prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Additional security check
if (!current_user_can('activate_plugins')) {
    exit;
}

// Verify this is actually our plugin being uninstalled
if (__FILE__ != WP_UNINSTALL_PLUGIN) {
    exit;
}

/**
 * Remove all plugin data from the database
 */
function ccl_cleanup_plugin_data() {
    global $wpdb;
    
    try {
        // 1. Delete main plugin options
        $plugin_options = array(
            'ccl_settings',
            'ccl_settings_version',
            'ccl_activated',
            'ccl_db_version', // For future use
            'ccl_activation_time',
            'ccl_last_cleanup'
        );
        
        foreach ($plugin_options as $option) {
            delete_option($option);
            delete_site_option($option); // For multisite compatibility
        }
        
        // 2. Delete all plugin transients
        $transients = array(
            'ccl_admin_notices',
            'ccl_settings_updated',
            'ccl_cache_cleared',
            'ccl_banner_cached',
            'ccl_modal_cached'
        );
        
        foreach ($transients as $transient) {
            delete_transient($transient);
            delete_site_transient($transient); // For multisite
        }
        
        // 3. Delete all transients that start with our prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ccl_%' OR option_name LIKE '_transient_timeout_ccl_%'");
        
        // For multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_ccl_%' OR meta_key LIKE '_site_transient_timeout_ccl_%'");
        }
        
        // 4. Delete any custom database tables (for future versions)
        $custom_tables = array(
            $wpdb->prefix . 'ccl_consent_logs',
            $wpdb->prefix . 'ccl_analytics',
            $wpdb->prefix . 'ccl_user_preferences'
        );
        
        foreach ($custom_tables as $table) {
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table));
        }
        
        // 5. Clean up user meta data (if we stored any user-specific data)
        $user_meta_keys = array(
            'ccl_user_preferences',
            'ccl_consent_history',
            'ccl_admin_dismissed_notices'
        );
        
        foreach ($user_meta_keys as $meta_key) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key));
        }
        
        // Alternative approach for user meta cleanup with LIKE pattern
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ccl_%'");
        
        // 6. Clean up post meta (if we stored any post-specific data)
        $post_meta_keys = array(
            'ccl_page_settings',
            'ccl_excluded_pages'
        );
        
        foreach ($post_meta_keys as $meta_key) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key));
        }
        
        // 7. Clear any scheduled events/cron jobs
        $scheduled_hooks = array(
            'ccl_cleanup_expired_consents',
            'ccl_daily_maintenance',
            'ccl_weekly_stats_cleanup',
            'ccl_cache_refresh'
        );
        
        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        
        // 8. Remove custom user capabilities (if we added any)
        $custom_capabilities = array(
            'manage_cookie_consent',
            'edit_cookie_settings',
            'view_consent_logs'
        );
        
        // Remove from all roles
        $roles = array('administrator', 'editor', 'author');
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($custom_capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        
        // 9. Clean up any uploaded files (if we allowed file uploads)
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/cookie-consent-lite/';
        
        if (is_dir($plugin_upload_dir)) {
            ccl_recursive_rmdir($plugin_upload_dir);
        }
        
        // 10. Clear object cache and flush rewrite rules
        wp_cache_flush();
        
        // 11. For multisite: clean up network options
        if (is_multisite()) {
            $network_options = array(
                'ccl_network_settings',
                'ccl_network_activation_time'
            );
            
            foreach ($network_options as $option) {
                delete_site_option($option);
            }
        }
        
        // 12. Remove any custom rewrite rules (if we added any)
        delete_option('rewrite_rules');
        flush_rewrite_rules();
        
    } catch (Exception $e) {
        // Log error but don't stop uninstallation
        error_log('Cookie Consent Lite uninstall error: ' . $e->getMessage());
    }
}

/**
 * Recursively remove directory and all contents
 * 
 * @param string $dir Directory path to remove
 * @return bool Success status
 */
function ccl_recursive_rmdir($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            ccl_recursive_rmdir($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Clean up multisite installations
 */
function ccl_cleanup_multisite() {
    if (!is_multisite()) {
        return;
    }
    
    global $wpdb;
    
    // Get all sites in the network
    $sites = get_sites(array(
        'fields' => 'ids',
        'number' => 0 // Get all sites
    ));
    
    foreach ($sites as $site_id) {
        switch_to_blog($site_id);
        
        // Clean up site-specific data
        ccl_cleanup_plugin_data();
        
        restore_current_blog();
    }
}

/**
 * Final cleanup verification
 */
function ccl_verify_cleanup() {
    global $wpdb;
    
    // Verify no plugin data remains
    $remaining_options = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'ccl_%'");
    $remaining_transients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_ccl_%' OR option_name LIKE '_transient_timeout_ccl_%'");
    $remaining_user_meta = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ccl_%'");
    
    // Log cleanup results for debugging (will be removed when WP debug log is cleared)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Cookie Consent Lite uninstall complete. Remaining data: Options: %d, Transients: %d, User Meta: %d',
            $remaining_options,
            $remaining_transients,
            $remaining_user_meta
        ));
    }
}

// Execute the cleanup
if (is_multisite()) {
    ccl_cleanup_multisite();
} else {
    ccl_cleanup_plugin_data();
}

// Verify cleanup was successful
ccl_verify_cleanup();

// Final cache flush
wp_cache_flush();

// Note: WordPress will automatically remove plugin files and directories
// We don't need to handle that manually