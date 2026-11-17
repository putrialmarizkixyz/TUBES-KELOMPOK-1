<?php
$judul = 'Riwayat Pesanan';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT p.*, u.nama
    FROM tb_pesanan p
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE p.id_petugas = ?
    AND p.status IN ('selesai', 'siap_diambil', 'diambil')
    ORDER BY p.tanggal_pesan DESC
");

mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>

<div class="page-heading">
    <div>
        <h1>Riwayat Pesanan</h1>
        <p>Lihat daftar pesanan yang telah selesai diproses, siap diambil, atau sudah diambil pelanggan.</p>
    </div>
</div>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Daftar Riwayat Pesanan</h3>
            <p>Pesanan yang pernah Anda tangani.</p>
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
                    <th>Status</th>
                    <th>Tanggal Pesanan</th>
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
                                <?= $p['tanggal_pesan']
                                    ? e(date('d/m/Y H:i', strtotime($p['tanggal_pesan'])))
                                    : '-' ?>
                            </td>

                            <td>
                                <strong><?= rupiah($p['total_harga']) ?></strong>
                            </td>

                            <td>
                                <a href="riwayat_detail.php?id=<?= (int)$p['id_pesanan'] ?>"
                                class="petugas-btn-manage">
                                    Lihat Detail →
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="petugas-empty">
                            <span>📋</span>
                            <strong>Belum Ada Riwayat Pesanan</strong>
                            <p>Pesanan yang telah selesai diproses atau sudah diambil pelanggan akan muncul di sini.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
