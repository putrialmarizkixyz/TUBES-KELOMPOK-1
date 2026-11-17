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

    <div class="brand">
        <div class="brand-icon">🧺</div>
        <h1>Buat Akun Baru</h1>
        <p class="sub">Daftar untuk menggunakan layanan laundry kami</p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
        <div class="alert ok">
            <?= e($ok) ?>
            <a href="login.php">Ke halaman login</a>
        </div>
    <?php endif; ?>

    <form method="post">

        <label>Nama lengkap</label>
        <input
            name="nama"
            required
            placeholder="Masukkan nama lengkap"
            value="<?= e($_POST['nama'] ?? '') ?>"
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            required
            placeholder="Masukkan email"
            value="<?= e($_POST['email'] ?? '') ?>"
        >

        <label>No. telepon</label>
        <input
            name="no_telepon"
            maxlength="15"
            placeholder="Masukkan nomor telepon"
            value="<?= e($_POST['no_telepon'] ?? '') ?>"
        >

        <label>Alamat</label>
        <textarea
            name="alamat"
            rows="2"
            placeholder="Masukkan alamat"
        ><?= e($_POST['alamat'] ?? '') ?></textarea>

        <label>Password</label>
        <div class="password-wrap">
            <input
                type="password"
                name="password"
                id="pw"
                required
                placeholder="Minimal 6 karakter"
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

        <label>Ulangi password</label>
        <div class="password-wrap">
            <input
                type="password"
                name="password2"
                id="pw2"
                required
                placeholder="Masukkan ulang password"
            >  
            <button
                type="button"
                class="password-toggle"
                data-target="pw2"
                aria-label="Tampilkan password"
                title="Tampilkan password"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>

        <button type="submit">Daftar Sekarang</button>

    </form>

    <p class="foot">
        Sudah punya akun?
        <a href="login.php">Masuk sekarang</a>
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

