<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
$judul = 'Pembayaran';

$id_pesanan = (int)($_GET['id'] ?? $_POST['id_pesanan'] ?? 0);
$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, 'SELECT * FROM tb_pesanan WHERE id_pesanan = ? AND id_pengguna = ?');
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
        header('Location: bayar.php?id=' . $id_pesanan . '&ok=1');
        exit;
    }
}

require __DIR__ . '/_header.php';
?>
<h2>Pembayaran Pesanan #<?= $id_pesanan ?></h2>
<?php if ($p['status'] === 'ditolak'): ?>
  <div class="alert">Pesanan ini telah ditolak petugas, sehingga tidak perlu dibayar.</div>
  <p><a href="pesanan_detail.php?id=<?= $id_pesanan ?>">&larr; Kembali ke detail pesanan</a></p>
<?php require __DIR__ . '/_footer.php'; exit; endif; ?>
<?php if (!empty($_GET['ok'])): ?><div class="alert ok">Pembayaran berhasil dicatat. Terima kasih!</div><?php endif; ?>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <p>Status pesanan: <span class="badge <?= e($p['status']) ?>"><?= e(label_status($p['status'])) ?></span></p>
  <p class="total"><?= $sudah_timbang ? 'Harga pasti' : 'Perkiraan total' ?>: <?= rupiah($p['total_harga']) ?></p>
</div>

<?php if ($lunas): ?>
  <div class="panel">
    <p><span class="badge lunas">Lunas</span></p>
    <p>Metode: <?= e($bayar['metode_bayar']) ?><br>
       Jumlah: <?= rupiah($bayar['jumlah_bayar']) ?><br>
       Tanggal: <?= e(date('d/m/Y H:i', strtotime($bayar['tanggal_bayar']))) ?></p>
    <?php if ($bayar['bukti_bayar']): ?><p><a href="<?= BASE_URL . '/' . e($bayar['bukti_bayar']) ?>" target="_blank">Lihat bukti pembayaran</a></p><?php endif; ?>
  </div>
<?php elseif (!$sudah_timbang): ?>
  <div class="alert">Harga pasti belum tersedia. Pembayaran dapat dilakukan setelah petugas menimbang cucian Anda.</div>
<?php else: ?>
  <form method="post" enctype="multipart/form-data" class="panel">
    <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
    <label>Metode pembayaran</label>
    <select name="metode" id="metode" required>
      <option value="">- Pilih -</option>
      <option>Transfer Bank</option>
      <option>E-Wallet</option>
      <option>Tunai</option>
    </select>
    <div id="boxBukti">
      <label>Bukti pembayaran (JPG/PNG/PDF, maks. 2 MB)</label>
      <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf">
    </div>
    <button type="submit">Konfirmasi Pembayaran <?= rupiah($p['total_harga']) ?></button>
  </form>
  <script>
    const m = document.getElementById('metode'), b = document.getElementById('boxBukti');
    function tampil() { b.style.display = (m.value === 'Tunai') ? 'none' : 'block'; }
    m.addEventListener('change', tampil); tampil();
  </script>
<?php endif; ?>
<p><a href="pesanan_detail.php?id=<?= $id_pesanan ?>">&larr; Kembali ke detail pesanan</a></p>
<?php require __DIR__ . '/_footer.php'; ?>
