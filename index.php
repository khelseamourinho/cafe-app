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

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$keranjang    = $_SESSION['keranjang'];
$total_item   = array_sum($keranjang);

require 'includes/header.php';
?>

<h1 class="h3 mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?></h1>
<p class="text-muted mb-4">Silakan pilih menu favorit Anda di bawah ini.</p>

<?php
$query_menu = "SELECT * FROM menus";
$hasil_menu = mysqli_query($koneksi, $query_menu) or die("Query gagal: " . mysqli_error($koneksi));
?>

<div class="row row-cols-1 row-cols-md-3 g-4" <?php if ($total_item > 0): ?>style="padding-bottom: 80px"<?php endif; ?>>
    <?php while ($menu = mysqli_fetch_assoc($hasil_menu)): ?>
        <?php $qty_di_keranjang = $keranjang[$menu['id_menu']] ?? 0; ?>
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

                <div class="card-footer bg-white" id="footer-menu-<?= $menu['id_menu'] ?>">
                    <?php if ($qty_di_keranjang > 0): ?>
                        <!-- Mode: Sudah di keranjang — tampilkan counter -->
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <form action="keranjang_proses.php" method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="update">
                                <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                <input type="hidden" name="jumlah" value="<?= $qty_di_keranjang - 1 ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">
                                    −
                                </button>
                            </form>

                            <span class="fw-bold fs-5 mx-2"><?= $qty_di_keranjang ?></span>

                            <form action="keranjang_proses.php" method="POST" class="d-inline">
                                <input type="hidden" name="aksi" value="update">
                                <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                <input type="hidden" name="jumlah" value="<?= $qty_di_keranjang + 1 ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">
                                    +
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Mode: Belum dipesan — tampilkan tombol Pesan -->
                        <form action="keranjang_proses.php" method="POST">
                            <input type="hidden" name="aksi" value="tambah">
                            <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                            <input type="hidden" name="jumlah" value="1">
                            <button type="submit" class="btn btn-primary w-100">Pesan</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php if ($total_item > 0): ?>
<!-- Floating bar: Lanjut ke Checkout -->
<div class="position-fixed bottom-0 start-0 end-0 bg-white border-top shadow-lg py-3" style="z-index:1050">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-semibold"><?= $total_item ?> item</span>
            <span class="text-muted">di pesanan Anda</span>
        </div>
        <a href="checkout.php" class="btn btn-primary">
            Lanjut pilih metode pembayaran &rarr;
        </a>
    </div>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
