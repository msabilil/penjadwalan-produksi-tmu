<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/estimasi_functions.php';

/**
 * CRUD Functions untuk tabel pesanan
 */

/**
 * Menambah pesanan baru
 * @param array $data Data pesanan yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_pesanan($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_pesanan($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah nomor pesanan sudah ada - ALLOW DUPLICATE PO NUMBERS
        // Multiple orders can share the same PO number
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE no = ?");
        // $stmt->execute([$data['no']]);
        
        // if ($stmt->fetchColumn() > 0) {
        //     close_database($pdo);
        //     return ['success' => false, 'message' => 'Nomor pesanan sudah digunakan'];
        // }
        
        // Cek apakah desain ada (optional - allow orders without design)
        if (!empty($data['id_desain'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM desain WHERE id_desain = ?");
            $stmt->execute([$data['id_desain']]);
            
            if ($stmt->fetchColumn() == 0) {
                close_database($pdo);
                return ['success' => false, 'message' => 'Desain tidak ditemukan'];
            }
        }
        
        // Cek apakah user ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_user = ?");
        $stmt->execute([$data['id_user']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Insert pesanan baru (id_desain bisa NULL untuk pesanan tanpa desain)
        $query = "INSERT INTO pesanan (
            id_desain, id_user, no, nama_pemesan, no_telepon, alamat, jumlah, 
            tanggal_pesanan, deskripsi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            !empty($data['id_desain']) ? $data['id_desain'] : null,
            $data['id_user'],
            $data['no'],
            $data['nama_pemesan'],
            $data['no_telepon'] ?? null,
            $data['alamat'] ?? null,
            $data['jumlah'],
            $data['tanggal_pesanan'],
            $data['deskripsi'] ?? null
        ]);
        
        $pesanan_id = $pdo->lastInsertId();
        
        // AUTO HITUNG ESTIMASI jika pesanan memiliki desain
        if (!empty($data['id_desain'])) {
            // Hitung estimasi otomatis dengan parameter default
            $estimasi_result = hitung_estimasi_otomatis($pesanan_id);
            
            if (!$estimasi_result['success']) {
                log_activity("Warning: Gagal menghitung estimasi otomatis untuk pesanan ID: $pesanan_id - " . $estimasi_result['message'], 'WARNING');
            } else {
                log_activity("Estimasi otomatis berhasil dihitung untuk pesanan ID: $pesanan_id (Estimasi ID: {$estimasi_result['id_estimasi']})");
            }
        }
        
        close_database($pdo);
        
        log_activity("Pesanan baru ditambahkan: {$data['no']} (ID: $pesanan_id)");
        
        return [
            'success' => true, 
            'message' => 'Pesanan berhasil ditambahkan' . (!empty($data['id_desain']) ? ' dengan estimasi otomatis' : ''),
            'data' => ['id_pesanan' => $pesanan_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan pesanan'];
    }
}

/**
 * Mengambil semua data pesanan dengan join ke tabel terkait
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data pesanan
 */
