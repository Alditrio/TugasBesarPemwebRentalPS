<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/layout.php';
wajib_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0); $aksi = $_POST['aksi'] ?? '';
    $p = q1($db, "SELECT status FROM penyewaan WHERE id=?", 'i', [$id]);
    if (!$p) flash('Transaksi tidak ditemukan.', 'err');
    elseif ($aksi === 'mulai' && $p['status'] === 'menunggu') {
        $bentrok = q1($db, "SELECT COUNT(*) c FROM detail_penyewaan d JOIN detail_penyewaan d2 ON d2.unit_id=d.unit_id AND d2.penyewaan_id<>d.penyewaan_id JOIN penyewaan p2 ON p2.id=d2.penyewaan_id WHERE d.penyewaan_id=? AND p2.status='berlangsung'", 'i', [$id]);
        if ($bentrok['c']) flash('Meja masih dipakai transaksi lain yang berlangsung.', 'err');
        else { q($db, "UPDATE penyewaan SET status='berlangsung' WHERE id=?", 'i', [$id]); flash('Transaksi dimulai.'); }
    }
    elseif ($aksi === 'selesai' && $p['status'] === 'berlangsung') { q($db, "UPDATE penyewaan SET status='selesai' WHERE id=?", 'i', [$id]); flash('Transaksi diselesaikan, meja kembali tersedia.'); }
    elseif ($aksi === 'batal' && in_array($p['status'], ['menunggu', 'berlangsung'])) { q($db, "UPDATE penyewaan SET status='dibatalkan' WHERE id=?", 'i', [$id]); flash('Transaksi dibatalkan.'); }
    elseif ($aksi === 'hapus' && in_array($p['status'], ['selesai', 'dibatalkan'])) { q($db, "DELETE FROM penyewaan WHERE id=?", 'i', [$id]); flash('Transaksi dihapus.'); }
    else flash('Aksi tidak diizinkan untuk status saat ini.', 'err');
    redirect('admin/transaksi.php');
}
$ok = ['menunggu', 'berlangsung', 'selesai', 'dibatalkan'];
$fs = in_array($_GET['status'] ?? '', $ok) ? $_GET['status'] : ''; $cari = trim($_GET['cari'] ?? '');
$w = []; $ty = ''; $pr = [];
if ($fs) { $w[] = 'p.status=?'; $ty .= 's'; $pr[] = $fs; }
if ($cari !== '') { $w[] = '(p.kode LIKE ? OR us.nama LIKE ? OR u.nama LIKE ?)'; $ty .= 'sss'; $l = "%$cari%"; array_push($pr, $l, $l, $l); }
$rows = q($db, "SELECT p.*, us.nama pelanggan, u.nama unit, k.nama kategori FROM penyewaan p JOIN users us ON us.id=p.user_id JOIN detail_penyewaan d ON d.penyewaan_id=p.id JOIN unit_rental u ON u.id=d.unit_id JOIN kategori k ON k.id=u.kategori_id" . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY p.waktu_mulai DESC LIMIT 200", $ty, $pr);
function tombol($id, $aksi, $teks, $cls, $konf = '') { return '<form method="post" class="inline"' . ($konf ? ' data-confirm="' . e($konf) . '"' : '') . '><input type="hidden" name="id" value="' . $id . '"><button class="btn sm ' . $cls . '" name="aksi" value="' . $aksi . '">' . $teks . '</button></form> '; }
page_head('Kelola Transaksi'); ?>
<h1 class="page-title">Kelola transaksi</h1>
<form class="chips" method="get"><?php echo '<a class="chip ' . (!$fs ? 'on' : '') . '" href="?">Semua</a>'; foreach ($ok as $o) echo '<a class="chip ' . ($fs === $o ? 'on' : '') . '" href="?status=' . $o . '">' . ucfirst($o) . '</a>'; ?>
  <input name="cari" value="<?= e($cari) ?>" placeholder="Cari kode, pelanggan, meja" style="max-width:260px"><?php if ($fs) echo '<input type="hidden" name="status" value="' . e($fs) . '">'; ?>
  <button class="btn sm navy">Cari</button> <a class="btn sm" href="<?= BASE_URL ?>/admin/sewa.php">Transaksi baru</a></form>
<div class="panel table-wrap"><table><tr><th>Kode</th><th>Pelanggan</th><th>Meja</th><th>Mulai</th><th>Selesai</th><th>Biaya</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $r) { echo '<tr><td>' . e($r['kode']) . '</td><td>' . e($r['pelanggan']) . '</td><td>' . e($r['unit']) . ' <small class="meta">' . e($r['kategori']) . '</small></td><td>' . tgl($r['waktu_mulai']) . '</td><td>' . date('H:i', strtotime($r['waktu_selesai'])) . '</td><td>' . rupiah($r['total_biaya']) . '<br><small class="meta">' . $r['durasi_jam'] . ' jam</small></td><td>' . badge($r['status']) . '</td><td>';
  if ($r['status'] === 'menunggu') echo tombol($r['id'], 'mulai', 'Mulai', 'navy') . tombol($r['id'], 'batal', 'Batal', 'red', 'Batalkan transaksi ini?');
  if ($r['status'] === 'berlangsung') echo tombol($r['id'], 'selesai', 'Selesai', '') . tombol($r['id'], 'batal', 'Batal', 'red', 'Batalkan transaksi ini?');
  if (in_array($r['status'], ['selesai', 'dibatalkan'])) echo tombol($r['id'], 'hapus', 'Hapus', 'red', 'Hapus transaksi permanen?');
  echo '</td></tr>'; }
if (!$rows) echo '<tr><td colspan="8">' . kosong('Tidak ada transaksi yang cocok dengan filter ini.', 16) . '</td></tr>'; ?></table></div>
<?php page_foot();
