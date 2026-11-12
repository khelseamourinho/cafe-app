<?php
/**
 * batal_pesanan.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Endpoint pembatalan pesanan untuk PELANGGAN.
 * Hanya menerima HTTP POST (AJAX / Form).
 * Pelanggan hanya boleh membatalkan pesanan miliknya sendiri yang masih Pending.
 * ─────────────────────────────────────────────────────────────────────────────
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// 1. Cek sesi login
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

// 2. Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

require_once 'config/koneksi.php';

$id_user  = (int) $_SESSION['id_user'];
$id_order = (int) ($_POST['id_order'] ?? 0);

if ($id_order <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID pesanan tidak valid.']);
    exit;
}

// 3. Validasi keberadaan pesanan & kepemilikan
$stmt = mysqli_prepare($koneksi,
    'SELECT id_order, status_pesanan FROM orders WHERE id_order = ? AND id_user = ?'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'ii', $id_order, $id_user);
mysqli_stmt_execute($stmt);
$hasil  = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    exit;
}

// 4. Validasi status: pelanggan hanya boleh batalkan saat Pending
if ($pesanan['status_pesanan'] !== 'Pending') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan tidak dapat dibatalkan karena sudah diproses dapur.'
    ]);
    exit;
}

// 5. Eksekusi pembatalan (double-check id_user & status agar race-condition aman)
$upd = mysqli_prepare($koneksi,
    "UPDATE orders SET status_pesanan = 'Dibatalkan'
     WHERE id_order = ? AND id_user = ? AND status_pesanan = 'Pending'"
);
if (!$upd) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}
mysqli_stmt_bind_param($upd, 'ii', $id_order, $id_user);
mysqli_stmt_execute($upd);
$affected = mysqli_stmt_affected_rows($upd);
mysqli_stmt_close($upd);

if ($affected === 0) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan tidak dapat dibatalkan. Mungkin statusnya sudah berubah.'
    ]);
    exit;
}

// 6. Sukses
http_response_code(200);
echo json_encode([
    'success'  => true,
    'message'  => 'Pesanan berhasil dibatalkan.',
    'id_order' => $id_order,
]);
exit;

