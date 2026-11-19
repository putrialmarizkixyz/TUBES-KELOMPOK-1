<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Layanan & Harga';
$idl = laundry_petugas($conn); // laundry tempat petugas bertugas

function jalankan($stmt) { try { return mysqli_stmt_execute($stmt); } catch (Throwable $e) { return false; } }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idl !== null) {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int)($_POST['id_layanan'] ?? 0);
        $nama = trim($_POST['nama_layanan'] ?? '');
        $harga = (float)str_replace(',', '.', $_POST['harga_per_kg'] ?? '0');
        $est = (int)($_POST['estimasi_selesai'] ?? 0);
        $ket = trim($_POST['keterangan'] ?? '');
        if ($nama === '' || $harga <= 0 || $est <= 0) {
            $error = 'Nama, harga (> 0), dan estimasi jam (> 0) wajib diisi dengan benar.';
        } else {
            if ($id > 0) {
                $s = mysqli_prepare($conn, 'UPDATE tb_layanan SET nama_layanan=?, harga_per_kg=?, estimasi_selesai=?, keterangan=? WHERE id_layanan=? AND id_laundry=?');
                mysqli_stmt_bind_param($s, 'sdisii', $nama, $harga, $est, $ket, $id, $idl);
            } else {
                $s = mysqli_prepare($conn, 'INSERT INTO tb_layanan (id_laundry, nama_layanan, harga_per_kg, estimasi_selesai, keterangan) VALUES (?,?,?,?,?)');
                mysqli_stmt_bind_param($s, 'isdis', $idl, $nama, $harga, $est, $ket);
            }
            if (jalankan($s)) { header('Location: layanan.php?msg=tersimpan'); exit; }
            $error = 'Gagal menyimpan layanan.';
        }
    }
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_layanan'] ?? 0);
        $s = mysqli_prepare($conn, 'DELETE FROM tb_layanan WHERE id_layanan = ? AND id_laundry = ?');
        mysqli_stmt_bind_param($s, 'ii', $id, $idl);
        if (jalankan($s)) { header('Location: layanan.php?msg=dihapus'); exit; }
        $error = 'Layanan sudah dipakai pada pesanan, sehingga tidak dapat dihapus. Ubah harganya saja.';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = mysqli_prepare($conn, 'SELECT * FROM tb_layanan WHERE id_layanan = ? AND id_laundry = ?');
    $ide = (int)$_GET['edit'];
    $idl_q = (int)$idl;
    mysqli_stmt_bind_param($s, 'ii', $ide, $idl_q);
    mysqli_stmt_execute($s);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
}
$idl_q = (int)$idl;
$qd = mysqli_prepare($conn, 'SELECT * FROM tb_layanan WHERE id_laundry = ? ORDER BY id_layanan');
mysqli_stmt_bind_param($qd, 'i', $idl_q);
mysqli_stmt_execute($qd);
$daftar = mysqli_stmt_get_result($qd);

$nama_laundry = '';
if ($idl !== null) {
    $ql = mysqli_prepare($conn, 'SELECT nama_laundry FROM tb_laundry WHERE id_laundry = ?');
    mysqli_stmt_bind_param($ql, 'i', $idl);
    mysqli_stmt_execute($ql);
    $nama_laundry = (string)(mysqli_fetch_assoc(mysqli_stmt_get_result($ql))['nama_laundry'] ?? '');
}
require __DIR__ . '/_header.php';
?>
<div class="admin-service-page">
<h2>Layanan &amp; Harga<?= $nama_laundry !== '' ? ' - ' . e($nama_laundry) : '' ?></h2>
<?php if ($idl === null): ?>
  <div class="alert">Akun Anda belum ditautkan ke laundry mana pun, sehingga belum bisa mengelola layanan. Hubungi admin.</div>
<?php require __DIR__ . '/_footer.php'; exit; endif; ?>
<p>Layanan dan harga di bawah ini yang akan dilihat pelanggan saat memesan di laundry Anda.</p>
<?php if (($_GET['msg'] ?? '') === 'tersimpan'): ?><div class="alert ok">Layanan berhasil disimpan.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'dihapus'): ?><div class="alert ok">Layanan dihapus.</div><?php endif; ?>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="panel">
  <h3><?= $edit ? 'Ubah Layanan' : 'Tambah Layanan' ?></h3>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id_layanan" value="<?= $edit ? (int)$edit['id_layanan'] : 0 ?>">
  <label>Nama layanan</label>
  <input name="nama_layanan" required maxlength="100" value="<?= e($edit['nama_layanan'] ?? '') ?>">
  <label>Harga per kg (Rp)</label>
  <input type="number" name="harga_per_kg" required min="0" step="100" value="<?= e($edit ? (int)$edit['harga_per_kg'] : '') ?>">
  <label>Estimasi selesai (jam)</label>
  <input type="number" name="estimasi_selesai" required min="1" value="<?= e($edit['estimasi_selesai'] ?? '') ?>">
  <label>Keterangan</label>
  <textarea name="keterangan" rows="2"><?= e($edit['keterangan'] ?? '') ?></textarea>
  <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Layanan' ?></button>
  <?php if ($edit): ?><p><a href="layanan.php">Batal ubah</a></p><?php endif; ?>
</form>

<div class="tbl-wrap">
<table>
  <tr><th>Layanan</th><th>Harga / kg</th><th>Estimasi</th><th>Keterangan</th><th></th></tr>
  <?php while ($l = mysqli_fetch_assoc($daftar)): ?>
  <tr>
    <td><strong><?= e($l['nama_layanan']) ?></strong></td>
    <td><?= rupiah($l['harga_per_kg']) ?></td>
    <td><?= (int)$l['estimasi_selesai'] ?> jam</td>
    <td><?= e($l['keterangan']) ?></td>
    <td>
      <a href="layanan.php?edit=<?= (int)$l['id_layanan'] ?>">Ubah</a>
      <form method="post" style="display:inline" onsubmit="return confirm('Hapus layanan ini?')">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id_layanan" value="<?= (int)$l['id_layanan'] ?>">
        <button type="submit" style="width:auto;margin:0 0 0 8px;padding:4px 10px">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>