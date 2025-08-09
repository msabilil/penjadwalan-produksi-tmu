<?php
/**
 * File: detail_jadwal_functions.php
 * Deskripsi: Fungsi-fungsi untuk CRUD tabel detail_jadwal
 * 
 * Detail jadwal berisi rincian proses per jadwal produksi dengan mesin spesifik
 * dan estimasi waktu per tahap produksi.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * Tambah detail jadwal baru
 * @param array $data Data detail jadwal baru
 * @return int|false ID detail jadwal yang baru dibuat atau false jika gagal
 */
function tambah_detail_jadwal($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return false;
    }
    
    // Validasi data
    $validasi = validasi_data_detail_jadwal($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return false;
    }
    
    try {
        // Generate nomor detail jadwal
        $data['no_detail_jadwal'] = generate_nomor_detail_jadwal();
        
        // Cek apakah jadwal produksi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_produksi WHERE id_jadwal = ?");
        $stmt->execute([$data['id_jadwal']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return false;
        }
        
        // Cek apakah mesin ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$data['id_mesin']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return false;
        }
        
        // Cek konflik jadwal detail
        $konflik = cek_konflik_detail_jadwal($data['id_mesin'], $data['waktu_mulai'], $data['waktu_selesai']);
        if ($konflik) {
            close_database($pdo);
            return false;
        }
        
        // Insert detail jadwal
        $query = "INSERT INTO detail_jadwal (id_jadwal, id_mesin, no_detail_jadwal, urutan_proses, 
                  nama_proses, waktu_mulai, waktu_selesai, estimasi_waktu_menit, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['id_jadwal'],
            $data['id_mesin'],
            $data['no_detail_jadwal'],
            $data['urutan_proses'],
            $data['nama_proses'],
            $data['waktu_mulai'],
            $data['waktu_selesai'],
            $data['estimasi_waktu_menit'],
            $data['status'] ?? 'terjadwal'
        ]);
        
        $id_detail_jadwal = $pdo->lastInsertId();
        close_database($pdo);
        
        log_activity("Detail jadwal ditambahkan (ID: $id_detail_jadwal, No: {$data['no_detail_jadwal']})");
        
        return $id_detail_jadwal;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah detail jadwal: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Ambil detail jadwal berdasarkan ID
 * @param int $id_detail_jadwal ID detail jadwal
 * @return array|null Data detail jadwal atau null jika tidak ditemukan
 */
function ambil_detail_jadwal_by_id($id_detail_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return null;
    }
    
    try {
        $query = "
            SELECT dj.*, jp.no_jadwal, jp.batch_ke, 
                   m.nama_mesin, m.tipe_mesin, m.kapasitas,
                   e.waktu_standar_hari, e.waktu_standar_jam, e.waktu_standar_menit,
                   p.nama_pemesan, p.no as no_pesanan, d.nama_produk
            FROM detail_jadwal dj
            LEFT JOIN jadwal_produksi jp ON dj.id_jadwal = jp.id_jadwal
            LEFT JOIN mesin m ON dj.id_mesin = m.id_mesin
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE dj.id_detail_jadwal = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_detail_jadwal]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        return $result ?: null;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil detail jadwal by ID: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * Ambil semua detail jadwal dengan pagination
 * @param int $page Halaman (default: 1)
 * @param int $limit Jumlah per halaman (default: 10)
 * @param string $search Kata kunci pencarian
 * @param string $status_filter Filter berdasarkan status
 * @return array
 */
