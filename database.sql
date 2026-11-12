-- Database Sistem Informasi Rental PlayStation
CREATE DATABASE IF NOT EXISTS rental_ps CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE rental_ps;

DROP TABLE IF EXISTS detail_penyewaan;
DROP TABLE IF EXISTS penyewaan;
DROP TABLE IF EXISTS unit_rental;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  no_hp VARCHAR(20) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','pelanggan') NOT NULL DEFAULT 'pelanggan',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(20) NOT NULL UNIQUE,      -- PS3 / PS4 / PS5
  deskripsi VARCHAR(150) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE unit_rental (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori_id INT NOT NULL,
  nama VARCHAR(60) NOT NULL UNIQUE,      -- nama / nomor meja
  jenis_tv VARCHAR(80) NOT NULL,
  fasilitas VARCHAR(255) DEFAULT NULL,
  harga_per_jam INT NOT NULL,
  status ENUM('aktif','maintenance') NOT NULL DEFAULT 'aktif',
  foto VARCHAR(120) DEFAULT NULL,
  CONSTRAINT fk_unit_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE penyewaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  waktu_mulai DATETIME NOT NULL,
  waktu_selesai DATETIME NOT NULL,
  durasi_jam INT NOT NULL,
  total_biaya INT NOT NULL,
  status ENUM('menunggu','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_waktu (waktu_mulai, waktu_selesai),
  CONSTRAINT fk_sewa_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE detail_penyewaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penyewaan_id INT NOT NULL,
  unit_id INT NOT NULL,
  harga_per_jam INT NOT NULL,            -- harga saat transaksi (snapshot)
  durasi_jam INT NOT NULL,
  subtotal INT NOT NULL,
  CONSTRAINT fk_detail_sewa FOREIGN KEY (penyewaan_id) REFERENCES penyewaan(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_detail_unit FOREIGN KEY (unit_id) REFERENCES unit_rental(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- DATA AWAL  (admin: admin@rentalps.test / admin123 ; pelanggan: budi@mail.test / user123)
INSERT INTO users (nama,email,no_hp,password,role) VALUES
('Administrator','admin@rentalps.test','081200000000','$2y$10$4CK1EewC/OfA5UU7xbmEkeXTsUEv.njrYbAw5TdOcrvlpeypQxlOG','admin'),
('Budi Santoso','budi@mail.test','081311112222','$2y$10$1ucwIXpc5LsOz1JpTp32IO.DrKvmQh935kERwTbYO8qjrgeYqlW0.','pelanggan');

INSERT INTO kategori (nama,deskripsi) VALUES
('PS3','Klasik, game legendaris dan harga hemat'),
('PS4','Game terbaru dan multiplayer seru'),
('PS5','Grafis 4K, DualSense, loading super cepat');

INSERT INTO unit_rental (kategori_id,nama,jenis_tv,fasilitas,harga_per_jam,status) VALUES
(1,'Meja 01 - Retro','TV LED 32 inci','2 stik, kipas angin, sofa',5000,'aktif'),
(1,'Meja 02 - Retro','TV LED 32 inci','2 stik, kipas angin, sofa',5000,'aktif'),
(2,'Meja 03 - Arena','TV LED 43 inci','2 stik, AC, headset, sofa',8000,'aktif'),
(2,'Meja 04 - Arena','TV LED 43 inci','2 stik, AC, headset, sofa',8000,'aktif'),
(2,'Meja 05 - Squad','Monitor 27 inci 144Hz','3 stik, AC, headset, meja snack',10000,'aktif'),
(3,'Meja 06 - Next Gen','TV 4K 50 inci','2 DualSense, AC, headset, sofa',15000,'aktif'),
(3,'Meja 07 - Next Gen','TV 4K 50 inci','2 DualSense, AC, headset, sofa',15000,'aktif'),
(3,'Bilik 08 - VIP','TV 4K 65 inci','4 DualSense, AC, sound system, sofa, kulkas mini',25000,'aktif'),
(3,'Bilik 09 - VIP','Monitor 32 inci 4K','2 DualSense, AC, headset gaming',20000,'maintenance');
