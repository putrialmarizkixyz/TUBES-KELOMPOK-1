<?php
$judul = 'Pesanan Saya';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];
$q = mysqli_prepare($conn, "SELECT p.*, u.nama FROM tb_pesanan p JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE p.id_petugas = ? ORDER BY (p.status = 'siap_diambil') ASC, p.tanggal_pesan DESC");
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>
<h2>Pesanan yang Saya Tangani</h2>
<div class="tbl-wrap">
<table>
  <tr><th>No.</th><th>Pelanggan</th><th>Status</th><th>Estimasi Selesai</th><th>Total</th><th></th></tr>
  <?php $ada = false; while ($p = mysqli_fetch_assoc($hasil)): $ada = true; ?>
  <tr>
    <td>#<?= (int)$p['id_pesanan'] ?></td>
    <td><?= e($p['nama']) ?></td>
    <td><span class="badge <?= e($p['status']) ?>"><?= e(label_status($p['status'])) ?></span></td>
    <td><?= $p['estimasi_selesai'] ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai']))) : '-' ?></td>
    <td><?= rupiah($p['total_harga']) ?></td>
    <td><a href="pesanan_kelola.php?id=<?= (int)$p['id_pesanan'] ?>">Kelola</a></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="6">Belum ada pesanan. Konfirmasi dulu dari <a href="pesanan_masuk.php">Pesanan Masuk</a>.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
