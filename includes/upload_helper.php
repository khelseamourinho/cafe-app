<?php
/**
 * upload_helper.php
 * Helper prosedural terpusat untuk manajemen aset gambar cafe-app.
 *
 * Fungsi:
 *   - upload_gambar()       : Memvalidasi, menamakan, dan menyimpan berkas unggahan.
 *   - hapus_berkas_fisik()  : Menghapus berkas fisik lama dari server (anti-orphan).
 *
 * Konvensi Penamaan Berkas:
 *   [fitur]_[subfitur]_u[id_user]_[YYYYMMDD_His]_[token_unik].[ekstensi]
 *   Contoh: menu_coffe_u1_20261102_120530_a91f3b20.png
 *            banner_carousel_u1_20261102_120701_f41e890c.webp
 */

/**
 * Memproses upload gambar secara prosedural.
 *
 * @param string $field_name  Nama atribut <input type="file"> pada form HTML.
 * @param string $fitur       Modul aplikasi ('menu' | 'banner').
 * @param string $subfitur    Konteks spesifik:
 *                              - Untuk menu: nilai kategori DB ('coffe', 'non_coffe',
 *                                'pastry_and_dessert', 'rice_bowl', 'pasta', 'snack').
 *                              - Untuk banner: 'carousel' | 'popup'.
 * @param int    $id_user     ID pengguna yang sedang login ($_SESSION['id_user']).
 *
 * @return string Path relatif (dari root cafe-app/) untuk disimpan ke kolom `gambar`
 *                di database. Contoh: 'assets/uploads/menu/menu_coffe_u1_...png'
 *                Mengembalikan string kosong '' jika tidak ada berkas yang diunggah.
 */
function upload_gambar(string $field_name, string $fitur, string $subfitur, int $id_user): string
{
    // Tidak ada berkas atau tidak ada nama file
    if (empty($_FILES[$field_name]['name']) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    // Tangani error upload lainnya
    if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        die('Error saat upload gambar. Kode error: ' . $_FILES[$field_name]['error']);
    }

    // --- Validasi MIME Type via finfo (lebih andal dari ekstensi saja) ---
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $tmp_name = $_FILES[$field_name]['tmp_name'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    if (!isset($allowed_mimes[$mime])) {
        die('Format file tidak didukung. Harap upload gambar berformat JPG, PNG, WEBP, atau GIF.');
    }

    // --- Validasi Ukuran Maksimal: 5MB ---
    if ($_FILES[$field_name]['size'] > 5 * 1024 * 1024) {
        die('Ukuran file terlalu besar. Maksimal 5MB per gambar.');
    }

    // --- Bangun Nama Berkas ---
    $ext           = $allowed_mimes[$mime];
    $sanitized_sub = preg_replace('/[^a-z0-9]/', '_', strtolower($subfitur)); // aman untuk nama file
    $timestamp     = date('Ymd_His');
    $token         = bin2hex(random_bytes(4)); // 8 karakter heks, entropi untuk cegah tabrakan
    $nama_file     = "{$fitur}_{$sanitized_sub}_u{$id_user}_{$timestamp}_{$token}.{$ext}";

    // --- Tentukan Direktori Target ---
    if ($fitur === 'menu') {
        $target_subpath = 'assets/uploads/menu/';
    } elseif ($fitur === 'banner') {
        $is_popup       = in_array($subfitur, ['popup', 'popup_banner'], true);
        $target_subpath = $is_popup
            ? 'assets/uploads/banner/popup/'
            : 'assets/uploads/banner/carousel/';
    } else {
        // Fallback aman untuk fitur lain di masa depan
        $target_subpath = 'assets/uploads/lainnya/';
    }

    // Absolut path target (upload_helper.php berada di includes/, naik satu level ke root)
    $absolute_dir = __DIR__ . '/../' . $target_subpath;

    // Buat direktori jika belum ada (recursive, permission 0755)
    if (!is_dir($absolute_dir)) {
        mkdir($absolute_dir, 0755, true);
    }

    // --- Simpan Berkas ---
    $destination = $absolute_dir . $nama_file;
    if (!move_uploaded_file($tmp_name, $destination)) {
        die("Gagal menyimpan gambar ke {$target_subpath}. Periksa permission folder pada server.");
    }

    return $target_subpath . $nama_file;
}

/**
 * Menghapus berkas gambar fisik dari server (digunakan saat edit atau hapus data).
 *
 * Proteksi keamanan: Hanya berkas di dalam 'assets/uploads/' yang akan dihapus.
 * Berkas statis di 'assets/static/' atau URL eksternal TIDAK akan tersentuh.
 *
 * @param string|null $path_relatif Path relatif yang tersimpan di kolom `gambar` database.
 *                                  Contoh: 'assets/uploads/menu/menu_coffe_u1_...png'
 * @return bool True jika berkas berhasil dihapus, False jika tidak ditemukan atau dilindungi.
 */
function hapus_berkas_fisik(?string $path_relatif): bool
{
    if (empty($path_relatif)) {
        return false;
    }

    // Proteksi: Hanya hapus berkas di dalam folder uploads/ (bukan static/ atau URL eksternal)
    if (strpos($path_relatif, 'assets/uploads/') !== 0) {
        return false;
    }

    // Absolut path dari root cafe-app (upload_helper.php ada di includes/)
    $absolute_path = __DIR__ . '/../' . $path_relatif;

    if (file_exists($absolute_path) && is_file($absolute_path)) {
        return unlink($absolute_path);
    }

    return false;
}

