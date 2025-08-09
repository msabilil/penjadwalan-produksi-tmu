<?php
/**
 * Manager Penerbit - Beranda Page
 * Dashboard page for manager penerbit role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['manager penerbit']);

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Set page variables
$page_title = 'Beranda Manager Penerbit';
$page_description = 'Beranda overview untuk manajemen penerbitan dan operasional';

// Get statistics data
$pesanan_result = ambil_semua_pesanan();
$pesanan_list = $pesanan_result['success'] ? $pesanan_result['data'] : [];

$desain_result = ambil_semua_desain();
$desain_list = $desain_result['success'] ? $desain_result['data'] : [];

// Calculate statistics
$total_pesanan = count($pesanan_list);
$total_desain = count($desain_list);

// Count orders by status (if status field exists)
$pesanan_pending = 0;
$pesanan_aktif = 0;
$pesanan_selesai = 0;

foreach ($pesanan_list as $pesanan) {
    $status = $pesanan['status'] ?? 'pending';
    switch ($status) {
        case 'pending':
            $pesanan_pending++;
            break;
        case 'active':
            $pesanan_aktif++;
            break;
        case 'completed':
            $pesanan_selesai++;
            break;
    }
}

// Count designs by type
$desain_buku = 0;
$desain_majalah = 0;
$desain_lainnya = 0;

foreach ($desain_list as $desain) {
    $jenis = $desain['jenis_produk'] ?? '';
    switch ($jenis) {
        case 'buku':
            $desain_buku++;
            break;
        case 'majalah':
            $desain_majalah++;
            break;
        default:
            $desain_lainnya++;
            break;
    }
}

// Recent orders (last 5)
$recent_orders = array_slice($pesanan_list, -5, 5);
$recent_orders = array_reverse($recent_orders);

// Recent designs (last 5)
$recent_designs = array_slice($desain_list, -5, 5);
$recent_designs = array_reverse($recent_designs);

// Start output buffering to capture content
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
        <p class="text-gray-600 mt-2"><?= $page_description ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Pesanan -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Pesanan</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($total_pesanan) ?></p>
                </div>
            </div>
        </div>

        <!-- Total Desain -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-palette text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Desain</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($total_desain) ?></p>
                </div>
            </div>
        </div>

        <!-- Pesanan Aktif -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pesanan Aktif</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($pesanan_aktif) ?></p>
                </div>
            </div>
        </div>

        <!-- Pesanan Selesai -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pesanan Selesai</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($pesanan_selesai) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Pesanan Terbaru</h2>
                <a href="pesanan.php" class="text-green-600 hover:text-green-700 text-sm font-medium">
                    Lihat Semua →
                </a>
            </div>
            
            <?php if (!empty($recent_orders)): ?>
                <div class="space-y-3">
                    <?php foreach ($recent_orders as $pesanan): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($pesanan['no'] ?? '') ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($pesanan['nama_pemesan'] ?? '') ?></p>
                                <p class="text-xs text-gray-500">
                                    <?= isset($pesanan['tanggal_pesanan']) ? format_tanggal($pesanan['tanggal_pesanan']) : '' ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= number_format($pesanan['jumlah'] ?? 0) ?> item
                                </p>
                                <?php if ($pesanan['id_desain'] && $pesanan['nama_desain']): ?>
                                    <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                        Ada Desain
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded">
                                        Belum Ada Desain
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                    <p>Belum ada pesanan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Designs -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Desain Terbaru</h2>
                <a href="desain.php" class="text-green-600 hover:text-green-700 text-sm font-medium">
                    Lihat Semua →
                </a>
            </div>
            
            <?php if (!empty($recent_designs)): ?>
                <div class="space-y-3">
                    <?php foreach ($recent_designs as $desain): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($desain['nama'] ?? '') ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($desain['jenis_produk'] ?? '') ?></p>
                                <p class="text-xs text-gray-500">
                                    <?= htmlspecialchars($desain['jenis_desain'] ?? '') ?> • 
                                    <?= htmlspecialchars($desain['model_warna'] ?? '') ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                    <?= htmlspecialchars($desain['halaman'] ?? 0) ?> hal
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-palette text-3xl mb-2 block"></i>
                    <p>Belum ada desain</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="pesanan.php" 
               class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                <div class="p-2 bg-blue-100 rounded-lg mr-3">
                    <i class="fas fa-shopping-cart text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Kelola Pesanan</p>
                    <p class="text-sm text-gray-600">Pantau dan kelola orders</p>
                </div>
            </a>

            <a href="desain.php" 
               class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                <div class="p-2 bg-green-100 rounded-lg mr-3">
                    <i class="fas fa-palette text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Kelola Desain</p>
                    <p class="text-sm text-gray-600">Buat dan edit desain</p>
                </div>
            </a>

            <a href="download_file.php?type=report" 
               class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                <div class="p-2 bg-purple-100 rounded-lg mr-3">
                    <i class="fas fa-download text-purple-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Download Laporan</p>
                    <p class="text-sm text-gray-600">Ekspor data dan laporan</p>
                </div>
            </a>

            <button onclick="window.location.reload()" 
                    class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <i class="fas fa-sync-alt text-gray-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Refresh Data</p>
                    <p class="text-sm text-gray-600">Perbarui beranda</p>
                </div>
            </button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include '../../layouts/sidebar_manager_penerbit.php';
?>
