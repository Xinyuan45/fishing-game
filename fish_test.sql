-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 04:18 PM
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
-- Database: `fish_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `baits`
--

CREATE TABLE `baits` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` int(11) NOT NULL,
  `rarity_boost` float DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `baits`
--

INSERT INTO `baits` (`id`, `name`, `price`, `rarity_boost`, `image`, `description`) VALUES
(1, 'Worm', 5, 1, 'worm_1765877374.png', 'Basic bait. Good for small fish.'),
(2, 'Shrimp', 20, 1.3, 'shrimp_1765877383.png', 'Fresh shrimp. Increases rare find chance.'),
(3, 'Squid', 50, 1.6, 'squid_1765877387.png', 'Juicy squid. Great for big catches.'),
(4, 'Golden Lure', 200, 2, 'golden_lure_1765877393.png', 'Shiny lure that attracts legendary fish.');

-- --------------------------------------------------------

--
-- Table structure for table `fish_catches`
--

CREATE TABLE `fish_catches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `weight` float DEFAULT 1,
  `caught_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_catches`
--

INSERT INTO `fish_catches` (`id`, `user_id`, `fish_type_id`, `weight`, `caught_at`) VALUES
(1, 11, 4, 5.2, '2025-12-16 09:37:16'),
(2, 11, 14, 3.58, '2025-12-16 09:37:25'),
(3, 11, 4, 4.8, '2025-12-16 09:37:34'),
(4, 11, 4, 5.25, '2025-12-16 09:37:42'),
(5, 11, 33, 4.41, '2025-12-16 10:26:15'),
(6, 11, 57, 0.95, '2025-12-16 10:27:22'),
(7, 11, 4, 3.96, '2025-12-16 10:27:45'),
(8, 11, 2, 3.6, '2025-12-16 10:27:55'),
(9, 11, 4, 4.59, '2025-12-16 10:28:03'),
(10, 11, 14, 3, '2025-12-16 10:28:12'),
(12, 12, 61, 3.14, '2025-12-17 08:01:05'),
(13, 12, 61, 3.62, '2025-12-17 08:01:26'),
(14, 12, 3, 5.83, '2025-12-17 08:01:46'),
(15, 12, 7, 9.18, '2025-12-17 08:02:06'),
(16, 12, 22, 12.21, '2025-12-17 08:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `fish_types`
--

CREATE TABLE `fish_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rarity` varchar(20) NOT NULL,
  `value` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'default_fish.png',
  `is_custom` tinyint(1) DEFAULT 0,
  `map_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_types`
--

INSERT INTO `fish_types` (`id`, `name`, `rarity`, `value`, `image`, `is_custom`, `map_id`) VALUES
(1, 'Gulper Eel', 'Rare', 50, 'fish_1_1765877331.png', 0, NULL),
(2, 'Fangtooth', 'Rare', 72, 'fish_2_1765877331.png', 0, NULL),
(3, 'Anglerfish', 'Rare', 117, 'fish_3_1765877331.png', 0, NULL),
(4, 'Giant Isopod', 'Rare', 92, 'fish_4_1765877331.png', 0, NULL),
(5, 'Giant Squid', 'Epic', 200, 'fish_5_1765877405.png', 0, NULL),
(6, 'Colossal Squid', 'Epic', 200, 'fish_6_1765877405.png', 0, NULL),
(7, 'Oarfish', 'Epic', 459, 'fish_7_1765877405.png', 0, NULL),
(8, 'Frilled Shark', 'Epic', 200, 'fish_8_1765877405.png', 0, NULL),
(9, 'Kraken', 'Legendary', 1000, 'fish_9_1765877331.png', 0, NULL),
(10, 'Leviathan', 'Legendary', 1000, 'fish_10_1765877331.png', 0, NULL),
(11, 'Abyssal Wyrm', 'Legendary', 1000, 'fish_11_1765877331.png', 0, NULL),
(12, 'Lanternfish', 'Common', 10, 'fish_12_1765877405.png', 0, NULL),
(13, 'Hatchetfish', 'Common', 10, 'fish_13_1765877405.png', 0, NULL),
(14, 'Viperfish', 'Common', 30, 'fish_14_1765877405.png', 0, NULL),
(15, 'Dragonfish', 'Common', 10, 'fish_15_1765877405.png', 0, NULL),
(16, 'Void Stalker', 'Rare', 50, 'fish_16_1765877532.png', 0, NULL),
(17, 'Abyss Crawler', 'Rare', 50, 'fish_17_1765877532.png', 0, NULL),
(18, 'Dark Manta', 'Rare', 50, 'fish_18_1765877532.png', 0, NULL),
(19, 'Spectral Squid', 'Rare', 50, 'fish_19_1765877532.png', 0, NULL),
(20, 'Elder Kraken', 'Epic', 200, 'fish_20_1765877532.png', 0, NULL),
(21, 'Void Leviathan', 'Epic', 200, 'fish_21_1765877532.png', 0, NULL),
(22, 'Nightmare Whale', 'Epic', 611, 'fish_22_1765877532.png', 0, NULL),
(23, 'Abyssal Dragon', 'Epic', 200, 'fish_23_1765877532.png', 0, NULL),
(24, 'Megalodon', 'Legendary', 1000, 'fish_24_1765877532.png', 0, NULL),
(25, 'Cthulhu', 'Legendary', 1000, 'fish_25_1765877532.png', 0, NULL),
(26, 'Void Emperor', 'Legendary', 1000, 'fish_26_1765877532.png', 0, NULL),
(27, 'Ancient One', 'Legendary', 1000, 'fish_27_1765877532.png', 0, NULL),
(28, 'Ghostfish', 'Common', 10, 'fish_28_1765877532.png', 0, NULL),
(29, 'Void Shrimp', 'Common', 10, 'fish_29_1765876320.png', 0, NULL),
(30, 'Shadow Eel', 'Common', 10, 'fish_30_1765877532.png', 0, NULL),
(31, 'Phantom Ray', 'Common', 10, 'fish_31_1765877532.png', 0, NULL),
(32, 'Sea Bass', 'Rare', 50, 'fish_32_1765877624.png', 0, NULL),
(33, 'Red Snapper', 'Rare', 88, 'fish_33_1765877624.png', 0, NULL),
(34, 'Flounder', 'Rare', 50, 'fish_34_1765877624.png', 0, NULL),
(35, 'Mullet', 'Rare', 50, 'fish_35_1765877624.png', 0, NULL),
(36, 'Dolphin', 'Epic', 200, 'fish_36_1765877624.png', 0, NULL),
(37, 'Manta Ray', 'Epic', 200, 'fish_37_1765877624.png', 0, NULL),
(38, 'Sailfish', 'Epic', 200, 'fish_38_1765877624.png', 0, NULL),
(39, 'Golden Marlin', 'Legendary', 1000, 'fish_39_1765877624.png', 0, NULL),
(40, 'Sea Dragon', 'Legendary', 1000, 'fish_40_1765877624.png', 0, NULL),
(41, 'Carp', 'Common', 10, 'fish_41_1765876395.png', 0, NULL),
(42, 'Goldfish', 'Common', 10, 'fish_42_1765876414.png', 0, NULL),
(43, 'Sardine', 'Common', 11, 'fish_43_1765876438.png', 0, NULL),
(44, 'Anchovy', 'Common', 10, 'fish_44_1765877013.png', 0, NULL),
(45, 'Mackerel', 'Common', 10, 'fish_45_1765876426.png', 0, NULL),
(46, 'Lionfish', 'Rare', 50, 'fish_46_1765877736.png', 0, NULL),
(47, 'Moray Eel', 'Rare', 50, 'fish_47_1765877736.png', 0, NULL),
(48, 'Octopus', 'Rare', 50, 'fish_48_1765877736.png', 0, NULL),
(49, 'Pufferfish', 'Rare', 50, 'fish_49_1765877736.png', 0, NULL),
(50, 'Giant Clam', 'Epic', 200, 'fish_50_1765877736.png', 0, NULL),
(51, 'Sea Turtle', 'Epic', 200, 'fish_51_1765877736.png', 0, NULL),
(52, 'Reef Shark', 'Epic', 200, 'fish_52_1765877736.png', 0, NULL),
(53, 'Barracuda', 'Epic', 200, 'fish_53_1765877736.png', 0, NULL),
(54, 'Rainbow Serpent', 'Legendary', 1000, 'fish_54_1765877736.png', 0, NULL),
(55, 'Coral Guardian', 'Legendary', 1000, 'fish_55_1765877736.png', 0, NULL),
(56, 'Clownfish', 'Common', 10, 'fish_56_1765877736.png', 0, NULL),
(57, 'Tang', 'Common', 10, 'fish_57_1765877736.png', 0, NULL),
(58, 'Parrotfish', 'Common', 10, 'fish_58_1765877736.png', 0, NULL),
(59, 'Angelfish', 'Common', 10, 'fish_59_1765877736.png', 0, NULL),
(60, 'Butterflyfish', 'Common', 10, 'fish_60_1765877736.png', 0, NULL),
(61, 'New Fish', 'Common', 36, 'fish_61_1765958315.png', 1, 2),
(62, 'pufferrr', 'Common', 100, 'custom_pufferrr_1765958271.png', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `maps`
--

CREATE TABLE `maps` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `unlock_cost` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT 'default_map.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maps`
--

