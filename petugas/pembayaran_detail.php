
<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$judul = 'Detail Pembayaran';

$id_pembayaran = (int)($_GET['id'] ?? 0);
$idpt = (int)$_SESSION['id_pengguna'];

// Ambil pembayaran berdasarkan pesanan yang ditangani petugas
$q = mysqli_prepare($conn, "
    SELECT
        b.*,
        p.id_pesanan,
        p.total_harga,
        p.status AS status_pesanan,
        p.tanggal_pesan,
        u.nama,
        u.no_telepon
    FROM tb_pembayaran b
    JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan
    JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna
    WHERE b.id_pembayaran = ?
    AND p.id_petugas = ?
");

mysqli_stmt_bind_param($q, 'ii', $id_pembayaran, $idpt);
mysqli_stmt_execute($q);

$b = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

if (!$b) {
    http_response_code(404);
    die('Data pembayaran tidak ditemukan atau bukan pesanan Anda.');
}

require __DIR__ . '/_header.php';
?>

<div class="page-heading">
    <div>
        <h1>Detail Pembayaran</h1>
        <p>Informasi transaksi pembayaran pesanan #<?= (int)$b['id_pesanan'] ?>.</p>
    </div>

    <a href="pembayaran.php" class="petugas-back-link">
        ← Kembali
    </a>
</div>

<!-- INFORMASI PEMBAYARAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Informasi Pembayaran</h3>
            <p>Rincian transaksi pembayaran pelanggan.</p>
        </div>

        <span class="badge <?= e($b['status_bayar']) ?>">
            <?= e($b['status_bayar'] === 'lunas'
                ? 'Lunas'
                : 'Belum Dibayar') ?>
        </span>
    </div>

    <div class="petugas-info-grid">

        <div class="petugas-info-item">
            <span>ID Pembayaran</span>
            <strong>#<?= (int)$b['id_pembayaran'] ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nomor Pesanan</span>
            <strong>#<?= (int)$b['id_pesanan'] ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nama Pelanggan</span>
            <strong><?= e($b['nama']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nomor Telepon</span>
            <strong><?= e($b['no_telepon']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Metode Pembayaran</span>
            <strong><?= e($b['metode_bayar']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Tanggal Pembayaran</span>
            <strong>
                <?= $b['tanggal_bayar']
                    ? e(date('d/m/Y H:i', strtotime($b['tanggal_bayar'])))
                    : '-' ?>
            </strong>
        </div>

        <div class="petugas-info-item">
            <span>Tanggal Pemesanan</span>
            <strong>
                <?= $b['tanggal_pesan']
                    ? e(date('d/m/Y H:i', strtotime($b['tanggal_pesan'])))
                    : '-' ?>
            </strong>
        </div>

        <div class="petugas-info-item">
            <span>Status Pesanan</span>
            <strong><?= e(label_status($b['status_pesanan'])) ?></strong>
        </div>

    </div>

    <div class="petugas-total-box">
        <div>
            <span>Jumlah Dibayar</span>
            <strong><?= rupiah($b['jumlah_bayar']) ?></strong>
        </div>
    </div>

</div>

<!-- BUKTI PEMBAYARAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Bukti Pembayaran</h3>
            <p>Bukti transaksi yang diunggah oleh pelanggan.</p>
        </div>
    </div>

    <?php if (!empty($b['bukti_bayar'])): ?>

        <div class="petugas-info-item">
            <span>File Bukti Pembayaran</span>

            <strong>
                <?= e(basename($b['bukti_bayar'])) ?>
            </strong>
        </div>

        <p class="petugas-bottom-link">
            <a
                href="<?= BASE_URL . '/' . e($b['bukti_bayar']) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                Lihat Bukti Pembayaran →
            </a>
        </p>

    <?php else: ?>

        <div class="petugas-alert alert-warning">
            Pembayaran ini tidak memiliki file bukti yang diunggah.
            Hal ini dapat terjadi pada pembayaran tunai.
        </div>

    <?php endif; ?>

</div>

<p class="petugas-bottom-link">
    <a href="pembayaran.php">← Kembali ke Data Pembayaran</a>
</p>

<?php require __DIR__ . '/_footer.php'; ?>