function ambil_semua_detail_jadwal($page = 1, $limit = 10, $search = '', $status_filter = '') {
    $pdo = connect_database();
    
    try {
        $offset = ($page - 1) * $limit;
        
        // Base query conditions
        $where_conditions = [];
        $params = [];
        
        // Search condition
        if (!empty($search)) {
            $where_conditions[] = "(dj.no_detail_jadwal LIKE ? OR dj.nama_proses LIKE ? OR jp.no_jadwal LIKE ? OR p.nama_pemesan LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "dj.status = ?";
            $params[] = $status_filter;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Count query
        $count_query = "
            SELECT COUNT(*) as total
            FROM detail_jadwal dj
            LEFT JOIN jadwal_produksi jp ON dj.id_jadwal = jp.id_jadwal
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            $where_clause
        ";
        
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Main query
        $query = "
            SELECT dj.*, jp.no_jadwal, jp.batch_ke, 
                   m.nama_mesin, m.tipe_mesin,
                   p.nama_pemesan, p.no as no_pesanan, d.nama_produk
            FROM detail_jadwal dj
            LEFT JOIN jadwal_produksi jp ON dj.id_jadwal = jp.id_jadwal
            LEFT JOIN mesin m ON dj.id_mesin = m.id_mesin
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            $where_clause
            ORDER BY dj.waktu_mulai DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ]
        ];
    } catch (PDOException $e) {
        error_log("Database error in ambil_semua_detail_jadwal: " . $e->getMessage());
        return [
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
 * Ambil detail jadwal berdasarkan ID jadwal produksi
 * @param int $id_jadwal ID jadwal produksi
 * @return array
 */
function ambil_detail_jadwal_by_jadwal($id_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal', 'data' => []];
    }
    
    try {
        $query = "
            SELECT dj.*, m.nama_mesin, m.tipe_mesin, m.kapasitas
            FROM detail_jadwal dj
            LEFT JOIN mesin m ON dj.id_mesin = m.id_mesin
            WHERE dj.id_jadwal = ?
            ORDER BY dj.urutan_proses ASC, dj.tanggal_mulai ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_jadwal]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $data,
            'message' => 'Data detail jadwal berhasil diambil'
        ];
    } catch (PDOException $e) {
        close_database($pdo);
        error_log("Database error in ambil_detail_jadwal_by_jadwal: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Gagal mengambil detail jadwal: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

/**
 * Ambil detail jadwal berdasarkan mesin dan tanggal
 * @param int $id_mesin ID mesin
 * @param string $tanggal Tanggal (YYYY-MM-DD)
 * @return array
 */
function ambil_detail_jadwal_by_mesin_tanggal($id_mesin, $tanggal) {
    $pdo = connect_database();
    
    try {
        $query = "
            SELECT dj.*, jp.no_jadwal, jp.batch_ke, 
                   p.nama_pemesan, p.no as no_pesanan, d.nama_produk
            FROM detail_jadwal dj
            LEFT JOIN jadwal_produksi jp ON dj.id_jadwal = jp.id_jadwal
            LEFT JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE dj.id_mesin = ? 
            AND (DATE(dj.waktu_mulai) = ? OR DATE(dj.waktu_selesai) = ?)
            ORDER BY dj.waktu_mulai ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_mesin, $tanggal, $tanggal]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in ambil_detail_jadwal_by_mesin_tanggal: " . $e->getMessage());
        return [];
    } finally {
        close_database($pdo);
    }
}

/**
 * Update data detail jadwal
 * @param int $id_detail_jadwal ID detail jadwal yang akan diupdate
 * @param array $data Data detail jadwal yang baru
 * @return bool True jika berhasil
 */
function update_detail_jadwal($id_detail_jadwal, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return false;
    }
    
    // Validasi data (tidak perlu check required IDs karena ini update)
    $validasi = validasi_data_detail_jadwal($data, false);
    if (!$validasi['valid']) {
        close_database($pdo);
        return false;
    }
    
    try {
        // Cek apakah detail jadwal ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detail_jadwal WHERE id_detail_jadwal = ?");
        $stmt->execute([$id_detail_jadwal]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return false;
        }
        
        // Cek konflik jadwal jika waktu diubah
        if (isset($data['waktu_mulai']) && isset($data['waktu_selesai']) && isset($data['id_mesin'])) {
            $konflik = cek_konflik_detail_jadwal($data['id_mesin'], $data['waktu_mulai'], $data['waktu_selesai'], $id_detail_jadwal);
            if ($konflik) {
                close_database($pdo);
                return false;
            }
        }
        
        // Update detail jadwal
        $update_fields = [];
        $update_values = [];
        
        $allowed_fields = ['id_mesin', 'urutan_proses', 'nama_proses', 'waktu_mulai', 'waktu_selesai', 'estimasi_waktu_menit', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $update_values[] = $data[$field];
            }
        }
        
        if (empty($update_fields)) {
            close_database($pdo);
            return false;
        }
        
        $update_values[] = $id_detail_jadwal;
        $query = "UPDATE detail_jadwal SET " . implode(', ', $update_fields) . " WHERE id_detail_jadwal = ?";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute($update_values);
        
        close_database($pdo);
        
        if ($result) {
            log_activity("Detail jadwal diupdate (ID: $id_detail_jadwal)");
        }
        
        return $result;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update detail jadwal: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Update status detail jadwal
 * @param int $id_detail_jadwal ID detail jadwal
 * @param string $status Status baru
 * @return bool True jika berhasil
 */
function update_status_detail_jadwal($id_detail_jadwal, $status) {
    $pdo = connect_database();
    if (!$pdo) {
        return false;
    }
    
    try {
        $query = "UPDATE detail_jadwal SET status = ? WHERE id_detail_jadwal = ?";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$status, $id_detail_jadwal]);
        
        close_database($pdo);
        
        if ($result) {
            log_activity("Status detail jadwal diupdate (ID: $id_detail_jadwal, Status: $status)");
        }
        
        return $result;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update status detail jadwal: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Hapus detail jadwal
 * @param int $id_detail_jadwal ID detail jadwal yang akan dihapus
 * @return bool True jika berhasil
 */
function hapus_detail_jadwal($id_detail_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return false;
    }
    
    try {
        // Ambil data detail jadwal sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT no_detail_jadwal FROM detail_jadwal WHERE id_detail_jadwal = ?");
        $stmt->execute([$id_detail_jadwal]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$detail) {
            close_database($pdo);
            return false;
        }
        
        // Hapus detail jadwal
        $stmt = $pdo->prepare("DELETE FROM detail_jadwal WHERE id_detail_jadwal = ?");
        $result = $stmt->execute([$id_detail_jadwal]);
        
        close_database($pdo);
        
        if ($result) {
            log_activity("Detail jadwal dihapus (ID: $id_detail_jadwal, No: {$detail['no_detail_jadwal']})");
        }
        
        return $result;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus detail jadwal: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Generate nomor detail jadwal otomatis
 * @return string Nomor detail jadwal
 */
function generate_nomor_detail_jadwal() {
    $pdo = connect_database();
    if (!$pdo) {
        return 'DJ' . date('Ymd') . '001';
    }
    
    try {
        $prefix = 'DJ' . date('Ymd');
        
        $query = "SELECT no_detail_jadwal FROM detail_jadwal 
                  WHERE no_detail_jadwal LIKE ? 
                  ORDER BY no_detail_jadwal DESC LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$prefix . '%']);
        $last_no = $stmt->fetchColumn();
        
        close_database($pdo);
        
        if ($last_no) {
            $last_sequence = intval(substr($last_no, -3));
            $new_sequence = $last_sequence + 1;
        } else {
            $new_sequence = 1;
        }
        
        return $prefix . str_pad($new_sequence, 3, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error generate nomor detail jadwal: " . $e->getMessage(), 'ERROR');
        return 'DJ' . date('Ymd') . '001';
    }
}

/**
 * Cek konflik jadwal detail pada mesin dan waktu tertentu
 * @param int $id_mesin ID mesin
 * @param string $waktu_mulai Waktu mulai (YYYY-MM-DD HH:MM:SS)
 * @param string $waktu_selesai Waktu selesai (YYYY-MM-DD HH:MM:SS)
 * @param int $exclude_id ID detail jadwal yang dikecualikan (untuk update)
 * @return bool True jika ada konflik
 */
function cek_konflik_detail_jadwal($id_mesin, $waktu_mulai, $waktu_selesai, $exclude_id = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return false;
    }
    
    try {
        $query = "SELECT COUNT(*) FROM detail_jadwal 
                  WHERE id_mesin = ? 
                  AND ((waktu_mulai < ? AND waktu_selesai > ?) 
                       OR (waktu_mulai < ? AND waktu_selesai > ?))";
        
        $params = [$id_mesin, $waktu_selesai, $waktu_mulai, $waktu_mulai, $waktu_selesai];
        
        if ($exclude_id) {
            $query .= " AND id_detail_jadwal != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $count = $stmt->fetchColumn();
        close_database($pdo);
        
        return $count > 0;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cek konflik detail jadwal: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Ambil statistik detail jadwal
 * @param int $bulan Bulan (1-12)
 * @param int $tahun Tahun
 * @return array
 */
function hitung_statistik_detail_jadwal($bulan = null, $tahun = null) {
    $pdo = connect_database();
    
    try {
        $bulan = $bulan ?? date('n');
        $tahun = $tahun ?? date('Y');
        
        // Total detail jadwal
        $query = "SELECT COUNT(*) as total FROM detail_jadwal 
                  WHERE MONTH(waktu_mulai) = ? AND YEAR(waktu_mulai) = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan, $tahun]);
        $total_detail = $stmt->fetchColumn();
        
        // Detail jadwal per status
        $query = "SELECT status, COUNT(*) as jumlah FROM detail_jadwal 
                  WHERE MONTH(waktu_mulai) = ? AND YEAR(waktu_mulai) = ?
                  GROUP BY status";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan, $tahun]);
        $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Detail jadwal per mesin
        $query = "SELECT m.nama_mesin, COUNT(*) as jumlah 
                  FROM detail_jadwal dj
                  LEFT JOIN mesin m ON dj.id_mesin = m.id_mesin
                  WHERE MONTH(dj.waktu_mulai) = ? AND YEAR(dj.waktu_mulai) = ?
                  GROUP BY dj.id_mesin, m.nama_mesin
                  ORDER BY jumlah DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan, $tahun]);
        $mesin_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Rata-rata waktu per proses
        $query = "SELECT AVG(estimasi_waktu_menit) as rata_rata_waktu FROM detail_jadwal 
                  WHERE MONTH(waktu_mulai) = ? AND YEAR(waktu_mulai) = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$bulan, $tahun]);
        $rata_rata_waktu = $stmt->fetchColumn();
        
        return [
            'total_detail_jadwal' => $total_detail,
            'rata_rata_waktu_menit' => round($rata_rata_waktu, 2),
            'status_breakdown' => $status_breakdown,
            'mesin_breakdown' => $mesin_breakdown,
            'bulan' => $bulan,
            'tahun' => $tahun
        ];
        
    } catch (PDOException $e) {
        error_log("Database error in hitung_statistik_detail_jadwal: " . $e->getMessage());
        return [
            'total_detail_jadwal' => 0,
            'rata_rata_waktu_menit' => 0,
            'status_breakdown' => [],
            'mesin_breakdown' => [],
            'bulan' => $bulan ?? date('n'),
            'tahun' => $tahun ?? date('Y')
        ];
    } finally {
        close_database($pdo);
    }
}

