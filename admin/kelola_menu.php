<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/koneksi.php';

// ─── HAPUS MENU ──────────────────────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM menus WHERE id_menu = $id_hapus")
        or die(mysqli_error($koneksi));
    header('Location: kelola_menu.php?pesan=hapus');
    exit;
}

// ─── Helper: proses upload foto ──────────────────────────────────────────────
function proses_upload_foto(string $field_name): string
{
    // Tidak ada file diunggah → kembalikan string kosong
    if (empty($_FILES[$field_name]['name'])) {
        return '';
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $file_type     = mime_content_type($_FILES[$field_name]['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        die('Tipe file tidak diizinkan. Gunakan JPG, PNG, WEBP, atau GIF.');
    }

    $ekstensi  = pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
    $nama_file = 'menu_' . time() . '_' . rand(100, 999) . '.' . strtolower($ekstensi);
    $tujuan    = __DIR__ . '/../assets/' . $nama_file;

    if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $tujuan)) {
        die('Gagal menyimpan foto. Periksa permission folder assets/.');
    }

    return 'assets/' . $nama_file; // path relatif dari root proyek
}

// ─── TAMBAH MENU ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $harga     = (int) $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Upload foto; jika tidak ada file, gunakan URL manual
    $gambar = proses_upload_foto('foto_menu');
    if ($gambar === '') {
        $gambar = mysqli_real_escape_string($koneksi, $_POST['gambar_url'] ?? '');
    }

    mysqli_query($koneksi,
        "INSERT INTO menus (nama_menu, kategori, harga, deskripsi, gambar)
         VALUES ('$nama_menu', '$kategori', $harga, '$deskripsi', '$gambar')"
    ) or die(mysqli_error($koneksi));

    header('Location: kelola_menu.php?pesan=tambah');
    exit;
}

// ─── EDIT MENU ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    $id_menu   = (int) $_POST['id_menu'];
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $harga     = (int) $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Upload foto baru; jika tidak ada, pertahankan gambar lama
    $gambar = proses_upload_foto('foto_menu');
    if ($gambar === '') {
        $gambar = mysqli_real_escape_string($koneksi, $_POST['gambar_lama'] ?? '');
    }

    mysqli_query($koneksi,
        "UPDATE menus SET nama_menu='$nama_menu', kategori='$kategori', harga=$harga,
         deskripsi='$deskripsi', gambar='$gambar' WHERE id_menu=$id_menu"
    ) or die(mysqli_error($koneksi));

    header('Location: kelola_menu.php?pesan=edit');
    exit;
}

// ─── SIMPAN PENGATURAN BIAYA ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_biaya') {
    $pajak_val   = max(0, (int) $_POST['pajak_persen']);
    $layanan_val = max(0, (int) $_POST['biaya_layanan_persen']);
    $kemasan_val = max(0, (int) $_POST['biaya_kemasan']);
    $isi_file = "<?php\n/**\n * Pengaturan Pajak, Biaya Layanan & Biaya Kemasan\n * File ini di-include oleh checkout.php dan diupdate oleh admin/kelola_menu.php\n */\n\$pajak_persen         = $pajak_val;\n\$biaya_layanan_persen = $layanan_val;\n\$biaya_kemasan        = $kemasan_val;\n?>\n";
    file_put_contents(__DIR__ . '/../config/biaya.php', $isi_file);
    header('Location: kelola_menu.php?pesan=biaya');
    exit;
}

// ─── Ambil daftar menu ───────────────────────────────────────────────────────
$hasil_menu  = mysqli_query($koneksi, "SELECT * FROM menus ORDER BY id_menu ASC")
    or die(mysqli_error($koneksi));
$daftar_menu = mysqli_fetch_all($hasil_menu, MYSQLI_ASSOC);

// ─── Baca pengaturan biaya saat ini ──────────────────────────────────────────
include __DIR__ . '/../config/biaya.php';

// ─── Data menu untuk modal edit ──────────────────────────────────────────────
$menu_edit = null;
if (isset($_GET['edit'])) {
    $id_edit   = (int) $_GET['edit'];
    $res_edit  = mysqli_query($koneksi, "SELECT * FROM menus WHERE id_menu = $id_edit");
    $menu_edit = mysqli_fetch_assoc($res_edit);
}

$pesan     = $_GET['pesan'] ?? '';
$base_path = '../';
require '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-1">
    <h1 class="h3 mb-0">Kelola Menu</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Menu</button>
</div>
<p class="text-muted mb-4">Tambah, ubah, atau hapus item menu kafe.</p>

