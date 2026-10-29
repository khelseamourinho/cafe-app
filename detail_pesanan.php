<?php
/**
 * Endpoint AJAX: Ambil detail pesanan berdasarkan id_order
 * Return: JSON dengan data pesanan lengkap (items + gambar + info user)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Belum login']);
    exit;
}

include 'config/koneksi.php';
include 'config/biaya.php';

$id_order = (int) ($_GET['id'] ?? 0);
$id_user  = (int) $_SESSION['id_user'];

if ($id_order <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID pesanan tidak valid']);
    exit;
}

// Ambil data order + nama user
$res_order = mysqli_query($koneksi,
    "SELECT o.*, u.nama AS nama_pelanggan
     FROM orders o
     JOIN users u ON o.id_user = u.id_user
     WHERE o.id_order = $id_order AND o.id_user = $id_user"
);

if (!$res_order || mysqli_num_rows($res_order) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Pesanan tidak ditemukan']);
    exit;
}

$order = mysqli_fetch_assoc($res_order);

// Ambil items pesanan + gambar menu
$res_items = mysqli_query($koneksi,
    "SELECT oi.jumlah, oi.subtotal, m.nama_menu, m.harga, m.gambar
     FROM order_items oi
     JOIN menus m ON oi.id_menu = m.id_menu
     WHERE oi.id_order = $id_order"
);

$items      = [];
$total_item = 0;
$subtotal_semua = 0;

while ($item = mysqli_fetch_assoc($res_items)) {
    $items[] = [
        'nama_menu' => $item['nama_menu'],
        'gambar'    => $item['gambar'] ?? '',
        'harga'     => (int) $item['harga'],
        'jumlah'    => (int) $item['jumlah'],
        'subtotal'  => (int) $item['subtotal'],
    ];
    $total_item     += (int) $item['jumlah'];
    $subtotal_semua += (int) $item['subtotal'];
}

// Hitung rincian biaya
$pajak_nominal    = (int) round($subtotal_semua * $pajak_persen / 100);
$layanan_nominal  = (int) round($subtotal_semua * $biaya_layanan_persen / 100);
$kemasan_nominal  = (($order['tipe_order'] ?? '') === 'take_away') ? (int) $biaya_kemasan : 0;

echo json_encode([
    'id_order'           => (int) $order['id_order'],
    'nama_pelanggan'     => $order['nama_pelanggan'],
    'tipe_order'         => $order['tipe_order'] ?? 'dine_in',
    'metode_pembayaran'  => $order['metode_pembayaran'],
    'status_pesanan'     => $order['status_pesanan'],
    'created_at'         => $order['created_at'],
    'total_harga'        => (int) $order['total_harga'],
    'items'              => $items,
    'total_item'         => $total_item,
    'rincian' => [
        'subtotal'          => $subtotal_semua,
        'pajak_persen'      => $pajak_persen,
        'pajak_nominal'     => $pajak_nominal,
        'layanan_persen'    => $biaya_layanan_persen,
        'layanan_nominal'   => $layanan_nominal,
        'kemasan_nominal'   => $kemasan_nominal,
    ],
]);
