<?php
require_once __DIR__ . '/../config/auth.php';
if (!empty($_SESSION['id_pengguna'])) { header('Location: ' . halaman_utama($_SESSION['role'])); exit; }

$error = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telp = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if ($nama === '' || $email === '' || $pw === '') $error = 'Nama, email, dan password wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (strlen($pw) < 6) $error = 'Password minimal 6 karakter.';
    elseif ($pw !== $pw2) $error = 'Konfirmasi password tidak sama.';
    else {
        $cek = mysqli_prepare($conn, 'SELECT 1 FROM tb_pengguna WHERE email = ?');
        mysqli_stmt_bind_param($cek, 's', $email);
        mysqli_stmt_execute($cek);
        if (mysqli_fetch_row(mysqli_stmt_get_result($cek))) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            // Registrasi publik SELALU berperan pelanggan. Petugas/admin dibuat oleh admin.
            $ins = mysqli_prepare($conn, "INSERT INTO tb_pengguna (nama,email,password,no_telepon,alamat,role) VALUES (?,?,?,?,?,'pelanggan')");
            mysqli_stmt_bind_param($ins, 'sssss', $nama, $email, $hash, $telp, $alamat);
            mysqli_stmt_execute($ins);
            $ok = 'Registrasi berhasil. Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar - Laundry</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="auth">
<div class="card">
  <h1>Daftar Akun</h1>
  <p class="sub">Akun pelanggan baru</p>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert ok"><?= e($ok) ?> <a href="login.php">Ke halaman login</a></div><?php endif; ?>
  <form method="post">
    <label>Nama lengkap</label><input name="nama" required value="<?= e($_POST['nama'] ?? '') ?>">
    <label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    <label>No. telepon</label><input name="no_telepon" maxlength="15" value="<?= e($_POST['no_telepon'] ?? '') ?>">
    <label>Alamat</label><textarea name="alamat" rows="2"><?= e($_POST['alamat'] ?? '') ?></textarea>
    <label>Password</label><input type="password" name="password" required>
    <label>Ulangi password</label><input type="password" name="password2" required>
    <button type="submit">Daftar</button>
  </form>
  <p class="foot">Sudah punya akun? <a href="login.php">Masuk</a></p>
</div>
</body>
</html>
