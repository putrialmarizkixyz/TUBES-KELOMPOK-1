<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Petugas') ?> - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="top">
  <strong>Laundry - Petugas</strong>
  <nav>
    <a href="<?= BASE_URL ?>/petugas/dashboard.php">Beranda</a>
    <a href="<?= BASE_URL ?>/petugas/pesanan_masuk.php">Pesanan Masuk</a>
    <a href="<?= BASE_URL ?>/petugas/pesanan_aktif.php">Pesanan Saya</a>
  </nav>
  <span>Halo, <?= e($_SESSION['nama']) ?> | <a href="<?= BASE_URL ?>/auth/logout.php">Keluar</a></span>
</header>
<main>
