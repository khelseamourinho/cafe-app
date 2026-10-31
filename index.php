<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require 'config/koneksi.php';

// ─── Pemetaan kategori DB → label + emoji + anchor ───────────────────────────
$label_kategori = [
    'coffe'              => ['label' => 'Coffee',          'emoji' => '☕', 'id' => 'kat-coffe'],
    'non_coffe'          => ['label' => 'Non-Coffee',      'emoji' => '🧃', 'id' => 'kat-non-coffe'],
    'pastry_and_dessert' => ['label' => 'Pastry & Dessert','emoji' => '🥐', 'id' => 'kat-pastry'],
    'rice_bowl'          => ['label' => 'Rice Bowl',       'emoji' => '🍚', 'id' => 'kat-rice-bowl'],
    'pasta'              => ['label' => 'Pasta',           'emoji' => '🍝', 'id' => 'kat-pasta'],
    'snack'              => ['label' => 'Snacks',          'emoji' => '🍿', 'id' => 'kat-snack'],
];

// ─── Inisialisasi keranjang ───────────────────────────────────────────────────
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
$keranjang  = $_SESSION['keranjang'];
$total_item = array_sum($keranjang);

// ─── Detail keranjang untuk offcanvas ────────────────────────────────────────
$daftar_keranjang      = [];
$total_harga_keranjang = 0;
if (!empty($keranjang)) {
    $ids_k   = implode(',', array_map('intval', array_keys($keranjang)));
    $res_k   = mysqli_query($koneksi, "SELECT id_menu, nama_menu, harga, gambar FROM menus WHERE id_menu IN ($ids_k)");
    while ($mk = mysqli_fetch_assoc($res_k)) {
        $id_mk = (int) $mk['id_menu'];
        $jml_k = (int) $keranjang[$id_mk];
        $sub_k = $mk['harga'] * $jml_k;
        $total_harga_keranjang += $sub_k;
        $daftar_keranjang[] = [
            'id_menu'   => $id_mk,
            'nama_menu' => $mk['nama_menu'],
            'harga'     => (int) $mk['harga'],
            'gambar'    => $mk['gambar'] ?? '',
            'jumlah'    => $jml_k,
            'subtotal'  => $sub_k,
        ];
    }
}

// ─── Ambil menu dikelompokkan per kategori ────────────────────────────────────
$hasil_menu = mysqli_query($koneksi,
    "SELECT * FROM menus ORDER BY FIELD(kategori,
        'coffe','non_coffe','pastry_and_dessert','rice_bowl','pasta','snack'
    ), nama_menu ASC"
) or die("Query gagal: " . mysqli_error($koneksi));

$menu_per_kategori = [];
while ($m = mysqli_fetch_assoc($hasil_menu)) {
    $menu_per_kategori[$m['kategori']][] = $m;
}

// ─── Ambil carousel banners ───────────────────────────────────────────────────
$hasil_banner = mysqli_query($koneksi,
    "SELECT * FROM banner WHERE keterangan='carousel_banner' AND status=1 ORDER BY urutan ASC, id_image ASC"
);
$banners = $hasil_banner ? mysqli_fetch_all($hasil_banner, MYSQLI_ASSOC) : [];

// ─── Ambil pop-up banner (sekali per login via $_SESSION) ─────────────────────
$popup        = null;
$tampil_popup = false;
if (!isset($_SESSION['popup_seen'])) {
    $res_popup = mysqli_query($koneksi,
        "SELECT gambar FROM banner WHERE keterangan='popup_banner' AND status=1 LIMIT 1");
    if ($res_popup) {
        $popup = mysqli_fetch_assoc($res_popup);
    }
    if ($popup) {
        $tampil_popup           = true;
        $_SESSION['popup_seen'] = true;
    }
}

require 'includes/header.php';
?>

