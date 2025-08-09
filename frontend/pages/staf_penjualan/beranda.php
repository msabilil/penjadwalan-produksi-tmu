<?php
/**
 * Staf Penjualan - Beranda Page
 * Dashboard for staf penjualan role
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['staf penjualan']);

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Set page variables
$page_title = 'Beranda Staf Penjualan';
$page_description = 'Ringkasan pesanan dan aktivitas penjualan';

// Get dashboard data
$pesanan_result = ambil_semua_pesanan();
$pesanan_list = $pesanan_result['success'] ? $pesanan_result['data'] : [];

// Calculate statistics
$total_pesanan = count($pesanan_list);
$pesanan_pending = count(array_filter($pesanan_list, function($p) { 
    return isset($p['design_status']) && $p['design_status'] == 'design_needed'; 
}));
$pesanan_aktif = count(array_filter($pesanan_list, function($p) { 
    return isset($p['design_status']) && $p['design_status'] == 'design_ready'; 
}));
$pesanan_selesai = 0; // Belum ada kolom status selesai di database

// Get recent orders (last 5)
$pesanan_terbaru = array_slice($pesanan_list, 0, 5);

// Start output buffering to capture content
ob_start();
?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
        <p class="text-gray-600 mt-2"><?= $page_description ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Orders Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pesanan</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $total_pesanan ?></p>
                </div>
            </div>
        </div>

        <!-- Pending Orders Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Butuh Desain</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $pesanan_pending ?></p>
                </div>
            </div>
        </div>

        <!-- Active Orders Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-play text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Desain Siap</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $pesanan_aktif ?></p>
                </div>
            </div>
        </div>

        <!-- Completed Orders Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-check text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Dalam Produksi</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $pesanan_selesai ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Pesanan Terbaru</h2>
                        <a href="pesanan.php" class="text-green-600 hover:text-green-700 text-sm font-medium">
                            Lihat Semua
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No. Pesanan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pemesan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($pesanan_terbaru)): ?>
                                <?php foreach ($pesanan_terbaru as $pesanan): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($pesanan['no']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($pesanan['nama_pemesan']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= number_format($pesanan['jumlah']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            if (isset($pesanan['design_status'])) {
                                                switch ($pesanan['design_status']) {
                                                    case 'design_needed':
                                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                                        $status_text = 'Butuh Desain';
                                                        break;
                                                    case 'design_ready':
                                                        $status_class = 'bg-green-100 text-green-800';
                                                        $status_text = 'Desain Siap';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-gray-100 text-gray-800';
                                                        $status_text = 'Tidak Diketahui';
                                                }
                                            } else {
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                $status_text = 'Tidak Diketahui';
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= format_tanggal($pesanan['tanggal_pesanan']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
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

        <!-- Quick Actions -->
        <div class="space-y-6">
            <!-- Quick Add Order -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                <div class="space-y-3">
                    <a href="pesanan.php" 
                       class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Pesanan</span>
                    </a>
                    
                    <a href="pesanan.php" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <i class="fas fa-list"></i>
                        <span>Lihat Semua Pesanan</span>
                    </a>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Butuh Desain</span>
                        <span class="text-sm font-medium text-yellow-600"><?= $pesanan_pending ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Desain Siap</span>
                        <span class="text-sm font-medium text-green-600"><?= $pesanan_aktif ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Dalam Produksi</span>
                        <span class="text-sm font-medium text-purple-600"><?= $pesanan_selesai ?></span>
                    </div>
                </div>
            </div>

            <!-- Today's Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Hari Ini</h3>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600"><?= date('d') ?></p>
                    <p class="text-sm text-gray-500"><?= date('F Y') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include '../../layouts/sidebar_staf_penjualan.php';
?>
