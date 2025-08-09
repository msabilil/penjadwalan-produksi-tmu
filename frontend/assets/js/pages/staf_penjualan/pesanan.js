/**
 * Staf Penjualan - Pesanan Page JavaScript
 * Handle interaction untuk halaman pesanan
 */

// Global variables
let currentEditId = null;
let pesananTable = null;
let isLoading = false;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

/**
 * Initialize page functionality
 */
function initializePage() {
    // Initialize search functionality
    initializeSearch();
    
    // Initialize table interactions
    initializeTableActions();
    
    // Initialize modals
    initializeModals();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Show success/error messages if set
    showInitialMessages();
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 500); // Debounce 500ms
        });
    }
}

/**
 * Perform search operation
 */
function performSearch(keyword) {
    if (isLoading) return;
    
    const tableBody = document.getElementById('pesananTableBody');
    if (!tableBody) return;
    
    if (keyword.length < 3 && keyword.length > 0) {
        return; // Wait for at least 3 characters
    }
    
    setLoading(true);
    
    // Show loading state
    tableBody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-8 text-center">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mencari pesanan...
                </div>
            </td>
        </tr>
    `;
    
    // Simulate API call - in real implementation, use fetch
    setTimeout(() => {
        loadPesananData(keyword);
        setLoading(false);
    }, 1000);
}

/**
 * Initialize table action buttons
 */
function initializeTableActions() {
    // Event delegation for dynamically created buttons
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;
        
        const action = target.dataset.action;
        const pesananId = target.dataset.pesananId;
        
        switch (action) {
            case 'view':
                viewPesanan(pesananId);
                break;
            case 'edit':
                editPesanan(pesananId);
                break;
            case 'delete':
                deletePesanan(pesananId);
                break;
            case 'download-po':
                downloadPO(pesananId);
                break;
        }
    });
}

/**
 * View pesanan details
 */
function viewPesanan(pesananId) {
    showModal('viewPesananModal');
    
    // Show loading in modal
    const modalContent = document.getElementById('viewPesananContent');
    if (modalContent) {
        modalContent.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <svg class="animate-spin h-8 w-8 mr-3 text-green-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-600">Memuat detail pesanan...</span>
            </div>
        `;
        
        // Simulate loading detail
        setTimeout(() => {
            loadPesananDetail(pesananId);
        }, 1000);
    }
}

/**
 * Edit pesanan
 */
function editPesanan(pesananId) {
    currentEditId = pesananId;
    showModal('editPesananModal');
    
    // Load data into form
    loadPesananForEdit(pesananId);
}

/**
 * Delete pesanan with confirmation
 */
function deletePesanan(pesananId) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus pesanan ini? Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            performDeletePesanan(pesananId);
        }
    });
}

/**
 * Download Purchase Order
 */
function downloadPO(pesananId) {
    // Show loading indicator
    Swal.fire({
        title: 'Mempersiapkan File',
        text: 'Mohon tunggu, sedang menyiapkan Purchase Order...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulate file preparation
    setTimeout(() => {
        // In real implementation, redirect to download endpoint
        window.location.href = `download_file.php?type=po&id=${pesananId}`;
        
        Swal.close();
        
        // Show success message
        setTimeout(() => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Purchase Order berhasil diunduh.',
                icon: 'success',
                confirmButtonColor: '#16a34a'
            });
        }, 500);
    }, 2000);
}

/**
 * Show modal by ID
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

/**
 * Hide modal by ID
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

/**
 * Initialize modal functionality
 */
function initializeModals() {
    // Close modal when clicking backdrop
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            const modalId = e.target.getAttribute('id');
            hideModal(modalId);
        }
    });
    
    // Close modal with close buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close')) {
            const modal = e.target.closest('.modal-backdrop');
            if (modal) {
                hideModal(modal.getAttribute('id'));
            }
        }
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Validate form inputs
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Field ini wajib diisi');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

/**
 * Show field error
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('border-red-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Clear field error
 */
function clearFieldError(field) {
    field.classList.remove('border-red-500');
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Set loading state
 */
function setLoading(loading) {
    isLoading = loading;
    
    const loadingElements = document.querySelectorAll('.loading-indicator');
    loadingElements.forEach(el => {
        el.style.display = loading ? 'block' : 'none';
    });
}

/**
 * Show initial messages from PHP
 */
function showInitialMessages() {
    // These are set by PHP in the layout
    if (typeof window.swalSuccess !== 'undefined') {
        Swal.fire({
            title: 'Berhasil!',
            text: window.swalSuccess,
            icon: 'success',
            confirmButtonColor: '#16a34a'
        });
    }
    
    if (typeof window.swalError !== 'undefined') {
        Swal.fire({
            title: 'Error!',
            text: window.swalError,
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    }
}

/**
 * Load pesanan data (mock function)
 */
function loadPesananData(keyword = '') {
    // This would be replaced with actual fetch call
    // For now, just reload the page with search parameter
    if (keyword) {
        const url = new URL(window.location);
        url.searchParams.set('search', keyword);
        window.location.href = url.toString();
    } else {
        window.location.reload();
    }
}

/**
 * Load pesanan detail for view modal (mock)
 */
function loadPesananDetail(pesananId) {
    const modalContent = document.getElementById('viewPesananContent');
    if (modalContent) {
        // Mock detail content
        modalContent.innerHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomor Pesanan</label>
                        <p class="mt-1 text-sm text-gray-900">PSN-${pesananId.padStart(4, '0')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Pesanan</label>
                        <p class="mt-1 text-sm text-gray-900">${new Date().toLocaleDateString('id-ID')}</p>
                    </div>
                </div>
                <div class="text-center text-gray-500 py-4">
                    Detail lengkap akan dimuat dari database...
                </div>
            </div>
        `;
    }
}

/**
 * Load pesanan for edit form (mock)
 */
function loadPesananForEdit(pesananId) {
    // This would populate the edit form with current data
    console.log('Loading pesanan for edit:', pesananId);
}

/**
 * Perform delete operation (mock)
 */
function performDeletePesanan(pesananId) {
    // Show loading
    Swal.fire({
        title: 'Menghapus...',
        text: 'Sedang menghapus pesanan',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulate delete operation
    setTimeout(() => {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Pesanan berhasil dihapus',
            icon: 'success',
            confirmButtonColor: '#16a34a'
        }).then(() => {
            // Reload page or remove row from table
            window.location.reload();
        });
    }, 1500);
}

// Export functions for global access
window.pesananJS = {
    viewPesanan,
    editPesanan,
    deletePesanan,
    downloadPO,
    showModal,
    hideModal
};
