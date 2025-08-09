<?php
/**
 * Staf Penjualan - Pesanan Page
 * Order management page for staf penjualan role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['staf penjualan']);

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Set page variables
$page_title = 'Pesanan';
$page_description = 'Kelola pesanan pelanggan, tambah pesanan baru, dan download Purchase Order';

// Handle form submissions
$success_message = '';
$error_message = '';
$swal_success = '';
$swal_error = '';

// Check for success message from redirect
if (isset($_GET['success'])) {
    $swal_success = $_GET['success'];
}

// Check for error message from redirect
if (isset($_GET['error'])) {
    $swal_error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'tambah_pesanan':
            // Auto-generate nomor pesanan jika kosong
            if (empty($_POST['no'])) {
                $_POST['no'] = generate_nomor_pesanan();
            }
            
            // Get user ID from session with fallback
            $id_user = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? null;
            
            if (!$id_user) {
                // Redirect with error message
                header('Location: pesanan.php?error=' . urlencode('Session user tidak valid. Silakan login ulang.'));
                exit;
            }
            
            $data = [
                'no' => sanitize_input($_POST['no']),
                'id_desain' => !empty($_POST['id_desain']) ? (int)$_POST['id_desain'] : null,
                'id_user' => (int)$id_user,
                'nama_pemesan' => sanitize_input($_POST['nama_pemesan']),
                'no_telepon' => sanitize_input($_POST['no_telepon']),
                'alamat' => sanitize_input($_POST['alamat']),
                'jumlah' => (int)$_POST['jumlah'],
                'tanggal_pesanan' => $_POST['tanggal_pesanan'],
                'deskripsi' => sanitize_input($_POST['deskripsi'] ?? '')
            ];
            
            // Note: Design requirements are handled in the design management module
            // This form only creates the order, design details will be managed separately
            
            $result = tambah_pesanan($data);
            if ($result['success']) {
                $nomor_pesanan = $data['no']; // Nomor yang sudah di-generate atau input manual
                if (empty($data['id_desain'])) {
                    $success_msg = 'Pesanan berhasil ditambahkan dengan nomor: ' . $nomor_pesanan . '! Tetapi desain belum dipilih.';
                } else {
                    $success_msg = 'Pesanan berhasil ditambahkan dengan nomor: ' . $nomor_pesanan . '!';
                }
                // Redirect to prevent form resubmission
                header('Location: pesanan.php?success=' . urlencode($success_msg));
                exit;
            } else {
                // Redirect with error message
                header('Location: pesanan.php?error=' . urlencode($result['message']));
                exit;
            }
            break;
            
        case 'update_pesanan':
            // Get user ID from session with fallback for update
            $id_user = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? null;
            
            if (!$id_user) {
                // Redirect with error message
                header('Location: pesanan.php?error=' . urlencode('Session user tidak valid. Silakan login ulang.'));
                exit;
            }
            
            $data = [
                'no' => sanitize_input($_POST['no']),
                'id_desain' => !empty($_POST['id_desain']) ? (int)$_POST['id_desain'] : null,
                'id_user' => (int)$id_user,
                'nama_pemesan' => sanitize_input($_POST['nama_pemesan']),
                'no_telepon' => sanitize_input($_POST['no_telepon']),
                'alamat' => sanitize_input($_POST['alamat']),
                'jumlah' => (int)$_POST['jumlah'],
                'tanggal_pesanan' => $_POST['tanggal_pesanan'],
                'deskripsi' => sanitize_input($_POST['deskripsi'] ?? '')
            ];
            
            // Note: Design requirements are handled in the design management module
            
            $result = update_pesanan($_POST['id_pesanan'], $data);
            if ($result['success']) {
                // Redirect to prevent form resubmission
                header('Location: pesanan.php?success=' . urlencode('Pesanan berhasil diperbarui!'));
                exit;
            } else {
                // Redirect with error message
                header('Location: pesanan.php?error=' . urlencode($result['message']));
                exit;
            }
            break;
            
        case 'hapus_pesanan':
            $result = hapus_pesanan($_POST['id_pesanan']);
            if ($result['success']) {
                // Redirect to prevent form resubmission
                header('Location: pesanan.php?success=' . urlencode('Pesanan berhasil dihapus!'));
                exit;
            } else {
                // Redirect with error message
                header('Location: pesanan.php?error=' . urlencode($result['message']));
                exit;
            }
            break;
    }
}

// Get data for display
$pesanan_result = ambil_semua_pesanan();
$pesanan_list = $pesanan_result['success'] ? $pesanan_result['data'] : [];

$desain_result = ambil_semua_desain();
$desain_list = $desain_result['success'] ? $desain_result['data'] : [];

// Ensure $desain_list is always an array
if (!is_array($desain_list)) {
    $desain_list = [];
}

// Start output buffering to capture content
ob_start();
?>

<link rel="stylesheet" href="../../assets/css/pages/staf_penjualan/pesanan.css">

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
        <p class="text-gray-600 mt-2"><?= $page_description ?></p>
    </div>
    
    <!-- Add Order Button -->
    <div class="mb-6">
        <button type="button" 
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center space-x-2 shadow-sm"
                onclick="openAddModal()">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Tambah Pesanan</span>
        </button>
    </div>
    
    <!-- Search and Filter -->
    <div class="search-container">
        <input type="text" 
               id="searchInput" 
               placeholder="Cari pesanan..." 
               class="search-input">
        
        <!-- <button type="button" 
                onclick="showModal('filterModal')" 
                class="filter-button">
            <i class="fas fa-filter"></i>
            <span>Filter</span>
        </button> -->
    </div>
    
    <!-- Orders Table -->
    <div class="table-container">
        <div class="table-header">
            <h2 class="text-lg font-semibold text-gray-900">Daftar Pesanan</h2>
        </div>
        
        <div class="table-content">
            <table class="data-table" id="pesananTable">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Judul</th>
                        <th>Pemesan</th>
                        <th>No. Telepon</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                        <th>Status Estimasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pesanan_list) && is_array($pesanan_list)): ?>
                        <?php foreach ($pesanan_list as $pesanan): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($pesanan['no'] ?? '') ?></td>
                                <td>
                                    <?php if ($pesanan['id_desain'] && $pesanan['nama_desain']): ?>
                                        <span class="text-green-600 font-medium"><?= htmlspecialchars($pesanan['nama_desain']) ?></span>
                                    <?php else: ?>
                                        <span class="text-orange-600 font-medium">Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($pesanan['nama_pemesan'] ?? '') ?></td>
                                <td><?= htmlspecialchars($pesanan['no_telepon'] ?? '') ?></td>
                                <td><?= number_format($pesanan['jumlah'] ?? 0) ?></td>
                                <td><?= isset($pesanan['tanggal_pesanan']) ? format_tanggal($pesanan['tanggal_pesanan']) : '' ?></td>
                                <td>
                                    <?php
                                    // Cek status estimasi untuk pesanan ini
                                    $estimasi_result = ambil_estimasi_by_pesanan($pesanan['id_pesanan']);
                                    if ($estimasi_result['success'] && !empty($estimasi_result['data'])) {
                                        echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Terestimasi</span>';
                                    } else {
                                        echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Belum Estimasi</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php $pesanan_id = $pesanan['id_pesanan'] ?? 0; ?>
                                        <button onclick="editPesanan(<?= $pesanan_id ?>)" 
                                                class="btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="viewDetail(<?= $pesanan_id ?>)" 
                                                class="btn-success" title="Detail Pesanan">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="deletePesanan(<?= $pesanan_id ?>)" 
                                                class="btn-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-gray-500 py-8">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                Belum ada pesanan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="pesananModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah Pesanan</h3>
            <button type="button" onclick="hideModal('pesananModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="pesananForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="tambah_pesanan">
                <input type="hidden" name="id_pesanan" id="id_pesanan">
                
                <!-- Info untuk auto-generate nomor -->
                <div id="autoGenerateInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span class="text-sm text-blue-700">
                            Nomor pesanan akan dibuat otomatis dengan format: <strong>PO/2025/00001</strong>
                        </span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <!-- Nomor pesanan akan di-generate otomatis, tidak perlu ditampilkan -->
                    <input type="hidden" name="no" id="no" value="">
                    
                    <div>
                        <label class="form-label">Desain</label>
                        <select name="id_desain" id="id_desain" class="form-select">
                            <option value="">-- Belum ada desain (kelola di halaman desain) --</option>
                            <?php if (!empty($desain_list)): ?>
                                <?php foreach ($desain_list as $desain): ?>
                                    <option value="<?= $desain['id_desain'] ?? '' ?>">
                                        <?= htmlspecialchars($desain['nama'] ?? 'Nama tidak tersedia') ?> - <?= htmlspecialchars($desain['jenis_produk'] ?? 'Jenis tidak tersedia') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada desain tersedia</option>
                            <?php endif; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Kosongkan jika tidak ada desain yang dipilih</p>
                    </div>
                    
                    <div>
                        <label class="form-label">Nama Pemesan <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_pemesan" id="nama_pemesan" class="form-input" required 
                               list="pelangganList" autocomplete="off"
                               placeholder="Ketik nama pelanggan atau pilih dari daftar">
                        <datalist id="pelangganList">
                            <?php if (!empty($pesanan_list)): ?>
                                <?php
                                $unique_customers = [];
                                foreach ($pesanan_list as $pesanan) {
                                    $key = $pesanan['nama_pemesan'] . '|' . $pesanan['no_telepon'] . '|' . $pesanan['alamat'];
                                    if (!isset($unique_customers[$key])) {
                                        $unique_customers[$key] = $pesanan;
                                    }
                                }
                                ?>
                                <?php foreach ($unique_customers as $customer): ?>
                                    <option value="<?= htmlspecialchars($customer['nama_pemesan']) ?>" 
                                            data-phone="<?= htmlspecialchars($customer['no_telepon'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($customer['alamat'] ?? '') ?>">
                                        <?= htmlspecialchars($customer['nama_pemesan']) ?> - <?= htmlspecialchars($customer['no_telepon'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </datalist>
                    </div>
                    
                    <div>
                        <label class="form-label">No. Telepon <span class="text-red-500">*</span></label>
                        <input type="text" name="no_telepon" id="no_telepon" class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Alamat <span class="text-red-500">*</span></label>
                        <textarea name="alamat" id="alamat" class="form-textarea" required></textarea>
                    </div>
                    
                    <div>
                        <label class="form-label">Jumlah <span class="text-red-500">*</span></label>
                        <input type="number" name="jumlah" id="jumlah" class="form-input" min="1" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Tanggal Pesanan <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_pesanan" id="tanggal_pesanan" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" class="form-textarea" rows="3" placeholder="Catatan atau keterangan tambahan untuk pesanan"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('pesananModal')" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">
                    <span id="submitText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-semibold text-gray-900">Filter Pesanan</h3>
            <button type="button" onclick="hideModal('filterModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="filterForm" method="GET">
            <div class="modal-body">
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-input">
                    </div>
                    
                    <div>
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-input">
                    </div>
                    
                    <div>
                        <label class="form-label">Jenis Produk</label>
                        <select name="jenis_produk" class="form-select">
                            <option value="">Semua Jenis</option>
                            <option value="buku">Buku</option>
                            <option value="majalah">Majalah</option>
                            <option value="katalog">Katalog</option>
                            <option value="kalender">Kalender</option>
                            <option value="soal ujian">Soal Ujian</option>
                            <option value="lembar jawaban ujian">Lembar Jawaban Ujian</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="hideModal('filterModal')" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Terapkan Filter</button>
            </div>
        </form>
    </div>
</div>

<script src="../../assets/js/pages/staf_penjualan/pesanan.js"></script>

<script>
// Modal functions - Enhanced version
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        // Add focus trap for accessibility
        const firstInput = modal.querySelector('input, select, textarea, button');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
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
        }
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Pesanan';
    document.getElementById('formAction').value = 'tambah_pesanan';
    document.getElementById('submitText').textContent = 'Simpan';
    document.getElementById('pesananForm').reset();
    document.getElementById('tanggal_pesanan').value = '<?= date('Y-m-d') ?>';
    document.getElementById('id_pesanan').value = '';
    document.getElementById('deskripsi').value = '';
    
    // Clear hidden nomor field - akan di-generate otomatis
    document.getElementById('no').value = '';
    
    // Show auto-generate info box
    const autoGenerateInfo = document.getElementById('autoGenerateInfo');
    if (autoGenerateInfo) {
        autoGenerateInfo.style.display = 'block';
    }
    
    // Hide nomor field container for add mode
    const nomorFieldContainer = document.getElementById('nomorFieldContainer');
    if (nomorFieldContainer) {
        nomorFieldContainer.style.display = 'none';
    }
    
    showModal('pesananModal');
}

// Handle design field display based on selection
document.addEventListener('DOMContentLoaded', function() {
    const designSelect = document.getElementById('id_desain');
    
    if (designSelect) {
        designSelect.addEventListener('change', function() {
            // Visual feedback for design selection - no additional fields needed
            // Design management will be handled in separate module
        });
    }
});

function editPesanan(id) {
    // Get pesanan data
    const pesananData = <?= json_encode($pesanan_list) ?>;
    const pesanan = pesananData.find(p => p.id_pesanan == id);
    
    if (!pesanan) {
        Swal.fire({
            title: 'Error!',
            text: 'Data pesanan tidak ditemukan',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        return;
    }
    
    // Set modal title and action
    document.getElementById('modalTitle').textContent = 'Edit Pesanan';
    document.getElementById('formAction').value = 'update_pesanan';
    document.getElementById('submitText').textContent = 'Update';
    document.getElementById('id_pesanan').value = id;
    
    // Fill form with pesanan data
    document.getElementById('no').value = pesanan.no || '';
    
    // Hide auto-generate info box for edit mode
    const autoGenerateInfo = document.getElementById('autoGenerateInfo');
    if (autoGenerateInfo) {
        autoGenerateInfo.style.display = 'none';
    }
    
    // Show nomor pesanan field for edit mode
    let nomorFieldContainer = document.getElementById('nomorFieldContainer');
    if (!nomorFieldContainer) {
        // Create nomor field container if it doesn't exist
        nomorFieldContainer = document.createElement('div');
        nomorFieldContainer.id = 'nomorFieldContainer';
        nomorFieldContainer.innerHTML = `
            <label class="form-label">No. Pesanan <span class="text-red-500">*</span></label>
            <input type="text" name="no" class="form-input" value="" required readonly>
            <p class="text-sm text-gray-500 mt-1">Nomor pesanan tidak dapat diubah</p>
        `;
        // Insert before design field
        const designDiv = document.querySelector('select[name="id_desain"]').closest('div');
        designDiv.parentNode.insertBefore(nomorFieldContainer, designDiv);
    }
    
    // Update the visible nomor field
    const visibleNomorField = nomorFieldContainer.querySelector('input[name="no"]');
    visibleNomorField.value = pesanan.no || '';
    nomorFieldContainer.style.display = 'block';
    
    document.getElementById('id_desain').value = pesanan.id_desain || '';
    document.getElementById('nama_pemesan').value = pesanan.nama_pemesan || '';
    document.getElementById('no_telepon').value = pesanan.no_telepon || '';
    document.getElementById('alamat').value = pesanan.alamat || '';
    document.getElementById('jumlah').value = pesanan.jumlah || '';
    document.getElementById('tanggal_pesanan').value = pesanan.tanggal_pesanan || '';
    document.getElementById('deskripsi').value = pesanan.deskripsi || '';
    
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

function viewDetail(id) {
    window.open(`detail_pesanan.php?id=${id}`, '_blank');
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#pesananTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Auto-fill customer data when selected from datalist
document.getElementById('nama_pemesan').addEventListener('input', function() {
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

// Clear auto-filled data when name is manually changed
document.getElementById('nama_pemesan').addEventListener('keydown', function(e) {
    // If user types manually (not selecting from list), clear dependent fields after delay
    setTimeout(() => {
        const selectedName = this.value;
        const datalist = document.getElementById('pelangganList');
        const options = datalist.querySelectorAll('option');
        let found = false;
        
        options.forEach(option => {
            if (option.value === selectedName) {
                found = true;
            }
        });
        
        // If name doesn't match any existing customer, don't clear fields
        // This allows for new customer entry
    }, 100);
});

// Show SweetAlert messages if set
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($swal_success)): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= addslashes($swal_success) ?>',
        icon: 'success',
        confirmButtonColor: '#16a34a'
    }).then(() => {
        // Clean URL after showing message
        if (window.location.search.includes('success=')) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($swal_error)): ?>
    Swal.fire({
        title: 'Error!',
        text: '<?= addslashes($swal_error) ?>',
        icon: 'error',
        confirmButtonColor: '#dc2626'
    }).then(() => {
        // Clean URL after showing message
        if (window.location.search.includes('error=')) {
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    });
    <?php endif; ?>
});

// Form submission handler for auto-generate feedback
document.getElementById('pesananForm').addEventListener('submit', function(e) {
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

// Enhanced modal interactions
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php
$content = ob_get_clean();

// Include layout
include '../../layouts/sidebar_staf_penjualan.php';
?>
