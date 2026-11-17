<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
$judul = 'Pembayaran';

$id_pesanan = (int)($_GET['id'] ?? $_POST['id_pesanan'] ?? 0);
$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, "
    SELECT *
    FROM tb_pesanan
    WHERE id_pesanan = ?
    AND id_pengguna = ?
    AND status != 'ditolak'
");

mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idp);
mysqli_stmt_execute($q);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
if (!$p) { http_response_code(404); die('Pesanan tidak ditemukan.'); }

// Harga pasti tersedia jika semua item sudah ditimbang petugas
$q = mysqli_prepare($conn, 'SELECT COUNT(*) AS total, COALESCE(SUM(berat_aktual IS NULL),0) AS belum FROM tb_detail_laundry WHERE id_pesanan = ?');
mysqli_stmt_bind_param($q, 'i', $id_pesanan);
mysqli_stmt_execute($q);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
$sudah_timbang = ((int)$r['total'] > 0 && (int)$r['belum'] === 0);

function ambil_bayar($conn, $id_pesanan) {
    $q = mysqli_prepare($conn, 'SELECT * FROM tb_pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1');
    mysqli_stmt_bind_param($q, 'i', $id_pesanan);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}
$bayar = ambil_bayar($conn, $id_pesanan);
$lunas = ($bayar && $bayar['status_bayar'] === 'lunas');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lunas && $sudah_timbang && $p['status'] !== 'ditolak') {
    $metode = $_POST['metode'] ?? '';
    $path = null;

    if (!in_array($metode, ['Transfer Bank', 'E-Wallet', 'Tunai'], true)) {
        $error = 'Pilih metode pembayaran.';
    } elseif ($metode !== 'Tunai') {
        $f = $_FILES['bukti'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Unggah bukti pembayaran (JPG, PNG, atau PDF).';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran bukti maksimal 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
            if (!isset($ekstensi[$mime])) {
                $error = 'Format bukti harus JPG, PNG, atau PDF.';
            } else {
                $dir = __DIR__ . '/../uploads/bukti';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $nama = 'bukti_' . $id_pesanan . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi[$mime];
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $nama)) $path = 'uploads/bukti/' . $nama;
                else $error = 'Gagal menyimpan file bukti.';
            }
        }
    }

    if (!$error) {
        $now = date('Y-m-d H:i:s');
        $jumlah = (float)$p['total_harga'];
        $ins = mysqli_prepare($conn, "INSERT INTO tb_pembayaran (id_pesanan, metode_bayar, jumlah_bayar, status_bayar, tanggal_bayar, bukti_bayar) VALUES (?, ?, ?, 'lunas', ?, ?)");
        mysqli_stmt_bind_param($ins, 'isdss', $id_pesanan, $metode, $jumlah, $now, $path);
        mysqli_stmt_execute($ins);

        if (mysqli_stmt_affected_rows($ins) > 0) {

            $judul_notif = 'Pembayaran Berhasil';
            $pesan_notif = 'Pembayaran pesanan #' . $id_pesanan .
                ' telah berhasil dicatat. Terima kasih telah menggunakan LaundryKu.';

            $n = mysqli_prepare($conn, "
                INSERT INTO tb_notifikasi
                    (id_pengguna, id_pesanan, judul, pesan)
                VALUES (?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $n,
                'iiss',
                $idp,
                $id_pesanan,
                $judul_notif,
                $pesan_notif
            );

            mysqli_stmt_execute($n);

            header('Location: bayar.php?id=' . $id_pesanan . '&ok=1');
            exit;

        } else {
            $error = 'Pembayaran gagal disimpan. Silakan coba kembali.';
        }
    }
}

require __DIR__ . '/_header.php';
?>

<style>
/* Penyesuaian judul pembayaran */
.payment-title {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    margin: 8px 0;
}

.payment-title h2 {
    margin: 0;
    line-height: 1.3;
}

.payment-title span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 10px;
    border-radius: 6px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 15px;
    font-weight: 700;
    white-space: nowrap;
}

.payment-select {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 42px 12px 14px;
    background-position: right 16px center;
    cursor: pointer;
}
</style>

