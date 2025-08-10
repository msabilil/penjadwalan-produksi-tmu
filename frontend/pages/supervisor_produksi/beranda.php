<?php
/**
 * Supervisor Produksi - Beranda Page
 * Beranda page for supervisor produksi role with production overview and statistics
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['supervisor produksi']);

// Set page variables
$page_title = 'Beranda Supervisor Produksi';

// Include required functions (only what's needed for basic functionality)
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Recent data for tables with error handling
try {
    $recent_pesanan = ambil_semua_pesanan(5, 0); // limit=5, offset=0
    if (!$recent_pesanan['success']) {
        $recent_pesanan = ['success' => false, 'data' => []];
    }
} catch (Exception $e) {
    $recent_pesanan = ['success' => false, 'data' => []];
    error_log("Error getting recent orders: " . $e->getMessage());
}

try {
    $recent_estimasi = ambil_semua_estimasi(5, 0); // limit=5, offset=0
    if (!$recent_estimasi['success']) {
        $recent_estimasi = ['success' => false, 'data' => []];
    }
} catch (Exception $e) {
    $recent_estimasi = ['success' => false, 'data' => []];
    error_log("Error getting recent estimations: " . $e->getMessage());
}

try {
    // Use available function instead of non-existent ambil_semua_jadwal
    if (function_exists('getJadwalList')) {
        $jadwal_data = getJadwalList();
        // Limit to 5 recent items
        $recent_jadwal_data = array_slice($jadwal_data, 0, 5);
        $recent_jadwal = ['success' => true, 'data' => $recent_jadwal_data];
    } else {
        $recent_jadwal = ['success' => false, 'data' => []];
    }
} catch (Exception $e) {
    $recent_jadwal = ['success' => false, 'data' => []];
    error_log("Error getting recent schedules: " . $e->getMessage());
}

// Start output buffering
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Beranda Supervisor Produksi</h1>
        <p class="text-gray-600">Selamat datang di panel supervisor produksi. Kelola estimasi dan jadwal produksi.</p>
    </div>

    <!-- Recent Data -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Pesanan -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pesanan Terbaru</h3>
            </div>
            <div class="p-6">
                <?php if ($recent_pesanan['success'] && !empty($recent_pesanan['data'])): ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_pesanan['data'] as $pesanan): ?>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($pesanan['nama_desain'] ?? 'Desain Baru') ?> - 
                                        <?= number_format($pesanan['jumlah'] ?? 0) ?> pcs
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500"><?= isset($pesanan['tanggal_pesanan']) ? format_tanggal($pesanan['tanggal_pesanan']) : '-' ?></p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?= ucfirst($pesanan['status'] ?? 'baru') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="pesanan.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Lihat semua pesanan →
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500">Belum ada pesanan terbaru</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Estimasi -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Estimasi Terbaru</h3>
            </div>
            <div class="p-6">
                <?php if ($recent_estimasi['success'] && !empty($recent_estimasi['data'])): ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_estimasi['data'] as $estimasi): ?>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-900">Pesanan #<?= htmlspecialchars($estimasi['id_pesanan']) ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?= number_format($estimasi['waktu_hari'] ?? 0, 1) ?> hari - 
                                        <?= number_format($estimasi['waktu_jam'] ?? 0, 1) ?> jam
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500"><?= isset($estimasi['tanggal_estimasi']) ? format_tanggal($estimasi['tanggal_estimasi']) : '-' ?></p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <?= ucfirst($estimasi['status'] ?? 'pending') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="estimasi.php" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                            Lihat semua estimasi →
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calculator text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500">Belum ada estimasi terbaru</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>
