<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guard: hanya admin yang boleh masuk
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Cafe Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar atas -->
<nav class="navbar navbar-dark bg-dark border-bottom px-3">
    <span class="navbar-brand mb-0 h1">☕ Cafe Kita – Admin</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <nav class="col-md-2 bg-light border-end min-vh-100 py-3 px-0">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active fw-bold' : 'text-dark' ?>"
                       href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'kelola_menu.php' ? 'active fw-bold' : 'text-dark' ?>"
                       href="kelola_menu.php">🍽️ Kelola Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'kelola_pesanan.php' ? 'active fw-bold' : 'text-dark' ?>"
                       href="kelola_pesanan.php">📦 Kelola Pesanan</a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-secondary" href="../index.php">← Lihat Toko</a>
                </li>
            </ul>
        </nav>

        <!-- Konten utama -->
        <main class="col-md-10 py-4 px-4">
