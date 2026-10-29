<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/koneksi.php';

// ─── UPDATE STATUS PESANAN ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_order'], $_POST['status_pesanan'])) {
    $id_order    = (int) $_POST['id_order'];
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_pesanan']);

    $status_valid = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
    if (!in_array($status_baru, $status_valid)) {
        die('Status tidak valid.');
    }

    // Ambil status sekarang untuk validasi alur
    $res_cek   = mysqli_query($koneksi, "SELECT status_pesanan FROM orders WHERE id_order = $id_order");
    $status_lama = mysqli_fetch_assoc($res_cek)['status_pesanan'] ?? '';

    // Aturan: tidak bisa mundur ke status sebelumnya, tidak bisa ubah yang sudah Selesai
    $urutan = array_flip($status_valid); // ['Pending'=>0, 'Sedang Dibuat'=>1, ...]
    $error_status = '';
    if ($status_lama === 'Selesai') {
        $error_status = 'Pesanan yang sudah Selesai tidak dapat diubah statusnya.';
    } elseif ($urutan[$status_baru] < $urutan[$status_lama]) {
        $error_status = 'Status tidak dapat dimundurkan dari "' . $status_lama . '" ke "' . $status_baru . '".';
    }

    if ($error_status === '') {
        mysqli_query($koneksi,
            "UPDATE orders SET status_pesanan = '$status_baru' WHERE id_order = $id_order"
        ) or die(mysqli_error($koneksi));
        header('Location: kelola_pesanan.php?pesan=update');
    } else {
        header('Location: kelola_pesanan.php?pesan=error&detail=' . urlencode($error_status));
    }
    exit;
}

// ─── Filter status ────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? '';
$where = $filter_status
    ? "WHERE o.status_pesanan = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'"
    : '';

$query = "SELECT o.id_order, u.nama AS nama_customer, o.total_harga,
                 o.metode_pembayaran, o.status_pesanan, o.created_at
          FROM orders o
          JOIN users u ON o.id_user = u.id_user
          $where
          ORDER BY o.created_at DESC";
$hasil        = mysqli_query($koneksi, $query) or die(mysqli_error($koneksi));
$daftar_order = mysqli_fetch_all($hasil, MYSQLI_ASSOC);

// ─── Item tiap pesanan (dengan id_menu) ───────────────────────────────────────
$semua_item = [];
if (!empty($daftar_order)) {
    $ids      = implode(',', array_column($daftar_order, 'id_order'));
    $res_item = mysqli_query($koneksi,
        "SELECT oi.id_order, oi.id_menu, m.nama_menu, oi.jumlah, oi.subtotal
         FROM order_items oi
         JOIN menus m ON oi.id_menu = m.id_menu
         WHERE oi.id_order IN ($ids)"
    ) or die(mysqli_error($koneksi));
    while ($item = mysqli_fetch_assoc($res_item)) {
        $semua_item[$item['id_order']][] = $item;
    }
}

$status_list = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
$warna_badge = [
    'Pending'       => 'bg-warning text-dark',
    'Sedang Dibuat' => 'bg-info text-dark',
    'Siap Pick-up'  => 'bg-primary',
    'Selesai'       => 'bg-success'
];

$pesan        = $_GET['pesan']  ?? '';
$detail_error = $_GET['detail'] ?? '';
$base_path    = '../';
require '../includes/header.php';
?>

<h1 class="h3 mb-1">Kelola Pesanan</h1>
<p class="text-muted mb-4">Pantau dan perbarui status pesanan customer.</p>

<?php if ($pesan === 'update'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Status pesanan berhasil diperbarui.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($pesan === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($detail_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Status -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="kelola_pesanan.php"
       class="btn btn-sm <?= $filter_status === '' ? 'btn-dark' : 'btn-outline-dark' ?>">Semua</a>
    <?php foreach ($status_list as $st): ?>
        <a href="?status=<?= urlencode($st) ?>"
           class="btn btn-sm <?= $filter_status === $st ? 'btn-dark' : 'btn-outline-secondary' ?>">
            <?= $st ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Daftar Pesanan -->
<?php if (empty($daftar_order)): ?>
    <div class="alert alert-info">
        Tidak ada pesanan<?= $filter_status ? ' dengan status <strong>' . htmlspecialchars($filter_status) . '</strong>' : '' ?>.
    </div>
<?php endif; ?>

<?php foreach ($daftar_order as $order):
    $urutan_status = array_flip($status_list);
    $sudah_selesai = $order['status_pesanan'] === 'Selesai';
?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <strong>Pesanan #<?= $order['id_order'] ?></strong>
            &nbsp;·&nbsp;
            <span><?= htmlspecialchars($order['nama_customer']) ?></span><br>
            <small class="text-muted"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></small>
            &nbsp;·&nbsp;
            <small class="text-muted"><?= $order['metode_pembayaran'] ?></small>
        </div>
        <span class="badge <?= $warna_badge[$order['status_pesanan']] ?> fs-6">
            <?= $order['status_pesanan'] ?>
        </span>
    </div>

    <div class="card-body">
        <!-- Detail item pesanan -->
        <table class="table table-sm table-bordered mb-3">
            <thead class="table-light">
                <tr>
                    <th>ID Menu</th>
                    <th>Nama Menu</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($semua_item[$order['id_order']] ?? [] as $item): ?>
                <tr>
                    <td class="text-muted small">#<?= $item['id_menu'] ?></td>
                    <td><?= htmlspecialchars($item['nama_menu']) ?></td>
                    <td class="text-center"><?= $item['jumlah'] ?></td>
                    <td class="text-end">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- Ubah Status -->
        <?php if ($sudah_selesai): ?>
            <p class="text-muted small mb-0">✅ Pesanan ini sudah selesai dan tidak dapat diubah.</p>
        <?php else: ?>
            <form method="POST" action="kelola_pesanan.php"
                  onsubmit="return konfirmasiStatus(this)">
                <input type="hidden" name="id_order" value="<?= $order['id_order'] ?>">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="form-label mb-0 me-1 text-nowrap fw-semibold">Ubah Status:</label>
                    <select name="status_pesanan" class="form-select form-select-sm" style="max-width:200px">
                        <?php foreach ($status_list as $st): ?>
                            <option value="<?= $st ?>"
                                <?= $order['status_pesanan'] === $st ? 'selected' : '' ?>
                                <?= $urutan_status[$st] < $urutan_status[$order['status_pesanan']] ? 'disabled' : '' ?>>
                                <?= $st ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<script>
function konfirmasiStatus(form) {
    const select     = form.querySelector('select[name="status_pesanan"]');
    const statusBaru = select.options[select.selectedIndex].text;
    return confirm('Yakin ingin mengubah status pesanan ini menjadi "' + statusBaru + '"?');
}
</script>

<?php require '../includes/footer.php'; ?>
