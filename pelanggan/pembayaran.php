<?php
$judul = 'Pembayaran';
require __DIR__ . '/_header.php';

$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT
        p.id_pesanan,
        p.tanggal_pesan,
        p.total_harga,
        b.id_pembayaran,
        b.metode_bayar,
        b.jumlah_bayar,
        b.status_bayar,
        b.tanggal_bayar,
        b.bukti_bayar
    FROM tb_pesanan p
    LEFT JOIN tb_pembayaran b
        ON b.id_pesanan = p.id_pesanan
    WHERE p.id_pengguna = ?
    ORDER BY p.tanggal_pesan DESC, b.id_pembayaran DESC
");

mysqli_stmt_bind_param($q, 'i', $idp);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>

<section class="payment-page">

    <div class="page-heading">
        <div>
            <h1>Pembayaran</h1>
            <p>Informasi tagihan dan riwayat pembayaran pesanan Anda.</p>
        </div>
    </div>

    <div class="payment-info">
        <div class="payment-info-icon">💳</div>
        <div>
            <h3>Informasi Pembayaran</h3>
            <p>
                Periksa jumlah tagihan dan status pembayaran
                pada setiap pesanan Anda.
            </p>
        </div>
    </div>

    <div class="payment-list">
        <?php $ada = false; ?>

        <?php while ($b = mysqli_fetch_assoc($hasil)): $ada = true; ?>

            <div class="payment-card">

                <div class="payment-card-header">
                    <div>
                        <span>Nomor Pesanan</span>
                        <h3>#<?= (int)$b['id_pesanan'] ?></h3>
                    </div>

                    <?php if ($b['id_pembayaran']): ?>
                        <span class="payment-status <?= e($b['status_bayar']) ?>">
                            <?= e(label_bayar($b['status_bayar'])) ?>
                        </span>
                    <?php else: ?>
                        <span class="payment-status belum_bayar">
                            Belum Ada Pembayaran
                        </span>
                    <?php endif; ?>
                </div>

                <div class="payment-card-body">

                    <div>
                        <span>Tanggal Pesanan</span>
                        <strong>
                            <?= e(date('d/m/Y H:i', strtotime($b['tanggal_pesan']))) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Metode Pembayaran</span>
                        <strong>
                            <?= e($b['metode_bayar'] ?: 'Belum ditentukan') ?>
                        </strong>
                    </div>

                    <div>
                        <span>Tanggal Pembayaran</span>
                        <strong>
                            <?= $b['tanggal_bayar']
                                ? e(date('d/m/Y H:i', strtotime($b['tanggal_bayar'])))
                                : '-' ?>
                        </strong>
                    </div>

                    <div>
                        <span>Jumlah Pembayaran</span>
                        <strong>
                            <?= $b['id_pembayaran']
                                ? rupiah($b['jumlah_bayar'])
                                : rupiah($b['total_harga']) ?>
                        </strong>
                    </div>

                </div>

                <div class="payment-total">
                    <span>Total Tagihan Pesanan</span>
                    <strong><?= rupiah($b['total_harga']) ?></strong>
                </div>

                <div class="payment-card-action">
                    <?php if (
                        !$b['id_pembayaran'] ||
                        in_array($b['status_bayar'], ['belum_bayar', 'ditolak'], true)
                    ): ?>
                        <a href="bayar.php?id=<?= (int)$b['id_pesanan'] ?>"
                        class="btn-bayar">
                            💳 Bayar Sekarang
                        </a>
                    <?php else: ?>
                        <span class="payment-paid">
                            <?= $b['status_bayar'] === 'lunas' ? '✓ Pembayaran Lunas' : '⏳ Menunggu Verifikasi Petugas' ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

        <?php endwhile; ?>

        <?php if (!$ada): ?>
            <div class="payment-empty">
                <div>💳</div>
                <h3>Belum Ada Tagihan</h3>
                <p>Tagihan pembayaran akan tampil setelah Anda membuat pesanan.</p>
                <a href="cari_laundry.php" class="btn">Buat Pesanan</a>
            </div>
        <?php endif; ?>

    </div>

    <a href="dashboard.php" class="detail-back">
        ← Kembali ke Dashboard
    </a>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
