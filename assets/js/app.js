/**
 * FreeLLMAPI - Main Application JavaScript
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    function initializeApp() {
        // Add any global initialization here
        console.log('FreeLLMAPI initialized');
        
        // Add keyboard shortcuts
        setupKeyboardShortcuts();
        
        // Auto-dismiss messages
        autoDismissMessages();
    }

    // Keyboard shortcuts
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search (future feature)
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // Could add search functionality here
            }
            
            // Escape to close modals (future feature)
            if (e.key === 'Escape') {
                // Close any open modals
            }
        });
    }

    // Auto-dismiss message banners
    function autoDismissMessages() {
        const banners = document.querySelectorAll('.message-banner');
        banners.forEach(banner => {
            setTimeout(() => {
                banner.style.animation = 'slideDown 0.3s ease reverse';
                setTimeout(() => banner.remove(), 300);
            }, 5000);
        });
    }

    // Utility: Copy to clipboard
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    };

    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showNotification('Copied to clipboard!', 'success');
        } catch (err) {
            showNotification('Failed to copy', 'error');
        }
        document.body.removeChild(textarea);
    }

    // Utility: Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `message-banner ${type}`;
        const iconClass = type === 'success' ? 'fa-check' : type === 'error' ? 'fa-xmark' : 'fa-circle-info';
        notification.innerHTML = `
            <span class="message-icon"><i class="fa-solid ${iconClass}" aria-hidden="true"></i></span>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Utility: Format numbers with commas
    window.formatNumber = function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    // Utility: Format time ago
    window.timeAgo = function(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        return Math.floor(seconds / 86400) + 'd ago';
    };

    // Chat-specific functionality is in home.php
    // This file handles global utilities and shared functionality

})();