function ambil_semua_pesanan($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                d.model_warna,
                d.ukuran,
                d.halaman,
                u.nama as nama_user,
                u.role as role_user,
                CASE 
                    WHEN p.id_desain IS NULL THEN 'design_needed'
                    ELSE 'design_ready'
                END as design_status
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Mengambil data pesanan berdasarkan ID
 * @param int $id_pesanan ID pesanan
 * @return array Data pesanan
 */
function ambil_pesanan_by_id($id_pesanan) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                d.model_warna,
                d.jumlah_warna,
                d.sisi,
                d.jenis_cover,
                d.laminasi,
                d.jilid,
                d.kualitas_warna,
                d.ukuran,
                d.halaman,
                d.estimasi_waktu_desain,
                u.nama as nama_user,
                u.username,
                u.role as role_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            WHERE p.id_pesanan = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_pesanan]);
        $pesanan = $stmt->fetch();
        
        close_database($pdo);
        
        if ($pesanan) {
            return [
                'success' => true,
                'data' => $pesanan
            ];
        } else {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Update data pesanan
 * @param int $id_pesanan ID pesanan yang akan diupdate
 * @param array $data Data pesanan yang baru
 * @return array Status operasi
 */
function update_pesanan($id_pesanan, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_pesanan($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah pesanan ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }
        
        // Cek apakah nomor PO sudah digunakan pesanan lain - ALLOW DUPLICATE PO
        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE no = ? AND id_pesanan != ?");
        // $stmt->execute([$data['no'], $id_pesanan]);
        
        // if ($stmt->fetchColumn() > 0) {
        //     close_database($pdo);
        //     return ['success' => false, 'message' => 'Nomor PO sudah digunakan'];
        // }
        
        // Cek apakah desain ada (optional)
        if (!empty($data['id_desain'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM desain WHERE id_desain = ?");
            $stmt->execute([$data['id_desain']]);
            
            if ($stmt->fetchColumn() == 0) {
                close_database($pdo);
                return ['success' => false, 'message' => 'Desain tidak ditemukan'];
            }
        }
        
        // Cek apakah user ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_user = ?");
        $stmt->execute([$data['id_user']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Ambil data pesanan lama untuk perbandingan
        $stmt = $pdo->prepare("SELECT id_desain FROM pesanan WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        $pesanan_lama = $stmt->fetch();
        $desain_lama = $pesanan_lama['id_desain'];
        
        // Update pesanan
        $query = "UPDATE pesanan SET 
            id_desain = ?, id_user = ?, no = ?, nama_pemesan = ?, 
            no_telepon = ?, alamat = ?, jumlah = ?, tanggal_pesanan = ?, deskripsi = ?
            WHERE id_pesanan = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            !empty($data['id_desain']) ? $data['id_desain'] : null,
            $data['id_user'],
            $data['no'],
            $data['nama_pemesan'],
            $data['no_telepon'] ?? null,
            $data['alamat'] ?? null,
            $data['jumlah'],
            $data['tanggal_pesanan'],
            $data['deskripsi'] ?? null,
            $id_pesanan
        ]);
        
        // AUTO HITUNG ESTIMASI jika:
        // 1. Pesanan sebelumnya tidak memiliki desain DAN sekarang ditambahkan desain, ATAU
        // 2. Desain diganti dengan desain lain
        $estimasi_message = '';
        if (!empty($data['id_desain']) && ($desain_lama != $data['id_desain'])) {
            // Gunakan fungsi yang lebih robust untuk handle estimasi
            $estimasi_result = recalculate_estimasi_after_design_change($id_pesanan);
            
            if (!$estimasi_result['success']) {
                log_activity("Warning: Gagal menghitung estimasi untuk pesanan ID: $id_pesanan setelah update - " . $estimasi_result['message'], 'WARNING');
            } else {
                log_activity("Estimasi berhasil dihitung/dihitung ulang untuk pesanan ID: $id_pesanan setelah update desain");
                $estimasi_message = ' dengan estimasi terupdate';
            }
        }
        
        close_database($pdo);
        
        log_activity("Pesanan diupdate: {$data['no']} (ID: $id_pesanan)");
        
        return ['success' => true, 'message' => 'Pesanan berhasil diupdate' . $estimasi_message];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate pesanan'];
    }
}

/**
 * Hapus pesanan
 * @param int $id_pesanan ID pesanan yang akan dihapus
 * @return array Status operasi
 */
function hapus_pesanan($id_pesanan) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah pesanan memiliki estimasi atau jadwal aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Pesanan tidak dapat dihapus karena memiliki estimasi'];
        }
        
        // Ambil data pesanan sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT no FROM pesanan WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        $pesanan = $stmt->fetch();
        
        if (!$pesanan) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }
        
        // Hapus pesanan
        $stmt = $pdo->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        
        close_database($pdo);
        
        log_activity("Pesanan dihapus: {$pesanan['no']} (ID: $id_pesanan)");
        
        return ['success' => true, 'message' => 'Pesanan berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus pesanan'];
    }
}

