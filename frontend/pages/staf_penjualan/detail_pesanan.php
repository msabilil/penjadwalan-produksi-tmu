<?php
/**
 * Staf Penjualan - Detail Pesanan Page
 * Purchase Order detail view with download functionality
 */

// Authentication
require_once '../../../backend/utils/auth_helper.php';
check_authentication();
check_role(['staf penjualan']);

// Include required functions
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Get pesanan ID from URL
$id_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pesanan <= 0) {
    header('Location: pesanan.php?error=' . urlencode('ID pesanan tidak valid'));
    exit;
}

// Get pesanan data
$pesanan_result = ambil_pesanan_by_id($id_pesanan);
if (!$pesanan_result['success']) {
    header('Location: pesanan.php?error=' . urlencode('Pesanan tidak ditemukan'));
    exit;
}

$pesanan = $pesanan_result['data'];

// Get desain data if exists
$desain = null;
if ($pesanan['id_desain']) {
    $desain_result = ambil_desain_by_id($pesanan['id_desain']);
    if ($desain_result['success']) {
        $desain = $desain_result['data'];
    }
}

// Set page variables
$page_title = 'Detail Pesanan - ' . $pesanan['no'];
$page_description = 'Purchase Order dan detail lengkap pesanan';

// Start output buffering to capture content
ob_start();
?>

<link rel="stylesheet" href="../../assets/css/pages/staf_penjualan/detail_pesanan.css">

