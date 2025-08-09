<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel jadwal_produksi
 */

/**
 * Menambah jadwal produksi baru
 * @param array $data Data jadwal produksi yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_jadwal_produksi($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah estimasi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$data['id_estimasi']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Cek apakah mesin ada jika id_mesin disediakan, atau pilih mesin berdasarkan spesifikasi desain
        if (isset($data['id_mesin']) && !empty($data['id_mesin'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesin WHERE id_mesin = ?");
            $stmt->execute([$data['id_mesin']]);
            
            if ($stmt->fetchColumn() == 0) {
                close_database($pdo);
                return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
            }
        } else {
            // Auto-assign mesin berdasarkan spesifikasi desain dari estimasi
            $mesin_result = pilih_mesin_untuk_jadwal($data['id_estimasi'], $pdo);
            if (!$mesin_result['success']) {
                close_database($pdo);
                return $mesin_result;
            }
            
            $data['id_mesin'] = $mesin_result['data']['id_mesin'];
        }
        
        // Generate no_jadwal otomatis jika tidak ada
        if (!isset($data['no_jadwal']) || empty($data['no_jadwal'])) {
            $data['no_jadwal'] = generate_nomor_jadwal();
        }
        
        // Cek konflik jadwal dengan mesin yang dipilih
        $konflik = cek_konflik_jadwal($data['id_mesin'], $data['tanggal_mulai'], $data['tanggal_selesai']);
        if (!$konflik['success']) {
            close_database($pdo);
            return $konflik;
        }
        
        // Insert jadwal produksi baru
        $query = "INSERT INTO jadwal_produksi (
            id_estimasi, id_mesin, no_jadwal, batch_ke, jumlah_batch_ini,
            tanggal_mulai, tanggal_selesai, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['id_estimasi'],
            $data['id_mesin'],
            $data['no_jadwal'],
            $data['batch_ke'] ?? 1,
            $data['jumlah_batch_ini'],
            $data['tanggal_mulai'],
            $data['tanggal_selesai'],
            $data['status'] ?? 'terjadwal'
        ]);
        
        $jadwal_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        log_activity("Jadwal produksi baru ditambahkan: {$data['no_jadwal']} (ID: $jadwal_id)");
        
        return [
            'success' => true, 
            'message' => 'Jadwal produksi berhasil ditambahkan',
            'data' => ['id_jadwal' => $jadwal_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah jadwal produksi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan jadwal produksi'];
    }
}

/**
 * Mengambil semua data jadwal produksi dengan join ke tabel terkait
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data jadwal produksi
 */
function ambil_semua_jadwal_produksi($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as estimasi_waktu_hari,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                d.nama as nama_desain,
                d.jenis_produk,
                m.nama_mesin,
                m.urutan_proses,
                u.nama as nama_user
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            ORDER BY jp.tanggal_mulai ASC, e.waktu_hari ASC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $jadwals = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $jadwals
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua jadwal produksi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data jadwal produksi'];
    }
}

/**
 * Mengambil data jadwal produksi berdasarkan ID
 * @param int $id_jadwal ID jadwal produksi
 * @return array Data jadwal produksi
 */
function ambil_jadwal_produksi_by_id($id_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as estimasi_waktu_hari,
                e.waktu_menit as estimasi_waktu_menit,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                p.tanggal_pesanan,
                d.nama as nama_desain,
                d.jenis_produk,
                d.model_warna,
                d.halaman,
                m.nama_mesin,
                m.urutan_proses,
                m.kapasitas,
                u.nama as nama_user
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            WHERE jp.id_jadwal = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_jadwal]);
        $jadwal = $stmt->fetch();
        
        close_database($pdo);
        
        if ($jadwal) {
            return [
                'success' => true,
                'data' => $jadwal
            ];
        } else {
            return ['success' => false, 'message' => 'Jadwal produksi tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil jadwal produksi by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data jadwal produksi'];
    }
}

/**
 * Update data jadwal produksi
 * @param int $id_jadwal ID jadwal produksi yang akan diupdate
 * @param array $data Data jadwal produksi yang baru
 * @return array Status operasi
 */
