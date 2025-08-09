/**
 * Manager Penerbit - Pesanan Page JavaScript
 * Handles modal interactions, form validations, and user interactions
 */

// Modal functions - Enhanced version
function showModal(modalId) {
    console.log('Showing modal:', modalId); // Debug log
    const modal = document.getElementById(modalId);
    
    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }
    
    modal.classList.remove('hidden');
    console.log('Modal classes after remove hidden:', modal.className); // Debug log
    
    // Add focus trap for accessibility
    const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
    if (firstInput) {
        setTimeout(() => {
            firstInput.focus();
            console.log('Focused on:', firstInput); // Debug log
        }, 100);
    }
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function hideModal(modalId) {
    console.log('Hiding modal:', modalId); // Debug log
    const modal = document.getElementById(modalId);
    
    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }
    
    modal.classList.add('hidden');
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Clear any form if it's a form modal
    const form = modal.querySelector('form');
    if (form && (modalId === 'pesananModal' || modalId === 'filterModal')) {
        // Ask for confirmation if form has been modified
        const formData = new FormData(form);
        let hasData = false;
        for (let [key, value] of formData.entries()) {
            if (value && !['action', 'id_pesanan'].includes(key)) {
                hasData = true;
                break;
            }
        }
        
        if (hasData && modalId === 'pesananModal') {
            if (!confirm('Form telah diisi. Apakah Anda yakin ingin menutup tanpa menyimpan?')) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                return;
            }
        }
        
        // Clean up any dynamically added nomor field
        const visibleNomorDiv = document.getElementById('no_visible');
        if (visibleNomorDiv) {
            visibleNomorDiv.closest('div').remove();
        }
        
        // Reset form
        form.reset();
    }
}

function openAddModal() {
    console.log('Opening add modal'); // Debug log
    
    document.getElementById('modalTitle').textContent = 'Tambah Pesanan';
    document.getElementById('formAction').value = 'tambah_pesanan';
    document.getElementById('submitText').textContent = 'Simpan';
    document.getElementById('pesananForm').reset();
    document.getElementById('tanggal_pesanan').value = new Date().toISOString().split('T')[0];
    document.getElementById('id_pesanan').value = '';
    document.getElementById('deskripsi').value = '';
    
    // Clear hidden nomor field - akan di-generate otomatis
    document.getElementById('no').value = '';
    
    // Show auto-generate info box
    const autoGenerateInfo = document.getElementById('autoGenerateInfo');
    if (autoGenerateInfo) {
        autoGenerateInfo.style.display = 'block';
    }
    
    // Remove visible nomor field if exists (from edit mode)
    const visibleNomorDiv = document.getElementById('no_visible');
    if (visibleNomorDiv) {
        visibleNomorDiv.closest('div').remove();
    }
    
    showModal('pesananModal');
}

