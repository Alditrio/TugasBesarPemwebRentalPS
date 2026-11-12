<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('pelanggan');
$id = (int)user()['id'];
$s = q1($db, "SELECT COUNT(*) total, COALESCE(SUM(status IN ('menunggu','berlangsung')),0) aktif, COALESCE(SUM(CASE WHEN status='selesai' THEN total_biaya END),0) belanja FROM penyewaan WHERE user_id=?", 'i', [$id]);
$next = q($db, "SELECT p.*, u.nama unit, k.nama kategori FROM penyewaan p JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id JOIN kategori k ON k.id=u.kategori_id
  WHERE p.user_id=? AND p.status IN ('menunggu','berlangsung') ORDER BY p.waktu_mulai LIMIT 5", 'i', [$id]);
$units = q($db, SQL_UNIT . " ORDER BY k.nama, u.nama");
page_head('Beranda Pelanggan'); ?>
<h1 class="page-title">Halo, <?= e(user()['nama']) ?></h1>
<div class="kpis">
  <div class="kpi"><b><?= (int)$s['total'] ?></b><span>Total penyewaan</span></div>
  <div class="kpi hot"><b><?= (int)$s['aktif'] ?></b><span>Pesanan aktif</span></div>
  <div class="kpi"><b><?= rupiah($s['belanja']) ?></b><span>Total belanja (selesai)</span></div>
</div>
<div class="panel"><div class="sec-title" style="margin-bottom:10px"><h2 style="font-size:1.6rem">Pesanan aktif</h2><a class="btn sm" href="<?= BASE_URL ?>/pelanggan/sewa.php">Sewa meja</a></div>
<?php if (!$next) echo kosong('Belum ada pesanan aktif. Pilih meja dan jadwalkan sesi mainmu.', 20); else { ?>
<div class="table-wrap"><table><tr><th>Kode</th><th>Meja</th><th>Waktu</th><th>Total</th><th>Status</th></tr>
<?php foreach ($next as $r) echo '<tr><td>' . e($r['kode']) . '</td><td>' . e($r['unit'] . ' (' . $r['kategori'] . ')') . '</td><td>' . tgl($r['waktu_mulai']) . ' - ' . date('H:i', strtotime($r['waktu_selesai'])) . '</td><td>' . rupiah($r['total_biaya']) . '</td><td>' . badge($r['status']) . '</td></tr>'; ?>
</table></div><?php } ?></div>
<section class="sec"><div class="sec-title"><h2 style="font-size:1.6rem;color:var(--navy)">Status meja sekarang</h2></div>
<div class="mon"><?php foreach ($units as $u) echo '<div class="m-' . $u['status_live'] . '"><strong>' . e($u['nama']) . '</strong>' . e($u['kategori']) . ' &middot; ' . ($u['status_live'] === 'tersedia' ? 'Kosong' : ($u['status_live'] === 'digunakan' ? 'Dipakai' : 'Maintenance')) . '</div>'; ?></div></section>
<?php page_foot();