function update_jadwal_produksi($id_jadwal, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_jadwal($data, false);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah jadwal produksi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_produksi WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Jadwal produksi tidak ditemukan'];
        }
        
        // Cek konflik jadwal jika tanggal diubah
        if (isset($data['tanggal_mulai']) && isset($data['tanggal_selesai']) && isset($data['id_mesin'])) {
            $konflik = cek_konflik_jadwal($data['id_mesin'], $data['tanggal_mulai'], $data['tanggal_selesai'], $id_jadwal);
            if (!$konflik['success']) {
                close_database($pdo);
                return $konflik;
            }
        }
        
        // Update jadwal produksi
        $update_fields = [];
        $update_values = [];
        
        $allowed_fields = ['id_mesin', 'no_jadwal', 'batch_ke', 'jumlah_batch_ini', 'tanggal_mulai', 'tanggal_selesai', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $update_values[] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Tidak ada data untuk diupdate'];
        }
        
        $update_values[] = $id_jadwal;
        $query = "UPDATE jadwal_produksi SET " . implode(', ', $update_fields) . " WHERE id_jadwal = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($update_values);
        
        close_database($pdo);
        
        log_activity("Jadwal produksi diupdate (ID: $id_jadwal)");
        
        return ['success' => true, 'message' => 'Jadwal produksi berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update jadwal produksi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate jadwal produksi'];
    }
}

/**
 * Hapus jadwal produksi
 * @param int $id_jadwal ID jadwal produksi yang akan dihapus
 * @return array Status operasi
 */
function hapus_jadwal_produksi($id_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data jadwal produksi sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT no_jadwal FROM jadwal_produksi WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        $jadwal = $stmt->fetch();
        
        if (!$jadwal) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Jadwal produksi tidak ditemukan'];
        }
        
        // Hapus detail jadwal terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM detail_jadwal WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        
        // Hapus jadwal produksi
        $stmt = $pdo->prepare("DELETE FROM jadwal_produksi WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        
        close_database($pdo);
        
        log_activity("Jadwal produksi dihapus: {$jadwal['no_jadwal']} (ID: $id_jadwal)");
        
        return ['success' => true, 'message' => 'Jadwal produksi berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus jadwal produksi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus jadwal produksi'];
    }
}

/**
 * Cek konflik jadwal pada mesin dan waktu tertentu
 * @param int $id_mesin ID mesin
 * @param string $tanggal_mulai Tanggal mulai
 * @param string $tanggal_selesai Tanggal selesai
 * @param int $exclude_id_jadwal ID jadwal yang dikecualikan dari pengecekan (untuk update)
 * @return array Status operasi
 */
function cek_konflik_jadwal($id_mesin, $tanggal_mulai, $tanggal_selesai, $exclude_id_jadwal = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT COUNT(*) 
            FROM jadwal_produksi 
            WHERE id_mesin = ? 
            AND status NOT IN ('selesai', 'dibatalkan')
            AND (
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) OR
                (tanggal_mulai <= ? AND tanggal_selesai >= ?) OR
                (tanggal_mulai >= ? AND tanggal_selesai <= ?)
            )
        ";
        
        $params = [
            $id_mesin,
            $tanggal_mulai, $tanggal_mulai,  // Cek overlap dengan start
            $tanggal_selesai, $tanggal_selesai,  // Cek overlap dengan end
            $tanggal_mulai, $tanggal_selesai  // Cek jadwal yang berada di dalam range
        ];
        
        if ($exclude_id_jadwal) {
            $query .= " AND id_jadwal != ?";
            $params[] = $exclude_id_jadwal;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $count = $stmt->fetchColumn();
        
        close_database($pdo);
        
        if ($count > 0) {
            return ['success' => false, 'message' => 'Terdapat konflik jadwal pada mesin dan waktu tersebut'];
        }
        
        return ['success' => true, 'message' => 'Tidak ada konflik jadwal'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cek konflik jadwal: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengecek konflik jadwal'];
    }
}

/**
 * Mengambil jadwal produksi berdasarkan tanggal (SPT optimization)
 * @param string $tanggal Tanggal dalam format YYYY-MM-DD
 * @return array Data jadwal produksi
 */
