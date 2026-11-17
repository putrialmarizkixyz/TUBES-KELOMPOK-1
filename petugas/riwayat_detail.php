
<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$judul = 'Detail Riwayat Pesanan';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idpt = (int)$_SESSION['id_pengguna'];

// Ambil informasi pesanan milik petugas yang sedang login
$q = mysqli_prepare($conn, "
    SELECT p.*, u.nama, u.no_telepon
    FROM tb_pesanan p
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE p.id_pesanan = ?
    AND p.id_petugas = ?
    AND p.status IN ('selesai', 'siap_diambil', 'diambil')
");

mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idpt);
mysqli_stmt_execute($q);

$p = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

if (!$p) {
    http_response_code(404);
    die('Riwayat pesanan tidak ditemukan atau bukan pesanan Anda.');
}

// Ambil detail layanan pesanan
$q_detail = mysqli_prepare($conn, "
    SELECT d.*, l.nama_layanan, l.harga_per_kg
    FROM tb_detail_laundry d
    JOIN tb_layanan l ON l.id_layanan = d.id_layanan
    WHERE d.id_pesanan = ?
");

mysqli_stmt_bind_param($q_detail, 'i', $id_pesanan);
mysqli_stmt_execute($q_detail);

$detail = mysqli_fetch_all(
    mysqli_stmt_get_result($q_detail),
    MYSQLI_ASSOC
);

// Tahapan untuk menampilkan progres
$tahap = [
    'menunggu',
    'diterima',
    'dicuci',
    'dikeringkan',
    'disetrika',
    'selesai',
    'siap_diambil',
    'diambil'
];

$posisi = array_search($p['status'], $tahap, true);

require __DIR__ . '/_header.php';
?>

<div class="page-heading">
    <div>
        <h1>Detail Riwayat Pesanan #<?= $id_pesanan ?></h1>
        <p>Lihat informasi dan rincian pesanan yang telah ditangani.</p>
    </div>

    <a href="riwayat_pesanan.php" class="petugas-back-link">
        ← Kembali
    </a>
</div>

<!-- INFORMASI PESANAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Informasi Pesanan</h3>
            <p>Informasi pelanggan dan waktu pemesanan.</p>
        </div>

        <span class="badge <?= e($p['status']) ?>">
            <?= e(label_status($p['status'])) ?>
        </span>
    </div>

    <div class="petugas-info-grid">

        <div class="petugas-info-item">
            <span>Nomor Pesanan</span>
            <strong>#<?= (int)$p['id_pesanan'] ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nama Pelanggan</span>
            <strong><?= e($p['nama']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nomor Telepon</span>
            <strong><?= e($p['no_telepon']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Tanggal Pemesanan</span>
            <strong>
                <?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?>
            </strong>
        </div>

        <div class="petugas-info-item">
            <span>Estimasi Selesai</span>
            <strong>
                <?= $p['estimasi_selesai']
                    ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai'])))
                    : 'Belum ditentukan' ?>
            </strong>
        </div>

    </div>
</div>

<!-- PROGRES PENGERJAAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Progres Pengerjaan</h3>
            <p>Riwayat tahapan proses laundry pesanan.</p>
        </div>
    </div>

    <div class="petugas-progress">
        <?php foreach ($tahap as $i => $t): ?>

            <div class="petugas-progress-step <?= $i <= $posisi ? 'done' : '' ?>">

                <div class="petugas-progress-circle">
                    <?= $i < $posisi ? '✓' : ($i + 1) ?>
                </div>

                <span><?= e(label_status($t)) ?></span>

            </div>

        <?php endforeach; ?>
    </div>

</div>

<!-- DETAIL LAYANAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Detail Layanan Laundry</h3>
            <p>Rincian layanan, berat aktual, dan biaya pesanan.</p>
        </div>
    </div>

    <div class="tbl-wrap">
        <table class="petugas-table petugas-detail-table">

            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Harga/kg</th>
                    <th>Berat Perkiraan</th>
                    <th>Berat Aktual</th>
                    <th>Subtotal</th>
                    <th>Catatan</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($detail) > 0): ?>

                    <?php foreach ($detail as $d): ?>

                        <tr>
                            <td>
                                <strong><?= e($d['nama_layanan']) ?></strong>
                            </td>

                            <td>
                                <?= rupiah($d['harga_per_kg']) ?>
                            </td>

                            <td>
                                <?= number_format((float)$d['berat_perkiraan'], 2, ',', '.') ?> kg
                            </td>

                            <td>
                                <?= $d['berat_aktual'] !== null
                                    ? number_format((float)$d['berat_aktual'], 2, ',', '.') . ' kg'
                                    : '-' ?>
                            </td>

                            <td>
                                <strong><?= rupiah($d['subtotal']) ?></strong>
                            </td>

                            <td>
                                <?= e($d['catatan'] ?: '-') ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="petugas-empty">
                            Belum ada detail layanan.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

    <div class="petugas-total-box">
        <div>
            <span>Total Pembayaran</span>
            <strong><?= rupiah($p['total_harga']) ?></strong>
        </div>
    </div>

</div>

<!-- KETERANGAN STATUS -->
<?php if ($p['status'] === 'diambil'): ?>

    <div class="petugas-alert alert-success">
        ✓ Pesanan sudah diambil oleh pelanggan.
    </div>

<?php elseif ($p['status'] === 'siap_diambil'): ?>

    <div class="petugas-alert alert-success">
        ✓ Pesanan sudah selesai diproses dan siap diambil pelanggan.
    </div>

<?php elseif ($p['status'] === 'selesai'): ?>

    <div class="petugas-alert alert-success">
        ✓ Proses pengerjaan pesanan telah selesai.
    </div>

<?php endif; ?>

<p class="petugas-bottom-link">
    <a href="riwayat_pesanan.php">← Kembali ke Riwayat Pesanan</a>
</p>

<?php require __DIR__ . '/_footer.php'; ?>
