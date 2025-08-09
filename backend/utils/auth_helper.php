<?php
/**
 * Authentication Helper Functions
 * Handles user authentication and role-based access control
 * Designed for direct function call architecture without API layer
 */

session_start();

/**
 * Check if user is authenticated
 * Redirects to login if not authenticated
 */
function check_authentication() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
        header('Location: ../../auth/login.php');
        exit;
    }
    
    // Ensure both session variables are set for compatibility
    if (isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
        $_SESSION['id_user'] = $_SESSION['user_id'];
    } elseif (isset($_SESSION['id_user']) && !isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $_SESSION['id_user'];
    }
}

/**
 * Check if user has required role
 * @param array $allowed_roles Array of allowed roles
 */
function check_role($allowed_roles) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Get current user information
 * @return array User data
 */
function get_current_user_info() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

/**
 * Check if user has specific permission for a resource
 * @param string $resource Resource name (pesanan, desain, mesin, etc.)
 * @param string $action Action type (create, read, update, delete)
 * @return bool Permission status
 */
function has_permission($resource, $action) {
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Role-based permissions matrix
    $permissions = [
        'administrator' => [
            'users' => ['create', 'read', 'update', 'delete'],
            'desain' => ['create', 'read', 'update', 'delete'],
            'mesin' => ['create', 'read', 'update', 'delete'],
            'pesanan' => ['create', 'read', 'update', 'delete'],
            'estimasi' => ['create', 'read', 'update', 'delete'],
            'jadwal' => ['create', 'read', 'update', 'delete']
        ],
        'staf penjualan' => [
            'pesanan' => ['create', 'read', 'update']
        ],
        'manager penerbit' => [
            'pesanan' => ['create', 'read', 'update'],
            'desain' => ['create', 'read', 'update']
        ],
        'supervisor produksi' => [
            'pesanan' => ['read'],
            'estimasi' => ['create', 'read', 'update'],
            'jadwal' => ['create', 'read', 'update']
        ]
    ];
    
    return isset($permissions[$user_role][$resource]) && 
           in_array($action, $permissions[$user_role][$resource]);
}

/**
 * Require specific permission for a resource
 * Dies with 403 if permission not granted
 * @param string $resource Resource name
 * @param string $action Action type
 */
function require_permission($resource, $action) {
    if (!has_permission($resource, $action)) {
        header('HTTP/1.1 403 Forbidden');
        die("Access denied. You don't have permission to {$action} {$resource}.");
    }
}

/**
 * Login user and create session
 * @param array $user_data User data from database
 */
function login_user($user_data) {
    $_SESSION['user_id'] = $user_data['id_user'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['user_name'] = $user_data['nama'];
    $_SESSION['user_role'] = $user_data['role'];
    $_SESSION['email'] = $user_data['email'] ?? '';
    $_SESSION['login_time'] = time();
}

/**
 * Logout user and destroy session
 */
function logout_user() {
    session_unset();
    session_destroy();
    header('Location: http://localhost/penjadwalan-produksi-tmu/login.php');
    exit();
}

/**
 * Check if session is expired (optional security measure)
 * @param int $timeout Session timeout in seconds (default: 8 hours)
 * @return bool True if expired
 */
function is_session_expired($timeout = 28800) {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    return (time() - $_SESSION['login_time']) > $timeout;
}

/**
 * Regenerate session ID for security
 */
function regenerate_session() {
    session_regenerate_id(true);
}

/**
 * Get role-specific navigation items
 * @param string $role User role
 * @return array Navigation items
 */
function get_navigation_items($role) {
    $navigation = [
        'administrator' => [
            ['label' => 'Beranda', 'url' => '../pages/beranda.php', 'icon' => 'home'],
            ['label' => 'Data User', 'url' => '../pages/data_user.php', 'icon' => 'users'],
            ['label' => 'Data Desain', 'url' => '../pages/data_desain.php', 'icon' => 'design'],
            ['label' => 'Data Mesin', 'url' => '../pages/data_mesin.php', 'icon' => 'cog']
        ],
        'staf penjualan' => [
            ['label' => 'Beranda', 'url' => '../pages/beranda.php', 'icon' => 'home'],
            ['label' => 'Pesanan', 'url' => '../pages/pesanan.php', 'icon' => 'document']
        ],
        'manager penerbit' => [
            ['label' => 'Beranda', 'url' => '../pages/beranda.php', 'icon' => 'home'],
            ['label' => 'Pesanan', 'url' => '../pages/pesanan.php', 'icon' => 'document'],
            ['label' => 'Desain', 'url' => '../pages/desain.php', 'icon' => 'design']
        ],
        'supervisor produksi' => [
            ['label' => 'Pesanan', 'url' => '../pages/pesanan.php', 'icon' => 'document'],
            ['label' => 'Estimasi', 'url' => '../pages/estimasi.php', 'icon' => 'calculator'],
            ['label' => 'Jadwal', 'url' => '../pages/jadwal.php', 'icon' => 'calendar']
        ]
    ];
    
    return $navigation[$role] ?? [];
}

/**
 * Get role-specific beranda data
 * @param string $role User role
 * @return array Beranda configuration
 */
function get_role_beranda_config($role) {
    $configs = [
        'administrator' => [
            'title' => 'Administrator Beranda',
            'color' => 'blue',
            'widgets' => ['users', 'designs', 'machines', 'orders']
        ],
        'staf penjualan' => [
            'title' => 'Sales Beranda',
            'color' => 'green',
            'widgets' => ['orders', 'customers']
        ],
        'manager penerbit' => [
            'title' => 'Publisher Beranda',
            'color' => 'purple',
            'widgets' => ['orders', 'designs', 'projects']
        ],
        'supervisor produksi' => [
            'title' => 'Production Beranda',
            'color' => 'orange',
            'widgets' => ['schedules', 'estimations', 'production']
        ]
    ];
    
    return $configs[$role] ?? $configs['administrator'];
}
?>
