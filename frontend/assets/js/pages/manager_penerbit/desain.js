/**
 * Manager Penerbit - Data Desain Page JavaScript
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

function showEditModal(desain) {
    // Populate edit form with current data
    document.getElementById('edit_id_desain').value = desain.id_desain;
    document.getElementById('edit_nama').value = desain.nama;
    document.getElementById('edit_jenis_desain').value = desain.jenis_desain;
    document.getElementById('edit_jenis_produk').value = desain.jenis_produk;
    
    // Show current file info if exists
    const currentFileDisplay = document.getElementById('current_file_display');
    if (desain.file_cetak) {
        currentFileDisplay.innerHTML = `File saat ini: ${desain.file_cetak}`;
    } else {
        currentFileDisplay.innerHTML = 'Belum ada file yang diupload';
    }
    
    document.getElementById('edit_model_warna').value = desain.model_warna;
    // Auto-update jumlah warna berdasarkan model warna
    updateJumlahWarna('edit_');
    
    document.getElementById('edit_sisi').value = desain.sisi;
    document.getElementById('edit_halaman').value = desain.halaman;
    document.getElementById('edit_jenis_cover').value = desain.jenis_cover;
    document.getElementById('edit_laminasi').value = desain.laminasi;
    document.getElementById('edit_jilid').value = desain.jilid;
    document.getElementById('edit_kualitas_warna').value = desain.kualitas_warna;
    document.getElementById('edit_ukuran').value = desain.ukuran;
    
    // Auto-update estimasi waktu berdasarkan jenis desain
    updateEstimasiWaktu('edit_');
    
    document.getElementById('edit_tanggal_upload').value = desain.tanggal_selesai || '';
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_nama').focus();
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.add('hidden');
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

function confirmDelete(desainId, desainName) {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded!');
        return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Hapus Desain',
        text: `Apakah Anda yakin ingin menghapus desain "${desainName}"? Tindakan ini tidak dapat dibatalkan`,
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
            document.getElementById('deleteDesainId').value = desainId;
            document.getElementById('deleteForm').submit();
        } else {
            // Force close and remove dialog elements
            Swal.close();
            
            // Force remove SweetAlert DOM elements if they still exist
            setTimeout(() => {
                const swalContainer = document.querySelector('.swal2-container');
                const swalBackdrop = document.querySelector('.swal2-backdrop-show');
                const swalPopup = document.querySelector('.swal2-popup');
                
                if (swalContainer) swalContainer.remove();
                if (swalBackdrop) swalBackdrop.remove();
                if (swalPopup) swalPopup.remove();
                
                // Remove body class that might be preventing scroll
                document.body.classList.remove('swal2-shown', 'swal2-height-auto');
                document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
            }, 100);
        }
    }).catch((error) => {
        console.error('SweetAlert error:', error);
        // Force close dialog on error and clean up
        Swal.close();
        setTimeout(() => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) swalContainer.remove();
            document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        }, 100);
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
    
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
    
    // Close modals when clicking outside
    const tambahModal = document.getElementById('tambahModal');
    const editModal = document.getElementById('editModal');
    
    if (tambahModal) {
        tambahModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTambahModal();
            }
        });
    }

    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    }

    // Handle ESC key to force close SweetAlert if stuck
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Force close SweetAlert if any is open
            if (Swal.isVisible()) {
                Swal.close();
            }
        }
    });

    // Auto-update jumlah_warna based on model_warna selection
    const modelWarna = document.getElementById('model_warna');
    if (modelWarna) {
        modelWarna.addEventListener('change', function() {
            const jumlahWarnaInput = document.getElementById('jumlah_warna');
            if (jumlahWarnaInput) {
                switch(this.value) {
                    case 'fullcolor':
                        jumlahWarnaInput.value = 4;
                        break;
                    case 'dua warna':
                        jumlahWarnaInput.value = 2;
                        break;
                    case 'b/w':
                        jumlahWarnaInput.value = 1;
                        break;
                    default:
                        jumlahWarnaInput.value = 1;
                }
            }
        });
    }

    const editModelWarna = document.getElementById('edit_model_warna');
    if (editModelWarna) {
        editModelWarna.addEventListener('change', function() {
            const jumlahWarnaInput = document.getElementById('edit_jumlah_warna');
            if (jumlahWarnaInput) {
                switch(this.value) {
                    case 'fullcolor':
                        jumlahWarnaInput.value = 4;
                        break;
                    case 'dua warna':
                        jumlahWarnaInput.value = 2;
                        break;
                    case 'b/w':
                        jumlahWarnaInput.value = 1;
                        break;
                    default:
                        jumlahWarnaInput.value = 1;
                }
            }
        });
    }

    // Auto-set tanggal upload when file is selected
    const fileCetak = document.getElementById('file_cetak');
    if (fileCetak) {
        fileCetak.addEventListener('change', function() {
            if (this.files.length > 0) {
                const today = new Date().toISOString().split('T')[0];
                const tanggalUpload = document.getElementById('tanggal_upload');
                if (tanggalUpload) {
                    tanggalUpload.value = today;
                }
            }
        });
    }

    const editFileCetak = document.getElementById('edit_file_cetak');
    if (editFileCetak) {
        editFileCetak.addEventListener('change', function() {
            if (this.files.length > 0) {
                const today = new Date().toISOString().split('T')[0];
                const editTanggalUpload = document.getElementById('edit_tanggal_upload');
                if (editTanggalUpload) {
                    editTanggalUpload.value = today;
                }
            }
        });
    }

    // Auto-update estimasi waktu based on jenis_desain
    const jenisDesain = document.getElementById('jenis_desain');
    if (jenisDesain) {
        jenisDesain.addEventListener('change', function() {
            const estimasiInput = document.getElementById('estimasi_waktu_desain');
            if (estimasiInput) {
                switch(this.value) {
                    case 'desain default':
                        estimasiInput.value = 0;
                        break;
                    case 'desain sederhana':
                        estimasiInput.value = 3;
                        break;
                    case 'desain kompleks':
                        estimasiInput.value = 10;
                        break;
                    case 'desain premium':
                        estimasiInput.value = 20;
                        break;
                    default:
                        estimasiInput.value = 1;
                }
            }
        });
    }

    const editJenisDesain = document.getElementById('edit_jenis_desain');
    if (editJenisDesain) {
        editJenisDesain.addEventListener('change', function() {
            const estimasiInput = document.getElementById('edit_estimasi_waktu_desain');
            if (estimasiInput) {
                switch(this.value) {
                    case 'desain default':
                        estimasiInput.value = 0;
                        break;
                    case 'desain sederhana':
                        estimasiInput.value = 3;
                        break;
                    case 'desain kompleks':
                        estimasiInput.value = 10;
                        break;
                    case 'desain premium':
                        estimasiInput.value = 20;
                        break;
                    default:
                        estimasiInput.value = 1;
                }
            }
        });
    }

    // Check for SweetAlert messages from PHP
    if (typeof window.swalSuccess !== 'undefined' && window.swalSuccess) {
        showSuccessMessage(window.swalSuccess);
        window.swalSuccess = null; // Clear to prevent duplicate
    }
    
    if (typeof window.swalError !== 'undefined' && window.swalError) {
        showErrorMessage(window.swalError);
        window.swalError = null; // Clear to prevent duplicate
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

// Auto-calculate functions
function updateJumlahWarna(prefix) {
    const modelWarnaSelect = document.getElementById(prefix + 'model_warna');
    const jumlahWarnaInput = document.getElementById(prefix + 'jumlah_warna');
    
    if (modelWarnaSelect && jumlahWarnaInput) {
        const modelWarna = modelWarnaSelect.value;
        let jumlahWarna = 1;
        
        switch(modelWarna) {
            case 'fullcolor':
                jumlahWarna = 4;
                break;
            case 'dua warna':
                jumlahWarna = 2;
                break;
            case 'b/w':
                jumlahWarna = 1;
                break;
            default:
                jumlahWarna = 1;
        }
        
        jumlahWarnaInput.value = jumlahWarna;
    }
}

function updateEstimasiWaktu(prefix) {
    const jenisDesainSelect = document.getElementById(prefix + 'jenis_desain');
    const estimasiWaktuInput = document.getElementById(prefix + 'estimasi_waktu_desain');
    
    if (jenisDesainSelect && estimasiWaktuInput) {
        const jenisDesain = jenisDesainSelect.value;
        let estimasiWaktu = 0;
        
        switch(jenisDesain) {
            case 'desain default':
                estimasiWaktu = 0;
                break;
            case 'desain sederhana':
                estimasiWaktu = 3;
                break;
            case 'desain kompleks':
                estimasiWaktu = 10;
                break;
            case 'desain premium':
                estimasiWaktu = 20;
                break;
            default:
                estimasiWaktu = 0;
        }
        
        estimasiWaktuInput.value = estimasiWaktu;
    }
}