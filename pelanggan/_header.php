<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Pelanggan') ?> - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="app-page">

<header class="app-header">
    <a class="app-brand" href="<?= BASE_URL ?>/pelanggan/dashboard.php">
        <span class="brand-symbol">🧺</span>
        <span>
            <strong>LaundryKu</strong>
            <small>Layanan Laundry</small>
        </span>
    </a>

    <nav class="app-nav">
        <a href="<?= BASE_URL ?>/pelanggan/dashboard.php">Beranda</a>
        <a href="<?= BASE_URL ?>/pelanggan/layanan.php">Layanan &amp; Harga</a>
        <a href="<?= BASE_URL ?>/pelanggan/pesanan_baru.php">Buat Pesanan</a>
        <a href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php">Pesanan Saya</a>
    </nav>

    <div class="app-user">
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">

