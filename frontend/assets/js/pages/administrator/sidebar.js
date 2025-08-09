/**
 * Administrator Page JavaScript Functions
 * Clean, modular JavaScript for administrator layout and user management
 * 
 * @author TMU Development Team
 * @version 2.0
 * @since 2025
 */

/**
 * Administrator Layout Manager
 * Handles sidebar functionality, notifications, and logout
 */
class AdministratorLayout {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.showAutoMessages();
        // this.setupAutoHideAlerts(); // Auto-hide disabled
    }

    bindEvents() {
        // Any additional layout-specific events can be added here
        this.setupKeyboardShortcuts();
    }

    /**
     * Logout confirmation with SweetAlert
     */
    confirmLogout() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari sistem?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#10b981',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../../backend/utils/logout.php';
                }
            });
        } else {
            // Fallback if SweetAlert is not available
            if (confirm('Apakah Anda yakin ingin keluar dari sistem?')) {
                window.location.href = '../../../backend/utils/logout.php';
            }
        }
    }

    /**
     * Show success/error messages with SweetAlert from PHP variables
     */
    showAutoMessages() {
        // These will be populated by PHP if messages exist
        if (typeof window.swalSuccess !== 'undefined' && window.swalSuccess) {
            this.showSuccess(window.swalSuccess);
        }

        if (typeof window.swalError !== 'undefined' && window.swalError) {
            this.showError(window.swalError);
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
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK'
            });
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                confirmButtonColor: '#10b981'
            });
        }
    }

    /**
     * Auto-hide alert messages after 5 seconds
     * DISABLED - Alerts will remain visible until manually closed
     */
    setupAutoHideAlerts() {
        // Auto-hide functionality disabled
        // setTimeout(() => {
        //     const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        //     alerts.forEach((alert) => {
        //         alert.style.opacity = '0';
        //         alert.style.transition = 'opacity 0.5s ease-out';
        //         setTimeout(() => {
        //             alert.remove();
        //         }, 500);
        //     });
        // }, 5000);
    }

    /**
     * Setup keyboard shortcuts for administrator
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + L for logout
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                this.confirmLogout();
            }
        });
    }
}

/**
 * User Management Class
 * Handles user CRUD operations and modal interactions
 */
class UserManagement {
    constructor() {
        this.modal = document.getElementById('tambahModal');
        this.form = this.modal?.querySelector('form');
        this.deleteForm = document.getElementById('deleteForm');
        this.init();
    }

    init() {
        this.bindEvents();
        this.showMessages();
    }

    bindEvents() {
        // Close modal when clicking outside
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal?.classList.contains('hidden')) {
                this.closeModal();
            }
        });
    }

    showModal() {
        if (!this.modal) return;
        
        this.modal.classList.remove('hidden');
        const nameInput = document.getElementById('nama');
        nameInput?.focus();
    }

    closeModal() {
        if (!this.modal || !this.form) return;
        
        this.modal.classList.add('hidden');
        this.form.reset();
    }

    confirmDelete(userId, userName) {
        const config = {
            title: 'Konfirmasi Hapus User',
            text: `Apakah Anda yakin ingin menghapus user "${userName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'animate-fade-in'
            }
        };

        Swal.fire(config).then((result) => {
            if (result.isConfirmed) {
                this.deleteUser(userId);
            }
        });
    }

    deleteUser(userId) {
        const deleteUserIdInput = document.getElementById('deleteUserId');
        if (deleteUserIdInput && this.deleteForm) {
            deleteUserIdInput.value = userId;
            this.deleteForm.submit();
        }
    }

    showMessages() {
        const successMessage = this.getPhpVariable('successMessage');
        const errorMessage = this.getPhpVariable('errorMessage');

        if (successMessage) {
            this.showSuccessAlert(successMessage);
        }

        if (errorMessage) {
            this.showErrorAlert(errorMessage);
        }
    }

    showSuccessAlert(message) {
        Swal.fire({
            title: 'Berhasil!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#16a34a',
            customClass: {
                popup: 'animate-fade-in'
            }
        });
    }

    showErrorAlert(message) {
        Swal.fire({
            title: 'Error!',
            text: message,
            icon: 'error',
            confirmButtonColor: '#dc2626',
            customClass: {
                popup: 'animate-fade-in'
            }
        });
    }

    getPhpVariable(variableName) {
        // This method will be called from inline script with PHP variables
        return window[variableName] || null;
    }
}

// Global instances
let administratorLayout;
let userManagement;

// Global functions for backward compatibility
function showTambahModal() {
    userManagement?.showModal();
}

function closeTambahModal() {
    userManagement?.closeModal();
}

function confirmDelete(userId, userName) {
    userManagement?.confirmDelete(userId, userName);
}

function confirmLogout() {
    administratorLayout?.confirmLogout();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize administrator layout manager
    administratorLayout = new AdministratorLayout();
    
    // Initialize user management (only if modal exists - for data_user.php)
    if (document.getElementById('tambahModal')) {
        userManagement = new UserManagement();
    }
    
    // Make functions globally available
    window.showTambahModal = showTambahModal;
    window.closeTambahModal = closeTambahModal;
    window.confirmDelete = confirmDelete;
    window.confirmLogout = confirmLogout;
    
    console.log('Administrator page initialized successfully');
});

/**
 * Handle PHP-generated notifications
 * This function will be called from PHP via inline script
 */
function showPhpNotifications(successMessage, errorMessage) {
    if (administratorLayout) {
        if (successMessage) {
            administratorLayout.showSuccess(successMessage);
        }
        
        if (errorMessage) {
            administratorLayout.showError(errorMessage);
        }
    }
}
