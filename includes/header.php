<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sudah_login = isset($_SESSION['id_user']);
$is_admin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
<nav class="navbar bg-body-tertiary border-bottom">
    <div class="container d-flex justify-content-between align-items-center">

        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/logo.jpeg" alt="Logo Cafe Kita" height="36" class="me-2">
            Cafe Kita
        </a>

        <div class="d-flex align-items-center">
            <?php if (!$sudah_login): ?>
                <a class="btn btn-outline-primary btn-sm me-2" href="login.php">Login</a>
            <?php endif; ?>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" aria-controls="menuOffcanvas" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="menuOffcanvas" aria-labelledby="menuOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="menuOffcanvasLabel">
            <?php if ($sudah_login): ?>
                <?= htmlspecialchars($_SESSION['nama'] ?? 'Pengguna') ?>
            <?php else: ?>
                Menu
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            <?php if ($sudah_login): ?>
                <?php if ($is_admin): ?>
                    <a class="list-group-item list-group-item-action" href="admin/dashboard.php">Dashboard Admin</a>
                <?php endif; ?>
                <a class="list-group-item list-group-item-action" href="riwayat.php">Riwayat Pesanan</a>
                <a class="list-group-item list-group-item-action" href="profil.php">Profil Akun</a>
                <a class="list-group-item list-group-item-action text-danger" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="list-group-item list-group-item-action" href="login.php">Login</a>
                <a class="list-group-item list-group-item-action" href="register.php">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<main class="container py-4">
