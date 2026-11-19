CARA PASANG (Laragon)
1. Salin isi folder ini ke C:\laragon\www\TUBES-RPL
2. Laragon > Start All, lalu Database (HeidiSQL) > File > Run SQL file > db_laundry.sql
3. Buka http://localhost/TUBES-RPL
Akun uji (password: password):
  admin@laundry.test / petugas@laundry.test / pelanggan@laundry.test
Halaman baru: di baris awal tulis
  require_once __DIR__ . '/../config/auth.php'; wajib_login(['petugas']);
Fitur Cari & Peta Laundry (menu pelanggan > Cari Laundry):
  - Database baru: import ulang db_laundry.sql, ATAU jalankan migrasi_peta_laundry.sql saja (tabel tb_laundry, 31 data)
  - Butuh internet (peta Leaflet + tile OpenStreetMap dimuat dari CDN)
  - Foto laundry: isi kolom foto_laundry dengan nama file (simpan di uploads/laundry/) atau tautan https

Akun petugas tiap laundry (30 akun + petugas@laundry.test untuk Enas Laundry):
  lihat akun_petugas_laundry.txt (password semua akun: password)
  Database lama: jalankan migrasi_akun_layanan_laundry.sql sekali setelah migrasi lainnya.
