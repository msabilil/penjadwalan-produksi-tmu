<?php
/**
 * Supervisor Produksi - Data Desain Page
 * Design management page for supervisor produksi role - View only for production planning
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['supervisor produksi']);

$success_message = '';
$error_message = '';

// Get all designs for viewing
$desains_result = ambil_semua_desain();
$desains = $desains_result['success'] ? $desains_result['data'] : [];

$page_title = 'Data Desain - Supervisor Produksi';

// Start output buffering
ob_start();
?>

<!-- Page Content -->
<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Data Desain</h1>
        <p class="text-gray-600">Lihat data desain untuk estimasi dan penjadwalan produksi percetakan.</p>
    </div>

    <!-- Designs Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Daftar Desain</h3>
            <p class="text-sm text-gray-600 mt-1">Total: <?= count($desains) ?> desain</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis Desain</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jenis Produk</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Model Warna</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kualitas Warna</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sisi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ukuran</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Halaman</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cover</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Laminasi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jilid</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estimasi Waktu</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Desain Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($desains)): ?>
                        <?php foreach ($desains as $index => $desain): ?>
                            <?php
                            // Define color classes for jenis desain
                            $jenis_colors = [
                                'desain default' => 'bg-gray-100 text-gray-800 border-gray-200',
                                'desain sederhana' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'desain kompleks' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'desain premium' => 'bg-green-100 text-green-800 border-green-200'
                            ];
                            $color_class = $jenis_colors[$desain['jenis_desain']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                            ?>
                            <tr class="hover:bg-gray-50">
                                <!-- No -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900"><?= $index + 1 ?></span>
                                </td>

                                <!-- Nama -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($desain['nama']) ?></div>
                                </td>

                                <!-- Jenis Desain -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $color_class ?> border">
                                        <?= ucwords(str_replace('_', ' ', $desain['jenis_desain'])) ?>
                                    </span>
                                </td>

                                <!-- Jenis Produk -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['jenis_produk']) ?></div>
                                </td>

                                <!-- Model Warna -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['model_warna']) ?></div>
                                </td>

                                <!-- Kualitas Warna -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['kualitas_warna']) ?></div>
                                </td>

                                <!-- Sisi -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= $desain['sisi'] ?></div>
                                </td>

                                <!-- Ukuran -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($desain['ukuran']) ?></div>
                                </td>

                                <!-- Halaman -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= number_format($desain['halaman']) ?></div>
                                </td>

                                <!-- Cover -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['jenis_cover']) ?></div>
                                </td>

                                <!-- Laminasi -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['laminasi']) ?></div>
                                </td>

                                <!-- Jilid -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= ucwords($desain['jilid']) ?></div>
                                </td>

                                <!-- Estimasi Waktu -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= $desain['estimasi_waktu_desain'] ?> hari
                                    </div>
                                </td>

                                <!-- Desain Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if (!empty($desain['file_cetak'])): ?>
                                        <div class="text-xs text-green-600">✓ Desain tersedia</div>
                                    <?php else: ?>
                                        <div class="text-xs text-red-600">Belum memiliki desain</div>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if (!empty($desain['file_cetak'])): ?>
                                        <a href="download_file.php?id=<?= $desain['id_desain'] ?>" 
                                           class="bg-green-100 hover:bg-green-200 text-green-700 hover:text-green-900 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-lg">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Tidak ada file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="16" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada data desain</h3>
                                    <p class="text-gray-500">Belum ada desain yang tersedia untuk produksi</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
