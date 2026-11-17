<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
$judul = 'Monitoring Transaksi';

$tahap = ['menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil','ditolak'];
$filter = $_GET['status'] ?? '';

$sql = "SELECT p.*, u.nama AS pelanggan, t.nama AS petugas,
  (SELECT status_bayar FROM tb_pembayaran b WHERE b.id_pesanan = p.id_pesanan ORDER BY b.id_pembayaran DESC LIMIT 1) AS status_bayar,
  (SELECT metode_bayar FROM tb_pembayaran b WHERE b.id_pesanan = p.id_pesanan ORDER BY b.id_pembayaran DESC LIMIT 1) AS metode_bayar,
  (SELECT bukti_bayar FROM tb_pembayaran b WHERE b.id_pesanan = p.id_pesanan ORDER BY b.id_pembayaran DESC LIMIT 1) AS bukti_bayar
  FROM tb_pesanan p
  JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
  LEFT JOIN tb_pengguna t ON t.id_pengguna = p.id_petugas";
if (in_array($filter, $tahap, true)) {
    $q = mysqli_prepare($conn, $sql . ' WHERE p.status = ? ORDER BY p.tanggal_pesan DESC');
    mysqli_stmt_bind_param($q, 's', $filter);
    mysqli_stmt_execute($q);
    $hasil = mysqli_stmt_get_result($q);
} else {
    $hasil = mysqli_query($conn, $sql . ' ORDER BY p.tanggal_pesan DESC');
}
require __DIR__ . '/_header.php';
?>
<div class="admin-transaction-page">
    <h2>Monitoring Transaksi</h2>

    <form method="get" class="panel">
  <label>Filter status pesanan</label>
  <select name="status" onchange="this.form.submit()">
    <option value="">Semua</option>
    <?php foreach ($tahap as $t): ?><option value="<?= $t ?>" <?= $filter === $t ? 'selected' : '' ?>><?= e(label_status($t)) ?></option><?php endforeach; ?>
  </select>
</form>
<div class="tbl-wrap">
<table>
  <tr><th>No.</th><th>Tanggal</th><th>Pelanggan</th><th>Petugas</th><th>Status</th><th>Total</th><th>Pembayaran</th></tr>
  <?php $ada = false; while ($p = mysqli_fetch_assoc($hasil)): $ada = true; ?>
  <tr>
    <td>#<?= (int)$p['id_pesanan'] ?></td>
    <td><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></td>
    <td><?= e($p['pelanggan']) ?></td>
    <td><?= e($p['petugas'] ?? '-') ?></td>
    <td><span class="badge <?= e($p['status']) ?>"><?= e(label_status($p['status'])) ?></span></td>
    <td><?= rupiah($p['total_harga']) ?></td>
    <td>
      <?php if ($p['status_bayar'] === 'lunas'): ?>
        <span class="badge lunas">Lunas</span> <small><?= e($p['metode_bayar']) ?></small>
        <?php if ($p['bukti_bayar']): ?><br><a href="<?= BASE_URL . '/' . e($p['bukti_bayar']) ?>" target="_blank">Bukti</a><?php endif; ?>
      <?php else: ?><span class="badge belum_bayar">Belum bayar</span><?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="7">Tidak ada transaksi.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
