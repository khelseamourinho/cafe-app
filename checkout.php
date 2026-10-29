<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/koneksi.php';
include 'config/biaya.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$id_user = (int) $_SESSION['id_user'];

// Jika keranjang kosong, kembali ke katalog
if (empty($_SESSION['keranjang'])) {
    header('Location: index.php');
    exit;
}

$keranjang = $_SESSION['keranjang'];

// ─── PROSES PEMBAYARAN ───────────────────────────────────────────────────────
if (isset($_POST['bayar'])) {
    $metode_pembayaran = mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']);
    $tipe_order        = mysqli_real_escape_string($koneksi, $_POST['tipe_order']);

    // Hitung ulang total dari DB (keamanan)
    $ids         = implode(',', array_map('intval', array_keys($keranjang)));
    $res_menu    = mysqli_query($koneksi, "SELECT id_menu, harga FROM menus WHERE id_menu IN ($ids)");
    $subtotal_semua = 0;
    $data_item      = [];

    while ($m = mysqli_fetch_assoc($res_menu)) {
        $id_m    = (int) $m['id_menu'];
        $jml     = (int) $keranjang[$id_m];
        $sub     = $m['harga'] * $jml;
        $subtotal_semua += $sub;
        $data_item[] = ['id_menu' => $id_m, 'jumlah' => $jml, 'subtotal' => $sub];
    }

    $pajak          = (int) round($subtotal_semua * $pajak_persen / 100);
    $biaya_layanan  = (int) round($subtotal_semua * $biaya_layanan_persen / 100);
    $kemasan        = ($tipe_order === 'take_away') ? (int) $biaya_kemasan : 0;
    $total_harga    = $subtotal_semua + $pajak + $biaya_layanan + $kemasan;

    // Insert order
    $query_order = mysqli_query($koneksi,
        "INSERT INTO orders (id_user, total_harga, metode_pembayaran, status_pesanan, tipe_order)
         VALUES ($id_user, $total_harga, '$metode_pembayaran', 'Pending', '$tipe_order')"
    );
    if (!$query_order) die(mysqli_error($koneksi));
    $id_order = mysqli_insert_id($koneksi);

    // Insert order items
    foreach ($data_item as $item) {
        mysqli_query($koneksi,
            "INSERT INTO order_items (id_order, id_menu, jumlah, subtotal)
             VALUES ($id_order, {$item['id_menu']}, {$item['jumlah']}, {$item['subtotal']})"
        ) or die(mysqli_error($koneksi));
    }

    // Kosongkan keranjang
    $_SESSION['keranjang'] = [];

    // Redirect sesuai metode pembayaran
    if ($metode_pembayaran === 'QRIS') {
        header("Location: riwayat.php?qris=$id_order");
    } else {
        header('Location: riwayat.php');
    }
    exit;
}

// ─── TAMPILKAN RINGKASAN ─────────────────────────────────────────────────────
$ids      = implode(',', array_map('intval', array_keys($keranjang)));
$res_menu = mysqli_query($koneksi, "SELECT * FROM menus WHERE id_menu IN ($ids)");
if (!$res_menu) die(mysqli_error($koneksi));

$daftar_item    = [];
$subtotal_semua = 0;

while ($m = mysqli_fetch_assoc($res_menu)) {
    $id_m = (int) $m['id_menu'];
    $jml  = (int) $keranjang[$id_m];
    $sub  = $m['harga'] * $jml;
    $subtotal_semua += $sub;
    $daftar_item[] = [
        'id_menu'   => $id_m,
        'nama_menu' => $m['nama_menu'],
        'harga'     => $m['harga'],
        'gambar'    => $m['gambar'] ?? '',
        'jumlah'    => $jml,
        'subtotal'  => $sub,
    ];
}

