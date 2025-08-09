<?php
session_start();

// Jika sudah login, redirect ke beranda sesuai role
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'administrator':
            header('Location: frontend/pages/administrator/beranda.php');
            break;
        case 'staf penjualan':
            header('Location: frontend/pages/staf_penjualan/beranda.php');
            break;
        case 'manager penerbit':
            header('Location: frontend/pages/manager_penerbit/beranda.php');
            break;
        case 'supervisor produksi':
            header('Location: frontend/pages/supervisor_produksi/beranda.php');
            break;
        default:
            header('Location: penjadwalan-produksi-tmu/login.php');
    }
    exit();
}

// Jika belum login, redirect ke login
header('Location: login.php');
exit();
?>