<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/koneksi.php';

// --- Statistik ringkas ---
$total_menu    = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM menus"))[0];
$total_pesanan = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM orders"))[0];
$total_pending = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM orders WHERE status_pesanan = 'Pending'"))[0];
$total_revenue = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) FROM orders WHERE status_pesanan = 'Selesai'"))[0];

// --- 5 pesanan terbaru ---
$query_terbaru = "SELECT o.id_order, u.nama, o.total_harga, o.status_pesanan, o.created_at
                  FROM orders o
                  JOIN users u ON o.id_user = u.id_user
                  ORDER BY o.created_at DESC
                  LIMIT 5";
$hasil_terbaru = mysqli_query($koneksi, $query_terbaru) or die(mysqli_error($koneksi));

$warna_badge = [
    'Pending'       => 'bg-warning text-dark',
    'Sedang Dibuat' => 'bg-info text-dark',
    'Siap Pick-up'  => 'bg-primary',
    'Selesai'       => 'bg-success'
];

$base_path = '../';
require '../includes/header.php';
?>

<h1 class="h3 mb-1">Dashboard</h1>
<p class="text-muted mb-4">Selamat datang kembali, <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>.</p>

<!-- Kartu Statistik -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total Menu</p>
                <div class="fs-2 fw-bold"><?= $total_menu ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total Pesanan</p>
                <div class="fs-2 fw-bold"><?= $total_pesanan ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Pesanan Pending</p>
                <div class="fs-2 fw-bold text-warning"><?= $total_pending ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Total Pendapatan</p>
                <div class="fs-5 fw-bold text-success">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Pesanan Terbaru -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Pesanan Terbaru</h5>
    <a href="kelola_pesanan.php" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($baris = mysqli_fetch_assoc($hasil_terbaru)): ?>
                <tr>
                    <td class="text-muted small"><?= $baris['id_order'] ?></td>
                    <td><?= htmlspecialchars($baris['nama']) ?></td>
                    <td class="text-end">Rp <?= number_format($baris['total_harga'], 0, ',', '.') ?></td>
                    <td class="text-center">
                        <span class="badge <?= $warna_badge[$baris['status_pesanan']] ?>">
                            <?= $baris['status_pesanan'] ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= date('d M Y, H:i', strtotime($baris['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
