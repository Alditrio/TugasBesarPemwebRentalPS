<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('admin');
$kat = q($db, "SELECT * FROM kategori ORDER BY nama");
$dir = __DIR__ . '/../assets/images/unit/';
$err = []; $edit = null;
$f = ['id' => 0, 'kategori_id' => 0, 'nama' => '', 'jenis_tv' => '', 'fasilitas' => '', 'harga_per_jam' => '', 'status' => 'aktif', 'foto' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        if (q1($db, "SELECT COUNT(*) c FROM detail_penyewaan WHERE unit_id=?", 'i', [$id])['c'] > 0) flash('Meja punya riwayat transaksi, tidak bisa dihapus. Ubah statusnya menjadi maintenance.', 'err');
        else {
            $old = q1($db, "SELECT foto FROM unit_rental WHERE id=?", 'i', [$id]);
            q($db, "DELETE FROM unit_rental WHERE id=?", 'i', [$id]);
            if ($old && $old['foto'] && is_file($dir . $old['foto'])) unlink($dir . $old['foto']);
            flash('Meja berhasil dihapus.');
        }
        redirect('admin/unit.php');
    }
    $f = ['id' => (int)($_POST['id'] ?? 0), 'kategori_id' => (int)($_POST['kategori_id'] ?? 0), 'nama' => trim($_POST['nama'] ?? ''), 'jenis_tv' => trim($_POST['jenis_tv'] ?? ''),
          'fasilitas' => trim($_POST['fasilitas'] ?? ''), 'harga_per_jam' => trim($_POST['harga_per_jam'] ?? ''), 'status' => $_POST['status'] ?? '', 'foto' => $_POST['foto_lama'] ?? ''];
    if (!q1($db, "SELECT id FROM kategori WHERE id=?", 'i', [$f['kategori_id']])) $err[] = 'Pilih tipe PlayStation.';
    if ($f['nama'] === '' || strlen($f['nama']) > 60) $err[] = 'Nama/nomor meja wajib diisi (maks. 60 karakter).';
    elseif (q1($db, "SELECT id FROM unit_rental WHERE nama=? AND id<>?", 'si', [$f['nama'], $f['id']])) $err[] = 'Nama meja sudah dipakai.';
    if ($f['jenis_tv'] === '' || strlen($f['jenis_tv']) > 80) $err[] = 'Jenis TV/monitor wajib diisi.';
    if (strlen($f['fasilitas']) > 255) $err[] = 'Fasilitas maksimal 255 karakter.';
    if (!ctype_digit($f['harga_per_jam']) || (int)$f['harga_per_jam'] < 1000 || (int)$f['harga_per_jam'] > 1000000) $err[] = 'Harga per jam harus angka 1.000 - 1.000.000.';
    if (!in_array($f['status'], ['aktif', 'maintenance'])) $err[] = 'Status tidak valid.';
    $fotoBaru = null;
    if (!empty($_FILES['foto']['name'])) {
        $fi = $_FILES['foto'];
        $info = $fi['error'] === 0 ? @getimagesize($fi['tmp_name']) : false;
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if (!$ext) $err[] = 'Foto harus berupa JPG, PNG, atau WEBP yang valid.';
        elseif ($fi['size'] > 2 * 1024 * 1024) $err[] = 'Ukuran foto maksimal 2 MB.';
        else $fotoBaru = ['tmp' => $fi['tmp_name'], 'ext' => $ext];
    }
    if (!$err) {
        if ($fotoBaru) {
            $nama = bin2hex(random_bytes(8)) . '.' . $fotoBaru['ext'];
            move_uploaded_file($fotoBaru['tmp'], $dir . $nama);
            if ($f['foto'] && is_file($dir . $f['foto'])) unlink($dir . $f['foto']);
            $f['foto'] = $nama;
        }
        $foto = $f['foto'] !== '' ? $f['foto'] : null; $h = (int)$f['harga_per_jam'];
        if ($f['id']) q($db, "UPDATE unit_rental SET kategori_id=?,nama=?,jenis_tv=?,fasilitas=?,harga_per_jam=?,status=?,foto=? WHERE id=?", 'isssissi', [$f['kategori_id'], $f['nama'], $f['jenis_tv'], $f['fasilitas'], $h, $f['status'], $foto, $f['id']]);
        else q($db, "INSERT INTO unit_rental (kategori_id,nama,jenis_tv,fasilitas,harga_per_jam,status,foto) VALUES (?,?,?,?,?,?,?)", 'isssiss', [$f['kategori_id'], $f['nama'], $f['jenis_tv'], $f['fasilitas'], $h, $f['status'], $foto]);
        flash($f['id'] ? 'Data meja diperbarui.' : 'Meja baru ditambahkan.'); redirect('admin/unit.php');
    }
} elseif (isset($_GET['edit'])) {
    $edit = q1($db, "SELECT * FROM unit_rental WHERE id=?", 'i', [(int)$_GET['edit']]);
    if ($edit) { $f = $edit; $f['harga_per_jam'] = (string)$edit['harga_per_jam']; $f['foto'] = (string)$edit['foto']; }
}
$show_form = $f['id'] || isset($_GET['tambah']) || $err;
$units = q($db, SQL_UNIT . " ORDER BY k.nama, u.nama");
page_head('Kelola Meja'); ?>
<h1 class="page-title">Kelola meja dan perangkat</h1>
<?php if ($show_form): ?>
<form class="panel" method="post" enctype="multipart/form-data" style="margin-bottom:26px" novalidate>
  <h2 style="font-size:1.6rem;color:var(--navy)"><?= $f['id'] ? 'Edit meja' : 'Tambah meja' ?></h2>
  <?php if ($err) echo '<ul class="errs"><li>' . implode('</li><li>', array_map('e', $err)) . '</li></ul>'; ?>
  <input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><input type="hidden" name="foto_lama" value="<?= e($f['foto']) ?>">
  <div class="row2"><div><label for="nama">Nama / nomor meja</label><input id="nama" name="nama" value="<?= e($f['nama']) ?>" required></div>
  <div><label for="kat">Tipe PlayStation</label><select id="kat" name="kategori_id"><option value="">- pilih -</option><?php foreach ($kat as $k) echo '<option value="' . $k['id'] . '"' . ($f['kategori_id'] == $k['id'] ? ' selected' : '') . '>' . e($k['nama']) . '</option>'; ?></select></div></div>
  <div class="row2"><div><label for="tv">Jenis TV / monitor</label><input id="tv" name="jenis_tv" value="<?= e($f['jenis_tv']) ?>" required></div>
  <div><label for="harga">Harga per jam (Rp)</label><input id="harga" name="harga_per_jam" inputmode="numeric" value="<?= e($f['harga_per_jam']) ?>" required></div></div>
  <label for="fas">Fasilitas</label><input id="fas" name="fasilitas" value="<?= e($f['fasilitas']) ?>" placeholder="mis. 2 stik, AC, headset, sofa">
  <div class="row2"><div><label for="st">Status unit</label><select id="st" name="status"><option value="aktif" <?= $f['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="maintenance" <?= $f['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option></select></div>
  <div><label for="foto">Foto meja (JPG/PNG/WEBP, maks. 2 MB)</label><input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp"></div></div>
  <?php if ($f['foto']) echo '<p class="meta" style="margin-top:8px">Foto saat ini: ' . e($f['foto']) . ' (unggah baru untuk mengganti)</p>'; ?>
  <div class="row-actions"><button class="btn">Simpan meja</button><a class="btn ghost" href="<?= BASE_URL ?>/admin/unit.php">Batal</a></div>
</form>
<?php else: ?><p style="margin-bottom:18px"><a class="btn" href="?tambah=1">Tambah meja</a></p><?php endif; ?>
<div class="panel table-wrap"><table><tr><th>Meja</th><th>Tipe</th><th>TV / monitor</th><th>Fasilitas</th><th>Harga/jam</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($units as $u) { echo '<tr><td><strong>' . e($u['nama']) . '</strong></td><td>' . e($u['kategori']) . '</td><td>' . e($u['jenis_tv']) . '</td><td>' . e($u['fasilitas']) . '</td><td>' . rupiah($u['harga_per_jam']) . '</td>
  <td><span class="dot d-' . $u['status_live'] . '">' . ucfirst($u['status_live']) . '</span></td><td><a class="btn sm navy" href="?edit=' . $u['id'] . '">Edit</a> 
  <form method="post" class="inline" data-confirm="Hapus ' . e($u['nama']) . '?"><input type="hidden" name="id" value="' . $u['id'] . '"><button class="btn sm red" name="aksi" value="hapus">Hapus</button></form></td></tr>'; }
if (!$units) echo '<tr><td colspan="7" class="meta">Belum ada meja. Tambahkan meja pertama.</td></tr>'; ?></table></div>
<?php page_foot();
