<?php
$judul = 'Dashboard Admin';
require __DIR__ . '/_header.php';

$n_pel = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tb_pengguna WHERE role = 'pelanggan'"))[0];
$n_pes = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tb_pesanan"))[0];
$pend  = (float)mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_bayar),0) FROM tb_pembayaran WHERE status_bayar = 'lunas'"))[0];
?>
<section class="welcome-section">
    <div>
        <span class="welcome-label">DASHBOARD ADMIN</span>
        <h1>Selamat Datang, <?= e($_SESSION['nama']) ?>!</h1>
        <p>Pelanggan: <strong><?= $n_pel ?></strong> &nbsp;|&nbsp; Total pesanan: <strong><?= $n_pes ?></strong> &nbsp;|&nbsp; Pendapatan (lunas): <strong><?= rupiah($pend) ?></strong></p>
    </div>
    <div class="welcome-icon">👨‍💼</div>
</section>

<section class="dashboard-section">
    <div class="section-heading">
        <h2>Menu Pengelolaan</h2>
        <p>Pilih aktivitas yang ingin Anda lakukan.</p>
    </div>
    <div class="dashboard-menu">
        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='layanan.php'">
            <div class="dashboard-icon blue">🧺</div>
            <div><h3>Layanan &amp; Harga</h3><p>Tambah, ubah, dan hapus jenis layanan.</p><a class="menu-status" href="layanan.php">Buka &rarr;</a></div>
        </div>
        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='pengguna.php'">
            <div class="dashboard-icon purple">👥</div>
            <div><h3>Pengguna &amp; Hak Akses</h3><p>Kelola pelanggan, petugas, dan peran akun.</p><a class="menu-status" href="pengguna.php">Buka &rarr;</a></div>
        </div>
        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='transaksi.php'">
            <div class="dashboard-icon orange">🧾</div>
            <div><h3>Monitoring Transaksi</h3><p>Pantau seluruh pesanan dan pembayaran.</p><a class="menu-status" href="transaksi.php">Buka &rarr;</a></div>
        </div>
        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='laporan.php'">
            <div class="dashboard-icon green">📊</div>
            <div><h3>Laporan</h3><p>Rekap transaksi dan pendapatan per bulan.</p><a class="menu-status" href="laporan.php">Buka &rarr;</a></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
