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

INSERT INTO tb_layanan (nama_layanan,harga_per_kg,estimasi_selesai,keterangan) VALUES
('Cuci Reguler',6000,48,'Cuci + kering'),
('Cuci Kilat',10000,12,'Cuci + kering'),
('Cuci Setrika',8000,72,'Cuci + kering + setrika');

-- Data laundry untuk fitur Cari & Peta (sumber: Template_Data_31_Laundry.xlsx)
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

-- Foto laundry (file di uploads/laundry/)
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

-- Petugas demo bertugas di Enas Laundry (id 16); ubah lewat Admin > Pengguna
UPDATE tb_pengguna SET id_laundry = 16 WHERE email = 'petugas@laundry.test';
