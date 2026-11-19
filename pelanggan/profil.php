<?php
require_once __DIR__ . '/../config/auth.php';
wajib_login(['pelanggan']);

$idp = (int)$_SESSION['id_pengguna'];

if (empty($_SESSION['csrf_profil'])) {
    $_SESSION['csrf_profil'] = bin2hex(random_bytes(32));
}

$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_profil'] ?? '';

    if (!hash_equals($_SESSION['csrf_profil'], $token)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if ($nama === '' || $email === '') {
            $error = 'Nama dan email wajib diisi.';
        } elseif (strlen($nama) > 100 || strlen($email) > 100) {
            $error = 'Nama atau email terlalu panjang.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif ($telepon !== '' &&
            (!preg_match('/^[0-9+\-\s()]{8,15}$/', $telepon))) {
            $error = 'Nomor telepon harus 8–15 karakter.';
        } else {
            $cek = mysqli_prepare($conn, '
                SELECT id_pengguna FROM tb_pengguna
                WHERE email = ? AND id_pengguna <> ?
            ');
            mysqli_stmt_bind_param($cek, 'si', $email, $idp);
            mysqli_stmt_execute($cek);
            $email_ada = mysqli_stmt_get_result($cek)->num_rows > 0;
            mysqli_stmt_close($cek);

            if ($email_ada) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                $qfoto = mysqli_prepare($conn, '
                    SELECT foto_profil FROM tb_pengguna
                    WHERE id_pengguna = ?
                ');
                mysqli_stmt_bind_param($qfoto, 'i', $idp);
                mysqli_stmt_execute($qfoto);
                $data_lama = mysqli_fetch_assoc(mysqli_stmt_get_result($qfoto));
                mysqli_stmt_close($qfoto);

                $foto_simpan = $data_lama['foto_profil'] ?? null;
                $foto_baru = null;

                if (isset($_FILES['foto_profil']) &&
                    $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {

                    if ($_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Foto gagal diunggah.';
                    } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                        $error = 'Ukuran foto maksimal 2 MB.';
                    } else {
                        $tmp = $_FILES['foto_profil']['tmp_name'];
                        $mime = mime_content_type($tmp);

                        $jenis = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp'
                        ];

                        if (!isset($jenis[$mime])) {
                            $error = 'Format foto harus JPG, PNG, atau WEBP.';
                        } else {
                            $folder = __DIR__ . '/uploads/profil/';

                            if (!is_dir($folder)) {
                                mkdir($folder, 0755, true);
                            }

                            $nama_file = bin2hex(random_bytes(16)) . '.' . $jenis[$mime];

                            if (move_uploaded_file($tmp, $folder . $nama_file)) {
                                $foto_baru = 'uploads/profil/' . $nama_file;
                                $foto_simpan = $foto_baru;
                            } else {
                                $error = 'Foto tidak dapat disimpan.';
                            }
                        }
                    }
                }

                if ($error === '') {
                    $update = mysqli_prepare($conn, '
                        UPDATE tb_pengguna
                        SET nama = ?, email = ?, no_telepon = ?,
                            alamat = ?, foto_profil = ?
                        WHERE id_pengguna = ?
                    ');

                    mysqli_stmt_bind_param(
                        $update,
                        'sssssi',
                        $nama,
                        $email,
                        $telepon,
                        $alamat,
                        $foto_simpan,
                        $idp
                    );

                    if (mysqli_stmt_execute($update)) {
                        $_SESSION['nama'] = $nama;

                        if ($foto_baru && !empty($data_lama['foto_profil'])) {
                            $foto_lama = $data_lama['foto_profil'];
                            if (strpos($foto_lama, 'uploads/profil/') === 0) {
                                $path_lama = __DIR__ . '/' . $foto_lama;
                                if (is_file($path_lama)) {
                                    unlink($path_lama);
                                }
                            }
                        }

                        $pesan = 'Profil berhasil diperbarui.';
                    } else {
                        $error = 'Profil gagal diperbarui. Silakan coba lagi.';
                        if ($foto_baru && is_file(__DIR__ . '/' . $foto_baru)) {
                            unlink(__DIR__ . '/' . $foto_baru);
                        }
                    }

                    mysqli_stmt_close($update);
                }
            }
        }
    }
}