<style>
html { scroll-behavior: smooth; }
/* Nav pills kategori tidak wrap di mobile */
.kategori-nav { scrollbar-width: none; }
.kategori-nav::-webkit-scrollbar { display: none; }
/* Card menu: gambar lebih kecil */
.menu-card-img { height: 160px; object-fit: cover; width: 100%; }
/* Placeholder gambar */
.img-placeholder { height: 160px; background: #f8f9fa; display:flex; align-items:center; justify-content:center; }
</style>

<?php if ($tampil_popup && $popup): ?>
<!-- ═══ POP-UP MODAL PROMO ═══════════════════════════════════════════════════ -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background:transparent; box-shadow:none;">
            <div class="position-relative">
                <button type="button"
                        class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                        style="z-index:10; background-color: rgba(0,0,0,0.5); border-radius:50%; padding:8px;"
                        data-bs-dismiss="modal" aria-label="Tutup"></button>
                <img src="<?= htmlspecialchars($popup['gambar']) ?>"
                     class="img-fluid rounded shadow-lg" alt="Promo"
                     style="width:100%; max-height:80vh; object-fit:contain;">
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('promoModal'), {
        backdrop: true,
        keyboard: true
    });
    modal.show();
});
</script>
<?php endif; ?>

<!-- ═══ GREETING ════════════════════════════════════════════════════════════ -->
<h1 class="h4 mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?> 👋</h1>
<p class="text-muted mb-3 small">Silakan pilih menu favorit Anda di bawah ini.</p>

