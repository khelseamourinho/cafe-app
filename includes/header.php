<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sudah_login = isset($_SESSION['id_user']);
$is_admin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// $base_path diset oleh halaman yang include file ini.
// Halaman di root  → $base_path = ''
// Halaman di admin/ → $base_path = '../'
if (!isset($base_path)) {
    $base_path = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar bg-body-tertiary border-bottom sticky-top">
    <div class="container d-flex justify-content-between align-items-center">

        <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>index.php">
            <img src="<?= $base_path ?>assets/logo.jpeg" alt="Logo Cafe Kita" height="36" class="me-2">
            Cafe Kita
        </a>

        <div class="d-flex align-items-center">
            <?php if (!$sudah_login): ?>
                <a class="btn btn-outline-primary btn-sm me-2" href="<?= $base_path ?>login.php">Login</a>
            <?php endif; ?>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#menuOffcanvas" aria-controls="menuOffcanvas" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="menuOffcanvas" aria-labelledby="menuOffcanvasLabel">
    <div class="offcanvas-header">
        <?php if ($sudah_login): ?>
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <div>
                    <h5 class="offcanvas-title mb-0" id="menuOffcanvasLabel">
                        Hai, <?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?>
                    </h5>
                    <small class="text-muted">@<?= htmlspecialchars($_SESSION['username'] ?? '') ?></small>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm ms-auto d-flex align-items-center justify-content-center"
                        data-bs-toggle="modal" data-bs-target="#modalEditProfil"
                        title="Edit Profil"
                        style="width:32px;height:32px;padding:0;border-radius:6px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                    </svg>
                </button>
            </div>
        <?php else: ?>
            <h5 class="offcanvas-title" id="menuOffcanvasLabel">Menu</h5>
        <?php endif; ?>
        <button type="button" class="btn-close ms-2" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            <?php if ($sudah_login): ?>
                <a class="list-group-item list-group-item-action" href="<?= $base_path ?>index.php">Katalog Menu</a>
                <?php if ($is_admin): ?>
                    <div class="list-group-item list-group-item-action disabled text-muted small px-3 py-1">Panel Admin</div>
                    <a class="list-group-item list-group-item-action" href="<?= $base_path ?>admin/dashboard.php">Dashboard</a>
                    <a class="list-group-item list-group-item-action" href="<?= $base_path ?>admin/kelola_menu.php">Kelola Menu</a>
                    <a class="list-group-item list-group-item-action" href="<?= $base_path ?>admin/kelola_pesanan.php">Kelola Pesanan</a>
                <?php endif; ?>
                <a class="list-group-item list-group-item-action" href="<?= $base_path ?>riwayat.php">Riwayat Pesanan</a>
                <a class="list-group-item list-group-item-action text-danger" href="<?= $base_path ?>logout.php">Logout</a>
            <?php else: ?>
                <a class="list-group-item list-group-item-action" href="<?= $base_path ?>login.php">Login</a>
                <a class="list-group-item list-group-item-action" href="<?= $base_path ?>register.php">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($sudah_login): ?>
<!-- ══════ MODAL EDIT PROFIL ══════ -->
<div class="modal fade" id="modalEditProfil" tabindex="-1" aria-labelledby="labelEditProfil" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelEditProfil">Edit Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="<?= $base_path ?>profil_proses.php" id="formEditProfil">
                <div class="modal-body">
                    <!-- Alert untuk pesan -->
                    <div class="alert alert-danger d-none" id="profilAlert"></div>
                    <div class="alert alert-success d-none" id="profilSuccess"></div>

                    <!-- Edit Nama -->
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label fw-semibold">Nama</label>
                        <input type="text" class="form-control" id="edit_nama" name="nama"
                               value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" required>
                    </div>

                    <!-- Edit Username -->
                    <div class="mb-3">
                        <label for="edit_username" class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username"
                               value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                    </div>

                    <hr>

                    <!-- Ganti Password (opsional) -->
                    <p class="text-muted small mb-2">Kosongkan jika tidak ingin mengganti password.</p>

                    <div class="mb-3">
                        <label for="password_lama" class="form-label fw-semibold">Password Lama</label>
                        <input type="password" class="form-control" id="password_lama" name="password_lama">
                    </div>

                    <div class="mb-3">
                        <label for="password_baru" class="form-label fw-semibold">Password Baru</label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanProfil">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formEditProfil');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var alertEl   = document.getElementById('profilAlert');
        var successEl = document.getElementById('profilSuccess');
        var btn       = document.getElementById('btnSimpanProfil');

        alertEl.classList.add('d-none');
        successEl.classList.add('d-none');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        var formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.sukses) {
                successEl.textContent = data.pesan;
                successEl.classList.remove('d-none');

                // Update session display
                if (data.nama) {
                    // Update offcanvas title
                    var titleEl = document.getElementById('menuOffcanvasLabel');
                    if (titleEl) titleEl.innerHTML = 'Hai, ' + data.nama;
                    // Update greeting on index if exists
                    var h1 = document.querySelector('h1.h3');
                    if (h1 && h1.textContent.indexOf('Selamat datang') !== -1) {
                        h1.textContent = 'Selamat datang, ' + data.nama;
                    }
                }
                if (data.username) {
                    // Update @username display
                    var usernameEl = titleEl ? titleEl.parentElement.querySelector('small') : null;
                    if (usernameEl) usernameEl.textContent = '@' + data.username;
                }

                // Reset password fields
                form.querySelector('#password_lama').value = '';
                form.querySelector('#password_baru').value = '';

                setTimeout(function() {
                    successEl.classList.add('d-none');
                }, 3000);
            } else {
                alertEl.textContent = data.pesan || 'Terjadi kesalahan.';
                alertEl.classList.remove('d-none');
            }
        })
        .catch(function() {
            alertEl.textContent = 'Gagal menyimpan. Coba lagi.';
            alertEl.classList.remove('d-none');
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = 'Simpan';
        });
    });
});
</script>
<?php endif; ?>

<main class="container py-4">
