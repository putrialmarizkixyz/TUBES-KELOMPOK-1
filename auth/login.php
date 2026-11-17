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

    <div class="brand">
        <div class="brand-icon">🧺</div>
        <h1>Selamat Datang</h1>
        <p class="sub">Sistem Informasi Manajemen Laundry</p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email</label>
        <input
            type="email"
            name="email"
            required
            placeholder="Masukkan email"
            value="<?= e($_POST['email'] ?? '') ?>"
        >

        <label>Password</label>
        <div class="password-wrap">
            <input
                type="password"
                name="password"
                id="pw"
                required
                placeholder="Masukkan password"
            >
            <button
                type="button"
                class="password-toggle"
                data-target="pw"
                aria-label="Tampilkan password"
                title="Tampilkan password"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>

        </div>

        <button type="submit">Masuk</button>
    </form>

    <p class="foot">
        Belum punya akun?
        <a href="register.php">Daftar sekarang</a>
    </p>

</div>

<script>
document.querySelectorAll('.password-toggle').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        const visible = input.type === 'password';

        input.type = visible ? 'text' : 'password';

        this.innerHTML = visible
            ? `<svg viewBox="0 0 24 24" aria-hidden="true">
                 <path d="M3 3l18 18"/>
                 <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                 <path d="M9.9 5.2A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a15 15 0 01-4 4.7"/>
                 <path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/>
               </svg>`
            : `<svg viewBox="0 0 24 24" aria-hidden="true">
                 <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                 <circle cx="12" cy="12" r="3"/>
               </svg>`;

        this.setAttribute(
            'aria-label',
            visible ? 'Sembunyikan password' : 'Tampilkan password'
        );
        this.title = visible ? 'Sembunyikan password' : 'Tampilkan password';
    });
});
</script>

</body>
</html>
