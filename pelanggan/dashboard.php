<?php
$judul = 'Dashboard Pelanggan';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];
$q = mysqli_prepare($conn, "SELECT COUNT(*) AS total, SUM(status NOT IN ('selesai','siap_diambil')) AS aktif FROM tb_pesanan WHERE id_pengguna = ?");
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$ringkas = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
?>
<h2>Menu</h2>
<p class="sub">Pesanan aktif: <strong><?= (int)$ringkas['aktif'] ?></strong> &nbsp;|&nbsp; Total pesanan: <strong><?= (int)$ringkas['total'] ?></strong></p>
<ul class="menu">
  <li><a href="layanan.php">Lihat Layanan &amp; Harga</a></li>
  <li><a href="pesanan_baru.php">Buat Pesanan</a></li>
  <li><a href="pesanan_saya.php">Status Laundry &amp; Riwayat</a></li>
  <li>Pembayaran <br><small>(segera hadir)</small></li>
  <li>Profil <br><small>(segera hadir)</small></li>
</ul>
<?php require __DIR__ . '/_footer.php'; ?>