function editPesanan(id) {
    console.log('Edit pesanan called with ID:', id); // Debug log
    
    // Get pesanan data from the global variable that should be set by PHP
    if (typeof window.pesananData === 'undefined') {
        console.error('Pesanan data not found in window.pesananData');
        Swal.fire({
            title: 'Error!',
            text: 'Data pesanan tidak tersedia. Silakan refresh halaman.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    const pesananData = window.pesananData;
    console.log('Pesanan data:', pesananData); // Debug log
    
    const pesanan = pesananData.find(p => p.id_pesanan == id);
    console.log('Found pesanan:', pesanan); // Debug log
    
    if (!pesanan) {
        Swal.fire({
            title: 'Error!',
            text: 'Data pesanan tidak ditemukan',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    // Reset form first
    document.getElementById('pesananForm').reset();
    
    // Set modal title and action
    document.getElementById('modalTitle').textContent = 'Edit Pesanan';
    document.getElementById('formAction').value = 'update_pesanan';
    document.getElementById('submitText').textContent = 'Update';
    document.getElementById('id_pesanan').value = id;
    
    // Hide auto-generate info box for edit mode
    const autoGenerateInfo = document.getElementById('autoGenerateInfo');
    if (autoGenerateInfo) {
        autoGenerateInfo.style.display = 'none';
    }
    
    // Remove any existing visible nomor field
    const existingNomorVisible = document.getElementById('no_visible');
    if (existingNomorVisible) {
        existingNomorVisible.closest('div').remove();
    }
    
    // Create a visible nomor field for editing
    const nomorDiv = document.createElement('div');
    nomorDiv.innerHTML = `
        <label class="form-label">No. Pesanan <span class="text-red-500">*</span></label>
        <input type="text" id="no_visible" class="form-input" value="${pesanan.no || ''}" required>
        <p class="text-sm text-gray-500 mt-1">Ubah nomor pesanan jika diperlukan</p>
    `;
    
    // Insert before design field
    const modalBody = document.querySelector('#pesananModal .modal-body .space-y-4');
    modalBody.insertBefore(nomorDiv, modalBody.firstChild);
    
    // Sync visible field with hidden field
    document.getElementById('no_visible').addEventListener('input', function() {
        document.getElementById('no').value = this.value;
    });
    
    // Fill form with pesanan data
    document.getElementById('no').value = pesanan.no || '';
    document.getElementById('id_desain').value = pesanan.id_desain || '';
    document.getElementById('nama_pemesan').value = pesanan.nama_pemesan || '';
    document.getElementById('no_telepon').value = pesanan.no_telepon || '';
    document.getElementById('alamat').value = pesanan.alamat || '';
    document.getElementById('jumlah').value = pesanan.jumlah || '';
    document.getElementById('tanggal_pesanan').value = pesanan.tanggal_pesanan || '';
    document.getElementById('deskripsi').value = pesanan.deskripsi || '';
    
    // Show the modal
    showModal('pesananModal');
}

function deletePesanan(id) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Apakah Anda yakin ingin menghapus pesanan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="hapus_pesanan">
                <input type="hidden" name="id_pesanan" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function downloadPO(id) {
    window.open(`download_file.php?type=po&id=${id}`, '_blank');
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#pesananTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Auto-fill customer data when selected from datalist
function initializeCustomerAutoFill() {
    const namaPemesanInput = document.getElementById('nama_pemesan');
    if (namaPemesanInput) {
        namaPemesanInput.addEventListener('input', function() {
            const selectedName = this.value;
            const datalist = document.getElementById('pelangganList');
            const options = datalist.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === selectedName) {
                    // Auto-fill phone and address
                    document.getElementById('no_telepon').value = option.getAttribute('data-phone') || '';
                    document.getElementById('alamat').value = option.getAttribute('data-address') || '';
                }
            });
        });
    }
}

// Form submission handler for auto-generate feedback
function initializeFormSubmission() {
    const pesananForm = document.getElementById('pesananForm');
    if (pesananForm) {
        pesananForm.addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const formAction = document.getElementById('formAction').value;
            
            // Show auto-generate feedback for new orders (tambah_pesanan)
            if (formAction === 'tambah_pesanan') {
                // Update button text to show processing
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-magic mr-2"></i>Membuat nomor otomatis & menyimpan...';
                submitBtn.disabled = true;
                
                // Re-enable button after short delay (form will submit)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 1500);
            }
        });
    }
}

// Enhanced modal interactions
function initializeModalInteractions() {
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal:not(.hidden)');
            openModals.forEach(modal => {
                hideModal(modal.id);
            });
        }
    });
    
    // Close modal when clicking backdrop
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && !e.target.classList.contains('hidden')) {
            hideModal(e.target.id);
        }
    });
    
    // Prevent modal content clicks from bubbling to backdrop
    document.querySelectorAll('.modal-content').forEach(content => {
        content.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// Success/Error Message Functions
function showSuccessMessage(message) {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#16a34a',
        confirmButtonText: '<i class="fas fa-check"></i> OK',
        customClass: {
            confirmButton: 'btn-primary'
        },
        buttonsStyling: false
    });
}

function showErrorMessage(message) {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        confirmButtonColor: '#dc2626',
        confirmButtonText: '<i class="fas fa-times"></i> OK',
        customClass: {
            confirmButton: 'btn-danger'
        },
        buttonsStyling: false
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manager Penerbit Pesanan page loaded');
    
    // Initialize all functionality
    initializeSearch();
    initializeCustomerAutoFill();
    initializeFormSubmission();
    initializeModalInteractions();
    
    // Show SweetAlert messages if set from PHP
    if (typeof window.swalSuccess !== 'undefined' && window.swalSuccess) {
        showSuccessMessage(window.swalSuccess);
        // Clean URL after showing message
        if (window.location.search.includes('success=')) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
    
    if (typeof window.swalError !== 'undefined' && window.swalError) {
        showErrorMessage(window.swalError);
        // Clean URL after showing message
        if (window.location.search.includes('error=')) {
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
    
    // Debug: Log pesanan data on page load if available
    if (typeof window.pesananData !== 'undefined') {
        console.log('Pesanan data loaded:', window.pesananData);
        console.log('Number of pesanan records:', window.pesananData.length);
    }
    
    // Debug: Test if edit buttons exist
    const editButtons = document.querySelectorAll('button[onclick*="editPesanan"]');
    console.log('Found edit buttons:', editButtons.length);
    
    // Add click event listeners to edit buttons for debugging
    editButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            console.log('Edit button clicked:', index, this.getAttribute('onclick'));
        });
    });
});
