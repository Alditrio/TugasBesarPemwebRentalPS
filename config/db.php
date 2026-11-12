<?php
// Konfigurasi koneksi database & konstanta aplikasi
define('BASE_URL', '/rental-ps');   // sesuaikan dengan nama folder di htdocs
define('JAM_BUKA', 10);
define('JAM_TUTUP', 24);

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli('localhost', 'root', '', 'rental_ps');
    $db->set_charset('utf8mb4');
    $db->query("SET time_zone = '+07:00'");
} catch (Exception $e) {
    die('Koneksi database gagal. Pastikan MySQL aktif dan database.sql sudah di-import. (' . htmlspecialchars($e->getMessage()) . ')');
}
