CREATE DATABASE penjadwalan_produksi_tmu;

CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('administrator', 'staf penjualan', 'manager penerbit', 'supervisor produksi') NOT NULL,
    no_telepon VARCHAR(20)
);

CREATE TABLE desain (
    id_desain INT PRIMARY KEY AUTO_INCREMENT,
    jenis_desain ENUM('desain default', 'desain sederhana', 'desain kompleks', 'desain premium') NOT NULL,
    nama VARCHAR(255) NOT NULL,
    file_cetak VARCHAR(255),
    file_desain LONGBLOB,
    jenis_produk ENUM('buku', 'majalah', 'katalog', 'kalender', 'soal ujian', 'lembar jawaban ujian') NOT NULL,
    model_warna ENUM('fullcolor', 'b/w', 'dua warna') NOT NULL,
    jumlah_warna TINYINT NOT NULL COMMENT '4=fullcolor, 1=b/w, 2=duawarna',
    sisi TINYINT NOT NULL COMMENT '1 atau 2',
    jenis_cover ENUM('softcover', 'hardcover', 'tidak') NOT NULL,
    laminasi ENUM('glossy', 'doff', 'tidak') NOT NULL,
    jilid ENUM('lem', 'jahit', 'spiral', 'tidak') NOT NULL,
    kualitas_warna ENUM('tinggi', 'cukup') NOT NULL,
    ukuran VARCHAR(20) NOT NULL,
    halaman SMALLINT NOT NULL,
    estimasi_waktu_desain SMALLINT NOT NULL,
    tanggal_selesai DATE
);

CREATE TABLE pesanan (
    id_pesanan INT PRIMARY KEY AUTO_INCREMENT,
    id_desain INT NOT NULL,
    id_user INT NOT NULL,
    no VARCHAR(50) UNIQUE NOT NULL,
    nama_pemesan VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    jumlah INT NOT NULL,
    tanggal_pesanan DATE NOT NULL,
    deskripsi TEXT,
    FOREIGN KEY (id_desain) REFERENCES desain(id_desain),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
);

CREATE TABLE mesin (
    id_mesin INT PRIMARY KEY AUTO_INCREMENT,
    nama_mesin VARCHAR(100) NOT NULL,
    urutan_proses TINYINT NOT NULL,
    kapasitas INT NOT NULL,
    waktu_setup INT NOT NULL,
    waktu_mesin_per_eks DECIMAL(10,6) NOT NULL,
    menit_operasional INT DEFAULT 480 COMMENT 'Menit operasional per hari (8 jam = 480 menit)'
);

CREATE TABLE estimasi (
    id_estimasi INT PRIMARY KEY AUTO_INCREMENT,
    id_pesanan INT NOT NULL,
    waktu_desain DECIMAL(10,2) DEFAULT 0,
    waktu_plat DECIMAL(10,2) DEFAULT 0,
    waktu_total_setup DECIMAL(10,2) DEFAULT 0,
    waktu_mesin DECIMAL(12,2) DEFAULT 0,
    waktu_qc DECIMAL(10,2) DEFAULT 0,
    waktu_packing DECIMAL(10,2) DEFAULT 0,
    waktu_menit DECIMAL(12,2) DEFAULT 0,
    waktu_jam DECIMAL(10,2) DEFAULT 0,
    waktu_hari DECIMAL(8,2) DEFAULT 0,
    tanggal_estimasi DATE NOT NULL,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan)
);

CREATE TABLE detail_estimasi (
    id_detail_estimasi INT PRIMARY KEY AUTO_INCREMENT,
    id_estimasi INT NOT NULL,
    waktu_desain DECIMAL(10,2) DEFAULT 0,
    waktu_per_plat DECIMAL(8,2) DEFAULT 1.0,
    jumlah_halaman_per_plat TINYINT DEFAULT 8,
    jumlah_plat_per_set TINYINT DEFAULT 0,
    jumlah_plat SMALLINT DEFAULT 0,
    waktu_manual_hardcover DECIMAL(8,2) DEFAULT 1.0,
    waktu_standar_qc DECIMAL(6,3) DEFAULT 0.5,
    waktu_standar_packing DECIMAL(6,2) DEFAULT 5.0,
    jumlah_desainer TINYINT DEFAULT 1,
    waktu_mesin_per_eks DECIMAL(10,6) DEFAULT 0,
    pekerja_qc TINYINT DEFAULT 4,
    kapasitas_box TINYINT DEFAULT 40,
    jumlah_box SMALLINT DEFAULT 0,
    pekerja_packing TINYINT DEFAULT 4,
    FOREIGN KEY (id_estimasi) REFERENCES estimasi(id_estimasi)
);

CREATE TABLE jadwal_produksi (
    id_jadwal INT PRIMARY KEY AUTO_INCREMENT,
    id_estimasi INT NOT NULL,
    id_mesin INT NOT NULL,
    no_jadwal VARCHAR(50) UNIQUE NOT NULL,
    batch_ke TINYINT DEFAULT 1,
    jumlah_batch_ini INT NOT NULL,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    status ENUM('terjadwal', 'dalam proses', 'selesai', 'terlambat', 'selesai lebih cepat') DEFAULT 'terjadwal',
    FOREIGN KEY (id_estimasi) REFERENCES estimasi(id_estimasi),
    FOREIGN KEY (id_mesin) REFERENCES mesin(id_mesin)
);

CREATE TABLE detail_jadwal (
    id_detail_jadwal INT PRIMARY KEY AUTO_INCREMENT,
    id_jadwal INT NOT NULL,
    urutan_proses TINYINT NOT NULL,
    nama_proses ENUM('desain', 'plat', 'setup', 'cetak', 'laminasi', 'finishing', 'qc', 'packing') NOT NULL,
    tanggal_mulai DATETIME,
    tanggal_selesai DATETIME,
    durasi_jam DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (id_jadwal) REFERENCES jadwal_produksi(id_jadwal)
);

