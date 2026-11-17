<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Admin') ?> - LaundryKu</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="app-page">

<header class="app-header">
    <a class="app-brand" href="<?= BASE_URL ?>/admin/dashboard.php">
        <span class="brand-symbol">🧺</span>
        <span>
            <strong>LaundryKu</strong>
            <small>Admin</small>
        </span>
    </a>

    <nav class="app-nav">
        <a href="<?= BASE_URL ?>/admin/dashboard.php"
        class="<?= $halaman_aktif === 'dashboard.php' ? 'active' : '' ?>">
            Beranda
        </a>

        <a href="<?= BASE_URL ?>/admin/layanan.php"
        class="<?= $halaman_aktif === 'layanan.php' ? 'active' : '' ?>">
            Layanan
        </a>

        <a href="<?= BASE_URL ?>/admin/pengguna.php"
        class="<?= $halaman_aktif === 'pengguna.php' ? 'active' : '' ?>">
            Pengguna
        </a>

        <a href="<?= BASE_URL ?>/admin/transaksi.php"
        class="<?= $halaman_aktif === 'transaksi.php' ? 'active' : '' ?>">
            Transaksi
        </a>

        <a href="<?= BASE_URL ?>/admin/laporan.php"
        class="<?= $halaman_aktif === 'laporan.php' ? 'active' : '' ?>">
            Laporan
        </a>
    </nav>

    <div class="app-user">
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">
