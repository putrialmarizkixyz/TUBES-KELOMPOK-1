<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Pesanan Masuk';

// Konfirmasi pesanan: menunggu -> diterima, sekaligus mencatat petugas yang menangani
// Tolak pesanan: menunggu -> ditolak (dengan alasan opsional)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $idpt = (int)$_SESSION['id_pengguna'];

    if (($_POST['aksi'] ?? '') === 'tolak') {
        $alasan = trim($_POST['alasan'] ?? '');
        $alasan = $alasan === '' ? null : mb_substr($alasan, 0, 255);
        $t = mysqli_prepare($conn, "UPDATE tb_pesanan SET status = 'ditolak', alasan_penolakan = ? WHERE id_pesanan = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($t, 'si', $alasan, $id_pesanan);
        mysqli_stmt_execute($t);
        header('Location: pesanan_masuk.php?msg=' . (mysqli_stmt_affected_rows($t) > 0 ? 'ditolak' : 'gagal'));
        exit;
    }

    $u = mysqli_prepare($conn, "UPDATE tb_pesanan SET status = 'diterima', id_petugas = ? WHERE id_pesanan = ? AND status = 'menunggu'");
    mysqli_stmt_bind_param($u, 'ii', $idpt, $id_pesanan);
    mysqli_stmt_execute($u);
    if (mysqli_stmt_affected_rows($u) > 0) {
        header('Location: pesanan_kelola.php?id=' . $id_pesanan . '&msg=diterima');
    } else {
        header('Location: pesanan_masuk.php?msg=gagal'); // sudah diambil petugas lain
    }
    exit;
}

$hasil = mysqli_query($conn, "SELECT p.*, u.nama, u.no_telepon FROM tb_pesanan p JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE p.status = 'menunggu' ORDER BY p.tanggal_pesan ASC");
require __DIR__ . '/_header.php';
?>
<h2>Pesanan Masuk</h2>
<?php if (($_GET['msg'] ?? '') === 'gagal'): ?><div class="alert">Pesanan tersebut sudah diproses petugas lain.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') === 'ditolak'): ?><div class="alert ok">Pesanan berhasil ditolak.</div><?php endif; ?>
<div class="tbl-wrap">
<table>
  <tr><th>No.</th><th>Tanggal</th><th>Pelanggan</th><th>Telepon</th><th>Perkiraan Total</th><th></th></tr>
  <?php $ada = false; while ($p = mysqli_fetch_assoc($hasil)): $ada = true; ?>
  <tr>
    <td>#<?= (int)$p['id_pesanan'] ?></td>
    <td><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></td>
    <td><?= e($p['nama']) ?></td>
    <td><?= e($p['no_telepon']) ?></td>
    <td><?= rupiah($p['total_harga']) ?></td>
    <td>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="post" style="margin:0">
          <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan'] ?>">
          <button type="submit" style="margin:0">Konfirmasi &amp; Terima</button>
        </form>
        <form method="post" style="margin:0;display:flex;gap:6px;align-items:center" onsubmit="return confirm('Tolak pesanan #<?= (int)$p['id_pesanan'] ?>?');">
          <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan'] ?>">
          <input type="hidden" name="aksi" value="tolak">
          <input type="text" name="alasan" maxlength="255" placeholder="Alasan (opsional)" style="margin:0;width:160px">
          <button type="submit" style="margin:0;background:#c0392b;color:#fff">Tolak</button>
        </form>
      </div>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="6">Belum ada pesanan baru.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
