-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 11, 2026 at 04:58 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rm_tambaksari`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int NOT NULL,
  `id_pesanan` int DEFAULT NULL,
  `id_menu` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `harga_satuan` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `status_memasak` enum('antre','dimasak','selesai','diantar') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_menu`, `jumlah`, `harga_satuan`, `subtotal`, `status_memasak`) VALUES
(1, 3, 2, 1, 5000.00, 5000.00, 'diantar'),
(2, 3, 3, 1, 25000.00, 25000.00, 'diantar'),
(3, 4, 2, 1, 5000.00, 5000.00, 'diantar'),
(4, 4, 5, 1, 25000.00, 25000.00, 'diantar'),
(5, 5, 3, 1, 25000.00, 25000.00, 'diantar'),
(6, 6, 3, 1, 25000.00, 25000.00, 'selesai'),
(7, 7, 2, 1, 5000.00, 5000.00, 'selesai'),
(8, 7, 3, 1, 25000.00, 25000.00, 'antre'),
(9, 8, 8, 1, 25000.00, 25000.00, 'antre'),
(10, 9, 4, 1, 25000.00, 25000.00, 'antre');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'minuman'),
(3, 'Makanan');

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id_meja` int NOT NULL,
  `nomor_meja` int DEFAULT NULL,
  `status_meja` enum('kosong','terisi') DEFAULT NULL,
  `qr_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id_meja`, `nomor_meja`, `status_meja`, `qr_code`) VALUES
(1, 1, 'terisi', 'meja_1'),
(2, 2, 'kosong', 'meja_2');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int NOT NULL,
  `id_kategori` int DEFAULT NULL,
  `nama_menu` varchar(100) DEFAULT NULL,
  `harga` decimal(12,2) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text,
  `status_stok` enum('tersedia','habis') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id_menu`, `id_kategori`, `nama_menu`, `harga`, `foto`, `deskripsi`, `status_stok`) VALUES
