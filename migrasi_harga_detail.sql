-- Jalankan SEKALI pada database db_laundry yang SUDAH ada (tanpa import ulang, data tidak hilang).
-- Menambah kolom harga_per_kg di tb_detail_laundry (harga layanan saat pesanan dibuat),
-- lalu memperbaiki subtotal dan total pesanan yang sudah terlanjur Rp 0.
USE db_laundry;

ALTER TABLE tb_detail_laundry
  ADD COLUMN harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER id_layanan;

-- isi harga lama dari harga layanan saat ini
UPDATE tb_detail_laundry d
JOIN tb_layanan l ON l.id_layanan = d.id_layanan
SET d.harga_per_kg = l.harga_per_kg
WHERE d.harga_per_kg = 0;

-- hitung ulang subtotal: berat aktual jika sudah ditimbang, selain itu berat perkiraan
UPDATE tb_detail_laundry
SET subtotal = ROUND(COALESCE(berat_aktual, berat_perkiraan) * harga_per_kg, 2);

-- hitung ulang total tiap pesanan
UPDATE tb_pesanan p
SET p.total_harga = (SELECT COALESCE(SUM(d.subtotal), 0) FROM tb_detail_laundry d WHERE d.id_pesanan = p.id_pesanan)
WHERE EXISTS (SELECT 1 FROM tb_detail_laundry d WHERE d.id_pesanan = p.id_pesanan);
