<?php
$judul = 'Detail Pesanan';
require __DIR__ . '/_header.php';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, 'SELECT * FROM tb_pesanan WHERE id_pesanan = ? AND id_pengguna = ?');
mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idp);
mysqli_stmt_execute($q);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
if (!$p) { http_response_code(404); die('Pesanan tidak ditemukan.'); }

$q = mysqli_prepare($conn, 'SELECT d.*, l.nama_layanan, l.harga_per_kg FROM tb_detail_laundry d JOIN tb_layanan l ON l.id_layanan = d.id_layanan WHERE d.id_pesanan = ?');
mysqli_stmt_bind_param($q, 'i', $id_pesanan);
mysqli_stmt_execute($q);
$detail = mysqli_stmt_get_result($q);

$tahap = ['menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil'];
$posisi = array_search($p['status'], $tahap, true);
$sudah_timbang = false;
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

        <div class="detail-steps">
            <?php foreach ($tahap as $i => $t): ?>
                <div class="detail-step">
                    <div class="step-circle <?= $i <= $posisi ? 'active' : '' ?>">
                        <?= $i <= $posisi ? '✓' : ($i + 1) ?>
                    </div>
                    <span class="<?= $i <= $posisi ? 'step-active' : '' ?>">
                        <?= e(label_status($t)) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

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

                <?php while ($d = mysqli_fetch_assoc($detail)): 
                    if ($d['berat_aktual'] !== null) $sudah_timbang = true;
                ?>
                <tr>
                    <td><strong><?= e($d['nama_layanan']) ?></strong></td>
                    <td><?= rupiah($d['harga_per_kg']) ?></td>
                    <td><?= e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) ?> kg</td>
                    <td>
                        <?= $d['berat_aktual'] !== null
                            ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg'
                            : 'Belum ditimbang' ?>
                    </td>
                    <td><?= rupiah($d['subtotal']) ?></td>
                    <td><?= e($d['catatan'] ?: '-') ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="detail-total-panel">
        <div>
            <span class="detail-label">TOTAL <?= $sudah_timbang ? 'HARGA PASTI' : 'PERKIRAAN BIAYA' ?></span>
            <h2><?= rupiah($p['total_harga']) ?></h2>

            <?php if (!$sudah_timbang): ?>
                <p>Harga pasti dihitung setelah petugas menimbang cucian Anda.</p>
            <?php else: ?>
                <p>Berat aktual sudah dicatat oleh petugas.</p>
            <?php endif; ?>
        </div>
    </div>

    <a href="pesanan_saya.php" class="detail-back">
        ← Kembali ke Pesanan Saya
    </a>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
