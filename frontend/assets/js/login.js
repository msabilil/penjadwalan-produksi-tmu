/**
 * Login Page JavaScript
 * Clean Code Implementation with ES6+ features
 * 
 * @author TMU Development Team
 * @version 1.0
 * @since 2025
 */

class LoginManager {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.usernameField = document.getElementById('username');
        this.passwordField = document.getElementById('password');
        this.loginButton = document.getElementById('loginButton');
        this.loginText = document.getElementById('loginText');
        this.loadingSpinner = document.getElementById('loadingSpinner');
        this.eyeIcon = document.getElementById('eyeIcon');
        
        this.isLoading = false;
        this.init();
    }

    /**
     * Initialize login functionality
     */
    init() {
        this.bindEvents();
        this.focusUsernameField();
        this.setupAccessibility();
    }

    /**
     * Bind all event listeners
     */
    bindEvents() {
        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Password toggle
        const toggleButton = document.querySelector('[onclick="togglePassword()"]');
        if (toggleButton) {
            toggleButton.removeAttribute('onclick');
            toggleButton.addEventListener('click', () => this.togglePassword());
        }

        // Enter key navigation
        if (this.usernameField) {
            this.usernameField.addEventListener('keypress', (e) => this.handleEnterKey(e, 'password'));
        }

        if (this.passwordField) {
            this.passwordField.addEventListener('keypress', (e) => this.handleEnterKey(e, 'submit'));
        }

        // Real-time validation (optional enhancement)
        this.setupValidation();
    }

    /**
     * Handle form submission
     */
    handleFormSubmit(event) {
        if (this.isLoading) {
            event.preventDefault();
            return;
        }

        // Basic client-side validation
        if (!this.validateForm()) {
            event.preventDefault();
            return;
        }

        // Show loading state
        this.setLoadingState(true);
        
        // Form will submit naturally to PHP
    }

    /**
     * Basic form validation
     */
    validateForm() {
        const username = this.usernameField?.value?.trim();
        const password = this.passwordField?.value?.trim();

        if (!username || !password) {
            this.showError('Username dan password wajib diisi');
            return false;
        }

        if (username.length < 3 || password.length < 3) {
            this.showError('Username dan password minimal 3 karakter');
            return false;
        }

        return true;
    }

    /**
     * Setup real-time validation
     */
    setupValidation() {
        // Clear validation errors on input
        [this.usernameField, this.passwordField].forEach(field => {
            if (field) {
                field.addEventListener('input', () => this.clearFieldError(field));
                field.addEventListener('blur', () => this.validateField(field));
            }
        });
    }

    /**
     * Validate individual field
     */
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;

        this.clearFieldError(field);

        if (!value) {
            this.setFieldError(field, `${this.getFieldLabel(fieldName)} wajib diisi`);
            return false;
        }

        if (value.length < 3) {
            this.setFieldError(field, `${this.getFieldLabel(fieldName)} minimal 3 karakter`);
            return false;
        }

        this.setFieldValid(field);
        return true;
    }

    /**
     * Get field label for error messages
     */
    getFieldLabel(fieldName) {
        const labels = {
            username: 'Username',
            password: 'Password'
        };
        return labels[fieldName] || fieldName;
    }

    /**
     * Set field error state
     */
    setFieldError(field, message) {
        field.classList.add('border-red-300');
        field.classList.remove('border-green-300');
        
        // Show error message if there's a container for it
        const errorContainer = field.parentNode.querySelector('.error-message');
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.classList.remove('hidden');
        }
    }

    /**
     * Clear field error state
     */
    clearFieldError(field) {
        field.classList.remove('border-red-300');
        
        const errorContainer = field.parentNode.querySelector('.error-message');
        if (errorContainer) {
            errorContainer.textContent = '';
            errorContainer.classList.add('hidden');
        }
    }

    /**
     * Set field valid state
     */
    setFieldValid(field) {
        field.classList.add('border-green-300');
        field.classList.remove('border-red-300');
    }

    /**
     * Toggle password visibility
     */
    togglePassword() {
        if (!this.passwordField || !this.eyeIcon) return;

        const isPassword = this.passwordField.type === 'password';
        
        this.passwordField.type = isPassword ? 'text' : 'password';
        this.updateEyeIcon(!isPassword);
    }

    /**
     * Update eye icon based on password visibility
     */
    updateEyeIcon(isHidden) {
        if (!this.eyeIcon) return;

        const eyeOpenIcon = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;

        const eyeClosedIcon = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;

        this.eyeIcon.innerHTML = isHidden ? eyeOpenIcon : eyeClosedIcon;
    }

    /**
     * Handle Enter key navigation
     */
    handleEnterKey(event, action) {
        if (event.key === 'Enter') {
            event.preventDefault();
            
            if (action === 'submit') {
                this.form?.submit();
            } else {
                const targetField = document.getElementById(action);
                targetField?.focus();
            }
        }
    }

    /**
     * Set loading state
     */
    setLoadingState(loading) {
        this.isLoading = loading;
        
        if (this.loginButton) {
            this.loginButton.disabled = loading;
        }
        
        if (this.loginText) {
            this.loginText.textContent = loading ? 'Memproses...' : 'Masuk';
        }
        
        if (this.loadingSpinner) {
            if (loading) {
                this.loadingSpinner.classList.remove('hidden');
            } else {
                this.loadingSpinner.classList.add('hidden');
            }
        }
    }

    /**
     * Focus username field on page load
     */
    focusUsernameField() {
        setTimeout(() => {
            this.usernameField?.focus();
        }, 100);
    }

    /**
     * Setup accessibility features
     */
    setupAccessibility() {
        // Add ARIA labels
        const toggleButton = document.querySelector('[onclick], button[type="button"]');
        if (toggleButton && toggleButton.querySelector('#eyeIcon')) {
            toggleButton.setAttribute('aria-label', 'Toggle password visibility');
        }

        // Add form role
        if (this.form) {
            this.form.setAttribute('role', 'form');
            this.form.setAttribute('aria-label', 'Login form');
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                timer: 1500,
                showConfirmButton: false,
                timerProgressBar: true
            });
        } else {
            alert(message);
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal!',
                text: message,
                confirmButtonColor: '#10b981'
            });
        } else {
            alert(message);
        }
    }
}

