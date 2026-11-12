<?php
require_once __DIR__ . '/config/functions.php';

// === IMPLEMENTASI PENGHAPUSAN SESSION ===
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// === IMPLEMENTASI PENGHAPUSAN COOKIE LOGIN ===
setcookie('user_id', '', time() - 3600, '/');
setcookie('key', '', time() - 3600, '/');

// Inisialisasi session baru khusus untuk membawa pesan notifikasi flash
session_start();
flash('Anda sudah logout.');
redirect('login.php');
