<?php
/**
 * Cookie Consent Modal Template - Simple Version
 *
 * @package CookieConsentLite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="ccl-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 10px; padding: 0; max-width: 500px; width: 90%; max-height: 80vh; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        
        <!-- Header -->
        <div style="padding: 20px; border-bottom: 1px solid #eee; background: white;">
            <h2 style="margin: 0; font-size: 20px; color: #333;">Manage Your Cookie Preferences</h2>
            <button id="ccl-modal-close" style="position: absolute; top: 15px; right: 15px; background: #f5f5f5; border: 1px solid #ddd; width: 30px; height: 30px; border-radius: 5px; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
        
        <!-- Body -->
        <div style="padding: 20px; background: white; max-height: 400px; overflow-y: auto;">
            <p style="margin: 0 0 20px; color: #666; line-height: 1.5;">
                We use cookies to personalize content and analyze our traffic. Choose which categories to allow.
            </p>
            
            <!-- Essential -->
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 5px; font-size: 16px; color: #333;">🍪 Essential</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">Required for the website to function. Cannot be disabled.</p>
                    </div>
                    <div style="background: #4CAF50; width: 40px; height: 20px; border-radius: 20px; position: relative;">
                        <div style="background: white; width: 16px; height: 16px; border-radius: 50%; position: absolute; top: 2px; right: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                    </div>
                </div>
            </div>
            
            <!-- Analytics -->
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 5px; font-size: 16px; color: #333;">📊 Analytics</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">Help us understand how visitors interact with our website.</p>
                    </div>
                    <label style="background: #ccc; width: 40px; height: 20px; border-radius: 20px; position: relative; cursor: pointer; display: block;">
                        <input type="checkbox" id="ccl-category-analytics" style="opacity: 0; width: 0; height: 0;">
                        <span style="background: white; width: 16px; height: 16px; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); display: block;"></span>
                    </label>
                </div>
            </div>
            
            <!-- Marketing -->
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 5px; font-size: 16px; color: #333;">🎯 Marketing</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">Used to track visitors across websites for advertising.</p>
                    </div>
                    <label style="background: #ccc; width: 40px; height: 20px; border-radius: 20px; position: relative; cursor: pointer; display: block;">
                        <input type="checkbox" id="ccl-category-marketing" style="opacity: 0; width: 0; height: 0;">
                        <span style="background: white; width: 16px; height: 16px; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); display: block;"></span>
                    </label>
                </div>
            </div>
            
            <!-- Preferences -->
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: white;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 5px; font-size: 16px; color: #333;">⚙️ Preferences</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">Remember your settings for future visits.</p>
                    </div>
                    <label style="background: #ccc; width: 40px; height: 20px; border-radius: 20px; position: relative; cursor: pointer; display: block;">
                        <input type="checkbox" id="ccl-category-preferences" style="opacity: 0; width: 0; height: 0;">
                        <span style="background: white; width: 16px; height: 16px; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); display: block;"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="padding: 20px; border-top: 1px solid #eee; background: white; display: flex; gap: 10px; justify-content: flex-end;">
            <button id="ccl-modal-reject-all" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">Reject All</button>
            <button id="ccl-modal-save-preferences" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">Save Preferences</button>
            <button id="ccl-modal-accept-all" style="background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">Accept All</button>
        </div>
    </div>
</div>

<style>
/* Toggle switch animation */
#ccl-modal input[type="checkbox"]:checked + span {
    transform: translateX(20px) !important;
}
#ccl-modal input[type="checkbox"]:checked {
    background: #4CAF50 !important;
}
#ccl-modal label:has(input:checked) {
    background: #4CAF50 !important;
}

/* Button hover effects */
#ccl-modal button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    var modal = document.getElementById('ccl-modal');
    var closeBtn = document.getElementById('ccl-modal-close');
    var acceptBtn = document.getElementById('ccl-modal-accept-all');
    var rejectBtn = document.getElementById('ccl-modal-reject-all');
    var saveBtn = document.getElementById('ccl-modal-save-preferences');
    
    // Show modal function
    window.showCookieModal = function() {
        if (modal) {
            modal.style.display = 'block';
            console.log('✅ Modal opened');
        }
    };
    
    // Hide modal function
    window.hideCookieModal = function() {
        if (modal) {
            modal.style.display = 'none';
            console.log('❌ Modal closed');
        }
    };
    
    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', window.hideCookieModal);
    }
    
    // Accept all
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
            console.log('✅ Accept All clicked');
            window.hideCookieModal();
        });
    }
    
    // Reject all
    if (rejectBtn) {
        rejectBtn.addEventListener('click', function() {
            console.log('❌ Reject All clicked');
            window.hideCookieModal();
        });
    }
    
    // Save preferences
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            console.log('💾 Save Preferences clicked');
            window.hideCookieModal();
        });
    }
    
    // Close on outside click
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                window.hideCookieModal();
            }
        });
    }
    
    console.log('🔧 Simple modal initialized. Use showCookieModal() to test.');
});
</script>