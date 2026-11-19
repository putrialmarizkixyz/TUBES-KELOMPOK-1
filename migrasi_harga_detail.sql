USE db_laundry;

ALTER TABLE tb_detail_laundry
  ADD COLUMN harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER id_layanan;

UPDATE tb_detail_laundry d
JOIN tb_layanan l ON l.id_layanan = d.id_layanan
SET d.harga_per_kg = l.harga_per_kg
WHERE d.harga_per_kg = 0;

UPDATE tb_detail_laundry
SET subtotal = ROUND(COALESCE(berat_aktual, berat_perkiraan) * harga_per_kg, 2);

UPDATE tb_pesanan p
SET p.total_harga = (SELECT COALESCE(SUM(d.subtotal), 0) FROM tb_detail_laundry d WHERE d.id_pesanan = p.id_pesanan)
WHERE EXISTS (SELECT 1 FROM tb_detail_laundry d WHERE d.id_pesanan = p.id_pesanan);
