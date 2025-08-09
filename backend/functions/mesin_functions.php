<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel mesin
 */

/**
 * Menambah mesin baru
 * @param array $data Data mesin yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_mesin($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_mesin($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Insert mesin baru
        $query = "INSERT INTO mesin (
            nama_mesin, urutan_proses, kapasitas, waktu_setup, 
            waktu_mesin_per_eks, menit_operasional
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['nama_mesin'],
            $data['urutan_proses'],
            $data['kapasitas'],
            $data['waktu_setup'],
            $data['waktu_mesin_per_eks'],
            $data['menit_operasional'] ?? MENIT_OPERASIONAL
        ]);
        
        $mesin_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        log_activity("Mesin baru ditambahkan: {$data['nama_mesin']} (ID: $mesin_id)");
        
        return [
            'success' => true, 
            'message' => 'Mesin berhasil ditambahkan',
            'data' => ['id_mesin' => $mesin_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan mesin'];
    }
}

/**
 * Mengambil semua data mesin
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data mesin
 */
function ambil_semua_mesin($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM mesin ORDER BY urutan_proses, nama_mesin";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data mesin'];
    }
}

/**
 * Mengambil data mesin berdasarkan ID
 * @param int $id_mesin ID mesin
 * @return array Data mesin
 */
function ambil_mesin_by_id($id_mesin) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM mesin WHERE id_mesin = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_mesin]);
        $mesin = $stmt->fetch();
        
        close_database($pdo);
        
        if ($mesin) {
            return [
                'success' => true,
                'data' => $mesin
            ];
        } else {
            return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil mesin by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data mesin'];
    }
}

/**
 * Update data mesin
 * @param int $id_mesin ID mesin yang akan diupdate
 * @param array $data Data mesin yang baru
 * @return array Status operasi
 */
function update_mesin($id_mesin, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_mesin($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah mesin ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
        }
        
        // Update mesin
        $query = "UPDATE mesin SET 
            nama_mesin = ?, urutan_proses = ?, kapasitas = ?, 
            waktu_setup = ?, waktu_mesin_per_eks = ?, menit_operasional = ?
            WHERE id_mesin = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['nama_mesin'],
            $data['urutan_proses'],
            $data['kapasitas'],
            $data['waktu_setup'],
            $data['waktu_mesin_per_eks'],
            $data['menit_operasional'] ?? MENIT_OPERASIONAL,
            $id_mesin
        ]);
        
        close_database($pdo);
        
        log_activity("Mesin diupdate: {$data['nama_mesin']} (ID: $id_mesin)");
        
        return ['success' => true, 'message' => 'Mesin berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate mesin'];
    }
}

/**
 * Hapus mesin
 * @param int $id_mesin ID mesin yang akan dihapus
 * @return array Status operasi
 */
function hapus_mesin($id_mesin) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah mesin memiliki jadwal aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_produksi WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Mesin tidak dapat dihapus karena memiliki jadwal produksi'];
        }
        
        // Ambil data mesin sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT nama_mesin FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        $mesin = $stmt->fetch();
        
        if (!$mesin) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
        }
        
        // Hapus mesin
        $stmt = $pdo->prepare("DELETE FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        
        close_database($pdo);
        
        log_activity("Mesin dihapus: {$mesin['nama_mesin']} (ID: $id_mesin)");
        
        return ['success' => true, 'message' => 'Mesin berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus mesin'];
    }
}

/**
 * Mengambil jumlah total mesin
 * @return int Jumlah mesin
 */
function hitung_total_mesin() {
    $pdo = connect_database();
    if (!$pdo) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesin");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        close_database($pdo);
        return $total;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung total mesin: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Cari mesin berdasarkan nama
 * @param string $keyword Kata kunci pencarian
 * @return array Data mesin yang ditemukan
 */
function cari_mesin($keyword) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM mesin 
                  WHERE nama_mesin LIKE ?
                  ORDER BY urutan_proses, nama_mesin";
        
        $search_term = "%$keyword%";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_term]);
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cari mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mencari mesin'];
    }
}

/**
 * Mengambil mesin berdasarkan urutan proses
 * @param int $urutan_proses Urutan proses
 * @return array Data mesin
 */
function ambil_mesin_by_urutan_proses($urutan_proses) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM mesin WHERE urutan_proses = ? ORDER BY nama_mesin";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$urutan_proses]);
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil mesin by urutan proses: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data mesin'];
    }
}

/**
 * Mengambil mesin dengan kapasitas tertinggi
 * @param int $limit Jumlah mesin yang diambil (default: 10)
 * @return array Data mesin
 */
