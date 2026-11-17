-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2026 at 03:27 AM
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
(9, 'assets/uploads/banner/carousel/banner_carousel_u4_20261115_151359_a83a1da6.jpg', 'carousel_banner', 1, 1, '2026-11-15 14:13:59'),
(10, 'assets/uploads/banner/carousel/banner_carousel_u4_20261115_151444_1d0f8dbc.jpg', 'carousel_banner', 2, 1, '2026-11-15 14:14:44'),
(11, 'assets/uploads/banner/carousel/banner_carousel_u4_20261115_152118_f3ce98f1.jpg', 'carousel_banner', 0, 1, '2026-11-15 14:21:18'),
(12, 'assets/uploads/banner/carousel/banner_carousel_u4_20261115_152224_78c7186b.jpg', 'carousel_banner', 3, 1, '2026-11-15 14:22:24'),
(13, 'assets/uploads/banner/carousel/banner_carousel_u4_20261115_152305_b07f9971.png', 'carousel_banner', 4, 1, '2026-11-15 14:23:05'),
(14, 'assets/uploads/banner/popup/banner_popup_u4_20261115_154002_e5a4d297.png', 'popup_banner', 0, 1, '2026-11-15 14:40:02');

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
(28, 'Nut Butterscotch', 'coffe', 27000, 'Perpaduan expresso, susu creamy, dan sirup butterscotch dengan sentuhan rasa kacang yang gurih, manis, dan aromatik', 'assets/uploads/menu/menu_coffe_u4_20261112_161346_3de3b7ce.jpg'),
(29, '[Anomali⚠] Cromboloni Brownies x Anomali Bundle', 'pastry_and_dessert', 40000, 'Cromboloni + Brownies spesial kolaborasi dengan anomali Tralalelo Tralala', 'assets/uploads/menu/menu_pastry_and_dessert_u4_20261115_152618_1ced00b0.jpg'),
(30, '[Anomali⚠] Duo Spaghetti x Anomali Bundle ', 'pasta', 57000, 'Carbonara Spaghetti  + Bolognese Spaghetti spesial kolaborasi dengan Anomali Tung Tung Sahur', 'assets/uploads/menu/menu_pasta_u4_20261115_153306_12131912.png'),
(31, '[Anomali⚠] Coffe Bundling x Anomali Bundle', 'coffe', 35000, 'Americno + Cappucino spesial kolaborasi dengan anomali Ballerina Cappuccina ', 'assets/uploads/menu/menu_coffe_u4_20261115_153624_a47d30e9.png'),
(32, '[Anomali⚠] Milkshake Series x Anomali Bundle', 'non_coffe', 45000, 'Stawberry Milk + Chocolate Milkshake spesial kolaborasi dengan Anomali Tung Tung Sahur dan Ballerina Cappuccina ', 'assets/uploads/menu/menu_non_coffe_u4_20261115_153936_2f35c603.png');

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
(45, 9, 70180, 'Cash', 'Selesai', '2026-11-16 15:22:21', 'dine_in'),
(46, 9, 114950, 'QRIS', 'Selesai', '2026-11-16 15:23:15', 'dine_in'),
(47, 9, 27830, 'Cash', 'Selesai', '2026-11-16 15:23:43', 'dine_in'),
(48, 10, 89700, 'QRIS', 'Selesai', '2026-11-16 16:14:53', 'take_away'),
(49, 10, 45980, 'Cash', 'Selesai', '2026-11-16 16:19:45', 'dine_in'),
(50, 10, 26780, 'QRIS', 'Dibatalkan', '2026-11-16 16:22:15', 'take_away');

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
(64, 45, 2, 1, 18000),
(65, 45, 29, 1, 40000),
(66, 46, 3, 1, 23000),
(67, 46, 13, 2, 44000),
(68, 46, 17, 1, 28000),
(69, 47, 4, 1, 23000),
(70, 48, 27, 1, 35000),
(71, 48, 31, 1, 35000),
(72, 49, 2, 1, 18000),
(73, 49, 15, 1, 20000),
(74, 50, 12, 1, 18000);

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
(9, 'Budi Santoso', 'budi', '$2y$10$9wQEZEZdLXz8L1kM49/9JO91vwviJGGt5ygm6SaKvm.AsRTC7K82G', 'customer'),
(10, 'Citra Apriani', 'citra', '$2y$10$dSTdFzMDbDUQ0Q91QidvL.bt77EUnmghgisiNinyWEq57d0PJqYVK', 'customer');

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
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id_order_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
