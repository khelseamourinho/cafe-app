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

// Ambil detail menu untuk preview keranjang (server-side initial render)
$daftar_keranjang = [];
$total_harga_keranjang = 0;
if (!empty($keranjang)) {
    $ids_k      = implode(',', array_map('intval', array_keys($keranjang)));
    $res_k      = mysqli_query($koneksi, "SELECT id_menu, nama_menu, harga, gambar FROM menus WHERE id_menu IN ($ids_k)");
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

require 'includes/header.php';
?>

<h1 class="h3 mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?></h1>
<p class="text-muted mb-4">Silakan pilih menu favorit Anda di bawah ini.</p>

<?php
$query_menu = "SELECT * FROM menus";
$hasil_menu = mysqli_query($koneksi, $query_menu) or die("Query gagal: " . mysqli_error($koneksi));
?>

<div class="row row-cols-1 row-cols-md-3 g-4" id="menu-grid" <?php if ($total_item > 0): ?>style="padding-bottom: 80px"<?php endif; ?>>
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
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                                    data-id="<?= (int) $menu['id_menu'] ?>" data-aksi="update" data-jumlah="<?= $qty_di_keranjang - 1 ?>"
                                    style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">
                                −
                            </button>

                            <span class="fw-bold fs-5 mx-2 qty-display" data-id="<?= $menu['id_menu'] ?>"><?= $qty_di_keranjang ?></span>

                            <button type="button" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                                    data-id="<?= (int) $menu['id_menu'] ?>" data-aksi="update" data-jumlah="<?= $qty_di_keranjang + 1 ?>"
                                    style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">
                                +
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Mode: Belum dipesan — tampilkan tombol Pesan -->
                        <button type="button" class="btn btn-primary w-100 btn-pesan" data-id="<?= (int) $menu['id_menu'] ?>">Pesan</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- ═══ FLOATING BAR: Keranjang ═══════════════════════════════════════════════ -->
<div id="floating-bar" class="position-fixed bottom-0 start-0 end-0 py-3 <?= $total_item > 0 ? '' : 'd-none' ?>" style="z-index:1050;background:linear-gradient(180deg,#f8f9fa,#ffffff);border-top:2px solid #e9ecef;box-shadow:0 -4px 24px rgba(0,0,0,.12);border-radius:16px 16px 0 0">
    <div class="container d-flex justify-content-center align-items-center">
        <a href="#" role="button" class="text-decoration-none text-dark d-flex align-items-center gap-2 px-4 py-2 rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#keranjangOffcanvas" style="background:#e9ecef;transition:all .2s ease" onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart3" viewBox="0 0 16 16">
                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <span class="fw-semibold" id="floating-total-item"><?= $total_item ?></span>
            <span class="text-muted small">item</span>
        </a>
        <a href="checkout.php" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm" style="font-weight:600;letter-spacing:.3px">
            Pilih Metode Pembayaran &rarr;
        </a>
    </div>
</div>

<!-- ═══ OFFCANVAS: Preview Keranjang ══════════════════════════════════════════ -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="keranjangOffcanvas" aria-labelledby="keranjangLabel" style="width: 400px; max-width: 90vw;">
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
        <!-- Isi keranjang dirender di sini -->
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
                    <!-- Gambar menu -->
                    <?php if (!empty($item_k['gambar'])): ?>
                        <img src="<?= htmlspecialchars($item_k['gambar']) ?>" alt="<?= htmlspecialchars($item_k['nama_menu']) ?>"
                             class="rounded" style="width:56px;height:56px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:56px;height:56px;flex-shrink:0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- Detail -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($item_k['nama_menu']) ?></h6>
                            <button type="button" class="btn-close btn-close-sm ms-2 btn-hapus-item" data-id="<?= $item_k['id_menu'] ?>" aria-label="Hapus" style="font-size:0.6rem"></button>
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

        <!-- Footer keranjang -->
        <div class="border-top p-3 bg-light" id="keranjang-footer" <?= empty($daftar_keranjang) ? 'style="display:none"' : '' ?>>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-bold" id="keranjang-total-harga">Rp <?= number_format($total_harga_keranjang, 0, ',', '.') ?></span>
            </div>
            <small class="text-muted d-block mb-3">Belum termasuk pajak & biaya layanan</small>
            <!-- <a href="checkout.php" class="btn btn-primary w-100 mb-2">Lanjut ke Checkout &rarr;</a> -->
            <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btn-kosongkan-keranjang">Kosongkan Keranjang</button>
        </div>
    </div>
</div>

<!-- ═══ JAVASCRIPT: AJAX Keranjang (tanpa reload) ═════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ─── Helper: kirim aksi ke keranjang via AJAX ────────────────────────────
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

    // ─── Helper: format Rupiah ───────────────────────────────────────────────
    function formatRp(n) {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    // ─── Helper: render ulang card footer (counter / tombol Pesan) ───────────
    function renderCardFooter(idMenu, qty) {
        const footer = document.getElementById('footer-menu-' + idMenu);
        if (!footer) return;

        if (qty > 0) {
            footer.innerHTML = `
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                            data-id="${idMenu}" data-aksi="update" data-jumlah="${qty - 1}"
                            style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">−</button>
                    <span class="fw-bold fs-5 mx-2 qty-display" data-id="${idMenu}">${qty}</span>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center btn-qty"
                            data-id="${idMenu}" data-aksi="update" data-jumlah="${qty + 1}"
                            style="width:36px;height:36px;font-size:1.1rem;font-weight:bold;border-width:2px">+</button>
                </div>`;
        } else {
            footer.innerHTML = `<button type="button" class="btn btn-primary w-100 btn-pesan" data-id="${idMenu}">Pesan</button>`;
        }
    }

    // ─── Helper: render ulang offcanvas keranjang ────────────────────────────
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
                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
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

    // ─── Helper: update floating bar ─────────────────────────────────────────
    function updateFloatingBar(totalItem) {
        const bar  = document.getElementById('floating-bar');
        const span = document.getElementById('floating-total-item');
        const grid = document.getElementById('menu-grid');

        if (totalItem > 0) {
            bar.classList.remove('d-none');
            span.textContent = totalItem;
            grid.style.paddingBottom = '80px';
        } else {
            bar.classList.add('d-none');
            grid.style.paddingBottom = '';
        }
    }

    // ─── Helper: update seluruh UI setelah response AJAX ─────────────────────
    function updateUI(data) {
        // Update floating bar
        updateFloatingBar(data.total_item);

        // Update setiap card footer di katalog
        const allFooters = document.querySelectorAll('[id^="footer-menu-"]');
        allFooters.forEach(function (footer) {
            const id = footer.id.replace('footer-menu-', '');
            const qty = data.keranjang[id] || 0;
            renderCardFooter(id, qty);
        });

        // Update offcanvas keranjang
        renderOffcanvasKeranjang(data);
    }

    // ─── Event Delegation ────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {

        // Tombol "Pesan" pada card katalog
        const btnPesan = e.target.closest('.btn-pesan');
        if (btnPesan) {
            e.preventDefault();
            const idMenu = btnPesan.dataset.id;
            btnPesan.disabled = true;
            kirimKeranjang('tambah', idMenu, 1).then(updateUI).finally(() => { btnPesan.disabled = false; });
            return;
        }

        // Tombol +/- pada card katalog
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

        // Tombol +/- di offcanvas keranjang
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

        // Tombol hapus item di offcanvas
        const btnHapus = e.target.closest('.btn-hapus-item');
        if (btnHapus) {
            e.preventDefault();
            const idMenu = btnHapus.dataset.id;
            kirimKeranjang('hapus', idMenu, 0).then(updateUI);
            return;
        }

        // Tombol kosongkan keranjang
        if (e.target.id === 'btn-kosongkan-keranjang') {
            e.preventDefault();
            if (confirm('Yakin ingin mengosongkan semua item di keranjang?')) {
                kirimKeranjang('kosongkan', 0, 0).then(updateUI);
            }
            return;
        }
    });

});
</script>

<?php require 'includes/footer.php'; ?>
