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

<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-5">
        <h3 class="mb-4">Daftar Akun Baru</h3>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-danger"><?= $pesan ?></div>
        <?php endif; ?>

        <form action="auth_process.php" method="POST">
            <input type="hidden" name="aksi" value="register">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" maxlength="100" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" maxlength="50" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" maxlength="255" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Daftar</button>
        </form>

        <hr>
        <p class="text-center mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
