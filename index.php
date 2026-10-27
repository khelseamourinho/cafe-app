<?php
// Semua logika PHP (session + redirect) HARUS selesai sebelum header.php output HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require 'config/koneksi.php';
require 'includes/header.php';
?>

<h1 class="h3 mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?></h1>
<p class="text-muted mb-4">Silakan pilih menu favorit Anda di bawah ini.</p>

<?php
$query_menu = "SELECT * FROM menus";
$hasil_menu = mysqli_query($koneksi, $query_menu) or die("Query gagal: " . mysqli_error($koneksi));
?>

<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php while ($menu = mysqli_fetch_assoc($hasil_menu)): ?>
        <div class="col">
            <div class="card h-100">
                <?php if (!empty($menu['gambar'])): ?>
                    <img src="<?= htmlspecialchars($menu['gambar']) ?>" class="card-img-top" alt="<?= htmlspecialchars($menu['nama_menu']) ?>">
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($menu['nama_menu']) ?></h5>
                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($menu['kategori']) ?></span>
                    <p class="card-text"><?= htmlspecialchars($menu['deskripsi'] ?? '') ?></p>
                    <p class="fw-bold mb-0">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></p>
                </div>

                <div class="card-footer bg-white">
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" name="jumlah" value="1" min="1" max="10" class="form-control form-control-sm" style="width:70px">
                            <button type="submit" class="btn btn-primary flex-fill">Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php require 'includes/footer.php'; ?>
