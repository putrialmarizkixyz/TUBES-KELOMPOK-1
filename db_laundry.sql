CREATE DATABASE IF NOT EXISTS db_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_laundry;

DROP TABLE IF EXISTS tb_laporan, tb_pembayaran, tb_detail_laundry, tb_pesanan, tb_layanan, tb_pengguna;

CREATE TABLE tb_pengguna (
  id_pengguna INT(11) AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_telepon VARCHAR(15),
  alamat TEXT,
  role ENUM('pelanggan','petugas','admin') NOT NULL DEFAULT 'pelanggan',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_layanan (
  id_layanan INT(11) AUTO_INCREMENT PRIMARY KEY,
  nama_layanan VARCHAR(100) NOT NULL,
  harga_per_kg DECIMAL(10,2) NOT NULL,
  estimasi_selesai INT(11) NOT NULL COMMENT 'jam',
  keterangan TEXT
);

CREATE TABLE tb_pesanan (
  id_pesanan INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pengguna INT(11) NOT NULL,
  id_petugas INT(11) NULL,
  tanggal_pesan DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil') NOT NULL DEFAULT 'menunggu',
  estimasi_selesai DATETIME NULL,
  total_harga DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_pengguna) REFERENCES tb_pengguna(id_pengguna),
  FOREIGN KEY (id_petugas) REFERENCES tb_pengguna(id_pengguna)
);

CREATE TABLE tb_detail_laundry (
  id_detail INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pesanan INT(11) NOT NULL,
  id_layanan INT(11) NOT NULL,
  berat_perkiraan DECIMAL(6,2) NOT NULL,
  berat_aktual DECIMAL(6,2) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  catatan TEXT,
  FOREIGN KEY (id_pesanan) REFERENCES tb_pesanan(id_pesanan) ON DELETE CASCADE,
  FOREIGN KEY (id_layanan) REFERENCES tb_layanan(id_layanan)
);

CREATE TABLE tb_pembayaran (
  id_pembayaran INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pesanan INT(11) NOT NULL,
  metode_bayar VARCHAR(50) NOT NULL,
  jumlah_bayar DECIMAL(10,2) NOT NULL,
  status_bayar ENUM('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar',
  tanggal_bayar DATETIME NULL,
  bukti_bayar VARCHAR(255) NULL,
  FOREIGN KEY (id_pesanan) REFERENCES tb_pesanan(id_pesanan) ON DELETE CASCADE
);

CREATE TABLE tb_laporan (
  id_laporan INT(11) AUTO_INCREMENT PRIMARY KEY,
  periode VARCHAR(20) NOT NULL,
  total_transaksi INT(11) NOT NULL,
  total_pendapatan DECIMAL(12,2) NOT NULL,
  dibuat_oleh INT(11) NOT NULL,
  tanggal_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (dibuat_oleh) REFERENCES tb_pengguna(id_pengguna)
);

INSERT INTO tb_pengguna (nama,email,password,no_telepon,alamat,role) VALUES
('Admin Laundry','admin@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081200000001','Palu','admin'),
('Petugas Satu','petugas@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081200000002','Palu','petugas'),
('Pelanggan Satu','pelanggan@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081200000003','Palu','pelanggan');

INSERT INTO tb_layanan (nama_layanan,harga_per_kg,estimasi_selesai,keterangan) VALUES
('Cuci Reguler',6000,48,'Cuci + kering, selesai 2 hari'),
('Cuci Kilat',10000,12,'Cuci + kering, selesai 12 jam'),
('Cuci Setrika',8000,48,'Cuci + kering + setrika');
