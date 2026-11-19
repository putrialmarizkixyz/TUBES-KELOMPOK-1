<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);

$halaman_aktif = basename($_SERVER['PHP_SELF']);
$q_notif = mysqli_prepare($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tb_notifikasi
    WHERE id_pengguna = ?
    AND status_baca = 'belum_dibaca'
");

mysqli_stmt_bind_param($q_notif, 'i', $_SESSION['id_pengguna']);
mysqli_stmt_execute($q_notif);

$jumlah_notifikasi = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_notif)
)['jumlah'];
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
        <a href="<?= BASE_URL ?>/pelanggan/dashboard.php"
        class="<?= $halaman_aktif === 'dashboard.php' ? 'active' : '' ?>">
            Beranda
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php"
        class="<?= $halaman_aktif === 'pesanan_saya.php' || $halaman_aktif === 'pesanan_detail.php' ? 'active' : '' ?>">
            Pesanan Saya
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/cari_laundry.php?pilih=1"
        class="<?= $halaman_aktif === 'pesanan_baru.php' ? 'active' : '' ?>">
            Buat Pesanan
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/cari_laundry.php"
        class="<?= $halaman_aktif === 'cari_laundry.php' ? 'active' : '' ?>">
            Cari Laundry
        </a>
    </nav>

    <div class="app-user">
        <a href="<?= BASE_URL ?>/pelanggan/notifikasi.php"
        class="notification-bell"
        title="Notifikasi">
            🔔

            <?php if ($jumlah_notifikasi > 0): ?>
                <span class="notification-count">
                    <?= $jumlah_notifikasi > 99 ? '99+' : $jumlah_notifikasi ?>
                </span>
            <?php endif; ?>
        </a>
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">

