<?php
/**
 * api_status_pesanan.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Endpoint JSON ringan untuk Smart Polling dari sisi pelanggan.
 * Hanya mengembalikan status pesanan yang BELUM selesai milik user yang login.
 *
 * Response time target : < 15ms
 * Payload size target  : 200–400 byte
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Pastikan sesi aktif sebelum melakukan pengecekan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Keamanan: hanya user yang sudah login yang boleh mengakses endpoint ini
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak diizinkan. Silakan login terlebih dahulu.']);
    exit;
}

// Koneksi database
require_once 'config/koneksi.php';

// Ambil ID user dari sesi (casting integer agar aman dari SQL Injection)
$id_user = (int) $_SESSION['id_user'];

// Header untuk memastikan response selalu dibaca sebagai JSON dan tidak di-cache browser
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ─── QUERY RINGAN: Ambil hanya pesanan yang BELUM selesai ─────────────────────
// Hanya 5 kolom yang diperlukan (tanpa JOIN berat) untuk menjaga payload kecil
$stmt = mysqli_prepare($koneksi,
    "SELECT id_order, status_pesanan, tipe_order, total_harga, created_at
     FROM orders
     WHERE id_user = ?
       AND status_pesanan NOT IN ('Selesai', 'Dibatalkan')
     ORDER BY created_at DESC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}

// Bind parameter dan eksekusi query
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

// Kumpulkan hasil ke dalam array
$orders = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $orders[] = [
        'id_order'       => (int) $baris['id_order'],
        'status_pesanan' => $baris['status_pesanan'],
        'tipe_order'     => $baris['tipe_order'],
        'total_harga'    => (int) $baris['total_harga'],
        'created_at'     => $baris['created_at'],
    ];
}

mysqli_stmt_close($stmt);

// ─── RESPONSE JSON ────────────────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'orders'  => $orders,
    // Jumlah pesanan aktif (dipakai JS untuk menghentikan polling jika 0)
    'total_aktif' => count($orders),
]);
exit;

