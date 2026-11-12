<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/layout.php';
$kat = q($db, "SELECT * FROM kategori ORDER BY nama");
$fk = (int)($_GET['kategori'] ?? 0);
$tanggal = $_GET['tanggal'] ?? ''; $jam = (int)($_GET['jam'] ?? 0); $dur = max(1, min(12, (int)($_GET['durasi'] ?? 1)));
$cek = false; $err = '';
if ($tanggal !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$d || $d->format('Y-m-d') !== $tanggal || $tanggal < date('Y-m-d')) $err = 'Tanggal tidak valid atau sudah lewat.';
    elseif ($jam < JAM_BUKA || $jam + $dur > JAM_TUTUP) $err = 'Jam dan durasi harus di dalam jam buka (' . JAM_BUKA . ':00 - ' . JAM_TUTUP . ':00).';
    else { $cek = true; $m = sprintf('%s %02d:00:00', $tanggal, $jam); $s = date('Y-m-d H:i:s', strtotime($m) + $dur * 3600); }
}
$sql = SQL_UNIT . ($fk ? " WHERE u.kategori_id=?" : "") . " ORDER BY k.nama, u.nama";
$units = $fk ? q($db, $sql, 'i', [$fk]) : q($db, $sql);
page_head('Katalog Meja'); ?>
<h1 class="page-title">Katalog meja rental</h1>
<div class="chips"><a class="chip <?= !$fk ? 'on' : '' ?>" href="?">Semua</a>
<?php foreach ($kat as $k) echo '<a class="chip ' . ($fk == $k['id'] ? 'on' : '') . '" href="?kategori=' . $k['id'] . '">' . e($k['nama']) . '</a>'; ?></div>
<form class="panel" method="get" style="margin-bottom:26px">
  <strong>Cek ketersediaan untuk jadwal tertentu</strong>
  <?php if ($fk) echo '<input type="hidden" name="kategori" value="' . $fk . '">'; ?>
  <?php if ($err) echo '<div class="errs" style="margin-top:10px">' . e($err) . '</div>'; ?>
  <div class="row2" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));align-items:end">
    <div><label for="t">Tanggal</label><input id="t" type="date" name="tanggal" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" value="<?= e($tanggal) ?>" required></div>
    <div><label for="j">Jam mulai</label><select id="j" name="jam"><?php for ($i = JAM_BUKA; $i < JAM_TUTUP; $i++) echo '<option value="' . $i . '"' . ($jam == $i ? ' selected' : '') . '>' . sprintf('%02d:00', $i) . '</option>'; ?></select></div>
    <div><label for="d">Durasi</label><select id="d" name="durasi"><?php for ($i = 1; $i <= 12; $i++) echo '<option value="' . $i . '"' . ($dur == $i ? ' selected' : '') . '>' . $i . ' jam</option>'; ?></select></div>
    <div><button class="btn navy">Cek jadwal</button></div>
  </div>
  <?php if ($tanggal !== '' || $err) echo '<p style="margin-top:12px"><a href="?' . ($fk ? 'kategori=' . $fk : '') . '">Hapus pengecekan</a></p>'; ?>
</form>
<?php if (!$units) echo '<div class="panel">' . kosong('Belum ada meja pada kategori ini.', 20) . '</div>'; ?>
<div class="grid"><?php foreach ($units as $r) {
    $bentrok = $cek ? ada_bentrok($db, $r['id'], $m, $s) : null;
    $link = BASE_URL . '/pelanggan/sewa.php?unit=' . $r['id'] . ($cek ? "&tanggal=$tanggal&jam=$jam&durasi=$dur" : '');
    kartu_unit($r, $link, 'Sewa', $bentrok);
} ?></div>
<?php page_foot();
