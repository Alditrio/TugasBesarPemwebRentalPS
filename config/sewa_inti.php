<?php
// Logika + tampilan form penyewaan, dipakai pelanggan/sewa.php ($mode='pelanggan') dan admin/sewa.php ($mode='admin')
$units = q($db, SQL_UNIT . " WHERE u.status='aktif' ORDER BY k.nama, u.nama");
$plg = $mode === 'admin' ? q($db, "SELECT id,nama,email FROM users WHERE role='pelanggan' ORDER BY nama") : [];
$f = ['unit' => (int)($_GET['unit'] ?? 0), 'tanggal' => $_GET['tanggal'] ?? date('Y-m-d'), 'jam' => (int)($_GET['jam'] ?? max(JAM_BUKA, min(JAM_TUTUP - 1, (int)date('G') + 1))),
      'durasi' => max(1, min(12, (int)($_GET['durasi'] ?? 1))), 'user' => 0];
$err = []; $ringkas = null; $home = $mode === 'admin' ? 'admin/transaksi.php' : 'pelanggan/riwayat.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = ['unit' => (int)($_POST['unit'] ?? 0), 'tanggal' => trim($_POST['tanggal'] ?? ''), 'jam' => (int)($_POST['jam'] ?? 0), 'durasi' => (int)($_POST['durasi'] ?? 0),
          'user' => $mode === 'admin' ? (int)($_POST['user'] ?? 0) : (int)user()['id']];
    [$err, $data] = validasi_sewa($db, $f['user'], $f['unit'], $f['tanggal'], $f['jam'], $f['durasi']);
    if (!$err && ($_POST['aksi'] ?? '') === 'simpan') {
        $kode = simpan_sewa($db, $data);
        if ($kode) { flash("Pesanan berhasil disimpan. Kode: $kode"); redirect($home); }
        $err[] = 'Maaf, jadwal baru saja diambil orang lain. Pilih jam lain.';
    } elseif (!$err) $ringkas = $data;
}
page_head($mode === 'admin' ? 'Transaksi Baru' : 'Sewa Meja'); ?>
<h1 class="page-title"><?= $mode === 'admin' ? 'Transaksi baru (walk-in)' : 'Sewa meja' ?></h1>
<?php if ($err) echo '<ul class="errs"><li>' . implode('</li><li>', array_map('e', $err)) . '</li></ul>'; ?>
<?php if ($ringkas): $r = $ringkas; ?>
<div class="panel" style="max-width:560px"><h2 style="color:var(--navy);font-size:1.8rem">Ringkasan transaksi</h2>
  <p class="meta">Periksa kembali sebelum disimpan.</p>
  <table><tr><th>Meja</th><td><?= e($r['unit']['nama']) ?> (<?= e($r['unit']['kategori']) ?>)</td></tr>
  <?php if ($mode === 'admin') { $pn = q1($db, "SELECT nama FROM users WHERE id=?", 'i', [$r['user_id']]); echo '<tr><th>Pelanggan</th><td>' . e($pn['nama']) . '</td></tr>'; } ?>
  <tr><th>Mulai</th><td><?= tgl($r['mulai']) ?></td></tr><tr><th>Selesai</th><td><?= tgl($r['selesai']) ?></td></tr>
  <tr><th>Durasi</th><td><?= $r['durasi'] ?> jam</td></tr><tr><th>Harga per jam</th><td><?= rupiah($r['harga']) ?></td></tr>
  <tr><th>Total biaya</th><td><strong style="font-size:1.3rem;color:var(--orange)"><?= rupiah($r['total']) ?></strong></td></tr></table>
  <form method="post" class="row-actions">
    <input type="hidden" name="unit" value="<?= $f['unit'] ?>"><input type="hidden" name="tanggal" value="<?= e($f['tanggal']) ?>">
    <input type="hidden" name="jam" value="<?= $f['jam'] ?>"><input type="hidden" name="durasi" value="<?= $f['durasi'] ?>"><input type="hidden" name="user" value="<?= $f['user'] ?>">
    <button class="btn" name="aksi" value="simpan">Konfirmasi dan simpan</button>
    <button class="btn ghost" type="button" onclick="history.back()">Ubah data</button>
  </form></div>
<?php else: ?>
<div class="cols">
 <form class="panel" id="form-sewa" method="post" data-api="<?= BASE_URL ?>/cek_jadwal.php" novalidate>
  <?php if ($mode === 'admin'): ?><label for="user">Pelanggan</label>
  <select id="user" name="user" required><option value="">- pilih pelanggan -</option><?php foreach ($plg as $p) echo '<option value="' . $p['id'] . '"' . ($f['user'] == $p['id'] ? ' selected' : '') . '>' . e($p['nama'] . ' (' . $p['email'] . ')') . '</option>'; ?></select><?php endif; ?>
  <label for="unit">Meja rental</label>
  <select id="unit" name="unit" required><option value="" data-harga="0">- pilih meja -</option>
  <?php foreach ($units as $u) echo '<option value="' . $u['id'] . '" data-harga="' . $u['harga_per_jam'] . '" data-nama="' . e($u['nama']) . '"' . ($f['unit'] == $u['id'] ? ' selected' : '') . '>' . e($u['nama'] . ' - ' . $u['kategori'] . ' - ' . rupiah($u['harga_per_jam']) . '/jam' . ($u['status_live'] === 'digunakan' ? ' (sedang dipakai)' : '')) . '</option>'; ?></select>
  <label for="tanggal">Tanggal</label>
  <input id="tanggal" type="date" name="tanggal" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" value="<?= e($f['tanggal']) ?>" required>
  <div class="row2"><div><label for="jam">Jam mulai</label><select id="jam" name="jam"><?php for ($i = JAM_BUKA; $i < JAM_TUTUP; $i++) echo '<option value="' . $i . '"' . ($f['jam'] == $i ? ' selected' : '') . '>' . sprintf('%02d:00', $i) . '</option>'; ?></select></div>
  <div><label for="durasi">Durasi</label><select id="durasi" name="durasi"><?php for ($i = 1; $i <= 12; $i++) echo '<option value="' . $i . '"' . ($f['durasi'] == $i ? ' selected' : '') . '>' . $i . ' jam</option>'; ?></select></div></div>
  <ul class="jadwal" id="jadwal"></ul>
  <div class="row-actions"><button class="btn" name="aksi" value="ringkasan">Lihat ringkasan</button></div>
 </form>
 <aside class="sum"><h3>Estimasi biaya</h3>
  <dl><dt>Meja</dt><dd id="s-unit">-</dd><dt>Harga</dt><dd id="s-harga">-</dd><dt>Durasi</dt><dd id="s-durasi">-</dd><dt>Waktu</dt><dd id="s-waktu">-</dd></dl>
  <span class="tot" id="s-total">Rp 0</span><p class="meta" style="color:#b9c6f5">Total final dihitung ulang oleh sistem saat disimpan.</p></aside>
</div>
<?php endif; page_foot();