/**
 * Mengambil jumlah total pesanan
 * @return int Jumlah pesanan
 */
function hitung_total_pesanan() {
    $pdo = connect_database();
    if (!$pdo) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        close_database($pdo);
        return $total;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung total pesanan: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Cari pesanan berdasarkan nomor, nama pemesan, atau nama desain
 * @param string $keyword Kata kunci pencarian
 * @return array Data pesanan yang ditemukan
 */
function cari_pesanan($keyword) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                u.nama as nama_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            WHERE p.no LIKE ? OR p.nama_pemesan LIKE ? OR d.nama LIKE ?
            ORDER BY p.tanggal_pesanan DESC
        ";
        
        $search_term = "%$keyword%";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cari pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mencari pesanan'];
    }
}

/**
 * Mengambil pesanan berdasarkan ID user
 * @param int $id_user ID user
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @return array Data pesanan
 */
function ambil_pesanan_by_user($id_user, $limit = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE p.id_user = ?
            ORDER BY p.tanggal_pesanan DESC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_user]);
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan by user: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Mengambil pesanan berdasarkan rentang tanggal
 * @param string $tanggal_mulai Tanggal mulai (YYYY-MM-DD)
 * @param string $tanggal_selesai Tanggal selesai (YYYY-MM-DD)
 * @return array Data pesanan
 */
function ambil_pesanan_by_tanggal($tanggal_mulai, $tanggal_selesai) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                u.nama as nama_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            WHERE p.tanggal_pesanan BETWEEN ? AND ?
            ORDER BY p.tanggal_pesanan DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tanggal_mulai, $tanggal_selesai]);
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan by tanggal: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Mengambil statistik pesanan
 * @return array Statistik pesanan
 */
function hitung_statistik_pesanan() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Total pesanan
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pesanan");
        $stmt->execute();
        $total_pesanan = $stmt->fetchColumn();
        
        // Total quantity
        $stmt = $pdo->prepare("SELECT SUM(jumlah) as total_quantity FROM pesanan");
        $stmt->execute();
        $total_quantity = $stmt->fetchColumn();
        
        // Pesanan per bulan (6 bulan terakhir)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(tanggal_pesanan, '%Y-%m') as bulan,
                COUNT(*) as jumlah_pesanan,
                SUM(jumlah) as total_quantity
            FROM pesanan 
            WHERE tanggal_pesanan >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(tanggal_pesanan, '%Y-%m')
            ORDER BY bulan DESC
        ");
        $stmt->execute();
        $per_bulan = $stmt->fetchAll();
        
        // Pesanan berdasarkan jenis produk
        $stmt = $pdo->prepare("
            SELECT 
                d.jenis_produk,
                COUNT(*) as jumlah_pesanan,
                SUM(p.jumlah) as total_quantity
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            GROUP BY d.jenis_produk
            ORDER BY jumlah_pesanan DESC
        ");
        $stmt->execute();
        $by_produk = $stmt->fetchAll();
        
        // Top customers
        $stmt = $pdo->prepare("
            SELECT 
                nama_pemesan,
                COUNT(*) as jumlah_pesanan,
                SUM(jumlah) as total_quantity
            FROM pesanan
            GROUP BY nama_pemesan
            ORDER BY jumlah_pesanan DESC
            LIMIT 10
        ");
        $stmt->execute();
        $top_customers = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'total_pesanan' => $total_pesanan,
                'total_quantity' => $total_quantity,
                'per_bulan' => $per_bulan,
                'by_produk' => $by_produk,
                'top_customers' => $top_customers
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik pesanan'];
    }
}

/**
 * Mengambil pesanan terbaru
 * @param int $limit Jumlah pesanan yang diambil (default: 10)
 * @return array Data pesanan terbaru
 */
