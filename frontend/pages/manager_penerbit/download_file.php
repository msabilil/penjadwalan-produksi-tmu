<?php
/**
 * Download File - Manager Penerbit
 * Handle download file desain dan Purchase Order
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/pesanan_functions.php';

// Check authentication and role
check_authentication();
check_role(['manager penerbit']);

$type = $_GET['type'] ?? 'desain'; // default to desain for backward compatibility

if ($type === 'po') {
    // Download Purchase Order
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
    
    // Generate PO content (simple text format for now)
    $po_content = "PURCHASE ORDER\n";
    $po_content .= "=================\n\n";
    $po_content .= "No. Pesanan: " . $pesanan['no'] . "\n";
    $po_content .= "Tanggal: " . date('d/m/Y', strtotime($pesanan['tanggal_pesanan'])) . "\n";
    $po_content .= "Pemesan: " . $pesanan['nama_pemesan'] . "\n";
    $po_content .= "Telepon: " . $pesanan['no_telepon'] . "\n";
    $po_content .= "Alamat: " . $pesanan['alamat'] . "\n\n";
    $po_content .= "Detail Pesanan:\n";
    $po_content .= "Jumlah: " . number_format($pesanan['jumlah']) . " eksemplar\n";
    if (!empty($pesanan['nama_desain'])) {
        $po_content .= "Desain: " . $pesanan['nama_desain'] . "\n";
    }
    if (!empty($pesanan['deskripsi'])) {
        $po_content .= "Deskripsi: " . $pesanan['deskripsi'] . "\n";
    }
    $po_content .= "\n\nTerima kasih atas pesanan Anda.\n";
    
    $file_name = "PO_" . $pesanan['no'] . "_" . date('Y-m-d') . ".txt";
    
    // Set headers for PO download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . strlen($po_content));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output PO content
    echo $po_content;
    exit();
    
} else {
    // Download Design File (existing functionality)
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        die('ID desain tidak valid');
    }
    
    $id_desain = intval($_GET['id']);
    
    // Download file
    $result = download_file_desain($id_desain);
    
    if (!$result['success']) {
        http_response_code(404);
        die($result['message']);
    }
    
    // Determine content type based on file extension
    $file_name = $result['file_name'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $content_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        // 'ai' => 'application/postscript',
        // 'psd' => 'application/octet-stream',
        // 'eps' => 'application/postscript'
    ];
    
    $content_type = $content_types[$file_extension] ?? 'application/octet-stream';
    
    // Set headers for file download
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . strlen($result['file_content']));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file content (menggunakan file_desain dari database)
    echo $result['file_content'];
    exit();
}
?>
