-- ============================================================
-- SQL DUMP: Sistem Informasi Inventaris Barang (MySQL / MariaDB Compatible)
-- Mendukung Multi-Barang (Header-Detail) & ID dengan Awalan KTG / BRG
-- ============================================================

CREATE DATABASE IF NOT EXISTS `inventaris_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventaris_db`;

-- Matikan foreign key checks sementara untuk reset tabel lama
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `borrowing_details`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `borrowers`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `categories`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Kategori Barang (ID dengan awalan KTG-xxx)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'box',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Data Barang (ID & Kode dengan awalan BRG-xxx)
CREATE TABLE IF NOT EXISTS `items` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `category_id` VARCHAR(50) NOT NULL,
    `total_stock` INT NOT NULL DEFAULT 1,
    `available_stock` INT NOT NULL DEFAULT 1,
    `location` VARCHAR(100) DEFAULT 'Gudang Utama',
    `condition` VARCHAR(50) DEFAULT 'Baik',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Peminjam
CREATE TABLE IF NOT EXISTS `borrowers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `identity_number` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(100),
    `department` VARCHAR(100) NOT NULL,
    `status` VARCHAR(30) DEFAULT 'Aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Header Transaksi Peminjaman
CREATE TABLE IF NOT EXISTS `borrowings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `borrower_id` INT NOT NULL,
    `borrow_date` DATE NOT NULL,
    `expected_return_date` DATE NOT NULL,
    `actual_return_date` DATE NULL,
    `status` ENUM('Dipinjam', 'Kembali', 'Terlambat') DEFAULT 'Dipinjam',
    `purpose` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`borrower_id`) REFERENCES `borrowers`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Detail Peminjaman (Dapat Meminjam Lebih dari Satu Barang)
