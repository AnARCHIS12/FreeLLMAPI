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

        // Styled confirmations
        setupConfirmForms();
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
                closeAppModal();
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
                showAppModal({
                    type: 'success',
                    title: 'Copied',
                    message: 'Content copied to clipboard.',
                    confirmText: 'OK',
                    showCancel: false
                });
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
            showAppModal({
                type: 'success',
                title: 'Copied',
                message: 'Content copied to clipboard.',
                confirmText: 'OK',
                showCancel: false
            });
        } catch (err) {
            showAppModal({
                type: 'error',
                title: 'Copy failed',
                message: 'Your browser blocked clipboard access.',
                confirmText: 'OK',
                showCancel: false
            });
        }
        document.body.removeChild(textarea);
    }

    function setupConfirmForms() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
                return;
            }

            e.preventDefault();

            showAppModal({
                type: 'warning',
                title: 'Confirm action',
                message: form.dataset.confirm,
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                showCancel: true,
                onConfirm: function() {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
            });
        });

        document.addEventListener('click', function(e) {
            const button = e.target.closest('button[data-confirm]');
            if (!button || !button.form || button.form.dataset.confirm) {
                return;
            }

            button.form.dataset.confirm = button.dataset.confirm;
        });
    }

    function showAppModal(options) {
        closeAppModal();

        const settings = Object.assign({
            type: 'info',
            title: 'Notice',
            message: '',
            confirmText: 'OK',
            cancelText: 'Cancel',
            showCancel: false,
            onConfirm: null,
        }, options || {});

        const overlay = document.createElement('div');
        overlay.className = 'app-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        const iconClass = {
            success: 'fa-check',
            error: 'fa-xmark',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info',
        }[settings.type] || 'fa-circle-info';

        overlay.innerHTML = `
            <div class="app-modal app-modal-${settings.type}">
                <button class="app-modal-close" type="button" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="app-modal-icon">
                    <i class="fa-solid ${iconClass}" aria-hidden="true"></i>
                </div>
                <div class="app-modal-body">
                    <h2>${escapeHtml(settings.title)}</h2>
                    <p>${escapeHtml(settings.message)}</p>
                </div>
                <div class="app-modal-actions">
                    ${settings.showCancel ? `<button type="button" class="btn btn-ghost app-modal-cancel">${escapeHtml(settings.cancelText)}</button>` : ''}
                    <button type="button" class="btn btn-primary app-modal-confirm">${escapeHtml(settings.confirmText)}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.classList.add('modal-open');

        overlay.querySelector('.app-modal-close').addEventListener('click', closeAppModal);
        const cancelButton = overlay.querySelector('.app-modal-cancel');
        if (cancelButton) {
            cancelButton.addEventListener('click', closeAppModal);
        }

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeAppModal();
            }
        });

        overlay.querySelector('.app-modal-confirm').addEventListener('click', function() {
            const onConfirm = settings.onConfirm;
            closeAppModal();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        setTimeout(() => overlay.querySelector('.app-modal-confirm').focus(), 0);
    }

    function closeAppModal() {
        const overlay = document.querySelector('.app-modal-overlay');
        if (!overlay) {
            return;
        }
        overlay.remove();
        document.body.classList.remove('modal-open');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.showAppModal = showAppModal;
    window.closeAppModal = closeAppModal;

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
