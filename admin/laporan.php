
<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['admin']);
$judul = 'Laporan';


$jenis = $_GET['jenis'] ?? $_POST['jenis'] ?? 'bulanan';

if (!in_array($jenis, ['harian', 'mingguan', 'bulanan'], true)) {
    $jenis = 'bulanan';
}

$tanggal_input = $_GET['tanggal'] ?? $_POST['tanggal'] ?? date('Y-m-d');
$bulan_input = $_GET['bulan'] ?? $_POST['bulan'] ?? date('Y-m');

$valid_tanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_input);
if ($valid_tanggal) {
    $valid_tanggal = checkdate(
        (int)substr($tanggal_input, 5, 2),
        (int)substr($tanggal_input, 8, 2),
        (int)substr($tanggal_input, 0, 4)
    );
}

$valid_bulan = preg_match('/^\d{4}-\d{2}$/', $bulan_input);
if ($valid_bulan) {
    $valid_bulan = checkdate(
        (int)substr($bulan_input, 5, 2),
        1,
        (int)substr($bulan_input, 0, 4)
    );
}

if (!$valid_tanggal) $tanggal_input = date('Y-m-d');
if (!$valid_bulan) $bulan_input = date('Y-m');

if ($jenis === 'harian') {
    $mulai = $tanggal_input;
    $akhir = date('Y-m-d', strtotime($mulai . ' +1 day'));
    $periode_simpan = 'H-' . $tanggal_input;
    $periode_label = 'Harian - ' . date('d/m/Y', strtotime($mulai));

} elseif ($jenis === 'mingguan') {
    $tanggal = new DateTime($tanggal_input);
    $hari = (int)$tanggal->format('N');
    $tanggal->modify('-' . ($hari - 1) . ' days');

    $mulai = $tanggal->format('Y-m-d');
    $akhir = date('Y-m-d', strtotime($mulai . ' +7 days'));
    $akhir_tampil = date('Y-m-d', strtotime($akhir . ' -1 day'));

    $periode_simpan = 'W-' . $mulai;
    $periode_label = 'Mingguan - ' .
        date('d/m/Y', strtotime($mulai)) . ' s.d. ' .
        date('d/m/Y', strtotime($akhir_tampil));

} else {
    $mulai = $bulan_input . '-01';
    $akhir = date('Y-m-d', strtotime($mulai . ' +1 month'));
    $periode_simpan = $bulan_input;
    $periode_label = 'Bulanan - ' . $bulan_input;
}

