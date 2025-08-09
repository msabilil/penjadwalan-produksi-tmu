<?php
/**
 * Manager Penerbit - Data Desain Page (Komponen Version)
 * Design management page for manager penerbit role - Menggunakan komponen
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/desain_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Include komponen
require_once '../../components/desain/page_header.php';
require_once '../../components/desain/desain_table.php';
require_once '../../components/desain/desain_form.php';
require_once '../../components/desain/delete_form.php';

// Check authentication and role
check_authentication();
check_role(['manager penerbit']);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah':
                $data = [
                    'jenis_desain' => sanitize_input($_POST['jenis_desain']),
                    'nama' => sanitize_input($_POST['nama']),
                    'jenis_produk' => sanitize_input($_POST['jenis_produk']),
                    'model_warna' => sanitize_input($_POST['model_warna']),
                    'jumlah_warna' => intval($_POST['jumlah_warna']),
                    'sisi' => intval($_POST['sisi']),
                    'jenis_cover' => sanitize_input($_POST['jenis_cover']),
                    'laminasi' => sanitize_input($_POST['laminasi']),
                    'jilid' => sanitize_input($_POST['jilid']),
                    'kualitas_warna' => sanitize_input($_POST['kualitas_warna']),
                    'ukuran' => sanitize_input($_POST['ukuran']),
                    'halaman' => intval($_POST['halaman']),
                    'estimasi_waktu_desain' => floatval($_POST['estimasi_waktu_desain']),
                    'tanggal_selesai' => !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null
                ];
                
                // Handle file upload
                $file_data = isset($_FILES['file_cetak']) && $_FILES['file_cetak']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['file_cetak'] : null;
                
                $result = tambah_desain($data, $file_data);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: desain.php?action=added');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'edit':
                $id_desain = intval($_POST['id_desain']);
                $data = [
                    'jenis_desain' => sanitize_input($_POST['jenis_desain']),
                    'nama' => sanitize_input($_POST['nama']),
                    'jenis_produk' => sanitize_input($_POST['jenis_produk']),
                    'model_warna' => sanitize_input($_POST['model_warna']),
                    'jumlah_warna' => intval($_POST['jumlah_warna']),
                    'sisi' => intval($_POST['sisi']),
                    'jenis_cover' => sanitize_input($_POST['jenis_cover']),
                    'laminasi' => sanitize_input($_POST['laminasi']),
                    'jilid' => sanitize_input($_POST['jilid']),
                    'kualitas_warna' => sanitize_input($_POST['kualitas_warna']),
                    'ukuran' => sanitize_input($_POST['ukuran']),
                    'halaman' => intval($_POST['halaman']),
                    'estimasi_waktu_desain' => floatval($_POST['estimasi_waktu_desain']),
                    'tanggal_selesai' => !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null
                ];
                
                // Handle file upload
                $file_data = isset($_FILES['file_cetak']) && $_FILES['file_cetak']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['file_cetak'] : null;
                
                $result = update_desain($id_desain, $data, $file_data);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: desain.php?action=updated');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'hapus':
                $id_desain = intval($_POST['id_desain']);
                $result = hapus_desain($id_desain);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: desain.php?action=deleted');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
        }
    }
}

// Handle GET redirects with success messages
if (isset($_GET['action']) && isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear after displaying
}

// Get all designs
$desains_result = ambil_semua_desain();
$desains = $desains_result['success'] ? $desains_result['data'] : [];

// Define options for form fields
$jenis_desain_options = [
    'desain default' => 'Desain Default',
    'desain sederhana' => 'Desain Sederhana',
    'desain kompleks' => 'Desain Kompleks',
    'desain premium' => 'Desain Premium'
];

$jenis_produk_options = [
    'buku' => 'Buku',
    'majalah' => 'Majalah',
    'katalog' => 'Katalog',
    'kalender' => 'Kalender',
    'soal ujian' => 'Soal Ujian',
    'lembar jawaban ujian' => 'Lembar Jawaban Ujian'
];

$model_warna_options = [
    'fullcolor' => 'Full Color',
    'b/w' => 'Black & White',
    'dua warna' => 'Dua Warna'
];

$jenis_cover_options = [
    'softcover' => 'Soft Cover',
    'hardcover' => 'Hard Cover',
    'tidak' => 'Tidak Ada'
];

$laminasi_options = [
    'glossy' => 'Glossy',
    'doff' => 'Doff',
    'tidak' => 'Tidak Ada'
];

$jilid_options = [
    'lem' => 'Lem',
    'jahit' => 'Jahit',
    'spiral' => 'Spiral',
    'tidak' => 'Tidak Ada'
];

$kualitas_warna_options = [
    'tinggi' => 'Tinggi',
    'cukup' => 'Cukup'
];

// Prepare options array for components
$options_arrays = [
    'jenis_desain' => $jenis_desain_options,
    'jenis_produk' => $jenis_produk_options,
    'model_warna' => $model_warna_options,
    'jenis_cover' => $jenis_cover_options,
    'laminasi' => $laminasi_options,
    'jilid' => $jilid_options,
    'kualitas_warna' => $kualitas_warna_options
];

$page_title = 'Data Desain';

// Add CSS file for this page
$additional_css = ['assets/css/pages/manager_penerbit/desain.css'];

// Add JavaScript file for this page
$additional_js = ['assets/js/pages/manager_penerbit/desain.js'];

// Set SweetAlert messages using the layout system
if ($success_message) {
    $swal_success = $success_message;
}
if ($error_message) {
    $swal_error = $error_message;
}

ob_start();
?>

<!-- Page Content -->
<div class="p-6">
    <?php 
    // Render Header Component
    render_page_header('Data Desain', 'Kelola data desain untuk produksi percetakan'); 
    ?>

    <?php 
    // Render Table Component
    render_desain_table($desains); 
    ?>
</div>

<?php 
// Render Modal Tambah
render_desain_form('tambahModal', 'Tambah Desain Baru', 'tambah', $options_arrays);

// Render Modal Edit
render_desain_form('editModal', 'Edit Desain', 'edit', $options_arrays);

// Render Delete Form
render_delete_form();
?>

<?php
$content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_manager_penerbit.php';
?>
