<?php
/**
 * Supervisor Produksi - Jadwal Page
 * Schedule management page for supervisor produksi role
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/jadwal_functions.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/detail_jadwal_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['supervisor produksi']);

// Initialize variables
$success_message = '';
$error_message = '';
$show_detail_modal = false;
$detail_jadwal_data = [];
$selected_jadwal = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah_jadwal':
                $data = [
                    'id_estimasi' => intval($_POST['id_estimasi']),
                    'tanggal_mulai' => $_POST['tanggal_mulai'],
                    'tanggal_selesai' => $_POST['tanggal_selesai'],
                    'batch_ke' => intval($_POST['batch_ke']),
                    'jumlah_batch_ini' => intval($_POST['jumlah_batch_ini']),
                    'status' => 'terjadwal'
                ];
                
                // Tambahkan id_mesin jika dipilih manual, jika tidak akan auto-assign
                if (isset($_POST['id_mesin']) && !empty($_POST['id_mesin'])) {
                    $data['id_mesin'] = intval($_POST['id_mesin']);
                }
                
                $result = tambah_jadwal_produksi($data);
                if ($result['success']) {
                    $success_message = $result['message'];
                    // Auto-generate detail jadwal
                    $detail_result = generate_detail_jadwal($result['data']['id_jadwal']);
                    if (!$detail_result['success']) {
                        $error_message = "Jadwal berhasil dibuat, namun gagal generate detail: " . $detail_result['message'];
                    }
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'update_jadwal':
                $id_jadwal = intval($_POST['id_jadwal']);
                $data = [
                    'tanggal_mulai' => $_POST['tanggal_mulai'],
                    'tanggal_selesai' => $_POST['tanggal_selesai'],
                    'status' => $_POST['status']
                ];
                
                $result = update_jadwal_produksi($id_jadwal, $data);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'hapus_jadwal':
                $id_jadwal = intval($_POST['id_jadwal']);
                $result = hapus_jadwal_produksi($id_jadwal);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'optimasi_spt':
                $tanggal = $_POST['tanggal_optimasi'];
                $result = urutkan_jadwal_harian($tanggal);
                if ($result['success']) {
                    $success_message = "Jadwal berhasil dioptimasi dengan algoritma SPT untuk tanggal " . format_tanggal($tanggal);
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'get_mesin_by_estimasi':
                $id_estimasi = intval($_POST['id_estimasi']);
                $result = ambil_mesin_sesuai_spesifikasi($id_estimasi);
                
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                break;
                
            case 'lihat_detail':
                $id_jadwal = intval($_POST['id_jadwal']);
                $detail_result = ambil_detail_jadwal_by_jadwal($id_jadwal);
                $jadwal_result = ambil_jadwal_by_id($id_jadwal);
                
                if ($detail_result['success'] && $jadwal_result['success']) {
                    $detail_jadwal_data = $detail_result['data'];
                    $selected_jadwal = $jadwal_result['data'];
                    $show_detail_modal = true;
                } else {
                    $error_message = "Gagal mengambil detail jadwal";
                }
                break;
        }
    }
}

// Get data for display
$estimasi_result = ambil_estimasi_siap_jadwal();
$estimasi_list = $estimasi_result['success'] ? $estimasi_result['data'] : [];

// Get current schedules with filters
$filter_tanggal = $_GET['filter_tanggal'] ?? '';  // Ubah default menjadi kosong
$filter_status = $_GET['filter_status'] ?? '';

if (!empty($filter_tanggal)) {
    $jadwal_result = ambil_jadwal_by_tanggal($filter_tanggal);
} else {
    $jadwal_result = ambil_semua_jadwal_simple();
}

$jadwal_list = $jadwal_result['success'] ? $jadwal_result['data'] : [];

// Debug: Log the data retrieval results
log_activity("DEBUG: Filter tanggal = $filter_tanggal, Filter status = $filter_status");
log_activity("DEBUG: Jadwal result success = " . ($jadwal_result['success'] ? 'true' : 'false'));
log_activity("DEBUG: Jadwal list count = " . count($jadwal_list));
if ($jadwal_result['success'] && !empty($jadwal_list)) {
    log_activity("DEBUG: First jadwal data = " . json_encode($jadwal_list[0]));
}

// Get statistics
$stats_result = hitung_statistik_jadwal();
$stats = $stats_result['success'] ? $stats_result['data'] : [
    'total_jadwal' => 0,
    'jadwal_hari_ini' => 0,
    'jadwal_terlambat' => 0,
    'jadwal_selesai' => 0
];

$page_title = 'Jadwal Produksi';

// Start output buffering to capture page content
ob_start();
?>

<!-- Page Content -->
<div class="p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Jadwal Produksi</h1>
            <p class="text-gray-600 mt-2">Kelola jadwal produksi dengan algoritma SPT (Shortest Processing Time)</p>
        </div>

        <!-- Action Buttons -->
                <div class="mb-6 flex flex-wrap gap-4">
                    <button onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Jadwal
                    </button>
                    <!-- <button onclick="openOptimizationModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-sort-amount-down mr-2"></i>
                        Optimasi SPT
                    </button> -->
                    <button onclick="exportSchedule()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        Export Jadwal
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-6 bg-white rounded-lg shadow p-4">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter Tanggal</label>
                            <input type="date" name="filter_tanggal" value="<?= htmlspecialchars($filter_tanggal) ?>" 
                                   class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                   placeholder="Semua tanggal">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter Status</label>
                            <select name="filter_status" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Semua Status</option>
                                <option value="terjadwal" <?= $filter_status === 'terjadwal' ? 'selected' : '' ?>>Terjadwal</option>
                                <option value="dalam proses" <?= $filter_status === 'dalam proses' ? 'selected' : '' ?>>Dalam Proses</option>
                                <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="terlambat" <?= $filter_status === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="?" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    </form>
                </div>

                <!-- Schedules Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Jadwal Produksi</h3>
                        <!-- Debug Info -->
                        <div class="text-xs text-gray-500 mt-1">
                            Debug: Filter tanggal = <?= htmlspecialchars($filter_tanggal) ?>, 
                            Jumlah data = <?= count($jadwal_list) ?>, 
                            Status query = <?= $jadwal_result['success'] ? 'Berhasil' : 'Gagal' ?>
                            <?php if (!$jadwal_result['success']): ?>
                                | Pesan error: <?= htmlspecialchars($jadwal_result['message'] ?? 'Tidak ada pesan') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Jadwal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pesanan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($jadwal_list)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-calendar-times fa-3x mb-4 text-gray-300"></i>
                                            <p>Belum ada jadwal produksi</p>
                                            <?php if (!empty($filter_tanggal)): ?>
                                                <p class="text-xs mt-2">Tidak ada jadwal untuk tanggal: <?= htmlspecialchars($filter_tanggal) ?></p>
                                                <p class="text-xs">Coba hapus filter atau pilih tanggal lain</p>
                                            <?php endif; ?>
                                            <!-- Debug raw data -->
                                            <details class="mt-4 text-left">
                                                <summary class="cursor-pointer text-blue-600">Show Debug Info</summary>
                                                <pre class="text-xs bg-gray-100 p-2 mt-2 overflow-auto"><?= htmlspecialchars(json_encode([
                                                    'filter_tanggal' => $filter_tanggal,
                                                    'filter_status' => $filter_status,
                                                    'jadwal_result' => $jadwal_result,
                                                    'jadwal_list_count' => count($jadwal_list)
                                                ], JSON_PRETTY_PRINT)) ?></pre>
                                            </details>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jadwal_list as $jadwal): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($jadwal['no_jadwal'] ?? 'J-' . str_pad($jadwal['id_jadwal'], 4, '0', STR_PAD_LEFT)) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div>
                                                    <div class="font-medium"><?= htmlspecialchars($jadwal['nama_pemesan'] ?? 'N/A') ?></div>
                                                    <div class="text-gray-500"><?= htmlspecialchars($jadwal['judul_desain'] ?? 'N/A') ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= format_tanggal($jadwal['tanggal_mulai']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= format_tanggal($jadwal['tanggal_selesai']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= round($jadwal['waktu_standar_hari'] ?? 0, 1) ?> hari
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $jadwal['batch_ke'] ?>/<?= $jadwal['total_batch'] ?? 1 ?>
                                                <div class="text-xs text-gray-500"><?= number_format($jadwal['jumlah_batch_ini']) ?> unit</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status_colors = [
                                                    'terjadwal' => 'bg-blue-100 text-blue-800',
                                                    'dalam proses' => 'bg-yellow-100 text-yellow-800',
                                                    'selesai' => 'bg-green-100 text-green-800',
                                                    'terlambat' => 'bg-red-100 text-red-800',
                                                    'dibatalkan' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $color_class = $status_colors[$jadwal['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $color_class ?>">
                                                    <?= ucfirst($jadwal['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="lihat_detail">
                                                    <input type="hidden" name="id_jadwal" value="<?= $jadwal['id_jadwal'] ?>">
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </form>
                                                <button onclick="editJadwal(<?= htmlspecialchars(json_encode($jadwal)) ?>)" 
                                                        class="text-green-600 hover:text-green-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($jadwal['status'] !== 'selesai'): ?>
                                                    <button onclick="deleteJadwal(<?= $jadwal['id_jadwal'] ?>)" 
                                                            class="text-red-600 hover:text-red-900" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    <!-- Add Schedule Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Tambah Jadwal Produksi</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="tambah_jadwal">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Estimasi</label>
                        <select name="id_estimasi" id="estimasiSelect" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Pilih estimasi yang siap dijadwalkan...</option>
                            <?php foreach ($estimasi_list as $estimasi): ?>
                                <option value="<?= $estimasi['id_estimasi'] ?>" 
                                        data-kualitas="<?= htmlspecialchars($estimasi['kualitas_warna'] ?? '') ?>"
                                        data-laminasi="<?= htmlspecialchars($estimasi['laminasi'] ?? '') ?>"
                                        data-jilid="<?= htmlspecialchars($estimasi['jilid'] ?? '') ?>"
                                        data-waktu-hari="<?= $estimasi['waktu_standar_hari'] ?>"
                                        data-jumlah="<?= $estimasi['jumlah'] ?>">
                                    <?= htmlspecialchars($estimasi['nama_pemesan']) ?> - <?= htmlspecialchars($estimasi['judul_desain']) ?>
                                    (<?= round($estimasi['waktu_standar_hari'], 1) ?> hari, <?= number_format($estimasi['jumlah']) ?> unit)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Mode Selection -->
                    <div id="modeSection" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode Penjadwalan</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="mode_jadwal" value="otomatis" id="modeOtomatis" checked class="mr-2">
                                <span class="text-sm">Otomatis (Recommended)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="mode_jadwal" value="manual" id="modeManual" class="mr-2">
                                <span class="text-sm">Manual</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Mode otomatis akan menghitung tanggal dan batch secara optimal</p>
                    </div>
                    
                    <!-- Auto Suggestion Panel -->
                    <div id="autoSuggestionPanel" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fas fa-lightbulb mr-1"></i>Saran Otomatis
                        </h4>
                        <div id="suggestionContent" class="text-sm text-blue-700 space-y-1">
                            <!-- Auto-generated suggestions will appear here -->
                        </div>
                        <button type="button" id="applySuggestion" class="mt-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                            Terapkan Saran
                        </button>
                    </div>
                    
                    <!-- Mesin Selection -->
                    <div id="mesinSection" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mesin</label>
                        <div id="mesinRekomendasi" class="mb-2 p-3 bg-blue-50 rounded-md border border-blue-200 hidden">
                            <h4 class="text-sm font-medium text-blue-800 mb-1">Rekomendasi Mesin:</h4>
                            <ul id="rekomendasiList" class="text-sm text-blue-700 list-disc list-inside"></ul>
                        </div>
                        <select name="id_mesin" id="mesinSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Auto-assign berdasarkan spesifikasi desain</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Kosongkan untuk auto-assign mesin sesuai spesifikasi desain</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Mulai
                                <span class="text-xs text-gray-500 ml-1" id="tanggalMulaiHelper">(Akan otomatis disesuaikan)</span>
                            </label>
                            <input type="date" name="tanggal_mulai" id="tanggalMulai" required 
                                   min="<?= date('Y-m-d') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <div class="text-xs text-gray-500 mt-1" id="tanggalMulaiInfo"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Selesai
                                <span class="text-xs text-gray-500 ml-1" id="tanggalSelesaiHelper">(Auto-calculated)</span>
                            </label>
                            <input type="date" name="tanggal_selesai" id="tanggalSelesai" required 
                                   min="<?= date('Y-m-d') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <div class="text-xs text-gray-500 mt-1" id="tanggalSelesaiInfo"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Batch Ke
                                <span class="text-xs text-gray-500 ml-1" id="batchKeHelper">(Auto-suggest)</span>
                            </label>
                            <input type="number" name="batch_ke" id="batchKe" value="1" min="1" required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <div class="text-xs text-gray-500 mt-1" id="batchKeInfo">Batch pertama untuk pesanan ini</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Jumlah Batch Ini
                                <span class="text-xs text-gray-500 ml-1" id="jumlahBatchHelper">(Dari total pesanan)</span>
                            </label>
                            <input type="number" name="jumlah_batch_ini" id="jumlahBatchIni" min="1" required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <div class="text-xs text-gray-500 mt-1" id="jumlahBatchInfo">
                                <span id="totalPesananInfo"></span>
                                <button type="button" id="setBatchFull" class="text-blue-600 hover:text-blue-800 ml-2 hidden">
                                    Gunakan semua unit
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Batch Strategy Options -->
                    <div id="batchStrategySection" class="hidden bg-gray-50 border rounded-lg p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Strategi Batch</label>
                        <div class="space-y-2 text-sm">
                            <label class="flex items-center">
                                <input type="radio" name="batch_strategy" value="single" id="strategySingle" checked class="mr-2">
                                <span>Satu batch untuk semua unit</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="batch_strategy" value="split" id="strategySplit" class="mr-2">
                                <span>Bagi menjadi beberapa batch</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="batch_strategy" value="custom" id="strategyCustom" class="mr-2">
                                <span>Tentukan jumlah batch secara manual</span>
                            </label>
                        </div>
                        <div id="batchSplitOptions" class="hidden mt-2 pl-4 border-l-2 border-blue-200">
                            <label class="block text-xs text-gray-600 mb-1">Maksimal unit per batch:</label>
                            <input type="number" id="maxUnitPerBatch" placeholder="Contoh: 1000" min="1" 
                                   class="w-32 text-sm border border-gray-300 rounded px-2 py-1">
                            <button type="button" id="calculateBatches" class="ml-2 px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                                Hitung
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeAddModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            Tambah Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Jadwal Produksi</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_jadwal">
                    <input type="hidden" name="id_jadwal" id="edit_id_jadwal">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" required 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" required 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="edit_status" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="terjadwal">Terjadwal</option>
                            <option value="dalam proses">Dalam Proses</option>
                            <option value="selesai">Selesai</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                            Update Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Optimization Modal -->
    <div id="optimizationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Optimasi SPT</h3>
                    <button onclick="closeOptimizationModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="optimasi_spt">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Optimasi</label>
                        <input type="date" name="tanggal_optimasi" required 
                               value="<?= date('Y-m-d') ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div class="text-sm text-gray-600">
                        <p><strong>Algoritma SPT (Shortest Processing Time):</strong></p>
                        <p>Jadwal akan diurutkan berdasarkan waktu proses terpendek terlebih dahulu untuk mengoptimalkan throughput produksi.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeOptimizationModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Optimasi Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Schedule Modal -->
    <?php if ($show_detail_modal && !empty($detail_jadwal_data)): ?>
    <div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Detail Jadwal Produksi</h3>
                        <p class="text-gray-600">
                            <?= htmlspecialchars($selected_jadwal['nama_pemesan'] ?? 'N/A') ?> - 
                            <?= htmlspecialchars($selected_jadwal['judul_desain'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>

                <!-- Schedule Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Informasi Jadwal</h4>
                        <div class="space-y-1 text-sm">
                            <div><span class="text-gray-600">No Jadwal:</span> <?= htmlspecialchars($selected_jadwal['no_jadwal'] ?? 'J-' . str_pad($selected_jadwal['id_jadwal'], 4, '0', STR_PAD_LEFT)) ?></div>
                            <div><span class="text-gray-600">Tanggal Mulai:</span> <?= format_tanggal($selected_jadwal['tanggal_mulai']) ?></div>
                            <div><span class="text-gray-600">Tanggal Selesai:</span> <?= format_tanggal($selected_jadwal['tanggal_selesai']) ?></div>
                            <div><span class="text-gray-600">Status:</span> <span class="font-medium"><?= ucfirst($selected_jadwal['status']) ?></span></div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Informasi Batch</h4>
                        <div class="space-y-1 text-sm">
                            <div><span class="text-gray-600">Batch:</span> <?= $selected_jadwal['batch_ke'] ?>/<?= $selected_jadwal['total_batch'] ?? 1 ?></div>
                            <div><span class="text-gray-600">Jumlah Unit:</span> <?= number_format($selected_jadwal['jumlah_batch_ini']) ?></div>
                            <div><span class="text-gray-600">Durasi Total:</span> <?= round($selected_jadwal['waktu_standar_hari'] ?? 0, 1) ?> hari</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Progress</h4>
                        <div class="space-y-2">
                            <?php
                            $completed_processes = array_filter($detail_jadwal_data, function($detail) {
                                return $detail['status'] === 'selesai';
                            });
                            $total_processes = count($detail_jadwal_data);
                            $progress_percentage = $total_processes > 0 ? (count($completed_processes) / $total_processes) * 100 : 0;
                            ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Progres:</span>
                                <span class="font-medium"><?= round($progress_percentage, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?= $progress_percentage ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= count($completed_processes) ?>/<?= $total_processes ?> proses selesai
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Process Timeline -->
                <div class="bg-white">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Timeline Proses Produksi</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urutan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Proses</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($detail_jadwal_data as $detail): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $detail['urutan_proses'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php
                                                $process_icons = [
                                                    'desain' => 'fas fa-palette text-purple-500',
                                                    'plat' => 'fas fa-layer-group text-blue-500',
                                                    'setup' => 'fas fa-cogs text-orange-500',
                                                    'cetak' => 'fas fa-print text-green-500',
                                                    'laminasi' => 'fas fa-shield-alt text-cyan-500',
                                                    'jilid' => 'fas fa-book text-indigo-500',
                                                    'qc' => 'fas fa-search text-red-500',
                                                    'packing' => 'fas fa-box text-brown-500'
                                                ];
                                                $icon_class = $process_icons[$detail['nama_proses']] ?? 'fas fa-circle text-gray-500';
                                                ?>
                                                <i class="<?= $icon_class ?> mr-3"></i>
                                                <span class="text-sm font-medium text-gray-900"><?= ucfirst($detail['nama_proses']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $detail['tanggal_mulai'] ? format_tanggal($detail['tanggal_mulai']) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $detail['tanggal_selesai'] ? format_tanggal($detail['tanggal_selesai']) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= round($detail['durasi_jam'], 1) ?> jam
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_colors = [
                                                'belum mulai' => 'bg-gray-100 text-gray-800',
                                                'dalam proses' => 'bg-yellow-100 text-yellow-800',
                                                'selesai' => 'bg-green-100 text-green-800',
                                                'terlambat' => 'bg-red-100 text-red-800'
                                            ];
                                            $color_class = $status_colors[$detail['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $color_class ?>">
                                                <?= ucfirst($detail['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?= htmlspecialchars($detail['keterangan'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button onclick="printScheduleDetail()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        <i class="fas fa-print mr-2"></i>Print Detail
                    </button>
                    <button onclick="closeDetailModal()" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Modal Management
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openOptimizationModal() {
            document.getElementById('optimizationModal').classList.remove('hidden');
        }

        function closeOptimizationModal() {
            document.getElementById('optimizationModal').classList.add('hidden');
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.remove();
            }
        }

        // Edit Schedule
        function editJadwal(jadwal) {
            document.getElementById('edit_id_jadwal').value = jadwal.id_jadwal;
            document.getElementById('edit_tanggal_mulai').value = jadwal.tanggal_mulai;
            document.getElementById('edit_tanggal_selesai').value = jadwal.tanggal_selesai;
            document.getElementById('edit_status').value = jadwal.status;
            openEditModal();
        }

        // Delete Schedule
        function deleteJadwal(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus jadwal ini?',
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
                        <input type="hidden" name="action" value="hapus_jadwal">
                        <input type="hidden" name="id_jadwal" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Export Schedule
        function exportSchedule() {
            // Implementation for export functionality
            Swal.fire('Info', 'Fitur export akan segera tersedia', 'info');
        }

        // Print Schedule Detail
        function printScheduleDetail() {
            window.print();
        }

        // Auto-calculate end date based on start date and estimation
        document.addEventListener('DOMContentLoaded', function() {
            const estimasiSelect = document.querySelector('select[name="id_estimasi"]');
            const tanggalMulaiInput = document.getElementById('tanggalMulai');
            const tanggalSelesaiInput = document.getElementById('tanggalSelesai');
            const mesinSection = document.getElementById('mesinSection');
            const mesinSelect = document.getElementById('mesinSelect');
            const mesinRekomendasi = document.getElementById('mesinRekomendasi');
            const rekomendasiList = document.getElementById('rekomendasiList');
            
            // New elements for auto-suggestion
            const modeSection = document.getElementById('modeSection');
            const modeOtomatis = document.getElementById('modeOtomatis');
            const modeManual = document.getElementById('modeManual');
            const autoSuggestionPanel = document.getElementById('autoSuggestionPanel');
            const suggestionContent = document.getElementById('suggestionContent');
            const applySuggestionBtn = document.getElementById('applySuggestion');
            const batchKeInput = document.getElementById('batchKe');
            const jumlahBatchIniInput = document.getElementById('jumlahBatchIni');
            const batchStrategySection = document.getElementById('batchStrategySection');
            const setBatchFullBtn = document.getElementById('setBatchFull');
            const totalPesananInfo = document.getElementById('totalPesananInfo');
            
            let currentSuggestion = null;
            
            function updateModeHelpers() {
                const isAutoMode = modeOtomatis.checked;
                
                // Update helper texts
                document.getElementById('tanggalMulaiHelper').textContent = 
                    isAutoMode ? '(Akan otomatis disesuaikan)' : '(Pilih manual)';
                document.getElementById('tanggalSelesaiHelper').textContent = 
                    isAutoMode ? '(Auto-calculated)' : '(Pilih manual)';
                document.getElementById('batchKeHelper').textContent = 
                    isAutoMode ? '(Auto-suggest)' : '(Input manual)';
                document.getElementById('jumlahBatchHelper').textContent = 
                    isAutoMode ? '(Dari total pesanan)' : '(Input manual)';
                
                // Enable/disable inputs based on mode
                tanggalMulaiInput.readOnly = isAutoMode;
                tanggalSelesaiInput.readOnly = isAutoMode;
                
                if (isAutoMode) {
                    tanggalMulaiInput.style.backgroundColor = '#f9fafb';
                    tanggalSelesaiInput.style.backgroundColor = '#f9fafb';
                } else {
                    tanggalMulaiInput.style.backgroundColor = 'white';
                    tanggalSelesaiInput.style.backgroundColor = 'white';
                }
            }

            function generateAutoSuggestion(estimasiData) {
                if (!estimasiData) return;
                
                const waktuHari = parseFloat(estimasiData.waktuHari) || 1;
                const jumlahUnit = parseInt(estimasiData.jumlah) || 1;
                
                // Calculate optimal start date (next working day)
                const today = new Date();
                const nextWorkingDay = new Date(today);
                // Skip weekends
                if (nextWorkingDay.getDay() === 6) { // Saturday
                    nextWorkingDay.setDate(nextWorkingDay.getDate() + 2);
                } else if (nextWorkingDay.getDay() === 0) { // Sunday
                    nextWorkingDay.setDate(nextWorkingDay.getDate() + 1);
                }
                
                // Calculate end date
                const endDate = new Date(nextWorkingDay);
                endDate.setDate(endDate.getDate() + Math.ceil(waktuHari));
                
                // Calculate optimal batch strategy
                let batchStrategy = 'single';
                let batchCount = 1;
                let unitsPerBatch = jumlahUnit;
                
                if (jumlahUnit > 5000) {
                    batchStrategy = 'split';
                    batchCount = Math.ceil(jumlahUnit / 2500); // Max 2500 per batch
                    unitsPerBatch = Math.ceil(jumlahUnit / batchCount);
                } else if (jumlahUnit > 2000) {
                    batchStrategy = 'split';
                    batchCount = 2;
                    unitsPerBatch = Math.ceil(jumlahUnit / 2);
                }
                
                currentSuggestion = {
                    tanggalMulai: nextWorkingDay.toISOString().split('T')[0],
                    tanggalSelesai: endDate.toISOString().split('T')[0],
                    batchKe: 1,
                    jumlahBatchIni: unitsPerBatch,
                    batchStrategy: batchStrategy,
                    totalBatches: batchCount
                };
                
                // Update suggestion content
                suggestionContent.innerHTML = `
                    <div class="space-y-1">
                        <div><strong>📅 Tanggal Mulai:</strong> ${formatTanggalIndonesia(nextWorkingDay)} (hari kerja berikutnya)</div>
                        <div><strong>📅 Tanggal Selesai:</strong> ${formatTanggalIndonesia(endDate)} (estimasi ${Math.ceil(waktuHari)} hari kerja)</div>
                        <div><strong>📦 Strategi Batch:</strong> ${batchStrategy === 'single' ? 'Satu batch' : `${batchCount} batch`}</div>
                        <div><strong>📊 Unit per batch:</strong> ${unitsPerBatch.toLocaleString()} dari total ${jumlahUnit.toLocaleString()} unit</div>
                        ${batchCount > 1 ? `<div class="text-xs text-blue-600">💡 Disarankan bagi menjadi ${batchCount} batch untuk efisiensi produksi</div>` : ''}
                    </div>
                `;
                
                autoSuggestionPanel.classList.remove('hidden');
            }
            
            function formatTanggalIndonesia(date) {
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                return date.toLocaleDateString('id-ID', options);
            }
            
            function applySuggestion() {
                if (!currentSuggestion) return;
                
                tanggalMulaiInput.value = currentSuggestion.tanggalMulai;
                tanggalSelesaiInput.value = currentSuggestion.tanggalSelesai;
                batchKeInput.value = currentSuggestion.batchKe;
                jumlahBatchIniInput.value = currentSuggestion.jumlahBatchIni;
                
                // Update info texts
                document.getElementById('tanggalMulaiInfo').textContent = 
                    'Mulai pada hari kerja berikutnya untuk efisiensi optimal';
                document.getElementById('tanggalSelesaiInfo').textContent = 
                    `Estimasi selesai dalam ${Math.ceil(parseFloat(estimasiSelect.selectedOptions[0]?.dataset.waktuHari) || 1)} hari kerja`;
                
                // Show batch strategy if multiple batches
                if (currentSuggestion.totalBatches > 1) {
                    batchStrategySection.classList.remove('hidden');
                    document.getElementById('strategySplit').checked = true;
                }
            }

            function updateEndDate() {
                if (estimasiSelect.value && tanggalMulaiInput.value) {
                    const selectedOption = estimasiSelect.selectedOptions[0];
                    const waktuHari = parseFloat(selectedOption.dataset.waktuHari) || 1;
                    
                    const startDate = new Date(tanggalMulaiInput.value);
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + Math.ceil(waktuHari));
                    
                    tanggalSelesaiInput.value = endDate.toISOString().split('T')[0];
                    
                    document.getElementById('tanggalSelesaiInfo').textContent = 
                        `Estimasi durasi: ${Math.ceil(waktuHari)} hari kerja`;
                }
            }
            
            function updateBatchInfo() {
                const selectedOption = estimasiSelect.selectedOptions[0];
                if (!selectedOption) return;
                
                const jumlahTotal = parseInt(selectedOption.dataset.jumlah) || 0;
                totalPesananInfo.textContent = `Total pesanan: ${jumlahTotal.toLocaleString()} unit`;
                
                if (jumlahTotal > 0) {
                    setBatchFullBtn.classList.remove('hidden');
                    setBatchFullBtn.onclick = function() {
                        jumlahBatchIniInput.value = jumlahTotal;
                        document.getElementById('jumlahBatchInfo').innerHTML = 
                            `<span class="text-green-600">✓ Menggunakan semua ${jumlahTotal.toLocaleString()} unit</span>`;
                    };
                }
                
                // Auto-suggest batch size based on quantity
                if (modeOtomatis.checked) {
                    if (jumlahTotal <= 1000) {
                        jumlahBatchIniInput.value = jumlahTotal;
                    } else if (jumlahTotal <= 5000) {
                        jumlahBatchIniInput.value = Math.ceil(jumlahTotal / 2);
                        document.getElementById('batchKeInfo').textContent = 
                            `Disarankan bagi menjadi 2 batch untuk efisiensi`;
                    } else {
                        jumlahBatchIniInput.value = 2500;
                        document.getElementById('batchKeInfo').textContent = 
                            `Batch optimal: 2500 unit per batch`;
                    }
                }
            }

            function loadMesinByEstimasi() {
                const idEstimasi = estimasiSelect.value;
                
                if (!idEstimasi) {
                    mesinSection.classList.add('hidden');
                    return;
                }

                // Show mesin section
                mesinSection.classList.remove('hidden');
                
                // Reset mesin select
                mesinSelect.innerHTML = '<option value="">Auto-assign berdasarkan spesifikasi desain</option>';
                mesinRekomendasi.classList.add('hidden');
                
                // Load mesin data via AJAX
                const formData = new FormData();
                formData.append('action', 'get_mesin_by_estimasi');
                formData.append('id_estimasi', idEstimasi);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show recommendations
                        if (data.data.rekomendasi && data.data.rekomendasi.length > 0) {
                            rekomendasiList.innerHTML = '';
                            data.data.rekomendasi.forEach(rec => {
                                const li = document.createElement('li');
                                li.textContent = rec;
                                rekomendasiList.appendChild(li);
                            });
                            mesinRekomendasi.classList.remove('hidden');
                        }
                        
                        // Populate mesin options
                        data.data.semua_mesin.forEach(mesin => {
                            const option = document.createElement('option');
                            option.value = mesin.id_mesin;
                            
                            // Check if this is recommended mesin
                            const isRecommended = data.data.mesin_utama_id == mesin.id_mesin;
                            option.textContent = mesin.nama_mesin + 
                                (isRecommended ? ' (Direkomendasikan)' : '') +
                                (mesin.tipe_mesin ? ' - ' + mesin.tipe_mesin : '');
                            
                            if (isRecommended) {
                                option.classList.add('font-bold');
                                option.style.backgroundColor = '#dbeafe'; // blue-100
                            }
                            
                            mesinSelect.appendChild(option);
                        });
                    } else {
                        console.error('Failed to load mesin:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading mesin:', error);
                });
            }

            // Event listeners
            estimasiSelect.addEventListener('change', function() {
                if (this.value) {
                    modeSection.classList.remove('hidden');
                    
                    const selectedOption = this.selectedOptions[0];
                    const estimasiData = {
                        waktuHari: selectedOption.dataset.waktuHari,
                        jumlah: selectedOption.dataset.jumlah
                    };
                    
                    if (modeOtomatis.checked) {
                        generateAutoSuggestion(estimasiData);
                    }
                    
                    updateBatchInfo();
                    loadMesinByEstimasi();
                    updateEndDate();
                } else {
                    modeSection.classList.add('hidden');
                    autoSuggestionPanel.classList.add('hidden');
                    mesinSection.classList.add('hidden');
                    batchStrategySection.classList.add('hidden');
                }
            });
            
            modeOtomatis.addEventListener('change', function() {
                updateModeHelpers();
                if (this.checked && estimasiSelect.value) {
                    const selectedOption = estimasiSelect.selectedOptions[0];
                    const estimasiData = {
                        waktuHari: selectedOption.dataset.waktuHari,
                        jumlah: selectedOption.dataset.jumlah
                    };
                    generateAutoSuggestion(estimasiData);
                    updateBatchInfo();
                }
            });
            
            modeManual.addEventListener('change', function() {
                updateModeHelpers();
                if (this.checked) {
                    autoSuggestionPanel.classList.add('hidden');
                    batchStrategySection.classList.add('hidden');
                }
            });
            
            tanggalMulaiInput.addEventListener('change', function() {
                if (!modeOtomatis.checked) {
                    updateEndDate();
                }
            });
            
            applySuggestionBtn.addEventListener('click', applySuggestion);
            
            // Batch strategy handlers
            document.getElementById('strategySplit').addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('batchSplitOptions').classList.remove('hidden');
                }
            });
            
            document.querySelectorAll('input[name="batch_strategy"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value !== 'split') {
                        document.getElementById('batchSplitOptions').classList.add('hidden');
                    }
                });
            });
            
            document.getElementById('calculateBatches').addEventListener('click', function() {
                const maxUnit = parseInt(document.getElementById('maxUnitPerBatch').value);
                const totalUnit = parseInt(estimasiSelect.selectedOptions[0]?.dataset.jumlah) || 0;
                
                if (maxUnit && totalUnit) {
                    const batchCount = Math.ceil(totalUnit / maxUnit);
                    const unitsThisBatch = Math.min(maxUnit, totalUnit);
                    
                    jumlahBatchIniInput.value = unitsThisBatch;
                    document.getElementById('jumlahBatchInfo').innerHTML = 
                        `<span class="text-blue-600">📊 ${batchCount} batch total, ${unitsThisBatch.toLocaleString()} unit batch ini</span>`;
                }
            });
            
            // Initialize mode helpers
            updateModeHelpers();
        });

        // Show success/error messages
        <?php if ($success_message): ?>
            Swal.fire('Berhasil', '<?= addslashes($success_message) ?>', 'success');
        <?php endif; ?>

        <?php if ($error_message): ?>
            Swal.fire('Error', '<?= addslashes($error_message) ?>', 'error');
        <?php endif; ?>
    </script>
</div>
<!-- End Page Content -->

<?php
// Capture the page content
$page_content = ob_get_clean();

// Include the sidebar layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>
