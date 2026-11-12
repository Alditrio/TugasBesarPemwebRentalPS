<?php
// === INISIALISASI SESSION ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// === IMPLEMENTASI COOKIE (PENCATAT KUNJUNGAN) ===
if (!isset($_COOKIE['last_visit'])) {
    setcookie('last_visit', date('Y-m-d H:i:s'), time() + (30 * 24 * 60 * 60), '/');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($dt) { return date('d M Y, H:i', strtotime($dt)); }
function redirect($url) { header('Location: ' . BASE_URL . '/' . ltrim($url, '/')); exit; }
function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert ' . e($t) . '" data-auto>' . e($m) . '</div>';
    }
}

// === IMPLEMENTASI SESSION & COOKIE UNTUK AUTENTIKASI PENGGUNA ===
function user() {
    global $db;
    // 1. Cek dari SESSION
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    // 2. Jika SESSION belum ada, pulihkan dari COOKIE (remember login)
    if (isset($_COOKIE['user_id']) && isset($_COOKIE['key'])) {
        $c_id = (int)$_COOKIE['user_id'];
        $c_key = $_COOKIE['key'];
        $u = q1($db, "SELECT * FROM users WHERE id=?", 'i', [$c_id]);
        if ($u && hash_equals(hash('sha256', $u['email']), $c_key)) {
            // Sinkronkan kembali ke session
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user'] = [
                'id' => $u['id'],
                'nama' => $u['nama'],
                'email' => $u['email'],
                'role' => $u['role']
            ];
            return $_SESSION['user'];
        }
    }

    return null;
}

function wajib_login($role = null) {
    $u = user();
    if (!$u) {
        $_SESSION['next'] = $_SERVER['REQUEST_URI'];
        flash('Silakan login terlebih dahulu.', 'err');
        redirect('login.php');
    }
    if ($role && $u['role'] !== $role) {
        flash('Anda tidak memiliki akses ke halaman itu.', 'err');
        redirect($u['role'] === 'admin' ? 'admin/' : 'pelanggan/');
    }
}

// Query helper (prepared statement). SELECT -> array baris; INSERT/UPDATE/DELETE -> jumlah baris terpengaruh
function q($db, $sql, $types = '', $p = []) {
    $s = $db->prepare($sql);
    if ($types) $s->bind_param($types, ...$p);
    $s->execute();
    $r = $s->get_result();
    if ($r === false) { $n = $s->affected_rows; $s->close(); return $n; }
    $rows = $r->fetch_all(MYSQLI_ASSOC); $s->close(); return $rows;
}
function q1($db, $sql, $types = '', $p = []) { $r = q($db, $sql, $types, $p); return $r[0] ?? null; }

// Sinkronisasi status transaksi berdasarkan waktu sekarang (monitoring otomatis)
function auto_sync($db) {
    $db->query("UPDATE penyewaan SET status='selesai' WHERE status='berlangsung' AND waktu_selesai <= NOW()");
    $db->query("UPDATE penyewaan SET status='berlangsung' WHERE status='menunggu' AND waktu_mulai <= NOW() AND waktu_selesai > NOW()");
    $db->query("UPDATE penyewaan SET status='dibatalkan' WHERE status='menunggu' AND waktu_selesai <= NOW()");
}
auto_sync($db);

// Unit + status live (tersedia / digunakan / maintenance) berdasarkan transaksi yang sedang berlangsung
const SQL_UNIT = "SELECT u.*, k.nama AS kategori,
  CASE WHEN u.status='maintenance' THEN 'maintenance'
       WHEN EXISTS (SELECT 1 FROM detail_penyewaan d JOIN penyewaan p ON p.id=d.penyewaan_id WHERE d.unit_id=u.id AND p.status='berlangsung') THEN 'digunakan'
       ELSE 'tersedia' END AS status_live,
  (SELECT MAX(p.waktu_selesai) FROM detail_penyewaan d JOIN penyewaan p ON p.id=d.penyewaan_id WHERE d.unit_id=u.id AND p.status='berlangsung') AS selesai_pada
  FROM unit_rental u JOIN kategori k ON k.id=u.kategori_id";

