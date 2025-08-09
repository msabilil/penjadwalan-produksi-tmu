<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel estimasi
 */

/**
 * Menambah estimasi baru
 * @param array $data Data estimasi yang akan ditambahkan
 * @return array Status operasi dan data
 */
function tambah_estimasi($data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_estimasi($data);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah pesanan ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_pesanan = ?");
        $stmt->execute([$data['id_pesanan']]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
        }
        
        // Cek apakah estimasi untuk pesanan ini sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi WHERE id_pesanan = ?");
        $stmt->execute([$data['id_pesanan']]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi untuk pesanan ini sudah ada'];
        }
        
        // Insert estimasi baru
        $query = "INSERT INTO estimasi (
            id_pesanan, waktu_desain, waktu_plat, waktu_total_setup, waktu_mesin,
            waktu_qc, waktu_packing, waktu_menit, waktu_jam, waktu_hari, tanggal_estimasi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['id_pesanan'],
            $data['waktu_desain'] ?? 0,
            $data['waktu_plat'] ?? 0,
            $data['waktu_total_setup'] ?? 0,
            $data['waktu_mesin'] ?? 0,
            $data['waktu_qc'] ?? 0,
            $data['waktu_packing'] ?? 0,
            $data['waktu_menit'] ?? 0,
            $data['waktu_jam'] ?? 0,
            $data['waktu_hari'] ?? 0,
            $data['tanggal_estimasi'] ?? date('Y-m-d')
        ]);
        
        $estimasi_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        log_activity("Estimasi baru ditambahkan untuk pesanan ID: {$data['id_pesanan']} (ID: $estimasi_id)");
        
        return [
            'success' => true, 
            'message' => 'Estimasi berhasil ditambahkan',
            'data' => ['id_estimasi' => $estimasi_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan estimasi'];
    }
}

/**
 * Mengambil semua data estimasi dengan join ke tabel terkait
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data estimasi
 */
function ambil_semua_estimasi($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                e.*,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                p.tanggal_pesanan,
                d.nama as nama_desain,
                d.jenis_produk,
                d.model_warna,
                u.nama as nama_user
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            ORDER BY e.tanggal_estimasi DESC, e.id_estimasi DESC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $estimasis = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $estimasis
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data estimasi'];
    }
}

/**
 * Mengambil data estimasi berdasarkan ID
 * @param int $id_estimasi ID estimasi
 * @return array Data estimasi
 */
function ambil_estimasi_by_id($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                e.*,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                p.tanggal_pesanan,
                p.deskripsi as deskripsi_pesanan,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                d.model_warna,
                d.jumlah_warna,
                d.sisi,
                d.halaman,
                d.estimasi_waktu_desain,
                u.nama as nama_user,
                u.username
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            WHERE e.id_estimasi = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_estimasi]);
        $estimasi = $stmt->fetch();
        
        close_database($pdo);
        
        if ($estimasi) {
            return [
                'success' => true,
                'data' => $estimasi
            ];
        } else {
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil estimasi by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data estimasi'];
    }
}

/**
 * Update data estimasi
 * @param int $id_estimasi ID estimasi yang akan diupdate
 * @param array $data Data estimasi yang baru
 * @return array Status operasi
 */
function update_estimasi($id_estimasi, $data) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Validasi data
    $validasi = validasi_data_estimasi($data, false);
    if (!$validasi['valid']) {
        close_database($pdo);
        return ['success' => false, 'message' => implode(', ', $validasi['errors'])];
    }
    
    try {
        // Cek apakah estimasi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Update estimasi
        $query = "UPDATE estimasi SET 
            waktu_desain = ?, waktu_plat = ?, waktu_total_setup = ?, waktu_mesin = ?,
            waktu_qc = ?, waktu_packing = ?, waktu_menit = ?, waktu_jam = ?, waktu_hari = ?
            WHERE id_estimasi = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['waktu_desain'] ?? 0,
            $data['waktu_plat'] ?? 0,
            $data['waktu_total_setup'] ?? 0,
            $data['waktu_mesin'] ?? 0,
            $data['waktu_qc'] ?? 0,
            $data['waktu_packing'] ?? 0,
            $data['waktu_menit'] ?? 0,
            $data['waktu_jam'] ?? 0,
            $data['waktu_hari'] ?? 0,
            $id_estimasi
        ]);
        
        close_database($pdo);
        
        log_activity("Estimasi diupdate (ID: $id_estimasi)");
        
        return ['success' => true, 'message' => 'Estimasi berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate estimasi'];
    }
}

/**
 * Hapus estimasi
 * @param int $id_estimasi ID estimasi yang akan dihapus
 * @return array Status operasi
 */
