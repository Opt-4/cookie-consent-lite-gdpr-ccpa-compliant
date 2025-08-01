/**
 * Cookie Consent Lite - Frontend JavaScript - Production Ready
 * Clean, organized functionality for banner and modal
 * 
 * @package CookieConsentLite
 */

window.CookieConsentLite = window.CookieConsentLite || {};

(function() {
    'use strict';

    // Main Cookie Consent object
    const CCL = {
        // Configuration
        config: {
            selectors: {
                banner: '[id*="ccl-banner"]',
                modal: '[id*="ccl-modal"]',
                buttons: {
                    accept: '[data-action="accept"]',
                    reject: '[data-action="reject"]',
                    settings: '[data-action="settings"]',
                    acceptAll: '[data-action="accept-all"]',
                    rejectAll: '[data-action="reject-all"]',
                    savePreferences: '[data-action="save-preferences"]',
                    closeModal: '[data-action="close-modal"]'
                },
                toggles: '.ccl-toggle:not(.ccl-toggle-disabled)',
                checkboxes: 'input[data-category]'
            }
        },

        // Elements
        elements: {
            banner: null,
            modal: null
        },

        // Settings
        settings: {},

        /**
         * Initialize the cookie consent system
         */
        init: function(bannerId, modalId, settings) {
            this.settings = settings || {};
            this.elements.banner = document.getElementById(bannerId);
            this.elements.modal = document.getElementById(modalId);

            if (!this.elements.banner || !this.elements.modal) {
                return false;
            }

            this.bindEvents();
            this.initializeGlobalAPI();
            
            return true;
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            this.bindBannerEvents();
            this.bindModalEvents();
            this.bindToggleEvents();
            this.bindKeyboardEvents();
        },

        /**
         * Bind banner button events
         */
        bindBannerEvents: function() {
            const banner = this.elements.banner;
            
            const acceptBtn = banner.querySelector(this.config.selectors.buttons.accept);
            const rejectBtn = banner.querySelector(this.config.selectors.buttons.reject);
            const settingsBtn = banner.querySelector(this.config.selectors.buttons.settings);

            if (acceptBtn) {
                acceptBtn.onclick = (e) => {
                    e.preventDefault();
                    this.acceptAll();
                    return false;
                };
            }

            if (rejectBtn) {
                rejectBtn.onclick = (e) => {
                    e.preventDefault();
                    this.rejectAll();
                    return false;
                };
            }

            if (settingsBtn) {
                settingsBtn.onclick = (e) => {
                    e.preventDefault();
                    this.showModal();
                    return false;
                };
            }
        },

        /**
         * Bind modal events
         */
        bindModalEvents: function() {
            const modal = this.elements.modal;
            
            const acceptAllBtn = modal.querySelector(this.config.selectors.buttons.acceptAll);
            const rejectAllBtn = modal.querySelector(this.config.selectors.buttons.rejectAll);
            const saveBtn = modal.querySelector(this.config.selectors.buttons.savePreferences);
            const closeBtn = modal.querySelector(this.config.selectors.buttons.closeModal);

            if (acceptAllBtn) {
                acceptAllBtn.onclick = (e) => {
                    e.preventDefault();
                    this.acceptAll();
                    return false;
                };
            }

            if (rejectAllBtn) {
                rejectAllBtn.onclick = (e) => {
                    e.preventDefault();
                    this.rejectAll();
                    return false;
                };
            }

            if (saveBtn) {
                saveBtn.onclick = (e) => {
                    e.preventDefault();
                    this.savePreferences();
                    return false;
                };
            }

            if (closeBtn) {
                closeBtn.onclick = (e) => {
                    e.preventDefault();
                    this.hideModal();
                    return false;
                };
            }

            // Close on overlay click
            modal.onclick = (e) => {
                if (e.target === modal || e.target.classList.contains('ccl-modal-overlay')) {
                    this.hideModal();
                }
            };
        },

        /**
         * Bind toggle switch events
         */
        bindToggleEvents: function() {
            const toggles = this.elements.modal.querySelectorAll(this.config.selectors.toggles);
            
            toggles.forEach((toggle) => {
                toggle.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const checkbox = toggle.querySelector('input[type="checkbox"]');
                    if (!checkbox) return false;

                    // Toggle state
                    checkbox.checked = !checkbox.checked;
                    this.updateToggleVisual(toggle, checkbox.checked);

                    return false;
                };
            });
        },

        /**
         * Bind keyboard events
         */
        bindKeyboardEvents: function() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.elements.modal.style.display === 'block') {
                    this.hideModal();
                }
            });
        },

        /**
         * Update toggle visual state
         */
        updateToggleVisual: function(toggle, isChecked) {
            const slider = toggle.querySelector('.ccl-toggle-slider');
            
            if (isChecked) {
                toggle.style.setProperty('background', '#4CAF50', 'important');
                toggle.style.setProperty('border-color', '#4CAF50', 'important');
                toggle.classList.add('ccl-toggle-active');
                
                if (slider) {
                    slider.style.setProperty('left', '28px', 'important');
                }
            } else {
                toggle.style.setProperty('background', '#ccc', 'important');
                toggle.style.setProperty('border-color', '#ddd', 'important');
                toggle.classList.remove('ccl-toggle-active');
                
                if (slider) {
                    slider.style.setProperty('left', '2px', 'important');
                }
            }
        },

        /**
         * Show modal
         */
        showModal: function() {
            this.elements.modal.style.display = 'block';
            this.setModalStates();
        },

        /**
         * Hide modal
         */
        hideModal: function() {
            this.elements.modal.style.display = 'none';
        },

        /**
         * Hide banner with animation
         */
        hideBanner: function() {
            const banner = this.elements.banner;
            
            if (banner) {
                banner.style.animation = 'ccl-slide-out 0.3s ease-in forwards';
                banner.style.opacity = '0';
                
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 300);
            }
        },

        /**
         * Accept all cookies
         */
        acceptAll: function() {
            const cookies = {
                ccl_consent: 'accepted',
                ccl_essential: 'true'
            };

            // Add optional categories if enabled
            if (this.settings.enable_analytics) cookies.ccl_analytics = 'true';
            if (this.settings.enable_marketing) cookies.ccl_marketing = 'true';
            if (this.settings.enable_preferences) cookies.ccl_preferences = 'true';

            this.setCookies(cookies);
            this.hideModal();
            this.hideBanner();
            this.triggerConsentEvent('accepted', cookies);
        },

        /**
         * Reject all non-essential cookies
         */
        rejectAll: function() {
            const cookies = {
                ccl_consent: 'rejected',
                ccl_essential: 'true',
                ccl_analytics: 'false',
                ccl_marketing: 'false',
                ccl_preferences: 'false'
            };

            this.setCookies(cookies);
            this.hideModal();
            this.hideBanner();
            this.triggerConsentEvent('rejected', cookies);
        },

        /**
         * Save custom preferences
         */
        savePreferences: function() {
            const cookies = {
                ccl_consent: 'customized',
                ccl_essential: 'true'
            };

            // Get toggle states
            const categories = ['analytics', 'marketing', 'preferences'];
            categories.forEach(category => {
                const checkbox = this.elements.modal.querySelector(`input[data-category="${category}"]`);
                cookies[`ccl_${category}`] = checkbox && checkbox.checked ? 'true' : 'false';
            });

            this.setCookies(cookies);
            this.hideModal();
            this.hideBanner();
            this.triggerConsentEvent('customized', cookies);
        },

        /**
         * Set multiple cookies with error handling
         */
        setCookies: function(cookies) {
            try {
                const expirationDays = this.settings.consent_expiration || 180;
                
                Object.entries(cookies).forEach(([name, value]) => {
                    this.setCookie(name, value, expirationDays);
                });
            } catch (error) {
                // Silent fail in production
            }
        },

        /**
         * Set individual cookie with validation
         */
        setCookie: function(name, value, days) {
            try {
                // Validate inputs
                if (!name || typeof name !== 'string') return false;
                if (!value || typeof value !== 'string') return false;
                if (!days || days < 1 || days > 365) days = 180;
                
                const expires = new Date();
                expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
                
                const cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
                document.cookie = cookieString;
                
                return true;
            } catch (error) {
                return false;
            }
        },

        /**
         * Get cookie value with validation
         */
        getCookie: function(name) {
            try {
                if (!name || typeof name !== 'string') return null;
                
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${encodeURIComponent(name)}=`);
                
                if (parts.length === 2) {
                    const cookieValue = parts.pop().split(';').shift();
                    return decodeURIComponent(cookieValue);
                }
                
                return null;
            } catch (error) {
                return null;
            }
        },

        /**
         * Set modal toggle states from existing cookies
         */
        setModalStates: function() {
            const categories = ['analytics', 'marketing', 'preferences'];
            
            categories.forEach(category => {
                const checkbox = this.elements.modal.querySelector(`input[data-category="${category}"]`);
                if (checkbox) {
                    const cookieValue = this.getCookie(`ccl_${category}`);
                    const isChecked = cookieValue === 'true';
                    
                    checkbox.checked = isChecked;
                    
                    const toggle = checkbox.closest('.ccl-toggle');
                    if (toggle) {
                        this.updateToggleVisual(toggle, isChecked);
                    }
                }
            });
        },

        /**
         * Trigger consent event for third-party integrations
         */
        triggerConsentEvent: function(action, cookies) {
            try {
                // Create custom event
                const event = new CustomEvent('cclConsentChanged', {
                    detail: {
                        action: action,
                        cookies: cookies,
                        timestamp: new Date().toISOString()
                    }
                });
                
                document.dispatchEvent(event);
                
                // Also trigger on window for broader compatibility
                if (window.gtag && typeof window.gtag === 'function') {
                    // Google Analytics consent mode
                    window.gtag('consent', 'update', {
                        'analytics_storage': cookies.ccl_analytics === 'true' ? 'granted' : 'denied',
                        'ad_storage': cookies.ccl_marketing === 'true' ? 'granted' : 'denied'
                    });
                }
                
            } catch (error) {
                // Silent fail in production
            }
        },

        /**
         * Initialize global API
         */
        initializeGlobalAPI: function() {
            // Expose public API
            window.CookieConsentLite = {
                // Check if category has consent
                hasConsent: (category) => {
                    return this.getCookie(`ccl_${category}`) === 'true';
                },

                // Get all consent status
                getConsentStatus: () => {
                    return {
                        essential: this.getCookie('ccl_essential') === 'true',
                        analytics: this.getCookie('ccl_analytics') === 'true',
                        marketing: this.getCookie('ccl_marketing') === 'true',
                        preferences: this.getCookie('ccl_preferences') === 'true',
                        timestamp: this.getCookie('ccl_consent_expiry')
                    };
                },

                // Show preferences modal
                showPreferences: () => {
                    this.showModal();
                },

                // Reset consent (for testing/debugging)
                resetConsent: () => {
                    if (typeof window.location !== 'undefined' && 
                        (window.location.hostname === 'localhost' || 
                         window.location.hostname.includes('dev') ||
                         window.location.hostname.includes('staging'))) {
                        
                        const cookies = ['ccl_consent', 'ccl_essential', 'ccl_analytics', 'ccl_marketing', 'ccl_preferences'];
                        cookies.forEach(cookie => {
                            document.cookie = `${cookie}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
                        });
                        
                        if (window.location && window.location.reload) {
                            window.location.reload();
                        }
                    }
                },

                // Get current consent timestamp
                getConsentDate: () => {
                    const expiry = this.getCookie('ccl_consent_expiry');
                    if (expiry) {
                        const expiryTime = parseInt(expiry);
                        const consentDays = this.settings.consent_expiration || 180;
                        const consentTime = expiryTime - (consentDays * 24 * 60 * 60 * 1000);
                        return new Date(consentTime);
                    }
                    return null;
                }
            };

            // Development/debugging API (only in non-production environments)
            if (typeof window.location !== 'undefined' && 
                (window.location.hostname === 'localhost' || 
                 window.location.hostname.includes('dev') ||
                 window.location.hostname.includes('staging'))) {
                
                window.CCL_Debug = {
                    acceptAll: () => this.acceptAll(),
                    rejectAll: () => this.rejectAll(),
                    showModal: () => this.showModal(),
                    hideModal: () => this.hideModal(),
                    getCookies: () => document.cookie,
                    elements: this.elements,
                    settings: this.settings
                };
            }
        },

        /**
         * Validate environment and settings
         */
        validateEnvironment: function() {
            // Check for required browser features
            const requiredFeatures = [
                'document',
                'document.cookie',
                'document.addEventListener',
                'document.getElementById',
                'document.querySelector'
            ];
            
            for (let feature of requiredFeatures) {
                const parts = feature.split('.');
                let obj = window;
                
                for (let part of parts) {
                    if (!obj || typeof obj[part] === 'undefined') {
                        return false;
                    }
                    obj = obj[part];
                }
            }
            
            return true;
        },

        /**
         * Handle script blocking based on consent
         */
        handleScriptBlocking: function() {
            const categories = ['analytics', 'marketing', 'preferences'];
            
            categories.forEach(category => {
                const hasConsent = this.getCookie(`ccl_${category}`) === 'true';
                
                if (hasConsent) {
                    this.enableScriptsForCategory(category);
                }
            });
        },

        /**
         * Enable scripts for a specific category
         */
        enableScriptsForCategory: function(category) {
            try {
                const scripts = document.querySelectorAll(`script[data-category="${category}"]`);
                
                scripts.forEach(script => {
                    if (script.getAttribute('data-src')) {
                        // External script
                        const newScript = document.createElement('script');
                        newScript.src = script.getAttribute('data-src');
                        
                        // Copy attributes
                        Array.from(script.attributes).forEach(attr => {
                            if (attr.name !== 'data-category' && attr.name !== 'data-src') {
                                newScript.setAttribute(attr.name, attr.value);
                            }
                        });
                        
                        document.head.appendChild(newScript);
                        script.remove();
                        
                    } else if (script.innerHTML && script.type === 'text/plain') {
                        // Inline script
                        const newScript = document.createElement('script');
                        newScript.type = 'text/javascript';
                        newScript.innerHTML = script.innerHTML;
                        
                        document.head.appendChild(newScript);
                        script.remove();
                    }
                });
            } catch (error) {
                // Silent fail in production
            }
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Will be initialized by the main plugin when banner is loaded
        });
    }

    // Expose CCL for initialization
    window.CCL_Init = function(bannerId, modalId, settings) {
        // Validate environment first
        if (!CCL.validateEnvironment()) {
            return false;
        }
        
        // Initialize with error handling
        try {
            const success = CCL.init(bannerId, modalId, settings);
            
            if (success) {
                // Handle existing script blocking
                CCL.handleScriptBlocking();
            }
            
            return success;
        } catch (error) {
            // Silent fail in production, but log for debugging
            if (typeof console !== 'undefined' && console.error) {
                console.error('CCL initialization failed:', error);
            }
            return false;
        }
    };

    // Expose utility functions
    window.CCL_Utils = {
        // Check if user has any consent
        hasAnyConsent: function() {
            const consent = document.cookie.includes('ccl_consent=');
            return consent;
        },
        
        // Check if consent is expired
        isConsentExpired: function() {
            const expiry = CCL.getCookie('ccl_consent_expiry');
            if (!expiry) return true;
            
            const expiryTime = parseInt(expiry);
            const currentTime = Date.now();
            
            return currentTime > expiryTime;
        },
        
        // Refresh consent (show banner again if expired)
        refreshConsent: function() {
            if (this.isConsentExpired()) {
                // Clear expired cookies
                const cookies = ['ccl_consent', 'ccl_essential', 'ccl_analytics', 'ccl_marketing', 'ccl_preferences'];
                cookies.forEach(cookie => {
                    document.cookie = `${cookie}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
                });
                
                // Reload page to show banner again
                if (window.location && window.location.reload) {
                    window.location.reload();
                }
            }
        }
    };

})();