<?php
$judul = 'Dashboard Pelanggan';
require __DIR__ . '/_header.php';

$id = (int)$_SESSION['id_pengguna'];
$q = mysqli_prepare($conn, "SELECT COUNT(*) AS total, SUM(status NOT IN ('selesai','siap_diambil','ditolak')) AS aktif FROM tb_pesanan WHERE id_pengguna = ?");
mysqli_stmt_bind_param($q, 'i', $id);
mysqli_stmt_execute($q);
$ringkas = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
?>

<section class="welcome-section">
    <div>
        <span class="welcome-label">DASHBOARD PELANGGAN</span>
        <h1>Selamat Datang di LaundryKu!</h1>
        <p>Kelola pesanan laundry Anda dengan mudah dan praktis.</p>
    </div>
    <div class="welcome-icon">🧺</div>
</section>

<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon active-icon">⏳</div>
        <div>
            <p>Pesanan Aktif</p>
            <h2><?= (int)$ringkas['aktif'] ?></h2>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon total-icon">📋</div>
        <div>
            <p>Total Pesanan</p>
            <h2><?= (int)$ringkas['total'] ?></h2>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-heading">
        <div>
            <h2>Menu Utama</h2>
            <p>Pilih layanan yang ingin Anda gunakan.</p>
        </div>
    </div>

    <div class="dashboard-menu">

        <a href="layanan.php" class="dashboard-card">
            <div class="dashboard-icon blue-icon">🧼</div>
            <div class="dashboard-card-content">
                <h3>Layanan &amp; Harga</h3>
                <p>Lihat pilihan layanan dan harga laundry.</p>
            </div>
            <span class="card-arrow">→</span>
        </a>

        <a href="pesanan_baru.php" class="dashboard-card">
            <div class="dashboard-icon green-icon">🛍️</div>
            <div class="dashboard-card-content">
                <h3>Buat Pesanan</h3>
                <p>Pesan layanan laundry sesuai kebutuhan.</p>
            </div>
            <span class="card-arrow">→</span>
        </a>

        <a href="pesanan_saya.php" class="dashboard-card">
            <div class="dashboard-icon orange-icon">📦</div>
            <div class="dashboard-card-content">
                <h3>Status &amp; Riwayat</h3>
                <p>Pantau proses dan riwayat pesanan Anda.</p>
            </div>
            <span class="card-arrow">→</span>
        </a>

        <a href="pembayaran.php" class="dashboard-card">
            <div class="dashboard-icon blue">💳</div>
            <div>
                <h3>Pembayaran</h3>
                <p>Lihat tagihan dan riwayat pembayaran.</p>
            </div>
            <span class="dashboard-arrow">→</span>
        </a>

        <a href="profil.php" class="dashboard-card">
            <div class="dashboard-icon purple">👤</div>
            <div>
                <h3>Profil</h3>
                <p>Lihat informasi akun dan data pribadi Anda.</p>
            </div>
            <span class="dashboard-arrow">→</span>
        </a>

    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