<div class="p-6">
    <!-- Back Navigation -->
    <div class="mb-6 no-print">
        <a href="pesanan.php" class="back-button">
            <i class="fas fa-arrow-left mr-2"></i>
            Kembali ke Daftar Pesanan
        </a>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons-container no-print">
        <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
        <div class="action-buttons">
            <button onclick="printPO(<?= $id_pesanan ?>)" class="action-button print">
                <i class="fas fa-print"></i>
                <span>Print</span>
            </button>
            <!-- <a href="generate_po_pdf.php?id=<?= $id_pesanan ?>&download=1" class="action-button download">
                <i class="fas fa-download"></i>
                <span>Download HTML</span>
            </a> -->
        </div>
    </div>

    <!-- Purchase Order -->
    <div class="purchase-order rounded-lg">
        <!-- Header -->
        <div class="po-header">
            <div class="flex items-center justify-center mb-4">
                <img src="../../../frontend/assets/images/tmu.webp" 
                     alt="TMU Logo" 
                     class="w-16 h-16 object-contain mr-4"
                     onerror="this.style.display='none'">
                <div>
                    <h1 class="text-3xl font-bold">PENERBIT TMU</h1>
                    <p class="text-lg opacity-90">Purchase Order</p>
                </div>
            </div>
            <div class="text-xl font-bold">
                PO No: <?= htmlspecialchars($pesanan['no']) ?>
            </div>
        </div>

        <div class="po-content">
            <!-- Company & Customer Info -->
            <div class="po-info-grid mb-6">
                <!-- Company Info -->
                <div class="po-section">
                    <h3>Dari:</h3>
                    <div class="space-y-2">
                        <div class="font-bold text-lg">PENERBIT TMU</div>
                        <div>Jl. Contoh Alamat No. 123</div>
                        <div>Kota, Provinsi 12345</div>
                        <div>Telp: (021) 1234-5678</div>
                        <div>Email: info@tmu.ac.id</div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="po-section">
                    <h3>Kepada:</h3>
                    <div class="space-y-2">
                        <div class="font-bold text-lg"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></div>
                        <div><?= nl2br(htmlspecialchars($pesanan['alamat'])) ?></div>
                        <div>Telp: <?= htmlspecialchars($pesanan['no_telepon']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="po-section">
                <h3>Detail Pesanan</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="po-info-item">
                        <span class="po-info-label">No. Pesanan:</span>
                        <span class="po-info-value"><?= htmlspecialchars($pesanan['no']) ?></span>
                    </div>
                    <div class="po-info-item">
                        <span class="po-info-label">Tanggal Pesanan:</span>
                        <span class="po-info-value"><?= format_tanggal($pesanan['tanggal_pesanan']) ?></span>
                    </div>
                    <div class="po-info-item">
                        <span class="po-info-label">Jumlah:</span>
                        <span class="po-info-value"><?= number_format($pesanan['jumlah']) ?> eksemplar</span>
                    </div>
                    <div class="po-info-item">
                        <span class="po-info-label">Status:</span>
                        <span class="po-info-value">
                            <?php if ($pesanan['id_desain']): ?>
                                <span class="text-green-600 font-medium">Desain Tersedia</span>
                            <?php else: ?>
                                <span class="text-orange-600 font-medium">Menunggu Desain</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Design Specifications -->
            <?php if ($desain): ?>
            <div class="po-section">
                <h3>Spesifikasi Desain</h3>
                <table class="po-table">
                    <tr>
                        <th>Nama Desain</th>
                        <td><?= htmlspecialchars($desain['nama']) ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Desain</th>
                        <td><?= ucwords(str_replace('_', ' ', $desain['jenis_desain'])) ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Produk</th>
                        <td><?= ucwords($desain['jenis_produk']) ?></td>
                    </tr>
                    <tr>
                        <th>Model Warna</th>
                        <td><?= strtoupper($desain['model_warna']) ?></td>
                    </tr>
                    <tr>
                        <th>Ukuran</th>
                        <td><?= htmlspecialchars($desain['ukuran']) ?></td>
                    </tr>
                    <tr>
                        <th>Halaman</th>
                        <td><?= $desain['halaman'] ?> halaman</td>
                    </tr>
                    <tr>
                        <th>Cover</th>
                        <td><?= ucwords($desain['jenis_cover']) ?></td>
                    </tr>
                    <tr>
                        <th>Laminasi</th>
                        <td><?= ucwords($desain['laminasi']) ?></td>
                    </tr>
                    <tr>
                        <th>Jilid</th>
                        <td><?= ucwords($desain['jilid']) ?></td>
                    </tr>
                    <tr>
                        <th>Kualitas Warna</th>
                        <td><?= ucwords($desain['kualitas_warna']) ?></td>
                    </tr>
                </table>
            </div>
            <?php else: ?>
            <div class="po-section">
                <h3>Spesifikasi Desain</h3>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2 block text-orange-500"></i>
                    <p>Desain belum dipilih atau belum tersedia</p>
                    <p class="text-sm">Silakan pilih desain di halaman manajemen pesanan</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Additional Notes -->
            <?php if (!empty($pesanan['deskripsi'])): ?>
            <div class="po-section">
                <h3>Catatan Tambahan</h3>
                <p class="text-gray-700"><?= nl2br(htmlspecialchars($pesanan['deskripsi'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Terms & Conditions -->
            <div class="po-section">
                <h3>Syarat dan Ketentuan</h3>
                <ul class="terms-list">
                    <li>Pesanan akan diproses setelah konfirmasi desain dan pembayaran</li>
                    <li>Waktu pengerjaan akan dihitung setelah desain final disetujui</li>
                    <li>Perubahan desain setelah proses produksi dimulai akan dikenakan biaya tambahan</li>
                    <li>Pembayaran dapat dilakukan secara bertahap sesuai kesepakatan</li>
                    <li>Barang yang sudah jadi tidak dapat dikembalikan kecuali ada kesalahan dari pihak percetakan</li>
                </ul>
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">Penerbit TMU</div>
                    <div class="signature-subtitle">Authorized Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></div>
                    <div class="signature-subtitle">Customer Signature</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-info">
                <p>Purchase Order ini dibuat pada <?= date('d/m/Y H:i') ?></p>
                <p>Dokumen ini sah tanpa tanda tangan basah</p>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/pages/staf_penjualan/detail_pesanan.js"></script>

<?php
$content = ob_get_clean();

// Include layout
include '../../layouts/sidebar_staf_penjualan.php';
?>