CREATE TABLE IF NOT EXISTS `borrowing_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `borrowing_id` INT NOT NULL,
    `item_id` VARCHAR(50) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `return_condition` VARCHAR(50) NULL,
    `return_notes` TEXT NULL,
    `status` ENUM('Dipinjam', 'Kembali', 'Terlambat') DEFAULT 'Dipinjam',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

-- Data Awal Kategori (ID berawalan KTG-)
INSERT INTO `categories` (`id`, `name`, `icon`, `description`) VALUES
('KTG-001', 'Elektronik & Gadget', 'laptop', 'Laptop, Tablet, dan perangkat gadget operasional'),
('KTG-002', 'Audio & Visual', 'video', 'Proyektor, Microphone, Sound System, dan Layar'),
('KTG-003', 'IT & Networking', 'network', 'Switch, Router, Access Point, dan Kabel LAN'),
('KTG-004', 'Alat Laboratorium', 'flask-conical', 'Peralatan ukur, mikroskop, dan sensor'),
('KTG-005', 'Peralatan & Perkakas', 'wrench', 'Perkakas teknik, bor listrik, dan toolkit'),
('KTG-006', 'Dokumentasi & Kamera', 'camera', 'Kamera DSLR, Mirrorless, Tripod, Lighting');

-- Data Awal Barang (ID berawalan BRG-)
INSERT INTO `items` (`id`, `code`, `name`, `category_id`, `total_stock`, `available_stock`, `location`, `condition`, `description`) VALUES
('BRG-001', 'BRG-001', 'MacBook Pro M2 14" (Silver)', 'KTG-001', 5, 3, 'Ruang IT Lantai 2', 'Baik', 'Spesifikasi RAM 16GB, SSD 512GB untuk kegiatan multimedia dan presentasi.'),
('BRG-002', 'BRG-002', 'Laptop ASUS ROG Strix G15', 'KTG-001', 4, 3, 'Lab Komputer 1', 'Baik', 'RAM 16GB, RTX 3060, untuk pengolahan grafis berat dan simulasi.'),
('BRG-003', 'BRG-003', 'Proyektor Epson EB-X500 3600 Lumens', 'KTG-002', 8, 5, 'Ruang Perlengkapan', 'Baik', 'Lengkap dengan kabel power, tas jinjing, dan kabel HDMI 5m.'),
('BRG-004', 'BRG-004', 'Layar Proyektor Tripod 70 inch', 'KTG-002', 6, 4, 'Gudang Utama', 'Baik', 'Layar portable dengan tripod kokoh.'),
('BRG-005', 'BRG-005', 'Kamera Sony Alpha A7 III + Lensa 28-70mm', 'KTG-006', 3, 2, 'Ruang Multimedia', 'Baik', 'Paket kamera Mirrorless Full Frame, 2 baterai, dan memory card 64GB.'),
('BRG-006', 'BRG-006', 'Wireless Microphone Saramonic Blink 500', 'KTG-002', 4, 2, 'Ruang Perlengkapan', 'Baik', 'Dual transmitter microphone clip-on untuk seminar dan video recording.'),
('BRG-007', 'BRG-007', 'Mikroskop Binokuler Olympus CX23', 'KTG-004', 10, 9, 'Laboratorium Biologi', 'Baik', 'Pembesaran hingga 1000x dengan lampu LED.'),
('BRG-008', 'BRG-008', 'Oscilloscope Digital Rigol DS1054Z', 'KTG-004', 5, 4, 'Laboratorium Elektro', 'Baik', 'Oscilloscope 4 channel 50MHz untuk pengujian rangkaian listrik.'),
('BRG-009', 'BRG-009', 'Crimping Tool & Cable Tester Proskit', 'KTG-003', 7, 7, 'Ruang IT Lantai 2', 'Baik', 'Peralatan instalasi kabel jaringan RJ45 dan RJ11.'),
('BRG-010', 'BRG-010', 'Bor Listrik Cordless Bosch GSB 180-LI', 'KTG-005', 3, 3, 'Bengkel Pemeliharaan', 'Baik', 'Bor baterai 18V dengan set mata bor lengkap.');

-- Data Awal Peminjam
INSERT INTO `borrowers` (`id`, `identity_number`, `name`, `phone`, `email`, `department`, `status`) VALUES
(1, '202301001', 'Ahmad Fadhilah, S.Kom', '081234567890', 'ahmad.fadhil@univ.ac.id', 'Dosen Teknik Informatika', 'Aktif'),
(2, '202302005', 'Siti Rahmawati Putri', '082198765432', 'siti.rahma@student.ac.id', 'BEM Fakultas / Mahasiswa', 'Aktif'),
(3, '202301012', 'Budi Santoso, M.T.', '085712344321', 'budi.santoso@univ.ac.id', 'Laboratorium Jaringan', 'Aktif'),
(4, '202403020', 'Dewi Lestari Anggraeni', '087811223344', 'dewi.anggraeni@univ.ac.id', 'Bagian Humas & Dokumentasi', 'Aktif'),
(5, '202405011', 'Rian Hidayat', '081399887766', 'rian.hidayat@student.ac.id', 'Himpunan Mahasiswa Elektro', 'Aktif');

-- Data Awal Header Transaksi Peminjaman
INSERT INTO `borrowings` (`id`, `code`, `borrower_id`, `borrow_date`, `expected_return_date`, `actual_return_date`, `status`, `purpose`, `notes`) VALUES
(1, 'TRX-202610-001', 1, '2026-10-26', '2026-10-29', NULL, 'Dipinjam', 'Workshop Kecerdasan Buatan dan Data Science', 'Diambil langsung di ruang IT, kabel charger lengkap.'),
(2, 'TRX-202610-002', 2, '2026-10-27', '2026-10-30', NULL, 'Dipinjam', 'Kegiatan Sidang Pleno BEM dan Presentasi Organisasi', 'Dilengkapi 3 kabel HDMI dan tas proyektor.'),
(3, 'TRX-202610-003', 4, '2026-10-24', '2026-10-29', NULL, 'Dipinjam', 'Dokumentasi Kunjungan Kerja Industri dan Liputan Dies Natalis', 'Termasuk 2 baterai original dan strap kamera.'),
(4, 'TRX-202610-004', 3, '2026-10-22', '2026-10-26', NULL, 'Terlambat', 'Praktikum Jaringan Komputer Lanjut', 'Konfirmasi terlambat 1 hari karena uji performa modul.'),
(5, 'TRX-202610-005', 5, '2026-10-20', '2026-10-24', '2026-10-24', 'Kembali', 'Penelitian Pengamatan Sampel Organik', 'Dikembalikan tepat waktu dalam kondisi sangat bersih.');

-- Data Awal Detail Peminjaman (Satu Transaksi Bisa Meminjam Lebih dari 1 Barang)
-- Transaksi 1 meminjam MacBook (2 unit) & Layar Tripod (2 unit)
INSERT INTO `borrowing_details` (`borrowing_id`, `item_id`, `quantity`, `return_condition`, `return_notes`, `status`) VALUES
(1, 'BRG-001', 2, NULL, NULL, 'Dipinjam'),
(1, 'BRG-004', 2, NULL, NULL, 'Dipinjam'),

-- Transaksi 2 meminjam Proyektor Epson (3 unit) & Wireless Mic (2 unit)
(2, 'BRG-003', 3, NULL, NULL, 'Dipinjam'),
(2, 'BRG-006', 2, NULL, NULL, 'Dipinjam'),

-- Transaksi 3 meminjam Kamera Sony (1 unit)
(3, 'BRG-005', 1, NULL, NULL, 'Dipinjam'),

-- Transaksi 4 meminjam Laptop ROG (1 unit)
(4, 'BRG-002', 1, NULL, NULL, 'Terlambat'),

-- Transaksi 5 meminjam Mikroskop (1 unit) dan Oscilloscope (1 unit) - Keduanya Sudah Kembali
(5, 'BRG-007', 1, 'Baik', 'Lensa bersih', 'Kembali'),
(5, 'BRG-008', 1, 'Baik', 'Probe lengkap dan normal', 'Kembali');