function ambil_jadwal_by_tanggal($tanggal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as waktu_standar_hari,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah,
                d.nama as judul_desain,
                d.jenis_produk,
                COUNT(jp2.id_jadwal) as total_batch
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN jadwal_produksi jp2 ON jp.id_estimasi = jp2.id_estimasi
            WHERE DATE(jp.tanggal_mulai) = ? OR DATE(jp.tanggal_selesai) = ?
            GROUP BY jp.id_jadwal
            ORDER BY e.waktu_hari ASC, jp.batch_ke ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tanggal, $tanggal]);
        $jadwals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $jadwals
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil jadwal by tanggal: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil jadwal berdasarkan tanggal'];
    }
}

/**
 * Mengambil jadwal produksi berdasarkan bulan dan tahun
 * @param int $bulan Bulan (1-12)
 * @param int $tahun Tahun
 * @return array Data jadwal produksi
 */
function ambil_jadwal_by_bulan($bulan, $tahun) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as estimasi_waktu_hari,
                p.no as no_pesanan,
                p.nama_pemesan,
                d.nama as nama_desain,
                m.nama_mesin
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain  
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            WHERE MONTH(jp.tanggal_mulai) = ? AND YEAR(jp.tanggal_mulai) = ?
            ORDER BY jp.tanggal_mulai ASC, e.waktu_hari ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan, $tahun]);
        $jadwals = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $jadwals
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil jadwal by bulan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil jadwal berdasarkan bulan'];
    }
}

/**
 * Generate nomor jadwal otomatis
 * @param string $prefix Prefix nomor (default: 'JDW')
 * @return string Nomor jadwal yang dihasilkan
 */
function generate_nomor_jadwal($prefix = 'JDW') {
    return generate_nomor_urut($prefix, 4);
}

/**
 * Mengambil statistik jadwal produksi
 * @return array Statistik jadwal produksi
 */
function hitung_statistik_jadwal() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Total jadwal
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jadwal_produksi");
        $stmt->execute();
        $total_jadwal = $stmt->fetchColumn();
        
        // Jadwal per status
        $stmt = $pdo->prepare("
            SELECT 
                status,
                COUNT(*) as jumlah
            FROM jadwal_produksi 
            GROUP BY status
        ");
        $stmt->execute();
        $per_status = $stmt->fetchAll();
        
        // Jadwal per bulan (6 bulan terakhir)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(tanggal_mulai, '%Y-%m') as bulan,
                COUNT(*) as jumlah_jadwal,
                AVG(TIMESTAMPDIFF(HOUR, tanggal_mulai, tanggal_selesai)) as avg_durasi_jam
            FROM jadwal_produksi 
            WHERE tanggal_mulai >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(tanggal_mulai, '%Y-%m')
            ORDER BY bulan DESC
        ");
        $stmt->execute();
        $per_bulan = $stmt->fetchAll();
        
        // Jadwal per mesin
        $stmt = $pdo->prepare("
            SELECT 
                m.nama_mesin,
                COUNT(jp.id_jadwal) as jumlah_jadwal,
                AVG(TIMESTAMPDIFF(HOUR, jp.tanggal_mulai, jp.tanggal_selesai)) as avg_durasi_jam
            FROM jadwal_produksi jp
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            GROUP BY jp.id_mesin, m.nama_mesin
            ORDER BY jumlah_jadwal DESC
        ");
        $stmt->execute();
        $per_mesin = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'total_jadwal' => $total_jadwal,
                'per_status' => $per_status,
                'per_bulan' => $per_bulan,
                'per_mesin' => $per_mesin
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik jadwal: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik jadwal'];
    }
}

/**
 * Ambil semua jadwal produksi dengan pagination dan filter
 * @param int $page Halaman (default: 1)
 * @param int $limit Jumlah per halaman (default: 10)
 * @param string $search Kata kunci pencarian
 * @param string $status_filter Filter berdasarkan status
 * @return array
 */
