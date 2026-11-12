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


<!-- ══════ POP-UP MODAL KONFIRMASI UBAH STATUS PESANAN ══════════════════════ -->
<!--
    Modal ini ditampilkan saat admin menekan tombol "Simpan" pada form ubah status.
    Data pesanan (nomor, nama customer, status lama, status baru) diinjeksikan oleh JS.
    Eksekusi AJAX Fetch hanya dikirim setelah admin mengklik tombol konfirmasi di sini.
-->
<div class="modal fade" id="modalKonfirmasiStatus" tabindex="-1" aria-labelledby="labelKonfirmasiStatus" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="labelKonfirmasiStatus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="text-warning me-2 mb-1" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                    Konfirmasi Perubahan Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2 pb-3">
                <p class="text-muted mb-3 small">Pastikan kamu sudah memilih status yang tepat sebelum melanjutkan.</p>
                <div class="rounded-3 border p-3 bg-light mb-0">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Nomor Pesanan</span>
                        <strong id="konfirm-id-order">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Customer</span>
                        <strong id="konfirm-nama-customer">—</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Status saat ini</span>
                        <span id="konfirm-status-lama" class="badge fs-6">—</span>
                    </div>
                    <div class="text-center text-muted my-2" style="font-size: 1.3rem;">↓</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-semibold">Status baru</span>
                        <span id="konfirm-status-baru" class="badge fs-6">—</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnKonfirmasiUbahStatus">
                    Ya, Ubah Status
                </button>
            </div>
        </div>
    </div>
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

/* ═══════════════════════════════════════════════════════════════════════
   VARIABEL KONTEKS MODAL KONFIRMASI
   Menyimpan referensi form aktif & FormData saat modal konfirmasi terbuka,
   agar bisa digunakan oleh handler tombol konfirmasi di dalam modal.
   ═══════════════════════════════════════════════════════════════════════ */
var _formAktif      = null;  // Form .form-ubah-status yang sedang dikonfirmasi
var _formDataAktif  = null;  // FormData dari form tersebut
var _selectAktif    = null;  // Elemen <select> dari form tersebut
var _btnSimpanAktif = null;  // Tombol simpan pada form tersebut
var _statusSebelumnya = null; // Nilai select sebelum diganti (untuk reset saat batal)

// Referensi elemen Modal Bootstrap
var modalKonfirmasiEl = document.getElementById('modalKonfirmasiStatus');
var bsModalKonfirmasi = new bootstrap.Modal(modalKonfirmasiEl);

/* ═══════════════════════════════════════════════════════════════════════
   HELPER: Dapatkan label & class badge berdasarkan nilai status
   ═══════════════════════════════════════════════════════════════════════ */
function getBadgeClass(status) {
    return WARNA_BADGE[status] || 'bg-secondary';
}

/* ═══════════════════════════════════════════════════════════════════════
   FITUR 1 — AJAX Submit Status dengan Pop-up Modal Konfirmasi
   Menggantikan window.confirm() lama dengan modal Bootstrap interaktif.
   ═══════════════════════════════════════════════════════════════════════ */
document.addEventListener('submit', function(e) {
    // Tangkap hanya form dengan class .form-ubah-status
    if (!e.target.classList.contains('form-ubah-status')) return;
    e.preventDefault(); // Cegah submit konvensional (cegah reload halaman)

    var form       = e.target;
    var select     = form.querySelector('select[name="status_pesanan"]');
    var orderId    = form.dataset.orderId;
    var statusBaru = select.value;

    // Simpan referensi ke konteks modal
    _formAktif        = form;
    _formDataAktif    = new FormData(form);
    _selectAktif      = select;
    _btnSimpanAktif   = form.querySelector('.btn-simpan-status');

    // Cari status lama dari data-status pada kartu pesanan
    var cardEl     = document.getElementById('order-card-' + orderId);
    var statusLama = cardEl ? cardEl.dataset.status : '—';

    // Ambil nama customer dari kartu pesanan (dari elemen span dalam card-header)
    var namaCustomer = '—';
    if (cardEl) {
        var spanCustomer = cardEl.querySelector('.card-header span');
        if (spanCustomer) namaCustomer = spanCustomer.textContent.trim();
    }

    // Injeksi data ke dalam body modal
    document.getElementById('konfirm-id-order').textContent      = '#' + orderId;
    document.getElementById('konfirm-nama-customer').textContent = namaCustomer;

    var konfirmStatusLama = document.getElementById('konfirm-status-lama');
    konfirmStatusLama.className = 'badge fs-6 ' + getBadgeClass(statusLama);
    konfirmStatusLama.textContent = statusLama;

    var konfirmStatusBaru = document.getElementById('konfirm-status-baru');
    konfirmStatusBaru.className = 'badge fs-6 ' + getBadgeClass(statusBaru);
    konfirmStatusBaru.textContent = statusBaru;

    // Tampilkan modal konfirmasi
    bsModalKonfirmasi.show();
});

