<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation_functions.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * CRUD Functions untuk tabel desain
 */

/**
 * Handle file upload untuk desain
 * @param array $file_data Data file dari $_FILES
 * @return array Result dengan file content atau error
 */
function handle_file_upload($file_data) {
    if (!isset($file_data) || $file_data['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'file_content' => null, 'file_name' => null];
    }
    
    if ($file_data['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error saat upload file'];
    }
    
    // Validasi tipe file
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/postscript'];
    $file_type = $file_data['type'];
    $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'ai', 'psd', 'eps', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan: PDF, AI, PSD, EPS, JPG, PNG'];
    }
    
    // Validasi ukuran file (maksimal 10MB)
    // $max_size = 10 * 1024 * 1024; // 10MB
    // if ($file_data['size'] > $max_size) {
    //     return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 10MB'];
    // }
    
    // Baca konten file
    $file_content = file_get_contents($file_data['tmp_name']);
    if ($file_content === false) {
        return ['success' => false, 'message' => 'Gagal membaca file'];
    }
    
    return [
        'success' => true, 
        'file_content' => $file_content,
        'file_name' => $file_data['name'],
        'file_size' => $file_data['size'],
        'file_type' => $file_type
    ];
}

/**
 * Menambah desain baru
 * @param array $data Data desain yang akan ditambahkan
 * @param array $file_data Data file dari $_FILES (optional)
 * @return array Status operasi dan data
 */
function tambah_desain($data, $file_data = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Handle file upload jika ada
    $file_content = null;
    $file_name = null;
    if ($file_data) {
        $upload_result = handle_file_upload($file_data);
        if (!$upload_result['success']) {
            close_database($pdo);
            return $upload_result;
        }
        $file_content = $upload_result['file_content'];
        $file_name = $upload_result['file_name'];
    }
    
    // Validasi data
    $validasi = validasi_data_desain($data);
    if (!$validasi || !$validasi['valid']) {
        close_database($pdo);
        $errors = isset($validasi['errors']) && is_array($validasi['errors']) ? $validasi['errors'] : ['Data tidak valid'];
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    try {
        // Insert desain baru dengan file_desain (sesuai schema database)
        $query = "INSERT INTO desain (
            jenis_desain, nama, file_cetak, file_desain, jenis_produk, model_warna, 
            jumlah_warna, sisi, jenis_cover, laminasi, jilid, 
            kualitas_warna, ukuran, halaman, estimasi_waktu_desain, tanggal_selesai
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $data['jenis_desain'],
            $data['nama'],
            $file_name,
            $file_content, // Masuk ke kolom file_desain
            $data['jenis_produk'],
            $data['model_warna'],
            $data['jumlah_warna'],
            $data['sisi'],
            $data['jenis_cover'],
            $data['laminasi'],
            $data['jilid'],
            $data['kualitas_warna'],
            $data['ukuran'],
            $data['halaman'],
            $data['estimasi_waktu_desain'],
            $data['tanggal_selesai']
        ]);
        
        $desain_id = $pdo->lastInsertId();
        
        close_database($pdo);
        
        $file_info = $file_name ? " dengan file: $file_name" : "";
        log_activity("Desain baru ditambahkan: {$data['nama']} (ID: $desain_id)$file_info");
        
        return [
            'success' => true, 
            'message' => 'Desain berhasil ditambahkan',
            'data' => ['id_desain' => $desain_id]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error tambah desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menambahkan desain'];
    }
}

/**
 * Mengambil semua data desain
 * @param int $limit Batas jumlah data (default: 0 = semua)
 * @param int $offset Offset data (default: 0)
 * @return array Data desain
 */
function ambil_semua_desain($limit = 0, $offset = 0) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data desain tanpa file_desain untuk menghemat memory
        $query = "SELECT id_desain, jenis_desain, nama, file_cetak, jenis_produk, model_warna, 
                  jumlah_warna, sisi, jenis_cover, laminasi, jilid, kualitas_warna, ukuran, 
                  halaman, estimasi_waktu_desain, tanggal_selesai 
                  FROM desain ORDER BY nama";
        
        if ($limit > 0) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil semua desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Mengambil data desain berdasarkan ID
 * @param int $id_desain ID desain
 * @return array Data desain
 */