function hapus_estimasi($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah estimasi memiliki jadwal produksi aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_produksi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak dapat dihapus karena memiliki jadwal produksi'];
        }
        
        // Ambil data estimasi sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT id_pesanan FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        $estimasi = $stmt->fetch();
        
        if (!$estimasi) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Hapus detail estimasi terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM detail_estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        // Hapus estimasi
        $stmt = $pdo->prepare("DELETE FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        close_database($pdo);
        
        log_activity("Estimasi dihapus untuk pesanan ID: {$estimasi['id_pesanan']} (ID: $id_estimasi)");
        
        return ['success' => true, 'message' => 'Estimasi berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus estimasi'];
    }
}

/**
 * Mengambil jumlah total estimasi
 * @return int Jumlah estimasi
 */
function hitung_total_estimasi() {
    $pdo = connect_database();
    if (!$pdo) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM estimasi");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        close_database($pdo);
        return $total;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung total estimasi: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Mengambil estimasi berdasarkan ID pesanan
 * @param int $id_pesanan ID pesanan
 * @return array Data estimasi
 */
function ambil_estimasi_by_pesanan($id_pesanan) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                e.*,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            WHERE e.id_pesanan = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_pesanan]);
        $estimasi = $stmt->fetch();
        
        close_database($pdo);
        
        if ($estimasi) {
            return [
                'success' => true,
                'data' => $estimasi
            ];
        } else {
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil estimasi by pesanan: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data estimasi'];
    }
}

/**
 * Hitung estimasi otomatis berdasarkan data pesanan dan desain dengan konfigurasi dinamis
 * @param int $id_pesanan ID pesanan
 * @param array $custom_config Konfigurasi custom (opsional)
 * @return array Data estimasi yang dihitung
 */
