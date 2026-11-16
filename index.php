<?php
require_once __DIR__ . '/config/auth.php';
if (!empty($_SESSION['id_pengguna'])) { header('Location: ' . halaman_utama($_SESSION['role'])); exit; }
header('Location: ' . BASE_URL . '/auth/login.php');
