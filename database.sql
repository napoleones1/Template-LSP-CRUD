-- ============================================
-- SIPEKA - Sistem Informasi Penggajian Karyawan
-- Database: db_penggajian
-- ============================================

CREATE DATABASE IF NOT EXISTS `db_penggajian` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_penggajian`;

-- ============================================
-- Tabel: jabatan
-- ============================================
CREATE TABLE IF NOT EXISTS `jabatan` (
    `id_jabatan` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_jabatan` VARCHAR(50) NOT NULL,
    `gapok` INT NOT NULL DEFAULT 0,
    `tunjangan_makan` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel: karyawan
-- ============================================
CREATE TABLE IF NOT EXISTS `karyawan` (
    `id_karyawan` INT AUTO_INCREMENT PRIMARY KEY,
    `nik` VARCHAR(20) NOT NULL UNIQUE,
    `nama_karyawan` VARCHAR(100) NOT NULL,
    `id_jabatan` INT NOT NULL,
    `tgl_masuk` DATE NOT NULL,
    CONSTRAINT `fk_karyawan_jabatan` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatan`(`id_jabatan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel: pinjaman
-- ============================================
CREATE TABLE IF NOT EXISTS `pinjaman` (
    `id_pinjaman` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `jumlah_pinjaman` INT NOT NULL DEFAULT 0,
    `tenor` INT NOT NULL DEFAULT 1,
    `cicilan_per_bulan` INT NOT NULL DEFAULT 0,
    `status` ENUM('Aktif','Lunas') NOT NULL DEFAULT 'Aktif',
    CONSTRAINT `fk_pinjaman_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id_karyawan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel: penggajian
-- ============================================
CREATE TABLE IF NOT EXISTS `penggajian` (
    `id_penggajian` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `bulan_tahun` VARCHAR(20) NOT NULL,
    `potongan_pinjaman` INT NOT NULL DEFAULT 0,
    `gaji_bersih` INT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_penggajian_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id_karyawan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Tabel: users
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Data: users (admin / admin123)
-- password_hash('admin123', PASSWORD_DEFAULT)
-- ============================================
INSERT INTO `users` (`username`, `password`) VALUES
('admin', '$2y$10$Rrl/Byvc037CF4a8dk.GneflU810j/iVntQYf9eXX6AwMo.Nc/eTS');

-- ============================================
-- Data: jabatan
-- ============================================
INSERT INTO `jabatan` (`nama_jabatan`, `gapok`, `tunjangan_makan`) VALUES
('Direktur', 15000000, 1500000),
('Manager', 10000000, 1200000),
('Supervisor', 7500000, 1000000),
('Staff Admin', 4500000, 750000),
('Staff Operasional', 4000000, 750000),
('OB / Office Boy', 3000000, 500000);

-- ============================================
-- Data: karyawan
-- ============================================
INSERT INTO `karyawan` (`nik`, `nama_karyawan`, `id_jabatan`, `tgl_masuk`) VALUES
('EMP-001', 'Budi Santoso', 2, '2019-03-15'),
('EMP-002', 'Siti Rahayu', 3, '2020-06-01'),
('EMP-003', 'Andi Wijaya', 4, '2021-01-10'),
('EMP-004', 'Dewi Lestari', 4, '2021-07-20'),
('EMP-005', 'Reza Pratama', 5, '2022-02-14'),
('EMP-006', 'Fitriani Noor', 5, '2022-09-05');

-- ============================================
-- Data: pinjaman (contoh)
-- ============================================
INSERT INTO `pinjaman` (`id_karyawan`, `jumlah_pinjaman`, `tenor`, `cicilan_per_bulan`, `status`) VALUES
(3, 5000000, 5, 1000000, 'Aktif'),
(5, 3000000, 3, 1000000, 'Aktif'),
(2, 6000000, 6, 1000000, 'Lunas');
