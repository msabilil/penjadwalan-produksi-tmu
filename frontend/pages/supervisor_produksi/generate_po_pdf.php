<?php
/**
 * Supervisor Produksi - Purchase Order PDF Generator
 * Generate PDF from Purchase Order data with production planning focus
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/estimasi_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication
check_authentication();
check_role(['supervisor produksi']);

// Get parameters
$pesanan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'po';

if ($pesanan_id <= 0) {
    http_response_code(400);
    echo 'Invalid pesanan ID';
    exit;
}

// Get pesanan data
$pesanan_result = ambil_pesanan_by_id($pesanan_id);
if (!$pesanan_result['success']) {
    http_response_code(404);
    echo 'Pesanan not found';
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

// Get estimasi data if exists
$estimasi = null;
$estimasi_result = ambil_estimasi_by_pesanan($pesanan_id);
if ($estimasi_result['success'] && !empty($estimasi_result['data'])) {
    $estimasi = $estimasi_result['data'];
}

// Generate HTML content for PDF
$html_content = generatePurchaseOrderHTML($pesanan, $desain, $estimasi);

// Check if this is a download request or display request
$is_download = isset($_GET['download']) && $_GET['download'] === '1';

if ($is_download) {
    // Set headers for HTML download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="PO-' . $pesanan['no'] . '-Production.html"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
} else {
    // Set headers for display in browser
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

// Output HTML with print styles
echo $html_content;

/**
 * Generate HTML content for Purchase Order with Production Planning
 */
