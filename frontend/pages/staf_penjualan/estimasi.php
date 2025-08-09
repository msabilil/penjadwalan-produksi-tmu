<?php
/**
 * Staf Penjualan - Estimasi Page
 * Simple estimation overview page for staf penjualan role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['staf penjualan']);

// Include required functions
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Set page variables
$page_title = 'Estimasi Produksi';
$page_description = 'Lihat status estimasi untuk pesanan yang telah dibuat';

// Handle search and filters
$search_keyword = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// Pagination parameters
$current_page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Get all estimasi with pesanan data
$estimasi_result = ambil_semua_estimasi($per_page, $offset);
$estimasi_list = $estimasi_result['success'] ? $estimasi_result['data'] : [];

// Apply filters if needed
if (!empty($search_keyword)) {
    $estimasi_list = array_filter($estimasi_list, function($estimasi) use ($search_keyword) {
        return stripos($estimasi['nama_pemesan'] ?? '', $search_keyword) !== false ||
               stripos($estimasi['no_pesanan'] ?? '', $search_keyword) !== false ||
               stripos($estimasi['nama_desain'] ?? '', $search_keyword) !== false;
    });
}

// Get total count for pagination
$total_records = count($estimasi_list);

// Success/Error messages
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Start output buffering
ob_start();
?>

<link rel="stylesheet" href="../../assets/css/pages/staf_penjualan/estimasi.css">

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $page_title ?></h1>
        <p class="text-gray-600"><?= $page_description ?></p>
    </div>

    <!-- Search and Filter -->
    <div class="mb-6 search-filter-bar">
        <div class="flex flex-col sm:flex-row gap-4 search-filter-content">
            <!-- Search -->
            <div class="flex-1 search-input">
                <div class="relative">
                    <div class="search-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           id="searchInput"
                           class="block w-full pr-3 py-2 leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 sm:text-sm" 
                           placeholder="Cari nomor pesanan, nama pemesan, atau desain..."
                           value="<?= htmlspecialchars($search_keyword) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Estimasi Table -->
    <div class="estimasi-table">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Daftar Estimasi</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="estimasiTable">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Pesanan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemesan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Desain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Estimasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($estimasi_list)): ?>
                        <tr>
                            <td colspan="6" class="table-cell text-center text-gray-500">
                                <div class="empty-state">
                                    <i class="fas fa-calculator empty-state-icon"></i>
                                    <p class="empty-state-title">Belum ada estimasi</p>
                                    <p class="empty-state-description">Estimasi akan muncul setelah pesanan diproses oleh supervisor produksi</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estimasi_list as $estimasi): ?>
                            <tr class="table-row">
                                <td class="table-cell font-medium text-blue-600">
                                    <?= htmlspecialchars($estimasi['no_pesanan'] ?? $estimasi['no'] ?? 'N/A') ?>
                                </td>
                                <td class="table-cell text-gray-900">
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($estimasi['nama_pemesan'] ?? 'N/A') ?></div>
                                        <div class="text-gray-500 text-xs"><?= htmlspecialchars($estimasi['no_telepon'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td class="table-cell text-gray-900">
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($estimasi['nama_desain'] ?? $estimasi['nama'] ?? 'Belum Ada') ?></div>
                                        <div class="text-gray-500 text-xs"><?= htmlspecialchars($estimasi['jenis_produk'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td class="table-cell text-gray-900">
                                    <?= number_format($estimasi['jumlah_pesanan'] ?? $estimasi['jumlah'] ?? 0) ?> unit
                                </td>
                                <td class="table-cell text-gray-900">
                                    <?php if (!empty($estimasi['waktu_hari']) && $estimasi['waktu_hari'] > 0): ?>
                                        <div class="font-medium text-green-600"><?= number_format($estimasi['waktu_hari'], 1) ?> hari</div>
                                        <div class="text-xs text-gray-500"><?= number_format($estimasi['waktu_jam'] ?? 0, 1) ?> jam</div>
                                    <?php else: ?>
                                        <span class="text-yellow-600 font-medium">Belum tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-cell text-gray-900">
                                    <?= isset($estimasi['tanggal_estimasi']) ? format_tanggal($estimasi['tanggal_estimasi']) : 
                                        (isset($estimasi['tanggal_pesanan']) ? format_tanggal($estimasi['tanggal_pesanan']) : 'N/A') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#estimasiTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isMatch = text.includes(searchTerm);
        row.style.display = isMatch ? '' : 'none';
        
        // Don't hide the "no data" row
        if (row.cells.length === 1 && row.cells[0].getAttribute('colspan')) {
            row.style.display = '';
        }
    });
});
</script>

<?php
$content = ob_get_clean();

// Include layout
include '../../layouts/sidebar_staf_penjualan.php';
?>