/**
 * Batch create detail jadwal untuk jadwal produksi
 * @param int $id_jadwal ID jadwal produksi
 * @param array $proses_list List proses dengan mesin dan estimasi waktu
 * @return array
 */
function batch_create_detail_jadwal($id_jadwal, $proses_list) {
    $pdo = connect_database();
    
    try {
        $created_details = [];
        $errors = [];
        
        // Sort berdasarkan urutan proses
        usort($proses_list, function($a, $b) {
            return $a['urutan_proses'] <=> $b['urutan_proses'];
        });
        
        $waktu_mulai = date('Y-m-d H:i:s'); // Start from now
        
        foreach ($proses_list as $proses) {
            $waktu_selesai = date('Y-m-d H:i:s', strtotime($waktu_mulai . ' + ' . $proses['estimasi_waktu_menit'] . ' minutes'));
            
            $data_detail = [
                'id_jadwal' => $id_jadwal,
                'id_mesin' => $proses['id_mesin'],
                'urutan_proses' => $proses['urutan_proses'],
                'nama_proses' => $proses['nama_proses'],
                'waktu_mulai' => $waktu_mulai,
                'waktu_selesai' => $waktu_selesai,
                'estimasi_waktu_menit' => $proses['estimasi_waktu_menit']
            ];
            
            $id_detail = tambah_detail_jadwal($data_detail);
            
            if ($id_detail) {
                $created_details[] = $id_detail;
                // Set waktu mulai proses berikutnya
                $waktu_mulai = $waktu_selesai;
            } else {
                $errors[] = "Gagal membuat detail jadwal untuk proses: " . $proses['nama_proses'];
            }
        }
        
        return [
            'success' => !empty($created_details),
            'created_details' => $created_details,
            'errors' => $errors,
            'message' => !empty($created_details) ? 'Detail jadwal berhasil dibuat' : 'Gagal membuat detail jadwal'
        ];
        
    } catch (Exception $e) {
        error_log("Error in batch_create_detail_jadwal: " . $e->getMessage());
        return [
            'success' => false,
            'created_details' => [],
            'errors' => ['Error: ' . $e->getMessage()],
            'message' => 'Error dalam batch create detail jadwal'
        ];
    } finally {
        close_database($pdo);
    }
}

