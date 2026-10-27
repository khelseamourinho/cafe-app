<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    $lokasi_index = str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../index.php' : 'index.php';
    header('Location: ' . $lokasi_index);
    exit;
}