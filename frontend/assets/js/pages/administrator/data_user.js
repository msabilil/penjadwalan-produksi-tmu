/**
 * Data User Page JavaScript
 * Handles modal interactions, form validations, and user interactions
 */

// Modal Functions
function showTambahModal() {
    const modal = document.getElementById('tambahModal');
    if (modal) {
        modal.classList.remove('hidden');
        const namaInput = document.getElementById('nama');
        if (namaInput) {
            namaInput.focus();
        }
    }
}

function closeTambahModal() {
    const modal = document.getElementById('tambahModal');
    if (modal) {
        modal.classList.add('hidden');
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

function confirmDelete(userId, userName) {
    if (typeof Swal === 'undefined') {
        return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Hapus User',
        text: `Apakah Anda yakin ingin menghapus user "${userName}"? Tindakan ini tidak dapat dibatalkan`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteForm').submit();
        }
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Global ESC key handler for SweetAlert cleanup
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (typeof Swal !== 'undefined') {
                Swal.close();
                setTimeout(() => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) swalContainer.remove();
                    document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                    document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
                }, 50);
            }
        }
    });
    
    // Close modal when clicking outside
    const tambahModal = document.getElementById('tambahModal');
    if (tambahModal) {
        tambahModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTambahModal();
            }
        });
    }
});