function ambil_semua_jadwal($page = 1, $limit = 10, $search = '', $status_filter = '') {
    $pdo = connect_database();
    
    try {
        $offset = ($page - 1) * $limit;
        
        // Base query
        $where_conditions = [];
        $params = [];
        
        // Search condition
        if (!empty($search)) {
            $where_conditions[] = "(jp.no_jadwal LIKE ? OR p.nama_pemesan LIKE ? OR d.nama_produk LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "jp.status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Count query untuk total
        $count_query = "
            SELECT COUNT(*) as total
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            $where_clause
        ";
        
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Main query
        $query = "
            SELECT jp.*, e.waktu_hari as waktu_standar_hari, e.waktu_jam as waktu_standar_jam, p.nama_pemesan, 
                   p.no as no_pesanan, d.nama as judul_desain, d.jenis_produk,
                   COUNT(jp2.id_jadwal) as total_batch
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN jadwal_produksi jp2 ON jp.id_estimasi = jp2.id_estimasi
            $where_clause
            GROUP BY jp.id_jadwal
            ORDER BY jp.tanggal_mulai DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
                'total_items' => $total,
                'items_per_page' => $limit
            ]
        ];
    } catch (PDOException $e) {
        error_log("Database error in ambil_semua_jadwal: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Gagal mengambil data jadwal',
            'data' => [],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0,
                'total_items' => 0,
                'items_per_page' => $limit
            ]
        ];
    } finally {
        close_database($pdo);
    }
}

/**
 * Ambil semua jadwal produksi (versi sederhana untuk halaman)
 * @return array Status operasi dan data
 */
function ambil_semua_jadwal_simple() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as waktu_standar_hari,
                p.nama_pemesan,
                p.jumlah,
                d.nama as judul_desain,
                d.jenis_produk,
                COUNT(jp2.id_jadwal) as total_batch
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN jadwal_produksi jp2 ON jp.id_estimasi = jp2.id_estimasi
            GROUP BY jp.id_jadwal
            ORDER BY jp.tanggal_mulai DESC, e.waktu_hari ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $jadwals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $jadwals
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in ambil_semua_jadwal_simple: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal mengambil semua jadwal: ' . $e->getMessage()
        ];
    }
}

/**
 * Ambil jadwal produksi berdasarkan ID
 * @param int $id_jadwal
 * @return array|null
 */
function ambil_jadwal_by_id($id_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                jp.*,
                e.waktu_hari as waktu_standar_hari,
                e.waktu_jam as waktu_standar_jam,
                e.waktu_menit as waktu_standar_menit,
                p.nama_pemesan,
                p.no as no_pesanan,
                p.jumlah as jumlah_pesanan,
                d.nama as judul_desain,
                d.jenis_produk,
                COUNT(jp2.id_jadwal) as total_batch
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN jadwal_produksi jp2 ON jp.id_estimasi = jp2.id_estimasi
            WHERE jp.id_jadwal = ?
            GROUP BY jp.id_jadwal
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_jadwal]);
        $jadwal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        
        if ($jadwal) {
            return ['success' => true, 'data' => $jadwal];
        } else {
            return ['success' => false, 'message' => 'Jadwal tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in ambil_jadwal_by_id: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil jadwal: ' . $e->getMessage()];
    }
}

/**
 * Ambil jadwal produksi berdasarkan mesin
 * @param int $id_mesin
 * @return array
 */
function ambil_jadwal_by_mesin($id_mesin) {
    $pdo = connect_database();
    
    try {
        $query = "
            SELECT jp.*, e.waktu_hari as waktu_standar_hari, e.waktu_jam as waktu_standar_jam, p.nama_pemesan, 
                   p.no as no_pesanan, d.nama as nama_produk, d.ukuran, d.jenis_produk,
                   m.nama_mesin, m.urutan_proses
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            WHERE jp.id_mesin = ?
            ORDER BY jp.tanggal_mulai ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_mesin]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in ambil_jadwal_by_mesin: " . $e->getMessage());
        return [];
    } finally {
        close_database($pdo);
    }
}

/**
 * Ambil jadwal produksi berdasarkan estimasi
 * @param int $id_estimasi
 * @return array
 */
function ambil_jadwal_by_estimasi($id_estimasi) {
    $pdo = connect_database();
    
    try {
        $query = "
            SELECT jp.*, e.waktu_hari as waktu_standar_hari, e.waktu_jam as waktu_standar_jam, p.nama_pemesan, 
                   p.no as no_pesanan, d.nama as nama_produk, d.ukuran, d.jenis_produk,
                   m.nama_mesin, m.urutan_proses
            FROM jadwal_produksi jp
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN mesin m ON jp.id_mesin = m.id_mesin
            WHERE jp.id_estimasi = ?
            ORDER BY jp.tanggal_mulai ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in ambil_jadwal_by_estimasi: " . $e->getMessage());
        return [];
    } finally {
        close_database($pdo);
    }
}

/**
 * Tambah jadwal harian dengan SPT optimization
 * @param string $tanggal
 * @param array $estimasi_list
 * @return array
 */
