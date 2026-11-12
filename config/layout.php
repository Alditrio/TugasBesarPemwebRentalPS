<?php
// Layout bersama: header + navigasi sesuai role, dan footer
function page_head($judul, $kelas = '') {
    $u = user(); $b = BASE_URL; $cur = basename($_SERVER['SCRIPT_NAME']);
    if (!$u) $menu = ['index.php' => ['Beranda', "$b/index.php"], 'katalog.php' => ['Katalog', "$b/katalog.php"], 'login.php' => ['Login', "$b/login.php"], 'register.php' => ['Daftar', "$b/register.php"]];
    elseif ($u['role'] === 'admin') $menu = ['index.php' => ['Dashboard', "$b/admin/index.php"], 'unit.php' => ['Meja', "$b/admin/unit.php"], 'transaksi.php' => ['Transaksi', "$b/admin/transaksi.php"], 'pelanggan.php' => ['Pelanggan', "$b/admin/pelanggan.php"], 'laporan.php' => ['Laporan', "$b/admin/laporan.php"], 'logout.php' => ['Logout', "$b/logout.php"]];
    else $menu = ['index.php' => ['Beranda', "$b/pelanggan/index.php"], 'katalog.php' => ['Katalog', "$b/katalog.php"], 'sewa.php' => ['Sewa', "$b/pelanggan/sewa.php"], 'riwayat.php' => ['Riwayat', "$b/pelanggan/riwayat.php"], 'logout.php' => ['Logout', "$b/logout.php"]];
    ?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | PlayZone Rental PS</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $b ?>/assets/css/style.css"></head>
<body class="<?= e($kelas) ?>">
<header class="nav"><a class="logo" href="<?= $b ?>/"><img src="<?= $b ?>/assets/images/logo.svg" alt="" width="42" height="42"><span>PLAY<br>ZONE</span></a>
<button class="burger" aria-label="Menu" aria-expanded="false">&#9776;</button>
<nav><?php foreach ($menu as $f => [$t, $h]) echo '<a href="' . $h . '"' . ($cur === $f ? ' class="on"' : '') . '>' . e($t) . '</a>'; ?></nav></header>
<main><?php show_flash();
}
function page_foot() { $b = BASE_URL; ?>
</main>
<footer class="foot-site"><div><strong>PlayZone Rental PS</strong><p>Buka setiap hari <?= JAM_BUKA ?>:00 - <?= JAM_TUTUP ?>:00 WIB</p></div>
<p>Sistem Informasi Manajemen Penyewaan PlayStation</p></footer>
<script src="<?= $b ?>/assets/js/app.js"></script></body></html>
<?php }
