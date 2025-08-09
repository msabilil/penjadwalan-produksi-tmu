<?php
/**
 * Helper functions untuk berbagai keperluan aplikasi
 */

/**
 * Menghasilkan ID unik untuk berbagai keperluan
 * @param string $prefix Prefix untuk ID
 * @return string ID yang dihasilkan
 */
function generate_unique_id($prefix = '') {
    return $prefix . date('YmdHis') . rand(1000, 9999);
}

/**
 * Format tanggal sesuai dengan format Indonesia
 * @param string $date Tanggal dalam format MySQL
 * @param string $format Format output (default: 'd/m/Y')
 * @return string Tanggal yang diformat
 */
function format_tanggal($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime sesuai dengan format Indonesia
 * @param string $datetime Datetime dalam format MySQL
 * @param string $format Format output (default: 'd/m/Y H:i')
 * @return string Datetime yang diformat
 */
function format_datetime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Konversi menit ke format jam:menit
 * @param float $menit Jumlah menit
 * @return string Format jam:menit
 */
function menit_ke_jam($menit) {
    if ($menit <= 0) return '0:00';
    
    $jam = floor($menit / 60);
    $sisa_menit = $menit % 60;
    
    return sprintf("%d:%02d", $jam, $sisa_menit);
}

/**
 * Konversi jam ke menit
 * @param float $jam Jumlah jam
 * @return float Jumlah menit
 */
function jam_ke_menit($jam) {
    return $jam * 60;
}

/**
 * Konversi hari ke menit (8 jam kerja per hari)
 * @param float $hari Jumlah hari
 * @return float Jumlah menit
 */
function hari_ke_menit($hari) {
    return $hari * MENIT_OPERASIONAL;
}

/**
 * Sanitize input untuk mencegah XSS
 * @param string $input Input yang akan disanitize
 * @return string Input yang sudah disanitize
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate nomor urut otomatis
 * @param string $prefix Prefix nomor
 * @param int $length Panjang nomor urut
 * @return string Nomor yang dihasilkan
 */
function generate_nomor_urut($prefix, $length = 4) {
    $tanggal = date('Ymd');
    $random = str_pad(rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    return $prefix . $tanggal . $random;
}

/**
 * Mengecek apakah string kosong atau null
 * @param mixed $value Nilai yang akan dicek
 * @return bool True jika kosong
 */
function is_empty($value) {
    return empty($value) || trim($value) === '';
}

/**
 * Mengkonversi array ke format JSON yang aman
 * @param array $data Data yang akan dikonversi
 * @return string JSON string
 */
function safe_json_encode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Log aktivitas sistem
 * @param string $message Pesan log
 * @param string $level Level log (INFO, WARNING, ERROR)
 */
function log_activity($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    $log_file = __DIR__ . '/../logs/activity.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Menghitung selisih waktu dalam menit
 * @param string $start_time Waktu mulai (YYYY-MM-DD HH:MM:SS)
 * @param string $end_time Waktu selesai (YYYY-MM-DD HH:MM:SS)
 * @return float Selisih dalam menit
 */
function hitung_selisih_menit($start_time, $end_time) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    
    return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i + ($interval->s / 60);
}

/**
 * Mengecek apakah tanggal valid
 * @param string $date Tanggal dalam format YYYY-MM-DD
 * @return bool True jika valid
 */
function is_valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Mengecek apakah datetime valid
 * @param string $datetime Datetime dalam format YYYY-MM-DD HH:MM:SS
 * @return bool True jika valid
 */
// function is_valid_datetime($datetime) {
//     $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
//     return $d && $d->format('Y-m-d H:i:s') === $datetime;
// }

?>
