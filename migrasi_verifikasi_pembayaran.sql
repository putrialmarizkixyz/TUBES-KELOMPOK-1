USE db_laundry;

ALTER TABLE tb_pembayaran
  MODIFY status_bayar ENUM('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar',
  ADD COLUMN catatan_verifikasi VARCHAR(255) NULL AFTER bukti_bayar;
