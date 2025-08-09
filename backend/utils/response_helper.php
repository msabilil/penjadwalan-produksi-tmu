<?php
/**
 * Response helper untuk API endpoints
 */

/**
 * Mengirim response JSON dengan format standar
 * @param bool $success Status keberhasilan
 * @param string $message Pesan response
 * @param mixed $data Data yang akan dikirim (opsional)
 * @param int $http_code HTTP status code (default: 200)
 */
function send_json_response($success, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Mengirim response sukses
 * @param string $message Pesan sukses
 * @param mixed $data Data yang akan dikirim (opsional)
 */
function send_success_response($message, $data = null) {
    send_json_response(true, $message, $data, 200);
}

/**
 * Mengirim response error
 * @param string $message Pesan error
 * @param int $http_code HTTP status code (default: 400)
 * @param mixed $data Data tambahan (opsional)
 */
function send_error_response($message, $http_code = 400, $data = null) {
    send_json_response(false, $message, $data, $http_code);
}

/**
 * Mengirim response untuk data tidak ditemukan
 * @param string $message Pesan not found (opsional)
 */
function send_not_found_response($message = 'Data tidak ditemukan') {
    send_json_response(false, $message, null, 404);
}

/**
 * Mengirim response untuk method tidak diizinkan
 * @param array $allowed_methods Method yang diizinkan
 */
function send_method_not_allowed_response($allowed_methods = []) {
    $message = 'Method tidak diizinkan';
    if (!empty($allowed_methods)) {
        $message .= '. Method yang diizinkan: ' . implode(', ', $allowed_methods);
        header('Allow: ' . implode(', ', $allowed_methods));
    }
    send_json_response(false, $message, null, 405);
}

/**
 * Mengirim response untuk request yang tidak valid
 * @param string $message Pesan error (default: 'Request tidak valid')
 */
function send_bad_request_response($message = 'Request tidak valid') {
    send_json_response(false, $message, null, 400);
}

/**
 * Mengirim response untuk server error
 * @param string $message Pesan error (default: 'Terjadi kesalahan server')
 */
function send_server_error_response($message = 'Terjadi kesalahan server') {
    send_json_response(false, $message, null, 500);
}

/**
 * Mengirim response untuk unauthorized access
 * @param string $message Pesan unauthorized (default: 'Akses tidak diizinkan')
 */
function send_unauthorized_response($message = 'Akses tidak diizinkan') {
    send_json_response(false, $message, null, 401);
}

/**
 * Mengirim response untuk forbidden access
 * @param string $message Pesan forbidden (default: 'Akses ditolak')
 */
function send_forbidden_response($message = 'Akses ditolak') {
    send_json_response(false, $message, null, 403);
}

/**
 * Validasi request method
 * @param string|array $allowed_methods Method yang diizinkan
 * @return bool True jika method diizinkan
 */
function validate_request_method($allowed_methods) {
    if (is_string($allowed_methods)) {
        $allowed_methods = [$allowed_methods];
    }
    
    $current_method = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($current_method, $allowed_methods)) {
        send_method_not_allowed_response($allowed_methods);
        return false;
    }
    
    return true;
}

/**
 * Mengambil JSON input dari request body
 * @param bool $require_json Apakah JSON wajib ada (default: true)
 * @return array|null Data JSON yang sudah di-decode
 */
function get_json_input($require_json = true) {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        if ($require_json) {
            send_bad_request_response('JSON input diperlukan');
            return null;
        }
        return [];
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_bad_request_response('Format JSON tidak valid: ' . json_last_error_msg());
        return null;
    }
    
    return $data;
}

/**
 * Sanitize input data untuk mencegah XSS
 * @param array $data Data yang akan disanitize
 * @return array Data yang sudah disanitize
 */
function sanitize_api_input($data) {
    if (!is_array($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_api_input($value);
        } else {
            $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    }
    
    return $sanitized;
}

/**
 * Validasi parameter yang diperlukan dalam request
 * @param array $data Data input
 * @param array $required_fields Field yang diperlukan
 * @return bool True jika semua field ada
 */
function validate_required_fields($data, $required_fields) {
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        send_bad_request_response('Field berikut diperlukan: ' . implode(', ', $missing_fields));
        return false;
    }
    
    return true;
}

/**
 * Validasi parameter numerik
 * @param mixed $value Nilai yang akan divalidasi
 * @param string $field_name Nama field (untuk pesan error)
 * @param bool $allow_zero Apakah nilai 0 diizinkan (default: false)
 * @return bool True jika valid
 */
function validate_numeric_field($value, $field_name, $allow_zero = false) {
    if (!is_numeric($value)) {
        send_bad_request_response("$field_name harus berupa angka");
        return false;
    }
    
    if (!$allow_zero && $value <= 0) {
        send_bad_request_response("$field_name harus lebih dari 0");
        return false;
    }
    
    return true;
}

/**
 * Set CORS headers untuk API
 */
function set_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * Format data untuk pagination
 * @param array $data Data array
 * @param int $page Halaman saat ini
 * @param int $limit Batas data per halaman
 * @param int $total Total data
 * @return array Data dengan informasi pagination
 */
function format_pagination_response($data, $page, $limit, $total) {
    $total_pages = ceil($total / $limit);
    
    return [
        'items' => $data,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => (int)$limit,
            'total_items' => (int)$total,
            'total_pages' => (int)$total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ];
}

/**
 * Validasi dan sanitize parameter pagination
 * @return array Parameter page dan limit yang sudah divalidasi
 */
function get_pagination_params() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Validasi page
    if ($page < 1) {
        $page = 1;
    }
    
    // Validasi limit (maksimal 100 untuk mencegah overload)
    if ($limit < 1) {
        $limit = 10;
    } elseif ($limit > 100) {
        $limit = 100;
    }
    
    return [$page, $limit];
}

/**
 * Mengirim response untuk resource yang berhasil dibuat
 * @param string $message Pesan sukses
 * @param mixed $data Data yang akan dikirim (optional)
 */
function send_created_response($message, $data = null) {
    send_json_response(true, $message, $data, 201);
}

/**
 * Mengirim response internal server error (500)
 * @param string $message Pesan error
 * @param mixed $data Data tambahan (optional)
 */
function send_internal_error_response($message, $data = null) {
    send_json_response(false, $message, $data, 500);
}
?>
