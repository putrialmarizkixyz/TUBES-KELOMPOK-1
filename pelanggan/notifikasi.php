<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);

$id = (int)$_SESSION['id_pengguna'];

// Tandai semua notifikasi sebagai sudah dibaca
if (isset($_POST['tandai_semua'])) {

    $q_semua = mysqli_prepare($conn, "
        UPDATE tb_notifikasi
        SET status_baca = 'sudah_dibaca'
        WHERE id_pengguna = ?
        AND status_baca = 'belum_dibaca'
    ");

    mysqli_stmt_bind_param($q_semua, 'i', $id);
    mysqli_stmt_execute($q_semua);

    header('Location: notifikasi.php');
    exit;
}

// Tandai notifikasi sebagai sudah dibaca ketika dibuka
if (isset($_GET['baca'])) {
    $id_notifikasi = (int)$_GET['baca'];

    $q_baca = mysqli_prepare($conn, "
        UPDATE tb_notifikasi
        SET status_baca = 'sudah_dibaca'
        WHERE id_notifikasi = ?
        AND id_pengguna = ?
    ");

    mysqli_stmt_bind_param($q_baca, 'ii', $id_notifikasi, $id);
    mysqli_stmt_execute($q_baca);

    $pesanan = (int)($_GET['pesanan'] ?? 0);

    if ($pesanan > 0) {
        header('Location: pesanan_detail.php?id=' . $pesanan);
    } else {
        header('Location: notifikasi.php');
    }
    exit;
}

$judul = 'Notifikasi';
require __DIR__ . '/_header.php';
$id = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, '
    SELECT * FROM tb_notifikasi
    WHERE id_pengguna = ?
    ORDER BY tanggal_dibuat DESC
');

mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$hasil = mysqli_stmt_get_result($q);
?>

<section class="history-heading">
    <span class="welcome-label">PEMBERITAHUAN</span>
    <h1>Notifikasi</h1>
    <p>Lihat informasi terbaru mengenai pesanan laundry Anda.</p>
</section>

<section class="history-section">
    <div class="history-section-heading">
        <div>
            <h2>Semua Notifikasi</h2>
            <p>Pemberitahuan dari LaundryKu.</p>
        </div>

        <form method="post">
            <button
                type="submit"
                name="tandai_semua"
                value="1"
                class="btn-tandai-semua">
                ✓ Tandai Semua Dibaca
            </button>
        </form>
    </div>

    <div class="notification-list">
        <?php if (mysqli_num_rows($hasil) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($hasil)): ?>
                <div class="notification-card <?= $n['status_baca'] === 'belum_dibaca' ? 'unread' : '' ?>">

                    <div class="notification-icon">
                        🔔
                    </div>

                    <div class="notification-content">
                        <div class="notification-title">
                            <strong><?= e($n['judul']) ?></strong>

                            <?php if ($n['status_baca'] === 'belum_dibaca'): ?>
                                <span class="notification-dot"></span>
                            <?php endif; ?>
                        </div>

                        <p><?= e($n['pesan']) ?></p>

                        <small>
                            <?= e(date('d/m/Y H:i', strtotime($n['tanggal_dibuat']))) ?>
                        </small>

                        <a href="notifikasi.php?baca=<?= (int)$n['id_notifikasi'] ?>&pesanan=<?= (int)$n['id_pesanan'] ?>">
                            Lihat Detail Pesanan →
                        </a>
                    </div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="history-empty">
                <div class="empty-icon">🔔</div>
                <h3>Belum Ada Notifikasi</h3>
                <p>Pemberitahuan pesanan Anda akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