function ambil_desain_by_id($id_desain) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Ambil data desain tanpa file_desain untuk menghemat memory
        $query = "SELECT id_desain, jenis_desain, nama, file_cetak, jenis_produk, model_warna, 
                  jumlah_warna, sisi, jenis_cover, laminasi, jilid, kualitas_warna, ukuran, 
                  halaman, estimasi_waktu_desain, tanggal_selesai 
                  FROM desain WHERE id_desain = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_desain]);
        $desain = $stmt->fetch();
        
        close_database($pdo);
        
        if ($desain) {
            return [
                'success' => true,
                'data' => $desain
            ];
        } else {
            return ['success' => false, 'message' => 'Desain tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil desain by ID: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Update data desain
 * @param int $id_desain ID desain yang akan diupdate
 * @param array $data Data desain yang baru
 * @param array $file_data Data file dari $_FILES (optional)
 * @return array Status operasi
 */
function update_desain($id_desain, $data, $file_data = null) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    // Handle file upload jika ada
    $file_content = null;
    $file_name = null;
    $update_file = false;
    
    if ($file_data) {
        $upload_result = handle_file_upload($file_data);
        if (!$upload_result['success']) {
            close_database($pdo);
            return $upload_result;
        }
        $file_content = $upload_result['file_content'];
        $file_name = $upload_result['file_name'];
        $update_file = true;
    }
    
    // Validasi data
    $validasi = validasi_data_desain($data);
    if (!$validasi || !$validasi['valid']) {
        close_database($pdo);
        $errors = isset($validasi['errors']) && is_array($validasi['errors']) ? $validasi['errors'] : ['Data tidak valid'];
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    try {
        // Cek apakah desain ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM desain WHERE id_desain = ?");
        $stmt->execute([$id_desain]);
        
        if ($stmt->fetchColumn() == 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Desain tidak ditemukan'];
        }
        
        // Update desain
        if ($update_file) {
            // Update dengan file baru menggunakan file_desain
            $query = "UPDATE desain SET 
                jenis_desain = ?, nama = ?, file_cetak = ?, file_desain = ?, jenis_produk = ?, model_warna = ?,
                jumlah_warna = ?, sisi = ?, jenis_cover = ?, laminasi = ?, jilid = ?,
                kualitas_warna = ?, ukuran = ?, halaman = ?, estimasi_waktu_desain = ?, tanggal_selesai = ?
                WHERE id_desain = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $data['jenis_desain'],
                $data['nama'],
                $file_name,
                $file_content, // Masuk ke kolom file_desain
                $data['jenis_produk'],
                $data['model_warna'],
                $data['jumlah_warna'],
                $data['sisi'],
                $data['jenis_cover'],
                $data['laminasi'],
                $data['jilid'],
                $data['kualitas_warna'],
                $data['ukuran'],
                $data['halaman'],
                $data['estimasi_waktu_desain'],
                $data['tanggal_selesai'],
                $id_desain
            ]);
        } else {
            // Update tanpa mengubah file
            $query = "UPDATE desain SET 
                jenis_desain = ?, nama = ?, jenis_produk = ?, model_warna = ?,
                jumlah_warna = ?, sisi = ?, jenis_cover = ?, laminasi = ?, jilid = ?,
                kualitas_warna = ?, ukuran = ?, halaman = ?, estimasi_waktu_desain = ?, tanggal_selesai = ?
                WHERE id_desain = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $data['jenis_desain'],
                $data['nama'],
                $data['jenis_produk'],
                $data['model_warna'],
                $data['jumlah_warna'],
                $data['sisi'],
                $data['jenis_cover'],
                $data['laminasi'],
                $data['jilid'],
                $data['kualitas_warna'],
                $data['ukuran'],
                $data['halaman'],
                $data['estimasi_waktu_desain'],
                $data['tanggal_selesai'],
                $id_desain
            ]);
        }
        
        close_database($pdo);
        
        $file_info = $update_file ? " dengan file baru: $file_name" : "";
        log_activity("Desain diupdate: {$data['nama']} (ID: $id_desain)$file_info");
        
        return ['success' => true, 'message' => 'Desain berhasil diupdate'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error update desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengupdate desain'];
    }
}

/**
 * Hapus desain
 * @param int $id_desain ID desain yang akan dihapus
 * @return array Status operasi
 */
function hapus_desain($id_desain) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Cek apakah desain memiliki pesanan aktif
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_desain = ?");
        $stmt->execute([$id_desain]);
        
        if ($stmt->fetchColumn() > 0) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Desain tidak dapat dihapus karena memiliki pesanan'];
        }
        
        // Ambil data desain sebelum dihapus untuk log
        $stmt = $pdo->prepare("SELECT nama FROM desain WHERE id_desain = ?");
        $stmt->execute([$id_desain]);
        $desain = $stmt->fetch();
        
        if (!$desain) {
            close_database($pdo);
            return ['success' => false, 'message' => 'Desain tidak ditemukan'];
        }
        
        // Hapus desain
        $stmt = $pdo->prepare("DELETE FROM desain WHERE id_desain = ?");
        $stmt->execute([$id_desain]);
        
        close_database($pdo);
        
        log_activity("Desain dihapus: {$desain['nama']} (ID: $id_desain)");
        
        return ['success' => true, 'message' => 'Desain berhasil dihapus'];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hapus desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghapus desain'];
    }
}

/**
 * Download file desain
 * @param int $id_desain ID desain
 * @return array File content dan info atau error
 */
