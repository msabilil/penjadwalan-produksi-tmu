<?php
/**
 * Supervisor Produksi - Estimasi Page
 * Estimation management page for supervisor produksi role
 * Calculate & Recalculate workflow: Pesanan → Auto Calculate → Custom Parameters → Recalculate → Finalize
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/detail_estimasi_functions.php';
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['supervisor produksi']);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya handle recalculate dan finalize - tidak ada hitung_estimasi manual lagi
    if ($_POST['action'] === 'recalculate_estimasi') {
        $new_params = [
            'jumlah_desainer' => intval($_POST['jumlah_desainer']),
            'pekerja_qc' => intval($_POST['pekerja_qc']),
            'pekerja_packing' => intval($_POST['pekerja_packing'])
        ];
        
        $result = recalculate_estimasi($_POST['id_estimasi'], $new_params);
        if ($result['success']) {
            $success_message = "Estimasi berhasil dihitung ulang dengan parameter baru!";
            // Get updated estimation and detail data for result modal
            $estimasi_result = ambil_estimasi_by_id($_POST['id_estimasi']);
            $detail_result = ambil_detail_estimasi_by_estimasi($_POST['id_estimasi']);
            
            if ($estimasi_result['success'] && $detail_result['success']) {
                $estimasi_data = $estimasi_result['data'];
                $detail_data = $detail_result['data'];
                $show_result_modal = true;
                $result_estimasi_id = $_POST['id_estimasi'];
            }
        } else {
            $error_message = $result['message'];
        }
    }
    
    if ($_POST['action'] === 'finalize_estimasi') {
        $result = finalize_estimasi($_POST['id_estimasi']);
        if ($result['success']) {
            $success_message = "Estimasi berhasil difinalisasi dan siap dijadwalkan!";
        } else {
            $error_message = $result['message'];
        }
    }
    
    if ($_POST['action'] === 'generate_missing_estimations') {
        $result = generate_missing_estimations();
        if ($result['success']) {
            $data = $result['data'];
            $success_message = "Proses generate estimasi selesai. Berhasil: {$data['berhasil']}, Gagal: {$data['gagal']}";
            if (!empty($data['errors'])) {
                $error_message = "Beberapa estimasi gagal: " . implode(', ', array_slice($data['errors'], 0, 3)) . (count($data['errors']) > 3 ? '...' : '');
            }
        } else {
            $error_message = $result['message'];
        }
    }
}

// Get data for display
$pesanan_tanpa_estimasi_result = ambil_pesanan_tanpa_estimasi();
$pesanan_tanpa_estimasi = $pesanan_tanpa_estimasi_result['success'] ? $pesanan_tanpa_estimasi_result['data'] : [];

$estimasi_result = ambil_semua_estimasi();
$estimasi_list = $estimasi_result['success'] ? $estimasi_result['data'] : [];

$page_title = 'Estimasi Produksi - Supervisor Produksi';

// Start output buffering
ob_start();
?>

<!-- Page Content -->
<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Estimasi Produksi</h1>
        <p class="text-gray-600">Informasi estimasi waktu produksi pesanan.</p>
        
        <!-- Info Box
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 text-lg mt-1"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Alur Estimasi Otomatis</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ol class="list-decimal list-inside space-y-1">
                            <li><strong>Otomatis:</strong> Estimasi dihitung secara otomatis saat pesanan dibuat dengan parameter default (1 desainer, 4 QC, 4 packing)</li>
                            <li><strong>Hitung Ulang:</strong> Ubah jumlah pekerja dan gunakan tombol "Hitung Ulang" untuk update estimasi dengan parameter baru</li>
                            <li><strong>Finalisasi:</strong> Finalisasi estimasi yang sudah sesuai untuk dijadwalkan produksi</li>
                        </ol>
                    </div>
                    <div class="mt-3 p-2 bg-green-100 rounded border-l-4 border-green-400">
                        <p class="text-xs text-green-800">
                            <i class="fas fa-robot mr-1"></i>
                            <strong>Tidak perlu input manual!</strong> Semua estimasi dibuat otomatis oleh sistem saat pesanan ditambahkan.
                        </p>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Section 1: Pesanan Memerlukan Estimasi Manual -->
    <?php if (!empty($pesanan_tanpa_estimasi)): ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-amber-200 mb-8">
        <div class="px-6 py-4 border-b border-amber-200 bg-amber-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-exclamation-triangle text-amber-600 mr-2"></i>
                Pesanan Memerlukan Estimasi Manual
            </h3>
            <p class="text-sm text-amber-700 mt-1">
                Pesanan berikut memiliki masalah dengan estimasi otomatis. Kemungkinan pesanan dibuat tanpa desain valid atau terjadi error sistem.
                Total: <?= count($pesanan_tanpa_estimasi) ?> pesanan memerlukan perhatian
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">No. Pesanan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Pemesan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Desain</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal Pesanan</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($pesanan_tanpa_estimasi as $pesanan): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($pesanan['no'] ?? 'N/A') ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($pesanan['nama_pemesan'] ?? 'Tidak diketahui') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($pesanan['nama_desain'])): ?>
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($pesanan['nama_desain']) ?></div>
                                    <div class="text-xs text-gray-500"><?= ucwords($pesanan['jenis_desain'] ?? 'tidak diketahui') ?></div>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times mr-1"></i>
                                        Tidak ada desain
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= number_format($pesanan['jumlah'] ?? 0) ?> eksemplar</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= format_tanggal($pesanan['tanggal_pesanan'] ?? date('Y-m-d')) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if (!empty($pesanan['id_desain'])): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        Siap estimasi
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        Perlu desain
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 bg-amber-25 border-t border-amber-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-amber-700">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <span><strong>Saran:</strong> Periksa apakah pesanan memiliki desain valid. Estimasi akan dibuat otomatis setelah desain ditambahkan ke pesanan.</span>
                </div>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="generate_missing_estimations">
                    <button type="submit" 
                            onclick="return confirm('Apakah Anda yakin ingin mencoba menghitung ulang estimasi untuk semua pesanan yang memiliki desain tapi belum terestimasi?')"
                            class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Generate Estimasi yang Hilang
                    </button>
                </form>
            </div>
        </div>
    </div>  
    <?php else: ?>
    <!-- <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-green-200 mb-8">
        <div class="px-6 py-4 bg-green-50 text-center">
            <div class="flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                <div>
                    <h3 class="text-lg font-semibold text-green-900">Semua Pesanan Sudah Terestimasi</h3>
                    <p class="text-sm text-green-700 mt-1">Sistem estimasi otomatis berjalan dengan baik. Semua pesanan memiliki estimasi produksi.</p>
                </div>
            </div>
        </div>
    </div> -->
    <?php endif; ?>

    <!-- Section 2: Estimasi yang Sudah Ada -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-list-alt text-blue-600 mr-2"></i>
                Estimasi Produksi
            </h3>
            <p class="text-sm text-gray-600 mt-1">
                Total: <?= count($estimasi_list) ?> estimasi 
                <!-- <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-robot mr-1"></i>
                    Otomatis saat pesanan dibuat
                </span> -->
            </p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">No. Pesanan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Pemesan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Desain</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Spesifikasi</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Desain</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Plat</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Setup</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Mesin</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">QC</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Packing</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Total (Hari)</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal Estimasi</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($estimasi_list)): ?>
                        <?php foreach ($estimasi_list as $estimasi): ?>
                            <tr class="hover:bg-gray-50">
                                <!-- No. Pesanan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($estimasi['no_pesanan'] ?? 'N/A') ?></span>
                                </td>
                                
                                <!-- Pemesan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($estimasi['nama_pemesan'] ?? 'Tidak diketahui') ?></div>
                                </td>
                                
                                <!-- Desain -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($estimasi['nama_desain'] ?? 'N/A') ?></div>
                                </td>
                                
                                <!-- Jumlah -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> eksemplar</span>
                                </td>
                                
                                <!-- Spesifikasi Desain -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <div class="text-xs">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                <?= ($estimasi['model_warna'] ?? '') === 'fullcolor' ? 'bg-blue-100 text-blue-800' : 
                                                    (($estimasi['model_warna'] ?? '') === 'dua warna' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') ?>">
                                                <?= htmlspecialchars($estimasi['model_warna'] ?? 'N/A') ?>
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= $estimasi['halaman'] ?? 0 ?> hal • <?= $estimasi['sisi'] ?? 1 ?> sisi
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Waktu Desain -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_desain'] ?? 0, 1) ?> menit</div>
                                </td>
                                
                                <!-- Waktu Plat -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_plat'] ?? 0, 1) ?> menit</div>
                                </td>
                                
                                <!-- Waktu Setup -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_total_setup'] ?? 0, 0) ?> menit</div>
                                </td>
                                
                                <!-- Waktu Mesin -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_mesin'] ?? 0, 1) ?> menit</div>
                                </td>
                                
                                <!-- Waktu QC -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_qc'] ?? 0, 1) ?> menit</div>
                                </td>
                                
                                <!-- Waktu Packing -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= number_format($estimasi['waktu_packing'] ?? 0, 1) ?> menit</div>
                                </td>
                                
                                <!-- Total Waktu -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-lg font-bold text-blue-600">
                                        <?= number_format($estimasi['waktu_hari'] ?? 0, 1) ?> hari
                                    </div>
                                </td>
                                
                                <!-- Tanggal -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= format_tanggal($estimasi['tanggal_estimasi'] ?? date('Y-m-d')) ?></div>
                                </td>
                                
                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-2">
                                        <a href="detail_estimasi.php?id=<?= $estimasi['id_estimasi'] ?>" 
                                           class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                            Detail
                                        </a>
                                        <button onclick="showRecalculateModal(<?= $estimasi['id_estimasi'] ?>, '<?= htmlspecialchars($estimasi['no_pesanan'] ?? 'N/A') ?>')" 
                                                class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                            Hitung Ulang
                                        </button>
                                        <button onclick="finalizeEstimasi(<?= $estimasi['id_estimasi'] ?>)" 
                                                class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                            Finalisasi
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="14" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-calculator text-gray-400 text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada estimasi</h3>
                                    <p class="text-gray-500">Belum ada estimasi yang tersedia</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal untuk hasil perhitungan detail -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-blue-50">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-calculator text-green-600 mr-2"></i>
                        Hasil Perhitungan Estimasi
                    </h3>
                    <button onclick="closeResultModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div id="resultContent" class="p-6">
                <!-- Content akan diisi via JavaScript -->
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                <button onclick="closeResultModal()" 
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-all duration-200">
                    Tutup
                </button>
                <button onclick="finalizeFromResult()" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all duration-200">
                    <i class="fas fa-check mr-2"></i>
                    Finalisasi Estimasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recalculate Estimasi -->
<div id="recalculateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="recalcModalTitle">Hitung Ulang Estimasi</h3>
                <button type="button" onclick="closeRecalculateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="recalculate_estimasi">
                <input type="hidden" name="id_estimasi" id="recalc_id_estimasi">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Desainer</label>
                        <input type="number" name="jumlah_desainer" value="1" min="1" max="10" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pekerja QC</label>
                        <input type="number" name="pekerja_qc" value="4" min="1" max="20" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pekerja Packing</label>
                        <input type="number" name="pekerja_packing" value="4" min="1" max="20" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeRecalculateModal()" 
                            class="px-4 py-2 text-gray-500 bg-gray-200 rounded-md hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Hitung Ulang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions - Hanya untuk Recalculate (Hitung Ulang)
function showRecalculateModal(idEstimasi, noPesanan) {
    document.getElementById('recalc_id_estimasi').value = idEstimasi;
    document.getElementById('recalcModalTitle').textContent = `Hitung Ulang Estimasi - Pesanan ${noPesanan}`;
    document.getElementById('recalculateModal').classList.remove('hidden');
}

function closeRecalculateModal() {
    document.getElementById('recalculateModal').classList.add('hidden');
}

// Result Modal functions
function showResultModal(estimasiData, detailData, noPesanan) {
    const content = generateResultContent(estimasiData, detailData, noPesanan);
    document.getElementById('resultContent').innerHTML = content;
    document.getElementById('resultModal').classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('resultModal').classList.add('hidden');
}

function generateResultContent(estimasi, detail, noPesanan) {
    return `
        <div class="space-y-6">
            <!-- Summary Header -->
            <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-6 border border-blue-200">
                <h4 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                    Ringkasan Estimasi - Pesanan ${noPesanan}
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 border border-blue-100">
                        <div class="text-sm text-gray-600 font-medium">Total Waktu</div>
                        <div class="text-2xl font-bold text-blue-600">${estimasi.waktu_hari} hari</div>
                        <div class="text-sm text-gray-500">${estimasi.waktu_jam} jam • ${estimasi.waktu_menit} menit</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-green-100">
                        <div class="text-sm text-gray-600 font-medium">Jumlah Produksi</div>
                        <div class="text-2xl font-bold text-green-600">${estimasi.jumlah_pesanan}</div>
                        <div class="text-sm text-gray-500">eksemplar</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-purple-100">
                        <div class="text-sm text-gray-600 font-medium">Efisiensi</div>
                        <div class="text-2xl font-bold text-purple-600">${(estimasi.waktu_menit / estimasi.jumlah_pesanan).toFixed(4)}</div>
                        <div class="text-sm text-gray-500">menit/eksemplar</div>
                    </div>
                </div>
            </div>

            <!-- Process Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Desain Process -->
                <div class="bg-blue-50 rounded-lg p-5 border border-blue-200">
                    <h5 class="font-bold text-blue-900 text-lg mb-4">
                        <i class="fas fa-paint-brush mr-2"></i>
                        Proses Desain
                    </h5>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Desain -->
                        <div class="bg-blue-100 p-3 rounded border-l-4 border-blue-500">
                            <div class="font-bold text-blue-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-blue-800">1. Waktu Menit Desain = Estimasi Waktu Desain (hari) × 480 menit/hari</div>
                                <div class="text-blue-800">2. Waktu Desain = Waktu Menit Desain ÷ Jumlah Desainer</div>
                                <div class="text-blue-600 mt-1 text-xs border-t pt-1">
                                    <div>Waktu Menit = ${estimasi.estimasi_waktu_desain || 0} hari × 480 = ${(estimasi.estimasi_waktu_desain || 0) * 480} menit</div>
                                    <div>Waktu Desain = ${(estimasi.estimasi_waktu_desain || 0) * 480} ÷ ${detail.jumlah_desainer} = ${estimasi.waktu_desain} menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between py-2 border-b border-blue-200">
                            <span class="font-medium">Estimasi Waktu Desain:</span>
                            <span class="font-bold">${estimasi.estimasi_waktu_desain || 0} hari</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-blue-200">
                            <span class="font-medium">Waktu Menit Desain:</span>
                            <span class="font-bold">${(estimasi.estimasi_waktu_desain || 0) * 480} menit</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-blue-200">
                            <span class="font-medium">Jumlah Desainer:</span>
                            <span class="font-bold">${detail.jumlah_desainer} orang</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-blue-200">
                            <span class="font-medium">Waktu per Desainer:</span>
                            <span class="font-bold">${((estimasi.estimasi_waktu_desain || 0) / Math.max(detail.jumlah_desainer, 1)).toFixed(2)} menit</span>
                        </div>
                        <div class="bg-blue-100 p-3 rounded">
                            <div class="flex justify-between">
                                <span class="font-bold text-blue-900">Total Waktu Desain:</span>
                                <span class="font-bold text-lg text-blue-900">${estimasi.waktu_desain} menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plat Process -->
                <div class="bg-green-50 rounded-lg p-5 border border-green-200">
                    <h5 class="font-bold text-green-900 text-lg mb-4">
                        <i class="fas fa-layer-group mr-2"></i>
                        Proses Plat
                    </h5>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Plat -->
                        <div class="bg-green-100 p-3 rounded border-l-4 border-green-500">
                            <div class="font-bold text-green-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-green-800">1. Plat per Set = Jumlah Warna × Sisi</div>
                                <div class="text-green-800">2. Total Plat = (Halaman ÷ Halaman per Plat) × Plat per Set</div>
                                <div class="text-green-800">3. Waktu Plat = Total Plat × Waktu per Plat</div>
                                <div class="text-green-600 mt-1 text-xs border-t pt-1">
                                    <div>${estimasi.jumlah_warna || 1} × ${estimasi.sisi || 1} = ${detail.jumlah_plat_per_set}</div>
                                    <div>(${estimasi.halaman || 0} ÷ ${detail.jumlah_halaman_per_plat}) × ${detail.jumlah_plat_per_set} = ${detail.jumlah_plat}</div>
                                    <div>${detail.jumlah_plat} × ${detail.waktu_per_plat} = ${estimasi.waktu_plat} menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between py-2 border-b border-green-200">
                            <span class="font-medium">Halaman Total:</span>
                            <span class="font-bold">${estimasi.halaman || 0} halaman</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-green-200">
                            <span class="font-medium">Halaman per Plat:</span>
                            <span class="font-bold">${detail.jumlah_halaman_per_plat} halaman</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-green-200">
                            <span class="font-medium">Warna × Sisi:</span>
                            <span class="font-bold">${estimasi.jumlah_warna || 1} × ${estimasi.sisi || 1} = ${detail.jumlah_plat_per_set}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-green-200">
                            <span class="font-medium">Total Plat:</span>
                            <span class="font-bold">${detail.jumlah_plat} plat</span>
                        </div>
                        <div class="bg-green-100 p-3 rounded">
                            <div class="flex justify-between">
                                <span class="font-bold text-green-900">Total Waktu Plat:</span>
                                <span class="font-bold text-lg text-green-900">${estimasi.waktu_plat} menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Machine Process -->
                <div class="bg-yellow-50 rounded-lg p-5 border border-yellow-200">
                    <h5 class="font-bold text-yellow-900 text-lg mb-4">
                        <i class="fas fa-cogs mr-2"></i>
                        Proses Mesin
                    </h5>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Mesin -->
                        <div class="bg-yellow-100 p-3 rounded border-l-4 border-yellow-500">
                            <div class="font-bold text-yellow-900 mb-2">📐 Rumus & Pemilihan Mesin:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-yellow-800">Waktu Mesin = Jumlah Pesanan × Waktu Mesin per Eksemplar</div>
                                <div class="text-yellow-600 mt-1 text-xs border-t pt-1">
                                    <div><strong>Pemilihan Mesin Berdasarkan Spesifikasi:</strong></div>
                                    <div>• Kualitas: \${estimasi.kualitas_warna || 'cukup'} → \${(estimasi.kualitas_warna || 'cukup') === 'tinggi' ? 'Mesin Sheet (45 min)' : 'Mesin Web (90 min)'}</div>
                                    <div>• Laminasi: \${estimasi.laminasi || 'tidak'} → \${(estimasi.laminasi === 'glossy' || estimasi.laminasi === 'doff') ? 'Mesin Vernis (30 min)' : 'Tidak perlu'}</div>
                                    <div>• Jilid: \${estimasi.jilid || 'tidak'} → Sesuai spesifikasi</div>
                                    <div class="mt-1">Total Setup: \${estimasi.waktu_total_setup} menit</div>
                                    <div>\${estimasi.jumlah_pesanan} × \${detail.waktu_mesin_per_eks} = \${estimasi.waktu_mesin} menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between py-2 border-b border-yellow-200">
                            <span class="font-medium">Jumlah Pesanan:</span>
                            <span class="font-bold">${estimasi.jumlah_pesanan} eksemplar</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-yellow-200">
                            <span class="font-medium">Waktu per Eksemplar:</span>
                            <span class="font-bold">${detail.waktu_mesin_per_eks} menit</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-yellow-200">
                            <span class="font-medium">Waktu Setup:</span>
                            <span class="font-bold">${estimasi.waktu_total_setup} menit</span>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded">
                            <div class="flex justify-between">
                                <span class="font-bold text-yellow-900">Total Waktu Mesin:</span>
                                <span class="font-bold text-lg text-yellow-900">${estimasi.waktu_mesin} menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finishing Process -->
                <div class="bg-purple-50 rounded-lg p-5 border border-purple-200">
                    <h5 class="font-bold text-purple-900 text-lg mb-4">
                        <i class="fas fa-check-double mr-2"></i>
                        Proses Finishing
                    </h5>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus QC & Packing -->
                        <div class="bg-purple-100 p-3 rounded border-l-4 border-purple-500">
                            <div class="font-bold text-purple-900 mb-2">📐 Rumus QC & Packing:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-purple-800">QC = (Jumlah × Standar QC) ÷ Pekerja QC</div>
                                <div class="text-purple-800">Box = CEIL(Jumlah ÷ Kapasitas Box)</div>
                                <div class="text-purple-800">Packing = (Box × Waktu per Box) ÷ Pekerja Packing</div>
                                <div class="text-purple-600 mt-1 text-xs border-t pt-1">
                                    <div>QC = (${estimasi.jumlah_pesanan} × ${detail.waktu_standar_qc}) ÷ ${detail.pekerja_qc} = ${estimasi.waktu_qc} menit</div>
                                    <div>Box = CEIL(${estimasi.jumlah_pesanan} ÷ ${detail.kapasitas_box}) = ${detail.jumlah_box}</div>
                                    <div>Packing = (${detail.jumlah_box} × ${detail.waktu_standar_packing}) ÷ ${detail.pekerja_packing} = ${estimasi.waktu_packing} menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between py-2 border-b border-purple-200">
                            <span class="font-medium">Waktu QC Total:</span>
                            <span class="font-bold">${(estimasi.jumlah_pesanan * detail.waktu_standar_qc).toFixed(2)} menit</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-purple-200">
                            <span class="font-medium">Pekerja QC:</span>
                            <span class="font-bold">${detail.pekerja_qc} orang</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-purple-200">
                            <span class="font-medium">Waktu QC Efektif:</span>
                            <span class="font-bold">${estimasi.waktu_qc} menit</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-purple-200">
                            <span class="font-medium">Jumlah Box:</span>
                            <span class="font-bold">${detail.jumlah_box} box</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-purple-200">
                            <span class="font-medium">Pekerja Packing:</span>
                            <span class="font-bold">${detail.pekerja_packing} orang</span>
                        </div>
                        <div class="bg-purple-100 p-3 rounded">
                            <div class="flex justify-between">
                                <span class="font-bold text-purple-900">Total Waktu Packing:</span>
                                <span class="font-bold text-lg text-purple-900">${estimasi.waktu_packing} menit</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final Summary -->
            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg p-6 border-2 border-gray-300">
                <h5 class="font-bold text-gray-900 text-xl mb-4">
                    <i class="fas fa-stopwatch mr-2"></i>
                    Ringkasan Total Perhitungan
                </h5>
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 text-center">
                    <div class="bg-blue-50 p-3 rounded border border-blue-200">
                        <div class="text-sm font-medium text-gray-600">Desain</div>
                        <div class="text-lg font-bold text-blue-600">${estimasi.waktu_desain}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded border border-green-200">
                        <div class="text-sm font-medium text-gray-600">Plat</div>
                        <div class="text-lg font-bold text-green-600">${estimasi.waktu_plat}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <div class="text-sm font-medium text-gray-600">Setup</div>
                        <div class="text-lg font-bold text-yellow-600">${estimasi.waktu_total_setup}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                    <div class="bg-orange-50 p-3 rounded border border-orange-200">
                        <div class="text-sm font-medium text-gray-600">Mesin</div>
                        <div class="text-lg font-bold text-orange-600">${estimasi.waktu_mesin}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                    <div class="bg-purple-50 p-3 rounded border border-purple-200">
                        <div class="text-sm font-medium text-gray-600">QC</div>
                        <div class="text-lg font-bold text-purple-600">${estimasi.waktu_qc}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                    <div class="bg-indigo-50 p-3 rounded border border-indigo-200">
                        <div class="text-sm font-medium text-gray-600">Packing</div>
                        <div class="text-lg font-bold text-indigo-600">${estimasi.waktu_packing}</div>
                        <div class="text-xs text-gray-500">menit</div>
                    </div>
                </div>
                
                <div class="mt-6 bg-gray-300 p-4 rounded-lg">
                    <!-- Rumus Total -->
                    <div class="bg-gray-800 p-4 rounded-lg mb-4">
                        <div class="font-bold text-yellow-300 mb-3 flex items-center justify-center">
                            <i class="fas fa-function mr-2"></i>
                            Rumus Total Perhitungan
                        </div>
                        <div class="font-mono text-sm bg-gray-900 p-3 rounded border text-center space-y-2">
                            <div class="text-yellow-200">
                                <strong>Total Waktu =</strong>
                            </div>
                            <div class="text-white text-xs">
                                Desain + Plat + Setup + Mesin + QC + Packing
                            </div>
                            <div class="text-gray-300 text-xs border-t border-gray-600 pt-2">
                                ${estimasi.waktu_desain} + ${estimasi.waktu_plat} + ${estimasi.waktu_total_setup} + ${estimasi.waktu_mesin} + ${estimasi.waktu_qc} + ${estimasi.waktu_packing}
                            </div>
                            <div class="text-yellow-300 font-bold">
                                = ${estimasi.waktu_standar_menit} menit
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 mb-2">
                            TOTAL ESTIMASI WAKTU PRODUKSI
                        </div>
                        <div class="text-4xl font-bold text-blue-600 mb-2">
                            ${estimasi.waktu_standar_hari} HARI
                        </div>
                        <div class="text-lg text-gray-700">
                            (${estimasi.waktu_standar_jam} jam • ${estimasi.waktu_standar_menit} menit)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

let currentEstimasiId = null;

function finalizeFromResult() {
    if (currentEstimasiId) {
        finalizeEstimasi(currentEstimasiId);
        closeResultModal();
    }
}

function finalizeEstimasi(idEstimasi) {
    Swal.fire({
        title: 'Finalisasi Estimasi?',
        text: 'Estimasi yang sudah difinalisasi tidak dapat diubah lagi dan siap untuk dijadwalkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Finalisasi',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="finalize_estimasi">
                <input type="hidden" name="id_estimasi" value="${idEstimasi}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'recalculateModal') closeRecalculateModal();
});

// Success/Error messages handling
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($success_message): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?= htmlspecialchars($success_message) ?>',
            icon: 'success',
            confirmButtonColor: '#16a34a'
        });
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?= htmlspecialchars($error_message) ?>',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    <?php endif; ?>
    
    // Auto show result modal if calculation was successful
    <?php if (isset($show_result_modal) && $show_result_modal): ?>
        const estimasiData = <?= json_encode($estimasi_data) ?>;
        const detailData = <?= json_encode($detail_data) ?>;
        const noPesanan = '<?= htmlspecialchars($estimasi_data['no_pesanan'] ?? 'N/A') ?>';
        currentEstimasiId = <?= $result_estimasi_id ?>;
        
        // Show result modal automatically after page loads
        setTimeout(function() {
            showResultModal(estimasiData, detailData, noPesanan);
        }, 500);
    <?php endif; ?>
});
</script>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>
