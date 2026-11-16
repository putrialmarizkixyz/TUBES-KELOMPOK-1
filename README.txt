CARA PASANG (Laragon)
1. Salin isi folder ini ke C:\laragon\www\TUBES-RPL
2. Laragon > Start All, lalu Database (HeidiSQL) > File > Run SQL file > db_laundry.sql
3. Buka http://localhost/TUBES-RPL
Akun uji (password: password):
  admin@laundry.test / petugas@laundry.test / pelanggan@laundry.test
Halaman baru: di baris awal tulis
  require_once __DIR__ . '/../config/auth.php'; wajib_login(['petugas']);
