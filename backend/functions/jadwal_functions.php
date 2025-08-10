<?php
// backend/functions/jadwal_functions.php
require_once __DIR__ . '/../config/constants.php';

function getJadwalList(): array {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM jadwal_produksi ORDER BY tanggal_mulai ASC";
    $res = $conn->query($sql);
    if (!$res) {
        die("Query error: " . $conn->error);
    }
    $jadwal_list = [];
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $jadwal_list[] = $row;
        }
    }
    $conn->close();
    return $jadwal_list;
}

function getJadwalProduksi(string $tanggal_mulai_produksi = null, string $filter_tanggal = null): array {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Set default start date to today if not provided
    if (!$tanggal_mulai_produksi) {
        $tanggal_mulai_produksi = date('Y-m-d');
    }

    // Get production capacity
    $sqlKapasitas = "SELECT kph FROM jadwal_produksi ORDER BY id_jadwal DESC LIMIT 1";
    $resKapasitas = $conn->query($sqlKapasitas);
    $kapasitas_perhari = ($resKapasitas && $resKapasitas->num_rows > 0)
        ? (int)$resKapasitas->fetch_assoc()['kph']
        : KAPASITAS_PER_HARI;

    $menit_per_hari = 480;

    $sql = "
    SELECT 
        e.id_estimasi,
        e.id_pesanan,
        e.waktu_desain,
        e.tanggal_estimasi,
        p.jumlah,
        p.nama_pemesan,
        p.tanggal_pesanan,
        e.tanggal_estimasi_selesai,
        e.waktu_hari,
        d.nama 
    FROM estimasi e
    JOIN pesanan p ON e.id_pesanan = p.id_pesanan
    JOIN desain d ON p.id_desain = d.id_desain
    ORDER BY p.tanggal_pesanan ASC, e.waktu_hari ASC
    ";

    $result = $conn->query($sql);

    $jadwal_produksi = [];
    
    // Tentukan tanggal referensi (tanggal pesanan paling awal)
    $tanggal_referensi = null;
    if ($result && $result->num_rows > 0) {
        $result->data_seek(0);
        while ($temp_row = $result->fetch_assoc()) {
            if ($tanggal_referensi === null || $temp_row['tanggal_pesanan'] < $tanggal_referensi) {
                $tanggal_referensi = $temp_row['tanggal_pesanan'];
            }
        }
        $result->data_seek(0);
    }
    
    if ($tanggal_referensi === null) {
        $tanggal_referensi = date('Y-m-d');
    }

    // Array untuk melacak kapasitas setiap hari
    $kapasitas_harian = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jumlah_sisa = $row['jumlah'];
            
            // Hitung kapan pesanan ini bisa mulai diproduksi
            // 1 hari persiapan produksi + waktu desain
            $delay_hari = ceil($row['waktu_desain'] / $menit_per_hari) + 1; // +1 hari persiapan produksi
            $tanggal_mulai_bisa_produksi = date('Y-m-d', strtotime($row['tanggal_pesanan'] . ' +' . $delay_hari . ' days'));
            
            // Hitung hari minimum dari tanggal referensi
            $hari_minimum = (int)((strtotime($tanggal_mulai_bisa_produksi) - strtotime($tanggal_referensi)) / 86400) + 1;
            if ($hari_minimum < 1) $hari_minimum = 1;

            $tanggal_selesai_estimasi = null;

            while ($jumlah_sisa > 0) {
                // Cari hari produksi yang tersedia mulai dari hari minimum
                $current_hari = $hari_minimum;
                
                // Cari slot yang tersedia
                while (true) {
                    // Inisialisasi kapasitas hari jika belum ada
                    if (!isset($kapasitas_harian[$current_hari])) {
                        $kapasitas_harian[$current_hari] = $kapasitas_perhari;
                    }
                    
                    // Jika ada kapasitas di hari ini, gunakan
                    if ($kapasitas_harian[$current_hari] > 0) {
                        break;
                    }
                    
                    // Jika tidak ada kapasitas, coba hari berikutnya
                    $current_hari++;
                }

                // Produksi sebanyak mungkin di hari ini
                $diproduksi_hari_ini = min($jumlah_sisa, $kapasitas_harian[$current_hari]);
                $jumlah_sisa -= $diproduksi_hari_ini;
                $kapasitas_harian[$current_hari] -= $diproduksi_hari_ini;
                
                // Hitung tanggal produksi aktual
                $tanggal_produksi = date('Y-m-d', strtotime($tanggal_referensi . ' +' . ($current_hari - 1) . ' days'));
                
                // Set estimate untuk produksi terakhir
                if ($jumlah_sisa <= 0) {
                    $tanggal_selesai_estimasi = $tanggal_produksi;
                }

                // Filter by date if needed
                $should_include = true;
                if ($filter_tanggal) {
                    $should_include = ($tanggal_produksi >= $filter_tanggal);
                } elseif ($tanggal_mulai_produksi) {
                    $should_include = ($tanggal_produksi >= $tanggal_mulai_produksi);
                }

                if ($should_include) {
                    $jadwal_produksi[] = [
                        'id_estimasi' => $row['id_estimasi'],
                        'id_pesanan' => $row['id_pesanan'],
                        'waktu_desain_menit' => $row['waktu_desain'],
                        'waktu_desain_hari' => round($row['waktu_desain'] / $menit_per_hari, 2),
                        'jumlah_diproduksi_hari_ini' => $diproduksi_hari_ini,
                        'kapasitas_perhari' => $kapasitas_perhari,
                        'sisa_kapasitas_hari_ini' => $kapasitas_harian[$current_hari],
                        'hari_produksi_ke' => $current_hari,
                        'tanggal_produksi' => $tanggal_produksi,
                        'tanggal_mulai_bisa_produksi' => $tanggal_mulai_bisa_produksi,
                        'nama' => $row['nama'],
                        'nama_pemesan' => $row['nama_pemesan'],
                        'jumlah' => $row['jumlah'],
                        'jumlah_sisa' => $jumlah_sisa,
                        'estimate' => $tanggal_selesai_estimasi ?? $tanggal_produksi,
                        'tanggal_pesanan' => $row['tanggal_pesanan'],
                        'tanggal_estimasi_selesai' => $row['tanggal_estimasi_selesai'],
                        'tanggal_estimasi' => hitungTanggalEstimasiSelesai(
                            $row['tanggal_pesanan'] ?? date('Y-m-d'),
                            (float)($row['waktu_hari'] ?? 0)
                        ),
                    ];
                }
                
                // Set hari minimum berikutnya untuk sisa produksi
                $hari_minimum = $current_hari + 1;
            }
            
            // Update semua entry pesanan ini dengan estimate yang benar
            if (!empty($jadwal_produksi) && $tanggal_selesai_estimasi) {
                for ($i = count($jadwal_produksi) - 1; $i >= 0; $i--) {
                    if ($jadwal_produksi[$i]['id_pesanan'] == $row['id_pesanan']) {
                        $jadwal_produksi[$i]['estimate'] = $tanggal_selesai_estimasi;
                    } else {
                        break;
                    }
                }
            }
        }
    }

    $conn->close();

    // Sort by production date
    usort($jadwal_produksi, function($a, $b) {
        if ($a['tanggal_produksi'] === $b['tanggal_produksi']) {
            return $a['hari_produksi_ke'] - $b['hari_produksi_ke'];
        }
        return strcmp($a['tanggal_produksi'], $b['tanggal_produksi']);
    });

    return $jadwal_produksi;
}

