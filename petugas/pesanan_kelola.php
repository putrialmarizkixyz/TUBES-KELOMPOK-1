<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Kelola Pesanan';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idpt = (int)$_SESSION['id_pengguna'];
$tahap = ['menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil'];

$tahap_progress = [
    'menunggu',
    'diterima',
    'dicuci',
    'dikeringkan',
    'disetrika',
    'selesai',
    'siap_diambil',
    'diambil'
];

function ambil_pesanan($conn, $id_pesanan, $idpt) {
    $q = mysqli_prepare($conn, 'SELECT p.*, u.nama, u.no_telepon FROM tb_pesanan p JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE p.id_pesanan = ? AND p.id_petugas = ?');
    mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idpt);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}
function ambil_detail($conn, $id_pesanan) {
    $q = mysqli_prepare($conn, '
        SELECT
            d.*,
            l.nama_layanan,
            l.estimasi_selesai AS jam_dasar
        FROM tb_detail_laundry d
        JOIN tb_layanan l ON l.id_layanan = d.id_layanan
        WHERE d.id_pesanan = ?
    ');

    mysqli_stmt_bind_param($q, 'i', $id_pesanan);
    mysqli_stmt_execute($q);

    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
}

function sudah_lunas($conn, $id_pesanan) {
    $q = mysqli_prepare($conn, "SELECT 1 FROM tb_pembayaran WHERE id_pesanan = ? AND status_bayar = 'lunas' LIMIT 1");
    mysqli_stmt_bind_param($q, 'i', $id_pesanan);
    mysqli_stmt_execute($q);
    return (bool)mysqli_fetch_row(mysqli_stmt_get_result($q));
}

function buat_notifikasi_status($conn, $id_pesanan, $status) {
    $daftar_pesan = [
        'diterima' => [
            'Pesanan Diterima',
            'Pesanan Anda telah diterima oleh petugas dan akan segera diproses.'
        ],
        'dicuci' => [
            'Pesanan Sedang Dicuci',
            'Pesanan Anda sedang dalam proses pencucian.'
        ],
        'dikeringkan' => [
            'Pesanan Sedang Dikeringkan',
            'Pesanan Anda sedang dalam proses pengeringan.'
        ],
        'disetrika' => [
            'Pesanan Sedang Disetrika',
            'Pesanan Anda sedang dalam proses penyetrikaan.'
        ],
        'selesai' => [
            'Pesanan Selesai Diproses',
            'Pesanan Anda telah selesai diproses.'
        ],
        'siap_diambil' => [
            'Pesanan Siap Diambil',
            'Pesanan Anda sudah siap diambil. Silakan datang ke tempat laundry.'
        ],
        'diambil' => [
            'Pesanan Telah Diambil',
            'Pesanan Anda telah diambil. Terima kasih telah menggunakan layanan LaundryKu.'
        ]
    ];

    if (!isset($daftar_pesan[$status])) {
        return;
    }

    $judul_notif = $daftar_pesan[$status][0];
    $pesan_notif = $daftar_pesan[$status][1];

    $q = mysqli_prepare($conn, "
        INSERT INTO tb_notifikasi
            (id_pengguna, id_pesanan, judul, pesan)
        SELECT id_pengguna, id_pesanan, ?, ?
        FROM tb_pesanan
        WHERE id_pesanan = ?
    ");

    mysqli_stmt_bind_param(
        $q,
        'ssi',
        $judul_notif,
        $pesan_notif,
        $id_pesanan
    );

    mysqli_stmt_execute($q);
}

$p = ambil_pesanan($conn, $id_pesanan, $idpt);
if (!$p) { http_response_code(404); die('Pesanan tidak ditemukan atau bukan pesanan Anda.'); }
$detail = ambil_detail($conn, $id_pesanan);

$error = ''; $ok = '';
if (($_GET['msg'] ?? '') === 'diterima') $ok = 'Pesanan diterima. Silakan timbang cucian dan isi berat aktual.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'timbang') {
        if ($p['status'] !== 'diterima') {
            $error = 'Berat hanya dapat diubah saat status "Diterima" (sebelum proses pencucian).';
        } elseif (sudah_lunas($conn, $id_pesanan)) {
            $error = 'Pesanan sudah dibayar lunas, sehingga berat dan harga tidak dapat diubah lagi.';
        } else {
            $berat = $_POST['berat'] ?? [];
            $baru = [];
            foreach ($detail as $d) {
                $b = str_replace(',', '.', trim($berat[$d['id_detail']] ?? ''));
                if (!is_numeric($b) || (float)$b <= 0 || (float)$b > 100) {
                    $error = 'Berat aktual untuk "' . $d['nama_layanan'] . '" harus angka antara 0,1 dan 100 kg.';
                    break;
                }
                $baru[$d['id_detail']] = round((float)$b, 2);
            }
            if (!$error) {
                try {
                    mysqli_begin_transaction($conn);
                    $total = 0; $jam_max = 0;
                    $u = mysqli_prepare($conn, 'UPDATE tb_detail_laundry SET berat_aktual = ?, subtotal = ? WHERE id_detail = ? AND id_pesanan = ?');
                    foreach ($detail as $d) {
                        $b = $baru[$d['id_detail']];
                        $sub = round($b * $d['harga_per_kg'], 2);   // harga pasti = berat aktual x harga per kg
                        $total += $sub;
                        $jam_max = max($jam_max, hitung_estimasi_jam($d['jam_dasar'], $b)); // estimasi otomatis
                        mysqli_stmt_bind_param($u, 'ddii', $b, $sub, $d['id_detail'], $id_pesanan);
                        mysqli_stmt_execute($u);
                    }
                    $estimasi = date('Y-m-d H:i:s', strtotime($p['tanggal_pesan'] . " +$jam_max hours"));
                    $t = mysqli_prepare($conn, 'UPDATE tb_pesanan SET total_harga = ?, estimasi_selesai = ? WHERE id_pesanan = ?');
                    mysqli_stmt_bind_param($t, 'dsi', $total, $estimasi, $id_pesanan);
                    mysqli_stmt_execute($t);
                    mysqli_commit($conn);
                    $ok = 'Berat aktual tersimpan. Harga pasti: ' . rupiah($total) . '. Estimasi selesai: ' . date('d/m/Y H:i', strtotime($estimasi)) . '.';
                } catch (Throwable $ex) {
                    mysqli_rollback($conn);
                    $error = 'Gagal menyimpan berat. Silakan coba lagi.';
                }
            }
        }
    }

    if ($aksi === 'konfirmasi_ambil') {

        if ($p['status'] !== 'siap_diambil') {
            $error = 'Pesanan belum siap diambil atau sudah dikonfirmasi sebelumnya.';
        } elseif (!sudah_lunas($conn, $id_pesanan)) {
            $error = 'Pesanan belum dapat diserahkan karena pembayaran belum diverifikasi lunas.';
        } else {
            $status_baru = 'diambil';

            $u = mysqli_prepare($conn, "
                UPDATE tb_pesanan
                SET status = ?
                WHERE id_pesanan = ?
                AND id_petugas = ?
                AND status = 'siap_diambil'
            ");

            mysqli_stmt_bind_param(
                $u,
                'sii',
                $status_baru,
                $id_pesanan,
                $idpt
            );

            mysqli_stmt_execute($u);

            if (mysqli_stmt_affected_rows($u) > 0) {
                buat_notifikasi_status($conn, $id_pesanan, 'diambil');
                $ok = 'Pengambilan pesanan berhasil dikonfirmasi.';
            } else {
                $error = 'Konfirmasi gagal. Status pesanan mungkin sudah berubah.';
            }
        }
    }

    if ($aksi === 'maju') {
        $pos = array_search($p['status'], $tahap, true);
        $berikut = $tahap[$pos + 1] ?? null;
        $belum = false;
        foreach ($detail as $d) if ($d['berat_aktual'] === null) $belum = true;

        if (!$berikut) $error = 'Pesanan sudah pada tahap akhir.';
        elseif ($p['status'] === 'diterima' && $belum) $error = 'Isi dan simpan berat aktual dulu sebelum memulai pencucian.';
        else {
            $u = mysqli_prepare($conn, 'UPDATE tb_pesanan SET status = ? WHERE id_pesanan = ? AND id_petugas = ? AND status = ?');
            mysqli_stmt_bind_param($u, 'siis', $berikut, $id_pesanan, $idpt, $p['status']);
            mysqli_stmt_execute($u);
            if (mysqli_stmt_affected_rows($u) > 0) {

                // Buat notifikasi untuk pelanggan
                buat_notifikasi_status($conn, $id_pesanan, $berikut);

                $ok = 'Status diperbarui menjadi: ' . label_status($berikut) . '.';

            } else {
                $error = 'Status tidak berubah karena pesanan sudah diperbarui sebelumnya. Data dimuat ulang.';
            }
        }
    }

    // muat ulang data setelah perubahan
    $p = ambil_pesanan($conn, $id_pesanan, $idpt);
    $detail = ambil_detail($conn, $id_pesanan);
}

$posisi = array_search($p['status'], $tahap_progress, true);
$posisi_tahap = array_search($p['status'], $tahap, true);
$berikut = ($posisi_tahap === false)
    ? null
    : ($tahap[$posisi_tahap + 1] ?? null);
$sudah_timbang = true;
foreach ($detail as $d) if ($d['berat_aktual'] === null) $sudah_timbang = false;
$bisa_timbang = ($p['status'] === 'diterima' && !sudah_lunas($conn, $id_pesanan));

// Mengambil informasi pembayaran terakhir
$q_bayar = mysqli_prepare($conn, "
    SELECT metode_bayar, jumlah_bayar, status_bayar, tanggal_bayar
    FROM tb_pembayaran
    WHERE id_pesanan = ?
    ORDER BY id_pembayaran DESC
    LIMIT 1
");

mysqli_stmt_bind_param($q_bayar, 'i', $id_pesanan);
mysqli_stmt_execute($q_bayar);

$bayar = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_bayar)
);

