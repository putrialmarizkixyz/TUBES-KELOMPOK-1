<?php
require_once __DIR__ . '/../config/koneksi.php';
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
