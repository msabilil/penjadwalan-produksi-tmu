<?php
// Database Configuration Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'penjadwalan_produksi_tmu');
define('DB_USER', 'root'); // Sesuaikan dengan kredensial database Anda
define('DB_PASS', ''); // Sesuaikan dengan kredensial database Anda
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');
define('MENIT_OPERASIONAL', 480); // 8 jam = 480 menit per hari

// Default Values for Estimasi Calculations
define('WAKTU_PER_PLAT_DEFAULT', 15.0);
define('WAKTU_MANUAL_HARDCOVER_DEFAULT', 120.0);
define('WAKTU_STANDAR_QC_DEFAULT', 0.5);
define('WAKTU_STANDAR_PACKING_DEFAULT', 5.0);
define('JUMLAH_HALAMAN_PER_PLAT_DEFAULT', 8);
define('KAPASITAS_BOX_DEFAULT', 40);

// User Roles
define('ROLE_ADMINISTRATOR', 'administrator');
define('ROLE_STAF_PENJUALAN', 'staf penjualan');
define('ROLE_MANAJER_PENERBIT', 'manager penerbit');
define('ROLE_SUPERVISOR_PRODUKSI', 'supervisor produksi');

// Status Constants
define('STATUS_TERJADWAL', 'terjadwal');
define('STATUS_DALAM_PROSES', 'dalam proses');
define('STATUS_SELESAI', 'selesai');
define('STATUS_TERLAMBAT', 'terlambat');
define('STATUS_SELESAI_LEBIH_CEPAT', 'selesai lebih cepat');

// Proses Produksi
define('PROSES_DESAIN', 'desain');
define('PROSES_PLAT', 'plat');
define('PROSES_SETUP', 'setup');
define('PROSES_CETAK', 'cetak');
define('PROSES_LAMINASI', 'laminasi');
define('PROSES_FINISHING', 'finishing');
define('PROSES_QC', 'qc');
define('PROSES_PACKING', 'packing');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);
?>
