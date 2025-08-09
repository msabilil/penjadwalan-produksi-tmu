<?php
/**
 * staf penjualan - Download File Desain
 * File download handler for design files
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/desain_functions.php';

// Check authentication and role
check_authentication();
check_role(['staf penjualan']);

// Get the design ID from URL parameter
$id_desain = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_desain <= 0) {
    http_response_code(400);
    die('ID desain tidak valid');
}

// Download file using the same function as manager penerbit
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
    // 'gif' => 'image/gif',
    // 'doc' => 'application/msword',
    // 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    // 'xls' => 'application/vnd.ms-excel',
    // 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    // 'ppt' => 'application/vnd.ms-powerpoint',
    // 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    // 'ai' => 'application/postscript',
    // 'psd' => 'application/octet-stream',
    // 'eps' => 'application/postscript',
    // 'zip' => 'application/zip',
    // 'rar' => 'application/x-rar-compressed'
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
exit;
?>
