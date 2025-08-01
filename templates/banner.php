<?php
/**
 * Cookie Consent Banner Template - Fixed
 *
 * @package CookieConsentLite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get settings safely
$settings = get_option('ccl_settings', array());

// Check if banner should be shown
$banner_enabled = !empty($settings['enable_banner']);

// If banner is disabled, don't render anything
if (!$banner_enabled) {
    echo '<script>console.log("CCL: Banner disabled in settings");</script>';
    return;
}

// Set defaults if settings are empty
$banner_text = !empty($settings['banner_text']) ? $settings['banner_text'] : 'This website uses cookies to enhance your experience, analyze traffic, and display personalized content. You can accept all cookies, reject non-essential ones, or manage your preferences via Cookie Settings.';
$accept_label = !empty($settings['accept_button_label']) ? $settings['accept_button_label'] : 'Accept All';
$reject_label = !empty($settings['reject_button_label']) ? $settings['reject_button_label'] : 'Reject';
$settings_label = !empty($settings['settings_button_label']) ? $settings['settings_button_label'] : 'Cookie Settings';
$learn_more_text = !empty($settings['learn_more_text']) ? $settings['learn_more_text'] : 'Learn more';
$learn_more_url = !empty($settings['learn_more_url']) ? $settings['learn_more_url'] : '/cookie-policy';
$banner_position = !empty($settings['banner_position']) ? $settings['banner_position'] : 'bottom';
$show_reject = !empty($settings['show_reject_button']);
$show_settings = !empty($settings['show_settings_button']);

$position_class = $banner_position === 'top' ? 'ccl-banner-top' : 'ccl-banner-bottom';
?>

<!-- Cookie Consent Banner -->
<div id="ccl-banner" class="ccl-banner <?php echo esc_attr($position_class); ?>">
    <div class="ccl-banner-container">
        <div class="ccl-banner-content">
            <div class="ccl-banner-text">
                <?php echo wp_kses_post($banner_text); ?>
                
                <?php if (!empty($learn_more_text) && !empty($learn_more_url)) : ?>
                    <a href="<?php echo esc_url($learn_more_url); ?>" 
                       class="ccl-learn-more-link"
                       target="_blank" 
                       rel="noopener noreferrer">
                        <?php echo esc_html($learn_more_text); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="ccl-banner-buttons">
                <?php if ($show_reject) : ?>
                    <button type="button" 
                            class="ccl-btn ccl-btn-reject" 
                            id="ccl-reject-btn">
                        <?php echo esc_html($reject_label); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($show_settings) : ?>
                    <button type="button" 
                            class="ccl-btn ccl-btn-settings" 
                            id="ccl-settings-btn"
                            onclick="showCookieModal()">
                        <?php echo esc_html($settings_label); ?>
                    </button>
                <?php endif; ?>
                
                <button type="button" 
                        class="ccl-btn ccl-btn-accept" 
                        id="ccl-accept-btn">
                    <?php echo esc_html($accept_label); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Debug and show banner
console.log('🍪 Banner template loaded');
console.log('CCL: Banner enabled =', <?php echo json_encode($banner_enabled); ?>);
console.log('CCL: Show reject =', <?php echo json_encode($show_reject); ?>);
console.log('CCL: Show settings =', <?php echo json_encode($show_settings); ?>);

document.addEventListener('DOMContentLoaded', function() {
    var banner = document.getElementById('ccl-banner');
    if (banner) {
        // Show the banner
        banner.style.display = 'block';
        console.log('✅ Banner element found and shown');
    } else {
        console.log('❌ Banner element not found');
    }
});
</script>