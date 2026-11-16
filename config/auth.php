<?php
require_once __DIR__ . '/koneksi.php';

// Pakai di awal setiap halaman terlindungi.
// Contoh: wajib_login(['admin']);  atau  wajib_login(); (semua role)
function wajib_login(array $roles = []) {
    if (empty($_SESSION['id_pengguna'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('Akses ditolak: halaman ini bukan untuk peran Anda.');
    }
}

function halaman_utama($role) {
    return BASE_URL . '/' . $role . '/dashboard.php';
}
