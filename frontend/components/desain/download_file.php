<?php
/**
 * Download File Desain
 * Handle download file desain dari database
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/desain_functions.php';

// Check authentication and role
check_authentication();
check_role(['administrator']);

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

// Output file content (sudah menggunakan file_desain di fungsi backend)
echo $result['file_content'];
exit();
?>