function ambil_mesin_kapasitas_tertinggi($limit = 10) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_mesin, nama_mesin, urutan_proses, kapasitas, waktu_setup
                  FROM mesin 
                  ORDER BY kapasitas DESC 
                  LIMIT ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil mesin kapasitas tertinggi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data mesin'];
    }
}

/**
 * Mengambil statistik mesin berdasarkan urutan proses
 * @return array Statistik mesin
 */
function hitung_statistik_mesin() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Statistik berdasarkan urutan proses
        $stmt = $pdo->prepare("
            SELECT urutan_proses, COUNT(*) as jumlah_mesin, 
                   AVG(kapasitas) as rata_kapasitas,
                   SUM(kapasitas) as total_kapasitas
            FROM mesin 
            GROUP BY urutan_proses
            ORDER BY urutan_proses
        ");
        $stmt->execute();
        $by_urutan = $stmt->fetchAll();
        
        // Total kapasitas semua mesin
        $stmt = $pdo->prepare("SELECT SUM(kapasitas) as total_kapasitas FROM mesin");
        $stmt->execute();
        $total_kapasitas = $stmt->fetchColumn();
        
        // Rata-rata waktu setup
        $stmt = $pdo->prepare("SELECT AVG(waktu_setup) as rata_waktu_setup FROM mesin");
        $stmt->execute();
        $rata_waktu_setup = $stmt->fetchColumn();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'by_urutan' => $by_urutan,
                'total_kapasitas' => $total_kapasitas,
                'rata_waktu_setup' => round($rata_waktu_setup, 2)
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik mesin'];
    }
}

/**
 * Mengecek ketersediaan mesin pada rentang waktu tertentu
 * @param int $id_mesin ID mesin
 * @param string $tanggal_mulai Tanggal mulai (YYYY-MM-DD HH:MM:SS)
 * @param string $tanggal_selesai Tanggal selesai (YYYY-MM-DD HH:MM:SS)
 * @return array Status ketersediaan
 */
function cek_ketersediaan_mesin($id_mesin, $tanggal_mulai, $tanggal_selesai) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah mesin ada
        $stmt = $pdo->prepare("SELECT nama_mesin FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        $mesin = $stmt->fetch();
        
        if (!$mesin) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
        }
        
        // Cek jadwal yang bentrok
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM jadwal_produksi 
            WHERE id_mesin = ? 
            AND status NOT IN ('selesai', 'dibatalkan')
            AND (
                (tanggal_mulai <= ? AND tanggal_selesai > ?) OR
                (tanggal_mulai < ? AND tanggal_selesai >= ?) OR
                (tanggal_mulai >= ? AND tanggal_selesai <= ?)
            )
        ");
        $stmt->execute([
            $id_mesin, 
            $tanggal_mulai, $tanggal_mulai,
            $tanggal_selesai, $tanggal_selesai,
            $tanggal_mulai, $tanggal_selesai
        ]);
        
        $konflik = $stmt->fetchColumn();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'tersedia' => $konflik == 0,
            'konflik' => $konflik > 0,
            'nama_mesin' => $mesin['nama_mesin']
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cek ketersediaan mesin: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengecek ketersediaan mesin'];
    }
}

/**
 * Mengambil mesin yang tersedia pada rentang waktu tertentu
 * @param string $tanggal_mulai Tanggal mulai (YYYY-MM-DD HH:MM:SS)
 * @param string $tanggal_selesai Tanggal selesai (YYYY-MM-DD HH:MM:SS)
 * @param int $urutan_proses Filter berdasarkan urutan proses (opsional)
 * @return array Data mesin yang tersedia
 */
function ambil_mesin_tersedia($tanggal_mulai, $tanggal_selesai, $urutan_proses = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT m.* FROM mesin m
            WHERE m.id_mesin NOT IN (
                SELECT DISTINCT jp.id_mesin 
                FROM jadwal_produksi jp
                WHERE jp.status NOT IN ('selesai', 'dibatalkan')
                AND (
                    (jp.tanggal_mulai <= ? AND jp.tanggal_selesai > ?) OR
                    (jp.tanggal_mulai < ? AND jp.tanggal_selesai >= ?) OR
                    (jp.tanggal_mulai >= ? AND jp.tanggal_selesai <= ?)
                )
            )
        ";
        
        $params = [
            $tanggal_mulai, $tanggal_mulai,
            $tanggal_selesai, $tanggal_selesai,
            $tanggal_mulai, $tanggal_selesai
        ];
        
        if ($urutan_proses !== null) {
            $query .= " AND m.urutan_proses = ?";
            $params[] = $urutan_proses;
        }
        
        $query .= " ORDER BY m.urutan_proses, m.nama_mesin";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil mesin tersedia: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil mesin tersedia'];
    }
}

