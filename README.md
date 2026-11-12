# TugasBesarPemwebRentalPS - PlayZone
Sistem Informasi Manajemen Penyewaan PlayStation (PHP Native + MySQL)

Project Akhir Mata Kuliah Pemrograman Web I.

## 🚀 Fitur Utama
- **Katalog & Sewa Meja PlayStation (PS3, PS4, PS5)**: Pemilihan unit meja rental, durasi sewa, dan pencegahan bentrok jadwal otomatis.
- **Autentikasi Dual Role**:
  - **Admin**: Monitoring meja live, kelola unit/meja, kelola pelanggan, transaksi walk-in, rekap dan laporan pendapatan.
  - **Pelanggan**: Sewa meja online, cek ketersediaan, riwayat penyewaan, dan status pemesanan.
- **Implementasi Session & Cookie PHP**:
  - **Session**: Manajemen status login (`$_SESSION['user']`, `$_SESSION['login']`), pembatasan hak akses (`wajib_login`), flash message notifikasi.
  - **Cookie**: Remember login / auto-login (7 hari), auto-fill email login terakhir (30 hari), dan pencatatan kunjungan terakhir (`$_COOKIE['last_visit']`).

## 🛠️ Panduan Instalasi & Menjalankan (XAMPP)
1. Buka **XAMPP Control Panel**, lalu aktifkan modul **Apache** dan **MySQL**.
2. Salin folder proyek ini ke `C:\xampp\htdocs\rental-ps`.
3. Buka browser ke `http://localhost/phpmyadmin/`.
4. Buat database atau langsung buka tab **Import**, pilih file `database.sql`, lalu klik **Go**.
5. Buka website di browser: `http://localhost/rental-ps/`.

> **Catatan**: Jika nama folder di `htdocs` berbeda atau MySQL memiliki password, sesuaikan konfigurasi di `config/db.php`.

## 🔑 Akun Demo
| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@rentalps.test` | `admin123` |
| **Pelanggan** | `budi@mail.test` | `user123` |
