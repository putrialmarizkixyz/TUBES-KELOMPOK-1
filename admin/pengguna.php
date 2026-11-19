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

    if ($aksi === 'laundry') {
        $id = (int)($_POST['id_pengguna'] ?? 0);
        $idl = (int)($_POST['id_laundry'] ?? 0);
        if ($idl > 0) {
            $c = mysqli_prepare($conn, 'SELECT 1 FROM tb_laundry WHERE id_laundry = ?');
            mysqli_stmt_bind_param($c, 'i', $idl);
            mysqli_stmt_execute($c);
            if (!mysqli_fetch_row(mysqli_stmt_get_result($c))) { $error = 'Laundry tidak ditemukan.'; $idl = -1; }
        }
        if ($idl >= 0) {
            $s = mysqli_prepare($conn, "UPDATE tb_pengguna SET id_laundry = NULLIF(?, 0) WHERE id_pengguna = ? AND role = 'petugas'");
            mysqli_stmt_bind_param($s, 'ii', $idl, $id);
            mysqli_stmt_execute($s);
            $ok = 'Laundry tempat bertugas diperbarui.';
        }
    }

    if ($aksi === 'ubah') {
        $id = (int)($_POST['id_pengguna'] ?? 0);
        $nama = trim($_POST['nama'] ?? ''); $email = trim($_POST['email'] ?? '');
        $telp = trim($_POST['no_telepon'] ?? ''); $alamat = trim($_POST['alamat'] ?? '');
        if ($id <= 0 || $nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Nama dan email yang valid wajib diisi.';
        } else {
            $c = mysqli_prepare($conn, 'SELECT 1 FROM tb_pengguna WHERE email = ? AND id_pengguna <> ?');
            mysqli_stmt_bind_param($c, 'si', $email, $id);
            mysqli_stmt_execute($c);
            if (mysqli_fetch_row(mysqli_stmt_get_result($c))) {
                $error = 'Email tersebut sudah dipakai akun lain.';
            } else {
                $s = mysqli_prepare($conn, 'UPDATE tb_pengguna SET nama = ?, email = ?, no_telepon = ?, alamat = ? WHERE id_pengguna = ?');
                mysqli_stmt_bind_param($s, 'ssssi', $nama, $email, $telp, $alamat, $id);
                mysqli_stmt_execute($s);
                $ok = 'Data akun diperbarui.';
            }
        }
    }

    if ($aksi === 'reset') {
        $id = (int)($_POST['id_pengguna'] ?? 0); $pw = $_POST['password_baru'] ?? '';
        if (strlen($pw) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $s = mysqli_prepare($conn, 'UPDATE tb_pengguna SET password = ? WHERE id_pengguna = ?');
            mysqli_stmt_bind_param($s, 'si', $hash, $id);
            mysqli_stmt_execute($s);
            $ok = 'Password akun berhasil direset.';
        }
    }

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_pengguna'] ?? 0);
        if ($id === $saya) {
            $error = 'Anda tidak dapat menghapus akun Anda sendiri.';
        } else {
            try {
                $s = mysqli_prepare($conn, 'DELETE FROM tb_pengguna WHERE id_pengguna = ?');
                mysqli_stmt_bind_param($s, 'i', $id);
                if (mysqli_stmt_execute($s)) $ok = 'Akun dihapus.';
                else $error = 'Akun ini sudah memiliki riwayat pesanan atau laporan sehingga tidak dapat dihapus.';
            } catch (Throwable $ex) {
                $error = 'Akun ini sudah memiliki riwayat pesanan atau laporan sehingga tidak dapat dihapus.';
            }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = mysqli_prepare($conn, 'SELECT * FROM tb_pengguna WHERE id_pengguna = ?');
    $ide = (int)$_GET['edit'];
    mysqli_stmt_bind_param($s, 'i', $ide);
    mysqli_stmt_execute($s);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
}

$daftar_laundry = mysqli_fetch_all(mysqli_query($conn, 'SELECT id_laundry, nama_laundry FROM tb_laundry ORDER BY nama_laundry'), MYSQLI_ASSOC);

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
<div class="admin-user-page">
<h2>Pengguna &amp; Hak Akses</h2>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>

<?php if ($edit): ?>
<form method="post" class="panel">
  <h3>Ubah Data Akun</h3>
  <input type="hidden" name="aksi" value="ubah">
  <input type="hidden" name="id_pengguna" value="<?= (int)$edit['id_pengguna'] ?>">
  <label>Nama</label><input name="nama" required value="<?= e($edit['nama']) ?>">
  <label>Email</label><input type="email" name="email" required value="<?= e($edit['email']) ?>">
  <label>No. telepon</label><input name="no_telepon" maxlength="15" value="<?= e($edit['no_telepon']) ?>">
  <label>Alamat</label><textarea name="alamat" rows="2"><?= e($edit['alamat']) ?></textarea>
  <button type="submit">Simpan Perubahan</button>
  <p><a href="pengguna.php">Batal ubah</a></p>
</form>
<?php endif; ?>

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
  <tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Peran</th><th>Laundry (petugas)</th><th>Ubah peran</th><th>Aksi</th></tr>
  <?php while ($u = mysqli_fetch_assoc($daftar)): ?>
  <tr>
    <td><?= e($u['nama']) ?></td>
    <td><?= e($u['email']) ?></td>
    <td><?= e($u['no_telepon']) ?></td>
    <td><?= e(ucfirst($u['role'])) ?></td>
    <td>
      <?php if ($u['role'] === 'petugas'): ?>
      <form method="post" style="margin:0">
        <input type="hidden" name="aksi" value="laundry">
        <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
        <select name="id_laundry" style="width:auto">
          <option value="0">- Belum ditautkan -</option>
          <?php foreach ($daftar_laundry as $lw): ?><option value="<?= (int)$lw['id_laundry'] ?>" <?= (int)$u['id_laundry'] === (int)$lw['id_laundry'] ? 'selected' : '' ?>><?= e($lw['nama_laundry']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" style="width:auto;margin:0 0 0 6px;padding:4px 10px">Simpan</button>
      </form>
      <?php else: ?>-<?php endif; ?>
    </td>
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
    <td>
      <a href="pengguna.php?edit=<?= (int)$u['id_pengguna'] ?>">Ubah data</a>
      <form method="post" style="margin:6px 0 0">
        <input type="hidden" name="aksi" value="reset">
        <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
        <input type="password" name="password_baru" placeholder="Password baru" minlength="6" required style="width:auto">
        <button type="submit" style="width:auto;margin:0 0 0 6px;padding:4px 10px">Reset</button>
      </form>
      <?php if ((int)$u['id_pengguna'] !== $saya): ?>
      <form method="post" style="margin:6px 0 0" onsubmit="return confirm('Hapus akun ini?')">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id_pengguna" value="<?= (int)$u['id_pengguna'] ?>">
        <button type="submit" style="width:auto;margin:0;padding:4px 10px">Hapus</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
