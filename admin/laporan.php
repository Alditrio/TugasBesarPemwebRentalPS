<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('admin');
$valid = function ($d) { $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; };
$dari = $_GET['dari'] ?? date('Y-m-01'); $sampai = $_GET['sampai'] ?? date('Y-m-d'); $err = '';
if (!$valid($dari) || !$valid($sampai)) { $err = 'Format tanggal tidak valid.'; $dari = date('Y-m-01'); $sampai = date('Y-m-d'); }
elseif ($dari > $sampai) { $err = 'Tanggal awal tidak boleh melebihi tanggal akhir.'; $dari = date('Y-m-01'); $sampai = date('Y-m-d'); }
$ring = q1($db, "SELECT COUNT(*) total, COALESCE(SUM(status='selesai'),0) selesai, COALESCE(SUM(status='dibatalkan'),0) batal,
  COALESCE(SUM(CASE WHEN status='selesai' THEN total_biaya END),0) pendapatan, COALESCE(SUM(CASE WHEN status='selesai' THEN durasi_jam END),0) jam
  FROM penyewaan WHERE DATE(waktu_mulai) BETWEEN ? AND ?", 'ss', [$dari, $sampai]);
$perKat = q($db, "SELECT k.nama, COUNT(*) trx, SUM(d.subtotal) total FROM penyewaan p JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id JOIN kategori k ON k.id=u.kategori_id
  WHERE p.status='selesai' AND DATE(p.waktu_mulai) BETWEEN ? AND ? GROUP BY k.nama ORDER BY total DESC", 'ss', [$dari, $sampai]);
$perHari = q($db, "SELECT DATE(waktu_mulai) tgl, COUNT(*) trx, SUM(total_biaya) total FROM penyewaan WHERE status='selesai' AND DATE(waktu_mulai) BETWEEN ? AND ? GROUP BY DATE(waktu_mulai) ORDER BY tgl", 'ss', [$dari, $sampai]);
$rows = q($db, "SELECT p.*, us.nama pelanggan, u.nama unit FROM penyewaan p JOIN users us ON us.id=p.user_id JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id WHERE DATE(p.waktu_mulai) BETWEEN ? AND ? ORDER BY p.waktu_mulai DESC", 'ss', [$dari, $sampai]);
$maks = max(array_merge([1], array_map(fn($r) => (int)$r['total'], $perHari)));
page_head('Laporan'); ?>
<h1 class="page-title">Laporan penyewaan</h1>
<form class="panel no-print" method="get" style="margin-bottom:24px"><?php if ($err) echo '<div class="errs">' . e($err) . '</div>'; ?>
  <div class="row2" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end">
  <div><label for="d1">Dari tanggal</label><input id="d1" type="date" name="dari" value="<?= e($dari) ?>" required></div>
  <div><label for="d2">Sampai tanggal</label><input id="d2" type="date" name="sampai" value="<?= e($sampai) ?>" required></div>
  <div class="row-actions" style="margin:0"><button class="btn navy">Tampilkan</button><button class="btn ghost" type="button" onclick="window.print()">Cetak</button></div></div></form>
<p class="meta" style="margin-bottom:12px">Periode <?= date('d M Y', strtotime($dari)) ?> sampai <?= date('d M Y', strtotime($sampai)) ?>. Pendapatan dihitung dari transaksi berstatus selesai.</p>
<div class="kpis"><div class="kpi"><b><?= (int)$ring['total'] ?></b><span>Total transaksi</span></div><div class="kpi"><b><?= (int)$ring['selesai'] ?></b><span>Selesai</span></div><div class="kpi"><b><?= (int)$ring['batal'] ?></b><span>Dibatalkan</span></div>
<div class="kpi"><b><?= (int)$ring['jam'] ?></b><span>Jam terpakai</span></div><div class="kpi hot"><b style="font-size:1.7rem"><?= rupiah($ring['pendapatan']) ?></b><span>Pendapatan</span></div></div>
<div class="cols"><div class="panel"><h2 style="font-size:1.4rem;color:var(--navy);margin-bottom:10px">Pendapatan per hari</h2>
<?php foreach ($perHari as $h) echo '<div style="margin:8px 0"><small>' . date('d M', strtotime($h['tgl'])) . ' &middot; ' . $h['trx'] . ' transaksi &middot; <strong>' . rupiah($h['total']) . '</strong></small><div class="bar" style="width:' . max(2, round($h['total'] / $maks * 100)) . '%"></div></div>';
if (!$perHari) echo '<p class="meta">Belum ada transaksi selesai pada periode ini.</p>'; ?></div>
<div class="panel"><h2 style="font-size:1.4rem;color:var(--navy);margin-bottom:10px">Per tipe PlayStation</h2><table><tr><th>Tipe</th><th>Transaksi</th><th>Pendapatan</th></tr>
<?php foreach ($perKat as $k) echo '<tr><td>' . e($k['nama']) . '</td><td>' . $k['trx'] . '</td><td>' . rupiah($k['total']) . '</td></tr>'; if (!$perKat) echo '<tr><td colspan="3" class="meta">Tidak ada data.</td></tr>'; ?></table></div></div>
<div class="panel table-wrap" style="margin-top:24px"><h2 style="font-size:1.4rem;color:var(--navy);margin-bottom:10px">Semua transaksi pada periode</h2>
<table><tr><th>Kode</th><th>Pelanggan</th><th>Meja</th><th>Mulai</th><th>Durasi</th><th>Biaya</th><th>Status</th></tr>
<?php foreach ($rows as $r) echo '<tr><td>' . e($r['kode']) . '</td><td>' . e($r['pelanggan']) . '</td><td>' . e($r['unit']) . '</td><td>' . tgl($r['waktu_mulai']) . '</td><td>' . $r['durasi_jam'] . ' jam</td><td>' . rupiah($r['total_biaya']) . '</td><td>' . badge($r['status']) . '</td></tr>';
if (!$rows) echo '<tr><td colspan="7" class="meta">Tidak ada transaksi pada periode ini.</td></tr>'; ?></table></div>
<?php page_foot();