function download_file_desain($id_desain) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Menggunakan file_desain sesuai dengan schema database
        $query = "SELECT file_cetak, file_desain FROM desain WHERE id_desain = ? AND file_desain IS NOT NULL";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_desain]);
        $result = $stmt->fetch();
        
        close_database($pdo);
        
        if ($result && $result['file_desain']) {
            return [
                'success' => true,
                'file_name' => $result['file_cetak'],
                'file_content' => $result['file_desain'] // Menggunakan file_desain
            ];
        } else {
            return ['success' => false, 'message' => 'File tidak ditemukan'];
        }
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error download file desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengunduh file'];
    }
}

/**
 * Mengambil jumlah total desain
 * @return int Jumlah desain
 */
function hitung_total_desain() {
    $pdo = connect_database();
    if (!$pdo) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM desain");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        close_database($pdo);
        return $total;
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung total desain: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Cari desain berdasarkan nama atau jenis produk
 * @param string $keyword Kata kunci pencarian
 * @return array Data desain yang ditemukan
 */
function cari_desain($keyword) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM desain 
                  WHERE nama LIKE ? OR jenis_produk LIKE ? OR jenis_desain LIKE ?
                  ORDER BY nama";
        
        $search_term = "%$keyword%";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error cari desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mencari desain'];
    }
}

/**
 * Mengambil desain berdasarkan jenis produk
 * @param string $jenis_produk Jenis produk
 * @return array Data desain
 */
function ambil_desain_by_jenis_produk($jenis_produk) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM desain WHERE jenis_produk = ? ORDER BY nama";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$jenis_produk]);
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil desain by jenis produk: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Mengambil statistik desain berdasarkan jenis
 * @return array Statistik desain
 */
function hitung_statistik_desain() {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        // Statistik berdasarkan jenis desain
        $stmt = $pdo->prepare("
            SELECT jenis_desain, COUNT(*) as jumlah 
            FROM desain 
            GROUP BY jenis_desain
        ");
        $stmt->execute();
        $by_jenis = $stmt->fetchAll();
        
        // Statistik berdasarkan jenis produk
        $stmt = $pdo->prepare("
            SELECT jenis_produk, COUNT(*) as jumlah 
            FROM desain 
            GROUP BY jenis_produk
        ");
        $stmt->execute();
        $by_produk = $stmt->fetchAll();
        
        // Statistik berdasarkan model warna
        $stmt = $pdo->prepare("
            SELECT model_warna, COUNT(*) as jumlah 
            FROM desain 
            GROUP BY model_warna
        ");
        $stmt->execute();
        $by_warna = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => [
                'by_jenis' => $by_jenis,
                'by_produk' => $by_produk,
                'by_warna' => $by_warna
            ]
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error hitung statistik desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal menghitung statistik desain'];
    }
}

/**
 * Mengambil desain dengan estimasi waktu terlama
 * @param int $limit Jumlah desain yang diambil (default: 10)
 * @return array Data desain
 */
function ambil_desain_estimasi_terlama($limit = 10) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT id_desain, nama, jenis_desain, jenis_produk, estimasi_waktu_desain
                  FROM desain 
                  ORDER BY estimasi_waktu_desain DESC 
                  LIMIT ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil desain estimasi terlama: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Mengambil desain berdasarkan jenis desain (default, sederhana, kompleks, premium)
 * @param string $jenis_desain Jenis desain
 * @return array Data desain
 */
function ambil_desain_by_jenis_desain($jenis_desain) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM desain WHERE jenis_desain = ? ORDER BY nama";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$jenis_desain]);
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil desain by jenis desain: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Mengambil desain berdasarkan model warna
 * @param string $model_warna Model warna (fullcolor, b/w, dua warna)
 * @return array Data desain
 */
function ambil_desain_by_model_warna($model_warna) {
    $pdo = connect_database();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Koneksi database gagal'];
    }
    
    try {
        $query = "SELECT * FROM desain WHERE model_warna = ? ORDER BY nama";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$model_warna]);
        $desains = $stmt->fetchAll();
        
        close_database($pdo);
        
        return [
            'success' => true,
            'data' => $desains
        ];
        
    } catch (PDOException $e) {
        close_database($pdo);
        log_activity("Error ambil desain by model warna: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => 'Gagal mengambil data desain'];
    }
}

/**
 * Hitung estimasi waktu desain berdasarkan jenis
 * @param string $jenis_desain Jenis desain
 * @return int Estimasi waktu dalam hari
 */
function hitung_estimasi_waktu_desain($jenis_desain) {
    switch ($jenis_desain) {
        case 'desain default':
            return 0; // ready-to-use templates, no design time required
        case 'desain sederhana':
            return 3; // simple customization and layout adjustments
        case 'desain kompleks':
            return 10; // complex layouts, multiple revisions, custom graphics
        case 'desain premium':
            return 20; // premium quality, extensive customization, multiple design iterations
        default:
            return 5; // default fallback
    }
}
?>
