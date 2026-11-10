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
        // Jika request AJAX, kembalikan error JSON
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($is_ajax) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
            exit;
        }
        die('Status tidak valid.');
    }

    // Ambil status sekarang untuk validasi alur
    $res_cek     = mysqli_query($koneksi, "SELECT status_pesanan FROM orders WHERE id_order = $id_order");
    $status_lama = mysqli_fetch_assoc($res_cek)['status_pesanan'] ?? '';

    // Aturan: tidak bisa mundur ke status sebelumnya, tidak bisa ubah yang sudah Selesai
    $urutan = array_flip($status_valid); // ['Pending'=>0, 'Sedang Dibuat'=>1, ...]
    $error_status = '';
    if ($status_lama === 'Selesai') {
        $error_status = 'Pesanan yang sudah Selesai tidak dapat diubah statusnya.';
    } elseif ($urutan[$status_baru] < $urutan[$status_lama]) {
        $error_status = 'Status tidak dapat dimundurkan dari "' . $status_lama . '" ke "' . $status_baru . '".';
    }

    // Pemetaan badge Bootstrap sesuai status
    $warna_badge = [
        'Pending'       => 'bg-warning text-dark',
        'Sedang Dibuat' => 'bg-info text-dark',
        'Siap Pick-up'  => 'bg-primary',
        'Selesai'       => 'bg-success'
    ];

    // ─── Deteksi apakah request ini berasal dari AJAX ───────────────────────
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($error_status === '') {
        mysqli_query($koneksi,
            "UPDATE orders SET status_pesanan = '$status_baru' WHERE id_order = $id_order"
        ) or die(mysqli_error($koneksi));

        if ($is_ajax) {
            // Response JSON untuk AJAX: kirim data yang dibutuhkan JS untuk update DOM
            header('Content-Type: application/json');
            echo json_encode([
                'success'     => true,
                'message'     => 'Status pesanan berhasil diperbarui.',
                'id_order'    => $id_order,
                'status_baru' => $status_baru,
                'badge_class' => $warna_badge[$status_baru],
            ]);
            exit;
        }
        // Fallback untuk non-AJAX (jika JS disabled)
        header('Location: kelola_pesanan.php?pesan=update');
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error_status]);
            exit;
        }
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
                 o.metode_pembayaran, o.status_pesanan, o.tipe_order, o.created_at
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
    <div class="alert alert-success alert-dismissible fade show" id="alertPesanServer">
        Status pesanan berhasil diperbarui.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($pesan === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" id="alertPesanServer">
        <?= htmlspecialchars($detail_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ─── Alert AJAX (disembunyikan secara default, ditampilkan oleh JS) ──────── -->
<div id="alertAjax" class="alert alert-dismissible fade show d-none" role="alert">
    <span id="alertAjaxMsg"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- ─── Filter Status + Tombol Refresh Manual ────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

    <!-- Filter Status (tetap seperti semula) -->
    <div class="d-flex gap-2 flex-wrap" id="filterStatusGroup">
        <a href="kelola_pesanan.php"
           class="btn btn-sm <?= $filter_status === '' ? 'btn-dark' : 'btn-outline-dark' ?>">Semua</a>
        <?php foreach ($status_list as $st): ?>
            <a href="?status=<?= urlencode($st) ?>"
               class="btn btn-sm <?= $filter_status === $st ? 'btn-dark' : 'btn-outline-secondary' ?>">
                <?= $st ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ─── Tombol Refresh Manual: Kontras & Jelas ───────────────────────────── -->
    <button type="button" id="btnManualRefresh"
            class="btn btn-warning fw-semibold shadow-sm d-flex align-items-center gap-2"
            title="Segarkan daftar pesanan tanpa reload halaman">
        <!-- Ikon refresh Bootstrap Icons (SVG inline agar bisa dianimasikan) -->
        <svg id="iconRefresh" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
             fill="currentColor" viewBox="0 0 16 16" style="transition: transform 0.5s ease;">
            <path fill-rule="evenodd"
                  d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
        </svg>
        Segarkan Pesanan
    </button>
</div>

<!-- ─── Daftar Pesanan (container ini yang di-refresh parsial oleh AJAX) ─────── -->
<div id="daftarPesananContainer">
<?php if (empty($daftar_order)): ?>
    <div class="alert alert-info">
        Tidak ada pesanan<?= $filter_status ? ' dengan status <strong>' . htmlspecialchars($filter_status) . '</strong>' : '' ?>.
    </div>
<?php endif; ?>

<?php foreach ($daftar_order as $order):
    $urutan_status = array_flip($status_list);
    $sudah_selesai = $order['status_pesanan'] === 'Selesai';
?>
<!-- Kartu pesanan: diberi id unik dan data-status untuk manipulasi DOM via JS -->
<div class="card mb-3" id="order-card-<?= $order['id_order'] ?>" data-status="<?= htmlspecialchars($order['status_pesanan']) ?>">
    <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <strong>Pesanan #<?= $order['id_order'] ?></strong>
            &nbsp;·&nbsp;
            <span><?= htmlspecialchars($order['nama_customer']) ?></span>
            &nbsp;·&nbsp;
            <span class="badge <?= ($order['tipe_order'] ?? '') === 'take_away' ? 'bg-warning text-dark' : 'bg-success' ?> small">
                <?= ($order['tipe_order'] ?? '') === 'take_away' ? '🥡 Take Away' : '🍽️ Dine In' ?>
            </span><br>
            <small class="text-muted"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></small>
            &nbsp;·&nbsp;
            <small class="text-muted"><?= $order['metode_pembayaran'] ?></small>
        </div>
        <!-- Badge status: diberi id unik agar bisa diupdate DOM tanpa reload -->
        <span class="badge <?= $warna_badge[$order['status_pesanan']] ?> fs-6" id="badge-<?= $order['id_order'] ?>">
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
            <!-- Form AJAX: onsubmit dicegat oleh JS, tidak akan reload halaman -->
            <form method="POST" action="kelola_pesanan.php" class="form-ubah-status"
                  data-order-id="<?= $order['id_order'] ?>">
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
                    <button type="submit" class="btn btn-sm btn-primary btn-simpan-status">Simpan</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div><!-- /#daftarPesananContainer -->

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   kelola_pesanan.php — JavaScript AJAX
   1. AJAX Submit Status  : ubah status tanpa reload, scroll tetap stabil
   2. AJAX Refresh Manual : tombol Segarkan Pesanan, scroll tetap stabil
   ═══════════════════════════════════════════════════════════════════════════ */

// ─── Pemetaan warna badge (mirror dari PHP $warna_badge) ─────────────────────
const WARNA_BADGE = {
    'Pending'      : 'bg-warning text-dark',
    'Sedang Dibuat': 'bg-info text-dark',
    'Siap Pick-up' : 'bg-primary',
    'Selesai'      : 'bg-success'
};

// ─── Helper: tampilkan alert AJAX sementara ──────────────────────────────────
function tampilkanAlert(pesan, tipe) {
    var alertEl  = document.getElementById('alertAjax');
    var alertMsg = document.getElementById('alertAjaxMsg');
    // Hapus kelas warna sebelumnya
    alertEl.classList.remove('alert-success', 'alert-danger', 'alert-warning', 'd-none');
    alertEl.classList.add('alert-' + tipe);
    alertMsg.textContent = pesan;
    // Auto-dismiss setelah 4 detik
    setTimeout(function() {
        alertEl.classList.add('d-none');
    }, 4000);
}

/* ═══════════════════════════════════════════════════════════════
   FITUR 1 — AJAX Submit Status (tanpa reload, scroll stabil)
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('submit', function(e) {
    // Tangkap hanya form dengan class .form-ubah-status
    if (!e.target.classList.contains('form-ubah-status')) return;
    e.preventDefault(); // Hentikan submit konvensional (cegah reload halaman)

    var form      = e.target;
    var select    = form.querySelector('select[name="status_pesanan"]');
    var statusBaru = select.options[select.selectedIndex].text;
    var orderId   = form.dataset.orderId;

    // Konfirmasi sebelum menyimpan (sama seperti behavior lama)
    if (!confirm('Yakin ingin mengubah status pesanan #' + orderId + ' menjadi "' + statusBaru + '"?')) {
        return;
    }

    // Nonaktifkan tombol agar tidak diklik ganda
    var btnSimpan = form.querySelector('.btn-simpan-status');
    btnSimpan.disabled  = true;
    btnSimpan.textContent = 'Menyimpan...';

    var formData = new FormData(form);

    // Kirim request AJAX ke backend yang sama
    fetch('kelola_pesanan.php', {
        method : 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, // Penanda AJAX untuk PHP
        body   : formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // ── Update badge warna & teks secara instan ──────────────────
            var badgeEl = document.getElementById('badge-' + data.id_order);
            if (badgeEl) {
                // Hapus semua class warna badge lama
                badgeEl.className = 'badge fs-6 ' + (WARNA_BADGE[data.status_baru] || 'bg-secondary');
                badgeEl.textContent = data.status_baru;
            }
            // Update atribut data-status pada kartu
            var cardEl = document.getElementById('order-card-' + data.id_order);
            if (cardEl) {
                cardEl.dataset.status = data.status_baru;
            }
            // Nonaktifkan opsi status yang sudah terlewati pada <select>
            var statusUrutan = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
            var idxBaru = statusUrutan.indexOf(data.status_baru);
            var opts = select.options;
            for (var i = 0; i < opts.length; i++) {
                if (statusUrutan.indexOf(opts[i].value) < idxBaru) {
                    opts[i].disabled = true;
                }
            }
            // Jika status sudah Selesai, sembunyikan form dan tampilkan teks
            if (data.status_baru === 'Selesai') {
                var formWrapper = form.closest('.card-body');
                if (formWrapper) {
                    form.outerHTML = '<p class="text-muted small mb-0">✅ Pesanan ini sudah selesai dan tidak dapat diubah.</p>';
                }
            }
            tampilkanAlert('✅ ' + data.message, 'success');
        } else {
            tampilkanAlert('❌ ' + (data.message || 'Gagal memperbarui status.'), 'danger');
            btnSimpan.disabled   = false;
            btnSimpan.textContent = 'Simpan';
        }
    })
    .catch(function() {
        tampilkanAlert('❌ Gagal menghubungi server. Periksa koneksi Anda.', 'danger');
        btnSimpan.disabled   = false;
        btnSimpan.textContent = 'Simpan';
    });
});

/* ═══════════════════════════════════════════════════════════════
   FITUR 2 — AJAX Refresh Manual (Tombol Segarkan Pesanan)
   Memperbarui #daftarPesananContainer tanpa reload browser.
   Posisi scroll admin dipertahankan setelah refresh parsial.
   ═══════════════════════════════════════════════════════════════ */
var sedangRefresh = false; // Flag: cegah klik ganda saat loading

document.getElementById('btnManualRefresh').addEventListener('click', function() {
    if (sedangRefresh) return; // Abaikan spam klik
    sedangRefresh = true;

    var btn      = this;
    var iconSvg  = document.getElementById('iconRefresh');

    // Animasi putaran pada ikon refresh
    iconSvg.style.transform    = 'rotate(360deg)';
    iconSvg.style.transition   = 'transform 0.6s ease';
    btn.disabled               = true;

    // Simpan posisi scroll saat ini sebelum update DOM
    var savedScrollTop = window.scrollY || document.documentElement.scrollTop;

    // Baca filter aktif dari URL agar refresh tetap mempertahankan filter
    var currentUrl = window.location.search; // contoh: "?status=Pending"

    // Ambil HTML halaman yang sama dengan filter aktif
    fetch('kelola_pesanan.php' + currentUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest-PartialRefresh' }
        // Catatan: header ini hanya untuk logging; PHP tetap merender halaman penuh
    })
    .then(function(res) { return res.text(); })
    .then(function(html) {
        // Parsing HTML dengan DOMParser (aman, tanpa eval)
        var parser  = new DOMParser();
        var doc     = parser.parseFromString(html, 'text/html');

        // Ambil hanya konten #daftarPesananContainer dari halaman baru
        var newContainer = doc.getElementById('daftarPesananContainer');
        var oldContainer = document.getElementById('daftarPesananContainer');

        if (newContainer && oldContainer) {
            oldContainer.innerHTML = newContainer.innerHTML;
        }

        // ── Kembalikan posisi scroll ke koordinat semula ──────────────────
        window.scrollTo({ top: savedScrollTop, behavior: 'instant' });

        tampilkanAlert('🔄 Daftar pesanan telah diperbarui.', 'success');
    })
    .catch(function() {
        tampilkanAlert('❌ Gagal memuat ulang daftar pesanan.', 'danger');
    })
    .finally(function() {
        // Reset animasi ikon dan aktifkan kembali tombol
        setTimeout(function() {
            iconSvg.style.transform = 'rotate(0deg)';
            iconSvg.style.transition = 'transform 0s';
        }, 100);
        btn.disabled  = false;
        sedangRefresh = false;
    });
});
</script>

<?php require '../includes/footer.php'; ?>
