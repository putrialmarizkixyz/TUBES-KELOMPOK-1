<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Pesanan Masuk';

// Konfirmasi pesanan: menunggu -> diterima, sekaligus mencatat petugas yang menangani
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $idpt = (int)$_SESSION['id_pengguna'];
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
<?php if (($_GET['msg'] ?? '') === 'gagal'): ?><div class="alert">Pesanan tersebut sudah dikonfirmasi petugas lain.</div><?php endif; ?>
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
      <form method="post" style="margin:0">
        <input type="hidden" name="id_pesanan" value="<?= (int)$p['id_pesanan'] ?>">
        <button type="submit" style="margin:0">Konfirmasi &amp; Terima</button>
      </form>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="6">Belum ada pesanan baru.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