/**
 * Demo Users Helper Functions
 */
class DemoUsersHelper {
    /**
     * Fill demo credentials (for development)
     */
    static fillCredentials(username, password) {
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        
        if (usernameField && passwordField) {
            usernameField.value = username;
            passwordField.value = password;
            
            // Trigger input events for validation
            usernameField.dispatchEvent(new Event('input', { bubbles: true }));
            passwordField.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Focus password field
            passwordField.focus();
            
            // Show notification
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Kredensial Terisi',
                    text: `Username dan password untuk ${username} telah diisi otomatis`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    timerProgressBar: true
                });
            }
        }
    }
}

/**
 * Global functions for backward compatibility
 */
function togglePassword() {
    if (window.loginManager) {
        window.loginManager.togglePassword();
    }
}

function fillCredentials(username, password) {
    DemoUsersHelper.fillCredentials(username, password);
}

/**
 * Initialize when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize login manager
    window.loginManager = new LoginManager();
    
    // Make functions globally available
    window.togglePassword = togglePassword;
    window.fillCredentials = fillCredentials;
    
    console.log('Login page initialized successfully');
});

/**
 * Handle PHP-generated notifications
 * This function will be called from PHP via inline script
 */
function showPhpNotifications(successMessage, errorMessage) {
    if (window.loginManager) {
        if (successMessage) {
            window.loginManager.showSuccess(successMessage);
        }
        
        if (errorMessage) {
            window.loginManager.showError(errorMessage);
        }
    }
}
