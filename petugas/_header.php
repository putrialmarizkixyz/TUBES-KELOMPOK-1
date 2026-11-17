<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Petugas') ?> - LaundryKu</title>
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
        <a href="<?= BASE_URL ?>/petugas/pesanan_masuk.php">Pesanan Masuk</a>
        <a href="<?= BASE_URL ?>/petugas/pesanan_aktif.php">Pesanan Saya</a>
    </nav>

    <div class="app-user">
        <span class="user-avatar">👤</span>
        <span class="user-name">Halo, <?= e($_SESSION['nama']) ?></span>
        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
    </div>
</header>

<main class="app-main">
