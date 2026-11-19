USE db_laundry;

ALTER TABLE tb_pesanan
  MODIFY status ENUM('menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil','diambil','ditolak') NOT NULL DEFAULT 'menunggu';

CREATE TABLE IF NOT EXISTS tb_notifikasi (
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
