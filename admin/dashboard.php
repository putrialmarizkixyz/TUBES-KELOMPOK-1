<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="top">
  <strong>Laundry - Dashboard Admin</strong>
  <span>Halo, <?= e($_SESSION['nama']) ?> | <a href="<?= BASE_URL ?>/auth/logout.php">Keluar</a></span>
</header>
<main>
  <h2>Menu</h2>
  <ul class="menu">
    <li>Kelola Pengguna</li><li>Kelola Layanan &amp; Harga</li><li>Monitoring Transaksi</li><li>Laporan</li>
  </ul>
  <p class="sub">(Tautan menu akan aktif seiring halaman dibuat.)</p>
</main>
</body>
</html>
