<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/layout.php';
$st = q1($db, "SELECT COUNT(*) total, SUM(status_live='tersedia') ada FROM (" . SQL_UNIT . ") x") ?: ['total' => 0, 'ada' => 0];
$min = q1($db, "SELECT MIN(harga_per_jam) h FROM unit_rental WHERE status='aktif'");
$pop = q($db, SQL_UNIT . " WHERE u.status='aktif' ORDER BY (SELECT COUNT(*) FROM detail_penyewaan d WHERE d.unit_id=u.id) DESC, u.id LIMIT 3");
$u = user(); $mulai = $u ? ($u['role'] === 'admin' ? 'admin/index.php' : 'pelanggan/sewa.php') : 'register.php';
page_head('Beranda'); ?>
<section class="hero">
  <div class="art"><b>PS5</b><img src="<?= BASE_URL ?>/assets/images/controller.svg" alt="Stik PlayStation"></div>
  <div class="txt">
    <h1>Main PlayStation tanpa antre.<br><em>Pesan meja</em>, cek jam kosong, langsung gas.</h1>
    <p>Pilih meja PS3, PS4, atau PS5, tentukan jam dan durasi. Biaya terhitung otomatis dan jadwal kamu dijamin tidak bentrok.</p>
    <div><a class="btn" href="<?= BASE_URL . '/' . $mulai ?>"><?= $u ? 'Sewa sekarang' : 'Daftar dan sewa' ?></a> <a class="btn ghost" href="<?= BASE_URL ?>/katalog.php">Lihat katalog</a></div>
    <div class="stats"><div><?= (int)$st['total'] ?><small>meja rental</small></div><div><?= (int)$st['ada'] ?><small>kosong sekarang</small></div><div><?= $min['h'] ? number_format($min['h'] / 1000) . 'K' : '-' ?><small>mulai per jam</small></div></div>
  </div>
</section>

<section class="sec">
  <div class="sec-title"><h2>Meja paling sering disewa</h2><span class="watermark">PLAYSTATION</span></div>
  <div class="grid"><?php foreach ($pop as $r) kartu_unit($r, BASE_URL . '/pelanggan/sewa.php?unit=' . $r['id']); ?></div>
</section>

<section class="band">
  <div class="l"><h2 style="font-size:2.4rem;color:var(--navy)">Satu stik, empat teman, nol bentrok jadwal</h2>
    <p>Setiap pesanan dicek terhadap jadwal meja. Kalau jamnya sudah terisi, sistem menolak dan menunjukkan jam yang masih kosong.</p>
    <div><a class="btn navy" href="<?= BASE_URL ?>/katalog.php">Cek ketersediaan</a></div></div>
  <div class="r"><strong>PS4</strong><img src="<?= BASE_URL ?>/assets/images/controller.svg" alt=""></div>
  <div class="badge-circle"><div><?= $min['h'] ? number_format($min['h'] / 1000) . 'K' : '-' ?><br><small>per jam</small></div></div>
</section>

<section class="sec">
  <div class="sec-title"><h2>Cara menyewa</h2></div>
  <div class="steps">
    <div class="step"><i>1</i><h3>Daftar atau login</h3><p>Buat akun pelanggan, cukup satu menit.</p></div>
    <div class="step"><i>2</i><h3>Pilih meja</h3><p>Lihat tipe PS, TV, fasilitas, dan status meja.</p></div>
    <div class="step"><i>3</i><h3>Tentukan jadwal</h3><p>Isi tanggal, jam mulai, dan durasi. Total biaya muncul langsung.</p></div>
    <div class="step"><i>4</i><h3>Datang dan main</h3><p>Tunjukkan kode pesanan ke kasir.</p></div>
  </div>
</section>
<?php page_foot();
