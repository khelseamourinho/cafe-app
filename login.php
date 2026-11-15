<?php
// Redirect SEBELUM header.php output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

$pesan       = '';
$jenis_alert = 'danger';
if (isset($_GET['gagal']) && $_GET['gagal'] === 'login') {
    $pesan = 'Username atau password salah.';
} elseif (isset($_GET['berhasil']) && $_GET['berhasil'] === 'daftar') {
    $pesan       = 'Registrasi berhasil, silakan login.';
    $jenis_alert = 'success';
}

require 'includes/header.php';
?>

<div class="row justify-content-center align-items-center my-4">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card auth-card p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="auth-badge mb-2 d-inline-block">☕ Cafe Kita</span>
                <h4 class="fw-bold mb-1" style="color: var(--espresso-primary);">Selamat Datang</h4>
                <p class="text-muted small mb-0">Masuk untuk mulai memesan menu favoritmu</p>
            </div>

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-<?= $jenis_alert ?> py-2 small rounded-3"><?= $pesan ?></div>
            <?php endif; ?>

            <form action="auth_process.php" method="POST">
                <input type="hidden" name="aksi" value="login">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Username</label>
                    <input type="text" name="username" class="form-control auth-input" placeholder="Masukkan username" maxlength="50" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-muted">Password</label>
                    <input type="password" name="password" class="form-control auth-input" placeholder="Masukkan password" maxlength="255" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Masuk</button>
            </form>

            <hr class="my-4" style="border-color: var(--border-soft);">
            <p class="text-center small mb-0 text-muted">
                Belum punya akun? <a href="register.php" class="auth-link">Daftar sekarang</a>
            </p>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>