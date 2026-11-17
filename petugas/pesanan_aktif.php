<?php
$judul = 'Pesanan Aktif';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT p.*, u.nama
    FROM tb_pesanan p
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE p.id_petugas = ?
    AND p.status IN ('diterima', 'dicuci', 'dikeringkan', 'disetrika')
    ORDER BY p.tanggal_pesan DESC
");
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>

<div class="page-heading">
    <div>
        <h1>Pesanan Aktif</h1>
        <p>Pantau dan kelola pesanan laundry yang sedang Anda tangani.</p>
    </div>
</div>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Daftar Pesanan yang Saya Tangani</h3>
            <p>Pesanan yang telah dikonfirmasi dan ditangani oleh Anda.</p>
        </div>

        <span class="petugas-count">
            <?= mysqli_num_rows($hasil) ?> Pesanan
        </span>
    </div>

    <div class="tbl-wrap">
        <table class="petugas-table">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Status Pengerjaan</th>
                    <th>Estimasi Selesai</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($hasil) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($hasil)): ?>
                        <tr>
                            <td>
                                <strong>#<?= (int)$p['id_pesanan'] ?></strong>
                            </td>

                            <td><?= e($p['nama']) ?></td>

                            <td>
                                <span class="badge <?= e($p['status']) ?>">
                                    <?= e(label_status($p['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= $p['estimasi_selesai']
                                    ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai'])))
                                    : '-' ?>
                            </td>

                            <td>
                                <strong><?= rupiah($p['total_harga']) ?></strong>
                            </td>

                            <td>
                                <a href="pesanan_kelola.php?id=<?= (int)$p['id_pesanan'] ?>"
                                   class="petugas-btn-manage">
                                    Kelola →
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="petugas-empty">
                            <span>📋</span>
                            <strong>Belum Ada Pesanan Aktif</strong>
                            <p>Pesanan yang sudah Anda terima akan muncul di sini.</p>
                            <a href="pesanan_masuk.php" class="petugas-btn-manage">
                                Lihat Pesanan Masuk →
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
