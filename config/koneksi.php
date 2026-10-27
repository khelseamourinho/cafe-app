<?php
$host = 'localhost';
$user = 'root';
$password = '';
$nama_database = 'cafe_app';

$koneksi = mysqli_connect($host, $user, $password, $nama_database);

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>