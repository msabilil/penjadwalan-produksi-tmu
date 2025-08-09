<?php
/**
 * Administrator - Data Mesin Page (Komponen Version)
 * Machine management page for administrator role - Menggunakan komponen
 */

require_once '../../../backend/utils/auth_helper.php';
require_once '../../../backend/functions/mesin_functions.php';
require_once '../../../backend/functions/helper_functions.php';

// Include komponen
require_once '../../components/mesin/page_header.php';
require_once '../../components/mesin/mesin_table.php';
require_once '../../components/mesin/mesin_form.php';
require_once '../../components/mesin/delete_form.php';

// Check authentication and role
check_authentication();
check_role(['administrator']);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah':
                $data = [
                    'nama_mesin' => sanitize_input($_POST['nama_mesin']),
                    'urutan_proses' => intval($_POST['urutan_proses']),
                    'kapasitas' => intval($_POST['kapasitas']),
                    'waktu_setup' => intval($_POST['waktu_setup']),
                    'waktu_mesin_per_eks' => floatval($_POST['waktu_mesin_per_eks']),
                    'menit_operasional' => intval($_POST['menit_operasional'])
                ];
                
                $result = tambah_mesin($data);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: data_mesin.php?action=added');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'edit':
                $id_mesin = intval($_POST['id_mesin']);
                $data = [
                    'nama_mesin' => sanitize_input($_POST['nama_mesin']),
                    'urutan_proses' => intval($_POST['urutan_proses']),
                    'kapasitas' => intval($_POST['kapasitas']),
                    'waktu_setup' => intval($_POST['waktu_setup']),
                    'waktu_mesin_per_eks' => floatval($_POST['waktu_mesin_per_eks']),
                    'menit_operasional' => intval($_POST['menit_operasional'])
                ];
                
                $result = update_mesin($id_mesin, $data);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: data_mesin.php?action=updated');
                    exit();
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'hapus':
                $id_mesin = intval($_POST['id_mesin']);
                $result = hapus_mesin($id_mesin);
                if ($result['success']) {
                    // Use PRG pattern to prevent duplicate submission
                    $_SESSION['success_message'] = $result['message'];
                    header('Location: data_mesin.php?action=deleted');
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

// Get all mesin data
$mesin_result = ambil_semua_mesin();
$mesins = $mesin_result['success'] ? $mesin_result['data'] : [];

// Define urutan proses options
$urutan_proses_options = [
    1 => 'Desain',
    2 => 'Plat',
    3 => 'Setup',
    4 => 'Cetak',
    5 => 'Laminasi',
    6 => 'Jilid',
    7 => 'QC',
    8 => 'Packing'
];

$page_title = 'Data Mesin';

// Add CSS file for this page
$additional_css = ['assets/css/pages/administrator/data_mesin.css'];

// Add JavaScript file for this page
$additional_js = ['assets/js/pages/administrator/data_mesin.js'];

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
    render_page_header_mesin('Data Mesin', 'Kelola informasi mesin produksi dan pengaturan kapasitas untuk optimalisasi produksi'); 
    ?>

    <?php 
    // Render Table Component
    render_mesin_table($mesins); 
    ?>
</div>

<?php 
// Render Form Modal
render_mesin_form($urutan_proses_options);

// Render Delete Form
render_delete_form();
?>

<?php
$page_content = ob_get_clean();

// Include the layout
include '../../layouts/sidebar_administrator.php';
?>