$pajak         = (int) round($subtotal_semua * $pajak_persen / 100);
$biaya_layanan = (int) round($subtotal_semua * $biaya_layanan_persen / 100);
$grand_total   = $subtotal_semua + $pajak + $biaya_layanan;

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4">Pilih Metode Pembayaran</h2>

        <div class="card mb-4">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th colspan="2">Menu</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_item as $item): ?>
                        <tr>
                            <td style="width:52px;padding:8px">
                                <?php if (!empty($item['gambar'])): ?>
                                    <img src="<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>" class="rounded" style="width:44px;height:44px;object-fit:cover">
                                <?php else: ?>
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle"><?= htmlspecialchars($item['nama_menu']) ?></td>
                            <td class="text-center align-middle"><?= $item['jumlah'] ?></td>
                            <td class="text-end align-middle">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td class="text-end align-middle">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>Rp <?= number_format($subtotal_semua, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-muted">
                    <span>Pajak (<?= $pajak_persen ?>%)</span>
                    <span>Rp <?= number_format($pajak, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-muted">
                    <span>Biaya Layanan (<?= $biaya_layanan_persen ?>%)</span>
                    <span>Rp <?= number_format($biaya_layanan, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-muted d-none" id="baris-kemasan">
                    <span>Biaya Kemasan (Take Away)</span>
                    <span>Rp <?= number_format($biaya_kemasan, 0, ',', '.') ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total</span>
                    <span id="grand-total-text">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Form Pembayaran -->
        <div class="card">
            <div class="card-body">

                <!-- Pilih Tipe Order -->
                <h5 class="card-title mb-3">Tipe Pesanan</h5>
                <div class="mb-4">
                    <label class="d-block mb-2 tipe-card" style="cursor:pointer">
                        <input class="d-none" type="radio" name="tipe_order_visual" value="dine_in" checked>
                        <div class="border rounded-3 p-3 d-flex align-items-center gap-3 tipe-card-inner" style="border-width:2px!important;transition:all .15s ease">
                            <div class="d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10" style="width:48px;height:48px;flex-shrink:0">
                                <span style="font-size:1.5rem">🍽️</span>
                            </div>
                            <div>
                                <div class="fw-bold">Dine In</div>
                                <div class="text-muted small">Makan di tempat</div>
                            </div>
                        </div>
                    </label>
                    <label class="d-block tipe-card" style="cursor:pointer">
                        <input class="d-none" type="radio" name="tipe_order_visual" value="take_away">
                        <div class="border rounded-3 p-3 d-flex align-items-center gap-3 tipe-card-inner" style="border-width:2px!important;transition:all .15s ease">
                            <div class="d-flex align-items-center justify-content-center rounded-3 bg-warning bg-opacity-10" style="width:48px;height:48px;flex-shrink:0">
                                <span style="font-size:1.5rem">🥡</span>
                            </div>
                            <div>
                                <div class="fw-bold">Take Away</div>
                                <div class="text-muted small">Bawa pulang · +Rp <?= number_format($biaya_kemasan, 0, ',', '.') ?> biaya kemasan</div>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Pilih Metode Pembayaran -->
                <h5 class="card-title mb-3">Pilih Metode Pembayaran</h5>
                <form method="POST" action="checkout.php" id="formBayar">
                    <input type="hidden" name="tipe_order" id="tipe_order_input" value="dine_in">
                    <div class="mb-3">
                        <label class="d-block mb-2 metode-card" style="cursor:pointer">
                            <input class="d-none" type="radio" name="metode_pembayaran" value="Cash" checked>
                            <div class="border rounded-3 p-4 d-flex align-items-center gap-3 metode-card-inner" style="border-width:2px!important;transition:all .15s ease">
                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10" style="width:56px;height:56px;flex-shrink:0">
                                    <span style="font-size:1.8rem">💵</span>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5">Bayar Tunai di Kasir</div>
                                    <div class="text-muted">Lakukan pembayaran langsung di kasir saat mengambil pesanan.</div>
                                </div>
                            </div>
                        </label>
                        <label class="d-block metode-card" style="cursor:pointer">
                            <input class="d-none" type="radio" name="metode_pembayaran" value="QRIS">
                            <div class="border rounded-3 p-4 d-flex align-items-center gap-3 metode-card-inner" style="border-width:2px!important;transition:all .15s ease">
                                <div class="d-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10" style="width:56px;height:56px;flex-shrink:0">
                                    <span style="font-size:1.8rem">📱</span>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5">QRIS</div>
                                    <div class="text-muted">Scan kode QRIS untuk pembayaran digital.</div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="bayar" class="btn btn-primary btn-lg flex-fill">Bayar Sekarang</button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">Kembali</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Script: highlight card & hitung biaya kemasan -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var grandTotalBase = <?= $grand_total ?>;
            var biayaKemasan   = <?= $biaya_kemasan ?>;

            function formatRupiah(angka) {
                return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // ─── Tipe Order highlight & biaya kemasan ───
            function updateTipeHighlight() {
                document.querySelectorAll('.tipe-card').forEach(function(card) {
                    var radio = card.querySelector('input[type="radio"]');
                    var inner = card.querySelector('.tipe-card-inner');
                    if (radio.checked) {
                        inner.style.borderColor = '#198754';
                        inner.style.backgroundColor = '#f0fdf4';
                    } else {
                        inner.style.borderColor = '#dee2e6';
                        inner.style.backgroundColor = '';
                    }
                });

                var tipeOrder = document.querySelector('input[name="tipe_order_visual"]:checked').value;
                document.getElementById('tipe_order_input').value = tipeOrder;

                var barisKemasan  = document.getElementById('baris-kemasan');
                var grandTotalEl  = document.getElementById('grand-total-text');

                if (tipeOrder === 'take_away') {
                    barisKemasan.classList.remove('d-none');
                    grandTotalEl.textContent = formatRupiah(grandTotalBase + biayaKemasan);
                } else {
                    barisKemasan.classList.add('d-none');
                    grandTotalEl.textContent = formatRupiah(grandTotalBase);
                }
            }
            updateTipeHighlight();
            document.querySelectorAll('.tipe-card input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', updateTipeHighlight);
            });

            // ─── Metode Pembayaran highlight ───
            function updateMetodeHighlight() {
                document.querySelectorAll('.metode-card').forEach(function(card) {
                    var radio = card.querySelector('input[type="radio"]');
                    var inner = card.querySelector('.metode-card-inner');
                    if (radio.checked) {
                        inner.style.borderColor = '#0d6efd';
                        inner.style.backgroundColor = '#f0f6ff';
                    } else {
                        inner.style.borderColor = '#dee2e6';
                        inner.style.backgroundColor = '';
                    }
                });
            }
            updateMetodeHighlight();
            document.querySelectorAll('.metode-card input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', updateMetodeHighlight);
            });
        });
        </script>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
