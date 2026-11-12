<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('admin');
$k = q1($db, "SELECT COUNT(*) total, COALESCE(SUM(status_live='tersedia'),0) ada, COALESCE(SUM(status_live='digunakan'),0) pakai FROM (" . SQL_UNIT . ") x");
$t = q1($db, "SELECT COUNT(*) total, COALESCE(SUM(status='berlangsung'),0) jalan, COALESCE(SUM(status='menunggu'),0) tunggu,
  COALESCE(SUM(CASE WHEN status='selesai' THEN total_biaya END),0) pendapatan,
  COALESCE(SUM(CASE WHEN status='selesai' AND DATE(waktu_mulai)=CURDATE() THEN total_biaya END),0) hari_ini FROM penyewaan");
$units = q($db, SQL_UNIT . " ORDER BY k.nama, u.nama");
$baru = q($db, "SELECT p.*, us.nama pelanggan, u.nama unit FROM penyewaan p JOIN users us ON us.id=p.user_id JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id ORDER BY p.id DESC LIMIT 6");
page_head('Dashboard Admin'); ?>
<h1 class="page-title">Dashboard admin</h1>
<div class="kpis">
  <div class="kpi"><b><?= (int)$k['total'] ?></b><span>Jumlah meja rental</span></div>
  <div class="kpi"><b><?= (int)$t['total'] ?></b><span>Jumlah transaksi</span></div>
  <div class="kpi"><b><?= (int)$k['ada'] ?></b><span>Meja tersedia sekarang</span></div>
  <div class="kpi hot"><b><?= (int)$t['jalan'] ?></b><span>Penyewaan berlangsung</span></div>
  <div class="kpi"><b><?= (int)$t['tunggu'] ?></b><span>Menunggu dimulai</span></div>
  <div class="kpi"><b style="font-size:1.6rem"><?= rupiah($t['pendapatan']) ?></b><span>Total pendapatan (selesai) &middot; hari ini <?= rupiah($t['hari_ini']) ?></span></div>
</div>
<div class="panel"><div class="sec-title" style="margin-bottom:12px"><h2 style="font-size:1.6rem">Monitoring meja</h2><a class="btn sm" href="<?= BASE_URL ?>/admin/sewa.php">Transaksi baru</a></div>
<div class="mon"><?php foreach ($units as $u) echo '<div class="m-' . $u['status_live'] . '"><strong>' . e($u['nama']) . '</strong>' . e($u['kategori']) . ' &middot; ' . ($u['status_live'] === 'tersedia' ? 'Kosong' : ($u['status_live'] === 'digunakan' ? 'Dipakai sampai ' . date('H:i', strtotime($u['selesai_pada'])) : 'Maintenance')) . '</div>'; ?></div></div>
<div class="panel table-wrap" style="margin-top:26px"><div class="sec-title" style="margin-bottom:6px"><h2 style="font-size:1.6rem">Transaksi terbaru</h2><a class="btn sm ghost" href="<?= BASE_URL ?>/admin/transaksi.php">Semua transaksi</a></div>
<table><tr><th>Kode</th><th>Pelanggan</th><th>Meja</th><th>Mulai</th><th>Total</th><th>Status</th></tr>
<?php foreach ($baru as $r) echo '<tr><td>' . e($r['kode']) . '</td><td>' . e($r['pelanggan']) . '</td><td>' . e($r['unit']) . '</td><td>' . tgl($r['waktu_mulai']) . '</td><td>' . rupiah($r['total_biaya']) . '</td><td>' . badge($r['status']) . '</td></tr>';
if (!$baru) echo '<tr><td colspan="6">' . kosong('Belum ada transaksi. Buat transaksi walk-in pertama.', 20) . '</td></tr>'; ?></table></div>
<?php page_foot();
