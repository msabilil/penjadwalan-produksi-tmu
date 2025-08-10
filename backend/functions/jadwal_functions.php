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

    $hari = 1;
    $sisa_kapasitas_hari_ini = $kapasitas_perhari;
    $last_tanggal_pesanan = null;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($last_tanggal_pesanan !== null && $row['tanggal_pesanan'] !== $last_tanggal_pesanan) {
                $hari = 1;
                $sisa_kapasitas_hari_ini = $kapasitas_perhari;
            }
            $last_tanggal_pesanan = $row['tanggal_pesanan'];

            $jumlah_sisa = $row['jumlah'];

            $delay_hari = ceil($row['waktu_desain'] / $menit_per_hari);
            if ($delay_hari > 0) {
                $hari += $delay_hari;
                $sisa_kapasitas_hari_ini = $kapasitas_perhari;
            }

            while ($jumlah_sisa > 0) {
                $diproduksi_hari_ini = min($jumlah_sisa, $sisa_kapasitas_hari_ini);
                $jumlah_sisa -= $diproduksi_hari_ini;
                $sisa_kapasitas_hari_ini -= $diproduksi_hari_ini;
                $tanggal_produksi = date('Y-m-d', strtotime($last_tanggal_pesanan . ' +' . ($hari) . ' days'));
                $estimate = date('Y-m-d', strtotime($row['tanggal_estimasi'] . " +". ceil($row['waktu_hari']) ." days"));

                // Create the schedule entry
                $schedule_entry = [
                    'id_estimasi' => $row['id_estimasi'],
                    'id_pesanan' => $row['id_pesanan'],
                    'waktu_desain_menit' => $row['waktu_desain'],
                    'waktu_desain_hari' => round($row['waktu_desain'] / $menit_per_hari, 2),
                    'jumlah_diproduksi_hari_ini' => $diproduksi_hari_ini,
                    'kapasitas_perhari' => $kapasitas_perhari,
                    'sisa_kapasitas_hari_ini' => $sisa_kapasitas_hari_ini,
                    'hari_produksi_ke' => $hari,
                    'tanggal_produksi' => $tanggal_produksi,
                    'nama' => $row['nama'],
                    'nama_pemesan' => $row['nama_pemesan'],
                    'jumlah' => $row['jumlah'],
                    'estimate' => $estimate,
                    'tanggal_pesanan' => $row['tanggal_pesanan'],
                    'tanggal_estimasi_selesai' => $row['tanggal_estimasi_selesai'],
                    'tanggal_estimasi' => 
                        (new DateTime($row['tanggal_pesanan'] ?? date('Y-m-d')))
                        ->modify('+' . ceil($row['waktu_hari'] ?? 0) . ' days')
                        ->format('Y-m-d'),
                ];

                // Add to array (we'll filter later)
                $jadwal_produksi[] = $schedule_entry;

                if ($sisa_kapasitas_hari_ini <= 0 && $jumlah_sisa > 0) {
                    $hari++;
                    $sisa_kapasitas_hari_ini = $kapasitas_perhari;
                }
            }
        }
    }

    $conn->close();

    // Filter by production date (from today or specified date onwards)
    $filtered_jadwal = array_filter($jadwal_produksi, function($item) use ($tanggal_mulai_produksi, $filter_tanggal) {
        // If specific filter date is provided, use it; otherwise use start date
        $filter_date = $filter_tanggal ?? $tanggal_mulai_produksi;
        return $item['tanggal_produksi'] >= $filter_date;
    });

    // Sort by production date
    usort($filtered_jadwal, function($a, $b) {
        return strcmp($a['tanggal_produksi'], $b['tanggal_produksi']);
    });

    return array_values($filtered_jadwal); // Re-index array
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