function ambil_pesanan_terbaru($limit = 10) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.id_pesanan,
                p.no,
                p.nama_pemesan,
                p.jumlah,
                p.tanggal_pesanan,
                d.nama as nama_desain,
                d.jenis_produk,
                u.nama as nama_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC
            LIMIT ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan terbaru: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil pesanan terbaru'];
    }
}

/**
 * Generate nomor pesanan otomatis dengan format PO/YYYY/00001
 * @param string $prefix Prefix nomor (default: 'PO')
 * @return string Nomor pesanan yang dihasilkan
 */
function generate_nomor_pesanan($prefix = 'PO') {
    $pdo = connect_database();
    if (!$pdo) {
        // Fallback jika database tidak tersedia
        return $prefix . '/' . date('Y') . '/' . str_pad(1, 5, '0', STR_PAD_LEFT);
    }
    
    try {
        // Ambil nomor urut terakhir untuk tahun ini
        $tahun = date('Y');
        $pattern = $prefix . '/' . $tahun . '/%';
        
        $query = "SELECT no FROM pesanan WHERE no LIKE ? ORDER BY CAST(SUBSTRING_INDEX(no, '/', -1) AS UNSIGNED) DESC LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$pattern]);
        $last_number = $stmt->fetchColumn();
        
        if ($last_number) {
            // Extract nomor urut dari nomor terakhir (bagian setelah slash terakhir)
            $parts = explode('/', $last_number);
            $urut = isset($parts[2]) ? (int)$parts[2] : 0;
            $urut++;
        } else {
            // Mulai dari 1 jika belum ada nomor untuk tahun ini
            $urut = 1;
        }
        
        // Format: PO/2024/00001 (5 digit dengan leading zero)
        $nomor_baru = $prefix . '/' . $tahun . '/' . str_pad($urut, 5, '0', STR_PAD_LEFT);
        
        // Double check apakah nomor sudah ada (safety check)
        $check_query = "SELECT COUNT(*) FROM pesanan WHERE no = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$nomor_baru]);
        
        if ($check_stmt->fetchColumn() > 0) {
            // Jika masih duplicate, coba nomor berikutnya
            $urut++;
            $nomor_baru = $prefix . '/' . $tahun . '/' . str_pad($urut, 5, '0', STR_PAD_LEFT);
        }
        
        close_database($pdo);
        return $nomor_baru;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error generate nomor pesanan: " . $e->getMessage(), 'ERROR');
        // Fallback dengan timestamp
        return $prefix . '/' . date('Y') . '/' . str_pad(1, 5, '0', STR_PAD_LEFT);
    }
}

/**
 * Mengambil pesanan berdasarkan filter
 * @param array $filters Filter criteria
 * @return array Data pesanan yang filtered
 */
function ambil_pesanan_by_filter($filters) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $where_conditions = [];
        $params = [];
        
        // Build WHERE clause
        if (isset($filters['id_desain']) && !empty($filters['id_desain'])) {
            $where_conditions[] = "p.id_desain = ?";
            $params[] = $filters['id_desain'];
        }
        
        if (isset($filters['id_user']) && !empty($filters['id_user'])) {
            $where_conditions[] = "p.id_user = ?";
            $params[] = $filters['id_user'];
        }
        
        if (isset($filters['nama_pemesan']) && !empty($filters['nama_pemesan'])) {
            $where_conditions[] = "p.nama_pemesan LIKE ?";
            $params[] = '%' . $filters['nama_pemesan'] . '%';
        }
        
        if (isset($filters['tanggal_mulai']) && !empty($filters['tanggal_mulai'])) {
            $where_conditions[] = "p.tanggal_pesanan >= ?";
            $params[] = $filters['tanggal_mulai'];
        }
        
        if (isset($filters['tanggal_selesai']) && !empty($filters['tanggal_selesai'])) {
            $where_conditions[] = "p.tanggal_pesanan <= ?";
            $params[] = $filters['tanggal_selesai'];
        }
        
        $query = "
            SELECT 
                p.*,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                d.model_warna,
                d.ukuran,
                d.halaman,
                u.nama as nama_user,
                u.role as role_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
        ";
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $query .= " ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $pesanans = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanans
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan by filter: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Tambah pesanan dengan desain baru (dual-flow: new design order)
 * @param array $desain_data Data desain baru
 * @param array $order_data Data pesanan
 * @return array Status operasi
 */
