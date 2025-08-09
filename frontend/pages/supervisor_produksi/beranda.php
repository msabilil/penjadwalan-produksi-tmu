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

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/jadwal_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Get statistics data
$stats = [
    'total_pesanan' => 0,
    'pesanan_by_status' => [],
    'total_estimasi' => 0,
    'estimasi_pending' => 0,
    'estimasi_completed' => 0,
    'total_jadwal' => 0,
    'jadwal_by_status' => [],
    'perlu_estimasi' => 0,
    'terestimasi' => 0,
    'terjadwal' => 0
];

// Order statistics
$pesanan_stats = hitung_statistik_pesanan();
if ($pesanan_stats['success']) {
    $stats['total_pesanan'] = $pesanan_stats['data']['total_pesanan'] ?? 0;
    $stats['pesanan_by_status'] = $pesanan_stats['data']['by_status'] ?? [];
}

// Estimation statistics
$estimasi_stats = hitung_statistik_estimasi();
if ($estimasi_stats['success']) {
    $stats['total_estimasi'] = $estimasi_stats['data']['total_estimasi'] ?? 0;
    $stats['estimasi_pending'] = $estimasi_stats['data']['estimasi_pending'] ?? 0;
    $stats['estimasi_completed'] = $estimasi_stats['data']['estimasi_completed'] ?? 0;
}

// Schedule statistics
$jadwal_stats = hitung_statistik_jadwal();
if ($jadwal_stats['success']) {
    $stats['total_jadwal'] = $jadwal_stats['data']['total_jadwal'] ?? 0;
    $stats['jadwal_by_status'] = $jadwal_stats['data']['by_status'] ?? [];
}

// Get recent orders for supervisor-specific statistics
$recent_orders_result = ambil_semua_pesanan(50, 0); // Get more data for better statistics
$recent_orders = $recent_orders_result['success'] ? $recent_orders_result['data'] : [];

// Calculate supervisor-specific statistics
$perlu_estimasi = 0;
$terestimasi = 0;
$terjadwal = 0;

foreach ($recent_orders as $pesanan) {
    // TODO: Replace with actual status checking functions when implemented
    // For now, simulate status based on some logic
    $has_estimasi = false; // placeholder
    $has_jadwal = false;   // placeholder
    
    if ($has_jadwal) {
        $terjadwal++;
    } elseif ($has_estimasi) {
        $terestimasi++;
    } else {
        $perlu_estimasi++;
    }
}

// Add supervisor-specific stats
$stats['perlu_estimasi'] = $perlu_estimasi;
$stats['terestimasi'] = $terestimasi;
$stats['terjadwal'] = $terjadwal;

// Recent data for tables
$recent_pesanan = ambil_semua_pesanan(5, 0);
$recent_estimasi = ambil_semua_estimasi(5, 0);
$recent_jadwal = ambil_semua_jadwal(1, 5); // page=1, limit=5

// Start output buffering
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Beranda Supervisor Produksi</h1>
        <p class="text-gray-600">Selamat datang di panel supervisor produksi. Kelola estimasi dan jadwal produksi.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Pesanan -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shopping-cart text-blue-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_pesanan'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <!-- Perlu Estimasi -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Perlu Estimasi</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['perlu_estimasi'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <!-- Terestimasi -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calculator text-yellow-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Terestimasi</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['terestimasi'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <!-- Terjadwal -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-check text-green-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Terjadwal</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['terjadwal'] ?? 0 ?></p>
                </div>
            </div>
        </div>
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
