<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel detail_estimasi
 */

/**
 * Menambah detail estimasi baru
 * @param array $data Data detail estimasi yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_detail_estimasi($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_detail_estimasi($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah estimasi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$data['id_estimasi']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Insert detail estimasi baru
        $query = "INSERT INTO detail_estimasi (
            id_estimasi, waktu_desain, waktu_per_plat, waktu_manual_hardcover,
            waktu_standar_qc, waktu_standar_packing, jumlah_desainer, jumlah_plat,
            jumlah_halaman_per_plat, jumlah_plat_per_set, waktu_mesin_per_eks,
            pekerja_qc, kapasitas_box, jumlah_box, pekerja_packing
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['id_estimasi'],
            $data['waktu_desain'] ?? 0,
            $data['waktu_per_plat'] ?? 15.0,
            $data['waktu_manual_hardcover'] ?? 120.0,
            $data['waktu_standar_qc'] ?? 0.5,
            $data['waktu_standar_packing'] ?? 5.0,
            $data['jumlah_desainer'] ?? 1,
            $data['jumlah_plat'] ?? 0,
            $data['jumlah_halaman_per_plat'] ?? 8,
            $data['jumlah_plat_per_set'] ?? 0,
            $data['waktu_mesin_per_eks'] ?? 0,
            $data['pekerja_qc'] ?? 4,
            $data['kapasitas_box'] ?? 40,
            $data['jumlah_box'] ?? 0,
            $data['pekerja_packing'] ?? 4
        ]);
        
        $detail_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        log_activity("Detail estimasi baru ditambahkan untuk estimasi ID: {$data['id_estimasi']} (ID: $detail_id)");
        
        return [
            'success' => true, 
            'message' => 'Detail estimasi berhasil ditambahkan',
            'data' => ['id_detail_estimasi' => $detail_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah detail estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan detail estimasi'];
    }
}

/**
 * Mengambil semua data detail estimasi dengan join ke tabel terkait
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data detail estimasi
 */
function ambil_semua_detail_estimasi($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                de.*,
                e.waktu_hari as estimasi_waktu_hari,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan
            FROM detail_estimasi de
            LEFT JOIN estimasi e ON de.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            ORDER BY de.id_detail_estimasi DESC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $details = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $details
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua detail estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data detail estimasi'];
    }
}

/**
 * Mengambil data detail estimasi berdasarkan ID detail estimasi
 * @param int $id_detail_estimasi ID detail estimasi
 * @return array Data detail estimasi
 */
function ambil_detail_estimasi_by_detail_id($id_detail_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                de.*,
                e.waktu_hari as estimasi_waktu_hari,
                e.tanggal_estimasi,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                d.nama as nama_desain,
                d.halaman,
                d.jumlah_warna,
                d.sisi
            FROM detail_estimasi de
            LEFT JOIN estimasi e ON de.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE de.id_detail_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_detail_estimasi]);
        $detail = $stmt->fetch();
        
        close_database($pdo);
        
        if ($detail) {
            return [
                'success' => true,
                'data' => $detail
            ];
        } else {
            return ['success' => false, 'message' => 'Detail estimasi tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil detail estimasi by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data detail estimasi'];
    }
}

/**
 * Mengambil detail estimasi berdasarkan ID estimasi
 * @param int $id_estimasi ID estimasi
 * @return array Data detail estimasi
 */
function ambil_detail_estimasi_by_estimasi($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                de.*,
                e.waktu_hari as estimasi_waktu_hari,
                p.no as no_pesanan,
                p.nama_pemesan
            FROM detail_estimasi de
            LEFT JOIN estimasi e ON de.id_estimasi = e.id_estimasi
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            WHERE de.id_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        $detail = $stmt->fetch();
        
        close_database($pdo);
        
        if ($detail) {
            return [
                'success' => true,
                'data' => $detail
            ];
        } else {
            return ['success' => false, 'message' => 'Detail estimasi tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil detail estimasi by estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data detail estimasi'];
    }
}

/**
 * Update data detail estimasi
 * @param int $id_detail_estimasi ID detail estimasi yang akan diupdate
 * @param array $data Data detail estimasi yang baru
 * @return array Status operasi
 */
function update_detail_estimasi($id_detail_estimasi, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_detail_estimasi($data, false);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah detail estimasi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detail_estimasi WHERE id_detail_estimasi = ?");
        $stmt->execute([$id_detail_estimasi]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Detail estimasi tidak ditemukan'];
        }
        
        // Update detail estimasi
        $query = "UPDATE detail_estimasi SET 
            waktu_desain = ?, waktu_per_plat = ?, waktu_manual_hardcover = ?,
            waktu_standar_qc = ?, waktu_standar_packing = ?, jumlah_desainer = ?,
            jumlah_plat = ?, jumlah_halaman_per_plat = ?, jumlah_plat_per_set = ?,
            waktu_mesin_per_eks = ?, pekerja_qc = ?, kapasitas_box = ?,
            jumlah_box = ?, pekerja_packing = ?
            WHERE id_detail_estimasi = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['waktu_desain'] ?? 0,
            $data['waktu_per_plat'] ?? 15.0,
            $data['waktu_manual_hardcover'] ?? 120.0,
            $data['waktu_standar_qc'] ?? 0.5,
            $data['waktu_standar_packing'] ?? 5.0,
            $data['jumlah_desainer'] ?? 1,
            $data['jumlah_plat'] ?? 0,
            $data['jumlah_halaman_per_plat'] ?? 8,
            $data['jumlah_plat_per_set'] ?? 0,
            $data['waktu_mesin_per_eks'] ?? 0,
            $data['pekerja_qc'] ?? 4,
            $data['kapasitas_box'] ?? 40,
            $data['jumlah_box'] ?? 0,
            $data['pekerja_packing'] ?? 4,
            $id_detail_estimasi
        ]);
        
        close_database($pdo);
        
        log_activity("Detail estimasi diupdate (ID: $id_detail_estimasi)");
        
        return ['success' => true, 'message' => 'Detail estimasi berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update detail estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate detail estimasi'];
    }
}