function tambah_pesanan_dengan_desain_baru($desain_data, $order_data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Validasi data desain
        require_once __DIR__ . '/desain_functions.php';
        $validasi_desain = validasi_data_desain($desain_data);
        if (!$validasi_desain['valid']) {
            $pdo->rollback();
            close_database($pdo);
            return ['success' => false, 'message' => 'Data desain tidak valid: ' . implode(', ', $validasi_desain['errors'])];
        }
        
        // Insert desain baru
        $query_desain = "INSERT INTO desain (
            jenis_desain, nama, file_cetak, jenis_produk, model_warna, jumlah_warna,
            sisi, jenis_cover, laminasi, jilid, kualitas_warna, ukuran, halaman,
            estimasi_waktu_desain, tanggal_selesai
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query_desain);
        $stmt->execute([
            $desain_data['jenis_desain'],
            $desain_data['nama'],
            $desain_data['file_cetak'] ?? null,
            $desain_data['jenis_produk'],
            $desain_data['model_warna'],
            $desain_data['jumlah_warna'],
            $desain_data['sisi'],
            $desain_data['jenis_cover'],
            $desain_data['laminasi'],
            $desain_data['jilid'],
            $desain_data['kualitas_warna'],
            $desain_data['ukuran'],
            $desain_data['halaman'],
            $desain_data['estimasi_waktu_desain'],
            $desain_data['tanggal_selesai']
        ]);
        
        $desain_id = $pdo->lastInsertId();
        
        // Set id_desain ke order data
        $order_data['id_desain'] = $desain_id;
        
        // Validasi data pesanan
        $validasi_pesanan = validasi_data_pesanan($order_data);
        if (!$validasi_pesanan['valid']) {
            $pdo->rollback();
            close_database($pdo);
            return ['success' => false, 'message' => 'Data pesanan tidak valid: ' . implode(', ', $validasi_pesanan['errors'])];
        }
        
        // Check nomor pesanan unique
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE no = ?");
        $stmt->execute([$order_data['no']]);
        
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollback();
            close_database($pdo);
            return ['success' => false, 'message' => 'Nomor pesanan sudah digunakan'];
        }
        
        // Check user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id_user = ?");
        $stmt->execute([$order_data['id_user']]);
        
        if ($stmt->fetchColumn() == 0) {
            $pdo->rollback();
            close_database($pdo);
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        // Insert pesanan baru
        $query_pesanan = "INSERT INTO pesanan (
            id_desain, id_user, no, nama_pemesan, no_telepon, alamat, jumlah, 
            tanggal_pesanan, deskripsi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query_pesanan);
        $stmt->execute([
            $order_data['id_desain'],
            $order_data['id_user'],
            $order_data['no'],
            $order_data['nama_pemesan'],
            $order_data['no_telepon'] ?? null,
            $order_data['alamat'] ?? null,
            $order_data['jumlah'],
            $order_data['tanggal_pesanan'],
            $order_data['deskripsi'] ?? null
        ]);
        
        $pesanan_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        close_database($pdo);
        
        log_activity("Pesanan baru dengan desain baru dibuat: {$order_data['no']} (Pesanan ID: $pesanan_id, Desain ID: $desain_id)");
        
        return [
            'success' => true, 
            'message' => 'Pesanan dengan desain baru berhasil dibuat',
            'data' => [
                'id_pesanan' => $pesanan_id,
                'id_desain' => $desain_id
            ]
        ];
        
    } catch (PDOException $e) {
        $pdo->rollback();
        close_database($pdo);
        log_activity("Error tambah pesanan dengan desain baru: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan pesanan dengan desain baru'];
    }
}
?>
