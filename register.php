<?php
// Redirect SEBELUM header.php output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

$pesan = '';
if (isset($_GET['gagal']) && $_GET['gagal'] === 'daftar') {
    $pesan = 'Registrasi gagal. Username mungkin sudah digunakan.';
}

require 'includes/header.php';
?>

<div class="row justify-content-center align-items-center my-4">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card auth-card p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="auth-badge mb-2 d-inline-block">☕ Cafe Kita</span>
                <h4 class="fw-bold mb-1" style="color: var(--espresso-primary);">Buat Akun Baru</h4>
                <p class="text-muted small mb-0">Daftar sebentar untuk kemudahan pesan kopi</p>
            </div>

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-danger py-2 small rounded-3"><?= $pesan ?></div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST">
                <input type="hidden" name="aksi" value="register">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control auth-input" placeholder="Contoh: Budi Santoso" maxlength="100" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Username</label>
                    <input type="text" name="username" class="form-control auth-input" placeholder="Buat username unik" maxlength="50" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-muted">Password</label>
                    <input type="password" name="password" class="form-control auth-input" placeholder="Minimal 6 karakter" maxlength="255" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Daftar Akun</button>
            </form>

            <hr class="my-4" style="border-color: var(--border-soft);">
            <p class="text-center small mb-0 text-muted">
                Sudah punya akun? <a href="login.php" class="auth-link">Login di sini</a>
            </p>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>