<?php
require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['supervisor produksi']);

// Get filter parameters from URL; default to current month/year
$filter_bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? intval($_GET['bulan']) : intval(date('n'));
$filter_tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? intval($_GET['tahun']) : intval(date('Y'));

// Siapkan bulan/tahun yang akan ditampilkan (bisa fallback)
$display_bulan = $filter_bulan;
$display_tahun = $filter_tahun;
$using_fallback = false;
$fallback_info = '';

// Ambil data untuk bulan/tahun yang dipilih
$filtered_result = ambil_estimasi_by_filter($display_bulan, $display_tahun);
$filtered_data = $filtered_result['success'] ? ($filtered_result['data'] ?? []) : [];

// Jika kosong, fallback ke bulan/tahun terakhir yang memiliki data
if (empty($filtered_data)) {
    $last = ambil_bulan_tahun_terakhir_ada_estimasi();
    if ($last['success']) {
        $display_bulan = (int)$last['data']['bulan'];
        $display_tahun = (int)$last['data']['tahun'];
        $filtered_result = ambil_estimasi_by_filter($display_bulan, $display_tahun);
        $filtered_data = $filtered_result['success'] ? ($filtered_result['data'] ?? []) : [];
        if (!empty($filtered_data)) {
            $using_fallback = true;
            $nama_bulan_map = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            $fallback_info = 'Tidak ada data untuk bulan yang dipilih. Menampilkan ' . $nama_bulan_map[$display_bulan] . ' ' . $display_tahun . ' (terbaru yang memiliki data).';
        }
    }
}

// Set page variables
$page_title = "Gantt Chart Estimasi";

// Start output buffering for page content
ob_start();
?>

