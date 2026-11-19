<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);
$judul = 'Buat Pesanan';

$error = '';

$id_laundry = (int)($_POST['id_laundry'] ?? $_GET['laundry'] ?? 0);
$laundry = null;
if ($id_laundry > 0) {
    $ql = mysqli_prepare($conn, 'SELECT id_laundry, nama_laundry, alamat, wilayah FROM tb_laundry WHERE id_laundry = ?');
    mysqli_stmt_bind_param($ql, 'i', $id_laundry);
    mysqli_stmt_execute($ql);
    $laundry = mysqli_fetch_assoc(mysqli_stmt_get_result($ql));
}
if (!$laundry) {
    header('Location: cari_laundry.php?pilih=1');
    exit;
}

$layanan = [];
$rl = mysqli_prepare($conn, 'SELECT * FROM tb_layanan WHERE id_laundry = ? ORDER BY harga_per_kg');
mysqli_stmt_bind_param($rl, 'i', $id_laundry);
mysqli_stmt_execute($rl);
$r = mysqli_stmt_get_result($rl);
while ($row = mysqli_fetch_assoc($r)) $layanan[(int)$row['id_layanan']] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $berat   = $_POST['berat'] ?? [];
    $catatan = $_POST['catatan'] ?? [];
    $pilih = [];   

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
                $total += $b * $layanan[$idl]['harga_per_kg'];   
                $jam_max = max($jam_max, hitung_estimasi_jam($layanan[$idl]['estimasi_selesai'], $b));
            }
            $estimasi = date('Y-m-d H:i:s', strtotime("+$jam_max hours"));
            $now = date('Y-m-d H:i:s');

            $ins = mysqli_prepare($conn, "INSERT INTO tb_pesanan (id_pengguna, id_laundry, tanggal_pesan, status, estimasi_selesai, total_harga) VALUES (?, ?, ?, 'menunggu', ?, ?)");
            mysqli_stmt_bind_param($ins, 'iissd', $idp, $id_laundry, $now, $estimasi, $total);
            mysqli_stmt_execute($ins);
            $id_pesanan = mysqli_insert_id($conn);

            $det = mysqli_prepare($conn, '
                INSERT INTO tb_detail_laundry
                (id_pesanan, id_layanan, harga_per_kg, berat_perkiraan, subtotal, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            foreach ($pilih as $idl => [$b, $c]) {
                $harga = (float)$layanan[$idl]['harga_per_kg'];
                $sub = $b * $harga;

                mysqli_stmt_bind_param(
                    $det,
                    'iiddds',
                    $id_pesanan,
                    $idl,
                    $harga,
                    $b,
                    $sub,
                    $c
                );

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

<section class="order-page">

    <div class="order-heading">
        <div>
            <span class="order-label">PEMESANAN LAUNDRY</span>
            <h1>Buat Pesanan Baru</h1>
            <p>Pilih layanan laundry sesuai kebutuhan Anda dan masukkan perkiraan berat pakaian.</p>
        </div>
        <div class="order-heading-icon">🧺</div>
    </div>

    <div class="panel" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div>
            <small>Memesan di</small><br>
            <strong>🧺 <?= e($laundry['nama_laundry']) ?></strong>
            <?php if (!empty($laundry['alamat'])): ?><br><small>📍 <?= e($laundry['alamat']) ?></small><?php endif; ?>
        </div>
        <a href="cari_laundry.php">Ganti laundry</a>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$layanan): ?>
        <div class="alert">
            Laundry ini belum mengatur layanan dan harga, sehingga belum bisa menerima pesanan online.
            Silakan <a href="cari_laundry.php">pilih laundry lain</a> atau hubungi laundry ini langsung.
        </div>
    </section>
    <?php require __DIR__ . '/_footer.php'; exit; ?>
    <?php endif; ?>

    <form method="post" class="order-form">
        <input type="hidden" name="id_laundry" value="<?= (int)$laundry['id_laundry'] ?>">

        <div class="order-section-title">
            <div>
                <h2>Pilih Layanan Laundry</h2>
                <p>Isi berat pakaian pada layanan yang ingin digunakan.</p>
            </div>
            <span class="order-count"><?= count($layanan) ?> Layanan</span>
        </div>

        <div class="order-grid">

            <?php foreach ($layanan as $idl => $l): ?>

            <div class="order-card">

                <div class="order-card-top">
                    <div class="order-icon">🧼</div>
                    <span class="order-tag">Tersedia</span>
                </div>

                <h3><?= e($l['nama_layanan']) ?></h3>

                <div class="order-price">
                    <strong><?= rupiah($l['harga_per_kg']) ?></strong>
                    <span>/ kg</span>
                </div>

                <div class="order-estimate">
                    <span>⏱️</span>
                    <div>
                        <small>Estimasi selesai</small>
                        <strong><?= (int)$l['estimasi_selesai'] ?> jam</strong>
                    </div>
                </div>

                <p class="order-description">
                    <?= e(trim(explode(',', $l['keterangan'])[0])) ?>
                </p>

                <div class="order-input">
                    <label>Perkiraan berat pakaian (kg)</label>
                    <input
                        type="number"
                        name="berat[<?= $idl ?>]"
                        step="0.1"
                        min="0"
                        max="100"
                        placeholder="Contoh: 3"
                        data-harga="<?= (float)$l['harga_per_kg'] ?>"
                        data-jam="<?= (int)$l['estimasi_selesai'] ?>"
                        value="<?= e($_POST['berat'][$idl] ?? '') ?>"
                    >
                </div>

                <div class="order-input">
                    <label>Catatan tambahan</label>
                    <input
                        type="text"
                        name="catatan[<?= $idl ?>]"
                        maxlength="200"
                        placeholder="Catatan (opsional)"
                        value="<?= e($_POST['catatan'][$idl] ?? '') ?>"
                    >
                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <div class="order-summary">
            <div>
                <span class="order-summary-label">TOTAL PERKIRAAN BIAYA</span>
                <div class="total">
                    <span id="total">Rp 0</span>
                </div>
                <p>Harga akhir ditentukan setelah petugas menimbang pakaian.</p>

                <span class="order-summary-label" style="display:block;margin-top:14px">ESTIMASI WAKTU SELESAI</span>
                <div class="total">
                    <span id="estimasi">-</span>
                </div>
                <p>Ditentukan oleh jenis layanan yang dipilih, tidak dipengaruhi berat cucian.</p>
            </div>

            <button type="submit" class="order-submit" id="tombolKirim" disabled>
                Kirim Pesanan <span>→</span>
            </button>
        </div>

    </form>

    <div class="order-note">
        <strong>Informasi:</strong>
        Berat yang dimasukkan merupakan perkiraan. Total pembayaran dapat berubah
        setelah petugas melakukan penimbangan berat aktual.
    </div>

</section>

<script>
const inputs = document.querySelectorAll('input[data-harga]');
const tombolKirim = document.getElementById('tombolKirim');

function hitung() {
    let t = 0;
    let jam = 0;
    let adaPesanan = false;
    let inputValid = true;

    inputs.forEach(i => {
        const nilai = i.value.trim();

        if (nilai === '') return;

        const b = parseFloat(nilai);

        if (!Number.isFinite(b) || b <= 0 || b > 100) {
            inputValid = false;
            return;
        }

        adaPesanan = true;
        t += b * parseFloat(i.dataset.harga);
        jam = Math.max(jam, parseInt(i.dataset.jam, 10));
    });

    document.getElementById('total').textContent =
        'Rp ' + Math.round(t).toLocaleString('id-ID');

    let teks = '-';

    if (jam > 0) {
        teks = jam + ' jam';

        if (jam >= 24) {
            const hari = Math.round(jam / 24 * 10) / 10;
            teks += ' (≈ ' + String(hari).replace('.', ',') + ' hari)';
        }
    }

    document.getElementById('estimasi').textContent = teks;

    tombolKirim.disabled = !(adaPesanan && inputValid);
}

inputs.forEach(i => i.addEventListener('input', hitung));

hitung();
</script>

<?php require __DIR__ . '/_footer.php'; ?>