function hitung_estimasi_otomatis($id_pesanan, $custom_config = []) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data pesanan dan desain dengan spesifikasi lengkap
        $query = "
            SELECT 
                p.jumlah,
                d.estimasi_waktu_desain,
                d.halaman,
                d.jumlah_warna,
                d.sisi,
                d.jenis_cover,
                d.kualitas_warna,
                d.laminasi,
                d.jilid
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            WHERE p.id_pesanan = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_pesanan]);
        $data = $stmt->fetch();
        
        if (!$data) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Data pesanan tidak ditemukan'];
        }
        
        // Parameter default yang bisa di-override dengan $custom_config
        $konfigurasi = array_merge([
            'menit_operasional' => 480,
            'jumlah_desainer' => 1,
            'waktu_per_plat' => 1.0,
            'jumlah_halaman_per_plat' => 8,
            'waktu_standar_qc' => 2.0,
            'pekerja_qc' => 4,
            'waktu_standar_packing' => 5.0,
            'kapasitas_box' => 40,
            'pekerja_packing' => 4,
            'waktu_manual_hardcover' => 1.0
        ], $custom_config);
        
        // Perhitungan detail estimasi
        // Konversi estimasi_waktu_desain dari hari ke menit, lalu bagi dengan jumlah desainer
        $waktu_menit_desain = $data['estimasi_waktu_desain'] * $konfigurasi['menit_operasional'];
        $waktu_desain = $waktu_menit_desain / $konfigurasi['jumlah_desainer'];
        
        $jumlah_plat_per_set = $data['jumlah_warna'] * $data['sisi'];
        $jumlah_plat = ($data['halaman'] / $konfigurasi['jumlah_halaman_per_plat']) * $jumlah_plat_per_set;
        $waktu_plat = $jumlah_plat * $konfigurasi['waktu_per_plat'];
        
        // Pemilihan mesin berdasarkan spesifikasi desain
        $mesin_diperlukan = [];
        $waktu_setup_total = 0;
        $waktu_mesin_per_eks_total = 0;
        
        // 1. Mesin Cetak (berdasarkan kualitas_warna)
        if ($data['kualitas_warna'] === 'tinggi') {
            // Gunakan Mesin Sheet
            $query_cetak = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Sheet' LIMIT 1";
        } else {
            // Gunakan Mesin Web (kualitas cukup)
            $query_cetak = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Web' LIMIT 1";
        }
        
        $stmt_cetak = $pdo->prepare($query_cetak);
        $stmt_cetak->execute();
        $mesin_cetak = $stmt_cetak->fetch(PDO::FETCH_ASSOC);
        
        if ($mesin_cetak) {
            $mesin_diperlukan[] = $mesin_cetak['nama_mesin'];
            $waktu_setup_total += $mesin_cetak['waktu_setup'];
            $waktu_mesin_per_eks_total += $mesin_cetak['waktu_mesin_per_eks'];
        }
        
        // 2. Mesin Laminasi (berdasarkan laminasi)
        if ($data['laminasi'] === 'glossy' || $data['laminasi'] === 'doff') {
            $query_laminasi = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Vernis' LIMIT 1";
            $stmt_laminasi = $pdo->prepare($query_laminasi);
            $stmt_laminasi->execute();
            $mesin_laminasi = $stmt_laminasi->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_laminasi) {
                $mesin_diperlukan[] = $mesin_laminasi['nama_mesin'];
                $waktu_setup_total += $mesin_laminasi['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_laminasi['waktu_mesin_per_eks'];
            }
        }
        
        // 3. Mesin Jilid (berdasarkan jilid)
        if ($data['jilid'] === 'lem') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin TSK' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        } elseif ($data['jilid'] === 'jahit') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Jahit' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        } elseif ($data['jilid'] === 'spiral') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Spiral' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        }
        
        // Set waktu total setup dari mesin yang diperlukan
        $waktu_total_setup = $waktu_setup_total;
        
        // Hitung waktu mesin dari total waktu_mesin_per_eks semua mesin
        $waktu_mesin_per_eks = $waktu_mesin_per_eks_total;
        $waktu_mesin = $data['jumlah'] * $waktu_mesin_per_eks;
        
        // Tambahkan waktu manual hardcover jika ada
        if ($data['jenis_cover'] === 'hardcover') {
            $waktu_mesin += $data['jumlah'] * $konfigurasi['waktu_manual_hardcover'];
        }
        
        $waktu_total_qc = $data['jumlah'] * $konfigurasi['waktu_standar_qc'];
        $waktu_qc = $waktu_total_qc / $konfigurasi['pekerja_qc'];
        
        $jumlah_box = ceil($data['jumlah'] / $konfigurasi['kapasitas_box']);
        $waktu_total_packing = $jumlah_box * $konfigurasi['waktu_standar_packing'];
        $waktu_packing = $waktu_total_packing / $konfigurasi['pekerja_packing'];
        
        // Waktu tambahan hardcover
        $waktu_hardcover = 0;
        if ($data['jenis_cover'] === 'hardcover') {
            $waktu_hardcover = $konfigurasi['waktu_manual_hardcover'];
        }
        
        // Total waktu
        $waktu_menit = $waktu_desain + $waktu_plat + $waktu_total_setup + $waktu_mesin + $waktu_qc + $waktu_packing + $waktu_hardcover;
        $waktu_jam = $waktu_menit / 60;
        $waktu_hari = $waktu_jam / 8;
        
        // Begin transaction untuk menyimpan ke database
        $pdo->beginTransaction();
        
        // Insert ke tabel estimasi
        $query_estimasi = "
            INSERT INTO estimasi (
                id_pesanan, waktu_desain, waktu_plat, waktu_total_setup,
                waktu_mesin, waktu_qc, waktu_packing,
                waktu_menit, waktu_jam, waktu_hari, tanggal_estimasi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ";
        
        $stmt_estimasi = $pdo->prepare($query_estimasi);
        $stmt_estimasi->execute([
            $id_pesanan,
            $waktu_desain,
            $waktu_plat,
            $waktu_total_setup,
            $waktu_mesin,
            $waktu_qc,
            $waktu_packing,
            $waktu_menit,
            $waktu_jam,
            $waktu_hari
        ]);
        
        $id_estimasi = $pdo->lastInsertId();
        
        // Insert ke tabel detail_estimasi dengan parameter yang digunakan
        $query_detail = "
            INSERT INTO detail_estimasi (
                id_estimasi, waktu_desain, waktu_per_plat, waktu_manual_hardcover,
                waktu_standar_qc, waktu_standar_packing, jumlah_desainer,
                jumlah_plat, jumlah_halaman_per_plat, jumlah_plat_per_set,
                waktu_mesin_per_eks, pekerja_qc, kapasitas_box, jumlah_box, 
                pekerja_packing
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt_detail = $pdo->prepare($query_detail);
        $stmt_detail->execute([
            $id_estimasi,
            $waktu_desain,
            $konfigurasi['waktu_per_plat'],
            $konfigurasi['waktu_manual_hardcover'],
            $konfigurasi['waktu_standar_qc'],
            $konfigurasi['waktu_standar_packing'],
            $konfigurasi['jumlah_desainer'],
            $jumlah_plat,
            $konfigurasi['jumlah_halaman_per_plat'],
            $jumlah_plat_per_set,
            $waktu_mesin_per_eks,
            $konfigurasi['pekerja_qc'],
            $konfigurasi['kapasitas_box'],
            $jumlah_box,
            $konfigurasi['pekerja_packing']
        ]);
        
        $pdo->commit();
        close_database($pdo);
        
        return [
            'success' => true,
            'message' => 'Estimasi berhasil dihitung dan disimpan',
            'id_estimasi' => $id_estimasi,
            'waktu_hari' => $waktu_hari,
            'params_used' => $konfigurasi
        ];
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        close_database($pdo);
        log_activity("Error hitung estimasi otomatis: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Tambah estimasi dengan perhitungan otomatis dan konfigurasi custom
 * @param int $id_pesanan ID pesanan
 * @param array $custom_config Konfigurasi custom untuk perhitungan
 * @return array Status operasi dan data
 */
function tambah_estimasi_dengan_konfigurasi($id_pesanan, $custom_config = []) {
    // Hitung estimasi otomatis dengan konfigurasi custom
    $hasil_estimasi = hitung_estimasi_otomatis($id_pesanan, $custom_config);
    
    if (!$hasil_estimasi['success']) {
        return $hasil_estimasi;
    }
    
    // Siapkan data untuk tambah_estimasi
    $data_estimasi = array_merge($hasil_estimasi['data'], [
        'id_pesanan' => $id_pesanan,
        'tanggal_estimasi' => date('Y-m-d')
    ]);
    
    // Tambah estimasi ke database
    $result = tambah_estimasi($data_estimasi);
    
    if ($result['success']) {
        // Log konfigurasi yang digunakan jika ada custom config
        if (!empty($custom_config)) {
            $custom_params = array_keys($custom_config);
            log_activity("Estimasi dibuat dengan konfigurasi custom: " . implode(', ', $custom_params) . " (ID Pesanan: $id_pesanan)");
        }
        
        return array_merge($result, [
            'konfigurasi_digunakan' => $hasil_estimasi['konfigurasi_digunakan'],
            'detail_perhitungan' => $hasil_estimasi['detail_perhitungan']
        ]);
    }
    
    return $result;
}

/**
 * Simulasi estimasi dengan berbagai konfigurasi tanpa menyimpan
 * @param int $id_pesanan ID pesanan
 * @param array $skenario_list Array berisi berbagai skenario konfigurasi
 * @return array Hasil simulasi berbagai skenario
 */
function simulasi_estimasi_multi_skenario($id_pesanan, $skenario_list) {
    $hasil_simulasi = [];
    
    foreach ($skenario_list as $nama_skenario => $konfigurasi) {
        $hasil_estimasi = hitung_estimasi_otomatis($id_pesanan, $konfigurasi);
        
        if ($hasil_estimasi['success']) {
            $hasil_simulasi[$nama_skenario] = [
                'estimasi' => $hasil_estimasi['data'],
                'konfigurasi' => $hasil_estimasi['params_used'],
                'detail' => $hasil_estimasi
            ];
        } else {
            $hasil_simulasi[$nama_skenario] = [
                'error' => $hasil_estimasi['message']
            ];
        }
    }
    
    return [
        'success' => !empty($hasil_simulasi),
        'data' => $hasil_simulasi,
        'message' => !empty($hasil_simulasi) ? 'Simulasi berhasil' : 'Gagal melakukan simulasi'
    ];
}

/**
 * Mengambil pesanan yang belum memiliki estimasi
 * @return array Data pesanan tanpa estimasi
 */
function ambil_pesanan_tanpa_estimasi() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "
            SELECT 
                p.id_pesanan,
                p.id_desain,
                p.no,
                p.nama_pemesan,
                p.jumlah,
                p.tanggal_pesanan,
                d.nama as nama_desain,
                d.jenis_desain,
                d.jenis_produk,
                u.nama as nama_user
            FROM pesanan p
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            LEFT JOIN estimasi e ON p.id_pesanan = e.id_pesanan
            WHERE e.id_pesanan IS NULL
            ORDER BY p.tanggal_pesanan DESC, p.id_pesanan DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $pesanan = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $pesanan
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil pesanan tanpa estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data pesanan'];
    }
}

/**
 * Recalculate estimasi dengan parameter baru
 * @param int $id_estimasi ID estimasi yang akan dihitung ulang
 * @param array $new_params Parameter baru untuk perhitungan
 * @return array Status operasi
 */
function recalculate_estimasi($id_estimasi, $new_params) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data estimasi dan pesanan terkait dengan spesifikasi lengkap
        $query = "
            SELECT 
                e.id_pesanan,
                p.jumlah,
                d.estimasi_waktu_desain,
                d.halaman,
                d.jumlah_warna,
                d.sisi,
                d.jenis_cover,
                d.kualitas_warna,
                d.laminasi,
                d.jilid
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
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Parameter default yang bisa di-override dengan $new_params
        $konfigurasi = array_merge([
            'menit_operasional' => 480,
            'jumlah_desainer' => 1,
            'waktu_per_plat' => 1.0,
            'jumlah_halaman_per_plat' => 8,
            'waktu_standar_qc' => 2.0,
            'pekerja_qc' => 4,
            'waktu_standar_packing' => 5.0,
            'kapasitas_box' => 40,
            'pekerja_packing' => 4,
            'waktu_manual_hardcover' => 1.0
        ], $new_params);
        
        // Perhitungan ulang estimasi
        // Konversi estimasi_waktu_desain dari hari ke menit, lalu bagi dengan jumlah desainer
        $waktu_menit_desain = $data['estimasi_waktu_desain'] * $konfigurasi['menit_operasional'];
        $waktu_desain = $waktu_menit_desain / $konfigurasi['jumlah_desainer'];
        
        $jumlah_plat_per_set = $data['jumlah_warna'] * $data['sisi'];
        $jumlah_plat = ($data['halaman'] / $konfigurasi['jumlah_halaman_per_plat']) * $jumlah_plat_per_set;
        $waktu_plat = $jumlah_plat * $konfigurasi['waktu_per_plat'];
        
        // Pemilihan mesin berdasarkan spesifikasi desain (sama seperti hitung_estimasi_otomatis)
        $mesin_diperlukan = [];
        $waktu_setup_total = 0;
        $waktu_mesin_per_eks_total = 0;
        
        // 1. Mesin Cetak (berdasarkan kualitas_warna)
        if ($data['kualitas_warna'] === 'tinggi') {
            // Gunakan Mesin Sheet
            $query_cetak = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Sheet' LIMIT 1";
        } else {
            // Gunakan Mesin Web (kualitas cukup)
            $query_cetak = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Web' LIMIT 1";
        }
        
        $stmt_cetak = $pdo->prepare($query_cetak);
        $stmt_cetak->execute();
        $mesin_cetak = $stmt_cetak->fetch(PDO::FETCH_ASSOC);
        
        if ($mesin_cetak) {
            $mesin_diperlukan[] = $mesin_cetak['nama_mesin'];
            $waktu_setup_total += $mesin_cetak['waktu_setup'];
            $waktu_mesin_per_eks_total += $mesin_cetak['waktu_mesin_per_eks'];
        }
        
        // 2. Mesin Laminasi (berdasarkan laminasi)
        if ($data['laminasi'] === 'glossy' || $data['laminasi'] === 'doff') {
            $query_laminasi = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Vernis' LIMIT 1";
            $stmt_laminasi = $pdo->prepare($query_laminasi);
            $stmt_laminasi->execute();
            $mesin_laminasi = $stmt_laminasi->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_laminasi) {
                $mesin_diperlukan[] = $mesin_laminasi['nama_mesin'];
                $waktu_setup_total += $mesin_laminasi['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_laminasi['waktu_mesin_per_eks'];
            }
        }
        
        // 3. Mesin Jilid (berdasarkan jilid)
        if ($data['jilid'] === 'lem') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin TSK' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        } elseif ($data['jilid'] === 'jahit') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Jahit' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        } elseif ($data['jilid'] === 'spiral') {
            $query_jilid = "SELECT nama_mesin, waktu_setup, waktu_mesin_per_eks FROM mesin WHERE nama_mesin = 'Mesin Spiral' LIMIT 1";
            $stmt_jilid = $pdo->prepare($query_jilid);
            $stmt_jilid->execute();
            $mesin_jilid = $stmt_jilid->fetch(PDO::FETCH_ASSOC);
            
            if ($mesin_jilid) {
                $mesin_diperlukan[] = $mesin_jilid['nama_mesin'];
                $waktu_setup_total += $mesin_jilid['waktu_setup'];
                $waktu_mesin_per_eks_total += $mesin_jilid['waktu_mesin_per_eks'];
            }
        }
        
        // Set waktu total setup dari mesin yang diperlukan
        $waktu_total_setup = $waktu_setup_total;
        
        // Hitung waktu mesin dari total waktu_mesin_per_eks semua mesin
        $waktu_mesin_per_eks = $waktu_mesin_per_eks_total;
        $waktu_mesin = $data['jumlah'] * $waktu_mesin_per_eks;
        
        // Tambahkan waktu manual hardcover jika ada
        if ($data['jenis_cover'] === 'hardcover') {
            $waktu_mesin += $data['jumlah'] * $konfigurasi['waktu_manual_hardcover'];
        }
        
        $waktu_total_qc = $data['jumlah'] * $konfigurasi['waktu_standar_qc'];
        $waktu_qc = $waktu_total_qc / $konfigurasi['pekerja_qc'];
        
        $jumlah_box = ceil($data['jumlah'] / $konfigurasi['kapasitas_box']);
        $waktu_total_packing = $jumlah_box * $konfigurasi['waktu_standar_packing'];
        $waktu_packing = $waktu_total_packing / $konfigurasi['pekerja_packing'];
        
        // Waktu tambahan hardcover
        $waktu_hardcover = 0;
        if ($data['jenis_cover'] === 'hardcover') {
            $waktu_hardcover = $konfigurasi['waktu_manual_hardcover'];
        }
        
        // Total waktu
        $waktu_menit = $waktu_desain + $waktu_plat + $waktu_total_setup + $waktu_mesin + $waktu_qc + $waktu_packing + $waktu_hardcover;
        $waktu_jam = $waktu_menit / 60;
        $waktu_hari = $waktu_jam / 8;
        
        // Begin transaction untuk update database
        $pdo->beginTransaction();
        
        // Update tabel estimasi
        $query_update_estimasi = "
            UPDATE estimasi SET 
                waktu_desain = ?, waktu_plat = ?, waktu_total_setup = ?,
                waktu_mesin = ?, waktu_qc = ?, waktu_packing = ?,
                waktu_menit = ?, waktu_jam = ?, waktu_hari = ?
            WHERE id_estimasi = ?
        ";
        
        $stmt_update_estimasi = $pdo->prepare($query_update_estimasi);
        $stmt_update_estimasi->execute([
            $waktu_desain, $waktu_plat, $waktu_total_setup,
            $waktu_mesin, $waktu_qc, $waktu_packing,
            $waktu_menit, $waktu_jam, $waktu_hari,
            $id_estimasi
        ]);
        
        // Update tabel detail_estimasi dengan parameter baru
        $query_update_detail = "
            UPDATE detail_estimasi SET 
                waktu_desain = ?, waktu_per_plat = ?, waktu_manual_hardcover = ?,
                waktu_standar_qc = ?, waktu_standar_packing = ?, jumlah_desainer = ?,
                jumlah_plat = ?, jumlah_halaman_per_plat = ?, jumlah_plat_per_set = ?,
                waktu_mesin_per_eks = ?, pekerja_qc = ?, kapasitas_box = ?, 
                jumlah_box = ?, pekerja_packing = ?
            WHERE id_estimasi = ?
        ";
        
        $stmt_update_detail = $pdo->prepare($query_update_detail);
        $stmt_update_detail->execute([
            $waktu_desain, $konfigurasi['waktu_per_plat'], $konfigurasi['waktu_manual_hardcover'],
            $konfigurasi['waktu_standar_qc'], $konfigurasi['waktu_standar_packing'], $konfigurasi['jumlah_desainer'],
            $jumlah_plat, $konfigurasi['jumlah_halaman_per_plat'], $jumlah_plat_per_set,
            $waktu_mesin_per_eks, $konfigurasi['pekerja_qc'], $konfigurasi['kapasitas_box'],
            $jumlah_box, $konfigurasi['pekerja_packing'],
            $id_estimasi
        ]);
        
        $pdo->commit();
        close_database($pdo);
        
        log_activity("Estimasi dihitung ulang (ID: $id_estimasi) dengan parameter custom");
        
        return [
            'success' => true,
            'message' => 'Estimasi berhasil dihitung ulang',
            'data' => [
                'id_estimasi' => $id_estimasi,
                'waktu_hari' => $waktu_hari,
                'params_used' => $konfigurasi
            ]
        ];
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        close_database($pdo);
        log_activity("Error recalculate estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update parameter estimasi tanpa full recalculation
 * @param int $id_estimasi ID estimasi
 * @param array $param_changes Parameter yang akan diubah
 * @return array Status operasi
 */
function update_parameter_estimasi($id_estimasi, $param_changes) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah estimasi ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detail_estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Detail estimasi tidak ditemukan'];
        }
        
        // Validasi parameter yang diizinkan
        $allowed_params = ['jumlah_desainer', 'pekerja_qc', 'pekerja_packing'];
        $update_fields = [];
        $update_values = [];
        
        foreach ($param_changes as $param => $value) {
            if (in_array($param, $allowed_params)) {
                $update_fields[] = "$param = ?";
                $update_values[] = intval($value);
            }
        }
        
        if (empty($update_fields)) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Tidak ada parameter valid untuk diupdate'];
        }
        
        // Update parameter di detail_estimasi
        $update_values[] = $id_estimasi; // Untuk WHERE clause
        $query = "UPDATE detail_estimasi SET " . implode(', ', $update_fields) . " WHERE id_estimasi = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($update_values);
        
        close_database($pdo);
        
        log_activity("Parameter estimasi diupdate (ID: $id_estimasi): " . implode(', ', array_keys($param_changes)));
        
        return ['success' => true, 'message' => 'Parameter estimasi berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update parameter estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate parameter estimasi'];
    }
}

