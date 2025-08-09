<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel users
 */

/**
 * Menambah user baru
 * @param array $data Data user yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_user($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_user($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah username sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user baru
        $query = "INSERT INTO users (username, password, nama, role, no_telepon) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['username'],
            $hashed_password,
            $data['nama'],
            $data['role'],
            $data['no_telepon'] ?? null
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        log_activity("User baru ditambahkan: {$data['username']} (ID: $user_id)");
        
        return [
            'success' => true, 
            'message' => 'User berhasil ditambahkan',
            'data' => ['id_user' => $user_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan user'];
    }
}

/**
 * Mengambil semua data user
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data users
 */
function ambil_semua_user($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_user, username, nama, role, no_telepon FROM users ORDER BY nama";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $users
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data user'];
    }
}

/**
 * Mengambil data user berdasarkan ID
 * @param int $id_user ID user
 * @return array Data user
 */
function ambil_user_by_id($id_user) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_user, username, nama, role, no_telepon FROM users WHERE id_user = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_user]);
        $user = $stmt->fetch();
        
        close_database($pdo);
        
        if ($user) {
            return [
                'success' => true,
                'data' => $user
            ];
        } else {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil user by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data user'];
    }
}

/**
 * Mengambil data user berdasarkan username
 * @param string $username Username user
 * @return array Data user
 */
function ambil_user_by_username($username) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_user, username, password, nama, role, no_telepon FROM users WHERE username = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        close_database($pdo);
        
        if ($user) {
            return [
                'success' => true,
                'data' => $user
            ];
        } else {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil user by username: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data user'];
    }
}

/**
 * Update data user
 * @param int $id_user ID user yang akan diupdate
 * @param array $data Data user yang baru
 * @return array Status operasi
 */
function update_user($id_user, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_user($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah user ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_user = ?");
        $stmt->execute([$id_user]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Cek apakah username sudah digunakan user lain
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id_user != ?");
        $stmt->execute([$data['username'], $id_user]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Siapkan query update
        $set_clauses = [];
        $params = [];
        
        $set_clauses[] = "username = ?";
        $params[] = $data['username'];
        
        $set_clauses[] = "nama = ?";
        $params[] = $data['nama'];
        
        $set_clauses[] = "role = ?";
        $params[] = $data['role'];
        
        $set_clauses[] = "no_telepon = ?";
        $params[] = $data['no_telepon'] ?? null;
        
        // Update password jika ada
        if (!empty($data['password'])) {
            $set_clauses[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $params[] = $id_user;
        
        $query = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id_user = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        close_database($pdo);
        
        log_activity("User diupdate: {$data['username']} (ID: $id_user)");
        
        return ['success' => true, 'message' => 'User berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate user'];
    }
}

/**
 * Hapus user
 * @param int $id_user ID user yang akan dihapus
 * @return array Status operasi
 */
function hapus_user($id_user) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah user memiliki pesanan aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_user = ?");
        $stmt->execute([$id_user]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak dapat dihapus karena memiliki pesanan'];
        }
        
        // Ambil data user sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id_user = ?");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch();
        
        if (!$user) {
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Hapus user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
        $stmt->execute([$id_user]);
        
        close_database($pdo);
        
        log_activity("User dihapus: {$user['username']} (ID: $id_user)");
        
        return ['success' => true, 'message' => 'User berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus user'];
    }
}

/**
 * Mengambil jumlah total user
 * @return int Jumlah user
 */
function hitung_total_user() {
    $pdo = connect_database();
    if (!$pdo) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        close_database($pdo);
        return $total;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung total user: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Cari user berdasarkan nama atau username
 * @param string $keyword Kata kunci pencarian
 * @return array Data users yang ditemukan
 */
function cari_user($keyword) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_user, username, nama, role, no_telepon 
                  FROM users 
                  WHERE nama LIKE ? OR username LIKE ? 
                  ORDER BY nama";
        
        $search_term = "%$keyword%";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_term, $search_term]);
        $users = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $users
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cari user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mencari user'];
    }
}

/**
 * Verifikasi login user
 * @param string $username Username
 * @param string $password Password
 * @return array Status login dan data user
 */
function verifikasi_login($username, $password) {
    $user_result = ambil_user_by_username($username);
    
    if (!$user_result['success']) {
        return ['success' => false, 'message' => 'Username tidak ditemukan'];
    }
    
    $user = $user_result['data'];
    
    if (password_verify($password, $user['password'])) {
        // Hapus password dari data yang dikembalikan
        unset($user['password']);
        
        log_activity("User login: $username");
        
        return [
            'success' => true,
            'message' => 'Login berhasil',
            'data' => $user
        ];
    } else {
        log_activity("Login gagal untuk user: $username", 'WARNING');
        return ['success' => false, 'message' => 'Password salah'];
    }
}

/**
 * Mengambil user berdasarkan role
 * @param string $role Role user
 * @return array Data users dengan role tertentu
 */
function ambil_user_by_role($role) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_user, username, nama, role, no_telepon 
                  FROM users 
                  WHERE role = ? 
                  ORDER BY nama";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$role]);
        $users = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $users
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil user by role: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data user'];
    }
}

/**
 * Hitung statistik user berdasarkan role
 * @return array Statistik user per role
 */
function hitung_statistik_user() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT role, COUNT(*) as jumlah 
            FROM users 
            GROUP BY role
            ORDER BY role
        ");
        $stmt->execute();
        $by_role = $stmt->fetchAll();
        
        // Total users
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $total_users = $stmt->fetchColumn();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'by_role' => $by_role,
                'total_users' => $total_users
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik user'];
    }
}

/**
 * Cek apakah username tersedia
 * @param string $username Username yang akan dicek
 * @param int $exclude_user_id ID user yang dikecualikan (untuk update)
 * @return array Status ketersediaan username
 */
function cek_username_tersedia($username, $exclude_user_id = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        
        if ($exclude_user_id) {
            $query .= " AND id_user != ?";
            $params[] = $exclude_user_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'tersedia' => $count == 0,
            'sudah_digunakan' => $count > 0
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cek username tersedia: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengecek username'];
    }
}
?>
