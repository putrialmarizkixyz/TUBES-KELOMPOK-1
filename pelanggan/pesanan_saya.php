<?php
$judul = 'Pesanan Saya';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];
$q = mysqli_prepare($conn, 'SELECT * FROM tb_pesanan WHERE id_pengguna = ? ORDER BY tanggal_pesan DESC');
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>

<section class="history-heading">
    <span class="welcome-label">RIWAYAT TRANSAKSI</span>
    <h1>Pesanan Saya</h1>
    <p>Pantau status dan lihat riwayat pesanan laundry Anda.</p>
</section>

<?php
$total_pesanan = 0;
$pesanan_aktif = 0;
$pesanan_selesai = 0;
?>

<?php
// Data pesanan akan dihitung saat ditampilkan di bawah.
?>

<div class="history-summary">
    <div class="history-stat">
        <span class="history-stat-icon">📋</span>
        <div>
            <small>Total Pesanan</small>
            <strong id="jumlah-total">0</strong>
        </div>
    </div>
    <div class="history-stat">
        <span class="history-stat-icon">⏳</span>
        <div>
            <small>Pesanan Aktif</small>
            <strong id="jumlah-aktif">0</strong>
        </div>
    </div>
    <div class="history-stat">
        <span class="history-stat-icon">✓</span>
        <div>
            <small>Pesanan Selesai</small>
            <strong id="jumlah-selesai">0</strong>
        </div>
    </div>
</div>

<section class="history-section">
    <div class="history-section-heading">
        <div>
            <h2>Daftar Pesanan</h2>
            <p>Informasi seluruh transaksi laundry Anda.</p>
        </div>
        <a href="pesanan_baru.php" class="history-add-button">+ Buat Pesanan</a>
    </div>

    <div class="history-list">
        <?php
        $ada = false;
        while ($p = mysqli_fetch_assoc($hasil)):
            $ada = true;
            $total_pesanan++;

            if ($p['status'] === 'selesai' || $p['status'] === 'siap_diambil') {
                $pesanan_selesai++;
            } elseif (!in_array($p['status'], ['ditolak'])) {
                $pesanan_aktif++;
            }
        ?>

        <div class="history-card">
            <div class="history-card-top">
                <div>
                    <span class="history-order-label">NOMOR PESANAN</span>
                    <h3>#<?= (int)$p['id_pesanan'] ?></h3>
                </div>

                <div class="history-status">
                    <span class="badge <?= e($p['status']) ?>">
                        <?= e(label_status($p['status'])) ?>
                    </span>

                    <?php if ($p['status'] === 'ditolak' && !empty($p['alasan_penolakan'])): ?>
                        <p class="alasan-penolakan">
                            <strong>Alasan penolakan:</strong>
                            <?= e($p['alasan_penolakan']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="history-card-info">
                <div>
                    <small>Tanggal Pesanan</small>
                    <strong><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></strong>
                </div>

                <div>
                    <small>Estimasi Selesai</small>
                    <strong>
                        <?= $p['estimasi_selesai']
                            ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai'])))
                            : '-' ?>
                    </strong>
                </div>

                <div>
                    <small>Total Biaya</small>
                    <strong class="history-price"><?= rupiah($p['total_harga']) ?></strong>
                </div>
            </div>

            <div class="history-card-bottom">
                <span>Detail transaksi dan proses laundry</span>
                <a href="pesanan_detail.php?id=<?= (int)$p['id_pesanan'] ?>">
                    Lihat Detail →
                </a>
            </div>
        </div>

        <?php endwhile; ?>

        <?php if (!$ada): ?>
        <div class="history-empty">
            <div class="empty-icon">📦</div>
            <h3>Belum Ada Pesanan</h3>
            <p>Anda belum memiliki transaksi laundry. Silakan buat pesanan pertama Anda.</p>
            <a href="pesanan_baru.php" class="history-add-button">Buat Pesanan Sekarang</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.getElementById('jumlah-total').textContent = <?= $total_pesanan ?>;
document.getElementById('jumlah-aktif').textContent = <?= $pesanan_aktif ?>;
document.getElementById('jumlah-selesai').textContent = <?= $pesanan_selesai ?>;
</script>

<?php require __DIR__ . '/_footer.php'; ?>

