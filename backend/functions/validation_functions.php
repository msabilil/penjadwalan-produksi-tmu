<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/helper_functions.php';

/**
 * Validation functions untuk berbagai data input
 */

/**
 * Validasi data user
 * @param array $data Data user yang akan divalidasi
 * @return array Array dengan status dan pesan error
 */
function validasi_data_user($data) {
    $errors = [];
    
    // Validasi username
    if (empty($data['username'])) {
        $errors[] = "Username tidak boleh kosong";
    } elseif (strlen($data['username']) < 3) {
        $errors[] = "Username minimal 3 karakter";
    } elseif (strlen($data['username']) > 50) {
        $errors[] = "Username maksimal 50 karakter";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = "Username hanya boleh mengandung huruf, angka, dan underscore";
    }
    
    // Validasi password (hanya jika ada)
    if (isset($data['password']) && !empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            $errors[] = "Password minimal 6 karakter";
        }
    }
    
    // Validasi nama
    if (empty($data['nama'])) {
        $errors[] = "Nama tidak boleh kosong";
    } elseif (strlen($data['nama']) > 100) {
        $errors[] = "Nama maksimal 100 karakter";
    }
    
    // Validasi role
    $valid_roles = [ROLE_ADMINISTRATOR, ROLE_STAF_PENJUALAN, ROLE_MANAJER_PENERBIT, ROLE_SUPERVISOR_PRODUKSI];
    if (empty($data['role'])) {
        $errors[] = "Role tidak boleh kosong";
    } elseif (!in_array($data['role'], $valid_roles)) {
        $errors[] = "Role tidak valid";
    }
    
    // Validasi nomor telepon (opsional)
    if (!empty($data['no_telepon'])) {
        if (strlen($data['no_telepon']) > 20) {
            $errors[] = "Nomor telepon maksimal 20 karakter";
        } elseif (!preg_match('/^[0-9+\-\s]+$/', $data['no_telepon'])) {
            $errors[] = "Format nomor telepon tidak valid";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data desain
 * @param array $data Data desain yang akan divalidasi
 * @return array Array dengan status dan pesan error
 */
function validasi_data_desain($data) {
    $errors = [];
    
    // Validasi jenis desain
    $valid_jenis = ['desain default', 'desain sederhana', 'desain kompleks', 'desain premium'];
    if (empty($data['jenis_desain'])) {
        $errors[] = "Jenis desain tidak boleh kosong";
    } elseif (!in_array($data['jenis_desain'], $valid_jenis)) {
        $errors[] = "Jenis desain tidak valid";
    }
    
    // Validasi nama
    if (empty($data['nama'])) {
        $errors[] = "Nama desain tidak boleh kosong";
    } elseif (strlen($data['nama']) > 200) {
        $errors[] = "Nama desain maksimal 200 karakter";
    }
    
    // Validasi jenis produk
    $valid_produk = ['buku', 'majalah', 'katalog', 'kalender', 'soal ujian', 'lembar jawaban ujian'];
    if (empty($data['jenis_produk'])) {
        $errors[] = "Jenis produk tidak boleh kosong";
    } elseif (!in_array($data['jenis_produk'], $valid_produk)) {
        $errors[] = "Jenis produk tidak valid";
    }
    
    // Validasi model warna
    $valid_warna = ['fullcolor', 'b/w', 'dua warna'];
    if (empty($data['model_warna'])) {
        $errors[] = "Model warna tidak boleh kosong";
    } elseif (!in_array($data['model_warna'], $valid_warna)) {
        $errors[] = "Model warna tidak valid";
    }
    
    // Validasi jumlah warna
    if (!isset($data['jumlah_warna']) || !is_numeric($data['jumlah_warna'])) {
        $errors[] = "Jumlah warna harus berupa angka";
    } elseif ($data['jumlah_warna'] < 1 || $data['jumlah_warna'] > 4) {
        $errors[] = "Jumlah warna harus antara 1-4";
    }
    
    // Validasi sisi
    if (!isset($data['sisi']) || !is_numeric($data['sisi'])) {
        $errors[] = "Sisi harus berupa angka";
    } elseif ($data['sisi'] < 1 || $data['sisi'] > 2) {
        $errors[] = "Sisi harus 1 atau 2";
    }
    
    // Validasi jenis cover
    $valid_cover = ['softcover', 'hardcover', 'tidak'];
    if (empty($data['jenis_cover'])) {
        $errors[] = "Jenis cover tidak boleh kosong";
    } elseif (!in_array($data['jenis_cover'], $valid_cover)) {
        $errors[] = "Jenis cover tidak valid";
    }
    
    // Validasi laminasi
    $valid_laminasi = ['glossy', 'doff', 'tidak'];
    if (empty($data['laminasi'])) {
        $errors[] = "Laminasi tidak boleh kosong";
    } elseif (!in_array($data['laminasi'], $valid_laminasi)) {
        $errors[] = "Laminasi tidak valid";
    }
    
    // Validasi jilid
    $valid_jilid = ['lem', 'jahit', 'spiral', 'tidak'];
    if (empty($data['jilid'])) {
        $errors[] = "Jilid tidak boleh kosong";
    } elseif (!in_array($data['jilid'], $valid_jilid)) {
        $errors[] = "Jilid tidak valid";
    }
    
    // Validasi kualitas warna
    $valid_kualitas = ['tinggi', 'cukup'];
    if (empty($data['kualitas_warna'])) {
        $errors[] = "Kualitas warna tidak boleh kosong";
    } elseif (!in_array($data['kualitas_warna'], $valid_kualitas)) {
        $errors[] = "Kualitas warna tidak valid";
    }
    
    // Validasi ukuran
    if (empty($data['ukuran'])) {
        $errors[] = "Ukuran tidak boleh kosong";
    } elseif (strlen($data['ukuran']) > 20) {
        $errors[] = "Ukuran maksimal 20 karakter";
    }
    
    // Validasi halaman
    if (!isset($data['halaman']) || !is_numeric($data['halaman'])) {
        $errors[] = "Halaman harus berupa angka";
    } elseif ($data['halaman'] < 1 || $data['halaman'] > 32767) {
        $errors[] = "Jumlah halaman tidak valid";
    }
    
    // Validasi estimasi waktu desain
    if (!isset($data['estimasi_waktu_desain']) || !is_numeric($data['estimasi_waktu_desain'])) {
        $errors[] = "Estimasi waktu desain harus berupa angka";
    } elseif ($data['estimasi_waktu_desain'] < 0) {
        $errors[] = "Estimasi waktu desain minimal 0 menit";
    }
    
    // Validasi tanggal selesai (opsional)
    if (!empty($data['tanggal_selesai']) && !is_valid_date($data['tanggal_selesai'])) {
        $errors[] = "Format tanggal selesai tidak valid";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data mesin
 * @param array $data Data mesin yang akan divalidasi
 * @return array Array dengan status dan pesan error
 */
function validasi_data_mesin($data) {
    $errors = [];
    
    // Validasi nama mesin
    if (empty($data['nama_mesin'])) {
        $errors[] = "Nama mesin tidak boleh kosong";
    } elseif (strlen($data['nama_mesin']) > 100) {
        $errors[] = "Nama mesin maksimal 100 karakter";
    }
    
    // Validasi urutan proses
    if (!isset($data['urutan_proses']) || !is_numeric($data['urutan_proses'])) {
        $errors[] = "Urutan proses harus berupa angka";
    } elseif ($data['urutan_proses'] < 1 || $data['urutan_proses'] > 8) {
        $errors[] = "Urutan proses harus antara 1-8";
    }
    
    // Validasi kapasitas
    if (!isset($data['kapasitas']) || !is_numeric($data['kapasitas'])) {
        $errors[] = "Kapasitas harus berupa angka";
    } elseif ($data['kapasitas'] < 1) {
        $errors[] = "Kapasitas harus lebih dari 0";
    }
    
    // Validasi waktu setup
    if (!isset($data['waktu_setup']) || !is_numeric($data['waktu_setup'])) {
        $errors[] = "Waktu setup harus berupa angka";
    } elseif ($data['waktu_setup'] < 0) {
        $errors[] = "Waktu setup tidak boleh negatif";
    }
    
    // Validasi waktu mesin per eksemplar
    if (!isset($data['waktu_mesin_per_eks']) || !is_numeric($data['waktu_mesin_per_eks'])) {
        $errors[] = "Waktu mesin per eksemplar harus berupa angka";
    } elseif ($data['waktu_mesin_per_eks'] <= 0) {
        $errors[] = "Waktu mesin per eksemplar harus lebih dari 0";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data pesanan
 * @param array $data Data pesanan yang akan divalidasi
 * @return array Array dengan status dan pesan error
 */
function validasi_data_pesanan($data) {
    $errors = [];
    
    // Validasi ID desain (opsional - bisa kosong untuk pesanan yang membutuhkan desain baru)
    if (!empty($data['id_desain'])) {
        if (!is_numeric($data['id_desain'])) {
            $errors[] = "ID desain harus berupa angka";
        } elseif ($data['id_desain'] < 1) {
            $errors[] = "ID desain tidak valid";
        }
    }
    
    // Validasi ID user
    if (!isset($data['id_user']) || !is_numeric($data['id_user'])) {
        $errors[] = "ID user harus berupa angka";
    } elseif ($data['id_user'] < 1) {
        $errors[] = "ID user tidak valid";
    }
    
    // Validasi nomor PO (opsional - akan di-generate otomatis jika kosong)
    if (!empty($data['no'])) {
        if (strlen($data['no']) > 50) {
            $errors[] = "Nomor PO maksimal 50 karakter";
        }
    }
    
    // Validasi nama pemesan
    if (empty($data['nama_pemesan'])) {
        $errors[] = "Nama pemesan tidak boleh kosong";
    } elseif (strlen($data['nama_pemesan']) > 100) {
        $errors[] = "Nama pemesan maksimal 100 karakter";
    }
    
    // Validasi nomor telepon (opsional)
    if (!empty($data['no_telepon'])) {
        if (strlen($data['no_telepon']) > 20) {
            $errors[] = "Nomor telepon maksimal 20 karakter";
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $data['no_telepon'])) {
            $errors[] = "Format nomor telepon tidak valid";
        }
    }
    
    // Validasi alamat (opsional)
    if (!empty($data['alamat']) && strlen($data['alamat']) > 500) {
        $errors[] = "Alamat maksimal 500 karakter";
    }
    
    // Validasi jumlah
    if (!isset($data['jumlah']) || !is_numeric($data['jumlah'])) {
        $errors[] = "Jumlah harus berupa angka";
    } elseif ($data['jumlah'] < 1) {
        $errors[] = "Jumlah harus lebih dari 0";
    }
    
    // Validasi tanggal pesanan
    if (empty($data['tanggal_pesanan'])) {
        $errors[] = "Tanggal pesanan tidak boleh kosong";
    } elseif (!is_valid_date($data['tanggal_pesanan'])) {
        $errors[] = "Format tanggal pesanan tidak valid";
    }
    
    // Validasi deskripsi (opsional)
    if (!empty($data['deskripsi']) && strlen($data['deskripsi']) > 1000) {
        $errors[] = "Deskripsi maksimal 1000 karakter";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi email
 * @param string $email Email yang akan divalidasi
 * @return bool True jika valid
 */
function validasi_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validasi data estimasi
 * @param array $data Data estimasi yang akan divalidasi
 * @param bool $check_pesanan_id Apakah perlu validasi id_pesanan (default: true)
 * @return array Array dengan status dan pesan error
 */
function validasi_data_estimasi($data, $check_pesanan_id = true) {
    $errors = [];
    
    // Validasi ID pesanan (hanya jika diperlukan)
    if ($check_pesanan_id) {
        if (!isset($data['id_pesanan']) || !is_numeric($data['id_pesanan'])) {
            $errors[] = "ID pesanan harus berupa angka";
        } elseif ($data['id_pesanan'] < 1) {
            $errors[] = "ID pesanan tidak valid";
        }
    }
    
    // Validasi waktu (semua dalam menit)
    $waktu_fields = ['waktu_desain', 'waktu_plat', 'waktu_total_setup', 'waktu_mesin', 'waktu_qc', 'waktu_packing', 'waktu_menit', 'waktu_jam', 'waktu_hari'];
    
    foreach ($waktu_fields as $field) {
        if (isset($data[$field])) {
            if (!is_numeric($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " harus berupa angka";
            } elseif ($data[$field] < 0) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " tidak boleh negatif";
            }
        }
    }
    
    // Validasi tanggal estimasi
    if (isset($data['tanggal_estimasi'])) {
        if (empty($data['tanggal_estimasi'])) {
            $errors[] = "Tanggal estimasi tidak boleh kosong";
        } elseif (!is_valid_date($data['tanggal_estimasi'])) {
            $errors[] = "Format tanggal estimasi tidak valid";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data detail estimasi
 * @param array $data Data detail estimasi yang akan divalidasi
 * @param bool $check_estimasi_id Apakah perlu validasi id_estimasi (default: true)
 * @return array Array dengan status dan pesan error
 */
function validasi_data_detail_estimasi($data, $check_estimasi_id = true) {
    $errors = [];
    
    // Validasi ID estimasi (hanya jika diperlukan)
    if ($check_estimasi_id) {
        if (!isset($data['id_estimasi']) || !is_numeric($data['id_estimasi'])) {
            $errors[] = "ID estimasi harus berupa angka";
        } elseif ($data['id_estimasi'] < 1) {
            $errors[] = "ID estimasi tidak valid";
        }
    }
    
    // Validasi field numerik yang harus positif
    $numeric_fields = [
        'waktu_desain' => 'Waktu desain',
        'waktu_per_plat' => 'Waktu per plat',
        'waktu_manual_hardcover' => 'Waktu manual hardcover',
        'waktu_standar_qc' => 'Waktu standar QC',
        'waktu_standar_packing' => 'Waktu standar packing',
        'waktu_mesin_per_eks' => 'Waktu mesin per eksemplar'
    ];
    
    foreach ($numeric_fields as $field => $label) {
        if (isset($data[$field])) {
            if (!is_numeric($data[$field])) {
                $errors[] = "$label harus berupa angka";
            } elseif ($data[$field] < 0) {
                $errors[] = "$label tidak boleh negatif";
            }
        }
    }
    
    // Validasi field integer yang harus positif
    $integer_fields = [
        'jumlah_desainer' => 'Jumlah desainer',
        'jumlah_plat' => 'Jumlah plat',
        'jumlah_halaman_per_plat' => 'Jumlah halaman per plat',
        'jumlah_plat_per_set' => 'Jumlah plat per set',
        'pekerja_qc' => 'Pekerja QC',
        'kapasitas_box' => 'Kapasitas box',
        'jumlah_box' => 'Jumlah box',
        'pekerja_packing' => 'Pekerja packing'
    ];
    
    foreach ($integer_fields as $field => $label) {
        if (isset($data[$field])) {
            if (!is_numeric($data[$field]) || !is_int($data[$field] + 0)) {
                $errors[] = "$label harus berupa bilangan bulat";
            } elseif ($data[$field] < 0) {
                $errors[] = "$label tidak boleh negatif";
            }
        }
    }
    
    // Validasi khusus untuk field tertentu
    if (isset($data['jumlah_desainer']) && $data['jumlah_desainer'] < 1) {
        $errors[] = "Jumlah desainer minimal 1";
    }
    
    if (isset($data['jumlah_halaman_per_plat']) && $data['jumlah_halaman_per_plat'] < 1) {
        $errors[] = "Jumlah halaman per plat minimal 1";
    }
    
    if (isset($data['pekerja_qc']) && $data['pekerja_qc'] < 1) {
        $errors[] = "Pekerja QC minimal 1";
    }
    
    if (isset($data['pekerja_packing']) && $data['pekerja_packing'] < 1) {
        $errors[] = "Pekerja packing minimal 1";
    }
    
    if (isset($data['kapasitas_box']) && $data['kapasitas_box'] < 1) {
        $errors[] = "Kapasitas box minimal 1";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data jadwal produksi
 * @param array $data Data jadwal produksi yang akan divalidasi
 * @param bool $check_required_ids Apakah perlu validasi ID yang required (default: true)
 * @return array Array dengan status dan pesan error
 */
function validasi_data_jadwal($data, $check_required_ids = true) {
    $errors = [];
    
    // Validasi ID estimasi (hanya jika diperlukan)
    if ($check_required_ids) {
        if (!isset($data['id_estimasi']) || !is_numeric($data['id_estimasi'])) {
            $errors[] = "ID estimasi harus berupa angka";
        } elseif ($data['id_estimasi'] < 1) {
            $errors[] = "ID estimasi tidak valid";
        }
        
        // Validasi ID mesin
        if (!isset($data['id_mesin']) || !is_numeric($data['id_mesin'])) {
            $errors[] = "ID mesin harus berupa angka";
        } elseif ($data['id_mesin'] < 1) {
            $errors[] = "ID mesin tidak valid";
        }
        
        // Validasi jumlah batch ini
        if (!isset($data['jumlah_batch_ini']) || !is_numeric($data['jumlah_batch_ini'])) {
            $errors[] = "Jumlah batch ini harus berupa angka";
        } elseif ($data['jumlah_batch_ini'] < 1) {
            $errors[] = "Jumlah batch ini minimal 1";
        }
        
        // Validasi tanggal mulai
        if (!isset($data['tanggal_mulai']) || empty($data['tanggal_mulai'])) {
            $errors[] = "Tanggal mulai tidak boleh kosong";
        }
        
        // Validasi tanggal selesai
        if (!isset($data['tanggal_selesai']) || empty($data['tanggal_selesai'])) {
            $errors[] = "Tanggal selesai tidak boleh kosong";
        }
    }
    
    // Validasi nomor jadwal jika ada
    if (isset($data['no_jadwal'])) {
        if (empty($data['no_jadwal'])) {
            $errors[] = "Nomor jadwal tidak boleh kosong";
        } elseif (strlen($data['no_jadwal']) > 50) {
            $errors[] = "Nomor jadwal maksimal 50 karakter";
        }
    }
    
    // Validasi batch_ke jika ada
    if (isset($data['batch_ke'])) {
        if (!is_numeric($data['batch_ke']) || !is_int($data['batch_ke'] + 0)) {
            $errors[] = "Batch ke harus berupa bilangan bulat";
        } elseif ($data['batch_ke'] < 1) {
            $errors[] = "Batch ke minimal 1";
        }
    }
    
    // Validasi jumlah_batch_ini jika ada
    if (isset($data['jumlah_batch_ini'])) {
        if (!is_numeric($data['jumlah_batch_ini'])) {
            $errors[] = "Jumlah batch ini harus berupa angka";
        } elseif ($data['jumlah_batch_ini'] < 1) {
            $errors[] = "Jumlah batch ini minimal 1";
        }
    }
    
    // Validasi format datetime
    if (isset($data['tanggal_mulai']) && !empty($data['tanggal_mulai'])) {
        if (!is_valid_datetime($data['tanggal_mulai'])) {
            $errors[] = "Format tanggal mulai tidak valid (gunakan YYYY-MM-DD HH:MM:SS)";
        }
    }
    
    if (isset($data['tanggal_selesai']) && !empty($data['tanggal_selesai'])) {
        if (!is_valid_datetime($data['tanggal_selesai'])) {
            $errors[] = "Format tanggal selesai tidak valid (gunakan YYYY-MM-DD HH:MM:SS)";
        }
    }
    
    // Validasi tanggal selesai harus setelah tanggal mulai
    if (isset($data['tanggal_mulai']) && isset($data['tanggal_selesai']) && 
        !empty($data['tanggal_mulai']) && !empty($data['tanggal_selesai'])) {
        if (strtotime($data['tanggal_selesai']) <= strtotime($data['tanggal_mulai'])) {
            $errors[] = "Tanggal selesai harus setelah tanggal mulai";
        }
    }
    
    // Validasi status
    if (isset($data['status'])) {
        $valid_status = ['terjadwal', 'dalam proses', 'selesai', 'terlambat', 'selesai lebih cepat'];
        if (!in_array($data['status'], $valid_status)) {
            $errors[] = "Status tidak valid";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi data detail jadwal
 * @param array $data Data detail jadwal yang akan divalidasi
 * @param bool $check_required_ids Apakah perlu validasi ID yang required (default: true)
 * @return array Array dengan status dan pesan error
 */
function validasi_data_detail_jadwal($data, $check_required_ids = true) {
    $errors = [];
    
    // Validasi ID jadwal (hanya jika diperlukan)
    if ($check_required_ids) {
        if (!isset($data['id_jadwal']) || !is_numeric($data['id_jadwal'])) {
            $errors[] = "ID jadwal harus berupa angka";
        } elseif ($data['id_jadwal'] < 1) {
            $errors[] = "ID jadwal tidak valid";
        }
        
        // Validasi ID mesin
        if (!isset($data['id_mesin']) || !is_numeric($data['id_mesin'])) {
            $errors[] = "ID mesin harus berupa angka";
        } elseif ($data['id_mesin'] < 1) {
            $errors[] = "ID mesin tidak valid";
        }
        
        // Validasi urutan proses
        if (!isset($data['urutan_proses']) || !is_numeric($data['urutan_proses'])) {
            $errors[] = "Urutan proses harus berupa angka";
        } elseif ($data['urutan_proses'] < 1) {
            $errors[] = "Urutan proses minimal 1";
        }
        
        // Validasi nama proses
        if (!isset($data['nama_proses']) || empty($data['nama_proses'])) {
            $errors[] = "Nama proses tidak boleh kosong";
        }
        
        // Validasi waktu mulai
        if (!isset($data['waktu_mulai']) || empty($data['waktu_mulai'])) {
            $errors[] = "Waktu mulai tidak boleh kosong";
        }
        
        // Validasi waktu selesai
        if (!isset($data['waktu_selesai']) || empty($data['waktu_selesai'])) {
            $errors[] = "Waktu selesai tidak boleh kosong";
        }
        
        // Validasi estimasi waktu menit
        if (!isset($data['estimasi_waktu_menit']) || !is_numeric($data['estimasi_waktu_menit'])) {
            $errors[] = "Estimasi waktu menit harus berupa angka";
        } elseif ($data['estimasi_waktu_menit'] < 1) {
            $errors[] = "Estimasi waktu menit minimal 1";
        }
    }
    
    // Validasi nama proses jika ada
    if (isset($data['nama_proses'])) {
        if (empty($data['nama_proses'])) {
            $errors[] = "Nama proses tidak boleh kosong";
        } elseif (strlen($data['nama_proses']) > 100) {
            $errors[] = "Nama proses maksimal 100 karakter";
        }
    }
    
    // Validasi urutan proses jika ada
    if (isset($data['urutan_proses'])) {
        if (!is_numeric($data['urutan_proses']) || !is_int($data['urutan_proses'] + 0)) {
            $errors[] = "Urutan proses harus berupa bilangan bulat";
        } elseif ($data['urutan_proses'] < 1) {
            $errors[] = "Urutan proses minimal 1";
        } elseif ($data['urutan_proses'] > 999) {
            $errors[] = "Urutan proses maksimal 999";
        }
    }
    
    // Validasi estimasi waktu menit jika ada
    if (isset($data['estimasi_waktu_menit'])) {
        if (!is_numeric($data['estimasi_waktu_menit'])) {
            $errors[] = "Estimasi waktu menit harus berupa angka";
        } elseif ($data['estimasi_waktu_menit'] < 1) {
            $errors[] = "Estimasi waktu menit minimal 1";
        } elseif ($data['estimasi_waktu_menit'] > 1440) { // Maksimal 24 jam
            $errors[] = "Estimasi waktu menit maksimal 1440 (24 jam)";
        }
    }
    
    // Validasi format datetime
    if (isset($data['waktu_mulai']) && !empty($data['waktu_mulai'])) {
        if (!is_valid_datetime($data['waktu_mulai'])) {
            $errors[] = "Format waktu mulai tidak valid (gunakan YYYY-MM-DD HH:MM:SS)";
        }
    }
    
    if (isset($data['waktu_selesai']) && !empty($data['waktu_selesai'])) {
        if (!is_valid_datetime($data['waktu_selesai'])) {
            $errors[] = "Format waktu selesai tidak valid (gunakan YYYY-MM-DD HH:MM:SS)";
        }
    }
    
    // Validasi waktu selesai harus setelah waktu mulai
    if (isset($data['waktu_mulai']) && isset($data['waktu_selesai']) && 
        !empty($data['waktu_mulai']) && !empty($data['waktu_selesai'])) {
        if (strtotime($data['waktu_selesai']) <= strtotime($data['waktu_mulai'])) {
            $errors[] = "Waktu selesai harus setelah waktu mulai";
        }
    }
    
    // Validasi status
    if (isset($data['status'])) {
        $valid_status = ['terjadwal', 'dalam proses', 'selesai', 'terlambat', 'selesai lebih cepat'];
        if (!in_array($data['status'], $valid_status)) {
            $errors[] = "Status tidak valid";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validasi format datetime
 * @param string $datetime Datetime yang akan divalidasi
 * @return bool True jika valid
 */
function is_valid_datetime($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

/**
 * Validasi nomor telepon Indonesia
 * @param string $phone Nomor telepon yang akan divalidasi
 * @return bool True jika valid
 */
function validasi_nomor_telepon($phone) {
    // Format nomor telepon Indonesia: 08xx-xxxx-xxxx atau +62xx-xxxx-xxxx
    $pattern = '/^(\+62|62|0)8[0-9]{2,3}-?[0-9]{3,4}-?[0-9]{3,4}$/';
    return preg_match($pattern, $phone);
}
?>