$q = mysqli_prepare($conn, '
    SELECT nama, email, no_telepon, alamat, created_at, foto_profil
    FROM tb_pengguna
    WHERE id_pengguna = ?
');
mysqli_stmt_bind_param($q, 'i', $idp);
mysqli_stmt_execute($q);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
mysqli_stmt_close($q);

if (!$profil) {
    http_response_code(404);
    die('Data profil tidak ditemukan.');
}

$mode_edit = isset($_GET['edit']) && $_GET['edit'] === '1';
$judul = 'Profil Saya';
require __DIR__ . '/_header.php';
?>


<section class="profile-page">

    <div class="page-heading">
        <div>
            <h1>Profil Saya</h1>
            <p>Lihat dan kelola informasi akun Anda.</p>
        </div>
    </div>

    <?php if ($pesan): ?>
        <p class="profile-success"><?= e($pesan) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="profile-error"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (!$mode_edit): ?>

        <div class="profile-card profile-view">

            <div class="profile-photo">
                <?php if (!empty($profil['foto_profil'])): ?>
                    <img src="<?= BASE_URL ?>/pelanggan/<?= e($profil['foto_profil']) ?>"
                         alt="Foto profil">
                <?php else: ?>
                    <span>👤</span>
                <?php endif; ?>
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

            <a href="profil.php?edit=1" class="profile-edit-btn">
                ✏️ Edit Profil
            </a>

        </div>

    <?php else: ?>

        <div class="profile-card profile-edit-card">

            <div class="edit-profile-heading">
                <h2>Edit Profil</h2>
                <p>Perbarui informasi akun dan foto profil Anda.</p>
            </div>

            <form method="POST" action="profil.php?edit=1"
                  enctype="multipart/form-data">

                <input type="hidden" name="csrf_profil"
                       value="<?= e($_SESSION['csrf_profil']) ?>">

                <div class="photo-edit-section">

                    <div class="profile-photo">
                        <?php if (!empty($profil['foto_profil'])): ?>
                            <img id="previewFoto"
                                 src="<?= BASE_URL ?>/pelanggan/<?= e($profil['foto_profil']) ?>"
                                 alt="Foto profil">
                        <?php else: ?>
                            <span id="placeholderFoto">👤</span>
                            <img id="previewFoto" src="" alt="Foto profil"
                                 style="display:none;">
                        <?php endif; ?>
                    </div>

                    <label for="foto_profil" class="profile-upload-btn">
                        <span>📷</span> Ganti Foto
                    </label>

                    <input type="file" 
                           name="foto_profil"
                           id="foto_profil"
                           accept="image/jpeg,image/png,image/webp"
                           hidden>

                    <p class="profile-hint">
                        JPG, PNG, atau WEBP. Maksimal 2 MB.
                    </p>

                </div>

                <div class="profile-form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama"
                           maxlength="100" required
                           value="<?= e($profil['nama']) ?>">
                </div>

                <div class="profile-form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           maxlength="100" required
                           value="<?= e($profil['email']) ?>">
                </div>

                <div class="profile-form-group">
                    <label for="no_telepon">Nomor Telepon</label>
                    <input type="text" id="no_telepon"
                           name="no_telepon" maxlength="15"
                           value="<?= e($profil['no_telepon'] ?? '') ?>">
                </div>

                <div class="profile-form-group">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat"
                              rows="4"><?= e($profil['alamat'] ?? '') ?></textarea>
                </div>

                <div class="profile-form-group">
                    <label>Tanggal Bergabung</label>
                    <input type="text" readonly
                           value="<?= $profil['created_at']
                               ? e(date('d/m/Y', strtotime($profil['created_at'])))
                               : '-' ?>">
                </div>

                <div class="profile-actions">
                    <button type="submit" class="profile-save-btn">
                        Simpan Perubahan
                    </button>

                    <a href="profil.php" class="profile-cancel-btn">
                        Batal
                    </a>
                </div>

            </form>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="detail-back">
        ← Kembali ke Dashboard
    </a>

</section>

<script>
const inputFoto = document.getElementById('foto_profil');
const previewFoto = document.getElementById('previewFoto');
const placeholderFoto = document.getElementById('placeholderFoto');

if (inputFoto) {
    inputFoto.addEventListener('change', function () {
        const file = this.files[0];

        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran foto maksimal 2 MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            previewFoto.src = e.target.result;
            previewFoto.style.display = 'block';

            if (placeholderFoto) {
                placeholderFoto.style.display = 'none';
            }
        };

        reader.readAsDataURL(file);
    });
}
</script>
