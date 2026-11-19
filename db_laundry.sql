CREATE DATABASE IF NOT EXISTS db_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_laundry;

DROP TABLE IF EXISTS tb_laundry, tb_notifikasi, tb_laporan, tb_pembayaran, tb_detail_laundry, tb_pesanan, tb_layanan, tb_pengguna;

CREATE TABLE tb_pengguna (
  id_pengguna INT(11) AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_telepon VARCHAR(15),
  alamat TEXT,
  role ENUM('pelanggan','petugas','admin') NOT NULL DEFAULT 'pelanggan',
  id_laundry INT(11) NULL COMMENT 'khusus petugas: laundry tempat bertugas',
  foto_profil VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_layanan (
  id_layanan INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_laundry INT(11) NULL COMMENT 'laundry pemilik layanan (diatur petugas)',
  nama_layanan VARCHAR(100) NOT NULL,
  harga_per_kg DECIMAL(10,2) NOT NULL,
  estimasi_selesai INT(11) NOT NULL COMMENT 'jam',
  keterangan TEXT
);

CREATE TABLE tb_pesanan (
  id_pesanan INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pengguna INT(11) NOT NULL,
  id_laundry INT(11) NULL COMMENT 'laundry tujuan pesanan',
  id_petugas INT(11) NULL,
  tanggal_pesan DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil','diambil','ditolak') NOT NULL DEFAULT 'menunggu',
  alasan_penolakan VARCHAR(255) NULL,
  estimasi_selesai DATETIME NULL,
  total_harga DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_pengguna) REFERENCES tb_pengguna(id_pengguna),
  FOREIGN KEY (id_petugas) REFERENCES tb_pengguna(id_pengguna)
);

CREATE TABLE tb_notifikasi (
  id_notifikasi INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pengguna INT(11) NOT NULL,
  id_pesanan INT(11) NULL,
  judul VARCHAR(100) NOT NULL,
  pesan TEXT NOT NULL,
  status_baca ENUM('belum_dibaca','sudah_dibaca') NOT NULL DEFAULT 'belum_dibaca',
  tanggal_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pengguna) REFERENCES tb_pengguna(id_pengguna) ON DELETE CASCADE,
  FOREIGN KEY (id_pesanan) REFERENCES tb_pesanan(id_pesanan) ON DELETE CASCADE
);