/**
 * Cek konflik jadwal mesin pada periode tertentu
 * @param int $id_mesin ID mesin
 * @param string $tanggal_mulai Tanggal mulai (YYYY-MM-DD HH:MM:SS)
 * @param string $tanggal_selesai Tanggal selesai (YYYY-MM-DD HH:MM:SS)
 * @param int $exclude_jadwal_id ID jadwal yang dikecualikan (untuk update)
 * @return array Status konflik dan data konflik jika ada
 */
function cek_konflik_jadwal($id_mesin, $tanggal_mulai, $tanggal_selesai, $exclude_jadwal_id = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT jp.*, p.nama_pemesan, d.nama as nama_desain
            FROM jadwal_produksi jp
            JOIN estimasi e ON jp.id_estimasi = e.id_estimasi
            JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            JOIN desain d ON p.id_desain = d.id_desain
            WHERE jp.id_mesin = ? 
            AND jp.status NOT IN ('selesai', 'dibatalkan')
            AND (
                (jp.tanggal_mulai BETWEEN ? AND ?) OR
                (jp.tanggal_selesai BETWEEN ? AND ?) OR
                (jp.tanggal_mulai <= ? AND jp.tanggal_selesai >= ?)
            )
        ";
        
        $params = [$id_mesin, $tanggal_mulai, $tanggal_selesai, 
                  $tanggal_mulai, $tanggal_selesai, $tanggal_mulai, $tanggal_selesai];
        
        if ($exclude_jadwal_id) {
            $query .= " AND jp.id_jadwal != ?";
            $params[] = $exclude_jadwal_id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $konflik = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'ada_konflik' => count($konflik) > 0,
            'data_konflik' => $konflik
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cek konflik jadwal: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengecek konflik jadwal'];
    }
}

/**
 * Mengambil mesin berdasarkan jenis (nama mesin contains keyword)
 * @param string $jenis_mesin Jenis mesin (web, sheet, vernis, tsk, jahit, spiral)
 * @return array Data mesin
 */
function ambil_mesin_by_jenis($jenis_mesin) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM mesin WHERE LOWER(nama_mesin) LIKE LOWER(?) ORDER BY nama_mesin";
        $search_term = "%$jenis_mesin%";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_term]);
        $mesins = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $mesins
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil mesin by jenis: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data mesin'];
    }
}

/**
 * Hitung kapasitas harian mesin dengan mempertimbangkan jadwal yang sudah ada
 * @param int $id_mesin ID mesin
 * @param string $tanggal Tanggal dalam format YYYY-MM-DD
 * @return array Kapasitas tersedia dan informasi terkait
 */
function hitung_kapasitas_harian($id_mesin, $tanggal) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data mesin
        $stmt = $pdo->prepare("SELECT * FROM mesin WHERE id_mesin = ?");
        $stmt->execute([$id_mesin]);
        $mesin = $stmt->fetch();
        
        if (!$mesin) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Mesin tidak ditemukan'];
        }
        
        // Hitung kapasitas yang sudah terpakai pada tanggal tersebut
        $stmt = $pdo->prepare("
            SELECT SUM(COALESCE(dj.durasi_jam, 0)) as total_jam_terpakai
            FROM jadwal_produksi jp
            JOIN detail_jadwal dj ON jp.id_jadwal = dj.id_jadwal
            WHERE jp.id_mesin = ? 
            AND DATE(dj.tanggal_mulai) = ?
            AND jp.status NOT IN ('selesai', 'dibatalkan')
        ");
        $stmt->execute([$id_mesin, $tanggal]);
        $result = $stmt->fetch();
        
        $jam_terpakai = $result['total_jam_terpakai'] ?? 0;
        $jam_operasional = $mesin['menit_operasional'] / 60; // Convert to hours
        $jam_tersedia = max(0, $jam_operasional - $jam_terpakai);
        
        // Hitung persentase utilisasi
        $utilisasi_persen = $jam_operasional > 0 ? ($jam_terpakai / $jam_operasional) * 100 : 0;
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'mesin' => $mesin,
                'jam_operasional' => $jam_operasional,
                'jam_terpakai' => $jam_terpakai,
                'jam_tersedia' => $jam_tersedia,
                'utilisasi_persen' => round($utilisasi_persen, 2),
                'dapat_dijadwalkan' => $jam_tersedia > 0
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung kapasitas harian: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung kapasitas mesin'];
    }
}
?>
