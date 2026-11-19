USE db_laundry;

ALTER TABLE tb_layanan
  ADD COLUMN id_laundry INT(11) NULL AFTER id_layanan,
  ADD INDEX idx_layanan_laundry (id_laundry);

UPDATE tb_layanan SET id_laundry = 16 WHERE id_laundry IS NULL;

ALTER TABLE tb_pengguna
  ADD COLUMN foto_profil VARCHAR(255) NULL;
