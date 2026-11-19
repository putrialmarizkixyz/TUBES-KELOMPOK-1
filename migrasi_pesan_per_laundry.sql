USE db_laundry;

ALTER TABLE tb_pesanan
  ADD COLUMN id_laundry INT(11) NULL AFTER id_pengguna,
  ADD INDEX idx_pesanan_laundry (id_laundry);

ALTER TABLE tb_pengguna
  ADD COLUMN id_laundry INT(11) NULL AFTER role;

UPDATE tb_pengguna SET id_laundry = 16 WHERE email = 'petugas@laundry.test';

UPDATE tb_pesanan SET id_laundry = 16 WHERE id_laundry IS NULL;
