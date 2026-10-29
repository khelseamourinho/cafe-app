<?php
// admin/edit_menu.php
// Redirect ke kelola_menu.php dengan parameter ?edit=<id> – form edit ada di modal halaman kelola_menu
$id_menu = isset($_GET['id']) ? (int) $_GET['id'] : 0;
header('Location: kelola_menu.php' . ($id_menu > 0 ? '?edit=' . $id_menu : ''));
exit;