CREATE TABLE tb_detail_laundry (
  id_detail INT(11) AUTO_INCREMENT PRIMARY KEY,
  id_pesanan INT(11) NOT NULL,
  id_layanan INT(11) NOT NULL,
  harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'harga layanan saat pesanan dibuat',
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
  status_bayar ENUM('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar',
  tanggal_bayar DATETIME NULL,
  bukti_bayar VARCHAR(255) NULL,
  catatan_verifikasi VARCHAR(255) NULL,
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

CREATE TABLE tb_laundry (
  id_laundry INT(11) AUTO_INCREMENT PRIMARY KEY,
  nama_laundry VARCHAR(100) NOT NULL,
  latitude DECIMAL(10,6) NOT NULL,
  longitude DECIMAL(10,6) NOT NULL,
  alamat VARCHAR(255) NULL,
  kontak VARCHAR(100) NULL,
  jenis_layanan TEXT NULL,
  jam_operasional VARCHAR(100) NULL,
  keterangan TEXT NULL,
  foto_laundry VARCHAR(255) NULL,
  wilayah VARCHAR(50) NULL,
  INDEX idx_nama (nama_laundry)
);

INSERT INTO tb_laundry (id_laundry,nama_laundry,latitude,longitude,alamat,kontak,jenis_layanan,jam_operasional,keterangan,foto_laundry,wilayah) VALUES
(1,'Nanda Laundry',-0.839507,119.883218,'JL. R.E Martadinata No.105(TONDO)','081917992204','Cuci Express,Cuci Basah,Cuci Regular,Cuci Kering,Cuci Setrika dan Setrika juga melayani deliveri(free)',NULL,NULL,NULL,'Tondo'),
(2,'Tondo Laundry',-0.838915,119.883250,'JL. R.E Martadinata (TONDO)','08114518887','Cuci Kiloan,Cuci Express,Bed Cover, Selimut, Sepatu dan lainnya,juga melayani deliveri(free)',NULL,'Promo : cuci 10 kg gratis 1 kg Khusus jumat gratis cuci alat shalat',NULL,'Tondo'),
(3,'Berkah Laundry',-0.842878,119.883582,'Jln. Untad 1','08539587008','Cuci Komplit,Cuci Lipat,Sprey,Gorden,Boneka,Selimut dan Bedcover',NULL,'Menerima Laundry Express : -Express 12 jam dan 6 jam',NULL,'Tondo'),
(4,'Meltry Laundry',-0.839596,119.883219,'Jl. Trans Sulawesi, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94148','082126947884',NULL,'08.00 am-9.00 pm','Bebas pilih parfum',NULL,'Tondo'),
(5,'Home Al-AzharLaundry',-0.843091,119.885485,'Jl. UNTAD 1, Depan Kantor Kelurahan Tondo-Mantikulore,Kota Palu-Sulteng','0811460920','Pakaian,Sepatu,Karpet,Boneka,Helm,Tas',NULL,NULL,NULL,'Tondo'),
(6,'Laundry Gen-Z',-0.845742,119.890451,'Jl. Roviga No.16, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','082315661778',NULL,NULL,'Gratis antar jemput',NULL,'Tondo'),
(7,'Moba Laundry',-0.843341,119.888343,'Jl. Untad I Bawah, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119','085211601158',NULL,'08.00 am - 21.00 pm','Gratis antar jemput',NULL,'Tondo'),
(8,'Rahma Laundry',-0.839082,119.900604,'Jl. Sosiologi (Belakang Kampus Untad)','082213118211 / 081299566201','Satuan dan Kiloan,Cuci Baju & Celana,Cuci Karpet & Bedcover,Cuci Sepatu,Helm &Tas Cuci Boneka,Gorden dll,Cuci Selimut & Seprei',NULL,NULL,NULL,'Tondo'),
(9,'Mega Laundry',-0.843363,119.902960,'Jl. Untad I Bumi Roviega, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','082225206397','Cuci Kiloan,Cuci Experss,Cuci Satuan,Cuci Basah,Cuci Kering,Cuci Setrika,Cuci Jas,Seprei,Cuci Sepatu,Cuci Boneka,Gorden/tirai,Karpet/tikar,Bed Cover/selimuta,Helm dll','07.00-10.30 am',NULL,NULL,'Tondo'),
(10,'Joss Laundry',-0.843493,119.902656,'Jl. Untad I Bumi Roviega, Tondo, Kec. Mantikulore',NULL,'Satuan,Sepatu,Tas,Helm,Karpet dan Perlengkapan Bayi',NULL,NULL,NULL,'Tondo'),
(11,'Rania Laundry',-0.843427,119.903191,'Jln. Untad 1 depan Pustu Roviga','082261363019','Cuci Kering,Cuci Lipat,Komplit,Setrika,Karpet Express,Sepatu Express,Boneka,Selimut,Bedcover,Helm dll',NULL,'Melayani antar jemput free ongkir',NULL,'Tondo'),
(12,'Raffasya Laundry Express',-0.843667,119.904529,'Jl. Untad I Bumi Roviega, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119','085230627772','Helm,Sepatu,Boneka,Karpet','07.30-22.00 WITA','Gratis antar jemput',NULL,'Tondo'),
(13,'Roehma Laundry',-0.843704,119.905032,'Jl. Untad I BTN Bumi Roviega Blok A2/8','082271581815','Cuci Setrika,Cuci Lipat,Setrika','07.00-22.00 WITA',NULL,NULL,'Tondo'),
(14,'Kesya Laundry',-0.843651,119.906221,'Jl. Tondo Untad 1','082260259556','Melayani Kiloan dan Satuan,Laundry Basah,Layanan Setrika,Layanan Express Dry Cleaning',NULL,NULL,NULL,'Tondo'),
(15,'Laundry Satuan dan kiloan',-0.843639,119.907884,NULL,'085137714097','Satuan dan Kiloan,Pakaian,Selimut,Bedcover,Boneka,Sprei DLL',NULL,NULL,NULL,'Tondo'),
(16,'Enas Laundry',-0.843536,119.896539,'Jl. Untad 1 Bumi Roviega Depan RS.UNTAD','082292528360','Cucian Pakaian,Cuci Jas,Cuci Seprei,Cuci Selimut,CuciBed Cover,Cuci Tas,Cuci Boneka,Cuci Sepattu,Cuci Gorden,Cuci Karpet,Cuci Helm,DLL,serta Gratis antar jemput',NULL,'Menerima Laundry Express',NULL,'Tondo'),
(17,'Madam Laundry',-0.845606,119.894882,'Perdos','082290535306','Satuan,Tas,Sepatu,Boneka & Helm','Senin-sabtu 08.00-18.00',NULL,NULL,'Tondo'),
(18,'Laundry-Q',-0.843282,119.887974,'Jl. Untad 1,Tondo','082192414335','Kiloan dan Satuan,Cuci Karpet,Bedcover,Helm,Boneka/Bantal,Cuci Stroller Box bayi dan Sepatu',NULL,'Free Delivery',NULL,'Tondo'),
(19,'Nura Laundry',-0.847422,119.891923,'Jl. Soekarno Hatta','085399499646 / 082191908381','Kiloan dan Satuan,Helm,Boneka,Tas dan Sepatu',NULL,NULL,NULL,'Tondo'),
(20,'Safira Laundry',-0.846915,119.892968,'Perumahan Dosen, Blok C2, No.1, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119',NULL,'Cuci Kering,Cuci Lipat,Cuci Setrika,Setrika','08.00-22.00 WITA','Gratis antar jemput',NULL,'Tondo'),
(21,'Siti Laundry',-0.848579,119.889706,'Jl. Roviga, Tondo','082194706873','Satuan,Kiloan,Karpet,Boneka,Bed Cover,Seprey,Gorden dll',NULL,'Menerima antar jemput minimal 5 kg free ongkir',NULL,'Tondo'),
(22,'Widhya Laundry',-0.848585,119.887719,'Jl. Roviga, Tondo, Kec. Mantikulore','081340341518','Cuci Komplit,Cuci Setrika,Cuci Lipat,Cuci Express,Cuci Bedcover Selimut,Cuci Sepatu,Boneka ,Karpet,Cuci Kasur Lantai dll',NULL,NULL,NULL,'Tondo'),
(23,'Ranah Laundry',-0.848785,119.888667,'Jl. Roviga Depan Kampus STAH',NULL,'Cuci Kiloan,Cuci Satuan,Setrika,Bedcover & Selimut','07:30-22:00 WITA',NULL,NULL,'Tondo'),
(24,'Omang Laundry',-0.848635,119.886125,'Jl. Roviga','082238662867','Satuan dan Kiloan,Cuci Lipat,Cuci Setrika,Bedcover Dll',NULL,'Menerima antar jemput minimal 5 kg',NULL,'Tondo'),
(25,'Alula Laundry',-0.848591,119.886947,'Jln. Roviga-Tondo','082190905603','Satuan dan Kiloan,Cuci Setrika,Karpet,Boneka,Bedcover,Sprey,Gorden dll',NULL,'Gratis antar jemput cucian',NULL,'Tondo'),
(26,'Laundry Bunda Hasna',-0.845100,119.900982,'Jl. Sosiologi, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','085656117434','Cuci Saja,Cuci Basah & Cuci Lipat',NULL,NULL,NULL,'Tondo'),
(27,'Wangi Mama Laundry',-0.871452,119.877600,'Jl. Hang Tuah, Talise, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94118','08114564470',NULL,NULL,NULL,NULL,'Talise'),
(28,'Razka Laundry',-0.882870,119.878009,'Jl. Tombolotutu No.85, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','085199084687','Satuan,Cuci Sepatu,Cuci Helm,Cuci Karpet,dll','07.00-22.00 WITA','Melayani antar jemput',NULL,'Talise'),
(29,'Pondok Laundry',-0.884199,119.879846,'Jl. Tombolotutu No.119, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','082116666171','Satuan,Express,Cuci Sepatu,Cuci Tas/Dompet,boneka dan Helm','07.00-22.00 WITA',NULL,NULL,'Talise'),
(30,'Alfatih Laundry',-0.877376,119.876702,'Jl. Hang Tuah No.49, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','081340888899',NULL,'08.00-21.00 WITA',NULL,NULL,'Talise'),
(31,'Syahrul Laundry',-0.876131,119.876991,'Jl. Hang Tuah, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','082188899057',NULL,NULL,NULL,NULL,'Talise');

UPDATE tb_laundry SET foto_laundry='laundry_01.jpg' WHERE id_laundry=1;
UPDATE tb_laundry SET foto_laundry='laundry_02.jpg' WHERE id_laundry=2;
UPDATE tb_laundry SET foto_laundry='laundry_03.jpg' WHERE id_laundry=3;
UPDATE tb_laundry SET foto_laundry='laundry_04.jpg' WHERE id_laundry=4;
UPDATE tb_laundry SET foto_laundry='laundry_05.jpg' WHERE id_laundry=5;
UPDATE tb_laundry SET foto_laundry='laundry_06.jpg' WHERE id_laundry=6;
UPDATE tb_laundry SET foto_laundry='laundry_07.jpg' WHERE id_laundry=7;
UPDATE tb_laundry SET foto_laundry='laundry_08.jpg' WHERE id_laundry=8;
UPDATE tb_laundry SET foto_laundry='laundry_09.jpg' WHERE id_laundry=9;
UPDATE tb_laundry SET foto_laundry='laundry_10.jpg' WHERE id_laundry=10;
UPDATE tb_laundry SET foto_laundry='laundry_11.jpg' WHERE id_laundry=11;
UPDATE tb_laundry SET foto_laundry='laundry_12.jpg' WHERE id_laundry=12;
UPDATE tb_laundry SET foto_laundry='laundry_13.jpg' WHERE id_laundry=13;
UPDATE tb_laundry SET foto_laundry='laundry_14.jpg' WHERE id_laundry=14;
UPDATE tb_laundry SET foto_laundry='laundry_15.jpg' WHERE id_laundry=15;
UPDATE tb_laundry SET foto_laundry='laundry_16.jpg' WHERE id_laundry=16;
UPDATE tb_laundry SET foto_laundry='laundry_17.jpg' WHERE id_laundry=17;
UPDATE tb_laundry SET foto_laundry='laundry_18.jpg' WHERE id_laundry=18;
UPDATE tb_laundry SET foto_laundry='laundry_19.jpg' WHERE id_laundry=19;
UPDATE tb_laundry SET foto_laundry='laundry_20.jpg' WHERE id_laundry=20;
UPDATE tb_laundry SET foto_laundry='laundry_21.jpg' WHERE id_laundry=21;
UPDATE tb_laundry SET foto_laundry='laundry_22.jpg' WHERE id_laundry=22;
UPDATE tb_laundry SET foto_laundry='laundry_23.jpg' WHERE id_laundry=23;
UPDATE tb_laundry SET foto_laundry='laundry_24.jpg' WHERE id_laundry=24;
UPDATE tb_laundry SET foto_laundry='laundry_25.jpg' WHERE id_laundry=25;
UPDATE tb_laundry SET foto_laundry='laundry_26.jpg' WHERE id_laundry=26;
UPDATE tb_laundry SET foto_laundry='laundry_27.jpg' WHERE id_laundry=27;
UPDATE tb_laundry SET foto_laundry='laundry_28.jpg' WHERE id_laundry=28;
UPDATE tb_laundry SET foto_laundry='laundry_29.jpg' WHERE id_laundry=29;
UPDATE tb_laundry SET foto_laundry='laundry_30.jpg' WHERE id_laundry=30;
UPDATE tb_laundry SET foto_laundry='laundry_31.jpg' WHERE id_laundry=31;

UPDATE tb_pengguna SET id_laundry = 16 WHERE email = 'petugas@laundry.test';
INSERT INTO tb_pengguna (nama,email,password,no_telepon,alamat,role,id_laundry) VALUES
('Petugas Nanda Laundry','petugas.nanda@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081917992204','JL. R.E Martadinata No.105(TONDO)','petugas',1),
('Petugas Tondo Laundry','petugas.tondo@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','08114518887','JL. R.E Martadinata (TONDO)','petugas',2),
('Petugas Berkah Laundry','petugas.berkah@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','08539587008','Jln. Untad 1','petugas',3),
('Petugas Meltry Laundry','petugas.meltry@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082126947884','Jl. Trans Sulawesi, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94148','petugas',4),
('Petugas Home Al-AzharLaundry','petugas.homealazhar@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0811460920','Jl. UNTAD 1, Depan Kantor Kelurahan Tondo-Mantikulore,Kota Palu-Sulteng','petugas',5),
('Petugas Laundry Gen-Z','petugas.genz@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082315661778','Jl. Roviga No.16, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','petugas',6),
('Petugas Moba Laundry','petugas.moba@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085211601158','Jl. Untad I Bawah, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119','petugas',7),
('Petugas Rahma Laundry','petugas.rahma@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082213118211','Jl. Sosiologi (Belakang Kampus Untad)','petugas',8),
('Petugas Mega Laundry','petugas.mega@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082225206397','Jl. Untad I Bumi Roviega, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','petugas',9),
('Petugas Joss Laundry','petugas.joss@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'Jl. Untad I Bumi Roviega, Tondo, Kec. Mantikulore','petugas',10),
('Petugas Rania Laundry','petugas.rania@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082261363019','Jln. Untad 1 depan Pustu Roviga','petugas',11),
('Petugas Raffasya Laundry Express','petugas.raffasyaexpress@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085230627772','Jl. Untad I Bumi Roviega, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119','petugas',12),
('Petugas Roehma Laundry','petugas.roehma@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082271581815','Jl. Untad I BTN Bumi Roviega Blok A2/8','petugas',13),
('Petugas Kesya Laundry','petugas.kesya@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082260259556','Jl. Tondo Untad 1','petugas',14),
('Petugas Laundry Satuan dan kiloan','petugas.satuandankiloan@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085137714097',NULL,'petugas',15),
('Petugas Madam Laundry','petugas.madam@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082290535306','Perdos','petugas',17),
('Petugas Laundry-Q','petugas.q@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082192414335','Jl. Untad 1,Tondo','petugas',18),
('Petugas Nura Laundry','petugas.nura@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085399499646','Jl. Soekarno Hatta','petugas',19),
('Petugas Safira Laundry','petugas.safira@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'Perumahan Dosen, Blok C2, No.1, Tondo, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94119','petugas',20),
('Petugas Siti Laundry','petugas.siti@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082194706873','Jl. Roviga, Tondo','petugas',21),
('Petugas Widhya Laundry','petugas.widhya@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081340341518','Jl. Roviga, Tondo, Kec. Mantikulore','petugas',22),
('Petugas Ranah Laundry','petugas.ranah@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,'Jl. Roviga Depan Kampus STAH','petugas',23),
('Petugas Omang Laundry','petugas.omang@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082238662867','Jl. Roviga','petugas',24),
('Petugas Alula Laundry','petugas.alula@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082190905603','Jln. Roviga-Tondo','petugas',25),
('Petugas Laundry Bunda Hasna','petugas.bundahasna@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085656117434','Jl. Sosiologi, Tondo, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94111','petugas',26),
('Petugas Wangi Mama Laundry','petugas.wangimama@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','08114564470','Jl. Hang Tuah, Talise, Kec. Mantikulore, Kota Palu, Sulawesi Tengah 94118','petugas',27),
('Petugas Razka Laundry','petugas.razka@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','085199084687','Jl. Tombolotutu No.85, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','petugas',28),
('Petugas Pondok Laundry','petugas.pondok@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082116666171','Jl. Tombolotutu No.119, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','petugas',29),
('Petugas Alfatih Laundry','petugas.alfatih@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','081340888899','Jl. Hang Tuah No.49, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','petugas',30),
('Petugas Syahrul Laundry','petugas.syahrul@laundry.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','082188899057','Jl. Hang Tuah, Talise, Kec. Palu Tim., Kota Palu, Sulawesi Tengah 94118','petugas',31)
ON DUPLICATE KEY UPDATE role = 'petugas', id_laundry = VALUES(id_laundry);

UPDATE tb_pengguna SET id_laundry = 16 WHERE email = 'petugas@laundry.test';

CREATE TEMPORARY TABLE tmp_layanan (
  id_laundry INT NOT NULL,
  nama_layanan VARCHAR(100) NOT NULL,
  harga_per_kg DECIMAL(10,2) NOT NULL,
  estimasi_selesai INT NOT NULL,
  keterangan TEXT
);

INSERT INTO tmp_layanan (id_laundry,nama_layanan,harga_per_kg,estimasi_selesai,keterangan) VALUES
(1,'Cuci Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(1,'Cuci Basah',6000,48,'Harga estimasi, dapat diubah petugas'),
(1,'Cuci Regular',6000,48,'Harga estimasi, dapat diubah petugas'),
(1,'Cuci Kering',6000,48,'Harga estimasi, dapat diubah petugas'),
(1,'Cuci Setrika dan Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(2,'Cuci Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(2,'Cuci Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(2,'Bed Cover',6000,48,'Harga estimasi, dapat diubah petugas'),
(2,'Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(2,'Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Cuci Komplit',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Sprey',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Gorden',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(3,'Selimut dan Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Pakaian',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(5,'Tas',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Satuan dan Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Cuci Baju & Celana',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Cuci Karpet & Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Cuci Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Helm &Tas Cuci Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Gorden',6000,48,'Harga estimasi, dapat diubah petugas'),
(8,'Cuci Selimut & Seprei',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Experss',10000,12,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Basah',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Kering',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Jas',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Seprei',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Cuci Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Gorden/tirai',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Karpet/tikar',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Bed Cover/selimuta',6000,48,'Harga estimasi, dapat diubah petugas'),
(9,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(10,'Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(10,'Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(10,'Tas',6000,48,'Harga estimasi, dapat diubah petugas'),
(10,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(10,'Karpet dan Perlengkapan Bayi',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Cuci Kering',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Komplit',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(11,'Karpet Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(11,'Sepatu Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(11,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(11,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(12,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(12,'Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(12,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(12,'Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(13,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(13,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(13,'Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(14,'Melayani Kiloan dan Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(14,'Laundry Basah',6000,48,'Harga estimasi, dapat diubah petugas'),
(14,'Layanan Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(14,'Layanan Express Dry Cleaning',10000,12,'Harga estimasi, dapat diubah petugas'),
(15,'Satuan dan Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(15,'Pakaian',6000,48,'Harga estimasi, dapat diubah petugas'),
(15,'Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(15,'Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(15,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(15,'Sprei',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cucian Pakaian',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Jas',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Seprei',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'CuciBed Cover',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Tas',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Sepattu',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Gorden',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(16,'Cuci Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(17,'Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(17,'Tas',6000,48,'Harga estimasi, dapat diubah petugas'),
(17,'Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(17,'Boneka & Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Kiloan dan Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Cuci Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Boneka/Bantal',6000,48,'Harga estimasi, dapat diubah petugas'),
(18,'Cuci Stroller Box bayi dan Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(19,'Kiloan dan Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(19,'Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(19,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(19,'Tas dan Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(20,'Cuci Kering',6000,48,'Harga estimasi, dapat diubah petugas'),
(20,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(20,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(20,'Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(21,'Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Bed Cover',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Seprey',6000,48,'Harga estimasi, dapat diubah petugas'),
(21,'Gorden',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Komplit',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Bedcover Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(22,'Cuci Kasur Lantai',6000,48,'Harga estimasi, dapat diubah petugas'),
(23,'Cuci Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(23,'Cuci Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(23,'Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(23,'Bedcover & Selimut',6000,48,'Harga estimasi, dapat diubah petugas'),
(24,'Satuan dan Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(24,'Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(24,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(24,'Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Satuan dan Kiloan',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Cuci Setrika',8000,72,'Harga estimasi, dapat diubah petugas'),
(25,'Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Boneka',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Bedcover',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Sprey',6000,48,'Harga estimasi, dapat diubah petugas'),
(25,'Gorden',6000,48,'Harga estimasi, dapat diubah petugas'),
(26,'Cuci Saja',6000,48,'Harga estimasi, dapat diubah petugas'),
(26,'Cuci Basah & Cuci Lipat',6000,48,'Harga estimasi, dapat diubah petugas'),
(28,'Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(28,'Cuci Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(28,'Cuci Helm',6000,48,'Harga estimasi, dapat diubah petugas'),
(28,'Cuci Karpet',6000,48,'Harga estimasi, dapat diubah petugas'),
(29,'Satuan',6000,48,'Harga estimasi, dapat diubah petugas'),
(29,'Express',10000,12,'Harga estimasi, dapat diubah petugas'),
(29,'Cuci Sepatu',6000,48,'Harga estimasi, dapat diubah petugas'),
(29,'Cuci Tas/Dompet',6000,48,'Harga estimasi, dapat diubah petugas'),
(29,'boneka dan Helm',6000,48,'Harga estimasi, dapat diubah petugas');

DELETE FROM tb_layanan
WHERE id_laundry = 16
  AND nama_layanan IN ('Cuci Reguler','Cuci Kilat','Cuci Setrika')
  AND id_layanan NOT IN (SELECT id_layanan FROM tb_detail_laundry);

INSERT INTO tb_layanan (id_laundry,nama_layanan,harga_per_kg,estimasi_selesai,keterangan)
SELECT t.id_laundry,t.nama_layanan,t.harga_per_kg,t.estimasi_selesai,t.keterangan
FROM tmp_layanan t
WHERE NOT EXISTS (
  SELECT 1 FROM tb_layanan l WHERE l.id_laundry = t.id_laundry AND l.nama_layanan = t.nama_layanan
);

DROP TEMPORARY TABLE tmp_layanan;
