
<?php
$judul = 'Layanan & Harga';
require __DIR__ . '/_header.php';

$hasil = mysqli_query($conn, 'SELECT * FROM tb_layanan ORDER BY harga_per_kg');
?>

<section class="page-heading">
    <span class="welcome-label">PILIHAN LAYANAN</span>
    <h1>Layanan &amp; Harga Laundry</h1>
    <p>Pilih layanan laundry sesuai kebutuhan Anda dengan harga yang terjangkau.</p>
</section>

<section class="service-section">
    <div class="section-heading">
        <div>
            <h2>Daftar Layanan</h2>
            <p>Informasi harga dan estimasi penyelesaian laundry.</p>
        </div>
    </div>

    <div class="service-grid">

        <?php while ($l = mysqli_fetch_assoc($hasil)): ?>
        <div class="service-card">

            <div class="service-card-top">
                <div class="service-icon">🧺</div>
                <span class="service-label">Layanan Laundry</span>
            </div>

            <h3><?= e($l['nama_layanan']) ?></h3>

            <div class="service-price">
                <strong><?= rupiah($l['harga_per_kg']) ?></strong>
                <span>/ kg</span>
            </div>

            <div class="service-info">
                <span>⏱️</span>
                <div>
                    <small>Estimasi selesai</small>
                    <strong><?= (int)$l['estimasi_selesai'] ?> jam</strong>
                </div>
            </div>

            <div class="service-description">
                <?= e(trim(explode(',', $l['keterangan'])[0])) ?>
            </div>

            <a href="cari_laundry.php" class="service-button">
                Pilih Layanan <span>→</span>
            </a>

        </div>
        <?php endwhile; ?>

    </div>
</section>

<div class="service-note">
    <strong>Informasi:</strong>
    Harga dihitung berdasarkan berat laundry dalam kilogram.
</div>

<?php require __DIR__ . '/_footer.php'; ?>
