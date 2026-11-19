USE db_laundry;
ALTER TABLE tb_pesanan
  MODIFY status ENUM('menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil','ditolak') NOT NULL DEFAULT 'menunggu',
  ADD COLUMN alasan_penolakan VARCHAR(255) NULL AFTER status;
