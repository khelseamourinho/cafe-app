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

// Cek apakah baru saja bayar QRIS
$qris_order_id = (int) ($_GET['qris'] ?? 0);

// Ambil semua pesanan user beserta items
$query = "SELECT o.id_order, o.total_harga, o.metode_pembayaran, o.status_pesanan,
                 o.tipe_order, o.created_at,
                 m.nama_menu, oi.jumlah, oi.subtotal
          FROM orders o
          JOIN order_items oi ON o.id_order = oi.id_order
          JOIN menus m ON oi.id_menu = m.id_menu
          WHERE o.id_user = $id_user
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
            'tipe_order'        => $baris['tipe_order'] ?? 'dine_in',
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

// Pisahkan menjadi dua kategori
$pesanan_berjalan = [];
$pesanan_selesai  = [];
foreach ($daftar_pesanan as $id_order => $pesanan) {
    if ($pesanan['status_pesanan'] === 'Selesai') {
        $pesanan_selesai[$id_order] = $pesanan;
    } else {
        $pesanan_berjalan[$id_order] = $pesanan;
    }
}

$warna_badge = [
    'Pending'       => 'bg-warning text-dark',
    'Sedang Dibuat' => 'bg-info text-dark',
    'Siap Pick-up'  => 'bg-primary',
    'Selesai'       => 'bg-success'
];

// Label tampilan (Pending -> Menunggu Pembayaran untuk customer)
$label_status = [
    'Pending'       => 'Menunggu Pembayaran',
    'Sedang Dibuat' => 'Sedang Dibuat',
    'Siap Pick-up'  => 'Siap Pick-up',
    'Selesai'       => 'Selesai'
];

// Hitung jumlah pesanan aktif untuk inisialisasi JS polling
$jumlah_pesanan_aktif = count($pesanan_berjalan);

include 'includes/header.php';
?>