// Cek bentrok jadwal: ada transaksi aktif untuk unit yang waktunya beririsan
function ada_bentrok($db, $unit, $mulai, $selesai) {
    $r = q1($db, "SELECT COUNT(*) c FROM detail_penyewaan d JOIN penyewaan p ON p.id=d.penyewaan_id
                  WHERE d.unit_id=? AND p.status IN ('menunggu','berlangsung') AND p.waktu_mulai < ? AND p.waktu_selesai > ?",
            'iss', [$unit, $selesai, $mulai]);
    return $r['c'] > 0;
}

// Validasi penyewaan. Return [errors[], data|null]. Biaya dihitung server (harga x durasi).
function validasi_sewa($db, $uid, $unit, $tanggal, $jam, $durasi) {
    $err = [];
    $u = q1($db, "SELECT u.*, k.nama kategori FROM unit_rental u JOIN kategori k ON k.id=u.kategori_id WHERE u.id=?", 'i', [(int)$unit]);
    if (!$u) $err[] = 'Pilih meja rental terlebih dahulu.';
    elseif ($u['status'] !== 'aktif') $err[] = 'Meja sedang maintenance dan tidak dapat disewa.';
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$d || $d->format('Y-m-d') !== $tanggal) $err[] = 'Tanggal tidak valid.';
    elseif ($tanggal < date('Y-m-d')) $err[] = 'Tanggal tidak boleh sebelum hari ini.';
    elseif ($tanggal > date('Y-m-d', strtotime('+30 days'))) $err[] = 'Pemesanan maksimal 30 hari ke depan.';
    $jam = (int)$jam; $durasi = (int)$durasi;
    if ($jam < JAM_BUKA || $jam >= JAM_TUTUP) $err[] = 'Jam mulai harus antara ' . JAM_BUKA . ':00 - ' . (JAM_TUTUP - 1) . ':00.';
    if ($durasi < 1 || $durasi > 12) $err[] = 'Durasi harus 1 sampai 12 jam.';
    if ($jam + $durasi > JAM_TUTUP) $err[] = 'Rental tutup pukul ' . JAM_TUTUP . ':00, kurangi durasi atau mulai lebih awal.';
    if (!$uid || !q1($db, "SELECT id FROM users WHERE id=? AND role='pelanggan'", 'i', [(int)$uid])) $err[] = 'Pelanggan tidak valid.';
    if ($err) return [$err, null];
    $mulai = sprintf('%s %02d:00:00', $tanggal, $jam);
    $selesai = date('Y-m-d H:i:s', strtotime($mulai) + $durasi * 3600);
    if (strtotime($mulai) < strtotime(date('Y-m-d H:00:00'))) $err[] = 'Jam mulai sudah terlewat.';
    elseif (ada_bentrok($db, $u['id'], $mulai, $selesai)) $err[] = 'Jadwal bentrok: meja sudah dipesan pada waktu tersebut. Pilih jam atau meja lain.';
    if ($err) return [$err, null];
    return [[], ['unit' => $u, 'mulai' => $mulai, 'selesai' => $selesai, 'durasi' => $durasi,
                 'harga' => (int)$u['harga_per_jam'], 'total' => (int)$u['harga_per_jam'] * $durasi, 'user_id' => (int)$uid]];
}

