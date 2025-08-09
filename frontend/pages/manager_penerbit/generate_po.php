<?php
/**
 * Generate Purchase Order PDF - Manager Penerbit
 * Generate professional Purchase Order document for printing/download
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/pesanan_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Check authentication and role
check_authentication();
check_role(['manager penerbit']);

// Get pesanan ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID pesanan tidak valid');
}

$id_pesanan = intval($_GET['id']);

// Get pesanan data
$pesanan_result = ambil_pesanan_by_id($id_pesanan);
if (!$pesanan_result['success']) {
    http_response_code(404);
    die('Pesanan tidak ditemukan');
}

$pesanan = $pesanan_result['data'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - <?= htmlspecialchars($pesanan['no']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #16a34a;
            padding-bottom: 25px;
            background: linear-gradient(135deg, #f0f9f4 0%, #ffffff 100%);
            padding: 30px 20px 25px;
            border-radius: 8px 8px 0 0;
        }
        
        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .company-tagline {
            font-size: 16px;
            color: #666;
            font-style: italic;
        }
        
        .po-title {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
            margin: 30px 0;
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            color: white;
            border-radius: 8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .content-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 20px;
            border-bottom: 2px solid #16a34a;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .section-title::before {
            content: "📋";
            margin-right: 10px;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 12px;
            align-items: start;
        }
        
        .info-label {
            font-weight: bold;
            color: #4b5563;
            padding: 8px 0;
        }
        
        .info-value {
            color: #1f2937;
            padding: 8px 0;
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        
        .order-details {
            border: 3px solid #16a34a;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
            position: relative;
        }
        
        .order-details::before {
            content: "";
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            z-index: -1;
            border-radius: 12px;
        }
        
        .detail-title {
            font-size: 22px;
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .highlight-box {
            background: #16a34a;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
            font-size: 18px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 2px solid #e5e7eb;
            padding-top: 30px;
            background: #f9fafb;
            padding: 30px;
            border-radius: 8px;
        }
        
        .footer strong {
            color: #16a34a;
            font-size: 16px;
        }
        
        .generated-info {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #16a34a;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.4);
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 10px;
                max-width: none;
            }
            .print-button { 
                display: none; 
            }
            .header {
                border-radius: 0;
                margin-bottom: 20px;
            }
            .po-title {
                margin: 20px 0;
            }
            .content-section {
                margin-bottom: 15px;
                padding: 15px;
            }
            .order-details {
                margin: 20px 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button">🖨️ Print PO</button>
    
    <div class="header">
        <div class="company-name">TMU PRINTING</div>
        <div class="company-tagline">Solusi Terpadu Percetakan Profesional</div>
    </div>

    <div class="po-title">PURCHASE ORDER</div>

    <div class="content-section">
        <div class="section-title">Informasi Purchase Order</div>
        <div class="info-grid">
            <div class="info-label">No. PO:</div>
            <div class="info-value"><strong><?= htmlspecialchars($pesanan['no']) ?></strong></div>
            
            <div class="info-label">Tanggal:</div>
            <div class="info-value"><?= date('d/m/Y', strtotime($pesanan['tanggal_pesanan'])) ?></div>
            
            <div class="info-label">Status:</div>
            <div class="info-value">Aktif</div>
        </div>
    </div>
    
    <div class="content-section">
        <div class="section-title">Informasi Pemesan</div>
        <div class="info-grid">
            <div class="info-label">Nama:</div>
            <div class="info-value"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></div>
            
            <div class="info-label">Telepon:</div>
            <div class="info-value"><?= htmlspecialchars($pesanan['no_telepon']) ?></div>
            
            <div class="info-label">Alamat:</div>
            <div class="info-value"><?= htmlspecialchars($pesanan['alamat']) ?></div>
        </div>
    </div>

    <div class="order-details">
        <div class="detail-title">📦 DETAIL PESANAN</div>
        
        <div class="highlight-box">
            Jumlah: <?= number_format($pesanan['jumlah']) ?> eksemplar
        </div>
        
        <div class="info-grid">
            <?php if (!empty($pesanan['nama_desain'])): ?>
            <div class="info-label">Desain:</div>
            <div class="info-value"><?= htmlspecialchars($pesanan['nama_desain']) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($pesanan['deskripsi'])): ?>
            <div class="info-label">Deskripsi:</div>
            <div class="info-value"><?= nl2br(htmlspecialchars($pesanan['deskripsi'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <p><strong>Terima kasih atas kepercayaan Anda kepada TMU Printing!</strong></p>
        <p>Hubungi kami untuk informasi lebih lanjut mengenai status pesanan Anda</p>
        <p style="margin-top: 15px; font-size: 13px;">📞 Telp: (021) 1234-5678 | 📧 Email: info@tmuprinting.com</p>
    </div>

    <div class="generated-info">
        Dokumen ini dibuat otomatis pada: <?= date('d/m/Y H:i:s') ?> WIB
    </div>

    <script>
        // Auto print when page loads (optional - dapat diaktifkan jika diperlukan)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // }
        
        // Add keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
