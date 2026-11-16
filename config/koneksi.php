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
    $m = ['menunggu'=>'Menunggu','diterima'=>'Diterima','dicuci'=>'Dicuci','dikeringkan'=>'Dikeringkan',
          'disetrika'=>'Disetrika','selesai'=>'Selesai','siap_diambil'=>'Siap Diambil'];
    return $m[$s] ?? $s;
}
