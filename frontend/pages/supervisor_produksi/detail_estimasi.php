<?php
/**
 * Supervisor Produksi - Detail Estimasi Page
 * Detailed view of estimation with all calculation parameters
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/detail_estimasi_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['supervisor produksi']);

// Get estimation ID from parameter
$id_estimasi = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_estimasi <= 0) {
    header('Location: estimasi.php?error=' . urlencode('ID estimasi tidak valid'));
    exit;
}

// Get estimation data
$estimasi_result = ambil_estimasi_by_id($id_estimasi);
if (!$estimasi_result['success']) {
    header('Location: estimasi.php?error=' . urlencode('Estimasi tidak ditemukan'));
    exit;
}

$estimasi = $estimasi_result['data'];

// Get detail estimation data
$detail_result = ambil_detail_estimasi_by_estimasi($id_estimasi);
$detail = $detail_result['success'] ? $detail_result['data'] : null;

$page_title = 'Detail Estimasi - Supervisor Produksi';

// Start output buffering
ob_start();
?>

<!-- Page Content -->
<div class="p-6">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Detail Estimasi</h1>
                <p class="text-gray-600">Detail perhitungan estimasi waktu produksi untuk pesanan <?= htmlspecialchars($estimasi['no_pesanan']) ?></p>
            </div>
            <a href="estimasi.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-all duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>
    </div>

    <!-- Estimation Summary -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                Ringkasan Estimasi
            </h3>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pesanan Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Informasi Pesanan</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">No. Pesanan:</span> <?= htmlspecialchars($estimasi['no_pesanan']) ?></div>
                        <div><span class="font-medium">Pemesan:</span> <?= htmlspecialchars($estimasi['nama_pemesan']) ?></div>
                        <div><span class="font-medium">Jumlah:</span> <?= number_format($estimasi['jumlah']) ?> eksemplar</div>
                        <div><span class="font-medium">Tanggal Estimasi:</span> <?= format_tanggal($estimasi['tanggal_estimasi']) ?></div>
                    </div>
                </div>

                <!-- Time Breakdown -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Breakdown Waktu</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Total Menit:</span> <?= number_format($estimasi['waktu_menit'], 2) ?></div>
                        <div><span class="font-medium">Total Jam:</span> <?= number_format($estimasi['waktu_jam'], 2) ?></div>
                        <div class="text-lg font-bold text-green-600">
                            <span class="font-medium">Total Hari:</span> <?= number_format($estimasi['waktu_hari'], 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Process Times -->
                <div class="bg-yellow-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Waktu Proses (Menit)</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Desain:</span> <?= number_format($estimasi['waktu_desain'], 2) ?></div>
                        <div><span class="font-medium">Plat:</span> <?= number_format($estimasi['waktu_plat'], 2) ?></div>
                        <div><span class="font-medium">Setup:</span> <?= number_format($estimasi['waktu_total_setup'], 2) ?></div>
                        <div><span class="font-medium">Mesin:</span> <?= number_format($estimasi['waktu_mesin'], 2) ?></div>
                    </div>
                </div>

                <!-- Finishing Times -->
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Waktu Finishing (Menit)</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">QC:</span> <?= number_format($estimasi['waktu_qc'], 2) ?></div>
                        <div><span class="font-medium">Packing:</span> <?= number_format($estimasi['waktu_packing'], 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calculation Breakdown -->
    <?php if ($detail): ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-calculator text-blue-600 mr-2"></i>
                Breakdown Perhitungan Detail
            </h3>
            <p class="text-sm text-gray-600 mt-1">Step-by-step perhitungan estimasi waktu produksi</p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Desain Calculation -->
                <div class="bg-blue-50 rounded-lg p-5">
                    <h4 class="font-bold text-blue-900 text-lg mb-4">
                        <i class="fas fa-paint-brush mr-2"></i>
                        Perhitungan Waktu Desain
                    </h4>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Desain -->
                        <div class="bg-blue-100 p-3 rounded border-l-4 border-blue-500">
                            <div class="font-bold text-blue-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-blue-800">1. Waktu Menit Desain = Estimasi Waktu Desain (hari) × 480 menit/hari</div>
                                <div class="text-blue-800">2. Waktu Desain = Waktu Menit Desain ÷ Jumlah Desainer</div>
                                <div class="text-blue-600 mt-1 text-xs border-t pt-1">
                                    <div>Waktu Menit = <?= $estimasi['estimasi_waktu_desain'] ?? 0 ?> hari × 480 = <?= ($estimasi['estimasi_waktu_desain'] ?? 0) * 480 ?> menit</div>
                                    <div>Waktu Desain = <?= ($estimasi['estimasi_waktu_desain'] ?? 0) * 480 ?> ÷ <?= $detail['jumlah_desainer'] ?> = <?= $estimasi['waktu_desain'] ?> menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-medium text-gray-700">Estimasi Waktu Desain:</span>
                            <span class="font-bold text-blue-800"><?= number_format($estimasi['estimasi_waktu_desain'] ?? 0, 0) ?> hari</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-medium text-gray-700">Waktu Menit Desain:</span>
                            <span class="font-bold text-blue-800"><?= number_format(($estimasi['estimasi_waktu_desain'] ?? 0) * 480, 0) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-blue-200">
                            <span class="font-medium text-gray-700">Jumlah Desainer:</span>
                            <span class="font-bold text-blue-800"><?= $detail['jumlah_desainer'] ?> orang</span>
                        </div>
                        <div class="bg-blue-100 p-3 rounded mt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-blue-900">Total Waktu Desain:</span>
                                <span class="font-bold text-lg text-blue-900"><?= number_format($estimasi['waktu_desain'], 2) ?> menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plat Calculation -->
                <div class="bg-green-50 rounded-lg p-5">
                    <h4 class="font-bold text-green-900 text-lg mb-4">
                        <i class="fas fa-layer-group mr-2"></i>
                        Perhitungan Waktu Plat
                    </h4>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Plat -->
                        <div class="bg-green-100 p-3 rounded border-l-4 border-green-500">
                            <div class="font-bold text-green-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-green-800">1. Plat per Set = Jumlah Warna × Sisi</div>
                                <div class="text-green-800">2. Total Plat = (Halaman ÷ Halaman per Plat) × Plat per Set</div>
                                <div class="text-green-800">3. Waktu Plat = Total Plat × Waktu per Plat</div>
                                <div class="text-green-600 mt-1 text-xs border-t pt-1">
                                    <div>Plat per Set = <?= ($estimasi['jumlah_warna'] ?? 1) ?> × <?= ($estimasi['sisi'] ?? 1) ?> = <?= $detail['jumlah_plat_per_set'] ?></div>
                                    <div>Total Plat = (<?= $estimasi['halaman'] ?? 0 ?> ÷ <?= $detail['jumlah_halaman_per_plat'] ?>) × <?= $detail['jumlah_plat_per_set'] ?> = <?= $detail['jumlah_plat'] ?></div>
                                    <div>Waktu Plat = <?= $detail['jumlah_plat'] ?> × <?= number_format($detail['waktu_per_plat'], 2) ?> = <?= number_format($estimasi['waktu_plat'], 2) ?> menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-green-200">
                            <span class="font-medium text-gray-700">Halaman Total:</span>
                            <span class="font-bold text-green-800"><?= $estimasi['halaman'] ?? 0 ?> halaman</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-green-200">
                            <span class="font-medium text-gray-700">Halaman per Plat:</span>
                            <span class="font-bold text-green-800"><?= $detail['jumlah_halaman_per_plat'] ?> halaman</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-green-200">
                            <span class="font-medium text-gray-700">Warna × Sisi:</span>
                            <span class="font-bold text-green-800"><?= ($estimasi['jumlah_warna'] ?? 1) ?> × <?= ($estimasi['sisi'] ?? 1) ?> = <?= $detail['jumlah_plat_per_set'] ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-green-200">
                            <span class="font-medium text-gray-700">Total Plat:</span>
                            <span class="font-bold text-green-800"><?= $detail['jumlah_plat'] ?> plat</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-green-200">
                            <span class="font-medium text-gray-700">Waktu per Plat:</span>
                            <span class="font-bold text-green-800"><?= number_format($detail['waktu_per_plat'], 2) ?> menit</span>
                        </div>
                        <div class="bg-green-100 p-3 rounded mt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-green-900">Total Waktu Plat:</span>
                                <span class="font-bold text-lg text-green-900"><?= number_format($estimasi['waktu_plat'], 2) ?> menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Machine Calculation -->
                <div class="bg-yellow-50 rounded-lg p-5">
                    <h4 class="font-bold text-yellow-900 text-lg mb-4">
                        <i class="fas fa-cogs mr-2"></i>
                        Perhitungan Waktu Mesin & Setup
                    </h4>
                    <div class="space-y-4 text-sm">
                        <!-- Algoritma Pemilihan Mesin -->
                        <div class="bg-yellow-100 p-4 rounded border-l-4 border-yellow-500">
                            <div class="font-bold text-yellow-900 mb-3">� Algoritma Pemilihan Mesin:</div>
                            <div class="bg-white p-3 rounded space-y-3">
                                
                                <!-- 1. Mesin Cetak -->
                                <div class="border-l-4 border-blue-400 pl-3">
                                    <div class="font-bold text-blue-800 mb-1">1. MESIN CETAK (Berdasarkan Kualitas Warna)</div>
                                    <div class="font-mono text-xs bg-blue-50 p-2 rounded">
                                        <div class="text-blue-700">IF (kualitas_warna == "tinggi")</div>
                                        <div class="text-blue-700 ml-4">THEN: Gunakan Mesin Sheet</div>
                                        <div class="text-blue-700 ml-4">Setup Time: 45 menit</div>
                                        <div class="text-blue-700">ELSE</div>
                                        <div class="text-blue-700 ml-4">THEN: Gunakan Mesin Web</div>
                                        <div class="text-blue-700 ml-4">Setup Time: 90 menit</div>
                                    </div>
                                    <div class="mt-1 text-sm">
                                        <span class="font-medium">Hasil:</span> Kualitas "<?= $estimasi['kualitas_warna'] ?? 'cukup' ?>" → 
                                        <span class="font-bold text-blue-600">
                                            <?= ($estimasi['kualitas_warna'] ?? 'cukup') === 'tinggi' ? 'Mesin Sheet (45 min)' : 'Mesin Web (90 min)' ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- 2. Mesin Laminasi -->
                                <div class="border-l-4 border-green-400 pl-3">
                                    <div class="font-bold text-green-800 mb-1">2. MESIN LAMINASI (Berdasarkan Laminasi)</div>
                                    <div class="font-mono text-xs bg-green-50 p-2 rounded">
                                        <div class="text-green-700">IF (laminasi == "glossy" OR laminasi == "doff")</div>
                                        <div class="text-green-700 ml-4">THEN: Gunakan Mesin Vernis</div>
                                        <div class="text-green-700 ml-4">Setup Time: 30 menit</div>
                                        <div class="text-green-700">ELSE</div>
                                        <div class="text-green-700 ml-4">THEN: Tidak perlu mesin laminasi</div>
                                        <div class="text-green-700 ml-4">Setup Time: 0 menit</div>
                                    </div>
                                    <div class="mt-1 text-sm">
                                        <span class="font-medium">Hasil:</span> Laminasi "<?= $estimasi['laminasi'] ?? 'tidak' ?>" → 
                                        <span class="font-bold text-green-600">
                                            <?php if (($estimasi['laminasi'] ?? 'tidak') === 'glossy' || ($estimasi['laminasi'] ?? 'tidak') === 'doff'): ?>
                                                Mesin Vernis (30 min)
                                            <?php else: ?>
                                                Tidak perlu (0 min)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- 3. Mesin Jilid -->
                                <div class="border-l-4 border-purple-400 pl-3">
                                    <div class="font-bold text-purple-800 mb-1">3. MESIN JILID (Berdasarkan Jenis Jilid)</div>
                                    <div class="font-mono text-xs bg-purple-50 p-2 rounded">
                                        <div class="text-purple-700">IF (jilid == "lem")</div>
                                        <div class="text-purple-700 ml-4">THEN: Gunakan Mesin TSK, Setup: 30 min</div>
                                        <div class="text-purple-700">ELSE IF (jilid == "jahit")</div>
                                        <div class="text-purple-700 ml-4">THEN: Gunakan Mesin Jahit, Setup: 20 min</div>
                                        <div class="text-purple-700">ELSE IF (jilid == "spiral")</div>
                                        <div class="text-purple-700 ml-4">THEN: Gunakan Mesin Spiral, Setup: 0 min</div>
                                        <div class="text-purple-700 ml-4">PLUS: Tambah 1 min per eksemplar</div>
                                        <div class="text-purple-700">ELSE</div>
                                        <div class="text-purple-700 ml-4">THEN: Tidak perlu mesin jilid</div>
                                    </div>
                                    <div class="mt-1 text-sm">
                                        <span class="font-medium">Hasil:</span> Jilid "<?= $estimasi['jilid'] ?? 'tidak' ?>" → 
                                        <span class="font-bold text-purple-600">
                                            <?php 
                                            switch($estimasi['jilid'] ?? 'tidak') {
                                                case 'lem': echo 'Mesin TSK (30 min)'; break;
                                                case 'jahit': echo 'Mesin Jahit (20 min)'; break;
                                                case 'spiral': echo 'Mesin Spiral (0 min + 1 min/eks)'; break;
                                                default: echo 'Tidak perlu (0 min)';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rumus Setup Total -->
                        <div class="bg-orange-100 p-3 rounded border-l-4 border-orange-500">
                            <div class="font-bold text-orange-900 mb-2">📊 Rumus Total Setup:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-orange-800">Total Setup = Setup Cetak + Setup Laminasi + Setup Jilid</div>
                                <div class="text-orange-600 mt-1 text-xs border-t pt-1">
                                    <?php
                                    $setup_cetak = ($estimasi['kualitas_warna'] ?? 'cukup') === 'tinggi' ? 45 : 90;
                                    $setup_laminasi = (($estimasi['laminasi'] ?? 'tidak') === 'glossy' || ($estimasi['laminasi'] ?? 'tidak') === 'doff') ? 30 : 0;
                                    $setup_jilid = 0;
                                    switch($estimasi['jilid'] ?? 'tidak') {
                                        case 'lem': $setup_jilid = 30; break;
                                        case 'jahit': $setup_jilid = 20; break;
                                        case 'spiral': $setup_jilid = 0; break;
                                    }
                                    ?>
                                    <div>Setup = <?= $setup_cetak ?> + <?= $setup_laminasi ?> + <?= $setup_jilid ?> = <?= number_format($estimasi['waktu_total_setup'], 0) ?> menit</div>
                                </div>
                            </div>
                        </div>

                        <!-- Rumus Waktu Mesin -->
                        <div class="bg-red-100 p-3 rounded border-l-4 border-red-500">
                            <div class="font-bold text-red-900 mb-2">⚙️ Rumus Waktu Mesin:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-red-800">Waktu Mesin = Jumlah Pesanan × Waktu per Eksemplar</div>
                                <?php if (($estimasi['jilid'] ?? 'tidak') === 'spiral'): ?>
                                <div class="text-red-800">Catatan: Untuk spiral, tambah 1 menit per eksemplar</div>
                                <?php endif; ?>
                                <div class="text-red-600 mt-1 text-xs border-t pt-1">
                                    <div><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> × <?= number_format($detail['waktu_mesin_per_eks'], 6) ?> = <?= number_format($estimasi['waktu_mesin'], 2) ?> menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-yellow-200">
                            <span class="font-medium text-gray-700">Jumlah Pesanan:</span>
                            <span class="font-bold text-yellow-800"><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> eksemplar</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-yellow-200">
                            <span class="font-medium text-gray-700">Waktu per Eksemplar:</span>
                            <span class="font-bold text-yellow-800"><?= number_format($detail['waktu_mesin_per_eks'], 6) ?> menit</span>
                        </div>
                        
                        <!-- Daftar Mesin yang Digunakan -->
                        <div class="bg-yellow-200 p-3 rounded mt-3">
                            <div class="font-bold text-yellow-900 mb-2">🔧 Mesin yang Digunakan:</div>
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span>1. Mesin Cetak:</span>
                                    <span class="font-bold">
                                        <?= ($estimasi['kualitas_warna'] ?? 'cukup') === 'tinggi' ? 'Mesin Sheet' : 'Mesin Web' ?>
                                    </span>
                                </div>
                                <?php if (($estimasi['laminasi'] ?? 'tidak') === 'glossy' || ($estimasi['laminasi'] ?? 'tidak') === 'doff'): ?>
                                <div class="flex justify-between">
                                    <span>2. Mesin Laminasi:</span>
                                    <span class="font-bold">Mesin Vernis</span>
                                </div>
                                <?php endif; ?>
                                <?php if (($estimasi['jilid'] ?? 'tidak') !== 'tidak'): ?>
                                <div class="flex justify-between">
                                    <span>3. Mesin Jilid:</span>
                                    <span class="font-bold">
                                        <?php 
                                        switch($estimasi['jilid'] ?? 'tidak') {
                                            case 'lem': echo 'Mesin TSK'; break;
                                            case 'jahit': echo 'Mesin Jahit'; break;
                                            case 'spiral': echo 'Mesin Spiral'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-100 p-3 rounded mt-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold text-yellow-900">Total Setup:</span>
                                <span class="font-bold text-lg text-yellow-900"><?= number_format($estimasi['waktu_total_setup'], 0) ?> menit</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-yellow-900">Total Waktu Mesin:</span>
                                <span class="font-bold text-lg text-yellow-900"><?= number_format($estimasi['waktu_mesin'], 2) ?> menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QC Calculation -->
                <div class="bg-purple-50 rounded-lg p-5">
                    <h4 class="font-bold text-purple-900 text-lg mb-4">
                        <i class="fas fa-check-circle mr-2"></i>
                        Perhitungan Waktu QC
                    </h4>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus QC -->
                        <div class="bg-purple-100 p-3 rounded border-l-4 border-purple-500">
                            <div class="font-bold text-purple-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-purple-800">1. Total Waktu QC = Jumlah Pesanan × Waktu Standar QC</div>
                                <div class="text-purple-800">2. Waktu QC Efektif = Total Waktu QC ÷ Jumlah Pekerja QC</div>
                                <div class="text-purple-600 mt-1 text-xs border-t pt-1">
                                    <div>Total = <?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> × <?= number_format($detail['waktu_standar_qc'], 3) ?> = <?= number_format(($estimasi['jumlah_pesanan'] ?? 0) * $detail['waktu_standar_qc'], 2) ?> menit</div>
                                    <div>Efektif = <?= number_format(($estimasi['jumlah_pesanan'] ?? 0) * $detail['waktu_standar_qc'], 2) ?> ÷ <?= $detail['pekerja_qc'] ?> = <?= number_format($estimasi['waktu_qc'], 2) ?> menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-purple-200">
                            <span class="font-medium text-gray-700">Jumlah Pesanan:</span>
                            <span class="font-bold text-purple-800"><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> eksemplar</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-purple-200">
                            <span class="font-medium text-gray-700">Waktu Standar QC:</span>
                            <span class="font-bold text-purple-800"><?= number_format($detail['waktu_standar_qc'], 3) ?> menit/eksemplar</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-purple-200">
                            <span class="font-medium text-gray-700">Total Waktu QC:</span>
                            <span class="font-bold text-purple-800"><?= number_format(($estimasi['jumlah_pesanan'] ?? 0) * $detail['waktu_standar_qc'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-purple-200">
                            <span class="font-medium text-gray-700">Jumlah Pekerja QC:</span>
                            <span class="font-bold text-purple-800"><?= $detail['pekerja_qc'] ?> orang</span>
                        </div>
                        <div class="bg-purple-100 p-3 rounded mt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-purple-900">Waktu QC Efektif:</span>
                                <span class="font-bold text-lg text-purple-900"><?= number_format($estimasi['waktu_qc'], 2) ?> menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Packing Calculation -->
                <div class="bg-indigo-50 rounded-lg p-5">
                    <h4 class="font-bold text-indigo-900 text-lg mb-4">
                        <i class="fas fa-box mr-2"></i>
                        Perhitungan Waktu Packing
                    </h4>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Packing -->
                        <div class="bg-indigo-100 p-3 rounded border-l-4 border-indigo-500">
                            <div class="font-bold text-indigo-900 mb-2">📐 Rumus:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-indigo-800">1. Jumlah Box = CEIL(Jumlah Pesanan ÷ Kapasitas Box)</div>
                                <div class="text-indigo-800">2. Total Waktu Packing = Jumlah Box × Waktu per Box</div>
                                <div class="text-indigo-800">3. Waktu Packing Efektif = Total Waktu ÷ Jumlah Pekerja</div>
                                <div class="text-indigo-600 mt-1 text-xs border-t pt-1">
                                    <div>Box = CEIL(<?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> ÷ <?= $detail['kapasitas_box'] ?>) = <?= $detail['jumlah_box'] ?></div>
                                    <div>Total = <?= $detail['jumlah_box'] ?> × <?= number_format($detail['waktu_standar_packing'], 2) ?> = <?= number_format($detail['jumlah_box'] * $detail['waktu_standar_packing'], 2) ?> menit</div>
                                    <div>Efektif = <?= number_format($detail['jumlah_box'] * $detail['waktu_standar_packing'], 2) ?> ÷ <?= $detail['pekerja_packing'] ?> = <?= number_format($estimasi['waktu_packing'], 2) ?> menit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Jumlah Pesanan:</span>
                            <span class="font-bold text-indigo-800"><?= number_format($estimasi['jumlah_pesanan'] ?? 0) ?> eksemplar</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Kapasitas per Box:</span>
                            <span class="font-bold text-indigo-800"><?= $detail['kapasitas_box'] ?> eksemplar</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Jumlah Box:</span>
                            <span class="font-bold text-indigo-800"><?= $detail['jumlah_box'] ?> box</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Waktu per Box:</span>
                            <span class="font-bold text-indigo-800"><?= number_format($detail['waktu_standar_packing'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Total Waktu Packing:</span>
                            <span class="font-bold text-indigo-800"><?= number_format($detail['jumlah_box'] * $detail['waktu_standar_packing'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-indigo-200">
                            <span class="font-medium text-gray-700">Jumlah Pekerja Packing:</span>
                            <span class="font-bold text-indigo-800"><?= $detail['pekerja_packing'] ?> orang</span>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded mt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-indigo-900">Waktu Packing Efektif:</span>
                                <span class="font-bold text-lg text-indigo-900"><?= number_format($estimasi['waktu_packing'], 2) ?> menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Final Summary -->
                <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg p-5 border-2 border-gray-300">
                    <h4 class="font-bold text-gray-900 text-lg mb-4">
                        <i class="fas fa-stopwatch mr-2"></i>
                        Ringkasan Total
                    </h4>
                    <div class="space-y-3 text-sm">
                        <!-- Rumus Total -->
                        <div class="bg-gray-200 p-3 rounded border-l-4 border-gray-500">
                            <div class="font-bold text-gray-900 mb-2">📐 Rumus Total:</div>
                            <div class="font-mono text-sm bg-white p-2 rounded space-y-1">
                                <div class="text-gray-800">Total Menit = Waktu Desain + Waktu Plat + Waktu Setup + Waktu Mesin + Waktu QC + Waktu Packing</div>
                                <div class="text-gray-800">Total Jam = Total Menit ÷ 60</div>
                                <div class="text-gray-800">Total Hari = Total Jam ÷ 8 (jam kerja per hari)</div>
                                <div class="text-gray-600 mt-1 text-xs border-t pt-1">
                                    <div><?= number_format($estimasi['waktu_desain'], 2) ?> + <?= number_format($estimasi['waktu_plat'], 2) ?> + <?= number_format($estimasi['waktu_total_setup'], 2) ?> + <?= number_format($estimasi['waktu_mesin'], 2) ?> + <?= number_format($estimasi['waktu_qc'], 2) ?> + <?= number_format($estimasi['waktu_packing'], 2) ?> = <?= number_format($estimasi['waktu_menit'], 2) ?> menit</div>
                                    <div><?= number_format($estimasi['waktu_menit'], 2) ?> ÷ 60 = <?= number_format($estimasi['waktu_jam'], 2) ?> jam</div>
                                    <div><?= number_format($estimasi['waktu_jam'], 2) ?> ÷ 8 = <?= number_format($estimasi['waktu_hari'], 2) ?> hari</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu Desain:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_desain'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu Plat:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_plat'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu Setup:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_total_setup'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu Mesin:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_mesin'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu QC:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_qc'], 2) ?> menit</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-300">
                            <span class="font-medium text-gray-700">Waktu Packing:</span>
                            <span class="font-bold text-gray-800"><?= number_format($estimasi['waktu_packing'], 2) ?> menit</span>
                        </div>
                        <div class="bg-gray-300 p-4 rounded mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-bold text-gray-900 text-lg">TOTAL WAKTU:</span>
                            </div>
                            <div class="flex justify-between items-center text-xl font-bold text-gray-900">
                                <span><?= number_format($estimasi['waktu_menit'], 2) ?> menit</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold text-gray-800 mt-1">
                                <span><?= number_format($estimasi['waktu_jam'], 2) ?> jam</span>
                            </div>
                            <div class="flex justify-between items-center text-2xl font-bold text-blue-600 mt-2">
                                <span><?= number_format($estimasi['waktu_hari'], 2) ?> HARI</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Parameters -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-cogs text-gray-600 mr-2"></i>
                Parameter Detail Estimasi
            </h3>
            <p class="text-sm text-gray-600 mt-1">Detail parameter yang digunakan dalam perhitungan estimasi</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Parameter</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Nilai</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Satuan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Waktu Desain -->
                    <tr class="bg-blue-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Waktu Desain</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_desain'], 2) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Waktu untuk proses desain</td>
                    </tr>
                    
                    <!-- Plat Parameters -->
                    <tr>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Waktu per Plat</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_per_plat'], 2) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Waktu pembuatan per plat</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Jumlah Halaman per Plat</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['jumlah_halaman_per_plat'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">halaman</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Kapasitas halaman per plat</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Jumlah Plat per Set</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['jumlah_plat_per_set'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">plat</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Plat yang dibutuhkan per set</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Total Jumlah Plat</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['jumlah_plat'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">plat</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Total plat yang dibutuhkan</td>
                    </tr>

                    <!-- Workforce Parameters -->
                    <tr class="bg-green-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Jumlah Desainer</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['jumlah_desainer'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">orang</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Parameter kustom tenaga kerja</td>
                    </tr>
                    <tr class="bg-green-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Pekerja QC</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['pekerja_qc'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">orang</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Parameter kustom tenaga kerja</td>
                    </tr>
                    <tr class="bg-green-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Pekerja Packing</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['pekerja_packing'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">orang</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Parameter kustom tenaga kerja</td>
                    </tr>

                    <!-- Machine Parameters -->
                    <tr class="bg-yellow-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Waktu Mesin per Eksemplar</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_mesin_per_eks'], 6) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Waktu mesin per unit produk</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Waktu Manual Hardcover</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_manual_hardcover'], 2) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Waktu manual untuk hardcover</td>
                    </tr>

                    <!-- QC & Packing Standards -->
                    <tr class="bg-purple-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Waktu Standar QC</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_standar_qc'], 3) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit/eksemplar</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Standar waktu QC per eksemplar</td>
                    </tr>
                    <tr class="bg-purple-25">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Waktu Standar Packing</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= number_format($detail['waktu_standar_packing'], 2) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">menit/box</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Standar waktu packing per box</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Kapasitas Box</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['kapasitas_box'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">eksemplar/box</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Kapasitas eksemplar per box</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">Jumlah Box</td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?= $detail['jumlah_box'] ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">box</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Total box yang dibutuhkan</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border">
        <div class="p-12 text-center">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Detail Parameter Tidak Tersedia</h3>
            <p class="text-gray-500">Detail parameter estimasi tidak ditemukan untuk estimasi ini.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_supervisor_produksi.php';
?>
