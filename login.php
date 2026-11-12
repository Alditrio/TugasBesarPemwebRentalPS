<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/layout.php';

// Jika sudah terautentikasi (lewat session maupun cookie), alihkan sesuai role
if (user()) redirect(user()['role'] === 'admin' ? 'admin/' : 'pelanggan/');

$err = [];
// === IMPLEMENTASI COOKIE: Ambil email yang tersimpan di cookie (jika ada) ===
$email = trim($_POST['email'] ?? ($_COOKIE['last_email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Format email tidak valid.';
    if ($pass === '') $err[] = 'Password wajib diisi.';
    if (!$err) {
        $u = q1($db, "SELECT * FROM users WHERE email=?", 's', [$email]);
        if ($u && password_verify($pass, $u['password'])) {
            // === IMPLEMENTASI SESSION: Simpan status login & data user ke Session ===
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user'] = [
                'id' => $u['id'],
                'nama' => $u['nama'],
                'email' => $u['email'],
                'role' => $u['role']
            ];

            // === IMPLEMENTASI COOKIE: Simpan cookie remember login (7 hari) & email terakhir (30 hari) ===
            setcookie('user_id', (string)$u['id'], time() + (7 * 24 * 60 * 60), '/');
            setcookie('key', hash('sha256', $u['email']), time() + (7 * 24 * 60 * 60), '/');
            setcookie('last_email', $u['email'], time() + (30 * 24 * 60 * 60), '/');

            $next = $_SESSION['next'] ?? ''; unset($_SESSION['next']);
            $role_dir = $u['role'] === 'admin' ? '/admin/' : '/pelanggan/';
            if ($next && strpos($next, BASE_URL . $role_dir) === 0 || ($next && strpos($next, BASE_URL . '/katalog') === 0)) {
                header('Location: ' . $next);
                exit;
            }
            flash('Selamat datang, ' . $u['nama'] . '!');
            redirect($u['role'] === 'admin' ? 'admin/' : 'pelanggan/');
        }
        $err[] = 'Email atau password salah.';
    }
}
page_head('Login'); ?>
<div class="panel auth"><h1 class="page-title" style="margin-top:0">Login</h1>
<?php if ($err) echo '<ul class="errs"><li>' . implode('</li><li>', array_map('e', $err)) . '</li></ul>'; ?>
<form method="post" novalidate>
  <label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($email) ?>" required autofocus>
  <label for="pw">Password</label><input id="pw" type="password" name="password" required>
  <div class="row-actions"><button class="btn">Masuk</button><a class="btn ghost" href="<?= BASE_URL ?>/register.php">Buat akun</a></div>
</form>
<p class="meta" style="margin-top:16px">Demo: admin@rentalps.test / admin123 &middot; budi@mail.test / user123</p></div>
<?php page_foot();
