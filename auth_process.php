<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$aksi     = $_POST['aksi'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($aksi === 'register') {
    $nama          = $_POST['nama'] ?? '';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role          = 'customer';

    $query = "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)";
    $stmt  = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        die(mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $nama, $username, $password_hash, $role);
    if (!mysqli_stmt_execute($stmt)) {
        header('Location: register.php?gagal=daftar');
        exit;
    }

    header('Location: login.php?berhasil=daftar');
    exit;
}

if ($aksi === 'login') {
    $query = "SELECT id_user, nama, password, role FROM users WHERE username = ?";
    $stmt  = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        die(mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param($stmt, 's', $username);
    if (!mysqli_stmt_execute($stmt)) {
        die(mysqli_error($koneksi));
    }

    $hasil = mysqli_stmt_get_result($stmt);
    $user  = mysqli_fetch_assoc($hasil);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }

    header('Location: login.php?gagal=login');
    exit;
}

header('Location: login.php');
exit;
