<?php
if (session_status() === PHP_SESSION_NONE) session_start();
define('BASE_URL', '/TUBES-RPL');
date_default_timezone_set('Asia/Makassar'); 

$conn = mysqli_connect('localhost', 'root', '', 'db_laundry');
if (!$conn) die('Koneksi database gagal: ' . mysqli_connect_error());
mysqli_set_charset($conn, 'utf8mb4');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function label_status($s) {
    $m = [
        'menunggu' => 'Menunggu',
        'diterima' => 'Diterima',
        'dicuci' => 'Dicuci',
        'dikeringkan' => 'Dikeringkan',
        'disetrika' => 'Disetrika',
        'selesai' => 'Selesai',
        'siap_diambil' => 'Siap Diambil',
        'diambil' => 'Sudah Diambil',
        'ditolak' => 'Ditolak'
    ];

    return $m[$s] ?? $s;
}
// id laundry tempat petugas yang sedang login bertugas (null jika belum ditautkan admin)
function laundry_petugas($conn) {
    $id = (int)($_SESSION['id_pengguna'] ?? 0);
    $q = mysqli_prepare($conn, "SELECT id_laundry FROM tb_pengguna WHERE id_pengguna = ? AND role = 'petugas'");
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    return ($r && $r['id_laundry'] !== null) ? (int)$r['id_laundry'] : null;
}
function hitung_estimasi_jam($jam_dasar, $berat_kg) {
    $tambahan = max(0, ceil(($berat_kg - 5) / 5));
    return (int)$jam_dasar + (int)($tambahan * 6);
}

// Label status pembayaran (pelanggan mengirim bukti -> petugas memverifikasi)
function label_bayar($s) {
    $m = [
        'belum_bayar' => 'Belum Dibayar',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'lunas' => 'Lunas',
        'ditolak' => 'Pembayaran Ditolak'
    ];
    return $m[$s] ?? 'Belum Dibayar';
}