// Simpan transaksi (transaksi DB + lock baris unit agar tidak terjadi double booking)
function simpan_sewa($db, $data) {
    $db->begin_transaction();
    try {
        q($db, "SELECT id FROM unit_rental WHERE id=? FOR UPDATE", 'i', [$data['unit']['id']]);
        if (ada_bentrok($db, $data['unit']['id'], $data['mulai'], $data['selesai'])) { $db->rollback(); return false; }
        $kode = 'PS' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        q($db, "INSERT INTO penyewaan (kode,user_id,waktu_mulai,waktu_selesai,durasi_jam,total_biaya,status) VALUES (?,?,?,?,?,?, 'menunggu')",
          'sissii', [$kode, $data['user_id'], $data['mulai'], $data['selesai'], $data['durasi'], $data['total']]);
        $id = $db->insert_id;
        q($db, "INSERT INTO detail_penyewaan (penyewaan_id,unit_id,harga_per_jam,durasi_jam,subtotal) VALUES (?,?,?,?,?)",
          'iiiii', [$id, $data['unit']['id'], $data['harga'], $data['durasi'], $data['total']]);
        $db->commit(); auto_sync($db); return $kode;
    } catch (Exception $ex) { $db->rollback(); return false; }
}

function badge($status) { return '<span class="badge b-' . e($status) . '">' . e(ucfirst($status)) . '</span>'; }
// Maskot: tiap karakter punya peran. Robot biru = penjaga jadwal / unit maintenance.
const MASKOT_POOL = ['PS3' => [3, 7, 19, 21], 'PS4' => [1, 6, 10, 22, 23, 24], 'PS5' => [4, 5, 8, 9, 12]];
const AVATAR_POOL = [1, 5, 6, 8, 9, 10, 22, 23, 24, 2, 4, 12];
function maskot($i) { return BASE_URL . '/assets/images/maskot/m' . sprintf('%02d', $i) . '.png'; }
function maskot_unit($u) {
    if (($u['status'] ?? '') === 'maintenance') return 17;
    $p = MASKOT_POOL[$u['kategori'] ?? ''] ?? [5];
    return $p[$u['id'] % count($p)];
}
function avatar_user($id) { return AVATAR_POOL[$id % count(AVATAR_POOL)]; }
function kosong($pesan, $i = 0) { return '<p class="meta" style="padding:10px 0">' . $pesan . '</p>'; }
function foto_unit($u) {
    return $u['foto'] ? BASE_URL . '/assets/images/unit/' . rawurlencode($u['foto']) : maskot(maskot_unit($u));
}

// Kartu meja rental (landing, katalog, dashboard)
function kartu_unit($u, $href, $label = 'Sewa', $slot_bentrok = null) {
    $s = $u['status_live'];
    if ($slot_bentrok !== null && $s !== 'maintenance') $s = $slot_bentrok ? 'digunakan' : 'tersedia';
    $txt = ['tersedia' => 'Tersedia', 'digunakan' => $slot_bentrok === null ? 'Sedang digunakan' : 'Terisi di jam ini', 'maintenance' => 'Maintenance'][$s];
    echo '<article class="card-unit"><div class="thumb t-' . strtolower(e($u['kategori'])) . '"><img class="' . ($u['foto'] ? 'foto' : 'mk') . '" src="' . e(foto_unit($u)) . '" alt="' . e($u['nama']) . '"><span class="pill-ps">' . e($u['kategori']) . '</span></div>';
    echo '<div class="body"><h3>' . e($u['nama']) . '</h3><p class="meta">' . e($u['jenis_tv']) . '</p><p class="fas">' . e($u['fasilitas']) . '</p>';
    echo '<div class="foot"><div><strong class="harga">' . rupiah($u['harga_per_jam']) . '</strong><small>/jam</small></div><span class="dot d-' . $s . '">' . $txt . '</span></div>';
    if ($u['status_live'] === 'digunakan' && $u['selesai_pada'] && $slot_bentrok === null) echo '<p class="meta">Kosong lagi sekitar ' . date('H:i', strtotime($u['selesai_pada'])) . '</p>';
    echo '</div>';
    if ($href && $u['status_live'] !== 'maintenance') echo '<a class="go" href="' . e($href) . '" aria-label="' . e($label) . ' ' . e($u['nama']) . '" title="' . e($label) . '">&#10095;</a>';
    echo '</article>';
}