<!-- Custom CSS for Gantt Chart -->
<link rel="stylesheet" href="../../assets/css/pages/supervisor_produksi/gantt_chart.css">

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gantt Chart Estimasi</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Visualisasi timeline estimasi produksi berdasarkan perhitungan sistem
                    <?php if ($display_bulan != intval(date('n')) || $display_tahun != intval(date('Y'))): ?>
                        <br>
                        <span class="text-blue-600 font-medium">
                            <?php
                            $nama_bulan = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            ?>
                            Menampilkan: <?= $nama_bulan[$display_bulan] ?> <?= $display_tahun ?>
                        </span>
                    <?php endif; ?>
                </p>
                <?php if ($using_fallback && $fallback_info): ?>
                <div class="mt-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($fallback_info) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                <button onclick="window.print()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>
                    Cetak
                </button>
                
                <button onclick="refreshChart()" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <?php
        // Gunakan data hasil display (setelah fallback jika ada)
        $stats = [
            'total_estimasi' => count($filtered_data),
            'estimasi_pending' => count($filtered_data), // sesuaikan jika ada kolom status
            'estimasi_progress' => 0,
            'estimasi_selesai' => 0
        ];
        ?>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-chart-gantt text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">
                        Total Estimasi
                        <span class="text-xs text-blue-600">
                            <?= ($display_bulan == intval(date('n')) && $display_tahun == intval(date('Y'))) ? '(Bulan ini)' : '(Filtered)' ?>
                        </span>
                    </p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_estimasi']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['estimasi_pending']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-play text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Progress</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['estimasi_progress']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg">
                    <i class="fas fa-check text-gray-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Selesai</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['estimasi_selesai']) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Options -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2">
                <label for="filter_bulan" class="text-sm font-medium text-gray-700">Bulan:</label>
                <select id="filter_bulan" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="1" <?= $display_bulan == 1 ? 'selected' : '' ?>>Januari</option>
                    <option value="2" <?= $display_bulan == 2 ? 'selected' : '' ?>>Februari</option>
                    <option value="3" <?= $display_bulan == 3 ? 'selected' : '' ?>>Maret</option>
                    <option value="4" <?= $display_bulan == 4 ? 'selected' : '' ?>>April</option>
                    <option value="5" <?= $display_bulan == 5 ? 'selected' : '' ?>>Mei</option>
                    <option value="6" <?= $display_bulan == 6 ? 'selected' : '' ?>>Juni</option>
                    <option value="7" <?= $display_bulan == 7 ? 'selected' : '' ?>>Juli</option>
                    <option value="8" <?= $display_bulan == 8 ? 'selected' : '' ?>>Agustus</option>
                    <option value="9" <?= $display_bulan == 9 ? 'selected' : '' ?>>September</option>
                    <option value="10" <?= $display_bulan == 10 ? 'selected' : '' ?>>Oktober</option>
                    <option value="11" <?= $display_bulan == 11 ? 'selected' : '' ?>>November</option>
                    <option value="12" <?= $display_bulan == 12 ? 'selected' : '' ?>>Desember</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label for="filter_tahun" class="text-sm font-medium text-gray-700">Tahun:</label>
                <select id="filter_tahun" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="2024" <?= $display_tahun == 2024 ? 'selected' : '' ?>>2024</option>
                    <option value="2025" <?= $display_tahun == 2025 ? 'selected' : '' ?>>2025</option>
                </select>
            </div>
            
            <button onclick="applyFilters()" 
                    class="px-4 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors">
                <i class="fas fa-filter mr-1"></i>
                Terapkan Filter
            </button>
            
            <?php if ($filter_bulan != intval(date('n')) || $filter_tahun != intval(date('Y'))): ?>
            <button onclick="clearFilters()" 
                    class="px-4 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition-colors">
                <i class="fas fa-times mr-1"></i>
                Hapus Filter
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Gantt Chart Component -->
    <?php 
    // Pass filter parameters to the component (gunakan display values)
    $GLOBALS['filter_bulan'] = $display_bulan;
    $GLOBALS['filter_tahun'] = $display_tahun;
    include '../../components/supervisor_produksi/gantt_chart.php'; 
    ?>
    
    <!-- Print Instructions -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
            <div>
                <h4 class="text-sm font-medium text-blue-900">Petunjuk Cetak</h4>
                <p class="text-sm text-blue-700 mt-1">
                    Untuk hasil cetak terbaik, gunakan orientasi landscape dan atur margin ke minimum.
                    Gantt chart akan otomatis menyesuaikan dengan ukuran kertas.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        font-size: 10px;
    }
    
    .gantt-container {
        overflow: visible;
    }
    
    .gantt-table {
        font-size: 8px;
    }
    
    .p-6 {
        padding: 1rem;
    }
    
    /* Hide sidebar during print */
    .sidebar {
        display: none;
    }
    
    .main-content {
        margin-left: 0;
    }
}
</style>

<script>
function refreshChart() {
    Swal.fire({
        title: 'Memuat ulang...',
        text: 'Sedang memperbarui data Gantt Chart',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Reload page after short delay
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function applyFilters() {
    const bulan = document.getElementById('filter_bulan').value;
    const tahun = document.getElementById('filter_tahun').value;
    
    let url = 'gantt_chart_estimasi.php';
    const params = [];
    
    // Kirim bulan (1-12) dan tahun
    if (bulan !== '') params.push('bulan=' + encodeURIComponent(bulan));
    if (tahun !== '') params.push('tahun=' + encodeURIComponent(tahun));
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    window.location.href = url;
}

function clearFilters() {
    // Kembali ke default: bulan & tahun saat ini
    window.location.href = 'gantt_chart_estimasi.php';
}

// Auto-refresh every 5 minutes
setInterval(() => {
    if (!document.hidden) {
        refreshChart();
    }
}, 300000); // 5 minutes

document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to gantt bars
    const ganttBars = document.querySelectorAll('.gantt-bar, .bg-yellow-400');
    ganttBars.forEach(bar => {
        bar.title = 'Klik untuk detail estimasi';
        bar.style.cursor = 'pointer';
    });
    
    // Smooth scroll for horizontal scrolling
    const ganttContainer = document.querySelector('.overflow-x-auto');
    if (ganttContainer) {
        ganttContainer.style.scrollBehavior = 'smooth';
    }
});
</script>

<?php
$page_content = ob_get_clean();
include '../../layouts/sidebar_supervisor_produksi.php';
?>
