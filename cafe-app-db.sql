-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 06:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_app`
--
CREATE DATABASE IF NOT EXISTS `cafe_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cafe_app`;

-- --------------------------------------------------------

--
-- Table structure for table `banner`
--

CREATE TABLE `banner` (
  `id_image` int(11) NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `keterangan` enum('carousel_banner','popup_banner') NOT NULL,
  `urutan` int(2) NOT NULL DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banner`
--

INSERT INTO `banner` (`id_image`, `gambar`, `keterangan`, `urutan`, `status`, `created_at`) VALUES
(5, 'assets/uploads/banner/carousel/banner_carousel_u4_20261102_093239_c1f95539.png', 'carousel_banner', 0, 1, '2026-11-02 08:32:39'),
(6, 'assets/uploads/banner/popup/banner_popup_u4_20261109_144032_1f201073.png', 'popup_banner', 0, 1, '2026-11-09 13:40:32'),
(7, 'assets/uploads/banner/carousel/banner_carousel_u4_20261109_144049_c4d958fa.png', 'carousel_banner', 3, 1, '2026-11-09 13:40:49'),
(8, 'assets/uploads/banner/carousel/banner_carousel_u4_20261109_144113_2d7c2e7d.jpg', 'carousel_banner', 2, 1, '2026-11-09 13:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id_menu` int(11) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` enum('coffe','non_coffe','pastry_and_dessert','rice_bowl','pasta','snack') NOT NULL,
  `harga` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id_menu`, `nama_menu`, `kategori`, `harga`, `deskripsi`, `gambar`) VALUES
(1, 'Aren Coffee', 'coffe', 22000, 'Perpaduan expresso, susu segar, dan gula aren dengan cita rasa manis yang khas', 'assets/uploads/menu/menu_coffe_u4_20261112_150026_16d9d04d.jpg'),
(2, 'Americano', 'coffe', 18000, 'Expresso dengan tambahan air, menghasilkan rasa kopi yang ringan dan menyegarkan', 'assets/uploads/menu/menu_coffe_u4_20261112_150204_3bb68f94.jpg'),
(3, 'Cafe Latte', 'coffe', 23000, 'Expresso yang dipadukan dengan susu steamed, menghasilkan rasa lembut dan creamy', 'assets/uploads/menu/menu_coffe_u4_20261112_150359_e46f6e69.jpg'),
(4, 'Cappucino', 'coffe', 23000, 'Perpaduan expresso, steamed milk, dan foam susu dengan rasa kopi yang seimbang', 'assets/uploads/menu/menu_coffe_u4_20261112_150530_03bf5c75.jpg'),
(5, 'Caramel Latte', 'coffe', 25000, 'Expresso dan susu segar dengan tambahan sirup karamel yang manis dan harum', 'assets/uploads/menu/menu_coffe_u4_20261112_150839_452ec6a1.jpg'),
(6, 'Chocolate', 'non_coffe', 22000, 'Minuman cokelat dengan tekstur lembut, rasa manis, dan aroma cokelat yang kaya', 'assets/uploads/menu/menu_non_coffe_u4_20261112_151258_6c531312.jpg'),
(7, 'Matcha Latte', 'non_coffe', 25000, 'Bubuk matcha pilihan yang dipadukan dengan susu segar, menghasilkan rasa creamy dan sedikit earthy', 'assets/uploads/menu/menu_non_coffe_u4_20261112_151523_ad2ee522.jpg'),
(8, 'Strawberry Milk', 'non_coffe', 28000, 'Perpaduan strawberry manis dengan susu creamy dalam lapisan yang cantik', 'assets/uploads/menu/menu_non_coffe_u4_20261112_152648_01e19ce4.jpg'),
(9, 'Taro Milk', 'non_coffe', 23000, 'Minuman berbahan dasar taro dengan susu creamy dan cita rasa manis yang lembut', 'assets/uploads/menu/menu_non_coffe_u4_20261112_152857_d397f150.jpg'),
(10, 'Lemon Tea', 'non_coffe', 17000, 'Teh segar dengan perasan lemon yang memberikan sensasi asam manis yang menyegarkan', 'assets/uploads/menu/menu_non_coffe_u4_20261112_153055_b51d856b.jpg'),
(11, 'Lychee Tea', 'non_coffe', 20000, 'Teh pilihan dengan sirup dan buah leci yang memberikan rasa manis dan aroma fruity', 'assets/uploads/menu/menu_non_coffe_u4_20261112_153937_5940a882.jpg'),
(12, 'Butter Croissant', 'pastry_and_dessert', 18000, 'Pastry berlapis dengan tekstur renyah diluar, lembut didalam, dan aroma mentega yang khas', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261112_154209_173f89f7.jpg'),
(13, 'Choco Croissant', 'pastry_and_dessert', 22000, 'Croissant renyah dengan isian coklat lumer yang manis dan lezat', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261112_154324_3b46e5e2.jpg'),
(14, 'Cinnamon Roll', 'pastry_and_dessert', 20000, 'Roti gulung lembut dengan taburan kayu manis dan lapisan glaze manis', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261112_155149_7d9464e8.jpg'),
(15, 'Brownies', 'pastry_and_dessert', 20000, 'Kue coklat dengan tekstur fudgy, padat, dan kaya rasa coklat', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261112_155309_63d53e57.jpg'),
(16, 'Cromboloni', 'pastry_and_dessert', 25000, 'Pastry berbentuk bulat dengan lapisan renyah dan isian cream yang lembut', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261112_155452_6d60b3d6.jpg'),
(17, 'Chiken Teriyaki', 'rice_bowl', 28000, 'Potongan ayam juicy dengan saus teriyaki manis gurih, disajikan bersama nasi hangat', 'assets/uploads/menu/menu_rice_bowl_u4_20261112_155617_e3dba3f4.jpg'),
(18, 'Chiken Katsu', 'rice_bowl', 30000, 'Ayam fillet berbalut tepung roti yang renyah, disajikan dengan nasi dan saus khas', 'assets/uploads/menu/menu_rice_bowl_u4_20261112_155745_3e1f65f3.jpg'),
(19, 'Spicy Korean Chiken', 'rice_bowl', 30000, 'Potongan ayam crispy dengan balutan saus pedas manis khas korea yang menggugah selera', 'assets/uploads/menu/menu_rice_bowl_u4_20261112_155900_2169ce3f.jpg'),
(20, 'Chiken Sambal Matah', 'rice_bowl', 29000, 'Ayam goreng dengan sambal matah segar bercita rasa pedas, gurih, dan sedikit asam dari perasan jeruk nipis', 'assets/uploads/menu/menu_rice_bowl_u4_20261112_160010_e6fd9f1c.jpg'),
(21, 'Spaghetti Bolognese', 'pasta', 30000, 'Spaghetti dengan saus tomat khas italia, daging cincang, dan bumbu pilihan', 'assets/uploads/menu/menu_pasta_u4_20261112_160118_feb54075.jpg'),
(22, 'Spaghetti Carbonara', 'pasta', 32000, 'Spaghetti dengan saus creamy, keju, susu dan sentuhan lada hitam', 'assets/uploads/menu/menu_pasta_u4_20261112_160216_24dc8596.jpg'),
(23, 'Spaghetti Aglio Olio', 'pasta', 28000, 'Spaghetti dengan tumisan bawang putih, minyak zaitun, cabai, dan taburan parsley', 'assets/uploads/menu/menu_pasta_u4_20261112_160316_7c8cb80d.jpg'),
(24, 'French Fries', 'snack', 18000, 'Kentang goreng keemasan dengan tekstur renyah di luar dan lembut di dalam', 'assets/uploads/menu/menu_snack_u4_20261112_160438_4be950a4.jpg'),
(25, 'Chicken Popcorn', 'snack', 23000, 'Potongan ayam kecil berbalut tepung crispy dengan bumbu gurih yang lezat', 'assets/uploads/menu/menu_snack_u4_20261112_160536_b02d7f43.jpg'),
(26, 'Chicken Wings', 'snack', 28000, 'Sayap ayam berbumbu yang digoreng hingga matang dan disajikan dengan saus pilihan', 'assets/uploads/menu/menu_snack_u4_20261112_160635_eb016feb.jpg'),
(27, 'Mix Platter', 'snack', 35000, 'Kombinasi kentang goreng, chicken popcorn, dan sosis yang cocok dinikmati bersama', 'assets/uploads/menu/menu_snack_u4_20261112_160736_105af9d7.jpg'),
(28, 'Nut Butterscotch', 'coffe', 27000, 'Perpaduan expresso, susu creamy, dan sirup butterscotch dengan sentuhan rasa kacang yang gurih, manis, dan aromatik', 'assets/uploads/menu/menu_coffe_u4_20261112_161346_3de3b7ce.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `metode_pembayaran` enum('Cash','QRIS') NOT NULL,
  `status_pesanan` enum('Pending','Sedang Dibuat','Siap Pick-up','Selesai','Dibatalkan') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipe_order` enum('dine_in','take_away') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id_order`, `id_user`, `total_harga`, `metode_pembayaran`, `status_pesanan`, `created_at`, `tipe_order`) VALUES
(1, 5, 123, 'QRIS', 'Siap Pick-up', '2026-10-28 08:04:24', 'dine_in'),
(2, 5, 50000, 'Cash', 'Selesai', '2026-10-28 08:06:43', 'dine_in'),
(3, 5, 50000, 'Cash', 'Pending', '2026-10-29 08:14:13', 'dine_in'),
(4, 5, 132250, 'QRIS', 'Pending', '2026-10-29 08:28:11', 'dine_in'),
(5, 5, 74750, 'QRIS', 'Pending', '2026-10-29 08:29:06', 'dine_in'),
(6, 5, 74750, 'QRIS', 'Sedang Dibuat', '2026-10-29 08:29:55', 'dine_in'),
(7, 5, 230000, 'QRIS', 'Pending', '2026-10-29 13:15:00', 'dine_in'),
(8, 5, 92000, 'QRIS', 'Pending', '2026-10-29 13:32:59', 'dine_in'),
(9, 5, 172500, 'Cash', 'Pending', '2026-10-29 13:48:51', 'dine_in'),
(10, 5, 17250, 'Cash', 'Pending', '2026-10-29 14:23:45', 'dine_in'),
(11, 5, 60500, 'QRIS', 'Pending', '2026-10-29 15:06:03', 'take_away'),
(12, 5, 60500, 'Cash', 'Pending', '2026-10-29 15:10:00', 'take_away'),
(13, 5, 17250, 'QRIS', 'Pending', '2026-10-29 15:11:07', 'dine_in'),
(14, 5, 57500, 'QRIS', 'Pending', '2026-10-29 15:20:12', 'dine_in'),
(15, 5, 20250, 'QRIS', 'Selesai', '2026-10-29 15:26:20', 'take_away'),
(16, 5, 57500, 'QRIS', 'Selesai', '2026-10-29 15:29:20', 'dine_in'),
(17, 6, 109250, 'QRIS', 'Selesai', '2026-10-29 15:53:29', 'dine_in'),
(18, 6, 60500, 'QRIS', 'Selesai', '2026-10-29 16:08:36', 'take_away'),
(19, 6, 115000, 'QRIS', 'Selesai', '2026-10-29 16:15:50', 'dine_in'),
(20, 6, 1131, 'Cash', 'Selesai', '2026-10-30 08:11:34', 'dine_in'),
(21, 7, 250391, 'QRIS', 'Selesai', '2026-10-30 08:12:36', 'take_away'),
(22, 4, 77750, 'QRIS', 'Selesai', '2026-10-30 08:15:20', 'take_away'),
(23, 6, 17250, 'QRIS', 'Selesai', '2026-10-30 08:20:41', 'dine_in'),
(24, 5, 115000, 'QRIS', 'Selesai', '2026-10-31 06:35:09', 'dine_in'),
(25, 5, 172500, 'Cash', 'Selesai', '2026-10-31 06:38:37', 'dine_in'),
(26, 8, 57641, 'QRIS', 'Selesai', '2026-10-31 08:19:10', 'dine_in'),
(27, 8, 57500, 'QRIS', 'Selesai', '2026-10-31 09:22:59', 'dine_in'),
(28, 6, 92000, 'QRIS', 'Selesai', '2026-10-31 09:59:24', 'dine_in'),
(29, 8, 17250, 'Cash', 'Selesai', '2026-10-31 10:04:36', 'dine_in'),
(30, 5, 34050, 'QRIS', 'Siap Pick-up', '2026-11-02 08:30:21', 'take_away'),
(31, 6, 60500, 'QRIS', 'Selesai', '2026-11-09 14:18:15', 'take_away'),
(32, 6, 17250, 'Cash', 'Selesai', '2026-11-09 14:21:09', 'dine_in'),
(33, 6, 17250, 'QRIS', 'Pending', '2026-11-10 11:24:42', 'dine_in'),
(34, 6, 17250, 'QRIS', 'Dibatalkan', '2026-11-12 09:49:58', 'dine_in'),
(35, 4, 65100, 'QRIS', 'Dibatalkan', '2026-11-12 15:20:03', 'take_away');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id_order_item` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id_order_item`, `id_order`, `id_menu`, `jumlah`, `subtotal`) VALUES
(1, 1, 1, 1, 123),
(2, 2, 2, 1, 50000),
(3, 3, 2, 1, 50000),
(4, 4, 2, 2, 100000),
(5, 4, 3, 1, 15000),
(6, 5, 2, 1, 50000),
(7, 5, 3, 1, 15000),
(8, 6, 2, 1, 50000),
(9, 6, 3, 1, 15000),
(10, 7, 2, 1, 50000),
(11, 7, 3, 10, 150000),
(12, 8, 2, 1, 50000),
(13, 8, 3, 2, 30000),
(14, 9, 2, 3, 150000),
(15, 10, 3, 1, 15000),
(16, 11, 2, 1, 50000),
(17, 12, 2, 1, 50000),
(18, 13, 3, 1, 15000),
(19, 14, 2, 1, 50000),
(20, 15, 3, 1, 15000),
(21, 16, 2, 1, 50000),
(22, 17, 2, 1, 50000),
(23, 17, 3, 3, 45000),
(24, 18, 2, 1, 50000),
(25, 19, 2, 2, 100000),
(26, 20, 1, 8, 984),
(27, 21, 1, 1, 123),
(28, 21, 2, 4, 200000),
(29, 21, 3, 1, 15000),
(30, 22, 2, 1, 50000),
(31, 22, 3, 1, 15000),
(32, 23, 3, 1, 15000),
(33, 24, 2, 2, 100000),
(34, 25, 2, 3, 150000),
(35, 26, 1, 1, 123),
(36, 26, 2, 1, 50000),
(37, 27, 2, 1, 50000),
(38, 28, 2, 1, 50000),
(39, 28, 5, 2, 30000),
(40, 29, 5, 1, 15000),
(41, 30, 3, 1, 15000),
(42, 30, 4, 1, 12000),
(43, 31, 2, 1, 50000),
(44, 32, 5, 1, 15000),
(45, 33, 3, 1, 15000),
(46, 34, 3, 1, 15000),
(47, 35, 6, 1, 22000),
(48, 35, 22, 1, 32000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`) VALUES
(4, 'admin', 'admin', '$2y$10$S4Shb6OXJPD258GCWCBTIeHWOLLAbeAj3sngFm.iJ35wUDlmXC6DW', 'admin'),
(5, 'member', 'member', '$2y$10$fTEtqbI12NF8Q77b2OllZuRRljNao2RVjqN1YwufOPD3sRNvNvyZu', 'customer'),
(6, 'Khelsea', 'khelsea', '$2y$10$EAhL1QL4O9jFAZHd1Xtleu6JKycFZSOFlIv6Rb4kOmT9xQQcLOkcW', 'customer'),
(7, 'p', 'p', '$2y$10$7h14EiuEDJ0UH07A9CUgA.7.OVit99mL5vvHd6co7fIOEyiFU0ee.', 'customer'),
(8, 'Elvina ', 'elvina', '$2y$10$w7ka2Ad4ZmFaOBf0MlECGeZTpeIZxu/eJMFRworRbPoVNdAsvy5oi', 'customer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id_image`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id_order_item`),
  ADD KEY `id_order` (`id_order`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banner`
--
ALTER TABLE `banner`
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id_order_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id_menu`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