function rekap($conn, $mulai, $akhir) {
    $q = mysqli_prepare($conn, "
        SELECT
            COUNT(*) AS n,
            COALESCE(SUM(jumlah_bayar), 0) AS total
        FROM tb_pembayaran
        WHERE status_bayar = 'lunas'
        AND tanggal_bayar >= ?
        AND tanggal_bayar < ?
    ");

    mysqli_stmt_bind_param($q, 'ss', $mulai, $akhir);
    mysqli_stmt_execute($q);

    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}

$ok = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $r = rekap($conn, $mulai, $akhir);

    $n = (int)$r['n'];
    $total = (float)$r['total'];
    $admin = (int)$_SESSION['id_pengguna'];

    $c = mysqli_prepare($conn, "
        SELECT id_laporan
        FROM tb_laporan
        WHERE periode = ?
        ORDER BY id_laporan DESC
        LIMIT 1
    ");

    mysqli_stmt_bind_param($c, 's', $periode_simpan);
    mysqli_stmt_execute($c);
    $ada_laporan = mysqli_fetch_assoc(mysqli_stmt_get_result($c));

    if ($ada_laporan) {
        $idl = (int)$ada_laporan['id_laporan'];

        $s = mysqli_prepare($conn, "
            UPDATE tb_laporan
            SET total_transaksi = ?,
                total_pendapatan = ?,
                dibuat_oleh = ?,
                tanggal_dibuat = NOW()
            WHERE id_laporan = ?
        ");

        mysqli_stmt_bind_param($s, 'idii', $n, $total, $admin, $idl);
        mysqli_stmt_execute($s);

        $ok = 'Laporan ' . $periode_label . ' berhasil diperbarui.';
    } else {
        $s = mysqli_prepare($conn, "
            INSERT INTO tb_laporan
            (periode, total_transaksi, total_pendapatan, dibuat_oleh)
            VALUES (?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $s,
            'sidi',
            $periode_simpan,
            $n,
            $total,
            $admin
        );

        mysqli_stmt_execute($s);
        $ok = 'Laporan ' . $periode_label . ' berhasil disimpan.';
    }
}

$rekap = rekap($conn, $mulai, $akhir);

$q = mysqli_prepare($conn, "
    SELECT b.*, u.nama
    FROM tb_pembayaran b
    JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE b.status_bayar = 'lunas'
    AND b.tanggal_bayar >= ?
    AND b.tanggal_bayar < ?
    ORDER BY b.tanggal_bayar
");

mysqli_stmt_bind_param($q, 'ss', $mulai, $akhir);
mysqli_stmt_execute($q);
$rinci = mysqli_stmt_get_result($q);

$tersimpan = mysqli_query($conn, "
    SELECT l.*, u.nama
    FROM tb_laporan l
    JOIN tb_pengguna u ON u.id_pengguna = l.dibuat_oleh
    ORDER BY l.id_laporan DESC
");

require __DIR__ . '/_header.php';
?>

<div class="admin-report-page">

<h2>Laporan Transaksi &amp; Pendapatan</h2>

<?php if ($ok): ?>
    <div class="alert ok"><?= e($ok) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert"><?= e($error) ?></div>
<?php endif; ?>

<!-- FILTER PERIODE -->
<form method="get" class="panel">
    <label>Jenis Periode</label>

    <select name="jenis" onchange="this.form.submit()">
        <option value="harian" <?= $jenis === 'harian' ? 'selected' : '' ?>>Harian</option>
        <option value="mingguan" <?= $jenis === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
        <option value="bulanan" <?= $jenis === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
    </select>

    <?php if ($jenis === 'bulanan'): ?>
        <label>Pilih Bulan</label>
        <input type="month" name="bulan" value="<?= e($bulan_input) ?>">
    <?php else: ?>
        <label>Pilih Tanggal</label>
        <input type="date" name="tanggal" value="<?= e($tanggal_input) ?>">
    <?php endif; ?>

    <button type="submit">Tampilkan</button>
</form>

<!-- RINGKASAN -->
<div class="panel">
    <p>Periode laporan: <strong><?= e($periode_label) ?></strong></p>

    <p>
        Jumlah transaksi lunas:
        <strong><?= (int)$rekap['n'] ?></strong>
    </p>

    <p class="total">
        Total pendapatan: <?= rupiah($rekap['total']) ?>
    </p>

    <form method="post" style="margin:0">
        <input type="hidden" name="jenis" value="<?= e($jenis) ?>">
        <input type="hidden" name="tanggal" value="<?= e($tanggal_input) ?>">
        <input type="hidden" name="bulan" value="<?= e($bulan_input) ?>">

        <button type="submit">Simpan Laporan Periode Ini</button>
    </form>
</div>

<!-- RINCIAN PEMBAYARAN -->
<h3>Rincian Pembayaran Lunas</h3>

<div class="tbl-wrap">
<table>
    <tr>
        <th>Tanggal Bayar</th>
        <th>Pesanan</th>
        <th>Pelanggan</th>
        <th>Metode</th>
        <th>Jumlah</th>
    </tr>

    <?php $ada = false; while ($b = mysqli_fetch_assoc($rinci)): $ada = true; ?>
    <tr>
        <td><?= e(date('d/m/Y H:i', strtotime($b['tanggal_bayar']))) ?></td>
        <td>#<?= (int)$b['id_pesanan'] ?></td>
        <td><?= e($b['nama']) ?></td>
        <td><?= e($b['metode_bayar']) ?></td>
        <td><?= rupiah($b['jumlah_bayar']) ?></td>
    </tr>
    <?php endwhile; ?>

    <?php if (!$ada): ?>
    <tr>
        <td colspan="5">Belum ada pembayaran pada periode ini.</td>
    </tr>
    <?php endif; ?>
</table>
</div>

<!-- LAPORAN TERSIMPAN -->
<h3>Laporan Tersimpan</h3>

<div class="tbl-wrap">
<table>
    <tr>
        <th>Periode</th>
        <th>Transaksi</th>
        <th>Pendapatan</th>
        <th>Dibuat Oleh</th>
        <th>Tanggal</th>
    </tr>

    <?php $ada = false; while ($l = mysqli_fetch_assoc($tersimpan)): $ada = true; ?>
    <tr>
        <td><?= e($l['periode']) ?></td>
        <td><?= (int)$l['total_transaksi'] ?></td>
        <td><?= rupiah($l['total_pendapatan']) ?></td>
        <td><?= e($l['nama']) ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($l['tanggal_dibuat']))) ?></td>
    </tr>
    <?php endwhile; ?>

    <?php if (!$ada): ?>
    <tr>
        <td colspan="5">Belum ada laporan tersimpan.</td>
    </tr>
    <?php endif; ?>
</table>
</div>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
