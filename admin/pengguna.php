<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
$judul = 'Kelola Pengguna';

$roles = ['pelanggan', 'petugas', 'admin'];
$saya = (int)$_SESSION['id_pengguna'];
$error = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? ''); $email = trim($_POST['email'] ?? '');
        $telp = trim($_POST['no_telepon'] ?? ''); $alamat = trim($_POST['alamat'] ?? '');
        $pw = $_POST['password'] ?? ''; $role = $_POST['role'] ?? '';
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6 || !in_array($role, $roles, true)) {
            $error = 'Isi nama, email valid, password (min. 6 karakter), dan peran dengan benar.';
        } else {
            $c = mysqli_prepare($conn, 'SELECT 1 FROM tb_pengguna WHERE email = ?');
            mysqli_stmt_bind_param($c, 's', $email);
            mysqli_stmt_execute($c);
            if (mysqli_fetch_row(mysqli_stmt_get_result($c))) {
                $error = 'Email sudah terdaftar.';
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $s = mysqli_prepare($conn, 'INSERT INTO tb_pengguna (nama,email,password,no_telepon,alamat,role) VALUES (?,?,?,?,?,?)');
                mysqli_stmt_bind_param($s, 'ssssss', $nama, $email, $hash, $telp, $alamat, $role);
                mysqli_stmt_execute($s);
                $ok = 'Akun ' . $role . ' berhasil ditambahkan.';
            }
        }
    }

    if ($aksi === 'role') {
        $id = (int)($_POST['id_pengguna'] ?? 0); $role = $_POST['role'] ?? '';
        if ($id === $saya) $error = 'Anda tidak dapat mengubah peran akun Anda sendiri.';
        elseif (!in_array($role, $roles, true)) $error = 'Peran tidak valid.';
        else {
            $s = mysqli_prepare($conn, 'UPDATE tb_pengguna SET role = ? WHERE id_pengguna = ?');
            mysqli_stmt_bind_param($s, 'si', $role, $id);
            mysqli_stmt_execute($s);
            $ok = 'Peran akun diperbarui.';
        }
    }
}

$filter = $_GET['role'] ?? '';
if (in_array($filter, $roles, true)) {
    $q = mysqli_prepare($conn, 'SELECT * FROM tb_pengguna WHERE role = ? ORDER BY role, nama');
    mysqli_stmt_bind_param($q, 's', $filter);
    mysqli_stmt_execute($q);
    $daftar = mysqli_stmt_get_result($q);
} else {
    $daftar = mysqli_query($conn, 'SELECT * FROM tb_pengguna ORDER BY role, nama');
}
require __DIR__ . '/_header.php';
?>
<h2>Pengguna &amp; Hak Akses</h2>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>

<form method="post" class="panel">
  <h3>Tambah Akun</h3>
  <input type="hidden" name="aksi" value="tambah">
  <label>Nama</label><input name="nama" required>
  <label>Email</label><input type="email" name="email" required>
  <label>No. telepon</label><input name="no_telepon" maxlength="15">
  <label>Alamat</label><textarea name="alamat" rows="2"></textarea>
  <label>Password</label><input type="password" name="password" required minlength="6">
  <label>Peran</label>
  <select name="role"><?php foreach ($roles as $r): ?><option value="<?= $r ?>"><?= ucfirst($r) ?></option><?php endforeach; ?></select>
  <button type="submit">Tambah Akun</button>
</form>

<p>Tampilkan:
  <a href="pengguna.php">Semua</a> |
  <?php foreach ($roles as $r): ?><a href="pengguna.php?role=<?= $r ?>"><?= ucfirst($r) ?></a> <?php endforeach; ?>
</p>
<div class="tbl-wrap">
<table>
  <tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Peran</th><th>Ubah peran</th></tr>
  <?php while ($u = mysqli_fetch_assoc($daftar)): ?>
  <tr>
    <td><?= e($u['nama']) ?></td>
    <td><?= e($u['email']) ?></td>
    <td><?= e($u['no_telepon']) ?></td>
    <td><?= e(ucfirst($u['role'])) ?></td>
    <td>
      <?php if ((int)$u['id_pengguna'] === $saya): ?>(akun Anda)
      <?php else: ?>
      <form method="post" style="margin:0">
        <input type="hidden" name="aksi" value="role">
        <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
        <select name="role" style="width:auto"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select>
        <button type="submit" style="width:auto;margin:0 0 0 6px;padding:4px 10px">Simpan</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