/**
 * Hapus detail estimasi
 * @param int $id_detail_estimasi ID detail estimasi yang akan dihapus
 * @return array Status operasi
 */
function hapus_detail_estimasi($id_detail_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data detail estimasi sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT id_estimasi FROM detail_estimasi WHERE id_detail_estimasi = ?");
        $stmt->execute([$id_detail_estimasi]);
        $detail = $stmt->fetch();
        
        if (!$detail) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Detail estimasi tidak ditemukan'];
        }
        
        // Hapus detail estimasi
        $stmt = $pdo->prepare("DELETE FROM detail_estimasi WHERE id_detail_estimasi = ?");
        $stmt->execute([$id_detail_estimasi]);
        
        close_database($pdo);
        
        log_activity("Detail estimasi dihapus untuk estimasi ID: {$detail['id_estimasi']} (ID: $id_detail_estimasi)");
        
        return ['success' => true, 'message' => 'Detail estimasi berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus detail estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus detail estimasi'];
    }
}

/**
 * Hitung detail estimasi berdasarkan data pesanan dan desain
 * @param int $id_estimasi ID estimasi
 * @return array Status operasi
 */
function hitung_detail_estimasi($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data estimasi, pesanan dan desain
        $query = "
            SELECT 
                e.id_estimasi,
                p.jumlah,
                d.estimasi_waktu_desain,
                d.halaman,
                d.jumlah_warna,
                d.sisi,
                d.jenis_cover
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE e.id_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        $data = $stmt->fetch();
        
        if (!$data) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Data estimasi tidak ditemukan'];
        }
        
        // Konstanta untuk perhitungan
        $menit_operasional = 480; // 8 jam
        $jumlah_desainer = 1;
        $waktu_per_plat = 15.0;
        $jumlah_halaman_per_plat = 8;
        $waktu_standar_qc = 0.5;
        $waktu_standar_packing = 5.0;
        $pekerja_qc = 4;
        $kapasitas_box = 40;
        $pekerja_packing = 4;
        
        // Perhitungan detail estimasi sesuai dengan instruksi (lines 58-67)
        $waktu_menit_desain = $data['estimasi_waktu_desain'] / $menit_operasional;
        $waktu_desain = $waktu_menit_desain / $jumlah_desainer;
        
        $jumlah_plat_per_set = $data['jumlah_warna'] * $data['sisi'];
        $jumlah_plat = ($data['halaman'] / $jumlah_halaman_per_plat) * $jumlah_plat_per_set;
        
        $kapasitas_per_menit = 5000 / $menit_operasional; // Default capacity
        $kapasitas_per_eksemplar = 1 / $kapasitas_per_menit;
        $waktu_mesin_per_eks = $kapasitas_per_eksemplar * $data['jumlah'];
        
        $waktu_total_qc = $data['jumlah'] * $waktu_standar_qc;
        $jumlah_box = ceil($data['jumlah'] / $kapasitas_box);
        $waktu_total_packing = $jumlah_box * $waktu_standar_packing;
        
        // Data detail estimasi
        $detail_data = [
            'id_estimasi' => $id_estimasi,
            'waktu_desain' => round($waktu_desain, 2),
            'waktu_per_plat' => $waktu_per_plat,
            'waktu_manual_hardcover' => ($data['jenis_cover'] === 'hardcover') ? 120.0 : 0,
            'waktu_standar_qc' => $waktu_standar_qc,
            'waktu_standar_packing' => $waktu_standar_packing,
            'jumlah_desainer' => $jumlah_desainer,
            'jumlah_plat' => ceil($jumlah_plat),
            'jumlah_halaman_per_plat' => $jumlah_halaman_per_plat,
            'jumlah_plat_per_set' => $jumlah_plat_per_set,
            'waktu_mesin_per_eks' => round($waktu_mesin_per_eks, 6),
            'pekerja_qc' => $pekerja_qc,
            'kapasitas_box' => $kapasitas_box,
            'jumlah_box' => $jumlah_box,
            'pekerja_packing' => $pekerja_packing
        ];
        
        // Cek apakah detail estimasi sudah ada
        $stmt = $pdo->prepare("SELECT id_detail_estimasi FROM detail_estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing detail
            $result = update_detail_estimasi($existing['id_detail_estimasi'], $detail_data);
        } else {
            // Create new detail
            $result = tambah_detail_estimasi($detail_data);
        }
        
        close_database($pdo);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Detail estimasi berhasil dihitung',
                'data' => $detail_data
            ];
        } else {
            return $result;
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung detail estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung detail estimasi'];
    }
}
?>
