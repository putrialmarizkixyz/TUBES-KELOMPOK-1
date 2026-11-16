<?php
$judul = 'Pesanan Saya';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];
$q = mysqli_prepare($conn, 'SELECT * FROM tb_pesanan WHERE id_pengguna = ? ORDER BY tanggal_pesan DESC');
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>
<h2>Pesanan Saya</h2>
<div class="tbl-wrap">
<table>
  <tr><th>No.</th><th>Tanggal</th><th>Status</th><th>Estimasi Selesai</th><th>Total</th><th></th></tr>
  <?php $ada = false; while ($p = mysqli_fetch_assoc($hasil)): $ada = true; ?>
  <tr>
    <td>#<?= (int)$p['id_pesanan'] ?></td>
    <td><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></td>
    <td><span class="badge <?= e($p['status']) ?>"><?= e(label_status($p['status'])) ?></span></td>
    <td><?= $p['estimasi_selesai'] ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai']))) : '-' ?></td>
    <td><?= rupiah($p['total_harga']) ?></td>
    <td><a href="pesanan_detail.php?id=<?= (int)$p['id_pesanan'] ?>">Detail</a></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="6">Belum ada pesanan. <a href="pesanan_baru.php">Buat pesanan pertama</a>.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
