-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2025 at 10:29 AM
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
-- Database: `fishing_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'เบ็ดตกปลา'),
(2, 'เหยื่อปลอม'),
(3, 'รอกตกปลา'),
(4, 'สายเอ็น'),
(5, 'กล่องใส่อุปกรณ์');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `created_at`) VALUES
(1, 1, 1800.00, 'pending', '2025-07-08 14:49:45'),
(2, 3, 1000.00, 'paid', '2025-07-08 14:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 1500.00),
(2, 1, 2, 1, 300.00),
(3, 2, 5, 2, 450.00),
(4, 2, 4, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `image`, `created_at`) VALUES
(1, 'เบ็ดตกปลา Shimano', 'เบ็ดตกปลาคุณภาพสูง เหมาะสำหรับมือใหม่และมือโปร', 1500.00, 10, 'rod1.jpg', '2025-07-08 14:46:06'),
(2, 'เหยื่อปลอม Rapala', 'เหยื่อปลอมรุ่นฮิต สีสันสดใส ดึงดูดปลาได้ดี', 300.00, 25, 'lure1.jpg', '2025-07-08 14:46:06'),
(3, 'รอกตกปลา Daiwa', 'รอกตกปลาทนทาน ระบบลากดีเยี่ยม', 2800.00, 5, 'reel1.jpg', '2025-07-08 14:46:06'),
(4, 'สายเอ็น Monofilament', 'สายเอ็นคุณภาพดี เหนียวทนทาน', 200.00, 50, 'line1.jpg', '2025-07-08 14:46:06'),
(5, 'กล่องใส่อุปกรณ์ตกปลา', 'กล่องเก็บอุปกรณ์ขนาดพกพา', 450.00, 15, 'box1.jpg', '2025-07-08 14:46:06'),
(6, 'เบ็ดตกปลา Abu Garcia', 'เบ็ดตกปลารุ่นคลาสสิค แข็งแรงทนทาน', 1800.00, 8, 'rod2.jpg', '2025-07-08 14:46:06'),
(7, 'เหยื่อปลอม Lucky Craft', 'เหยื่อปลอมแบบลอยน้ำ สีสันสดใส', 320.00, 20, 'lure2.jpg', '2025-07-08 14:46:06'),
(8, 'รอกตกปลา Penn', 'รอกตกปลาคุณภาพสูง เหมาะสำหรับตกน้ำลึก', 3500.00, 3, 'reel2.jpg', '2025-07-08 14:46:06'),
(9, 'สายเอ็น Fluorocarbon', 'สายเอ็นใส ทนทาน ใช้ตกปลาใหญ่', 250.00, 40, 'line2.jpg', '2025-07-08 14:46:06'),
(10, 'กล่องเก็บอุปกรณ์ Plano', 'กล่องอุปกรณ์ขนาดใหญ่พร้อมช่องเก็บหลากหลาย', 700.00, 10, 'box2.jpg', '2025-07-08 14:46:06'),
(11, 'เบ็ดตกปลา Okuma', 'เบ็ดตกปลาน้ำหนักเบา เหมาะสำหรับตกปลาเล็ก', 1300.00, 12, 'rod3.jpg', '2025-07-08 14:46:06'),
(12, 'เหยื่อปลอม Megabass', 'เหยื่อปลอมคุณภาพสูง สีสันจัดจ้าน', 400.00, 15, 'lure3.jpg', '2025-07-08 14:46:06'),
(13, 'รอกตกปลา Shimano Stella', 'รอกตกปลาระดับพรีเมี่ยม ระบบลากสมูธ', 8000.00, 2, 'reel3.jpg', '2025-07-08 14:46:06'),
(14, 'สายเอ็น Braided', 'สายเอ็นถักแข็งแรง เหมาะสำหรับตกน้ำเค็ม', 500.00, 30, 'line3.jpg', '2025-07-08 14:46:06'),
(15, 'กล่องเก็บเหยื่อ Rapala', 'กล่องใส่เหยื่อพร้อมช่องเก็บแบบพกพา', 550.00, 18, 'box3.jpg', '2025-07-08 14:46:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_category`
--

INSERT INTO `product_category` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 1),
(7, 2),
(8, 3),
(9, 4),
(10, 5),
(11, 1),
(12, 2),
(13, 3),
(14, 4),
(15, 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'user1', '$2y$10$abcdefghijklmnopqrstuv', 'user1@example.com', '2025-07-08 14:49:19'),
(2, 'user2', '$2y$10$mnopqrstuvabcdefghijk', 'user2@example.com', '2025-07-08 14:49:19'),
(3, 'fisherman', '$2y$10$somehashedpasswordhere123456', 'fishlover@example.com', '2025-07-08 14:49:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_category`
--
ALTER TABLE `product_category`
  ADD CONSTRAINT `product_category_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