function tambah_jadwal_harian($tanggal, $estimasi_list) {
    $pdo = connect_database();
    
    try {
        // Sort estimasi_list berdasarkan waktu_standar_hari (SPT - Shortest Processing Time)
        usort($estimasi_list, function($a, $b) {
            return $a['waktu_standar_hari'] <=> $b['waktu_standar_hari'];
        });
        
        $created_jadwal = [];
        $errors = [];
        
        foreach ($estimasi_list as $estimasi) {
            $data_jadwal = [
                'id_estimasi' => $estimasi['id_estimasi'],
                'id_mesin' => $estimasi['id_mesin'],
                'jumlah_batch_ini' => $estimasi['jumlah_batch_ini'],
                'tanggal_mulai' => $tanggal . ' 08:00:00',
                'tanggal_selesai' => date('Y-m-d H:i:s', strtotime($tanggal . ' 08:00:00 + ' . ($estimasi['waktu_standar_jam'] ?? 8) . ' hours'))
            ];
            
            $id_jadwal = tambah_jadwal_produksi($data_jadwal);
            
            if ($id_jadwal) {
                $created_jadwal[] = $id_jadwal;
            } else {
                $errors[] = "Gagal membuat jadwal untuk estimasi ID: " . $estimasi['id_estimasi'];
            }
        }
        
        if (!empty($created_jadwal)) {
            return [
                'success' => true,
                'message' => 'Jadwal harian berhasil dibuat',
                'created_jadwal' => $created_jadwal,
                'errors' => $errors
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal membuat jadwal harian',
                'errors' => $errors
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error in tambah_jadwal_harian: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    } finally {
        close_database($pdo);
    }
}

/**
 * Update status jadwal produksi
 * @param int $id_jadwal
 * @param string $status
 * @return bool
 */
function update_status_jadwal_produksi($id_jadwal, $status) {
    $pdo = connect_database();
    
    try {
        $query = "UPDATE jadwal_produksi SET status = ? WHERE id_jadwal = ?";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$status, $id_jadwal]);
        
        if ($result) {
            log_activity("Status jadwal produksi diupdate (ID: $id_jadwal, Status: $status)");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in update_status_jadwal_produksi: " . $e->getMessage());
        return false;
    } finally {
        close_database($pdo);
    }
}

/**
 * Ambil estimasi yang siap dijadwalkan
 * @return array Status operasi dan data
 */
function ambil_estimasi_siap_jadwal() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                e.id_estimasi,
                e.waktu_hari as waktu_standar_hari,
                p.nama_pemesan,
                p.jumlah,
                d.nama as judul_desain,
                d.jenis_produk,
                d.kualitas_warna,
                d.laminasi,
                d.jilid,
                d.jenis_cover
            FROM estimasi e
            JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            JOIN desain d ON p.id_desain = d.id_desain
            WHERE e.id_estimasi NOT IN (
                SELECT DISTINCT id_estimasi 
                FROM jadwal_produksi 
                WHERE status NOT IN ('dibatalkan', 'selesai')
            )
            ORDER BY e.waktu_hari ASC, e.tanggal_estimasi ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $estimasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        return [
            'success' => true,
            'data' => $estimasi_list,
            'message' => 'Data estimasi siap jadwal berhasil diambil'
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in ambil_estimasi_siap_jadwal: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal mengambil data estimasi siap jadwal: ' . $e->getMessage()
        ];
    }
}

/**
 * Optimasi jadwal harian menggunakan algoritma SPT (Shortest Processing Time)
 * @param string $tanggal
 * @return array Status operasi dan data
 */