/**
 * Finalisasi estimasi untuk penjadwalan
 * @param int $id_estimasi ID estimasi yang akan difinalisasi
 * @return array Status operasi
 */
function finalize_estimasi($id_estimasi) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah estimasi ada
        $stmt = $pdo->prepare("SELECT id_pesanan FROM estimasi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        $estimasi = $stmt->fetch();
        
        if (!$estimasi) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi tidak ditemukan'];
        }
        
        // Cek apakah sudah ada jadwal produksi untuk estimasi ini
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_produksi WHERE id_estimasi = ?");
        $stmt->execute([$id_estimasi]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Estimasi sudah dijadwalkan dan tidak dapat difinalisasi ulang'];
        }
        
        // Untuk saat ini, finalisasi hanya menandai bahwa estimasi siap dijadwalkan
        // Kita bisa menambah kolom status di tabel estimasi di masa depan jika diperlukan
        
        close_database($pdo);
        
        log_activity("Estimasi difinalisasi dan siap dijadwalkan (ID: $id_estimasi)");
        
        return [
            'success' => true, 
            'message' => 'Estimasi berhasil difinalisasi dan siap untuk dijadwalkan',
            'data' => ['id_estimasi' => $id_estimasi]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error finalize estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal memfinalisasi estimasi'];
    }
}

