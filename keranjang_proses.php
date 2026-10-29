<?php
/**
 * Handler keranjang belanja (session-based)
 * Aksi: tambah, update, hapus
 * Format session: $_SESSION['keranjang'][$id_menu] = jumlah
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
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

header('Location: index.php');
exit;
?>