/**
 * Generate detail jadwal otomatis berdasarkan estimasi
 * @param int $id_jadwal
 * @return array Status operasi dan data
 */
function generate_detail_jadwal($id_jadwal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data jadwal dan estimasi
        $query = "
            SELECT 
                jp.*,
                e.waktu_desain,
                e.waktu_plat,
                e.waktu_total_setup,
                e.waktu_mesin,
                e.waktu_qc,
                e.waktu_packing,
                e.waktu_menit,
                e.waktu_jam,
                e.waktu_hari,
                de.waktu_mesin_per_eks,
                de.jumlah_plat,
                de.waktu_per_plat,
                de.waktu_standar_qc,
                de.waktu_standar_packing,
                de.jumlah_desainer,
                de.pekerja_qc,
                de.pekerja_packing
            FROM jadwal_produksi jp
            JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            LEFT JOIN detail_estimasi de ON e.id_estimasi = de.id_estimasi
            WHERE jp.id_jadwal = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_jadwal]);
        $jadwal_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jadwal_data) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Jadwal tidak ditemukan'];
        }
        
        // Definisi proses produksi dengan durasi yang benar
        $processes = [
            1 => ['nama' => 'desain', 'durasi_menit' => $jadwal_data['waktu_desain'] ?? 0],
            2 => ['nama' => 'plat', 'durasi_menit' => $jadwal_data['waktu_plat'] ?? 0],
            3 => ['nama' => 'setup', 'durasi_menit' => $jadwal_data['waktu_total_setup'] ?? 0],
            4 => ['nama' => 'cetak', 'durasi_menit' => $jadwal_data['waktu_mesin'] ?? 0],
            5 => ['nama' => 'laminasi', 'durasi_menit' => 0], // Akan dihitung terpisah
            6 => ['nama' => 'finishing', 'durasi_menit' => 0], // Ganti 'jilid' dengan 'finishing'
            7 => ['nama' => 'qc', 'durasi_menit' => $jadwal_data['waktu_qc'] ?? 0],
            8 => ['nama' => 'packing', 'durasi_menit' => $jadwal_data['waktu_packing'] ?? 0]
        ];
        
        // Hitung waktu mulai untuk setiap proses
        $current_time = new DateTime($jadwal_data['tanggal_mulai']);
        $created_details = [];
        
        foreach ($processes as $urutan => $process) {
            $durasi_jam = round($process['durasi_menit'] / 60, 2);
            $end_time = clone $current_time;
            
            if ($durasi_jam > 0) {
                $end_time->add(new DateInterval('PT' . ceil($process['durasi_menit']) . 'M'));
            }
            
            // Insert detail jadwal dengan kolom yang sesuai struktur tabel
            $detail_data = [
                'id_jadwal' => $id_jadwal,
                'urutan_proses' => $urutan,
                'nama_proses' => $process['nama'],
                'tanggal_mulai' => $current_time->format('Y-m-d H:i:s'),
                'tanggal_selesai' => $end_time->format('Y-m-d H:i:s'),
                'durasi_jam' => $durasi_jam
            ];
            
            $insert_query = "
                INSERT INTO detail_jadwal 
                (id_jadwal, urutan_proses, nama_proses, tanggal_mulai, tanggal_selesai, durasi_jam)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $insert_stmt = $pdo->prepare($insert_query);
            $result = $insert_stmt->execute([
                $detail_data['id_jadwal'],
                $detail_data['urutan_proses'],
                $detail_data['nama_proses'],
                $detail_data['tanggal_mulai'],
                $detail_data['tanggal_selesai'],
                $detail_data['durasi_jam']
            ]);
            
            if ($result) {
                $detail_data['id_detail_jadwal'] = $pdo->lastInsertId();
                $created_details[] = $detail_data;
            }
            
            // Update current_time untuk proses berikutnya
            $current_time = $end_time;
        }
        
        close_database($pdo);
        log_activity("Detail jadwal berhasil di-generate untuk jadwal ID: $id_jadwal");
        
        return [
            'success' => true,
            'message' => 'Detail jadwal berhasil di-generate',
            'data' => $created_details,
            'total_created' => count($created_details)
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error in generate_detail_jadwal: " . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'message' => 'Gagal generate detail jadwal: ' . $e->getMessage()
        ];
    }
}
?>
