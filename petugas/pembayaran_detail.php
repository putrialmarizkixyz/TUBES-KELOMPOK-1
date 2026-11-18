
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

// ===== VERIFIKASI PEMBAYARAN OLEH PETUGAS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $b['status_bayar'] === 'menunggu_verifikasi') {
    $aksi = $_POST['aksi'] ?? '';
    $id_psn = (int)$b['id_pesanan'];
    $hasil_msg = '';

    if ($aksi === 'acc') {
        $u = mysqli_prepare($conn, "UPDATE tb_pembayaran SET status_bayar = 'lunas', catatan_verifikasi = NULL WHERE id_pembayaran = ? AND status_bayar = 'menunggu_verifikasi'");
        mysqli_stmt_bind_param($u, 'i', $id_pembayaran);
        mysqli_stmt_execute($u);
        if (mysqli_stmt_affected_rows($u) > 0) {
            $judul_notif = 'Pembayaran Diverifikasi';
            $pesan_notif = 'Pembayaran pesanan #' . $id_psn . ' telah diverifikasi petugas dan dinyatakan lunas.';
            $hasil_msg = 'acc';
        }
    } elseif ($aksi === 'tolak') {
        $alasan = trim($_POST['alasan'] ?? '');
        if ($alasan === '') {
            header('Location: pembayaran_detail.php?id=' . $id_pembayaran . '&msg=alasan');
            exit;
        }
        $alasan = mb_substr($alasan, 0, 255);
        $u = mysqli_prepare($conn, "UPDATE tb_pembayaran SET status_bayar = 'ditolak', catatan_verifikasi = ? WHERE id_pembayaran = ? AND status_bayar = 'menunggu_verifikasi'");
        mysqli_stmt_bind_param($u, 'si', $alasan, $id_pembayaran);
        mysqli_stmt_execute($u);
        if (mysqli_stmt_affected_rows($u) > 0) {
            $judul_notif = 'Pembayaran Ditolak';
            $pesan_notif = 'Pembayaran pesanan #' . $id_psn . ' ditolak petugas. Alasan: ' . $alasan . '. Silakan kirim ulang bukti pembayaran.';
            $hasil_msg = 'tolak';
        }
    }

    if ($hasil_msg !== '') {
        $n = mysqli_prepare($conn, "INSERT INTO tb_notifikasi (id_pengguna, id_pesanan, judul, pesan) SELECT id_pengguna, id_pesanan, ?, ? FROM tb_pesanan WHERE id_pesanan = ?");
        mysqli_stmt_bind_param($n, 'ssi', $judul_notif, $pesan_notif, $id_psn);
        mysqli_stmt_execute($n);
    }
    header('Location: pembayaran_detail.php?id=' . $id_pembayaran . '&msg=' . ($hasil_msg ?: 'gagal'));
    exit;
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
            <?= e(label_bayar($b['status_bayar'])) ?>
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

<!-- VERIFIKASI PEMBAYARAN -->
<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Verifikasi Pembayaran</h3>
            <p>Periksa bukti pembayaran, lalu terima atau tolak.</p>
        </div>
    </div>

    <?php $msg = $_GET['msg'] ?? ''; ?>
    <?php if ($msg === 'acc'): ?><div class="alert ok">Pembayaran diverifikasi dan dinyatakan lunas.</div><?php endif; ?>
    <?php if ($msg === 'tolak'): ?><div class="alert">Pembayaran ditolak. Pelanggan diminta mengirim ulang bukti.</div><?php endif; ?>
    <?php if ($msg === 'alasan'): ?><div class="alert">Alasan penolakan wajib diisi.</div><?php endif; ?>
    <?php if ($msg === 'gagal'): ?><div class="alert">Status pembayaran sudah berubah sebelumnya.</div><?php endif; ?>

    <?php if ($b['status_bayar'] === 'menunggu_verifikasi'): ?>
        <form method="post" style="margin-bottom:14px">
            <input type="hidden" name="aksi" value="acc">
            <button type="submit" onclick="return confirm('Terima pembayaran ini sebagai LUNAS?')">&#10003; Terima (ACC) Pembayaran</button>
        </form>
        <form method="post">
            <input type="hidden" name="aksi" value="tolak">
            <label>Alasan penolakan</label>
            <input name="alasan" maxlength="255" placeholder="Contoh: nominal tidak sesuai / bukti tidak jelas">
            <button type="submit" onclick="return confirm('Tolak pembayaran ini?')">&#10005; Tolak Pembayaran</button>
        </form>
    <?php elseif ($b['status_bayar'] === 'lunas'): ?>
        <p>Pembayaran ini sudah diverifikasi dan dinyatakan <strong>lunas</strong>.</p>
    <?php elseif ($b['status_bayar'] === 'ditolak'): ?>
        <p>Pembayaran ini <strong>ditolak</strong>. Alasan: <?= e($b['catatan_verifikasi'] ?? '-') ?></p>
    <?php endif; ?>

</div>

<p class="petugas-bottom-link">
    <a href="pembayaran.php">← Kembali ke Data Pembayaran</a>
</p>

<?php require __DIR__ . '/_footer.php'; ?>