<section class="payment-page">

    <!-- Judul halaman -->
    <div class="payment-heading">
        <div>
            <span class="detail-label">TRANSAKSI PEMBAYARAN</span>

            <div class="payment-title">
                <h2>Pembayaran Pesanan</h2>
                <span>#<?= $id_pesanan ?></span>
            </div>

            <p>Selesaikan pembayaran laundry Anda dengan mudah dan aman.</p>
        </div>

        <div class="payment-heading-icon">💳</div>
    </div>

    <!-- Pesan pembayaran -->
    <?php if (!empty($_GET['ok'])): ?>
        <div class="payment-alert success">
            <span>✓</span>
            <div>
                <strong>Pembayaran Berhasil!</strong>
                <p>Pembayaran Anda berhasil dicatat. Terima kasih telah menggunakan LaundryKu.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="payment-alert error">
            <span>!</span>
            <div>
                <strong>Pembayaran Belum Berhasil</strong>
                <p><?= e($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($p['status'] === 'ditolak'): ?>

        <div class="payment-panel">
            <div class="payment-rejected">
                <div class="payment-status-icon">✕</div>
                <h2>Pesanan Ditolak</h2>
                <p>Pesanan ini telah ditolak oleh petugas sehingga tidak perlu melakukan pembayaran.</p>
            </div>
        </div>

    <?php else: ?>

        <!-- Informasi pesanan -->
        <div class="payment-panel">
            <div class="payment-section-heading">
                <div>
                    <h2>Informasi Pesanan</h2>
                    <p>Ringkasan status dan biaya pesanan Anda.</p>
                </div>
                <span class="badge <?= e($p['status']) ?>">
                    <?= e(label_status($p['status'])) ?>
                </span>
            </div>

            <div class="payment-info">
                <div>
                    <small>Nomor Pesanan</small>
                    <strong>#<?= $id_pesanan ?></strong>
                </div>

                <div>
                    <small>Status Pembayaran</small>
                    <strong>
                        <?php if ($lunas): ?>
                            <span class="badge lunas">Lunas</span>
                        <?php else: ?>
                            <span class="badge belum_bayar">Belum Dibayar</span>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>

            <div class="payment-total">
                <div>
                    <small><?= $sudah_timbang ? 'TOTAL HARGA PASTI' : 'PERKIRAAN TOTAL' ?></small>
                    <h2><?= rupiah($p['total_harga']) ?></h2>
                    <p>
                        <?= $sudah_timbang
                            ? 'Berat aktual cucian sudah dicatat oleh petugas.'
                            : 'Harga pasti akan tersedia setelah petugas menimbang cucian Anda.' ?>
                    </p>
                </div>
                <div class="payment-total-icon">💰</div>
            </div>
        </div>

        <!-- Kondisi pembayaran -->
        <?php if ($lunas): ?>

            <div class="payment-panel">
                <div class="payment-section-heading">
                    <div>
                        <h2>Detail Pembayaran</h2>
                        <p>Pembayaran untuk pesanan ini sudah tercatat.</p>
                    </div>
                    <span class="badge lunas">Lunas</span>
                </div>

                <div class="payment-info payment-info-detail">
                    <div>
                        <small>Metode Pembayaran</small>
                        <strong><?= e($bayar['metode_bayar']) ?></strong>
                    </div>

                    <div>
                        <small>Jumlah Dibayar</small>
                        <strong><?= rupiah($bayar['jumlah_bayar']) ?></strong>
                    </div>

                    <div>
                        <small>Tanggal Pembayaran</small>
                        <strong><?= e(date('d/m/Y H:i', strtotime($bayar['tanggal_bayar']))) ?></strong>
                    </div>
                </div>

                <?php if ($bayar['bukti_bayar']): ?>
                    <a
                        href="<?= BASE_URL . '/' . e($bayar['bukti_bayar']) ?>"
                        target="_blank"
                        class="payment-proof-link"
                    >
                        Lihat Bukti Pembayaran →
                    </a>
                <?php endif; ?>
            </div>

        <?php elseif (!$sudah_timbang): ?>

            <div class="payment-alert info">
                <span>i</span>
                <div>
                    <strong>Pembayaran Belum Tersedia</strong>
                    <p>
                        Harga pasti belum tersedia. Anda dapat melakukan pembayaran
                        setelah petugas menimbang seluruh cucian dan mencatat berat aktualnya.
                    </p>
                </div>
            </div>

        <?php else: ?>

            <!-- Formulir pembayaran -->
            <div class="payment-panel">
                <div class="payment-section-heading">
                    <div>
                        <h2>Metode Pembayaran</h2>
                        <p>Pilih metode pembayaran yang ingin Anda gunakan.</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="payment-form">

                    <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">

                    <div class="payment-field">
                        <label for="metode">Pilih Metode Pembayaran</label>
                        <select name="metode" id="metode" class="payment-select" required>
                            <option value="">-- Pilih Metode Pembayaran --</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="E-Wallet">E-Wallet</option>
                            <option value="Tunai">Tunai</option>
                        </select>
                    </div>

                    <div class="payment-field" id="boxBukti">
                         <label for="bukti">Bukti pembayaran (JPG/PNG/PDF, maks. 2 MB)</label>
                          <input
                              type="file"
                              name="bukti"
                              id="bukti"
                              accept=".jpg,.jpeg,.png,.pdf"
                          >
                    </div>

                    <div class="payment-confirmation">
                        <div>
                            <small>Total yang harus dibayar</small>
                            <strong><?= rupiah($p['total_harga']) ?></strong>
                        </div>
                    </div>

                    <button type="submit" class="payment-submit">
                        Konfirmasi Pembayaran
                        <span>→</span>
                    </button>
                </form>
            </div>
            
            <script>
                const m = document.getElementById('metode');
                const b = document.getElementById('boxBukti');
                const f = document.getElementById('bukti');

                function tampil() {
                    const tunai = m.value === 'Tunai';

                    b.style.display = tunai ? 'none' : 'block';
                    f.required = !tunai;
                }

                m.addEventListener('change', tampil);
                tampil();
            </script>

        <?php endif; ?>

    <?php endif; ?>

    <a href="pesanan_detail.php?id=<?= $id_pesanan ?>" class="payment-back">
        ← Kembali ke Detail Pesanan
    </a>

</section>

<?php require __DIR__ . '/_footer.php'; ?>