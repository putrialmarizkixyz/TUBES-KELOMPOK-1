
<?php
$judul = 'Detail Pesanan';
require __DIR__ . '/_header.php';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, '
    SELECT * FROM tb_pesanan
    WHERE id_pesanan = ? AND id_pengguna = ?
');
mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idp);
mysqli_stmt_execute($q);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

if (!$p) {
    http_response_code(404);
    die('Pesanan tidak ditemukan.');
}

$ditolak = ($p['status'] === 'ditolak');

// Mengambil rincian layanan
$q = mysqli_prepare($conn, '
    SELECT d.*, l.nama_layanan
    FROM tb_detail_laundry d
    JOIN tb_layanan l ON l.id_layanan = d.id_layanan
    WHERE d.id_pesanan = ?
');
mysqli_stmt_bind_param($q, 'i', $id_pesanan);
mysqli_stmt_execute($q);
$hasil_detail = mysqli_stmt_get_result($q);

$rincian = [];
$sudah_timbang = true;

while ($d = mysqli_fetch_assoc($hasil_detail)) {
    $rincian[] = $d;

    if ($d['berat_aktual'] === null) {
        $sudah_timbang = false;
    }
}

if (count($rincian) === 0) {
    $sudah_timbang = false;
}

// Mengambil informasi pembayaran
$q_bayar = mysqli_prepare($conn, '
    SELECT metode_bayar, jumlah_bayar, status_bayar, tanggal_bayar
    FROM tb_pembayaran
    WHERE id_pesanan = ?
    ORDER BY id_pembayaran DESC
    LIMIT 1
');
mysqli_stmt_bind_param($q_bayar, 'i', $id_pesanan);
mysqli_stmt_execute($q_bayar);
$bayar = mysqli_fetch_assoc(mysqli_stmt_get_result($q_bayar));

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
?>

<section class="detail-page">

    <?php if (!empty($_GET['baru'])): ?>
        <div class="alert ok">
            Pesanan berhasil dibuat. Nomor pesanan Anda:
            <strong>#<?= $id_pesanan ?></strong>.
            Menunggu konfirmasi petugas.
        </div>
    <?php endif; ?>

    <div class="detail-heading">
        <div>
            <span class="detail-label">INFORMASI PESANAN</span>
            <h1>Detail Pesanan #<?= $id_pesanan ?></h1>
            <p>Pantau perkembangan dan rincian laundry Anda.</p>
        </div>
        <div class="detail-icon">🧺</div>
    </div>

    <div class="detail-panel">

        <div class="detail-section-heading">
            <h2>Status Pesanan</h2>
            <span class="badge <?= e($p['status']) ?>">
                <?= e(label_status($p['status'])) ?>
            </span>
        </div>

        <?php if ($ditolak): ?>
            <div class="detail-rejected-alert">
                <strong>Pesanan Ditolak</strong>
                <p>Mohon maaf, pesanan ini tidak dapat diproses oleh petugas.</p>

                <?php if (!empty($p['alasan_penolakan'])): ?>
                    <div class="detail-rejected-reason">
                        <strong>Alasan Penolakan:</strong>
                        <p><?= e($p['alasan_penolakan']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <div class="detail-steps">
                <?php foreach ($tahap as $i => $t): ?>
                    <div class="detail-step">
                        <div class="step-circle <?= $posisi !== false && $i <= $posisi ? 'active' : '' ?>">
                            <?= $posisi !== false && $i <= $posisi ? '✓' : ($i + 1) ?>
                        </div>
                        <span class="<?= $posisi !== false && $i <= $posisi ? 'step-active' : '' ?>">
                            <?= e(label_status($t)) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

        <div class="detail-dates">
            <div>
                <small>Tanggal pemesanan</small>
                <strong>
                    <?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?>
                </strong>
            </div>

            <div>
                <small>Estimasi selesai</small>
                <strong>
                    <?= $p['estimasi_selesai']
                        ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai'])))
                        : 'Belum ditentukan' ?>
                </strong>
            </div>
        </div>

    </div>

    <div class="detail-panel">
        <div class="detail-section-heading">
            <div>
                <h2>Rincian Layanan</h2>
                <p>Daftar layanan dalam pesanan Anda.</p>
            </div>
        </div>

        <div class="tbl-wrap">
            <table>
                <tr>
                    <th>Layanan</th>
                    <th>Harga/kg</th>
                    <th>Berat perkiraan</th>
                    <th>Berat aktual</th>
                    <th>Subtotal</th>
                    <th>Catatan</th>
                </tr>

                <?php foreach ($rincian as $d): ?>
                    <tr>
                        <td><strong><?= e($d['nama_layanan']) ?></strong></td>
                        <td><?= rupiah($d['harga_per_kg']) ?></td>
                        <td>
                            <?= e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) ?> kg
                        </td>
                        <td>
                            <?= $d['berat_aktual'] !== null
                                ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg'
                                : 'Belum ditimbang' ?>
                        </td>
                        <td><?= rupiah($d['subtotal']) ?></td>
                        <td><?= e($d['catatan'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="detail-total-panel">
        <div>
            <span class="detail-label">
                TOTAL <?= $sudah_timbang ? 'HARGA PASTI' : 'PERKIRAAN BIAYA' ?>
            </span>
            <h2><?= rupiah($p['total_harga']) ?></h2>

            <?php if (!$sudah_timbang): ?>
                <p>Harga pasti dihitung setelah petugas menimbang cucian Anda.</p>
            <?php else: ?>
                <p>Berat aktual sudah dicatat oleh petugas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- STRUK DIGITAL -->
    <div class="digital-receipt">

        <div class="receipt-heading">
            <span class="receipt-icon">🧾</span>
            <div>
                <h2>Struk Digital</h2>
                <p>Bukti rincian pesanan LaundryKu.</p>
            </div>
        </div>

        <div class="receipt-brand">
            <h2>LAUNDRYKU</h2>
            <p>Struk Pesanan Laundry</p>
        </div>

        <div class="receipt-divider"></div>

        <div class="receipt-info">
            <div>
                <span>Nomor Pesanan</span>
                <strong>#<?= $id_pesanan ?></strong>
            </div>

            <div>
                <span>Tanggal Pesanan</span>
                <strong><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></strong>
            </div>

            <div>
                <span>Status Pesanan</span>
                <strong><?= e(label_status($p['status'])) ?></strong>
            </div>
        </div>

        <div class="receipt-divider"></div>

        <h3>Rincian Layanan</h3>

        <?php foreach ($rincian as $d): ?>
            <div class="receipt-item">
                <div>
                    <strong><?= e($d['nama_layanan']) ?></strong>
                    <small>
                        <?= $d['berat_aktual'] !== null
                            ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg'
                            : e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) . ' kg (perkiraan)' ?>
                    </small>
                </div>
                <strong><?= rupiah($d['subtotal']) ?></strong>
            </div>
        <?php endforeach; ?>

        <div class="receipt-divider"></div>

        <div class="receipt-total">
            <span><?= $sudah_timbang ? 'Total Pembayaran' : 'Total Perkiraan' ?></span>
            <strong><?= rupiah($p['total_harga']) ?></strong>
        </div>

        <div class="receipt-divider"></div>

        <h3>Informasi Pembayaran</h3>

        <div class="receipt-info">
            <div>
                <span>Metode Pembayaran</span>
                <strong><?= $bayar ? e($bayar['metode_bayar']) : 'Belum melakukan pembayaran' ?></strong>
            </div>

            <div>
                <span>Status Pembayaran</span>
                <strong>
                    <?= $bayar
                        ? e(label_bayar($bayar['status_bayar']))
                        : 'Belum Dibayar' ?>
                </strong>
            </div>

            <div>
                <span>Tanggal Pembayaran</span>
                <strong>
                    <?= !empty($bayar['tanggal_bayar'])
                        ? e(date('d/m/Y H:i', strtotime($bayar['tanggal_bayar'])))
                        : '-' ?>
                </strong>
            </div>
        </div>

        <div class="receipt-footer">
            <strong>Terima kasih telah menggunakan LaundryKu!</strong>
            <p>Struk digital ini dapat dilihat kembali melalui halaman detail pesanan.</p>
        </div>

    </div>

    <a href="pesanan_saya.php" class="detail-back">
        ← Kembali ke Pesanan Saya
    </a>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
