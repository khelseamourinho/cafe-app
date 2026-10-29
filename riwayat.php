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
                <?php if (!empty($pesanan_berjalan)): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem">
                        <?= count($pesanan_berjalan) ?>
                    </span>
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
                <div class="alert alert-info">Tidak ada pesanan yang sedang berjalan.</div>
            <?php endif; ?>

            <div class="row g-3">
                <?php foreach ($pesanan_berjalan as $id_order => $pesanan): ?>
                <?php
                    $total_item = 0;
                    foreach ($pesanan['items'] as $it) $total_item += $it['jumlah'];
                    $tipe_label = ($pesanan['tipe_order'] === 'take_away') ? '🥡 Take Away' : '🍽️ Dine In';
                    $tipe_class = ($pesanan['tipe_order'] === 'take_away') ? 'bg-warning text-dark' : 'bg-success';
                ?>
                <div class="col-12 col-md-6">
                    <div class="order-card p-3" onclick="bukaDetail(<?= $id_order ?>)">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge <?= $tipe_class ?> small"><?= $tipe_label ?></span>
                            <span class="badge <?= $warna_badge[$pesanan['status_pesanan']] ?>">
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
                <div class="alert alert-secondary">Belum ada pesanan yang selesai.</div>
            <?php endif; ?>

            <div class="row g-3">
                <?php foreach ($pesanan_selesai as $id_order => $pesanan): ?>
                <?php
                    $total_item = 0;
                    foreach ($pesanan['items'] as $it) $total_item += $it['jumlah'];
                    $tipe_label = ($pesanan['tipe_order'] === 'take_away') ? '🥡 Take Away' : '🍽️ Dine In';
                    $tipe_class = ($pesanan['tipe_order'] === 'take_away') ? 'bg-warning text-dark' : 'bg-success';
                ?>
                <div class="col-12 col-md-6">
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
                    <img src="assets/qris.png" alt="Kode QRIS" class="img-fluid border rounded p-2" style="max-width:220px">
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
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// ─── Buka Modal Detail ───
function bukaDetail(idOrder, autoQris) {
    var modalEl = document.getElementById('modalDetail');
    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var body = document.getElementById('modalDetailBody');

    // Loading state
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2">Memuat detail pesanan...</p></div>';
    bsModal.show();

    // Fetch detail
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
}

function renderDetail(d, container) {
    var statusList = ['Pending', 'Sedang Dibuat', 'Siap Pick-up', 'Selesai'];
    var statusLabel = {
        'Pending': 'Menunggu Pembayaran',
        'Sedang Dibuat': 'Sedang Dibuat',
        'Siap Pick-up': 'Siap Pick-up',
        'Selesai': 'Selesai'
    };
    var statusIndex = statusList.indexOf(d.status_pesanan);

    // ─── 1. PROGRESS TRACKER ───
    var tracker = '<div class="progress-tracker">';
    for (var i = 0; i < statusList.length; i++) {
        var cls = '';
        if (i < statusIndex) cls = 'done';
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
    var tanggal = new Date(d.created_at);
    var opsiTanggal = {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'};
    var tanggalStr = tanggal.toLocaleDateString('id-ID', opsiTanggal);

    var infoSection = '<div class="mb-3 p-3 rounded" style="background:#f8f9fa">';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Pesanan</span><span class="fw-semibold">#' + d.id_order + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Pelanggan</span><span class="fw-semibold">' + d.nama_pelanggan + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between mb-1"><span class="text-muted small">Tipe</span><span>' + tipeLabel + '</span></div>';
    infoSection += '<div class="d-flex justify-content-between"><span class="text-muted small">Waktu</span><span class="small">' + tanggalStr + '</span></div>';
    infoSection += '</div>';

    // ─── 4. DAFTAR MENU ───
    var menuSection = '<h6 class="fw-bold mb-2">Pesanan</h6><div class="mb-3">';
    for (var j = 0; j < d.items.length; j++) {
        var item = d.items[j];
        var imgSrc = item.gambar ? item.gambar : 'assets/logo.jpeg';

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
    var paySection = '<h6 class="fw-bold mb-2">Detail Pembayaran</h6>';
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

    // ─── RENDER ───
    container.innerHTML = tracker + qrisSection + infoSection + menuSection + paySection;
}

// ─── Modal QRIS ───
function tampilkanQRIS(total) {
    document.getElementById('qrisTotalBayar').textContent = formatRupiah(total);
    var modalQRIS = new bootstrap.Modal(document.getElementById('modalQRIS'));
    modalQRIS.show();
}

// ─── Auto-show detail + QRIS jika baru checkout via QRIS ───
<?php if ($qris_order_id > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    bukaDetail(<?= $qris_order_id ?>, true);
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>