/**
 * Mengambil statistik estimasi
 * @return array Statistik estimasi
 */
function hitung_statistik_estimasi() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Total estimasi
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM estimasi");
        $stmt->execute();
        $total_estimasi = $stmt->fetchColumn();
        
        // Rata-rata waktu
        $stmt = $pdo->prepare("
            SELECT 
                AVG(waktu_hari) as avg_waktu_hari,
                MIN(waktu_hari) as min_waktu_hari,
                MAX(waktu_hari) as max_waktu_hari,
                SUM(waktu_hari) as total_waktu_hari
            FROM estimasi
        ");
        $stmt->execute();
        $stat_waktu = $stmt->fetch();
        
        // Estimasi per bulan
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(tanggal_estimasi, '%Y-%m') as bulan,
                COUNT(*) as jumlah_estimasi,
                AVG(waktu_hari) as avg_waktu_hari
            FROM estimasi 
            WHERE tanggal_estimasi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(tanggal_estimasi, '%Y-%m')
            ORDER BY bulan DESC
        ");
        $stmt->execute();
        $per_bulan = $stmt->fetchAll();
        
        // Estimasi berdasarkan status (pending/completed)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN jp.id_estimasi IS NULL THEN 1 END) as estimasi_pending,
                COUNT(CASE WHEN jp.status NOT IN ('selesai', 'dibatalkan') AND jp.id_estimasi IS NOT NULL THEN 1 END) as estimasi_progress,
                COUNT(CASE WHEN jp.status = 'selesai' THEN 1 END) as estimasi_selesai
            FROM estimasi e
            LEFT JOIN jadwal_produksi jp ON e.id_estimasi = jp.id_estimasi
        ");
        $stmt->execute();
        $status_counts = $stmt->fetch();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'total_estimasi' => $total_estimasi,
                'estimasi_pending' => $status_counts['estimasi_pending'] ?? 0,
                'estimasi_progress' => $status_counts['estimasi_progress'] ?? 0,
                'estimasi_selesai' => $status_counts['estimasi_selesai'] ?? 0,
                'statistik_waktu' => $stat_waktu,
                'per_bulan' => $per_bulan
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik estimasi: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik estimasi'];
    }
}

