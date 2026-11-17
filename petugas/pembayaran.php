
<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$judul = 'Data Pembayaran';
$idpt = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT
        b.*,
        p.id_pesanan,
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

require __DIR__ . '/_header.php';
?>

<div class="page-heading">
    <div>
        <h1>Data Pembayaran</h1>
        <p>Lihat daftar pembayaran dari pesanan yang Anda tangani.</p>
    </div>
</div>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Daftar Pembayaran</h3>
            <p>Informasi transaksi pembayaran pelanggan.</p>
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
                    <th>Pelanggan</th>
                    <th>Metode Pembayaran</th>
                    <th>Jumlah Dibayar</th>
                    <th>Status</th>
                    <th>Tanggal Pembayaran</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($hasil) > 0): ?>

                    <?php while ($b = mysqli_fetch_assoc($hasil)): ?>
                        <tr>
                            <td>
                                <strong>#<?= (int)$b['id_pesanan'] ?></strong>
                            </td>

                            <td><?= e($b['nama']) ?></td>

                            <td><?= e($b['metode_bayar']) ?></td>

                            <td>
                                <strong><?= rupiah($b['jumlah_bayar']) ?></strong>
                            </td>

                            <td>
                                <span class="badge <?= e($b['status_bayar']) ?>">
                                    <?= e($b['status_bayar'] === 'lunas'
                                        ? 'Lunas'
                                        : 'Belum Dibayar') ?>
                                </span>
                            </td>

                            <td>
                                <?= $b['tanggal_bayar']
                                    ? e(date('d/m/Y H:i', strtotime($b['tanggal_bayar'])))
                                    : '-' ?>
                            </td>

                            <td>
                                <a
                                    href="pembayaran_detail.php?id=<?= (int)$b['id_pembayaran'] ?>"
                                    class="petugas-btn-manage"
                                >
                                    Lihat Detail →
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="petugas-empty">
                            <span>💳</span>
                            <strong>Belum Ada Data Pembayaran</strong>
                            <p>Pembayaran dari pesanan yang Anda tangani akan muncul di sini.</p>
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
