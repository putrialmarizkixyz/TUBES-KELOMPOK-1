<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Pelanggan') ?> - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="top">
  <strong>Laundry</strong>
  <nav>
    <a href="<?= BASE_URL ?>/pelanggan/dashboard.php">Beranda</a>
    <a href="<?= BASE_URL ?>/pelanggan/layanan.php">Layanan &amp; Harga</a>
    <a href="<?= BASE_URL ?>/pelanggan/pesanan_baru.php">Buat Pesanan</a>
    <a href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php">Pesanan Saya</a>
  </nav>
  <span>Halo, <?= e($_SESSION['nama']) ?> | <a href="<?= BASE_URL ?>/auth/logout.php">Keluar</a></span>
</header>
<main>
