
<?php
$judul = 'Profil Saya';
require __DIR__ . '/_header.php';

$idp = (int)$_SESSION['id_pengguna'];

$q = mysqli_prepare($conn, '
    SELECT nama, email, no_telepon, alamat, created_at
    FROM tb_pengguna
    WHERE id_pengguna = ?
');
mysqli_stmt_bind_param($q, 'i', $idp);
mysqli_stmt_execute($q);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

if (!$profil) {
    http_response_code(404);
    die('Data profil tidak ditemukan.');
}
?>

<section class="profile-page">

    <div class="page-heading">
        <div>
            <h1>Profil Saya</h1>
            <p>Lihat informasi akun dan data pribadi Anda.</p>
        </div>
    </div>

    <div class="profile-card">

        <div class="profile-avatar">
            <span>👤</span>
        </div>

        <h2><?= e($profil['nama']) ?></h2>
        <p class="profile-role">Pelanggan LaundryKu</p>

        <div class="profile-divider"></div>

        <div class="profile-info">

            <div class="profile-info-item">
                <span>Nama Lengkap</span>
                <strong><?= e($profil['nama']) ?></strong>
            </div>

            <div class="profile-info-item">
                <span>Email</span>
                <strong><?= e($profil['email']) ?></strong>
            </div>

            <div class="profile-info-item">
                <span>Nomor Telepon</span>
                <strong><?= e($profil['no_telepon'] ?: '-') ?></strong>
            </div>

            <div class="profile-info-item">
                <span>Alamat</span>
                <strong><?= nl2br(e($profil['alamat'] ?: '-')) ?></strong>
            </div>

            <div class="profile-info-item">
                <span>Tanggal Bergabung</span>
                <strong>
                    <?= $profil['created_at']
                        ? e(date('d/m/Y', strtotime($profil['created_at'])))
                        : '-' ?>
                </strong>
            </div>

        </div>

    </div>

    <a href="dashboard.php" class="detail-back">
        ← Kembali ke Dashboard
    </a>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