INSERT INTO `maps` (`id`, `name`, `description`, `unlock_cost`, `image`) VALUES
(1, 'Sunny Coast', 'A peaceful beach perfect for beginners.', 0, 'sunny_coast.jpg'),
(2, 'Coral Reef', 'Vibrant underwater ecosystem with exotic fish.', 500, 'coral_reef.jpg'),
(3, 'Deep Trench', 'Dark waters hiding mysterious creatures.', 2000, 'deep_trench.jpg'),
(4, 'Abyssal Void', 'The darkest depths where legends dwell.', 5000, 'abyssal_void.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rods`
--

CREATE TABLE `rods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` int(11) NOT NULL,
  `luck_multiplier` float NOT NULL,
  `image` varchar(255) DEFAULT 'default_rod.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rods`
--

INSERT INTO `rods` (`id`, `name`, `price`, `luck_multiplier`, `image`) VALUES
(1, 'Bamboo Pole', 0, 1, 'bamboo_pole_1765876839.png'),
(2, 'Fiberglass Rod', 500, 1.2, 'fiberglass_rod_1765877357.png'),
(3, 'Deep Sea Destroyer', 2000, 1.5, 'deep_sea_destroyer_1765877362.png'),
(4, 'Poseidon\'s Trident', 10000, 2, 'poseidon_s_trident_1765877368.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `coins` int(11) DEFAULT 100,
  `level` int(11) DEFAULT 1,
  `xp` int(11) DEFAULT 0,
  `is_admin` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'user',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `coins`, `level`, `xp`, `is_admin`, `role`, `is_verified`, `verification_token`, `created_at`) VALUES
(1, 'XYSUPER', 'thamxinyuan@gmail.com', '$2y$10$X1dfk6y4.o8bbedkhuvT.OyRxKcmUCh4pziaBROBcbY7ff.OSIyfm', 200, 1, 0, 1, 'super_admin', 1, NULL, '2025-12-16 09:09:37'),
(6, 'YUAN', 'xinyuan4545@gmail.com', '$2y$10$jbviQSivWhO/Ky5ATTR0IuLXYDI18OuhzKCH6Rg8fh6qnw7JSXjEu', 200, 1, 0, 0, 'user', 1, NULL, '2025-12-16 09:19:17'),
(11, 'qqyy', 'chong.qinyuan@ypccollege.edu.my', '$2y$10$VZubtSh3O9eiXeL1ZZim/OQHZU.t94GUYeDit27qeDVLgR/eZYZ12', 10200, 20, 105, 1, 'admin', 1, NULL, '2025-12-16 09:24:57'),
(12, 'Tester', 'test@gmail.com', '$2y$10$hsetQyDpjZuGel6qjJeMrujnBGpiBbnLcDPbh4pSS.iPpMEysu2Nm', 68750, 11, 205, 1, 'admin', 1, NULL, '2025-12-17 07:50:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_baits`
--

CREATE TABLE `user_baits` (
  `user_id` int(11) NOT NULL,
  `bait_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_baits`
--

INSERT INTO `user_baits` (`user_id`, `bait_id`, `quantity`) VALUES
(1, 1, 10),
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(6, 1, 10),
(6, 2, 0),
(6, 3, 0),
(6, 4, 0),
(11, 1, 8),
(11, 2, 0),
(11, 3, 0),
(11, 4, 89),
(12, 1, 59),
(12, 2, 50),
(12, 3, 39),
(12, 4, 50);

-- --------------------------------------------------------

--
-- Table structure for table `user_discoveries`
--

CREATE TABLE `user_discoveries` (
  `user_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `discovered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_discoveries`
--

INSERT INTO `user_discoveries` (`user_id`, `fish_type_id`, `discovered_at`) VALUES
(11, 2, '2025-12-16 10:27:55'),
(11, 4, '2025-12-16 09:37:16'),
(11, 14, '2025-12-16 09:37:25'),
(11, 33, '2025-12-16 10:26:15'),
(11, 57, '2025-12-16 10:27:22'),
(12, 3, '2025-12-17 08:01:46'),
(12, 7, '2025-12-17 08:02:06'),
(12, 22, '2025-12-17 08:03:13'),
(12, 43, '2025-12-17 07:52:17'),
(12, 61, '2025-12-17 08:01:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_maps`
--

CREATE TABLE `user_maps` (
  `user_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_maps`
--

INSERT INTO `user_maps` (`user_id`, `map_id`, `unlocked_at`) VALUES
(1, 1, '2025-12-16 09:09:37'),
(6, 1, '2025-12-16 09:19:17'),
(11, 1, '2025-12-16 09:24:57'),
(11, 2, '2025-12-16 09:26:50'),
(11, 3, '2025-12-16 09:26:49'),
(11, 4, '2025-12-16 09:26:51'),
(12, 1, '2025-12-17 07:50:39'),
(12, 2, '2025-12-17 08:00:11'),
(12, 3, '2025-12-17 08:00:13'),
(12, 4, '2025-12-17 08:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `user_rods`
--

CREATE TABLE `user_rods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rod_id` int(11) NOT NULL,
  `is_equipped` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_rods`
--

INSERT INTO `user_rods` (`id`, `user_id`, `rod_id`, `is_equipped`) VALUES
(1, 1, 1, 1),
(4, 6, 1, 1),
(6, 11, 1, 1),
(7, 11, 3, 0),
(8, 11, 4, 0),
(9, 11, 2, 0),
(10, 12, 1, 0),
(11, 12, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_catches` int(11) DEFAULT 0,
  `total_value_earned` int(11) DEFAULT 0,
  `rare_catches` int(11) DEFAULT 0,
  `epic_catches` int(11) DEFAULT 0,
  `legendary_catches` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_stats`
--

INSERT INTO `user_stats` (`id`, `user_id`, `total_catches`, `total_value_earned`, `rare_catches`, `epic_catches`, `legendary_catches`) VALUES
(1, 1, 0, 0, 0, 0, 0),
(4, 6, 0, 0, 0, 0, 0),
(6, 11, 10, 0, 7, 0, 0),
(17, 12, 6, 0, 1, 2, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `baits`
--
ALTER TABLE `baits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fish_catches`
--
ALTER TABLE `fish_catches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `fish_types`
--
ALTER TABLE `fish_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `map_id` (`map_id`);

--
-- Indexes for table `maps`
--
ALTER TABLE `maps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rods`
--
ALTER TABLE `rods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_baits`
--
ALTER TABLE `user_baits`
  ADD PRIMARY KEY (`user_id`,`bait_id`),
  ADD KEY `bait_id` (`bait_id`);

--
-- Indexes for table `user_discoveries`
--
ALTER TABLE `user_discoveries`
  ADD PRIMARY KEY (`user_id`,`fish_type_id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `user_maps`
--
ALTER TABLE `user_maps`
  ADD PRIMARY KEY (`user_id`,`map_id`),
  ADD KEY `map_id` (`map_id`);

--
-- Indexes for table `user_rods`
--
ALTER TABLE `user_rods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rod_id` (`rod_id`);

--
-- Indexes for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `baits`
--
ALTER TABLE `baits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fish_catches`
--
ALTER TABLE `fish_catches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `fish_types`
--
ALTER TABLE `fish_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `maps`
--
ALTER TABLE `maps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rods`
--
ALTER TABLE `rods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_rods`
--
ALTER TABLE `user_rods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fish_catches`
--
ALTER TABLE `fish_catches`
  ADD CONSTRAINT `fish_catches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fish_catches_ibfk_2` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fish_types`
--
ALTER TABLE `fish_types`
  ADD CONSTRAINT `fish_types_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_baits`
--
ALTER TABLE `user_baits`
  ADD CONSTRAINT `user_baits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_baits_ibfk_2` FOREIGN KEY (`bait_id`) REFERENCES `baits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_discoveries`
--
ALTER TABLE `user_discoveries`
  ADD CONSTRAINT `user_discoveries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_discoveries_ibfk_2` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_maps`
--
ALTER TABLE `user_maps`
  ADD CONSTRAINT `user_maps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_maps_ibfk_2` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_rods`
--
ALTER TABLE `user_rods`
  ADD CONSTRAINT `user_rods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_rods_ibfk_2` FOREIGN KEY (`rod_id`) REFERENCES `rods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD CONSTRAINT `user_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