<style>
/* ─── Progress Tracker ─── */
.progress-tracker{display:flex;align-items:center;justify-content:space-between;padding:0;margin:0 auto 1.5rem;max-width:420px}
.progress-step{display:flex;flex-direction:column;align-items:center;flex:0 0 auto;position:relative;z-index:1}
.progress-step .step-circle{width:36px;height:36px;border-radius:50%;background:#dee2e6;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#6c757d;transition:all .3s ease}
.progress-step.active .step-circle{background:#0d6efd;color:#fff;box-shadow:0 0 0 4px rgba(13,110,253,.2)}
.progress-step.done .step-circle{background:#198754;color:#fff}
.progress-step .step-label{font-size:.65rem;margin-top:6px;text-align:center;color:#6c757d;max-width:72px;line-height:1.2}
.progress-step.active .step-label,.progress-step.done .step-label{color:#212529;font-weight:600}
.progress-line{flex:1;height:3px;background:#dee2e6;margin:0 4px;margin-bottom:22px;transition:background .3s ease}
.progress-line.done{background:#198754}

/* ─── Order Card ─── */
.order-card{cursor:pointer;border:1px solid #e9ecef;border-radius:12px;transition:all .2s ease;overflow:hidden}
.order-card:hover{border-color:#0d6efd;box-shadow:0 4px 16px rgba(13,110,253,.1);transform:translateY(-2px)}

/* ─── Detail Item Row ─── */
.detail-item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f0f0}
.detail-item-row:last-child{border-bottom:none}
.detail-item-img{width:52px;height:52px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#f5f5f5}

/* ─── Animasi fade kartu saat perpindahan tab ─── */
@keyframes fadeOutCard {
    from { opacity: 1; transform: scale(1); }
    to   { opacity: 0; transform: scale(0.95); }
}
.kartu-fade-out {
    animation: fadeOutCard 0.4s ease forwards;
}
@keyframes fadeInCard {
    from { opacity: 0; transform: scale(0.95); }
    to   { opacity: 1; transform: scale(1); }
}
.kartu-fade-in {
    animation: fadeInCard 0.4s ease forwards;
}
</style>

<div class="container my-4">

    <!-- Tombol Kembali + Judul -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Kembali
        </a>
        <h3 class="mb-0">Riwayat Pesanan</h3>
    </div>

    <!-- ══════ TABS (Centered) ══════ -->
    <ul class="nav nav-pills mb-4 justify-content-center" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active position-relative" id="tab-berjalan" data-bs-toggle="pill"
                    data-bs-target="#panel-berjalan" type="button" role="tab">
                Sedang Berjalan
                <!-- Badge counter: diberi id agar bisa diupdate JS saat kartu berpindah -->
                <?php if (!empty($pesanan_berjalan)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          id="badge-counter-berjalan" style="font-size:.65rem">
                        <?= count($pesanan_berjalan) ?>
                    </span>
                <?php else: ?>
                    <!-- Badge hidden jika kosong, tetap ada di DOM untuk JS -->
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                          id="badge-counter-berjalan" style="font-size:.65rem">0</span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-selesai" data-bs-toggle="pill"
                    data-bs-target="#panel-selesai" type="button" role="tab">
                Selesai
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ══════ PANEL: Sedang Berjalan ══════ -->
        <div class="tab-pane fade show active" id="panel-berjalan" role="tabpanel">
            <?php if (empty($pesanan_berjalan)): ?>
                <!-- Pesan kosong: diberi id agar JS bisa menyembunyikan/menampilkan -->
                <div class="alert alert-info" id="msg-berjalan-kosong">Tidak ada pesanan yang sedang berjalan.</div>
            <?php else: ?>
                <div class="alert alert-info d-none" id="msg-berjalan-kosong">Tidak ada pesanan yang sedang berjalan.</div>
            <?php endif; ?>

            <div class="row g-3" id="container-berjalan">
                <?php foreach ($pesanan_berjalan as $id_order => $pesanan): ?>
                <?php
                    $total_item = 0;
                    foreach ($pesanan['items'] as $it) $total_item += $it['jumlah'];
                    $tipe_label = ($pesanan['tipe_order'] === 'take_away') ? '🥡 Take Away' : '🍽️ Dine In';
                    $tipe_class = ($pesanan['tipe_order'] === 'take_away') ? 'bg-warning text-dark' : 'bg-success';
                ?>
                <!-- Kartu pesanan berjalan: id unik + data-status untuk Smart Polling -->
                <div class="col-12 col-md-6"
                     id="col-order-<?= $id_order ?>"
                     data-order-id="<?= $id_order ?>"
                     data-status="<?= htmlspecialchars($pesanan['status_pesanan']) ?>"
                     data-tipe="<?= htmlspecialchars($pesanan['tipe_order']) ?>"
                     data-tipe-label="<?= htmlspecialchars($tipe_label) ?>"
                     data-tipe-class="<?= htmlspecialchars($tipe_class) ?>"
                     data-total-item="<?= $total_item ?>"
                     data-tanggal="<?= htmlspecialchars(date('d M Y', strtotime($pesanan['created_at']))) ?>"
                     data-total-harga="<?= htmlspecialchars(number_format($pesanan['total_harga'], 0, ',', '.')) ?>">
                    <div class="order-card p-3" onclick="bukaDetail(<?= $id_order ?>)" id="order-card-<?= $id_order ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge <?= $tipe_class ?> small"><?= $tipe_label ?></span>
                            <!-- Badge status: id unik untuk update live via JS -->
                            <span class="badge <?= $warna_badge[$pesanan['status_pesanan']] ?>" id="status-badge-<?= $id_order ?>">
                                <?= $label_status[$pesanan['status_pesanan']] ?>
                            </span>
                        </div>
                        <div class="mb-2">
                            <?php foreach ($pesanan['items'] as $item): ?>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($item['nama_menu']) ?>
                                    <span class="text-body-secondary">×<?= $item['jumlah'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-muted small"><?= $total_item ?> item</span>
                            <span class="fw-bold">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══════ PANEL: Selesai ══════ -->
        <div class="tab-pane fade" id="panel-selesai" role="tabpanel">
            <?php if (empty($pesanan_selesai)): ?>
                <div class="alert alert-secondary" id="msg-selesai-kosong">Belum ada pesanan yang selesai.</div>
            <?php else: ?>
                <div class="alert alert-secondary d-none" id="msg-selesai-kosong">Belum ada pesanan yang selesai.</div>
            <?php endif; ?>

            <div class="row g-3" id="container-selesai">
                <?php foreach ($pesanan_selesai as $id_order => $pesanan): ?>
                <?php
                    $total_item = 0;
                    foreach ($pesanan['items'] as $it) $total_item += $it['jumlah'];
                    $tipe_label = ($pesanan['tipe_order'] === 'take_away') ? '🥡 Take Away' : '🍽️ Dine In';
                    $tipe_class = ($pesanan['tipe_order'] === 'take_away') ? 'bg-warning text-dark' : 'bg-success';
                ?>
                <div class="col-12 col-md-6" id="col-order-<?= $id_order ?>">
                    <div class="order-card p-3" onclick="bukaDetail(<?= $id_order ?>)" style="opacity:.85">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge <?= $tipe_class ?> small"><?= $tipe_label ?></span>
                            <span class="badge bg-success">✓ Selesai</span>
                        </div>
                        <div class="mb-2">
                            <?php foreach ($pesanan['items'] as $item): ?>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($item['nama_menu']) ?>
                                    <span class="text-body-secondary">×<?= $item['jumlah'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-muted small"><?= $total_item ?> item · <?= date('d M Y', strtotime($pesanan['created_at'])) ?></span>
                            <span class="fw-bold">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ MODAL DETAIL PESANAN (centered on desktop) ══════════════ -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" style="max-width:440px">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="modalDetailLabel">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <!-- Konten di-load via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Memuat detail pesanan...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ MODAL QRIS ══════════════ -->
<div class="modal fade" id="modalQRIS" tabindex="-1" aria-labelledby="labelQRIS" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
        <div class="modal-content" style="margin:0 16px">
            <div class="modal-header">
                <h5 class="modal-title" id="labelQRIS">Pembayaran QRIS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center py-3">
                <p class="text-muted mb-2 small">Scan kode QR di bawah ini untuk melakukan pembayaran</p>
                <div class="mb-2">
                    <img src="assets/static/qris.png" alt="Kode QRIS" class="img-fluid border rounded p-2" style="max-width:220px">
                </div>
                <div class="alert alert-info py-2 mb-0">
                    <strong class="small">Total yang harus dibayar:</strong><br>
                    <span class="fs-5 fw-bold" id="qrisTotalBayar">Rp 0</span>
                </div>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Selesai</button>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════════
   riwayat.php — JavaScript
   1. formatRupiah()     : format angka ke rupiah
   2. bukaDetail()       : buka modal detail pesanan via AJAX
   3. renderDetail()     : render HTML isi modal dari data JSON
   4. tampilkanQRIS()    : tampilkan modal QRIS
   5. Smart Adaptive Polling : cek perubahan status pesanan aktif secara berkala
      - Berhenti saat tab disembunyikan (Page Visibility API)
      - Berhenti total saat tidak ada pesanan aktif
      - Interval: 4 detik saat aktif
   6. pindahkanKartu()   : animasi fade-out → pindah kartu ke panel Selesai
   7. Live Modal Sync    : perbarui progress tracker di modal jika sedang dibuka
   ═══════════════════════════════════════════════════════════════════════════════ */

// ─── Variabel Global ──────────────────────────────────────────────────────────
var intervalPolling   = null;  // ID setInterval aktif
var currentModalOrder = null;  // ID order yang sedang ditampilkan di modal

// Cache data status lokal dari render PHP (agar bisa dibandingkan dengan polling)
var statusLokal = {};
<?php foreach ($pesanan_berjalan as $id_order => $pesanan): ?>
statusLokal[<?= $id_order ?>] = '<?= $pesanan['status_pesanan'] ?>';
<?php endforeach; ?>

// ─── Helper: format angka ke Rupiah ─────────────────────────────────────────
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/* ═══════════════════════════════════════════════════════════════
   BUKA MODAL DETAIL PESANAN
   ═══════════════════════════════════════════════════════════════ */
function bukaDetail(idOrder, autoQris) {
    var modalEl = document.getElementById('modalDetail');
    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var body    = document.getElementById('modalDetailBody');

    // Simpan ID order yang sedang ditampilkan (untuk Live Sync saat polling)
    currentModalOrder = idOrder;

    // Loading state
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2">Memuat detail pesanan...</p></div>';
    bsModal.show();

    // Fetch detail dari endpoint yang sudah ada
    fetch('detail_pesanan.php?id=' + idOrder)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) {
                body.innerHTML = '<div class="alert alert-danger">' + d.error + '</div>';
                return;
            }
            renderDetail(d, body);

            // Auto-show QRIS jika baru checkout via QRIS
            if (autoQris && d.metode_pembayaran === 'QRIS' && d.status_pesanan === 'Pending') {
                setTimeout(function() {
                    tampilkanQRIS(d.total_harga);
                }, 600);
            }
        })
        .catch(function() {
            body.innerHTML = '<div class="alert alert-danger">Gagal memuat data.</div>';
        });

    // Ketika modal ditutup, reset currentModalOrder
    modalEl.addEventListener('hidden.bs.modal', function resetModal() {
        currentModalOrder = null;
        modalEl.removeEventListener('hidden.bs.modal', resetModal);
    }, { once: true });
}

/* ═══════════════════════════════════════════════════════════════
   RENDER HTML ISI MODAL DETAIL
   ═══════════════════════════════════════════════════════════════ */
function renderDetail(d, container) {
    var statusList  = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
    var statusLabel = {
        'Pending'      : 'Menunggu Pembayaran',
        'Sedang Dibuat': 'Sedang Dibuat',
        'Siap Pick-up' : 'Siap Pick-up',
        'Selesai'      : 'Selesai'
    };
    var statusIndex = statusList.indexOf(d.status_pesanan);

    // ─── 1. PROGRESS TRACKER ───
    var tracker = '<div class="progress-tracker">';
    for (var i = 0; i < statusList.length; i++) {
        var cls = '';
        if (i < statusIndex)      cls = 'done';
        else if (i === statusIndex) cls = 'active';

        tracker += '<div class="progress-step ' + cls + '">';
        tracker += '  <div class="step-circle">' + (i < statusIndex ? '✓' : (i + 1)) + '</div>';
        tracker += '  <div class="step-label">' + statusLabel[statusList[i]] + '</div>';
        tracker += '</div>';

        if (i < statusList.length - 1) {
            tracker += '<div class="progress-line ' + (i < statusIndex ? 'done' : '') + '"></div>';
        }
    }
    tracker += '</div>';

    // ─── 2. QRIS BUTTON & KETERANGAN PENDING ───
    var qrisSection = '';
    if (d.status_pesanan === 'Pending') {
        qrisSection += '<div class="alert alert-warning text-center mb-3 py-2">';
        qrisSection += '<small>⏳ Menunggu Pembayaran dan Konfirmasi Kasir</small>';
        qrisSection += '</div>';
        if (d.metode_pembayaran === 'QRIS') {
            qrisSection += '<div class="text-center mb-3">';
            qrisSection += '<button class="btn btn-outline-primary" onclick="tampilkanQRIS(' + d.total_harga + ')">📱 Tampilkan QRIS</button>';
            qrisSection += '</div>';
        }
    }

    // ─── 3. INFO PESANAN ───
    var tipeLabel = d.tipe_order === 'take_away' ? '🥡 Take Away' : '🍽️ Dine In';
    var tanggal   = new Date(d.created_at);
    var opsiTanggal = {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'};
    var tanggalStr  = tanggal.toLocaleDateString('id-ID', opsiTanggal);

    var infoSection  = '<div class="mb-3 p-3 rounded" style="background:#f8f9fa">';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Pesanan</span><span class="fw-semibold">#' + d.id_order + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Pelanggan</span><span class="fw-semibold">' + d.nama_pelanggan + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Tipe</span><span>' + tipeLabel + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between"><span class="text-muted small">Waktu</span><span class="small">' + tanggalStr + '</span></div>';
    infoSection += '</div>';

    // ─── 4. DAFTAR MENU ───
    var menuSection = '<h6 class="fw-bold mb-2">Pesanan</h6><div class="mb-3">';
    for (var j = 0; j < d.items.length; j++) {
        var item   = d.items[j];
        var imgSrc = item.gambar ? item.gambar : 'assets/static/logo.jpeg';

        menuSection += '<div class="detail-item-row">';
        menuSection += '  <img src="' + imgSrc + '" alt="" class="detail-item-img">';
        menuSection += '  <div class="flex-grow-1">';
        menuSection += '    <div class="fw-semibold small">' + item.nama_menu + '</div>';
        menuSection += '    <div class="text-muted small">' + item.jumlah + ' × ' + formatRupiah(item.harga) + '</div>';
        menuSection += '  </div>';
        menuSection += '  <div class="fw-semibold small text-nowrap">' + formatRupiah(item.subtotal) + '</div>';
        menuSection += '</div>';
    }
    menuSection += '</div>';

    // ─── 5. DETAIL PEMBAYARAN ───
    var r = d.rincian;
    var paySection  = '<h6 class="fw-bold mb-2">Detail Pembayaran</h6>';
    paySection += '<div class="p-3 rounded" style="background:#f8f9fa">';
    paySection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Subtotal (' + d.total_item + ' item)</span><span class="small">' + formatRupiah(r.subtotal) + '</span></div>';
    paySection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Pajak (' + r.pajak_persen + '%)</span><span class="small">' + formatRupiah(r.pajak_nominal) + '</span></div>';
    paySection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Biaya Layanan (' + r.layanan_persen + '%)</span><span class="small">' + formatRupiah(r.layanan_nominal) + '</span></div>';

    if (r.kemasan_nominal > 0) {
        paySection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Biaya Kemasan</span><span class="small">' + formatRupiah(r.kemasan_nominal) + '</span></div>';
    }

    paySection += '<hr class="my-2">';
    paySection += '<div class="d-flex justify-content-between fw-bold"><span>Total</span><span>' + formatRupiah(d.total_harga) + '</span></div>';
    paySection += '<div class="d-flex justify-content-between mt-1"><span class="text-muted small">Metode</span><span class="badge bg-secondary">' + d.metode_pembayaran + '</span></div>';
    paySection += '</div>';

    // ─── RENDER KE MODAL ───
    container.innerHTML = tracker + qrisSection + infoSection + menuSection + paySection;
}

/* ═══════════════════════════════════════════════════════════════
   MODAL QRIS
   ═══════════════════════════════════════════════════════════════ */
function tampilkanQRIS(total) {
    document.getElementById('qrisTotalBayar').textContent = formatRupiah(total);
    var modalQRIS = new bootstrap.Modal(document.getElementById('modalQRIS'));
    modalQRIS.show();
}

/* ═══════════════════════════════════════════════════════════════
   PINDAHKAN KARTU (Opsi A) — dari tab Berjalan ke tab Selesai
   Disertai animasi fade-out → fade-in
   ═══════════════════════════════════════════════════════════════ */
function pindahkanKartuKeSelesai(idOrder) {
    var colEl = document.getElementById('col-order-' + idOrder);
    if (!colEl) return; // Kartu sudah tidak ada di DOM

    // Update badge di dalam kartu menjadi "✓ Selesai"
    var badgeEl = document.getElementById('status-badge-' + idOrder);
    if (badgeEl) {
        badgeEl.className   = 'badge bg-success';
        badgeEl.textContent = '✓ Selesai';
    }

    // Tambahkan opacity dan hilangkan event onclick (tidak perlu re-open sekarang)
    var cardInner = colEl.querySelector('.order-card');
    if (cardInner) cardInner.style.opacity = '0.85';

    // Animasi fade-out
    colEl.classList.add('kartu-fade-out');

    setTimeout(function() {
        // Pindahkan elemen ke container-selesai
        var containerSelesai = document.getElementById('container-selesai');
        if (containerSelesai) {
            colEl.classList.remove('kartu-fade-out');
            containerSelesai.prepend(colEl); // Taruh di paling atas (paling baru)
            colEl.classList.add('kartu-fade-in');
            // Sembunyikan pesan kosong panel Selesai jika sebelumnya tampil
            document.getElementById('msg-selesai-kosong').classList.add('d-none');
            setTimeout(function() { colEl.classList.remove('kartu-fade-in'); }, 400);
        }

        // ─── Perbarui badge counter di tombol tab "Sedang Berjalan" ──────
        var counterEl = document.getElementById('badge-counter-berjalan');
        if (counterEl) {
            var jumlahSekarang = parseInt(counterEl.textContent, 10) || 0;
            var jumlahBaru     = jumlahSekarang - 1;
            if (jumlahBaru <= 0) {
                // Sembunyikan badge counter dan tampilkan pesan kosong
                counterEl.classList.add('d-none');
                counterEl.textContent = '0';
                document.getElementById('msg-berjalan-kosong').classList.remove('d-none');
            } else {
                counterEl.textContent = jumlahBaru;
            }
        }

        // Hapus dari cache statusLokal agar polling tidak memprosesnya lagi
        delete statusLokal[idOrder];

        // Jika tidak ada lagi pesanan aktif, hentikan polling
        if (Object.keys(statusLokal).length === 0) {
            hentikanPolling();
        }

    }, 420); // Tunggu animasi fade-out selesai (~400ms + buffer)
}

/* ═══════════════════════════════════════════════════════════════
   SMART ADAPTIVE POLLING
   - Interval : 4 detik
   - Berhenti : saat tab disembunyikan (Page Visibility API)
   - Berhenti : saat total_aktif == 0 (semua pesanan selesai)
   - Hanya berjalan jika ada pesanan berjalan saat halaman dimuat
   ═══════════════════════════════════════════════════════════════ */

// Jumlah pesanan aktif dari PHP saat halaman pertama kali dimuat
var jumlahPesananAktif = <?= $jumlah_pesanan_aktif ?>;

function mulaiPolling() {
    // Jangan mulai jika sudah berjalan atau tidak ada pesanan aktif
    if (intervalPolling !== null || Object.keys(statusLokal).length === 0) return;

    intervalPolling = setInterval(function() {
        fetch('api_status_pesanan.php')
            .then(function(res) {
                if (!res.ok) throw new Error('Response tidak OK');
                return res.json();
            })
            .then(function(data) {
                if (!data.success) return;

                // Buat map dari response: { id_order: status_pesanan }
                var statusBaru = {};
                data.orders.forEach(function(o) {
                    statusBaru[o.id_order] = o.status_pesanan;
                });

                // ─── Periksa setiap pesanan dalam cache lokal ────────────
                Object.keys(statusLokal).forEach(function(idOrder) {
                    var id = parseInt(idOrder, 10);

                    if (!(id in statusBaru)) {
                        // Pesanan tidak ada lagi di response "aktif" → berarti sudah Selesai
                        // atau baru saja hilang; perlakukan sebagai Selesai
                        pindahkanKartuKeSelesai(id);
                        // Sinkronisasi modal jika sedang terbuka untuk pesanan ini
                        if (currentModalOrder === id) {
                            sinkronisasiModal(id);
                        }
                        return;
                    }

                    var statusServerBaru = statusBaru[id];
                    if (statusServerBaru !== statusLokal[idOrder]) {
                        // ─── Ada perubahan status! ─────────────────────────
                        statusLokal[idOrder] = statusServerBaru; // Update cache

                        if (statusServerBaru === 'Selesai') {
                            // Pindahkan kartu ke tab Selesai
                            pindahkanKartuKeSelesai(id);
                        } else {
                            // Update badge warna dan teks di kartu
                            var badgeEl = document.getElementById('status-badge-' + id);
                            if (badgeEl) {
                                var labelMap = {
                                    'Pending'      : 'Menunggu Pembayaran',
                                    'Sedang Dibuat': 'Sedang Dibuat',
                                    'Siap Pick-up' : 'Siap Pick-up'
                                };
                                var classMap = {
                                    'Pending'      : 'badge bg-warning text-dark',
                                    'Sedang Dibuat': 'badge bg-info text-dark',
                                    'Siap Pick-up' : 'badge bg-primary'
                                };
                                badgeEl.className   = classMap[statusServerBaru] || 'badge bg-secondary';
                                badgeEl.textContent = labelMap[statusServerBaru] || statusServerBaru;
                            }
                        }

                        // ─── Sinkronisasi modal jika sedang dibuka untuk pesanan ini ──
                        if (currentModalOrder === id) {
                            sinkronisasiModal(id);
                        }
                    }
                });

                // Hentikan polling jika tidak ada lagi pesanan aktif
                if (data.total_aktif === 0 && Object.keys(statusLokal).length === 0) {
                    hentikanPolling();
                }
            })
            .catch(function(err) {
                // Gagal fetch: abaikan (tidak hentikan polling, coba lagi di interval berikutnya)
                console.warn('[Smart Polling] Gagal fetch:', err);
            });
    }, 4000); // Interval 4 detik
}

function hentikanPolling() {
    if (intervalPolling !== null) {
        clearInterval(intervalPolling);
        intervalPolling = null;
    }
}

/* ─── Page Visibility API: hentikan polling saat tab disembunyikan ───────────
   Ini adalah fitur utama "Adaptive" dari Smart Adaptive Polling.
   Saat user berpindah tab atau meminimize browser, polling berhenti total.
   Polling dilanjutkan kembali saat user kembali ke tab ini. */
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        hentikanPolling();
    } else {
        // Hanya mulai ulang jika masih ada pesanan aktif
        if (Object.keys(statusLokal).length > 0) {
            mulaiPolling();
        }
    }
});

/* ═══════════════════════════════════════════════════════════════
   SINKRONISASI MODAL (Live Sync Progress Tracker)
   Dipanggil oleh polling saat modal sedang terbuka dan
   order yang ditampilkan mengalami perubahan status.
   ═══════════════════════════════════════════════════════════════ */
function sinkronisasiModal(idOrder) {
    // Fetch ulang data detail pesanan dari endpoint yang sudah ada
    fetch('detail_pesanan.php?id=' + idOrder)
        .then(function(res) { return res.json(); })
        .then(function(d) {
            if (d.error) return;
            var body = document.getElementById('modalDetailBody');
            if (body) {
                renderDetail(d, body); // Re-render seluruh isi modal dengan data terbaru
            }
        })
        .catch(function() {
            // Abaikan error sinkronisasi modal (tidak kritikal)
        });
}

/* ─── Inisialisasi: mulai polling hanya jika ada pesanan aktif ───────────────
   Polling tidak akan berjalan sama sekali jika pelanggan tidak memiliki
   pesanan yang sedang berlangsung, menghemat resource server. */
document.addEventListener('DOMContentLoaded', function() {
    if (jumlahPesananAktif > 0) {
        mulaiPolling();
    }
});

// ─── Auto-show detail + QRIS jika baru checkout via QRIS ───
<?php if ($qris_order_id > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    bukaDetail(<?= $qris_order_id ?>, true);
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
