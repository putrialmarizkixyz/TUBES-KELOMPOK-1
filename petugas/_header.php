<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Petugas') ?> - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="app-page">

<header class="app-header">
    <a class="app-brand" href="<?= BASE_URL ?>/petugas/dashboard.php">
        <span class="brand-symbol">🧺</span>
        <span>
            <strong>LaundryKu</strong>
            <small>Petugas</small>
        </span>
    </a>

    <nav class="app-nav">
        <a href="<?= BASE_URL ?>/petugas/dashboard.php"
        class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            Beranda
        </a>

        <a href="<?= BASE_URL ?>/petugas/pesanan_masuk.php"
        class="<?= $halaman_aktif === 'pesanan_masuk.php' ? 'active' : '' ?>">
            Pesanan Masuk
        </a>

        <a href="<?= BASE_URL ?>/petugas/pesanan_aktif.php"
        class="<?= in_array($halaman_aktif, ['pesanan_aktif.php', 'pesanan_kelola.php']) ? 'active' : '' ?>">
            Pesanan Aktif
        </a>

        <a href="<?= BASE_URL ?>/petugas/layanan.php"
        class="<?= $halaman_aktif === 'layanan.php' ? 'active' : '' ?>">
            Layanan
        </a>

        <a href="<?= BASE_URL ?>/petugas/riwayat_pesanan.php"
        class="<?= $halaman_aktif === 'riwayat_pesanan.php' ? 'active' : '' ?>">
            Riwayat Pesanan
        </a>

        <a href="<?= BASE_URL ?>/petugas/pembayaran.php"
        class="<?= in_array($halaman_aktif, ['pembayaran.php', 'pembayaran_detail.php']) ? 'active' : '' ?>">
            Pembayaran
        </a>

        <a href="<?= BASE_URL ?>/petugas/laporan_transaksi.php"
        class="<?= $halaman_aktif === 'laporan_transaksi.php' ? 'active' : '' ?>">
            Laporan Transaksi
        </a>

        <a href="<?= BASE_URL ?>/petugas/profil.php"
        class="<?= $halaman_aktif === 'profil.php' ? 'active' : '' ?>">
            Profil
        </a>
    </nav>

    <div class="app-user">
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">
