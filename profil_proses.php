<?php
/**
 * Endpoint AJAX: Update profil user (nama, username, password)
 * Return: JSON
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['sukses' => false, 'pesan' => 'Belum login']);
    exit;
}

require 'config/koneksi.php';

$id_user       = (int) $_SESSION['id_user'];
$nama_baru     = trim($_POST['nama'] ?? '');
$username_baru = trim($_POST['username'] ?? '');
$password_lama = $_POST['password_lama'] ?? '';
$password_baru = $_POST['password_baru'] ?? '';

// Validasi dasar
if ($nama_baru === '' || $username_baru === '') {
    echo json_encode(['sukses' => false, 'pesan' => 'Nama dan username tidak boleh kosong.']);
    exit;
}

// Cek apakah username sudah dipakai user lain
$cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE username = ? AND id_user != ?");
mysqli_stmt_bind_param($cek, 'si', $username_baru, $id_user);
mysqli_stmt_execute($cek);
$res_cek = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($res_cek) > 0) {
    echo json_encode(['sukses' => false, 'pesan' => 'Username sudah digunakan oleh pengguna lain.']);
    exit;
}

// Jika ingin ganti password
if ($password_baru !== '') {
    if ($password_lama === '') {
        echo json_encode(['sukses' => false, 'pesan' => 'Masukkan password lama untuk mengganti password.']);
        exit;
    }

    if (strlen($password_baru) < 6) {
        echo json_encode(['sukses' => false, 'pesan' => 'Password baru minimal 6 karakter.']);
        exit;
    }

    // Verifikasi password lama
    $res_pw = mysqli_query($koneksi, "SELECT password FROM users WHERE id_user = $id_user");
    $data_pw = mysqli_fetch_assoc($res_pw);

    if (!password_verify($password_lama, $data_pw['password'])) {
        echo json_encode(['sukses' => false, 'pesan' => 'Password lama salah.']);
        exit;
    }

    $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, username = ?, password = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'sssi', $nama_baru, $username_baru, $hash_baru, $id_user);
} else {
    $stmt = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, username = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $nama_baru, $username_baru, $id_user);
}

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['sukses' => false, 'pesan' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
    exit;
}

// Update session
$_SESSION['nama']     = $nama_baru;
$_SESSION['username'] = $username_baru;

echo json_encode([
    'sukses'   => true,
    'pesan'    => 'Profil berhasil diperbarui.',
    'nama'     => $nama_baru,
    'username' => $username_baru,
]);
