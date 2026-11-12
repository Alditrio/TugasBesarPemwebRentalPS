<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/layout.php';
if (user()) redirect('index.php');
$err = []; $f = ['nama' => '', 'email' => '', 'no_hp' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
    if (strlen($f['nama']) < 3 || strlen($f['nama']) > 80) $err[] = 'Nama harus 3 sampai 80 karakter.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Format email tidak valid.';
    if (!preg_match('/^[0-9+]{8,15}$/', $f['no_hp'])) $err[] = 'No. HP harus 8 sampai 15 digit angka.';
    if (strlen($pw) < 6) $err[] = 'Password minimal 6 karakter.';
    if ($pw !== $pw2) $err[] = 'Konfirmasi password tidak sama.';
    if (!$err && q1($db, "SELECT id FROM users WHERE email=?", 's', [$f['email']])) $err[] = 'Email sudah terdaftar. Gunakan email lain atau login.';
    if (!$err) {
        q($db, "INSERT INTO users (nama,email,no_hp,password,role) VALUES (?,?,?,?, 'pelanggan')", 'ssss', [$f['nama'], $f['email'], $f['no_hp'], password_hash($pw, PASSWORD_DEFAULT)]);
        // === IMPLEMENTASI COOKIE: Simpan email ke cookie untuk mempermudah login ===
        setcookie('last_email', $f['email'], time() + (30 * 24 * 60 * 60), '/');
        flash('Akun berhasil dibuat. Silakan login.'); redirect('login.php');
    }
}
page_head('Daftar'); ?>
<div class="panel auth"><h1 class="page-title" style="margin-top:0">Buat akun</h1>
<?php if ($err) echo '<ul class="errs"><li>' . implode('</li><li>', array_map('e', $err)) . '</li></ul>'; ?>
<form method="post" novalidate>
  <label for="nama">Nama lengkap</label><input id="nama" name="nama" value="<?= e($f['nama']) ?>" required>
  <label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($f['email']) ?>" required>
  <label for="hp">No. HP</label><input id="hp" name="no_hp" inputmode="tel" value="<?= e($f['no_hp']) ?>" required>
  <div class="row2"><div><label for="pw">Password</label><input id="pw" type="password" name="password" required></div>
  <div><label for="pw2">Ulangi password</label><input id="pw2" type="password" name="password2" required></div></div>
  <div class="row-actions"><button class="btn">Daftar</button><a class="btn ghost" href="<?= BASE_URL ?>/login.php">Sudah punya akun</a></div>
</form></div>
<?php page_foot();
