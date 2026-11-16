<?php
// Proses form DULU (sebelum ada output HTML) supaya redirect header() bisa jalan.
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
$judul = 'Buat Pesanan';

$error = '';
$layanan = [];
$r = mysqli_query($conn, 'SELECT * FROM tb_layanan ORDER BY harga_per_kg');
while ($row = mysqli_fetch_assoc($r)) $layanan[(int)$row['id_layanan']] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $berat   = $_POST['berat'] ?? [];
    $catatan = $_POST['catatan'] ?? [];
    $pilih = [];   // id_layanan => [berat, catatan]

    foreach ($layanan as $idl => $l) {
        $b = str_replace(',', '.', trim($berat[$idl] ?? ''));
        if ($b === '') continue;
        if (!is_numeric($b) || (float)$b <= 0 || (float)$b > 100) {
            $error = 'Berat untuk "' . $l['nama_layanan'] . '" harus berupa angka antara 0,1 dan 100 kg.';
            break;
        }
        $pilih[$idl] = [round((float)$b, 2), trim($catatan[$idl] ?? '')];
    }
    if (!$error && !$pilih) $error = 'Pilih minimal satu layanan dengan mengisi perkiraan berat.';

    if (!$error) {
        $idp = (int)$_SESSION['id_pengguna'];
        try {
            mysqli_begin_transaction($conn);

            $total = 0; $jam_max = 0;
            foreach ($pilih as $idl => [$b, $c]) {
                $total += $b * $layanan[$idl]['harga_per_kg'];   // harga dihitung di server, bukan dari form
                $jam_max = max($jam_max, (int)$layanan[$idl]['estimasi_selesai']);
            }
            $estimasi = date('Y-m-d H:i:s', strtotime("+$jam_max hours"));
            $now = date('Y-m-d H:i:s');

            $ins = mysqli_prepare($conn, "INSERT INTO tb_pesanan (id_pengguna, tanggal_pesan, status, estimasi_selesai, total_harga) VALUES (?, ?, 'menunggu', ?, ?)");
            mysqli_stmt_bind_param($ins, 'issd', $idp, $now, $estimasi, $total);
            mysqli_stmt_execute($ins);
            $id_pesanan = mysqli_insert_id($conn);

            $det = mysqli_prepare($conn, 'INSERT INTO tb_detail_laundry (id_pesanan, id_layanan, berat_perkiraan, subtotal, catatan) VALUES (?, ?, ?, ?, ?)');
            foreach ($pilih as $idl => [$b, $c]) {
                $sub = $b * $layanan[$idl]['harga_per_kg'];
                mysqli_stmt_bind_param($det, 'iidds', $id_pesanan, $idl, $b, $sub, $c);
                mysqli_stmt_execute($det);
            }

            mysqli_commit($conn);
            header('Location: pesanan_detail.php?id=' . $id_pesanan . '&baru=1');
            exit;
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $error = 'Pesanan gagal disimpan. Silakan coba lagi.';
        }
    }
}
require __DIR__ . '/_header.php';
?>
<h2>Buat Pesanan</h2>
<p class="sub">Isi perkiraan berat pada layanan yang diinginkan. Harga pasti ditentukan setelah petugas menimbang cucian.</p>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="panel">
  <?php foreach ($layanan as $idl => $l): ?>
  <div class="row-layanan">
    <div>
      <strong><?= e($l['nama_layanan']) ?></strong>
      <small><?= rupiah($l['harga_per_kg']) ?>/kg &middot; estimasi <?= (int)$l['estimasi_selesai'] ?> jam</small>
      <small><?= e($l['keterangan']) ?></small>
    </div>
    <input type="number" name="berat[<?= $idl ?>]" step="0.1" min="0" max="100" placeholder="Berat (kg)"
           data-harga="<?= (float)$l['harga_per_kg'] ?>" value="<?= e($_POST['berat'][$idl] ?? '') ?>">
    <input type="text" name="catatan[<?= $idl ?>]" maxlength="200" placeholder="Catatan (opsional)" value="<?= e($_POST['catatan'][$idl] ?? '') ?>">
  </div>
  <?php endforeach; ?>
  <div class="total">Perkiraan total: <span id="total">Rp 0</span></div>
  <button type="submit">Kirim Pesanan</button>
</form>

<script>
const inputs = document.querySelectorAll('input[data-harga]');
function hitung() {
  let t = 0;
  inputs.forEach(i => { const b = parseFloat(String(i.value).replace(',', '.')); if (b > 0) t += b * parseFloat(i.dataset.harga); });
  document.getElementById('total').textContent = 'Rp ' + Math.round(t).toLocaleString('id-ID');
}
inputs.forEach(i => i.addEventListener('input', hitung));
hitung();
</script>
<?php require __DIR__ . '/_footer.php'; ?>