function urutkan_jadwal_harian($tanggal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil semua jadwal untuk tanggal tersebut dengan estimasi
        $query = "
            SELECT 
                jp.id_jadwal,
                jp.id_estimasi,
                e.waktu_hari as waktu_standar_hari,
                jp.tanggal_mulai,
                jp.tanggal_selesai,
                jp.status
            FROM jadwal_produksi jp
            JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            WHERE DATE(jp.tanggal_mulai) = ? 
            AND jp.status NOT IN ('selesai', 'dibatalkan')
            ORDER BY e.waktu_hari ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tanggal]);
        $jadwal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($jadwal_list)) {
            close_database($pdo);
            return [
                'success' => false,
                'message' => 'Tidak ada jadwal yang dapat dioptimasi untuk tanggal tersebut'
            ];
        }
        
        // Update urutan berdasarkan SPT
        $current_time = $tanggal . ' 08:00:00'; // Mulai jam 8 pagi
        $updated_count = 0;
        
        foreach ($jadwal_list as $index => $jadwal) {
            $start_datetime = new DateTime($current_time);
            $duration_days = ceil($jadwal['waktu_standar_hari']);
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval('P' . $duration_days . 'D'));
            
            // Update tanggal mulai dan selesai berdasarkan urutan SPT
            $update_query = "
                UPDATE jadwal_produksi 
                SET 
                    tanggal_mulai = ?, 
                    tanggal_selesai = ?,
                    updated_at = NOW()
                WHERE id_jadwal = ?
            ";
            
            $update_stmt = $pdo->prepare($update_query);
            $update_result = $update_stmt->execute([
                $start_datetime->format('Y-m-d H:i:s'),
                $end_datetime->format('Y-m-d H:i:s'),
                $jadwal['id_jadwal']
            ]);
            
            if ($update_result) {
                $updated_count++;
                // Update current_time untuk jadwal berikutnya
                $current_time = $end_datetime->format('Y-m-d H:i:s');
            }
        }
        
        close_database($pdo);
        log_activity("Jadwal harian dioptimasi dengan SPT untuk tanggal $tanggal ($updated_count jadwal diupdate)");
        
        return [
            'success' => true,
            'message' => "Berhasil mengoptimasi $updated_count jadwal dengan algoritma SPT",
            'updated_count' => $updated_count
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in urutkan_jadwal_harian: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal mengoptimasi jadwal harian: ' . $e->getMessage()
        ];
    }
}

/**
 * Pilih mesin yang tepat untuk jadwal berdasarkan spesifikasi desain
 * @param int $id_estimasi ID estimasi
 * @param PDO $pdo Database connection
 * @return array Status operasi dan data mesin
 */
function pilih_mesin_untuk_jadwal($id_estimasi, $pdo) {
    try {
        // Ambil spesifikasi desain dari estimasi
        $query = "
            SELECT 
                d.kualitas_warna,
                d.laminasi,
                d.jilid,
                d.jenis_cover
            FROM estimasi e
            JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            JOIN desain d ON p.id_desain = d.id_desain
            WHERE e.id_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        $desain_spec = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$desain_spec) {
            return ['success' => false, 'message' => 'Spesifikasi desain tidak ditemukan'];
        }
        
        // Pilih mesin cetak berdasarkan kualitas_warna
        $nama_mesin_cetak = '';
        if ($desain_spec['kualitas_warna'] === 'tinggi') {
            $nama_mesin_cetak = 'Mesin Sheet';
        } else {
            $nama_mesin_cetak = 'Mesin Web'; // untuk kualitas cukup
        }
        
        // Ambil ID mesin cetak
        $stmt = $pdo->prepare("SELECT id_mesin FROM mesin WHERE nama_mesin = ? LIMIT 1");
        $stmt->execute([$nama_mesin_cetak]);
        $id_mesin = $stmt->fetchColumn();
        
        if (!$id_mesin) {
            // Fallback: ambil mesin pertama yang tersedia
            $stmt = $pdo->prepare("SELECT id_mesin FROM mesin ORDER BY id_mesin LIMIT 1");
            $stmt->execute();
            $id_mesin = $stmt->fetchColumn();
            
            if (!$id_mesin) {
                return ['success' => false, 'message' => 'Tidak ada mesin yang tersedia'];
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'id_mesin' => $id_mesin,
                'nama_mesin' => $nama_mesin_cetak,
                'spesifikasi_desain' => $desain_spec
            ],
            'message' => "Mesin dipilih berdasarkan spesifikasi: $nama_mesin_cetak"
        ];
        
    } catch (PDOException $e) {
        log_activity("Error in pilih_mesin_untuk_jadwal: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal memilih mesin yang sesuai: ' . $e->getMessage()
        ];
    }
}

/**
 * Ambil daftar mesin yang sesuai berdasarkan spesifikasi desain
 * @param int $id_estimasi ID estimasi
 * @return array Status operasi dan data mesin
 */