// Helper function to get schedule for a specific date range
function getJadwalProduksiByDateRange(string $start_date, string $end_date = null): array {
    $jadwal = getJadwalProduksi($start_date);
    
    if ($end_date) {
        $jadwal = array_filter($jadwal, function($item) use ($end_date) {
            return $item['tanggal_produksi'] <= $end_date;
        });
    }
    
    return array_values($jadwal);
}

// Helper function to get schedule for today only
function getJadwalProduksiHariIni(): array {
    $today = date('Y-m-d');
    $jadwal = getJadwalProduksi($today);
    
    $jadwal_hari_ini = array_filter($jadwal, function($item) use ($today) {
        return $item['tanggal_produksi'] === $today;
    });
    
    return array_values($jadwal_hari_ini);
}

/**
 * Calculate estimated completion date based on order date and estimation time
 * Formula: order_date + 1 day (preparation) + ceil(estimation_days)
 * 
 * @param string $tanggal_pesanan Order date in Y-m-d format
 * @param float $waktu_hari Estimation time in days (can be decimal)
 * @return string Estimated completion date in Y-m-d format
 */
function hitungTanggalEstimasiSelesai(string $tanggal_pesanan, float $waktu_hari): string {
    // Validate input
    if (empty($tanggal_pesanan) || $waktu_hari < 0) {
        return date('Y-m-d'); // Return today as fallback
    }
    
    // Round up the estimation days (ceil for decimal values)
    $hari_estimasi = (int) ceil($waktu_hari);
    
    // Add 1 day for preparation + estimation days
    $total_hari = 1 + $hari_estimasi;
    
    // Calculate completion date
    $timestamp = strtotime($tanggal_pesanan . ' +' . $total_hari . ' days');
    
    // Validate timestamp
    if ($timestamp === false) {
        return date('Y-m-d'); // Return today as fallback
    }
    
    return date('Y-m-d', $timestamp);
}