<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);
$judul = 'Pesanan Masuk';
$idl = laundry_petugas($conn); // laundry tempat petugas bertugas

// Konfirmasi pesanan: menunggu -> diterima, sekaligus mencatat petugas yang menangani
// Tolak pesanan: menunggu -> ditolak (dengan alasan opsional)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $idpt = (int)$_SESSION['id_pengguna'];

    if ($idl === null) {
        header('Location: pesanan_masuk.php?msg=gagal');
        exit;
    }

    if (($_POST['aksi'] ?? '') === 'tolak') {
        $alasan = trim($_POST['alasan'] ?? '');
        if ($alasan === '') {
            header('Location: pesanan_masuk.php?msg=alasan_kosong');
            exit;
        }
        $alasan = mb_substr($alasan, 0, 255);
        $t = mysqli_prepare($conn, "UPDATE tb_pesanan SET status = 'ditolak', alasan_penolakan = ? WHERE id_pesanan = ? AND status = 'menunggu' AND id_laundry = ?");
        mysqli_stmt_bind_param($t, 'sii', $alasan, $id_pesanan, $idl);
        mysqli_stmt_execute($t);

        if (mysqli_stmt_affected_rows($t) > 0) {
            $judul_notif = 'Pesanan Ditolak';
            $pesan_notif = 'Pesanan #' . $id_pesanan . ' ditolak oleh petugas. Alasan: ' . $alasan;

            $n = mysqli_prepare($conn, "INSERT INTO tb_notifikasi
                (id_pengguna, id_pesanan, judul, pesan)
                SELECT id_pengguna, id_pesanan, ?, ?
                FROM tb_pesanan
                WHERE id_pesanan = ?");

            mysqli_stmt_bind_param($n, 'ssi', $judul_notif, $pesan_notif, $id_pesanan);
            mysqli_stmt_execute($n);

            header('Location: pesanan_masuk.php?msg=ditolak');
        } else {
            header('Location: pesanan_masuk.php?msg=gagal');
        }
        exit;
    }

    $u = mysqli_prepare($conn, "UPDATE tb_pesanan SET status = 'diterima', id_petugas = ? WHERE id_pesanan = ? AND status = 'menunggu' AND id_laundry = ?");
    mysqli_stmt_bind_param($u, 'iii', $idpt, $id_pesanan, $idl);
    mysqli_stmt_execute($u);
    if (mysqli_stmt_affected_rows($u) > 0) {
        $judul_notif = 'Pesanan Diterima';
        $pesan_notif = 'Pesanan #' . $id_pesanan . ' telah diterima dan akan segera diproses oleh petugas.';

        $n = mysqli_prepare($conn, "INSERT INTO tb_notifikasi
            (id_pengguna, id_pesanan, judul, pesan)
            SELECT id_pengguna, id_pesanan, ?, ?
            FROM tb_pesanan
            WHERE id_pesanan = ?");

        mysqli_stmt_bind_param($n, 'ssi', $judul_notif, $pesan_notif, $id_pesanan);
        mysqli_stmt_execute($n);

        header('Location: pesanan_kelola.php?id=' . $id_pesanan . '&msg=diterima');
    } else {
        header('Location: pesanan_masuk.php?msg=gagal');
    }
    exit;
}

$idl_q = (int)$idl;
$qh = mysqli_prepare($conn, "SELECT p.*, u.nama, u.no_telepon FROM tb_pesanan p JOIN tb_pengguna u ON u.id_pengguna = p.id_pengguna WHERE p.status = 'menunggu' AND p.id_laundry = ? ORDER BY p.tanggal_pesan ASC");
mysqli_stmt_bind_param($qh, 'i', $idl_q);
mysqli_stmt_execute($qh);
$hasil = mysqli_stmt_get_result($qh);
require __DIR__ . '/_header.php';
?>
<div class="page-heading">
    <div>
        <h1>Pesanan Masuk</h1>
        <p>Kelola dan konfirmasi pesanan laundry dari pelanggan.</p>
    </div>
</div>

<?php if ($idl === null): ?>
    <div class="petugas-alert alert-warning">
        <span>⚠️</span> Akun Anda belum ditautkan ke laundry mana pun, sehingga belum menerima pesanan. Hubungi admin.
    </div>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'alasan_kosong'): ?>
    <div class="petugas-alert alert-warning">
        <span>⚠️</span> Alasan penolakan wajib diisi sebelum menolak pesanan.
    </div>
<?php endif; ?>

<?php if (($_GET['msg'] ?? '') === 'ditolak'): ?>
    <div class="petugas-alert alert-success">
        <span>✓</span> Pesanan berhasil ditolak.
    </div>
<?php endif; ?>

<div class="petugas-content-card">

    <div class="petugas-card-heading">
        <div>
            <h3>Daftar Pesanan Baru</h3>
            <p>Pesanan yang menunggu konfirmasi petugas.</p>
        </div>
        <span class="petugas-count">
            <?= mysqli_num_rows($hasil) ?> Pesanan
        </span>
    </div>

    <div class="tbl-wrap">
        <table class="petugas-table">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Telepon</th>
                    <th>Perkiraan Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($hasil) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($hasil)): ?>
                        <tr>
                            <td>
                                <strong>#<?= (int)$p['id_pesanan'] ?></strong>
                            </td>

                            <td>
                                <?= e(date('d/m/Y H:i', strtotime($p['tanggal_pesan']))) ?>
                            </td>

                            <td><?= e($p['nama']) ?></td>

                            <td><?= e($p['no_telepon'] ?: '-') ?></td>

                            <td>
                                <strong><?= rupiah($p['total_harga']) ?></strong>
                            </td>

                            <td>
                                <div class="petugas-action-group">

                                    <form method="post">
                                        <input type="hidden" name="id_pesanan"
                                               value="<?= (int)$p['id_pesanan'] ?>">

                                        <button type="submit"
                                                class="petugas-btn-accept">
                                            ✓ Terima
                                        </button>
                                    </form>

                                    <form method="post"
                                          class="petugas-reject-form"
                                          onsubmit="return confirm('Tolak pesanan #<?= (int)$p['id_pesanan'] ?>?');">

                                        <input type="hidden" name="id_pesanan"
                                               value="<?= (int)$p['id_pesanan'] ?>">

                                        <input type="hidden" name="aksi"
                                               value="tolak">

                                        <input type="text" name="alasan"
                                                maxlength="255"
                                                placeholder="Masukkan alasan penolakan..."
                                                required>

                                        <button type="submit"
                                                class="petugas-btn-reject">
                                            Tolak
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="petugas-empty">
                            <span>📋</span>
                            <strong>Belum Ada Pesanan Baru</strong>
                            <p>Pesanan pelanggan yang masuk akan muncul di sini.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<?php require __DIR__ . '/_footer.php'; ?>
