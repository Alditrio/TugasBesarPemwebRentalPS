<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('admin');
$err = []; $f = ['id' => 0, 'nama' => '', 'email' => '', 'no_hp' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'hapus') {
        $id = (int)$_POST['id'];
        if (q1($db, "SELECT COUNT(*) c FROM penyewaan WHERE user_id=?", 'i', [$id])['c'] > 0) flash('Pelanggan punya riwayat transaksi, tidak bisa dihapus.', 'err');
        else { q($db, "DELETE FROM users WHERE id=? AND role='pelanggan'", 'i', [$id]); flash('Pelanggan dihapus.'); }
        redirect('admin/pelanggan.php');
    }
    $f = ['id' => (int)($_POST['id'] ?? 0), 'nama' => trim($_POST['nama'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'no_hp' => trim($_POST['no_hp'] ?? '')];
    $pw = $_POST['password'] ?? '';
    if (strlen($f['nama']) < 3 || strlen($f['nama']) > 80) $err[] = 'Nama harus 3 sampai 80 karakter.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Format email tidak valid.';
    elseif (q1($db, "SELECT id FROM users WHERE email=? AND id<>?", 'si', [$f['email'], $f['id']])) $err[] = 'Email sudah terdaftar.';
    if (!preg_match('/^[0-9+]{8,15}$/', $f['no_hp'])) $err[] = 'No. HP harus 8 sampai 15 digit angka.';
    if (!$f['id'] && strlen($pw) < 6) $err[] = 'Password minimal 6 karakter.';
    if ($f['id'] && $pw !== '' && strlen($pw) < 6) $err[] = 'Password baru minimal 6 karakter.';
    if (!$err) {
        if ($f['id']) {
            q($db, "UPDATE users SET nama=?,email=?,no_hp=? WHERE id=? AND role='pelanggan'", 'sssi', [$f['nama'], $f['email'], $f['no_hp'], $f['id']]);
            if ($pw !== '') q($db, "UPDATE users SET password=? WHERE id=? AND role='pelanggan'", 'si', [password_hash($pw, PASSWORD_DEFAULT), $f['id']]);
        } else q($db, "INSERT INTO users (nama,email,no_hp,password,role) VALUES (?,?,?,?, 'pelanggan')", 'ssss', [$f['nama'], $f['email'], $f['no_hp'], password_hash($pw, PASSWORD_DEFAULT)]);
        flash($f['id'] ? 'Data pelanggan diperbarui.' : 'Pelanggan ditambahkan.'); redirect('admin/pelanggan.php');
    }
} elseif (isset($_GET['edit'])) {
    $e1 = q1($db, "SELECT id,nama,email,no_hp FROM users WHERE id=? AND role='pelanggan'", 'i', [(int)$_GET['edit']]); if ($e1) $f = $e1;
}
$rows = q($db, "SELECT u.*, (SELECT COUNT(*) FROM penyewaan p WHERE p.user_id=u.id) trx FROM users u WHERE role='pelanggan' ORDER BY u.nama");
page_head('Data Pelanggan'); ?>
<h1 class="page-title">Data pelanggan</h1>
<form class="panel" method="post" style="margin-bottom:26px" novalidate>
  <h2 style="font-size:1.5rem;color:var(--navy)"><?= $f['id'] ? 'Edit pelanggan' : 'Tambah pelanggan' ?></h2>
  <?php if ($err) echo '<ul class="errs"><li>' . implode('</li><li>', array_map('e', $err)) . '</li></ul>'; ?>
  <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
  <div class="row2"><div><label for="n">Nama</label><input id="n" name="nama" value="<?= e($f['nama']) ?>" required></div><div><label for="em">Email</label><input id="em" type="email" name="email" value="<?= e($f['email']) ?>" required></div></div>
  <div class="row2"><div><label for="hp">No. HP</label><input id="hp" name="no_hp" value="<?= e($f['no_hp']) ?>" required></div><div><label for="pw"><?= $f['id'] ? 'Password baru (kosongkan jika tidak diganti)' : 'Password' ?></label><input id="pw" type="password" name="password"></div></div>
  <div class="row-actions"><button class="btn">Simpan</button><?php if ($f['id']) echo '<a class="btn ghost" href="' . BASE_URL . '/admin/pelanggan.php">Batal</a>'; ?></div>
</form>
<div class="panel table-wrap"><table><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Transaksi</th><th>Aksi</th></tr>
<?php foreach ($rows as $r) echo '<tr><td>' . e($r['nama']) . '</td><td>' . e($r['email']) . '</td><td>' . e($r['no_hp']) . '</td><td>' . $r['trx'] . '</td><td><a class="btn sm navy" href="?edit=' . $r['id'] . '">Edit</a> <form method="post" class="inline" data-confirm="Hapus pelanggan ' . e($r['nama']) . '?"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn sm red" name="aksi" value="hapus">Hapus</button></form></td></tr>';
if (!$rows) echo '<tr><td colspan="5" class="meta">Belum ada pelanggan.</td></tr>'; ?></table></div>
<?php page_foot();
