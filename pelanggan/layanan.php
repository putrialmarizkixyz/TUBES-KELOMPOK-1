<?php
$judul = 'Layanan & Harga';
require __DIR__ . '/_header.php';

$hasil = mysqli_query($conn, 'SELECT * FROM tb_layanan ORDER BY harga_per_kg');
?>
<h2>Layanan &amp; Harga</h2>
<div class="tbl-wrap">
<table>
  <tr><th>Layanan</th><th>Harga / kg</th><th>Estimasi</th><th>Keterangan</th></tr>
  <?php while ($l = mysqli_fetch_assoc($hasil)): ?>
  <tr>
    <td><strong><?= e($l['nama_layanan']) ?></strong></td>
    <td><?= rupiah($l['harga_per_kg']) ?></td>
    <td><?= (int)$l['estimasi_selesai'] ?> jam</td>
    <td><?= e($l['keterangan']) ?></td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
<p><a class="btn" href="pesanan_baru.php">Buat Pesanan</a></p>
<?php require __DIR__ . '/_footer.php'; ?>
