
<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['petugas']);

$judul = 'Beranda';
$idpt = (int)$_SESSION['id_pengguna'];

// Menghitung pesanan masuk.
$idl = (int)laundry_petugas($conn);
$q_masuk = mysqli_prepare($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tb_pesanan
    WHERE status = 'menunggu' AND id_laundry = ?
");
mysqli_stmt_bind_param($q_masuk, 'i', $idl);
mysqli_stmt_execute($q_masuk);
$jumlah_masuk = mysqli_fetch_assoc(mysqli_stmt_get_result($q_masuk))['jumlah'];

// Menghitung pesanan aktif milik petugas.
$q_aktif = mysqli_prepare($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tb_pesanan
    WHERE id_petugas = ?
    AND status IN ('diterima', 'dicuci', 'dikeringkan', 'disetrika')
");
mysqli_stmt_bind_param($q_aktif, 'i', $idpt);
mysqli_stmt_execute($q_aktif);
$jumlah_aktif = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_aktif)
)['jumlah'];

// Menghitung pesanan selesai milik petugas.
$q_selesai = mysqli_prepare($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tb_pesanan
    WHERE id_petugas = ?
    AND status IN ('selesai', 'siap_diambil', 'diambil')
");
mysqli_stmt_bind_param($q_selesai, 'i', $idpt);
mysqli_stmt_execute($q_selesai);
$jumlah_selesai = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_selesai)
)['jumlah'];

// Menghitung jumlah transaksi pembayaran.
$q_bayar = mysqli_prepare($conn, "
    SELECT COUNT(*) AS jumlah
    FROM tb_pembayaran b
    JOIN tb_pesanan p ON p.id_pesanan = b.id_pesanan
    WHERE p.id_petugas = ?
");
mysqli_stmt_bind_param($q_bayar, 'i', $idpt);
mysqli_stmt_execute($q_bayar);
$jumlah_bayar = mysqli_fetch_assoc(
    mysqli_stmt_get_result($q_bayar)
)['jumlah'];

require __DIR__ . '/_header.php';
?>

<section class="welcome-section">
    <div>
        <span class="welcome-label">DASHBOARD PETUGAS</span>
        <h1>Selamat Datang, <?= e($_SESSION['nama']) ?>!</h1>
        <p>Kelola pesanan dan pantau proses pengerjaan laundry.</p>
    </div>
    <div class="welcome-icon">👨‍🔧</div>
</section>

<!-- RINGKASAN PESANAN -->
<section class="dashboard-section">
    <div class="section-heading">
        <h2>Ringkasan Aktivitas</h2>
        <p>Informasi jumlah pesanan dan transaksi laundry.</p>
    </div>

    <div class="dashboard-menu">

        <div class="dashboard-card">
            <div class="dashboard-icon blue">📥</div>
            <div>
                <h3>Pesanan Masuk</h3>
                <p>Menunggu konfirmasi petugas.</p>
                <strong><?= (int)$jumlah_masuk ?> Pesanan</strong>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-icon purple">🔄</div>
            <div>
                <h3>Pesanan Aktif</h3>
                <p>Sedang dalam proses pengerjaan.</p>
                <strong><?= (int)$jumlah_aktif ?> Pesanan</strong>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-icon green">📦</div>
            <div>
                <h3>Pesanan Selesai</h3>
                <p>Selesai hingga sudah diambil.</p>
                <strong><?= (int)$jumlah_selesai ?> Pesanan</strong>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-icon orange">💳</div>
            <div>
                <h3>Transaksi Pembayaran</h3>
                <p>Jumlah pembayaran pesanan petugas.</p>
                <strong><?= (int)$jumlah_bayar ?> Transaksi</strong>
            </div>
        </div>

    </div>
</section>

<!-- MENU PENGELOLAAN -->
<section class="dashboard-section">
    <div class="section-heading">
        <h2>Menu Pengelolaan</h2>
        <p>Pilih aktivitas yang ingin Anda lakukan.</p>
    </div>

    <div class="dashboard-menu">

        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='pesanan_masuk.php'">
            <div class="dashboard-icon blue">📥</div>
            <div>
                <h3>Pesanan Masuk</h3>
                <p>Melihat daftar pesanan dari pelanggan.</p>
                <a class="menu-status" href="pesanan_masuk.php">Buka &rarr;</a>
            </div>
        </div>

        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='layanan.php'">
            <div class="dashboard-icon blue">🧼</div>
            <div>
                <h3>Layanan &amp; Harga</h3>
                <p>Atur layanan dan harga laundry Anda.</p>
                <a class="menu-status" href="layanan.php">Buka &rarr;</a>
            </div>
        </div>

        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='pesanan_aktif.php'">
            <div class="dashboard-icon purple">⚖️</div>
            <div>
                <h3>Input Berat Aktual</h3>
                <p>Mencatat berat cucian setelah ditimbang.</p>
                <a class="menu-status" href="pesanan_aktif.php">Buka &rarr;</a>
            </div>
        </div>

        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='pesanan_aktif.php'">
            <div class="dashboard-icon orange">🔄</div>
            <div>
                <h3>Update Status Pengerjaan</h3>
                <p>Memperbarui tahapan proses laundry.</p>
                <a class="menu-status" href="pesanan_aktif.php">Buka &rarr;</a>
            </div>
        </div>

        <div class="dashboard-card" style="cursor:pointer" onclick="location.href='pesanan_aktif.php'">
            <div class="dashboard-icon green">📦</div>
            <div>
                <h3>Konfirmasi Siap Diambil</h3>
                <p>Menandai pesanan yang sudah siap diambil.</p>
                <a class="menu-status" href="pesanan_aktif.php">Buka &rarr;</a>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