(2, 1, 'Es Teh', 5000.00, '1790653853_6abb359d5427b.jpg', 'enak', 'tersedia'),
(3, 3, 'Gurami Bakar', 25000.00, '1790653736_6abb3528afcdd.jpg', '', 'tersedia'),
(4, 3, 'Ayam Bakar', 25000.00, '1790659074_6abb4a0206250.jpg', '', 'tersedia'),
(5, 3, 'Gurame Bakar saus asam manis', 25000.00, '1791686822_6acaf8a620b5b.jpeg', '', 'tersedia'),
(6, 3, 'Nila bakar saus madu', 25000.00, '1791686874_6acaf8dab9a60.jpeg', '', 'tersedia'),
(7, 3, 'Nila Goreng', 25000.00, '1791686908_6acaf8fca8af8.jpeg', '', 'tersedia'),
(8, 3, 'Lele Bakar', 25000.00, '1791687104_6acaf9c0be148.jpeg', '', 'tersedia'),
(9, 3, 'Lele Goreng', 25000.00, '1791687144_6acaf9e8d5571.jpeg', '', 'tersedia'),
(10, 3, 'Ayam Goreng Krispi', 30000.00, '1791687185_6acafa119d0dc.jpeg', '', 'tersedia'),
(11, 3, 'Udang Saus Bakar', 20000.00, '1791687235_6acafa43333c8.jpeg', '', 'tersedia'),
(12, 3, 'Udang Bakar Madu', 20000.00, '1791687279_6acafa6fa0c21.jpeg', '', 'tersedia'),
(13, 3, 'Cumi Saus Padang', 25000.00, '1791687351_6acafab723c7a.jpeg', '', 'tersedia'),
(14, 3, 'Cumi Krispi', 20000.00, '1791687397_6acafae517ed6.jpeg', '', 'tersedia'),
(15, 3, 'Udang Krispi', 20000.00, '1791687492_6acafb4426fd2.jpeg', '', 'tersedia'),
(16, 3, 'Sup Gurame Bumbu Kemiri', 30000.00, '1791687687_6acafc07423ca.jpeg', '', 'tersedia'),
(17, 3, 'Nasi Goreng Seafood', 30000.00, '1791687865_6acafcb932917.jpeg', '', 'tersedia'),
(18, 3, 'Nasi Groreng Ayam', 30000.00, '1791687982_6acafd2ee7e11.jpeg', '', 'tersedia'),
(19, 3, 'Kwitiaw Rebus', 25000.00, '1791688125_6acafdbde7094.jpeg', '', 'tersedia'),
(20, 3, 'Sup Keong Daun', 25000.00, '1791688179_6acafdf3b828a.jpeg', '', 'tersedia'),
(21, 3, 'Kwitiaw Goreng', 20000.00, '1791688232_6acafe2805150.jpeg', '', 'tersedia'),
(22, 3, 'Nasi Putih', 5000.00, '1791688285_6acafe5d8d327.jpeg', '', 'tersedia'),
(23, 3, 'Lalapan & Sambal', 8000.00, '1791688327_6acafe87a951f.jpeg', '', 'tersedia'),
(24, 3, 'Bakwan Sayur', 8000.00, '1791688369_6acafeb1224ab.jpeg', '', 'tersedia'),
(25, 3, 'Bakwan Jagung', 8000.00, '1791688424_6acafee85dd02.jpeg', '', 'tersedia'),
(26, 3, 'Mendoan', 8000.00, '1791688462_6acaff0e89a65.jpeg', '', 'tersedia'),
(27, 3, 'Kentang Groreng', 8000.00, '1791688502_6acaff36c3b73.jpeg', '', 'tersedia'),
(28, 3, 'Tempe Tahu Goreng', 5000.00, '1791688545_6acaff61b26da.jpeg', '', 'tersedia'),
(29, 3, 'Tumis Kangkung', 10000.00, '1791688608_6acaffa02feb8.jpeg', '', 'tersedia'),
(30, 1, 'Es Jeruk', 5000.00, '1791688689_6acafff177a67.jpeg', '', 'tersedia'),
(31, 1, 'Teh Panas', 3000.00, '1791688726_6acb001678bbe.jpeg', '', 'tersedia'),
(32, 1, 'Es Sirup', 5000.00, '1791689037_6acb014d24cca.jpg', '', 'tersedia'),
(33, 1, 'Es Cappucino', 5000.00, '1791689087_6acb017f38ded.jpeg', '', 'tersedia'),
(34, 1, 'Es Degan', 5000.00, '1791689125_6acb01a5d623e.jpeg', '', 'tersedia'),
(35, 1, 'Wedang Jahe', 5000.00, '1791689162_6acb01ca53adb.jpeg', '', 'tersedia'),
(36, 1, 'Kopi Hitam', 5000.00, '1791689202_6acb01f21d28c.jpeg', '', 'tersedia'),
(37, 1, 'Air Mineral', 3000.00, '1791689309_6acb025d21e18.jpg', '', 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL,
  `id_pesanan` int DEFAULT NULL,
  `metode_pembayaran` enum('cash','qris') DEFAULT NULL,
  `jumlah_bayar` decimal(12,2) DEFAULT NULL,
  `tanggal_bayar` datetime DEFAULT NULL,
  `status_pembayaran` enum('berhasil','pending') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int NOT NULL,
  `id_user` int DEFAULT NULL,
  `id_meja` int DEFAULT NULL,
  `total_harga` decimal(12,2) DEFAULT NULL,
  `status_bayar` enum('belum_bayar','lunas') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `metode_bayar` enum('debit','cash','qris','e-wallet') NOT NULL,
  `catatan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_user`, `id_meja`, `total_harga`, `status_bayar`, `created_at`, `metode_bayar`, `catatan`) VALUES
(1, 5, 1, 35000.00, 'lunas', '2026-10-11 10:40:56', 'debit', ''),
(2, 5, 1, 35000.00, 'lunas', '2026-10-11 10:41:35', 'debit', ''),
(3, 5, 1, 35000.00, 'lunas', '2026-10-11 10:42:11', 'debit', ''),
(4, 5, 1, 35000.00, 'lunas', '2026-10-11 10:44:43', 'debit', ''),
(5, 5, 1, 29500.00, 'lunas', '2026-10-11 10:47:47', 'debit', ''),
(6, 5, 1, 29500.00, 'lunas', '2026-10-11 10:48:09', 'debit', ''),
(7, NULL, 1, 35000.00, 'lunas', '2026-10-11 10:56:51', 'debit', ''),
(8, NULL, 1, 29500.00, 'lunas', '2026-10-11 11:14:17', 'cash', ''),
(9, NULL, 1, 29500.00, 'lunas', '2026-10-11 11:20:12', 'cash', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','pelayan','koki') DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `nama`) VALUES
(3, 'pelayan', '123', 'pelayan', 'pelayan'),
(4, 'koki', 'koki', 'koki', 'koki'),
(5, 'admin', 'admin', 'admin', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_pesanan` (`id_pesanan`),
  ADD KEY `fk_detail_menu` (`id_menu`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id_meja`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `fk_menu_kategori` (`id_kategori`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `fk_pembayaran_pesanan` (`id_pesanan`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `fk_pesanan_user` (`id_user`),
  ADD KEY `fk_pesanan_meja` (`id_meja`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id_meja` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_meja` FOREIGN KEY (`id_meja`) REFERENCES `meja` (`id_meja`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pesanan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
