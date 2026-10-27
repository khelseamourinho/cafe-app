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

<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-5">
        <h3 class="mb-4">Login</h3>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-<?= $jenis_alert ?>"><?= $pesan ?></div>
        <?php endif; ?>

        <form action="auth_process.php" method="POST">
            <input type="hidden" name="aksi" value="login">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" maxlength="50" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" maxlength="255" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <hr>
        <p class="text-center mb-0">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
