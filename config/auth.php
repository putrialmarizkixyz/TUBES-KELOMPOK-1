<?php
require_once __DIR__ . '/koneksi.php';

function wajib_login(array $roles = []) {
    global $conn;
    if (empty($_SESSION['id_pengguna'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    // Peran dicek ulang ke database di setiap permintaan, sehingga perubahan peran
    // atau penghapusan akun oleh admin langsung berlaku tanpa menunggu pengguna logout.
    $id = (int)$_SESSION['id_pengguna'];
    $q = mysqli_prepare($conn, 'SELECT nama, role FROM tb_pengguna WHERE id_pengguna = ?');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if (!$u) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    $_SESSION['nama'] = $u['nama'];
    $_SESSION['role'] = $u['role'];

    if ($roles && !in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('Akses ditolak: halaman ini bukan untuk peran Anda.');
    }
}

function halaman_utama($role) {
    return BASE_URL . '/' . $role . '/dashboard.php';
}
