/**
 * Data Mesin Page JavaScript
 * Handles modal interactions, form validations, and user interactions
 */

// Modal Functions
function bukaFormTambah() {
    document.getElementById('formOverlay').classList.add('active');
    
    // Reset form
    document.getElementById('mesinForm').reset();
    
    // Set form fields
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('editIdMesin').value = '';
    document.getElementById('formTitle').textContent = 'Tambah Mesin';
    document.getElementById('submitText').textContent = 'Simpan Mesin';
    
    // Set default value for menit operasional
    document.getElementById('tambah_menit_operasional').value = '480';
    
    // Focus pada field pertama
    setTimeout(() => {
        document.getElementById('tambah_nama_mesin').focus();
    }, 100);
}

function bukaFormEdit(mesin) {
    document.getElementById('formOverlay').classList.add('active');
    
    // Set form fields
    document.getElementById('formAction').value = 'edit';
    document.getElementById('editIdMesin').value = mesin.id_mesin;
    document.getElementById('formTitle').textContent = 'Edit Mesin';
    document.getElementById('submitText').textContent = 'Update Mesin';
    
    // Fill form data
    document.getElementById('tambah_nama_mesin').value = mesin.nama_mesin;
    document.getElementById('tambah_urutan_proses').value = mesin.urutan_proses;
    document.getElementById('tambah_kapasitas').value = mesin.kapasitas;
    document.getElementById('tambah_waktu_setup').value = mesin.waktu_setup;
    document.getElementById('tambah_waktu_mesin_per_eks').value = mesin.waktu_mesin_per_eks;
    document.getElementById('tambah_menit_operasional').value = mesin.menit_operasional;
    
    // Focus pada field pertama
    setTimeout(() => {
        document.getElementById('tambah_nama_mesin').focus();
    }, 100);
}

function tutupForm() {
    document.getElementById('formOverlay').classList.remove('active');
    
    // Reset form setelah animasi selesai
    setTimeout(() => {
        document.getElementById('mesinForm').reset();
    }, 300);
}

function konfirmasiHapus(idMesin, namaMesin) {
    Swal.fire({
        title: 'Konfirmasi Hapus Mesin',
        text: `Apakah Anda yakin ingin menghapus mesin "${namaMesin}"? Tindakan ini tidak dapat dibatalkan`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        allowOutsideClick: true,
        allowEscapeKey: true,
        focusCancel: false,
        focusConfirm: false,
        customClass: {
            popup: 'animate-fade-in'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // User clicked "Ya, Hapus!"
            document.getElementById('deleteIdMesin').value = idMesin;
            document.getElementById('deleteForm').submit();
        } else {
            // User clicked "Batal", pressed ESC, clicked outside, or dismissed
            console.log('Delete cancelled by user');
            // Force close any remaining dialogs
            Swal.close();
        }
    }).catch((error) => {
        console.error('SweetAlert error:', error);
        // Force close dialog on error
        Swal.close();
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    const formOverlay = document.getElementById('formOverlay');
    if (formOverlay) {
        formOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                tutupForm();
            }
        });
    }

    // Handle ESC key to close form
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const formOverlay = document.getElementById('formOverlay');
            if (formOverlay && formOverlay.classList.contains('active')) {
                tutupForm();
            }
        }
    });

    // Form submission handler
    const form = document.getElementById('mesinForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validasi form
            if (!validasiForm()) {
                return;
            }
            
            // Show loading
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg class="loading-spinner -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Memproses...';
            submitBtn.disabled = true;
            
            // Submit form after short delay
            setTimeout(() => {
                form.submit();
            }, 500);
        });
    }

    // Check for SweetAlert messages from PHP
    if (typeof window.swalSuccess !== 'undefined') {
        showSuccessMessage(window.swalSuccess);
    }
    
    if (typeof window.swalError !== 'undefined') {
        showErrorMessage(window.swalError);
    }
});

// Success/Error Message Functions
function showSuccessMessage(message) {
    Swal.fire({
        title: 'Berhasil!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: 'animate-fade-in'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Simply clean the URL without any complex operations
            if (window.location.search.includes('action=')) {
                window.location.href = window.location.pathname;
            }
        }
    });
}

function showErrorMessage(message) {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'OK',
        customClass: {
            popup: 'animate-fade-in'
        }
    });
}

/**
 * Validasi form sebelum submit
 */
function validasiForm() {
    const form = document.getElementById('mesinForm');
    const formData = new FormData(form);
    
    // Required fields validation
    const requiredFields = [
        { name: 'nama_mesin', label: 'Nama Mesin' },
        { name: 'urutan_proses', label: 'Urutan Proses' },
        { name: 'kapasitas', label: 'Kapasitas' },
        { name: 'waktu_setup', label: 'Waktu Setup' },
        { name: 'waktu_mesin_per_eks', label: 'Waktu per Eksemplar' },
        { name: 'menit_operasional', label: 'Menit Operasional' }
    ];
    
    for (let field of requiredFields) {
        const value = formData.get(field.name);
        if (!value || value.trim() === '') {
            Swal.fire({
                title: 'Validasi Gagal',
                text: `${field.label} harus diisi`,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }
    }
    
    // Numeric validation
    const numericFields = [
        { name: 'urutan_proses', label: 'Urutan Proses', min: 1, max: 8 },
        { name: 'kapasitas', label: 'Kapasitas', min: 1 },
        { name: 'waktu_setup', label: 'Waktu Setup', min: 0 },
        { name: 'waktu_mesin_per_eks', label: 'Waktu per Eksemplar', min: 0 },
        { name: 'menit_operasional', label: 'Menit Operasional', min: 1 }
    ];
    
    for (let field of numericFields) {
        const value = parseFloat(formData.get(field.name));
        if (isNaN(value) || value < field.min) {
            let message = `${field.label} harus berupa angka`;
            if (field.min > 0) {
                message += ` minimal ${field.min}`;
            }
            if (field.max) {
                message += ` dan maksimal ${field.max}`;
            }
            
            Swal.fire({
                title: 'Validasi Gagal',
                text: message,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }
        
        if (field.max && value > field.max) {
            Swal.fire({
                title: 'Validasi Gagal',
                text: `${field.label} tidak boleh lebih dari ${field.max}`,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }
    }
    
    return true;
}
