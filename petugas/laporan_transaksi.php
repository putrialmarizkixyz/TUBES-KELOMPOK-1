<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$judul = 'Laporan Transaksi';
$idpt = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT
        b.*,
        p.id_pesanan,
        p.tanggal_pesan,
        p.status AS status_pesanan,
        u.nama
    FROM tb_pembayaran b
    JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE p.id_petugas = ?
    ORDER BY b.tanggal_bayar DESC, b.id_pembayaran DESC
");

mysqli_stmt_bind_param($q, 'i', $idpt);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);

$q_ringkasan = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS jumlah_transaksi,
        SUM(CASE WHEN b.status_bayar = 'lunas'
            THEN 1 ELSE 0 END) AS transaksi_lunas,
        SUM(CASE WHEN b.status_bayar = 'lunas'
            THEN b.jumlah_bayar ELSE 0 END) AS total_pendapatan
    FROM tb_pembayaran b
    JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan
    WHERE p.id_petugas = ?
");

mysqli_stmt_bind_param($q_ringkasan, 'i', $idpt);
mysqli_stmt_execute($q_ringkasan);

$ringkasan = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_ringkasan)
);

require __DIR__ . '/_header.php';
?>

<div class="page-heading">
    <div>
        <h1>Laporan Transaksi</h1>
        <p>Rekap pembayaran dari pesanan yang Anda tangani.</p>
    </div>

    <button type="button" class="laporan-print-btn" onclick="window.print()">
        <span>🖨</span>
        Cetak Laporan
    </button>
</div>

<div class="laporan-print-header">
    <h1>LAUNDRYKU</h1>
    <h2>LAPORAN TRANSAKSI PEMBAYARAN</h2>
    <p>Sistem Informasi Manajemen Laundry</p>
    <hr>
    <div class="laporan-print-info">
        <span>Petugas: <?= e($_SESSION['nama']) ?></span>
        <span>Tanggal Cetak: <?= date('d/m/Y H:i') ?></span>
    </div>
</div>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Ringkasan Transaksi</h3>
            <p>Informasi keseluruhan pembayaran.</p>
        </div>
    </div>

    <div class="petugas-report-summary">

        <div class="petugas-report-item">
            <span>Total Transaksi</span>
            <strong><?= (int)$ringkasan['jumlah_transaksi'] ?></strong>
        </div>

        <div class="petugas-report-item">
            <span>Transaksi Lunas</span>
            <strong><?= (int)$ringkasan['transaksi_lunas'] ?></strong>
        </div>

        <div class="petugas-report-item">
            <span>Total Pendapatan</span>
            <strong><?= rupiah($ringkasan['total_pendapatan'] ?? 0) ?></strong>
        </div>

    </div>
</div>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Rincian Transaksi</h3>
            <p>Daftar pembayaran dari seluruh pesanan yang ditangani.</p>
        </div>

        <span class="petugas-count">
            <?= mysqli_num_rows($hasil) ?> Transaksi
        </span>
    </div>

    <div class="tbl-wrap">
        <table class="petugas-table">

            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Metode Pembayaran</th>
                    <th>Jumlah Dibayar</th>
                    <th>Status Pembayaran</th>
                    <th>Tanggal Pembayaran</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($hasil) > 0): ?>

                    <?php while ($b = mysqli_fetch_assoc($hasil)): ?>

                        <tr>
                            <td>
                                <strong>#<?= (int)$b['id_pesanan'] ?></strong>
                            </td>

                            <td>
                                <?= e(date(
                                    'd/m/Y',
                                    strtotime($b['tanggal_pesan'])
                                )) ?>
                            </td>

                            <td><?= e($b['nama']) ?></td>

                            <td><?= e($b['metode_bayar']) ?></td>

                            <td>
                                <strong><?= rupiah($b['jumlah_bayar']) ?></strong>
                            </td>

                            <td>
                                <span class="badge <?= e($b['status_bayar']) ?>">
                                    <?= e(label_bayar($b['status_bayar'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= $b['tanggal_bayar']
                                    ? e(date(
                                        'd/m/Y H:i',
                                        strtotime($b['tanggal_bayar'])
                                    ))
                                    : '-' ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="petugas-empty">
                            <span>📊</span>
                            <strong>Belum Ada Transaksi</strong>
                            <p>Data transaksi akan muncul setelah terdapat pembayaran.</p>
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
