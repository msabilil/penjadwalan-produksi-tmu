<?php
/**
 * Manager Penerbit - Pesanan Page
 * Order management page for manager penerbit role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['manager penerbit']);

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Set page variables
$page_title = 'Laporan Pesanan';
$page_description = 'Lihat dan pantau status pesanan pelanggan serta download Purchase Order';

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
    // Form submissions disabled for manager penerbit - view only access
    header('Location: pesanan.php?error=' . urlencode('Akses ditolak. Manager Penerbit hanya dapat melihat data pesanan.'));
    exit;
}

// Get data for display
$pesanan_result = ambil_semua_pesanan();
$pesanan_list = $pesanan_result['success'] ? $pesanan_result['data'] : [];

// Start output buffering to capture content
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
        <p class="text-gray-600 mt-2"><?= $page_description ?></p>
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
                                    <div class="action-buttons">
                                        <?php $pesanan_id = $pesanan['id_pesanan'] ?? 0; ?>
                                        <button onclick="downloadPO(<?= $pesanan_id ?>)" 
                                                class="btn-success" title="Download PO">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-gray-500 py-8">
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

<script>
// Download Purchase Order function
function downloadPO(id_pesanan) {
    if (id_pesanan) {
        // Open professional PO in new tab for printing/saving
        window.open(`generate_po.php?id=${id_pesanan}`, '_blank');
    } else {
        alert('ID pesanan tidak valid');
    }
}
</script>

<?php
$content = ob_get_clean();

// Add CSS file for this page
$additional_css = ['assets/css/pages/staf_penjualan/pesanan.css'];

// Add JavaScript file for this page - view only version  
$additional_js = [];

// Set SweetAlert messages using the layout system
if ($swal_success) {
    $swal_success = $swal_success;
}
if ($swal_error) {
    $swal_error = $swal_error;
}

// Include layout
include '../../layouts/sidebar_manager_penerbit.php';
?>