/* ─── Handler tombol "Ya, Ubah Status" di dalam modal ──────────────────────── */
document.getElementById('btnKonfirmasiUbahStatus').addEventListener('click', function() {
    // Tutup modal terlebih dahulu
    bsModalKonfirmasi.hide();

    // Pastikan konteks form masih valid
    if (!_formAktif || !_formDataAktif || !_selectAktif || !_btnSimpanAktif) return;

    var form      = _formAktif;
    var formData  = _formDataAktif;
    var select    = _selectAktif;
    var btnSimpan = _btnSimpanAktif;
    var orderId   = form.dataset.orderId;

    // Nonaktifkan tombol agar tidak diklik ganda
    btnSimpan.disabled    = true;
    btnSimpan.textContent = 'Menyimpan...';

    // Kirim request AJAX ke backend
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
                badgeEl.className   = 'badge fs-6 ' + (WARNA_BADGE[data.status_baru] || 'bg-secondary');
                badgeEl.textContent = data.status_baru;
            }
            // Update atribut data-status pada kartu
            var cardEl = document.getElementById('order-card-' + data.id_order);
            if (cardEl) {
                cardEl.dataset.status = data.status_baru;
            }
            // Nonaktifkan opsi status yang sudah terlewati pada <select>
            var statusUrutan = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
            var idxBaru      = statusUrutan.indexOf(data.status_baru);
            var opts         = select.options;
            for (var i = 0; i < opts.length; i++) {
                if (statusUrutan.indexOf(opts[i].value) < idxBaru) {
                    opts[i].disabled = true;
                }
            }
            // Jika status sudah Selesai, sembunyikan form dan tampilkan teks pengunci
            if (data.status_baru === 'Selesai') {
                form.outerHTML = '<p class="text-muted small mb-0">✅ Pesanan ini sudah selesai dan tidak dapat diubah.</p>';
            }
            // Tampilkan Pop-up Toast sukses (menggantikan alert statis)
            tampilkanToast('✅ ' + data.message, 'success');
        } else {
            // Tampilkan Pop-up Toast error
            tampilkanToast('❌ ' + (data.message || 'Gagal memperbarui status.'), 'danger', 5000);
            btnSimpan.disabled    = false;
            btnSimpan.textContent = 'Simpan';
        }
        // Reset konteks setelah selesai
        _formAktif = _formDataAktif = _selectAktif = _btnSimpanAktif = null;
    })
    .catch(function() {
        tampilkanToast('❌ Gagal menghubungi server. Periksa koneksi Anda.', 'danger', 5000);
        btnSimpan.disabled    = false;
        btnSimpan.textContent = 'Simpan';
        _formAktif = _formDataAktif = _selectAktif = _btnSimpanAktif = null;
    });
});

/* ─── Reset konteks saat modal ditutup via tombol Batal / klik luar ──────────
   Mencegah eksekusi AJAX tidak sengaja jika modal ditutup tanpa konfirmasi. */
modalKonfirmasiEl.addEventListener('hidden.bs.modal', function() {
    // Jika ditutup tanpa mengklik tombol konfirmasi, reset semua state
    if (_formAktif !== null) {
        _formAktif = _formDataAktif = _selectAktif = _btnSimpanAktif = null;
    }
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

    var btn     = this;
    var iconSvg = document.getElementById('iconRefresh');

    // Animasi putaran pada ikon refresh
    iconSvg.style.transform  = 'rotate(360deg)';
    iconSvg.style.transition = 'transform 0.6s ease';
    btn.disabled             = true;

    // Simpan posisi scroll saat ini sebelum update DOM
    var savedScrollTop = window.scrollY || document.documentElement.scrollTop;

    // Baca filter aktif dari URL agar refresh tetap mempertahankan filter
    var currentUrl = window.location.search; // contoh: "?status=Pending"

    // Ambil HTML halaman yang sama dengan filter aktif
    fetch('kelola_pesanan.php' + currentUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest-PartialRefresh' }
    })
    .then(function(res) { return res.text(); })
    .then(function(html) {
        // Parsing HTML dengan DOMParser (aman, tanpa eval)
        var parser = new DOMParser();
        var doc    = parser.parseFromString(html, 'text/html');

        // Ambil hanya konten #daftarPesananContainer dari halaman baru
        var newContainer = doc.getElementById('daftarPesananContainer');
        var oldContainer = document.getElementById('daftarPesananContainer');

        if (newContainer && oldContainer) {
            oldContainer.innerHTML = newContainer.innerHTML;
        }

        // ── Kembalikan posisi scroll ke koordinat semula ──────────────────
        window.scrollTo({ top: savedScrollTop, behavior: 'instant' });

        // Tampilkan Pop-up Toast (menggantikan alert statis)
        tampilkanToast('🔄 Daftar pesanan telah diperbarui.', 'success');
    })
    .catch(function() {
        tampilkanToast('❌ Gagal memuat ulang daftar pesanan.', 'danger', 5000);
    })
    .finally(function() {
        // Reset animasi ikon dan aktifkan kembali tombol
        setTimeout(function() {
            iconSvg.style.transform  = 'rotate(0deg)';
            iconSvg.style.transition = 'transform 0s';
        }, 100);
        btn.disabled  = false;
        sedangRefresh = false;
    });
});
</script>

<?php require '../includes/footer.php'; ?>
