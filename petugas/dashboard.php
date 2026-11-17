<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Petugas - LaundryKu</title>
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
        <a href="<?= BASE_URL ?>/petugas/dashboard.php">Beranda</a>
    </nav>

    <div class="app-user">
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">

    <section class="welcome-section">
        <div>
            <span class="welcome-label">DASHBOARD PETUGAS</span>
            <h1>Selamat Datang, <?= e($_SESSION['nama']) ?>!</h1>
            <p>Kelola pesanan dan pantau proses pengerjaan laundry.</p>
        </div>
        <div class="welcome-icon">👨‍🔧</div>
    </section>

    <section class="dashboard-section">
        <div class="section-heading">
            <h2>Menu Pengelolaan</h2>
            <p>Pilih aktivitas yang ingin Anda lakukan.</p>
        </div>

        <div class="dashboard-menu">

            <div class="dashboard-card">
                <div class="dashboard-icon blue">📥</div>
                <div>
                    <h3>Pesanan Masuk</h3>
                    <p>Melihat daftar pesanan dari pelanggan.</p>
                    <span class="menu-status">Segera tersedia</span>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-icon purple">⚖️</div>
                <div>
                    <h3>Input Berat Aktual</h3>
                    <p>Mencatat berat cucian setelah ditimbang.</p>
                    <span class="menu-status">Segera tersedia</span>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-icon orange">🔄</div>
                <div>
                    <h3>Update Status Pengerjaan</h3>
                    <p>Memperbarui tahapan proses laundry.</p>
                    <span class="menu-status">Segera tersedia</span>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-icon green">📦</div>
                <div>
                    <h3>Konfirmasi Siap Diambil</h3>
                    <p>Menandai pesanan yang sudah siap diambil.</p>
                    <span class="menu-status">Segera tersedia</span>
                </div>
            </div>

        </div>
    </section>

</main>

<footer class="app-footer">
    <p>&copy; 2026 LaundryKu. Sistem Informasi Manajemen Laundry.</p>
</footer>

</body>
</html>