/**
 * Menghitung ulang estimasi ketika desain pesanan berubah
 * @param int $id_pesanan ID pesanan yang desainnya berubah
 * @param array $custom_config Konfigurasi custom (opsional)
 * @return array Status operasi
 */
function recalculate_estimasi_after_design_change($id_pesanan, $custom_config = []) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah pesanan sudah memiliki estimasi
        $stmt = $pdo->prepare("SELECT id_estimasi FROM estimasi WHERE id_pesanan = ?");
        $stmt->execute([$id_pesanan]);
        $existing_estimasi = $stmt->fetch();
        
        if ($existing_estimasi) {
            // Jika sudah ada estimasi, gunakan recalculate_estimasi
            $result = recalculate_estimasi($existing_estimasi['id_estimasi'], $custom_config);
            if ($result['success']) {
                log_activity("Estimasi berhasil dihitung ulang setelah perubahan desain untuk pesanan ID: $id_pesanan", 'INFO');
            }
            return $result;
        } else {
            // Jika belum ada estimasi, buat baru
            $result = hitung_estimasi_otomatis($id_pesanan, $custom_config);
            if ($result['success']) {
                log_activity("Estimasi baru berhasil dibuat setelah penambahan desain untuk pesanan ID: $id_pesanan", 'INFO');
            }
            return $result;
        }
        
    } catch (Exception $e) {
        close_database($pdo);
        log_activity("Error recalculate estimasi after design change: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung ulang estimasi: ' . $e->getMessage()];
    }
}

