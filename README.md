=== Cookie Consent Lite – GDPR & CCPA Compliant ===
Contributors: opt4
Plugin URI: https://www.opt-4.co.uk/cookie-consent-lite-gdpr-ccpa-compliant/
Author URI: https://www.opt-4.co.uk/
Donate link: https://www.opt-4.co.uk/
Tags: cookie consent, gdpr, ccpa, privacy, cookies, compliance, banner, modal, cache
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
**Created by [Opt-4]([https://yourdomain.com](https://www.opt-4.co.uk/))**

A lightweight, GDPR & CCPA compliant cookie consent banner with nuclear cache-busting technology that works with any caching setup.

== Description ==

**Cookie Consent Lite** is a powerful yet lightweight solution for GDPR and CCPA compliance. Unlike other cookie consent plugins that struggle with caching, our plugin features revolutionary **Nuclear Cache-Busting Technology** that ensures your cookie banner works perfectly with any caching system.

### 🚀 Key Features

* **Nuclear Cache-Busting Technology** - Works with ANY caching plugin or CDN
* **GDPR & CCPA Compliant** - Legally compliant cookie consent management
* **Modern, Responsive Design** - Beautiful interface that works on all devices
* **Cookie Categories** - Analytics, Marketing, and Preferences categories
* **Customizable Styling** - Colors, positions, and button labels
* **Professional Admin Interface** - Modern toggle switches and intuitive settings
* **Aggressive Cache Clearing** - Automatically clears all major caching systems
* **Zero Configuration** - Works out of the box with sensible defaults

### 🎯 Why Choose Cookie Consent Lite?

**The Cache Problem Solved**: Most cookie consent plugins fail when used with caching plugins like WP Rocket, W3 Total Cache, or CDNs like Cloudflare. Our plugin uses a revolutionary AJAX-based approach that bypasses ALL caching layers, ensuring your banner always displays when needed.

**Professional Grade**: Built with enterprise-level code quality, following WordPress coding standards and best practices.

**Lightweight**: Minimal impact on your site's performance while providing maximum functionality.

### 🔧 Supported Cache Systems

* WP Rocket
* W3 Total Cache
* WP Super Cache
* LiteSpeed Cache
* WP Fastest Cache
* Autoptimize
* SG Optimizer
* Breeze
* Cloudflare CDN
* Redis
* Memcached
* OPcache

### 🎨 Customization Options

* **Colors**: Primary, secondary, background, and text colors
* **Position**: Top or bottom banner placement
* **Buttons**: Show/hide reject and settings buttons
* **Text**: Fully customizable button labels and banner text
* **Categories**: Enable/disable cookie categories
* **Expiration**: Set custom consent expiration periods

### 🌐 GDPR & CCPA Features

* **Granular Control**: Users can accept/reject specific cookie categories
* **Clear Information**: Transparent descriptions of cookie purposes
* **Easy Management**: Users can change preferences anytime
* **Compliance**: Meets legal requirements for data protection

### 🛠️ Developer Friendly

* **Clean Code**: Well-structured, documented codebase
* **Hooks & Filters**: Extensible for custom functionality
* **API Functions**: Check consent status programmatically
* **Debug Tools**: Built-in testing and debugging features

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Cookie Consent Lite"
4. Click "Install Now" and then "Activate"
5. Go to Settings → Cookie Consent to configure

### Manual Installation

1. Download the plugin ZIP file
2. Upload to your `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu
5. Go to Settings → Cookie Consent to configure

### Configuration

1. **Enable the Banner**: Turn on the cookie consent banner
2. **Customize Text**: Edit the banner message and button labels
3. **Set Categories**: Enable Analytics, Marketing, and/or Preferences cookies
4. **Style Colors**: Choose colors that match your brand
5. **Test**: Visit your website in incognito mode to see the banner

== Frequently Asked Questions ==

= Does this work with caching plugins? =

**Yes!** This is our main strength. Cookie Consent Lite uses Nuclear Cache-Busting Technology that works with ALL caching plugins and CDNs, including WP Rocket, W3 Total Cache, Cloudflare, and more.

= Is this GDPR compliant? =

Yes, the plugin is designed to meet GDPR requirements with granular cookie categories, clear consent options, and the ability for users to withdraw consent at any time.

= Does it work with CCPA? =

Yes, the plugin supports CCPA compliance with options to reject non-essential cookies and clear privacy controls.

= Will it slow down my website? =

No, the plugin is optimized for performance. The banner loads asynchronously and has minimal impact on page load times.

= Can I customize the appearance? =

Absolutely! You can customize colors, button labels, banner text, position, and more through the admin interface.

= Does it work on mobile devices? =

Yes, the plugin is fully responsive and works perfectly on all devices and screen sizes.

= Can I disable specific cookie categories? =

Yes, you can enable/disable Analytics, Marketing, and Preferences categories individually through the admin settings.

= How do I check if a user has given consent? =

You can use the JavaScript API: `CookieConsentLite.hasConsent('analytics')` or the PHP function `CCL_Frontend::has_category_consent('analytics')`.

= What happens when users reject cookies? =

The plugin sets cookies to remember the user's choice and blocks non-essential scripts from loading based on their preferences.

= Can I reset all cookie consent data? =

Yes, there's a debug mode with reset functionality for testing purposes.

== Screenshots ==

1. **Cookie Consent Banner** - Clean, responsive banner that appears at the bottom of your website with clear Accept All, Reject, and Cookie Settings buttons.

2. **Cookie Preferences Modal** - Detailed modal allowing users to control specific cookie categories (Essential, Analytics, Marketing) with intuitive toggle switches.

3. **Admin Settings Interface** - Modern admin page with clean white header, professional logo placement, and organized settings sections with toggle switches.

4. **Button Configuration Panel** - Comprehensive button customization options with real-time character counters, toggle switches for visibility, and professional form layout.

== Changelog ==

= 1.0.0 =
* Initial release
* Nuclear Cache-Busting Technology
* GDPR & CCPA compliance features
* Modern responsive design
* Support for all major caching plugins
* Customizable colors and text
* Cookie categories (Analytics, Marketing, Preferences)
* Professional admin interface with toggle switches
* Aggressive cache clearing system
* Debug tools and testing features

== Upgrade Notice ==

= 1.0.0 =
Initial release of Cookie Consent Lite with revolutionary cache-busting technology.

== Developer Documentation ==

### JavaScript API

Check consent status:
```javascript
// Check if user has consented to analytics cookies
if (CookieConsentLite.hasConsent('analytics')) {
    // Load analytics scripts
}

// Get all consent status
const consent = CookieConsentLite.getConsentStatus();
console.log(consent.analytics); // true/false
```

Show preferences modal:
```javascript
CookieConsentLite.showPreferences();
```

### PHP Functions

Check consent in PHP:
```php
// Check if user has consented to marketing cookies
if (CCL_Frontend::has_category_consent('marketing')) {
    // Execute marketing code
}
```

Load scripts conditionally:
```php
// Load script only if user consented to analytics
CCL_Frontend::load_script_if_consent('analytics', 'https://analytics.example.com/script.js');
```

### Hooks & Filters

Available filters:
* `ccl_banner_text` - Modify banner text
* `ccl_button_labels` - Modify button labels
* `ccl_cookie_categories` - Add custom cookie categories

Available actions:
* `ccl_consent_given` - Triggered when consent is given
* `ccl_consent_withdrawn` - Triggered when consent is withdrawn
* `ccl_cache_cleared` - Triggered after cache clearing

== Support ==

For support, documentation, and feature requests, please visit:
[https://www.opt-4.co.uk/](https://www.opt-4.co.uk/)

== Privacy Policy ==

This plugin stores user consent preferences in cookies and does not collect any personal data. All consent management is handled locally on your website.
