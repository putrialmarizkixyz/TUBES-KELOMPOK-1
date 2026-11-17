<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
$judul = 'Laporan';

$bulan = $_GET['bulan'] ?? $_POST['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

function rekap($conn, $bulan) {
    $q = mysqli_prepare($conn, "SELECT COUNT(*) AS n, COALESCE(SUM(jumlah_bayar),0) AS total FROM tb_pembayaran WHERE status_bayar = 'lunas' AND DATE_FORMAT(tanggal_bayar, '%Y-%m') = ?");
    mysqli_stmt_bind_param($q, 's', $bulan);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = rekap($conn, $bulan);            // dihitung ulang dari database, bukan dari input form
    $n = (int)$r['n']; $total = (float)$r['total']; $admin = (int)$_SESSION['id_pengguna'];
    $s = mysqli_prepare($conn, 'INSERT INTO tb_laporan (periode, total_transaksi, total_pendapatan, dibuat_oleh) VALUES (?,?,?,?)');
    mysqli_stmt_bind_param($s, 'sidi', $bulan, $n, $total, $admin);
    mysqli_stmt_execute($s);
    $ok = 'Laporan periode ' . $bulan . ' berhasil disimpan.';
}

$rekap = rekap($conn, $bulan);
$q = mysqli_prepare($conn, "SELECT b.*, u.nama FROM tb_pembayaran b JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE b.status_bayar = 'lunas' AND DATE_FORMAT(b.tanggal_bayar, '%Y-%m') = ? ORDER BY b.tanggal_bayar");
mysqli_stmt_bind_param($q, 's', $bulan);
mysqli_stmt_execute($q);
$rinci = mysqli_stmt_get_result($q);
$tersimpan = mysqli_query($conn, 'SELECT l.*, u.nama FROM tb_laporan l JOIN tb_pengguna u ON u.id_pengguna = l.dibuat_oleh ORDER BY l.id_laporan DESC');
require __DIR__ . '/_header.php';
?>
<h2>Laporan Transaksi &amp; Pendapatan</h2>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>

<form method="get" class="panel">
  <label>Periode (bulan)</label>
  <input type="month" name="bulan" value="<?= e($bulan) ?>" onchange="this.form.submit()">
</form>

<div class="panel">
  <p>Jumlah transaksi lunas: <strong><?= (int)$rekap['n'] ?></strong></p>
  <p class="total">Total pendapatan: <?= rupiah($rekap['total']) ?></p>
  <form method="post" style="margin:0">
    <input type="hidden" name="bulan" value="<?= e($bulan) ?>">
    <button type="submit">Simpan Laporan Periode Ini</button>
  </form>
</div>

<h3>Rincian Pembayaran Lunas (<?= e($bulan) ?>)</h3>
<div class="tbl-wrap">
<table>
  <tr><th>Tanggal bayar</th><th>Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th></tr>
  <?php $ada = false; while ($b = mysqli_fetch_assoc($rinci)): $ada = true; ?>
  <tr>
    <td><?= e(date('d/m/Y H:i', strtotime($b['tanggal_bayar']))) ?></td>
    <td>#<?= (int)$b['id_pesanan'] ?></td>
    <td><?= e($b['nama']) ?></td>
    <td><?= e($b['metode_bayar']) ?></td>
    <td><?= rupiah($b['jumlah_bayar']) ?></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="5">Belum ada pembayaran pada periode ini.</td></tr><?php endif; ?>
</table>
</div>

<h3>Laporan Tersimpan</h3>
<div class="tbl-wrap">
<table>
  <tr><th>Periode</th><th>Transaksi</th><th>Pendapatan</th><th>Dibuat oleh</th><th>Tanggal</th></tr>
  <?php $ada = false; while ($l = mysqli_fetch_assoc($tersimpan)): $ada = true; ?>
  <tr>
    <td><?= e($l['periode']) ?></td>
    <td><?= (int)$l['total_transaksi'] ?></td>
    <td><?= rupiah($l['total_pendapatan']) ?></td>
    <td><?= e($l['nama']) ?></td>
    <td><?= e(date('d/m/Y H:i', strtotime($l['tanggal_dibuat']))) ?></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$ada): ?><tr><td colspan="5">Belum ada laporan tersimpan.</td></tr><?php endif; ?>
</table>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