/**
 * Utility function untuk memaksa menghitung ulang semua pesanan tanpa estimasi
 * @return array Status operasi dan hasil
 */
function generate_missing_estimations() {
    $result = ambil_pesanan_tanpa_estimasi();
    if (!$result['success']) {
        return $result;
    }
    
    $pesanan_list = $result['data'];
    $berhasil = 0;
    $gagal = 0;
    $errors = [];
    
    foreach ($pesanan_list as $pesanan) {
        if (!empty($pesanan['id_desain'])) {
            $estimasi_result = hitung_estimasi_otomatis($pesanan['id_pesanan']);
            if ($estimasi_result['success']) {
                $berhasil++;
                log_activity("Generated missing estimation for order ID: {$pesanan['id_pesanan']}", 'INFO');
            } else {
                $gagal++;
                $errors[] = "Pesanan {$pesanan['no']}: {$estimasi_result['message']}";
                log_activity("Failed to generate estimation for order ID: {$pesanan['id_pesanan']} - {$estimasi_result['message']}", 'WARNING');
            }
        }
    }
    
    return [
        'success' => true,
        'message' => "Proses selesai. Berhasil: $berhasil, Gagal: $gagal",
        'data' => [
            'berhasil' => $berhasil,
            'gagal' => $gagal,
            'errors' => $errors
        ]
    ];
}

