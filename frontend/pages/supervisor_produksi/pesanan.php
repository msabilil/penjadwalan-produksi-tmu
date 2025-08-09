<?php
/**
 * Supervisor Produksi - Pesanan Page
 * Order management page for supervisor produksi role with estimation and scheduling focus
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['supervisor produksi']);

// Set page variables
$page_title = 'Pesanan - Supervisor Produksi';

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/jadwal_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Handle search and filters
$search_keyword = $_GET['search'] ?? '';
$filter_estimasi = $_GET['filter_estimasi'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// Pagination parameters
$current_page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Build filters for query
$filters = [];
if (!empty($search_keyword)) {
    $filters['search'] = $search_keyword;
}
if (!empty($filter_start_date)) {
    $filters['tanggal_mulai'] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $filters['tanggal_akhir'] = $filter_end_date;
}

// Get orders with pagination
$pesanan_result = ambil_semua_pesanan($per_page, $offset);
$pesanan_list = $pesanan_result['success'] ? $pesanan_result['data'] : [];

// Get total count for pagination
$total_records = hitung_total_pesanan();  // Fungsi ini return integer langsung

// Enrich data with estimation and schedule status
foreach ($pesanan_list as &$pesanan) {
    // Check if estimation exists
    $pesanan['estimasi_status'] = 'belum';
    $pesanan['jadwal_status'] = 'belum';
    
    // Cek status estimasi
    $estimasi_result = ambil_estimasi_by_pesanan($pesanan['id_pesanan']);
    if ($estimasi_result['success'] && !empty($estimasi_result['data'])) {
        $pesanan['estimasi_status'] = 'ada';
        $pesanan['id_estimasi'] = $estimasi_result['data']['id_estimasi'];
        $pesanan['estimasi_data'] = $estimasi_result['data'];
        $pesanan['waktu_estimasi'] = $estimasi_result['data']['waktu_hari'] ?? 0;
    }
    
    // Cek status jadwal (implementasi sederhana)
    if ($pesanan['estimasi_status'] === 'ada') {
        // Cek apakah ada jadwal produksi untuk estimasi ini
        // Untuk sementara, anggap belum ada jadwal (bisa ditambahkan nanti)
        $pesanan['jadwal_status'] = 'belum';
        
        // TODO: Implementasi pengecekan jadwal produksi
        // $jadwal_result = ambil_jadwal_by_estimasi($pesanan['id_estimasi']);
        // if ($jadwal_result['success'] && !empty($jadwal_result['data'])) {
        //     $pesanan['jadwal_status'] = 'ada';
        //     $pesanan['id_jadwal'] = $jadwal_result['data']['id_jadwal'];
        //     $pesanan['jadwal_data'] = $jadwal_result['data'];
        // }
    }
}

// Start output buffering
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Pesanan</h1>
        <p class="text-gray-600">Kelola estimasi dan jadwal produksi untuk pesanan yang masuk.</p>
    </div>

    <!-- Quick Actions -->
    <div class="mb-6">
        <div class="flex flex-wrap gap-4">
            <a href="estimasi.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3-3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Kelola Estimasi
            </a>
            
            <a href="jadwal.php" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Kelola Jadwal
            </a>
        </div>
    </div>

    <!-- Orders Table Component -->
    <?php include '../../components/supervisor_produksi/pesanan_table.php'; ?>
</div>

<script>
function refreshData() {
    window.location.reload();
}

// Success/Error messages handling
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['success'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?= htmlspecialchars($_GET['success']) ?>',
            icon: 'success',
            confirmButtonColor: '#16a34a'
        });
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?= htmlspecialchars($_GET['error']) ?>',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    <?php endif; ?>
});
</script>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>
