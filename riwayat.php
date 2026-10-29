<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];

// Cek apakah baru saja bayar QRIS
$qris_order_id = (int) ($_GET['qris'] ?? 0);
$qris_total    = 0;

if ($qris_order_id > 0) {
    $res_qris = mysqli_query($koneksi,
        "SELECT total_harga FROM orders WHERE id_order = $qris_order_id AND id_user = $id_user AND metode_pembayaran = 'QRIS'"
    );
    $data_qris = mysqli_fetch_assoc($res_qris);
    if ($data_qris) {
        $qris_total = (int) $data_qris['total_harga'];
    }
}

$query = "SELECT o.id_order, o.total_harga, o.metode_pembayaran, o.status_pesanan, o.created_at,
                 m.nama_menu, oi.jumlah, oi.subtotal
          FROM orders o
          JOIN order_items oi ON o.id_order = oi.id_order
          JOIN menus m ON oi.id_menu = m.id_menu
          WHERE o.id_user = '$id_user'
          ORDER BY o.created_at DESC, o.id_order DESC";
$hasil = mysqli_query($koneksi, $query) or die(mysqli_error($koneksi));

$daftar_pesanan = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $id_order = $baris['id_order'];
    if (!isset($daftar_pesanan[$id_order])) {
        $daftar_pesanan[$id_order] = [
            'total_harga'       => $baris['total_harga'],
            'metode_pembayaran' => $baris['metode_pembayaran'],
            'status_pesanan'    => $baris['status_pesanan'],
            'created_at'        => $baris['created_at'],
            'items'             => []
        ];
    }
    $daftar_pesanan[$id_order]['items'][] = [
        'nama_menu' => $baris['nama_menu'],
        'jumlah'    => $baris['jumlah'],
        'subtotal'  => $baris['subtotal']
    ];
}

$warna_badge = [
    'Pending'       => 'bg-warning text-dark',
    'Sedang Dibuat' => 'bg-info text-dark',
    'Siap Pick-up'  => 'bg-primary',
    'Selesai'       => 'bg-success'
];

include 'includes/header.php';
?>

<div class="container my-4">
    <h3 class="mb-4">Riwayat Pesanan</h3>

    <?php if (empty($daftar_pesanan)) { ?>
        <div class="alert alert-info">Belum ada pesanan. <a href="index.php">Lihat menu</a></div>
    <?php } ?>

    <?php foreach ($daftar_pesanan as $id_order => $pesanan) { ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Pesanan #<?= $id_order ?></strong><br>
                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($pesanan['created_at'])) ?></small>
                </div>
                <span class="badge <?= $warna_badge[$pesanan['status_pesanan']] ?>">
                    <?= $pesanan['status_pesanan'] ?>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesanan['items'] as $item) { ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_menu']) ?></td>
                                <td class="text-center"><?= $item['jumlah'] ?></td>
                                <td class="text-end">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span>Pembayaran: <strong><?= $pesanan['metode_pembayaran'] ?></strong></span>
                <div class="d-flex align-items-center gap-2">
                    <span>Total: <strong>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></strong></span>
                    <?php if ($pesanan['metode_pembayaran'] === 'QRIS' && $pesanan['status_pesanan'] === 'Pending'): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="tampilkanQRIS(<?= $pesanan['total_harga'] ?>)">
                            Tampilkan QRIS
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<!-- Modal QRIS -->
<div class="modal fade" id="modalQRIS" tabindex="-1" aria-labelledby="labelQRIS" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelQRIS">Pembayaran QRIS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-3">Scan kode QR di bawah ini untuk melakukan pembayaran</p>
                <div class="mb-3">
                    <img src="assets/qris.png" alt="Kode QRIS" class="img-fluid border rounded p-2" style="max-width:280px">
                </div>
                <div class="alert alert-info">
                    <strong>Total yang harus dibayar:</strong><br>
                    <span class="fs-4 fw-bold" id="qrisTotalBayar">Rp 0</span>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Selesai</button>
            </div>
        </div>
    </div>
</div>

<script>
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function tampilkanQRIS(total) {
    document.getElementById('qrisTotalBayar').textContent = formatRupiah(total);
    var modal = new bootstrap.Modal(document.getElementById('modalQRIS'));
    modal.show();
}

// Auto-show modal QRIS jika baru saja bayar QRIS
<?php if ($qris_total > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    tampilkanQRIS(<?= $qris_total ?>);
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>