<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$id_user = (int) $_SESSION['id_user'];

$id_menu = (int) ($_POST['id_menu'] ?? 0);
$jumlah  = (int) ($_POST['jumlah']  ?? 1);

if ($id_menu <= 0 || $jumlah <= 0) {
    header('Location: index.php');
    exit;
}

$query_menu = mysqli_query($koneksi, "SELECT id_menu, nama_menu, harga FROM menus WHERE id_menu = $id_menu");
if (!$query_menu) {
    die(mysqli_error($koneksi));
}
$menu = mysqli_fetch_assoc($query_menu);

if (!$menu) {
    header('Location: index.php');
    exit;
}

$subtotal    = $menu['harga'] * $jumlah;
$total_harga = $subtotal;

if (isset($_POST['bayar'])) {
    $metode_pembayaran = mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']);

    $query_order = mysqli_query($koneksi, "INSERT INTO orders (id_user, total_harga, metode_pembayaran, status_pesanan) VALUES ($id_user, $total_harga, '$metode_pembayaran', 'Pending')");
    if (!$query_order) {
        die(mysqli_error($koneksi));
    }
    $id_order = mysqli_insert_id($koneksi);

    $query_item = mysqli_query($koneksi, "INSERT INTO order_items (id_order, id_menu, jumlah, subtotal) VALUES ($id_order, $id_menu, $jumlah, $subtotal)");
    if (!$query_item) {
        die(mysqli_error($koneksi));
    }

    header('Location: riwayat.php');
    exit;
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <h2 class="mb-4">Ringkasan Pesanan</h2>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Menu</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($menu['nama_menu']) ?></td>
                    <td class="text-center"><?= $jumlah ?></td>
                    <td class="text-end">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total Harga</th>
                    <th class="text-end">Rp <?= number_format($total_harga, 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>

        <form method="POST" action="checkout.php">
            <input type="hidden" name="id_menu" value="<?= $id_menu ?>">
            <input type="hidden" name="jumlah"  value="<?= $jumlah ?>">

            <div class="mb-3">
                <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="Cash">Cash</option>
                    <option value="QRIS">QRIS</option>
                </select>
            </div>

            <button type="submit" name="bayar" class="btn btn-primary">Bayar Sekarang</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
