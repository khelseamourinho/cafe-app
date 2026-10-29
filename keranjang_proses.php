<?php
/**
 * Handler keranjang belanja (session-based)
 * Aksi: tambah, update, hapus, kosongkan
 * Format session: $_SESSION['keranjang'][$id_menu] = jumlah
 *
 * Mendukung dua mode:
 * 1. Form POST biasa  → proses lalu redirect ke index.php
 * 2. AJAX (fetch/XHR) → proses lalu return JSON
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Belum login']);
        exit;
    }
    header('Location: login.php');
    exit;
}

$aksi    = $_POST['aksi']    ?? '';
$id_menu = (int) ($_POST['id_menu'] ?? 0);
$jumlah  = (int) ($_POST['jumlah']  ?? 0);

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

switch ($aksi) {
    case 'tambah':
        if ($id_menu > 0 && $jumlah > 0) {
            if (isset($_SESSION['keranjang'][$id_menu])) {
                $_SESSION['keranjang'][$id_menu] += $jumlah;
            } else {
                $_SESSION['keranjang'][$id_menu] = $jumlah;
            }
        }
        break;

    case 'update':
        if ($id_menu > 0 && $jumlah > 0) {
            $_SESSION['keranjang'][$id_menu] = $jumlah;
        } elseif ($id_menu > 0 && $jumlah <= 0) {
            unset($_SESSION['keranjang'][$id_menu]);
        }
        break;

    case 'hapus':
        if ($id_menu > 0) {
            unset($_SESSION['keranjang'][$id_menu]);
        }
        break;

    case 'kosongkan':
        $_SESSION['keranjang'] = [];
        break;
}

// ─── MODE AJAX: kembalikan JSON ──────────────────────────────────────────────
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');

    $keranjang  = $_SESSION['keranjang'];
    $total_item = array_sum($keranjang);

    // Ambil detail menu dari DB jika keranjang tidak kosong
    $items = [];
    $total_harga = 0;

    if (!empty($keranjang)) {
        require 'config/koneksi.php';

        $ids      = implode(',', array_map('intval', array_keys($keranjang)));
        $res_menu = mysqli_query($koneksi, "SELECT id_menu, nama_menu, harga, gambar FROM menus WHERE id_menu IN ($ids)");

        while ($m = mysqli_fetch_assoc($res_menu)) {
            $id_m = (int) $m['id_menu'];
            $jml  = (int) $keranjang[$id_m];
            $sub  = $m['harga'] * $jml;
            $total_harga += $sub;

            $items[] = [
                'id_menu'   => $id_m,
                'nama_menu' => $m['nama_menu'],
                'harga'     => (int) $m['harga'],
                'gambar'    => $m['gambar'] ?? '',
                'jumlah'    => $jml,
                'subtotal'  => $sub,
            ];
        }
    }

    echo json_encode([
        'keranjang'   => $keranjang,
        'total_item'  => $total_item,
        'total_harga' => $total_harga,
        'items'       => $items,
    ]);
    exit;
}

// ─── MODE FORM BIASA: redirect ───────────────────────────────────────────────
header('Location: index.php');
exit;
?>
