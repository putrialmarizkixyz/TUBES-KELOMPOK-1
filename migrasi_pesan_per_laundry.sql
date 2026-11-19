-- Jalankan SEKALI pada database db_laundry yang SUDAH ada (data tidak hilang).
-- Pesanan kini ditujukan ke satu laundry (tb_laundry), dan petugas ditautkan ke laundry tempatnya bertugas.
USE db_laundry;

ALTER TABLE tb_pesanan
  ADD COLUMN id_laundry INT(11) NULL AFTER id_pengguna,
  ADD INDEX idx_pesanan_laundry (id_laundry);

ALTER TABLE tb_pengguna
  ADD COLUMN id_laundry INT(11) NULL AFTER role;

-- Petugas demo bertugas di Enas Laundry (id 16). Ubah lewat Admin > Pengguna.
UPDATE tb_pengguna SET id_laundry = 16 WHERE email = 'petugas@laundry.test';

-- Pesanan lama (sebelum fitur ini) dialihkan ke Enas Laundry agar tetap terlihat petugas demo.
UPDATE tb_pesanan SET id_laundry = 16 WHERE id_laundry IS NULL;