/**
 * Mengambil estimasi berdasarkan filter bulan dan tahun
 * @param int $bulan Bulan filter (1-12, 0 untuk semua bulan)
 * @param int $tahun Tahun filter
 * @param int $limit Limit data
 * @param int $offset Offset data
 * @return array Status operasi dan data
 */
function ambil_estimasi_by_filter($bulan = 0, $tahun = 0, $limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $where_conditions = [];
        $params = [];
        
        // Filter bulan jika dipilih (menggunakan tanggal_pesanan)
        if ($bulan > 0 && $bulan <= 12) {
            $where_conditions[] = "MONTH(p.tanggal_pesanan) = ?";
            $params[] = $bulan;
        }
        
        // Filter tahun jika dipilih (menggunakan tanggal_pesanan)
        if ($tahun > 0) {
            $where_conditions[] = "YEAR(p.tanggal_pesanan) = ?";
            $params[] = $tahun;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "
            SELECT 
                e.*,
                p.no as no_pesanan,
                p.nama_pemesan,
                p.jumlah as jumlah_pesanan,
                p.tanggal_pesanan,
                d.nama as nama_desain,
                d.jenis_produk,
                d.model_warna,
                d.judul_produk,
                u.nama as nama_user
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            LEFT JOIN desain d ON p.id_desain = d.id_desain
            LEFT JOIN users u ON p.id_user = u.id_user
            $where_clause
            ORDER BY p.tanggal_pesanan DESC, e.id_estimasi DESC
        ";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $estimasis = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $estimasis,
            'filter_info' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_data' => count($estimasis)
            ]
        ];
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil estimasi by filter: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data estimasi'];
    }
}

/**
 * Mengambil bulan & tahun paling terbaru yang memiliki data estimasi (berdasarkan tanggal_pesanan)
 * @return array ['success'=>bool, 'data'=>['bulan'=>int,'tahun'=>int]] jika ada
 */
function ambil_bulan_tahun_terakhir_ada_estimasi() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    try {
        $sql = "
            SELECT YEAR(p.tanggal_pesanan) AS tahun, MONTH(p.tanggal_pesanan) AS bulan
            FROM estimasi e
            LEFT JOIN pesanan p ON e.id_pesanan = p.id_pesanan
            WHERE p.tanggal_pesanan IS NOT NULL
            ORDER BY p.tanggal_pesanan DESC, e.id_estimasi DESC
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        close_database($pdo);
        if ($row) {
            return [
                'success' => true,
                'data' => [
                    'bulan' => (int)$row['bulan'],
                    'tahun' => (int)$row['tahun']
                ]
            ];
        }
        return ['success' => false, 'message' => 'Tidak ada data estimasi'];
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity('Error ambil bulan/tahun terakhir estimasi: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil bulan/tahun terakhir'];
    }
}
?>
