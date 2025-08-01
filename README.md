# Cookie Consent Lite – GDPR & CCPA Compliant

A lightweight, GDPR & CCPA compliant cookie consent banner with nuclear cache-busting technology that works with any caching setup.

## 🔧 Plugin Details

- **Contributors**: opt4  
- **Plugin URI**: [Cookie Consent Lite](https://www.opt-4.co.uk/cookie-consent-lite-gdpr-ccpa-compliant/)  
- **Author URI**: [Opt-4](https://www.opt-4.co.uk/)  
- **Donate link**: [Donate](https://www.opt-4.co.uk/)  
- **Tags**: cookie consent, gdpr, ccpa, privacy, cookies, compliance, banner, modal, cache  
- **Requires at least**: 5.0  
- **Tested up to**: 6.4  
- **Stable tag**: 1.0.0  
- **Requires PHP**: 7.4  
- **License**: GPLv2 or later  
- **License URI**: [GPLv2](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 📖 Description

**Cookie Consent Lite** is a powerful yet lightweight solution for GDPR and CCPA compliance. Unlike other cookie consent plugins that struggle with caching, our plugin features revolutionary **Nuclear Cache-Busting Technology** that ensures your cookie banner works perfectly with any caching system.

---

## 🚀 Key Features

- **Nuclear Cache-Busting Technology** – Works with ANY caching plugin or CDN  
- **GDPR & CCPA Compliant**  
- **Modern, Responsive Design**  
- **Cookie Categories** – Analytics, Marketing, Preferences  
- **Customizable Styling**  
- **Professional Admin Interface**  
- **Aggressive Cache Clearing**  
- **Zero Configuration**

---

## 🎯 Why Choose Cookie Consent Lite?

- **Cache Problem Solved** – Compatible with WP Rocket, W3 Total Cache, Cloudflare, and more  
- **Professional Grade** – Enterprise-level code quality  
- **Lightweight** – Minimal impact on performance

---

## 🔧 Supported Cache Systems

WP Rocket, W3 Total Cache, WP Super Cache, LiteSpeed Cache, WP Fastest Cache, Autoptimize, SG Optimizer, Breeze, Cloudflare, Redis, Memcached, OPcache

---

## 🎨 Customization Options

- Colors, position, button labels, banner text  
- Enable/disable cookie categories  
- Consent expiration settings

---

## 🌐 GDPR & CCPA Features

- Granular category control  
- Transparent descriptions  
- Consent withdraw options  
- Full legal compliance

---

## 🛠️ Developer Friendly

- Clean code with hooks & filters  
- JavaScript & PHP APIs  
- Debug mode for testing

---

## 📦 Installation

### Automatic
1. Go to Plugins → Add New  
2. Search for “Cookie Consent Lite”  
3. Install and Activate  
4. Configure under Settings → Cookie Consent

### Manual
1. Upload ZIP to `/wp-content/plugins/`  
2. Extract files  
3. Activate via WordPress admin

---

## ❓ Frequently Asked Questions

**Does this work with caching plugins?**  
Yes — it’s designed specifically for that.

**Is this GDPR/CCPA compliant?**  
Absolutely.

**Can I customize it?**  
Yes, fully customizable.

**Does it slow down the site?**  
No — it's lightweight and async-loaded.

---

## 🖼️ Screenshots

1. Cookie Consent Banner  
2. Preferences Modal  
3. Admin Settings Page  
4. Button Config Panel

---

## 📝 Changelog

### 1.0.0
- Initial release  
- Full compliance features  
- Cache-busting tech  
- Developer APIs  
- Debug tools

---

## 🔧 Developer Documentation

### JavaScript API

```js
if (CookieConsentLite.hasConsent('analytics')) {
    // Load analytics
}
const consent = CookieConsentLite.getConsentStatus();
CookieConsentLite.showPreferences();
```

### PHP Functions

```php
if (CCL_Frontend::has_category_consent('marketing')) {
    // Run marketing code
}
CCL_Frontend::load_script_if_consent('analytics', 'https://analytics.example.com/script.js');
```

### Hooks & Filters

- `ccl_banner_text`  
- `ccl_button_labels`  
- `ccl_cookie_categories`  
- `ccl_consent_given`  
- `ccl_consent_withdrawn`  
- `ccl_cache_cleared`

---

## 💬 Support

Visit [Opt-4](https://www.opt-4.co.uk/) for support and documentation.

---

## 🔒 Privacy Policy

This plugin stores cookie preferences locally and does not collect personal data.
