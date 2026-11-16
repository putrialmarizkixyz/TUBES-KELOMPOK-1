<?php
require_once __DIR__ . '/../config/auth.php';
if (!empty($_SESSION['id_pengguna'])) { header('Location: ' . halaman_utama($_SESSION['role'])); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, 'SELECT id_pengguna, nama, password, role FROM tb_pengguna WHERE email = ?');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['id_pengguna'] = $user['id_pengguna'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . halaman_utama($user['role']));
        exit;
    }
    $error = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="auth">
<div class="card">
  <h1>Masuk</h1>
  <p class="sub">Sistem Informasi Manajemen Laundry</p>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    <label>Password</label>
    <input type="password" name="password" id="pw" required>
    <label class="cek"><input type="checkbox" onclick="document.getElementById('pw').type=this.checked?'text':'password'"> Tampilkan password</label>
    <button type="submit">Masuk</button>
  </form>
  <p class="foot">Belum punya akun? <a href="register.php">Daftar</a></p>
</div>
</body>
</html>
