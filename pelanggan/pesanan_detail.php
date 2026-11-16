<?php
$judul = 'Detail Pesanan';
require __DIR__ . '/_header.php';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idp = (int)$_SESSION['id_pengguna'];

// Hanya boleh melihat pesanan milik sendiri
$q = mysqli_prepare($conn, 'SELECT * FROM tb_pesanan WHERE id_pesanan = ? AND id_pengguna = ?');
mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idp);
mysqli_stmt_execute($q);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
if (!$p) { http_response_code(404); die('Pesanan tidak ditemukan.'); }

$q = mysqli_prepare($conn, 'SELECT d.*, l.nama_layanan, l.harga_per_kg FROM tb_detail_laundry d JOIN tb_layanan l ON l.id_layanan = d.id_layanan WHERE d.id_pesanan = ?');
mysqli_stmt_bind_param($q, 'i', $id_pesanan);
mysqli_stmt_execute($q);
$detail = mysqli_stmt_get_result($q);

$tahap = ['menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil'];
$posisi = array_search($p['status'], $tahap, true);
$sudah_timbang = false;
?>
<?php if (!empty($_GET['baru'])): ?>
  <div class="alert ok">Pesanan berhasil dibuat. Nomor pesanan Anda: <strong>#<?= $id_pesanan ?></strong>. Menunggu konfirmasi petugas.</div>
<?php endif; ?>

<h2>Pesanan #<?= $id_pesanan ?></h2>
<div class="panel">
  <div class="steps">
    <?php foreach ($tahap as $i => $t): ?>
      <span class="<?= $i <= $posisi ? 'done' : '' ?>"><?= e(label_status($t)) ?></span>
    <?php endforeach; ?>
  </div>
  <p>Dipesan: <?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?><br>
     Estimasi selesai: <?= $p['estimasi_selesai'] ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai']))) : '-' ?></p>
</div>

<div class="tbl-wrap">
<table>
  <tr><th>Layanan</th><th>Harga/kg</th><th>Berat perkiraan</th><th>Berat aktual</th><th>Subtotal</th><th>Catatan</th></tr>
  <?php while ($d = mysqli_fetch_assoc($detail)): if ($d['berat_aktual'] !== null) $sudah_timbang = true; ?>
  <tr>
    <td><?= e($d['nama_layanan']) ?></td>
    <td><?= rupiah($d['harga_per_kg']) ?></td>
    <td><?= e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) ?> kg</td>
    <td><?= $d['berat_aktual'] !== null ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg' : 'Belum ditimbang' ?></td>
    <td><?= rupiah($d['subtotal']) ?></td>
    <td><?= e($d['catatan']) ?></td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
<p class="total"><?= $sudah_timbang ? 'Harga pasti' : 'Perkiraan total' ?>: <?= rupiah($p['total_harga']) ?></p>
<?php if (!$sudah_timbang): ?><p class="sub">Harga pasti dihitung setelah petugas menimbang cucian Anda.</p><?php endif; ?>
<p><a href="pesanan_saya.php">&larr; Kembali ke Pesanan Saya</a></p>
<?php require __DIR__ . '/_footer.php'; ?>