require __DIR__ . '/_header.php';
?>

<div class="page-heading">
    <div>
        <h1>Kelola Pesanan #<?= $id_pesanan ?></h1>
        <p>Periksa detail pesanan, masukkan berat aktual, dan perbarui proses laundry.</p>
    </div>
    <a href="pesanan_aktif.php" class="petugas-back-link">
    </a>
</div>

<?php if ($error): ?>
    <div class="petugas-alert alert-warning">
        ⚠️ <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($ok): ?>
    <div class="petugas-alert alert-success">
        ✓ <?= e($ok) ?>
    </div>
<?php endif; ?>

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
            <span>Nama Pelanggan</span>
            <strong><?= e($p['nama']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Nomor Telepon</span>
            <strong><?= e($p['no_telepon']) ?></strong>
        </div>

        <div class="petugas-info-item">
            <span>Tanggal Pemesanan</span>
            <strong><?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?></strong>
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

<!-- PROGRESS PENGERJAAN -->
<div class="petugas-content-card">
    <div class="petugas-card-heading">
        <div>
            <h3>Progres Pengerjaan</h3>
            <p>Pantau tahapan proses laundry pesanan ini.</p>
        </div>
    </div>

    <div class="petugas-progress">
        <?php foreach ($tahap_progress as $i => $t): ?>
            <div class="petugas-progress-step <?= $i <= $posisi ? 'done' : '' ?>">
                <div class="petugas-progress-circle">
                    <?= $i < $posisi ? '✓' : ($i + 1) ?>
                </div>
                <span><?= e(label_status($t)) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- DETAIL LAYANAN DAN BERAT -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Detail Layanan Laundry</h3>
            <p>Masukkan berat aktual setelah cucian ditimbang.</p>
        </div>
    </div>

    <form method="post">
        <input type="hidden" name="aksi" value="timbang">

        <div class="tbl-wrap">
            <table class="petugas-table petugas-detail-table">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Harga/kg</th>
                        <th>Berat Perkiraan</th>
                        <th>Berat Aktual (kg)</th>
                        <th>Subtotal</th>
                        <th>Catatan</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><strong><?= e($d['nama_layanan']) ?></strong></td>

                        <td><?= rupiah($d['harga_per_kg']) ?></td>

                        <td>
                            <?= e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) ?> kg
                        </td>

                        <td>
                            <?php if ($bisa_timbang): ?>
                                <input
                                    class="petugas-weight-input"
                                    type="number"
                                    name="berat[<?= (int)$d['id_detail'] ?>]"
                                    step="0.1"
                                    min="0.1"
                                    max="100"
                                    required
                                    value="<?= $d['berat_aktual'] !== null
                                        ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.'))
                                        : '' ?>"
                                    placeholder="0.0">
                            <?php else: ?>
                                <?= $d['berat_aktual'] !== null
                                    ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg'
                                    : '-' ?>
                            <?php endif; ?>
                        </td>

                        <td><strong><?= rupiah($d['subtotal']) ?></strong></td>

                        <td><?= e($d['catatan'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="petugas-total-box">
            <div>
                <span><?= $sudah_timbang ? 'Harga Pasti' : 'Perkiraan Total' ?></span>
                <strong><?= rupiah($p['total_harga']) ?></strong>
            </div>
        </div>

        <?php if ($bisa_timbang): ?>
            <div class="petugas-form-actions">
                <button type="submit" class="petugas-btn-save">
                    Simpan Berat Aktual
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>


<!-- STRUK PESANAN PETUGAS -->
<div class="petugas-content-card petugas-receipt-wrap">

    <div class="petugas-card-heading">
        <div>
            <h3>Struk Pesanan</h3>
            <p>Rincian transaksi pesanan pelanggan.</p>
        </div>

        <button
            type="button"
            class="petugas-receipt-print"
            onclick="window.print()">
            🖨 Cetak Struk
        </button>
    </div>

    <div class="petugas-receipt">

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
                <span>Nama Pelanggan</span>
                <strong><?= e($p['nama']) ?></strong>
            </div>

            <div>
                <span>Nomor Telepon</span>
                <strong><?= e($p['no_telepon']) ?></strong>
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

        <?php foreach ($detail as $d): ?>
            <div class="receipt-item">
                <div>
                    <strong><?= e($d['nama_layanan']) ?></strong>

                    <small>
                        <?= $d['berat_aktual'] !== null
                            ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg'
                            : e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) . ' kg (perkiraan)' ?>
                    </small>

                    <small>
                        <?= rupiah($d['harga_per_kg']) ?>/kg
                    </small>
                </div>

                <strong><?= rupiah($d['subtotal']) ?></strong>
            </div>
        <?php endforeach; ?>

        <div class="receipt-divider"></div>

        <div class="receipt-total">
            <span><?= $sudah_timbang ? 'Total Harga' : 'Total Perkiraan' ?></span>
            <strong><?= rupiah($p['total_harga']) ?></strong>
        </div>

        <div class="receipt-divider"></div>

        <h3>Informasi Pembayaran</h3>

        <div class="receipt-info">
            <div>
                <span>Metode Pembayaran</span>
                <strong>
                    <?= $bayar
                        ? e($bayar['metode_bayar'])
                        : 'Belum Ada Pembayaran' ?>
                </strong>
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
            <p>Harap simpan struk ini sebagai bukti rincian pesanan.</p>
        </div>

    </div>
</div>

<!-- TOMBOL PERUBAHAN STATUS -->
<?php if ($p['status'] === 'siap_diambil'): ?>

    <div class="petugas-next-box">
        <div>
            <h3>Konfirmasi Pengambilan</h3>
            <p>
                Pastikan pelanggan sudah menerima cucian sebelum melakukan konfirmasi.
            </p>
        </div>

        <form method="post"
              onsubmit="return confirm('Apakah pelanggan benar-benar sudah mengambil cucian ini?');">
            <input type="hidden" name="aksi" value="konfirmasi_ambil">

            <button type="submit" class="petugas-btn-next">
                Konfirmasi Pengambilan →
            </button>
        </form>
    </div>

<?php elseif ($berikut && $p['status'] !== 'diambil'): ?>

    <div class="petugas-next-box">
        <div>
            <h3>Lanjutkan Pengerjaan</h3>
            <p>
                Tahap berikutnya:
                <strong><?= e(label_status($berikut)) ?></strong>
            </p>
        </div>

        <form method="post">
            <input type="hidden" name="aksi" value="maju">

            <button type="submit" class="petugas-btn-next">
                <?= $berikut === 'siap_diambil'
                    ? 'Konfirmasi Siap Diambil →'
                    : 'Lanjut ke Tahap Berikutnya →' ?>
            </button>
        </form>
    </div>

<?php elseif ($p['status'] === 'diambil'): ?>

    <div class="petugas-alert alert-success">
        ✓ Pesanan sudah diambil oleh pelanggan.
    </div>

<?php endif; ?>

<p class="petugas-bottom-link">
    <a href="pesanan_aktif.php">← Kembali ke Pesanan Aktif</a>
</p>
<?php require __DIR__ . '/_footer.php'; ?>