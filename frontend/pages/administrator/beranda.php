<?php
/**
 * Administrator - Beranda Page
 * Beranda page for administrator role with system overview and statistics
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['administrator']);

// Set page variables
$page_title = 'Beranda Administrator';

// Include required functions
require_once '../../../backend/functions/user_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/mesin_functions.php';
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Get statistics data
$stats = [];

// User statistics
$user_stats = hitung_statistik_user();
if ($user_stats['success']) {
    $stats['total_users'] = $user_stats['data']['total_users'];
    
    // Convert by_role array to associative array
    $users_by_role = [];
    foreach ($user_stats['data']['by_role'] as $role_data) {
        $users_by_role[$role_data['role']] = $role_data['jumlah'];
    }
    $stats['users_by_role'] = $users_by_role;
}

// Design statistics  
$desain_result = ambil_semua_desain();
if ($desain_result['success']) {
    $stats['total_designs'] = count($desain_result['data']);
    
    // Count by jenis_desain
    $design_types = [];
    foreach ($desain_result['data'] as $desain) {
        $jenis = $desain['jenis_desain'];
        $design_types[$jenis] = ($design_types[$jenis] ?? 0) + 1;
    }
    $stats['designs_by_type'] = $design_types;
}

// Machine statistics
$mesin_result = ambil_semua_mesin();
if ($mesin_result['success']) {
    $stats['total_machines'] = count($mesin_result['data']);
    
    // Count by nama_mesin (since jenis_mesin doesn't exist)
    $machine_types = [];
    foreach ($mesin_result['data'] as $mesin) {
        $nama = $mesin['nama_mesin'];
        $machine_types[$nama] = ($machine_types[$nama] ?? 0) + 1;
    }
    $stats['machines_by_type'] = $machine_types;
}

// Order statistics (if function exists)
$pesanan_result = ambil_semua_pesanan();
if ($pesanan_result['success']) {
    $stats['total_orders'] = count($pesanan_result['data']);
    
    // Count recent orders (last 30 days)
    $recent_orders = 0;
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    
    foreach ($pesanan_result['data'] as $pesanan) {
        if ($pesanan['tanggal_pesanan'] >= $thirty_days_ago) {
            $recent_orders++;
        }
    }
    $stats['recent_orders'] = $recent_orders;
}

// Set content for layout
ob_start();
?>

<!-- Page Content -->
<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Beranda Administrator</h1>
        <p class="text-gray-600">Selamat datang di panel administrator sistem penjadwalan produksi TMU</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Users</p>
                    <p class="text-3xl font-bold"><?= $stats['total_users'] ?? 0 ?></p>
                </div>
                <div class="bg-blue-400 bg-opacity-50 rounded-lg p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Designs -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Desain</p>
                    <p class="text-3xl font-bold"><?= $stats['total_designs'] ?? 0 ?></p>
                </div>
                <div class="bg-green-400 bg-opacity-50 rounded-lg p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Machines -->
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Mesin</p>
                    <p class="text-3xl font-bold"><?= $stats['total_machines'] ?? 0 ?></p>
                </div>
                <div class="bg-purple-400 bg-opacity-50 rounded-lg p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.243 3.03a1 1 0 01.727 1.213L9.53 6h2.94l.56-2.243a1 1 0 111.94.486L14.53 6H16a1 1 0 110 2h-1.53l-1 4H15a1 1 0 110 2h-1.53l-.56 2.242a1 1 0 11-1.94-.485L11.47 14H8.53l-.56 2.242a1 1 0 11-1.94-.485L6.47 14H5a1 1 0 110-2h1.47l1-4H6a1 1 0 110-2h1.47l.56-2.243a1 1 0 01.213-.727z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Total Pesanan</p>
                    <p class="text-3xl font-bold"><?= $stats['total_orders'] ?? 0 ?></p>
                </div>
                <div class="bg-orange-400 bg-opacity-50 rounded-lg p-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Users by Role -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi User by Role</h3>
            <div class="space-y-3">
                <?php if (isset($stats['users_by_role'])): ?>
                    <?php foreach ($stats['users_by_role'] as $role => $count): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 capitalize"><?= htmlspecialchars($role) ?></span>
                        <div class="flex items-center">
                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?= $stats['total_users'] > 0 ? ($count / $stats['total_users']) * 100 : 0 ?>%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?= $count ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">Data tidak tersedia</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Designs by Type -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Desain by Type</h3>
            <div class="space-y-3">
                <?php if (isset($stats['designs_by_type'])): ?>
                    <?php foreach ($stats['designs_by_type'] as $type => $count): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 capitalize"><?= htmlspecialchars($type) ?></span>
                        <div class="flex items-center">
                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $stats['total_designs'] > 0 ? ($count / $stats['total_designs']) * 100 : 0 ?>%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?= $count ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">Data tidak tersedia</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Machines Statistics -->
    <div class="grid grid-cols-1 gap-6 mb-8">
        <!-- Machines by Name -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Mesin by Name</h3>
            <div class="space-y-3">
                <?php if (isset($stats['machines_by_type'])): ?>
                    <?php foreach ($stats['machines_by_type'] as $type => $count): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600"><?= htmlspecialchars($type) ?></span>
                        <div class="flex items-center">
                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: <?= $stats['total_machines'] > 0 ? ($count / $stats['total_machines']) * 100 : 0 ?>%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?= $count ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">Data tidak tersedia</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="data_user.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <div class="bg-blue-500 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Kelola User</p>
                    <p class="text-sm text-gray-600">Tambah & edit user</p>
                </div>
            </a>

            <a href="data_desain.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                <div class="bg-green-500 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Kelola Desain</p>
                    <p class="text-sm text-gray-600">Tambah & edit desain</p>
                </div>
            </a>

            <a href="data_mesin.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                <div class="bg-purple-500 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.243 3.03a1 1 0 01.727 1.213L9.53 6h2.94l.56-2.243a1 1 0 111.94.486L14.53 6H16a1 1 0 110 2h-1.53l-1 4H15a1 1 0 110 2h-1.53l-.56 2.242a1 1 0 11-1.94-.485L11.47 14H8.53l-.56 2.242a1 1 0 11-1.94-.485L6.47 14H5a1 1 0 110-2h1.47l1-4H6a1 1 0 110-2h1.47l.56-2.243a1 1 0 01.213-.727z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Kelola Mesin</p>
                    <p class="text-sm text-gray-600">Tambah & edit mesin</p>
                </div>
            </a>

            <a href="laporan.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                <div class="bg-orange-500 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Lihat Laporan</p>
                    <p class="text-sm text-gray-600">Analisis sistem</p>
                </div>
            </a>
        </div>
    </div>

    <!-- System Information -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Sistem</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
                <p class="text-gray-600">Versi Sistem</p>
                <p class="font-semibold">v1.0.0</p>
            </div>
            <div>
                <p class="text-gray-600">Last Login</p>
                <p class="font-semibold"><?= date('d/m/Y H:i') ?></p>
            </div>
            <div>
                <p class="text-gray-600">Server Time</p>
                <p class="font-semibold" id="serverTime"><?= date('d/m/Y H:i:s') ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    // Update server time every second
    function updateServerTime() {
        const now = new Date();
        const options = {
            year: 'numeric',
            month: '2-digit', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        document.getElementById('serverTime').textContent = now.toLocaleDateString('id-ID', options).replace(/\//g, '/');
    }

    // Update time every second
    setInterval(updateServerTime, 1000);
</script>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_administrator.php';
?>