function generatePurchaseOrderHTML($pesanan, $desain, $estimasi) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase Order - <?= htmlspecialchars($pesanan['no']) ?> - Production</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background: white;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .po-header {
                background: #16a34a;
                color: white;
                padding: 30px;
                text-align: center;
                margin-bottom: 30px;
            }
            
            .po-header h1 {
                font-size: 28px;
                margin-bottom: 10px;
            }
            
            .po-header h2 {
                font-size: 20px;
                margin-bottom: 15px;
                opacity: 0.9;
            }
            
            .po-number {
                font-size: 18px;
                font-weight: bold;
                background: rgba(255,255,255,0.1);
                padding: 10px;
                border-radius: 5px;
                margin-top: 15px;
            }
            
            .production-badge {
                background: #f59e0b;
                color: white;
                padding: 5px 15px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                margin-top: 10px;
                display: inline-block;
            }
            
            .po-section {
                margin-bottom: 25px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background: #fafafa;
            }
            
            .po-section h3 {
                color: #16a34a;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 15px;
                border-bottom: 2px solid #16a34a;
                padding-bottom: 8px;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-bottom: 25px;
            }
            
            .company-info {
                padding: 20px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            
            .company-info h4 {
                color: #16a34a;
                margin-bottom: 10px;
                font-size: 14px;
                font-weight: bold;
            }
            
            .company-name {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 8px;
            }
            
            .company-details {
                font-size: 14px;
                line-height: 1.4;
            }
            
            .po-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                background: white;
            }
            
            .po-table th {
                background: #f5f5f5;
                padding: 12px;
                border: 1px solid #ddd;
                text-align: left;
                font-weight: bold;
                font-size: 14px;
                width: 35%;
            }
            
            .po-table td {
                padding: 12px;
                border: 1px solid #ddd;
                font-size: 14px;
            }
            
            .order-details-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-top: 15px;
            }
            
            .order-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
                font-size: 14px;
            }
            
            .order-label {
                font-weight: 500;
                color: #555;
            }
            
            .order-value {
                font-weight: 600;
                color: #000;
            }
            
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
            }
            
            .status-estimated {
                background: #dbeafe;
                color: #1e40af;
            }
            
            .status-pending {
                background: #fef3c7;
                color: #d97706;
            }
            
            .estimation-section {
                background: #f0f9ff;
                border-left: 4px solid #0ea5e9;
            }
            
            .terms-list {
                list-style: disc;
                margin-left: 20px;
                font-size: 13px;
                line-height: 1.5;
            }
            
            .terms-list li {
                margin-bottom: 5px;
            }
            
            .signatures {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 40px;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #ddd;
            }
            
            .signature-box {
                text-align: center;
            }
            
            .signature-line {
                height: 60px;
                border-bottom: 1px solid #999;
                margin-bottom: 10px;
            }
            
            .signature-title {
                font-weight: bold;
                font-size: 14px;
            }
            
            .signature-subtitle {
                font-size: 12px;
                color: #666;
                margin-top: 2px;
            }
            
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 12px;
                color: #666;
            }
            
            @media print {
                body { margin: 0; }
                .container { padding: 0; }
                .po-section { break-inside: avoid; }
                .signatures { break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="po-header">
                <h1>PENERBIT TMU</h1>
                <h2>Purchase Order</h2>
                <div class="po-number">PO No: <?= htmlspecialchars($pesanan['no']) ?></div>
                <div class="production-badge">DOKUMEN PRODUKSI</div>
            </div>
            
            <!-- Company & Customer Info -->
            <div class="info-grid">
                <div class="company-info">
                    <h4>Dari:</h4>
                    <div class="company-name">PENERBIT TMU</div>
                    <div class="company-details">
                        Jl. Contoh Alamat No. 123<br>
                        Kota, Provinsi 12345<br>
                        Telp: (021) 1234-5678<br>
                        Email: info@tmu.ac.id
                    </div>
                </div>
                
                <div class="company-info">
                    <h4>Kepada:</h4>
                    <div class="company-name"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></div>
                    <div class="company-details">
                        <?= nl2br(htmlspecialchars($pesanan['alamat'])) ?><br>
                        Telp: <?= htmlspecialchars($pesanan['no_telepon']) ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="po-section">
                <h3>Detail Pesanan</h3>
                <div class="order-details-grid">
                    <div class="order-item">
                        <span class="order-label">No. Pesanan:</span>
                        <span class="order-value"><?= htmlspecialchars($pesanan['no']) ?></span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Tanggal Pesanan:</span>
                        <span class="order-value"><?= format_tanggal($pesanan['tanggal_pesanan']) ?></span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Jumlah:</span>
                        <span class="order-value"><?= number_format($pesanan['jumlah']) ?> eksemplar</span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Status Produksi:</span>
                        <span class="order-value">
                            <?php if ($estimasi): ?>
                                <span class="status-badge status-estimated">Sudah Diestimasi</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Menunggu Estimasi</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Production Planning Info -->
            <?php if ($estimasi): ?>
            <div class="po-section estimation-section">
                <h3>Informasi Estimasi Produksi</h3>
                <div class="order-details-grid">
                    <div class="order-item">
                        <span class="order-label">Waktu Estimasi:</span>
                        <span class="order-value"><?= number_format($estimasi['waktu_hari'], 2) ?> hari</span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Waktu Standar:</span>
                        <span class="order-value"><?= number_format($estimasi['waktu_jam'], 2) ?> jam</span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Tanggal Estimasi:</span>
                        <span class="order-value"><?= format_tanggal($estimasi['created_at']) ?></span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Status Estimasi:</span>
                        <span class="order-value">
                            <span class="status-badge status-estimated">Selesai</span>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Design Specifications -->
            <?php if ($desain): ?>
            <div class="po-section">
                <h3>Spesifikasi Desain</h3>
                <table class="po-table">
                    <tr><th>Nama Desain</th><td><?= htmlspecialchars($desain['nama']) ?></td></tr>
                    <tr><th>Jenis Desain</th><td><?= ucwords(str_replace('_', ' ', $desain['jenis_desain'])) ?></td></tr>
                    <tr><th>Jenis Produk</th><td><?= ucwords($desain['jenis_produk']) ?></td></tr>
                    <tr><th>Model Warna</th><td><?= strtoupper($desain['model_warna']) ?></td></tr>
                    <tr><th>Ukuran</th><td><?= htmlspecialchars($desain['ukuran']) ?></td></tr>
                    <tr><th>Halaman</th><td><?= $desain['halaman'] ?> halaman</td></tr>
                    <tr><th>Cover</th><td><?= ucwords($desain['jenis_cover']) ?></td></tr>
                    <tr><th>Laminasi</th><td><?= ucwords($desain['laminasi']) ?></td></tr>
                    <tr><th>Jilid</th><td><?= ucwords($desain['jilid']) ?></td></tr>
                    <tr><th>Kualitas Warna</th><td><?= ucwords($desain['kualitas_warna']) ?></td></tr>
                </table>
            </div>
            <?php else: ?>
            <div class="po-section">
                <h3>Spesifikasi Desain</h3>
                <p style="text-align: center; color: #666; padding: 30px;">
                    <strong>Desain belum dipilih atau belum tersedia</strong><br>
                    <small>Silakan pilih desain di halaman manajemen pesanan</small>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Additional Notes -->
            <?php if (!empty($pesanan['deskripsi'])): ?>
            <div class="po-section">
                <h3>Catatan Tambahan</h3>
                <p><?= nl2br(htmlspecialchars($pesanan['deskripsi'])) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Production Notes -->
            <div class="po-section">
                <h3>Catatan Produksi</h3>
                <ul class="terms-list">
                    <li>Estimasi waktu produksi berdasarkan spesifikasi desain dan jumlah pesanan</li>
                    <li>Jadwal produksi akan disusun setelah estimasi selesai dan disetujui</li>
                    <li>Proses produksi mengikuti urutan: Desain → Plat → Setup → Cetak → Laminasi → Jilid → QC → Packing</li>
                    <li>Perubahan spesifikasi setelah estimasi dapat mempengaruhi jadwal produksi</li>
                    <li>Koordinasi dengan tim desain dan operator mesin diperlukan untuk kelancaran produksi</li>
                </ul>
            </div>
            
            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">Supervisor Produksi</div>
                    <div class="signature-subtitle">Production Planning</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></div>
                    <div class="signature-subtitle">Customer Signature</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>Dokumen produksi dibuat pada <?= date('d/m/Y H:i') ?></p>
                <p>Supervisor Produksi - Penjadwalan Produksi TMU</p>
            </div>
        </div>
        
        <script>
            // Auto print when opened for print
            if (window.location.search.includes('print=1')) {
                window.onload = function() {
                    window.print();
                    // Close window after print dialog is closed
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                };
            }
            
            // Handle print completion
            window.onafterprint = function() {
                // Close window after printing
                window.close();
            };
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
