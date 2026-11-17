<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Kelola Pesanan';

$id_pesanan = (int)($_GET['id'] ?? 0);
$idpt = (int)$_SESSION['id_pengguna'];
$tahap = ['menunggu','diterima','dicuci','dikeringkan','disetrika','selesai','siap_diambil'];

function ambil_pesanan($conn, $id_pesanan, $idpt) {
    $q = mysqli_prepare($conn, 'SELECT p.*, u.nama, u.no_telepon FROM tb_pesanan p JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE p.id_pesanan = ? AND p.id_petugas = ?');
    mysqli_stmt_bind_param($q, 'ii', $id_pesanan, $idpt);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}
function ambil_detail($conn, $id_pesanan) {
    $q = mysqli_prepare($conn, 'SELECT d.*, l.nama_layanan, l.harga_per_kg, l.estimasi_selesai AS jam_dasar FROM tb_detail_laundry d JOIN tb_layanan l ON l.id_layanan = d.id_layanan WHERE d.id_pesanan = ?');
    mysqli_stmt_bind_param($q, 'i', $id_pesanan);
    mysqli_stmt_execute($q);
    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
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
                        $sub = $b * $d['harga_per_kg'];          // harga pasti = berat aktual x harga per kg
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
            $ok = 'Status diperbarui menjadi: ' . label_status($berikut) . '.';
        }
    }

    // muat ulang data setelah perubahan
    $p = ambil_pesanan($conn, $id_pesanan, $idpt);
    $detail = ambil_detail($conn, $id_pesanan);
}

$posisi = array_search($p['status'], $tahap, true);
$berikut = $tahap[$posisi + 1] ?? null;
$sudah_timbang = true;
foreach ($detail as $d) if ($d['berat_aktual'] === null) $sudah_timbang = false;
$bisa_timbang = ($p['status'] === 'diterima');

require __DIR__ . '/_header.php';
?>
<h2>Pesanan #<?= $id_pesanan ?></h2>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>

<div class="panel">
  <div class="steps">
    <?php foreach ($tahap as $i => $t): ?>
      <span class="<?= $i <= $posisi ? 'done' : '' ?>"><?= e(label_status($t)) ?></span>
    <?php endforeach; ?>
  </div>
  <p>Pelanggan: <strong><?= e($p['nama']) ?></strong> (<?= e($p['no_telepon']) ?>)<br>
     Dipesan: <?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?><br>
     Estimasi selesai: <?= $p['estimasi_selesai'] ? e(date('d/m/Y H:i', strtotime($p['estimasi_selesai']))) : '-' ?></p>
</div>

<form method="post">
  <input type="hidden" name="aksi" value="timbang">
  <div class="tbl-wrap">
  <table>
    <tr><th>Layanan</th><th>Harga/kg</th><th>Berat perkiraan</th><th>Berat aktual (kg)</th><th>Subtotal</th><th>Catatan</th></tr>
    <?php foreach ($detail as $d): ?>
    <tr>
      <td><?= e($d['nama_layanan']) ?></td>
      <td><?= rupiah($d['harga_per_kg']) ?></td>
      <td><?= e(rtrim(rtrim($d['berat_perkiraan'], '0'), '.')) ?> kg</td>
      <td>
        <?php if ($bisa_timbang): ?>
          <input type="number" name="berat[<?= (int)$d['id_detail'] ?>]" step="0.1" min="0.1" max="100" required
                 value="<?= $d['berat_aktual'] !== null ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) : '' ?>">
        <?php else: ?>
          <?= $d['berat_aktual'] !== null ? e(rtrim(rtrim($d['berat_aktual'], '0'), '.')) . ' kg' : '-' ?>
        <?php endif; ?>
      </td>
      <td><?= rupiah($d['subtotal']) ?></td>
      <td><?= e($d['catatan']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <p class="total"><?= $sudah_timbang ? 'Harga pasti' : 'Perkiraan total' ?>: <?= rupiah($p['total_harga']) ?></p>
  <?php if ($bisa_timbang): ?><button type="submit">Simpan Berat Aktual</button><?php endif; ?>
</form>

<?php if ($berikut): ?>
<form method="post">
  <input type="hidden" name="aksi" value="maju">
  <button type="submit"><?= $berikut === 'siap_diambil' ? 'Konfirmasi Siap Diambil' : 'Lanjut ke tahap: ' . e(label_status($berikut)) ?></button>
</form>
<?php else: ?>
<p class="alert ok">Laundry sudah siap diambil pelanggan.</p>
<?php endif; ?>
<p><a href="pesanan_aktif.php">&larr; Kembali ke Pesanan Saya</a></p>
<?php require __DIR__ . '/_footer.php'; ?>