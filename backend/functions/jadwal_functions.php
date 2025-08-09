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

function getJadwalProduksi(string $tanggal_mulai_produksi = null): array {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Set default tanggal mulai produksi = hari ini jika null
    if (!$tanggal_mulai_produksi) {
        $tanggal_mulai_produksi = date('Y-m-d');
    }

    // Ambil kapasitas per hari dari DB
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
        e.waktu_hari,
        d.nama 
    FROM estimasi e
    JOIN pesanan p ON e.id_pesanan = p.id_pesanan
    JOIN desain d ON p.id_desain = d.id_desain
    ORDER BY e.waktu_hari ASC
";

    $result = $conn->query($sql);

    $jadwal_produksi = [];

    if ($result && $result->num_rows > 0) {
        $hari = 1;
        $sisa_kapasitas_hari_ini = $kapasitas_perhari;

        while ($row = $result->fetch_assoc()) {
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

                // Hitung tanggal produksi ke-$hari dari $tanggal_mulai_produksi
                $tanggal_produksi = date('Y-m-d', strtotime($tanggal_mulai_produksi . ' +' . ($hari - 1) . ' days'));
              $waktu_hari_bulat = ceil($row['waktu_hari']);
$estimate = date('Y-m-d', strtotime($row['tanggal_estimasi'] . " +{$waktu_hari_bulat} days"));

                $jadwal_produksi[] = [
                    'id_estimasi' => $row['id_estimasi'],
                    'id_pesanan' => $row['id_pesanan'],
                    'waktu_desain_menit' => $row['waktu_desain'],
                    'waktu_desain_hari' => round($row['waktu_desain'] / $menit_per_hari, 2),
                    'jumlah_diproduksi_hari_ini' => $diproduksi_hari_ini,
                    'kapasitas_perhari' => $kapasitas_perhari,
                    'sisa_kapasitas_hari_ini' => $sisa_kapasitas_hari_ini,
                    'hari_produksi_ke' => $hari,
                    'tanggal_produksi' => $tanggal_produksi,  // tambahan tanggal produksi
                    'nama' => $row['nama'],
                    'nama_pemesan' => $row['nama_pemesan'],
                    'jumlah' => $row['jumlah'],
                    'tanggal_estimasi' => $estimate,
                ];

                if ($sisa_kapasitas_hari_ini <= 0 && $jumlah_sisa > 0) {
                    $hari++;
                    $sisa_kapasitas_hari_ini = $kapasitas_perhari;
                }
            }
        }
    }

    $conn->close();

    return $jadwal_produksi;
}
