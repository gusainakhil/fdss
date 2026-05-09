-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 09, 2026 at 05:58 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fdssbeatleanalyt_Database`
--

-- --------------------------------------------------------

--
-- Table structure for table `fdss_divisions`
--

CREATE TABLE `fdss_divisions` (
  `division_id` int(11) NOT NULL,
  `division_name` varchar(120) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_divisions`
--

INSERT INTO `fdss_divisions` (`division_id`, `division_name`, `zone_id`, `status`, `created_at`, `updated_at`) VALUES
(10, 'Mumbai', 1, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(11, 'Bhusawal', 1, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(12, 'Pune', 1, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(13, 'Nagpur', 1, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(14, 'Solapur', 1, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(17, 'Howrah', 2, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(18, 'Sealdah', 2, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(19, 'Asansol', 2, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(20, 'Malda', 2, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(24, 'Danapur', 3, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(25, 'Dhanbad', 3, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(26, 'Pandit Deen Dayal Upadhyaya', 3, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(27, 'Samastipur', 3, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(28, 'Sonpur', 3, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(31, 'Khurda Road', 4, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(32, 'Sambalpur', 4, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(33, 'Waltair', 4, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(34, 'Delhi', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(35, 'Ambala', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(36, 'Firozpur', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(37, 'Lucknow NR', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(38, 'Moradabad', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(41, 'Prayagraj', 6, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(42, 'Agra', 6, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(43, 'Jhansi', 6, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(44, 'Izzatnagar', 7, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(45, 'Lucknow NER', 7, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(46, 'Varanasi', 7, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(47, 'Alipurduar', 8, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(48, 'Katihar', 8, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(49, 'Lumding', 8, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(50, 'Rangiya', 8, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(51, 'Tinsukia', 8, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(54, 'Jaipur', 9, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(55, 'Ajmer', 9, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(56, 'Bikaner', 9, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(57, 'Jodhpur', 9, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(61, 'Secunderabad', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(62, 'Hyderabad', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(63, 'Vijayawada', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(64, 'Guntur', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(65, 'Guntakal', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(66, 'Nanded', 10, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(68, 'Visakhapatnam', 11, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(69, 'Bilaspur', 12, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(70, 'Raipur', 12, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(71, 'Nagpur SECR', 12, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(72, 'Adra', 13, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(73, 'Chakradharpur', 13, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(74, 'Kharagpur', 13, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(75, 'Ranchi', 13, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(79, 'Bengaluru', 14, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(80, 'Hubballi', 14, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(81, 'Mysuru', 14, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(82, 'Chennai', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(83, 'Salem', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(84, 'Tiruchirappalli', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(85, 'Madurai', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(86, 'Palakkad', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(87, 'Thiruvananthapuram', 15, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(89, 'Bhopal', 16, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(90, 'Jabalpur', 16, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(91, 'Kota', 16, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(92, 'Mumbai WR', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(93, 'Ahmedabad', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(94, 'Vadodara', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(95, 'Ratlam', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(96, 'Rajkot', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(97, 'Bhavnagar', 17, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(99, 'Kolkata Metro', 18, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47'),
(100, 'Jammu Railway', 5, '', '2025-07-25 13:09:47', '2025-07-25 13:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_stations`
--

CREATE TABLE `fdss_stations` (
  `station_id` int(11) NOT NULL,
  `station_name` varchar(120) NOT NULL,
  `division_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_stations`
--

INSERT INTO `fdss_stations` (`station_id`, `station_name`, `division_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'dehrdaun', 93, 'Active', '2026-04-06 08:20:26', '2026-04-06 08:20:26'),
(2, 'Haridwar', 38, 'Active', '2026-04-17 00:05:14', '2026-04-17 00:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_train_information`
--

CREATE TABLE `fdss_train_information` (
  `train_info_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `train_no` varchar(50) NOT NULL,
  `train_name` varchar(150) NOT NULL,
  `No_of_Coach` int(11) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fdss_users`
--

CREATE TABLE `fdss_users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(150) NOT NULL,
  `user_code` varchar(50) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('SUPER_ADMIN','ADMIN','ORG_ADMIN','ORG_USER','AUDITOR') NOT NULL DEFAULT 'ORG_ADMIN',
  `station_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_users`
--

INSERT INTO `fdss_users` (`user_id`, `user_name`, `user_code`, `username`, `email`, `password_hash`, `full_name`, `phone`, `designation`, `address`, `role`, `station_id`, `start_date`, `end_date`, `status`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 'beatle', NULL, 'beatle', 'beatle@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ORG_ADMIN', 1, '2026-04-06', '2026-07-30', 'Active', NULL, '2026-04-06 08:32:59', '2026-04-14 01:49:42'),
(2, 'kings', NULL, 'kings', 'kings@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ORG_ADMIN', 1, '2026-04-10', '2026-08-12', 'Active', NULL, '2026-04-10 08:45:10', '2026-04-14 08:20:17'),
(4, 'admin', NULL, 'admin', 're@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ADMIN', 1, '2026-04-06', '2026-07-30', 'Active', NULL, '2026-04-06 08:32:59', '2026-05-06 11:42:08'),
(5, 'aaa', NULL, 'aaa', 'aakh@gmail.com', '$2y$10$7on.bGREoRIspZUXEmKDIuLimMMn9caghLWlzhC5e.LXEAh04iL5q', NULL, NULL, NULL, NULL, 'ORG_ADMIN', 1, '2026-05-30', '2026-05-30', 'Active', NULL, '2026-05-06 08:08:19', '2026-05-06 11:41:46');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_zones`
--

CREATE TABLE `fdss_zones` (
  `zone_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_zones`
--

INSERT INTO `fdss_zones` (`zone_id`, `zone_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(2, 'Eastern Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(3, 'East Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(4, 'East Coast Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(5, 'Northern Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(6, 'North Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(7, 'North Eastern Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(8, 'Northeast Frontier Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(9, 'North Western Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(10, 'South Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(11, 'South Coast Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(12, 'South East Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(13, 'South Eastern Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(14, 'South Western Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(15, 'Southern Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(16, 'West Central Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(17, 'Western Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(18, 'Metro Railway, Kolkata', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03'),
(19, 'Konkan Railway', '', '2025-07-25 13:06:03', '2025-07-25 13:06:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  ADD PRIMARY KEY (`division_id`),
  ADD UNIQUE KEY `uk_division_zone` (`division_name`,`zone_id`),
  ADD KEY `idx_division_name` (`division_name`),
  ADD KEY `idx_division_zone` (`zone_id`);

--
-- Indexes for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  ADD PRIMARY KEY (`station_id`),
  ADD UNIQUE KEY `uk_station_division` (`station_name`,`division_id`),
  ADD KEY `idx_station_name` (`station_name`),
  ADD KEY `idx_station_division` (`division_id`);

--
-- Indexes for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  ADD PRIMARY KEY (`train_info_id`),
  ADD UNIQUE KEY `uk_user_train_no` (`user_id`,`train_no`),
  ADD KEY `idx_train_user` (`user_id`),
  ADD KEY `idx_train_no` (`train_no`);

--
-- Indexes for table `fdss_users`
--
ALTER TABLE `fdss_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_code` (`user_code`),
  ADD KEY `created_by_user_id` (`created_by_user_id`),
  ADD KEY `idx_user_name` (`user_name`),
  ADD KEY `idx_user_station` (`station_id`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `idx_user_email` (`email`);

--
-- Indexes for table `fdss_zones`
--
ALTER TABLE `fdss_zones`
  ADD PRIMARY KEY (`zone_id`),
  ADD UNIQUE KEY `zone_name` (`zone_name`),
  ADD KEY `idx_zone_name` (`zone_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  MODIFY `train_info_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fdss_users`
--
ALTER TABLE `fdss_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fdss_zones`
--
ALTER TABLE `fdss_zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  ADD CONSTRAINT `fdss_divisions_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `fdss_zones` (`zone_id`);

--
-- Constraints for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  ADD CONSTRAINT `fdss_stations_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `fdss_divisions` (`division_id`);

--
-- Constraints for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  ADD CONSTRAINT `fdss_train_information_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`);

--
-- Constraints for table `fdss_users`
--
ALTER TABLE `fdss_users`
  ADD CONSTRAINT `fdss_users_ibfk_1` FOREIGN KEY (`station_id`) REFERENCES `fdss_stations` (`station_id`),
  ADD CONSTRAINT `fdss_users_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `fdss_users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
