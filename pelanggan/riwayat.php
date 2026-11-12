<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('pelanggan');
$id = (int)user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal') {
    $n = q($db, "UPDATE penyewaan SET status='dibatalkan' WHERE id=? AND user_id=? AND status='menunggu'", 'ii', [(int)$_POST['id'], $id]);
    flash($n ? 'Pesanan dibatalkan.' : 'Pesanan tidak bisa dibatalkan (hanya status menunggu).', $n ? 'ok' : 'err'); redirect('pelanggan/riwayat.php');
}
$fs = $_GET['status'] ?? '';
$ok = ['menunggu', 'berlangsung', 'selesai', 'dibatalkan'];
$rows = q($db, "SELECT p.*, u.nama unit, k.nama kategori, d.harga_per_jam FROM penyewaan p JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id JOIN kategori k ON k.id=u.kategori_id
  WHERE p.user_id=?" . (in_array($fs, $ok) ? " AND p.status=?" : "") . " ORDER BY p.waktu_mulai DESC", in_array($fs, $ok) ? 'is' : 'i', in_array($fs, $ok) ? [$id, $fs] : [$id]);
page_head('Riwayat Penyewaan'); ?>
<h1 class="page-title">Riwayat penyewaan</h1>
<div class="chips"><a class="chip <?= !$fs ? 'on' : '' ?>" href="?">Semua</a><?php foreach ($ok as $o) echo '<a class="chip ' . ($fs === $o ? 'on' : '') . '" href="?status=' . $o . '">' . ucfirst($o) . '</a>'; ?></div>
<div class="panel table-wrap"><table><tr><th>Kode</th><th>Meja</th><th>Mulai</th><th>Selesai</th><th>Durasi</th><th>Total</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r) { echo '<tr><td>' . e($r['kode']) . '</td><td>' . e($r['unit'] . ' (' . $r['kategori'] . ')') . '</td><td>' . tgl($r['waktu_mulai']) . '</td><td>' . date('H:i', strtotime($r['waktu_selesai'])) . '</td><td>' . $r['durasi_jam'] . ' jam</td><td>' . rupiah($r['total_biaya']) . '</td><td>' . badge($r['status']) . '</td><td>';
  if ($r['status'] === 'menunggu') echo '<form method="post" class="inline" data-confirm="Batalkan pesanan ' . e($r['kode']) . '?"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn red sm" name="aksi" value="batal">Batalkan</button></form>';
  echo '</td></tr>'; }
if (!$rows) echo '<tr><td colspan="8">' . kosong('Belum ada riwayat. <a href="' . BASE_URL . '/pelanggan/sewa.php">Sewa meja pertamamu</a>.', 16) . '</td></tr>'; ?></table></div>
<?php page_foot();