<?php if ($pesan === 'tambah'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Menu berhasil ditambahkan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($pesan === 'edit'): ?>
    <div class="alert alert-info alert-dismissible fade show">
        Menu berhasil diperbarui. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($pesan === 'hapus'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        Menu berhasil dihapus. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif ($pesan === 'biaya'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Pengaturan pajak & biaya layanan berhasil disimpan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabel Menu -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:56px">Foto</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga</th>
                    <th class="d-none d-md-table-cell">Deskripsi</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_menu)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada menu.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar_menu as $menu): ?>
                <tr>
                    <td>
                        <?php if (!empty($menu['gambar'])): ?>
                            <img src="../<?= htmlspecialchars($menu['gambar']) ?>"
                                 alt="<?= htmlspecialchars($menu['nama_menu']) ?>"
                                 width="48" height="48" class="rounded object-fit-cover">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px">
                                <span class="text-muted small">—</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= htmlspecialchars($menu['nama_menu']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($menu['kategori']) ?></span></td>
                    <td class="text-end">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></td>
                    <td class="text-muted small d-none d-md-table-cell">
                        <?= htmlspecialchars(mb_strimwidth($menu['deskripsi'] ?? '', 0, 60, '…')) ?>
                    </td>
                    <td class="text-center">
                        <a href="?edit=<?= $menu['id_menu'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <a href="?hapus=<?= $menu['id_menu'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Yakin ingin menghapus menu ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════ Pengaturan Pajak, Biaya Layanan & Kemasan ══════════════ -->
<div class="card mt-4 mb-4">
    <div class="card-header">
        <h5 class="mb-0">Pengaturan Pajak, Biaya Layanan & Kemasan</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="kelola_menu.php">
            <input type="hidden" name="aksi" value="simpan_biaya">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Pajak (%)</label>
                    <input type="number" name="pajak_persen" class="form-control"
                           value="<?= $pajak_persen ?>" min="0" max="100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Biaya Layanan (%)</label>
                    <input type="number" name="biaya_layanan_persen" class="form-control"
                           value="<?= $biaya_layanan_persen ?>" min="0" max="100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Biaya Kemasan (Rp)</label>
                    <input type="number" name="biaya_kemasan" class="form-control"
                           value="<?= $biaya_kemasan ?>" min="0" required>
                    <div class="text-muted small mt-1">Dikenakan untuk pesanan Take Away.</div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Simpan Pengaturan</button>
                </div>
            </div>
            <div class="text-muted small mt-2">Pengaturan ini akan diterapkan pada setiap pesanan di halaman checkout.</div>
        </form>
    </div>
</div>

<!-- ══════════════ Modal Tambah ══════════════ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="labelTambah" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="kelola_menu.php" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title" id="labelTambah">Tambah Menu Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" name="nama_menu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach (['Coffee', 'Non-Coffee', 'Makanan', 'Snack'] as $kat): ?>
                                <option value="<?= $kat ?>"><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Menu</label>
                        <input type="file" name="foto_menu" id="fotoTambah" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="mt-2 text-center d-none" id="previewTambahWrap">
                            <img id="previewTambah" src="" alt="Preview" class="img-thumbnail" style="max-height:140px">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">atau URL Gambar (jika tidak upload)</label>
                        <input type="text" name="gambar_url" class="form-control form-control-sm"
                               placeholder="assets/nama-file.jpg atau URL eksternal">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════ Modal Edit ══════════════ -->
<?php if ($menu_edit): ?>
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="labelEdit" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="kelola_menu.php" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id_menu" value="<?= $menu_edit['id_menu'] ?>">
                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($menu_edit['gambar'] ?? '') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="labelEdit">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            onclick="window.location='kelola_menu.php'"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" name="nama_menu" class="form-control"
                               value="<?= htmlspecialchars($menu_edit['nama_menu']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <?php foreach (['Coffee', 'Non-Coffee', 'Makanan', 'Snack'] as $kat): ?>
                                <option value="<?= $kat ?>" <?= $menu_edit['kategori'] === $kat ? 'selected' : '' ?>>
                                    <?= $kat ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control"
                               value="<?= $menu_edit['harga'] ?>" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($menu_edit['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti Foto Menu</label>
                        <?php if (!empty($menu_edit['gambar'])): ?>
                            <div class="mb-2">
                                <img src="../<?= htmlspecialchars($menu_edit['gambar']) ?>"
                                     alt="Foto saat ini" class="img-thumbnail" style="max-height:100px">
                                <div class="text-muted small mt-1">Foto saat ini — unggah baru untuk mengganti.</div>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto_menu" id="fotoEdit" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="mt-2 text-center d-none" id="previewEditWrap">
                            <img id="previewEdit" src="" alt="Preview" class="img-thumbnail" style="max-height:140px">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="kelola_menu.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Buka modal edit otomatis
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    });
</script>
<?php endif; ?>

<!-- Preview foto sebelum upload -->
<script>
function setupPreview(inputId, imgId, wrapId) {
    document.getElementById(inputId).addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const wrap = document.getElementById(wrapId);
        const img  = document.getElementById(imgId);
        img.src    = URL.createObjectURL(file);
        wrap.classList.remove('d-none');
    });
}
setupPreview('fotoTambah', 'previewTambah', 'previewTambahWrap');
<?php if ($menu_edit): ?>
setupPreview('fotoEdit', 'previewEdit', 'previewEditWrap');
<?php endif; ?>
</script>

<?php require '../includes/footer.php'; ?>