<!-- ═══ CAROUSEL BANNER PROMO ════════════════════════════════════════════════ -->
<?php if (!empty($banners)): ?>
<div id="promoCarousel" class="carousel slide mb-4 rounded overflow-hidden shadow-sm"
     data-bs-ride="carousel" data-bs-interval="4000">

    <?php if (count($banners) > 1): ?>
    <div class="carousel-indicators">
        <?php foreach ($banners as $i => $b): ?>
        <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="<?= $i ?>"
                <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?>
                aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="carousel-inner">
        <?php foreach ($banners as $i => $b): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= htmlspecialchars($b['gambar']) ?>"
                 class="d-block w-100"
                 style="height:220px; object-fit:cover;"
                 alt="Banner Promo <?= $i + 1 ?>"
                 onerror="this.style.display='none'">
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($banners) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ═══ STICKY CATEGORY NAV-PILLS ════════════════════════════════════════════ -->
<div class="sticky-top bg-white border-bottom py-2 mb-4" style="top:56px; z-index:900;">
    <ul class="nav nav-pills kategori-nav flex-nowrap overflow-auto gap-1 mb-0 px-0">
        <?php foreach ($label_kategori as $key => $info): ?>
        <li class="nav-item">
            <a class="nav-link py-1 px-3 small" href="#<?= $info['id'] ?>">
                <?= $info['emoji'] ?> <?= $info['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- ═══ SEKSI MENU PER KATEGORI ══════════════════════════════════════════════ -->
<?php foreach ($label_kategori as $key => $info): ?>
<section id="<?= $info['id'] ?>" class="mb-5">
    <h5 class="fw-bold border-bottom pb-2 mb-3">
        <?= $info['emoji'] ?> <?= $info['label'] ?>
    </h5>

    <?php if (empty($menu_per_kategori[$key])): ?>
        <p class="text-muted small fst-italic">Belum ada menu di kategori ini.</p>
    <?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3" id="menu-grid-<?= $key ?>">
        <?php foreach ($menu_per_kategori[$key] as $menu): ?>
        <?php $qty_di_keranjang = $keranjang[$menu['id_menu']] ?? 0; ?>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <?php if (!empty($menu['gambar'])): ?>
                    <img src="<?= htmlspecialchars($menu['gambar']) ?>"
                         class="card-img-top menu-card-img"
                         alt="<?= htmlspecialchars($menu['nama_menu']) ?>">
                <?php else: ?>
                    <div class="img-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#adb5bd" viewBox="0 0 16 16">
                            <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                            <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="card-body p-2">
                    <h6 class="card-title mb-1 fw-semibold" style="font-size:.85rem; line-height:1.3">
                        <?= htmlspecialchars($menu['nama_menu']) ?>
                    </h6>
                    <?php if (!empty($menu['deskripsi'])): ?>
                    <p class="card-text text-muted mb-1" style="font-size:.75rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                        <?= htmlspecialchars($menu['deskripsi']) ?>
                    </p>
                    <?php endif; ?>
                    <p class="fw-bold mb-0" style="font-size:.85rem;">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></p>
                </div>

                <div class="card-footer bg-white p-2" id="footer-menu-<?= $menu['id_menu'] ?>">
                    <?php if ($qty_di_keranjang > 0): ?>
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                                    data-id="<?= (int) $menu['id_menu'] ?>" data-aksi="update" data-jumlah="<?= $qty_di_keranjang - 1 ?>"
                                    style="width:30px;height:30px;font-size:1rem;font-weight:bold;">−</button>
                            <span class="fw-bold qty-display" data-id="<?= $menu['id_menu'] ?>"><?= $qty_di_keranjang ?></span>
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                                    data-id="<?= (int) $menu['id_menu'] ?>" data-aksi="update" data-jumlah="<?= $qty_di_keranjang + 1 ?>"
                                    style="width:30px;height:30px;font-size:1rem;font-weight:bold;">+</button>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary btn-sm w-100 btn-pesan"
                                data-id="<?= (int) $menu['id_menu'] ?>">Pesan</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endforeach; ?>

<!-- ═══ FLOATING BAR: Keranjang ══════════════════════════════════════════════ -->
<div id="floating-bar" class="position-fixed bottom-0 start-0 end-0 py-3 <?= $total_item > 0 ? '' : 'd-none' ?>"
     style="z-index:1050;background:linear-gradient(180deg,#f8f9fa,#ffffff);border-top:2px solid #e9ecef;box-shadow:0 -4px 24px rgba(0,0,0,.12);border-radius:16px 16px 0 0">
    <div class="container d-flex justify-content-center align-items-center">
        <a href="#" role="button" class="text-decoration-none text-dark d-flex align-items-center gap-2 px-4 py-2 rounded-pill"
           data-bs-toggle="offcanvas" data-bs-target="#keranjangOffcanvas"
           style="background:#e9ecef;transition:all .2s ease"
           onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <span class="fw-semibold" id="floating-total-item"><?= $total_item ?></span>
            <span class="text-muted small">item</span>
        </a>
        <a href="checkout.php" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm ms-3" style="font-weight:600;letter-spacing:.3px">
            Pilih Metode Pembayaran &rarr;
        </a>
    </div>
</div>

<!-- ═══ OFFCANVAS: Preview Keranjang ═════════════════════════════════════════ -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="keranjangOffcanvas" aria-labelledby="keranjangLabel" style="width:400px;max-width:90vw;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="keranjangLabel">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-cart3 me-2" viewBox="0 0 16 16">
                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            Keranjang Anda
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column" id="keranjang-body">
        <div class="flex-grow-1 overflow-auto p-3" id="keranjang-list">
            <?php if (empty($daftar_keranjang)): ?>
                <div class="text-center text-muted py-5" id="keranjang-kosong">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-cart-x mb-3 opacity-50" viewBox="0 0 16 16">
                        <path d="M7.354 5.646a.5.5 0 1 0-.708.708L7.793 7.5 6.646 8.646a.5.5 0 1 0 .708.708L8.5 8.207l1.146 1.147a.5.5 0 0 0 .708-.708L9.207 7.5l1.147-1.146a.5.5 0 0 0-.708-.708L8.5 6.793z"/>
                        <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                    </svg>
                    <p class="mb-0">Keranjang masih kosong</p>
                </div>
            <?php else: ?>
                <?php foreach ($daftar_keranjang as $item_k): ?>
                <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom keranjang-item" data-id="<?= $item_k['id_menu'] ?>">
                    <?php if (!empty($item_k['gambar'])): ?>
                        <img src="<?= htmlspecialchars($item_k['gambar']) ?>" alt="<?= htmlspecialchars($item_k['nama_menu']) ?>"
                             class="rounded" style="width:56px;height:56px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px;flex-shrink:0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($item_k['nama_menu']) ?></h6>
                            <button type="button" class="btn-close btn-close-sm ms-2 btn-hapus-item"
                                    data-id="<?= $item_k['id_menu'] ?>" aria-label="Hapus" style="font-size:0.6rem"></button>
                        </div>
                        <small class="text-muted">Rp <?= number_format($item_k['harga'], 0, ',', '.') ?></small>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-keranjang-qty"
                                    data-id="<?= $item_k['id_menu'] ?>" data-delta="-1"
                                    style="width:28px;height:28px;font-size:0.85rem;font-weight:bold;padding:0">−</button>
                            <span class="fw-semibold keranjang-qty-display" data-id="<?= $item_k['id_menu'] ?>"><?= $item_k['jumlah'] ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-keranjang-qty"
                                    data-id="<?= $item_k['id_menu'] ?>" data-delta="1"
                                    style="width:28px;height:28px;font-size:0.85rem;font-weight:bold;padding:0">+</button>
                            <span class="text-muted ms-auto fw-semibold keranjang-subtotal" data-id="<?= $item_k['id_menu'] ?>">Rp <?= number_format($item_k['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="border-top p-3 bg-light" id="keranjang-footer" <?= empty($daftar_keranjang) ? 'style="display:none"' : '' ?>>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-bold" id="keranjang-total-harga">Rp <?= number_format($total_harga_keranjang, 0, ',', '.') ?></span>
            </div>
            <small class="text-muted d-block mb-3">Belum termasuk pajak & biaya layanan</small>
            <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btn-kosongkan-keranjang">Kosongkan Keranjang</button>
        </div>
    </div>
</div>

<!-- ═══ JAVASCRIPT: AJAX Keranjang ═══════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    function kirimKeranjang(aksi, idMenu, jumlah) {
        const body = new URLSearchParams();
        body.set('aksi', aksi);
        body.set('id_menu', idMenu);
        body.set('jumlah', jumlah);
        return fetch('keranjang_proses.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
        }).then(r => r.json());
    }

    function formatRp(n) {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function renderCardFooter(idMenu, qty) {
        const footer = document.getElementById('footer-menu-' + idMenu);
        if (!footer) return;
        if (qty > 0) {
            footer.innerHTML = `
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                            data-id="${idMenu}" data-aksi="update" data-jumlah="${qty - 1}"
                            style="width:30px;height:30px;font-size:1rem;font-weight:bold;">−</button>
                    <span class="fw-bold qty-display" data-id="${idMenu}">${qty}</span>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                            data-id="${idMenu}" data-aksi="update" data-jumlah="${qty + 1}"
                            style="width:30px;height:30px;font-size:1rem;font-weight:bold;">+</button>
                </div>`;
        } else {
            footer.innerHTML = `<button type="button" class="btn btn-primary btn-sm w-100 btn-pesan" data-id="${idMenu}">Pesan</button>`;
        }
    }

    function renderOffcanvasKeranjang(data) {
        const listEl   = document.getElementById('keranjang-list');
        const footerEl = document.getElementById('keranjang-footer');
        const totalEl  = document.getElementById('keranjang-total-harga');

        if (data.items.length === 0) {
            listEl.innerHTML = `
                <div class="text-center text-muted py-5" id="keranjang-kosong">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-cart-x mb-3 opacity-50" viewBox="0 0 16 16">
                        <path d="M7.354 5.646a.5.5 0 1 0-.708.708L7.793 7.5 6.646 8.646a.5.5 0 1 0 .708.708L8.5 8.207l1.146 1.147a.5.5 0 0 0 .708-.708L9.207 7.5l1.147-1.146a.5.5 0 0 0-.708-.708L8.5 6.793z"/>
                        <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                    </svg>
                    <p class="mb-0">Keranjang masih kosong</p>
                </div>`;
            footerEl.style.display = 'none';
            return;
        }

        footerEl.style.display = '';
        totalEl.textContent = formatRp(data.total_harga);

        let html = '';
        data.items.forEach(function (item) {
            const imgHtml = item.gambar
                ? `<img src="${item.gambar}" alt="${item.nama_menu}" class="rounded" style="width:56px;height:56px;object-fit:cover;flex-shrink:0">`
                : `<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px;flex-shrink:0">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                           <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                       </svg>
                   </div>`;
            html += `
            <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom keranjang-item" data-id="${item.id_menu}">
                ${imgHtml}
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">${item.nama_menu}</h6>
                        <button type="button" class="btn-close btn-close-sm ms-2 btn-hapus-item" data-id="${item.id_menu}" aria-label="Hapus" style="font-size:0.6rem"></button>
                    </div>
                    <small class="text-muted">${formatRp(item.harga)}</small>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-keranjang-qty"
                                data-id="${item.id_menu}" data-delta="-1"
                                style="width:28px;height:28px;font-size:0.85rem;font-weight:bold;padding:0">−</button>
                        <span class="fw-semibold keranjang-qty-display" data-id="${item.id_menu}">${item.jumlah}</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-keranjang-qty"
                                data-id="${item.id_menu}" data-delta="1"
                                style="width:28px;height:28px;font-size:0.85rem;font-weight:bold;padding:0">+</button>
                        <span class="text-muted ms-auto fw-semibold keranjang-subtotal" data-id="${item.id_menu}">${formatRp(item.subtotal)}</span>
                    </div>
                </div>
            </div>`;
        });
        listEl.innerHTML = html;
    }

    function updateFloatingBar(totalItem) {
        const bar  = document.getElementById('floating-bar');
        const span = document.getElementById('floating-total-item');
        if (totalItem > 0) {
            bar.classList.remove('d-none');
            span.textContent = totalItem;
        } else {
            bar.classList.add('d-none');
        }
    }

    function updateUI(data) {
        updateFloatingBar(data.total_item);
        const allFooters = document.querySelectorAll('[id^="footer-menu-"]');
        allFooters.forEach(function (footer) {
            const id  = footer.id.replace('footer-menu-', '');
            const qty = data.keranjang[id] || 0;
            renderCardFooter(id, qty);
        });
        renderOffcanvasKeranjang(data);
    }

    // ─── Event Delegation ────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {

        const btnPesan = e.target.closest('.btn-pesan');
        if (btnPesan) {
            e.preventDefault();
            const idMenu = btnPesan.dataset.id;
            btnPesan.disabled = true;
            kirimKeranjang('tambah', idMenu, 1).then(updateUI).finally(() => { btnPesan.disabled = false; });
            return;
        }

        const btnQty = e.target.closest('.btn-qty');
        if (btnQty) {
            e.preventDefault();
            const idMenu = btnQty.dataset.id;
            const jumlah = parseInt(btnQty.dataset.jumlah, 10);
            const aksi   = btnQty.dataset.aksi;
            btnQty.disabled = true;
            kirimKeranjang(aksi, idMenu, jumlah).then(updateUI).finally(() => { btnQty.disabled = false; });
            return;
        }

        const btnKQty = e.target.closest('.btn-keranjang-qty');
        if (btnKQty) {
            e.preventDefault();
            const idMenu    = btnKQty.dataset.id;
            const delta     = parseInt(btnKQty.dataset.delta, 10);
            const currentEl = document.querySelector('.keranjang-qty-display[data-id="' + idMenu + '"]');
            const current   = parseInt(currentEl.textContent, 10);
            const newQty    = current + delta;
            btnKQty.disabled = true;
            kirimKeranjang('update', idMenu, newQty).then(updateUI).finally(() => { btnKQty.disabled = false; });
            return;
        }

        const btnHapus = e.target.closest('.btn-hapus-item');
        if (btnHapus) {
            e.preventDefault();
            const idMenu = btnHapus.dataset.id;
            kirimKeranjang('hapus', idMenu, 0).then(updateUI);
            return;
        }

        if (e.target.id === 'btn-kosongkan-keranjang') {
            e.preventDefault();
            if (confirm('Yakin ingin mengosongkan semua item di keranjang?')) {
                kirimKeranjang('kosongkan', 0, 0).then(updateUI);
            }
            return;
        }
    });

    // ─── Highlight nav-pill aktif saat scroll ────────────────────────────────
    const sections  = document.querySelectorAll('section[id^="kat-"]');
    const navLinks  = document.querySelectorAll('.kategori-nav .nav-link');

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                navLinks.forEach(l => l.classList.remove('active'));
                const link = document.querySelector('.kategori-nav a[href="#' + entry.target.id + '"]');
                if (link) link.classList.add('active');
            }
        });
    }, { rootMargin: '-30% 0px -60% 0px' });

    sections.forEach(s => observer.observe(s));
});
</script>

<?php require 'includes/footer.php'; ?>