function ambil_mesin_sesuai_spesifikasi($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil spesifikasi desain dari estimasi
        $query = "
            SELECT 
                d.kualitas_warna,
                d.laminasi,
                d.jilid,
                d.jenis_cover,
                d.nama as nama_desain,
                p.nama_pemesan
            FROM estimasi e
            JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            JOIN desain d ON p.id_desain = d.id_desain
            WHERE e.id_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        $desain_spec = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$desain_spec) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Spesifikasi desain tidak ditemukan'];
        }
        
        $mesin_sesuai = [];
        $rekomendasi = [];
        
        // 1. Mesin Cetak (berdasarkan kualitas_warna) - PRIORITAS UTAMA
        if ($desain_spec['kualitas_warna'] === 'tinggi') {
            $mesin_cetak = 'Mesin Sheet';
            $rekomendasi[] = "Mesin Sheet (untuk kualitas tinggi)";
        } else {
            $mesin_cetak = 'Mesin Web';
            $rekomendasi[] = "Mesin Web (untuk kualitas cukup)";
        }
        
        // Ambil mesin cetak yang direkomendasikan
        $stmt = $pdo->prepare("SELECT id_mesin, nama_mesin, tipe_mesin, urutan_proses FROM mesin WHERE nama_mesin = ?");
        $stmt->execute([$mesin_cetak]);
        $mesin_utama = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mesin_utama) {
            $mesin_sesuai[] = array_merge($mesin_utama, ['prioritas' => 'utama', 'alasan' => 'Mesin cetak sesuai kualitas']);
        }
        
        // 2. Mesin Laminasi (jika diperlukan)
        if ($desain_spec['laminasi'] === 'glossy' || $desain_spec['laminasi'] === 'doff') {
            $stmt = $pdo->prepare("SELECT id_mesin, nama_mesin, tipe_mesin, urutan_proses FROM mesin WHERE nama_mesin = 'Mesin Vernis'");
            $stmt->execute();
            $mesin_laminasi = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_laminasi) {
                $mesin_sesuai[] = array_merge($mesin_laminasi, ['prioritas' => 'pendukung', 'alasan' => 'Untuk laminasi ' . $desain_spec['laminasi']]);
                $rekomendasi[] = "Mesin Vernis (untuk laminasi {$desain_spec['laminasi']})";
            }
        }
        
        // 3. Mesin Jilid (berdasarkan jenis jilid)
        $mesin_jilid = '';
        if ($desain_spec['jilid'] === 'lem') {
            $mesin_jilid = 'Mesin TSK';
            $rekomendasi[] = "Mesin TSK (untuk jilid lem)";
        } elseif ($desain_spec['jilid'] === 'jahit') {
            $mesin_jilid = 'Mesin Jahit';
            $rekomendasi[] = "Mesin Jahit (untuk jilid jahit)";
        } elseif ($desain_spec['jilid'] === 'spiral') {
            $mesin_jilid = 'Mesin Spiral';
            $rekomendasi[] = "Mesin Spiral (untuk jilid spiral)";
        }
        
        if (!empty($mesin_jilid)) {
            $stmt = $pdo->prepare("SELECT id_mesin, nama_mesin, tipe_mesin, urutan_proses FROM mesin WHERE nama_mesin = ?");
            $stmt->execute([$mesin_jilid]);
            $mesin_jilid_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid_data) {
                $mesin_sesuai[] = array_merge($mesin_jilid_data, ['prioritas' => 'pendukung', 'alasan' => 'Untuk jilid ' . $desain_spec['jilid']]);
            }
        }
        
        // 4. Ambil semua mesin sebagai alternatif
        $stmt = $pdo->prepare("SELECT id_mesin, nama_mesin, tipe_mesin, urutan_proses FROM mesin ORDER BY urutan_proses, nama_mesin");
        $stmt->execute();
        $semua_mesin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'mesin_sesuai' => $mesin_sesuai,
                'semua_mesin' => $semua_mesin,
                'spesifikasi_desain' => $desain_spec,
                'rekomendasi' => $rekomendasi,
                'mesin_utama_id' => $mesin_utama['id_mesin'] ?? null
            ],
            'message' => 'Daftar mesin berhasil diambil berdasarkan spesifikasi desain'
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in ambil_mesin_sesuai_spesifikasi: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal mengambil daftar mesin: ' . $e->getMessage()
        ];
    }
}
?>
