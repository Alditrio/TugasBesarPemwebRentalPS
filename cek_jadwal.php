<?php
// Endpoint JSON: daftar jam terisi untuk satu unit pada satu tanggal (dipakai JavaScript di form sewa)
require_once __DIR__ . '/config/functions.php';
header('Content-Type: application/json; charset=utf-8');
$unit = (int)($_GET['unit'] ?? 0); $tgl = $_GET['tanggal'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$unit || !$d || $d->format('Y-m-d') !== $tgl) { echo '[]'; exit; }
$rows = q($db, "SELECT DATE_FORMAT(p.waktu_mulai,'%H:%i') mulai, DATE_FORMAT(p.waktu_selesai,'%H:%i') selesai
  FROM detail_penyewaan d JOIN penyewaan p ON p.id=d.penyewaan_id
  WHERE d.unit_id=? AND p.status IN ('menunggu','berlangsung') AND DATE(p.waktu_mulai)<=? AND p.waktu_selesai>?
  ORDER BY p.waktu_mulai", 'iss', [$unit, $tgl, $tgl . ' 00:00:00']);
echo json_encode($rows);
