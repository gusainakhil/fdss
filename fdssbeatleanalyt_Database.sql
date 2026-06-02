-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 02, 2026 at 02:01 PM
-- Server version: 10.11.18-MariaDB
-- PHP Version: 8.4.21

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
-- Table structure for table `fdds_coach_inspection`
--

CREATE TABLE `fdds_coach_inspection` (
  `inspection_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `train_info_id` bigint(20) DEFAULT NULL,
  `coach_id` int(11) NOT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `tool_name` varchar(255) NOT NULL,
  `Serial_No` varchar(11) NOT NULL,
  `Conditions` varchar(255) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `inventory_parameter_id` int(11) DEFAULT NULL,
  `status` enum('Pending','In_Progress','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdds_coach_inspection`
--

INSERT INTO `fdds_coach_inspection` (`inspection_id`, `schedule_id`, `train_info_id`, `coach_id`, `auditor_id`, `user_id`, `inventory_id`, `tool_name`, `Serial_No`, `Conditions`, `unit_id`, `inventory_parameter_id`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(27, 18, NULL, 79, 6, 2, 17, 'Working Condition of Flasher light', 'S11111S', 'yes', 52, 48, 'Completed', NULL, '2026-06-01 13:47:30', '2026-06-01 13:47:30'),
(28, 18, NULL, 79, 6, 2, 14, 'Working Condition of Hooter', 'S11111S', 'no', 49, 48, 'Completed', NULL, '2026-06-01 13:47:42', '2026-06-01 13:47:42'),
(29, 18, NULL, 79, 6, 2, 15, 'Working Condition of Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'S11111S', 'no', 50, 48, 'Completed', NULL, '2026-06-01 13:47:53', '2026-06-01 13:47:53'),
(30, 18, NULL, 79, 6, 2, 16, 'Working Condition of Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'S11111S', 'yes', 51, 48, 'Completed', NULL, '2026-06-01 13:47:57', '2026-06-01 13:47:57');

-- --------------------------------------------------------

--
-- Table structure for table `fdds_inventory_unit`
--

CREATE TABLE `fdds_inventory_unit` (
  `unit_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `inventory_parameter_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `model_number` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `Warranty_expire` datetime DEFAULT NULL,
  `manufacturer_id` int(11) DEFAULT NULL,
  `use_status` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fdds_inventory_unit`
--

INSERT INTO `fdds_inventory_unit` (`unit_id`, `inventory_id`, `inventory_parameter_id`, `user_id`, `serial_number`, `model_number`, `purchase_date`, `Warranty_expire`, `manufacturer_id`, `use_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 7, NULL, 2, '12344', '1234', '2026-05-12', '2026-05-21 00:00:00', 5, 0, 'na', '2026-05-15 16:12:59', '2026-05-25 08:19:57'),
(4, 6, NULL, 2, '123456', '3463', '2026-05-12', NULL, 3, 0, 'na', '2026-05-15 16:21:08', '2026-05-25 08:19:59'),
(5, 6, NULL, 2, '12424', '2444', '2026-05-13', NULL, 3, 1, 'na', '2026-05-15 16:21:08', '2026-05-25 08:20:03'),
(6, 7, NULL, 2, '1234', '232', '2026-05-20', NULL, 5, 0, 'f', '2026-05-15 16:40:54', '2026-05-25 08:20:01'),
(7, 7, NULL, 2, '5432', '4543', '2026-05-12', NULL, 5, 0, 'f', '2026-05-15 16:40:54', '2026-05-25 08:20:07'),
(8, 7, NULL, 2, 'Y67JKGH87', '13321', '2026-05-06', '2026-05-07 00:00:00', 4, 0, 'g', '2026-05-15 16:41:16', '2026-05-25 08:21:12'),
(9, 7, NULL, 2, '22', '1234', '2026-05-14', '2026-05-27 00:00:00', 1, 0, 'g', '2026-05-15 16:41:16', '2026-05-25 08:20:05'),
(10, 7, NULL, 2, 'qwef', 'ert', '2026-04-29', '2026-05-22 00:00:00', 5, 0, 'f', '2026-05-15 16:41:55', '2026-05-25 08:21:15'),
(11, 7, NULL, 2, 'we', 'etewrt', '2026-05-06', NULL, 5, 0, 'f', '2026-05-15 16:41:55', '2026-05-25 08:21:17'),
(12, 7, NULL, 2, 'wewe', 'erter', '2026-05-27', NULL, 5, 0, 'f', '2026-05-15 16:41:55', '2026-05-25 08:21:22'),
(13, 7, NULL, 2, '124132', '223443', '2026-05-14', NULL, 5, 0, 'ff', '2026-05-15 16:44:13', '2026-05-25 08:21:25'),
(14, 7, NULL, 2, '24', '3435435', '2026-06-02', NULL, 5, 0, 'gg', '2026-05-15 16:44:13', '2026-05-25 08:21:19'),
(15, 7, NULL, 2, '12345', '123', '2026-05-18', '2026-05-19 00:00:00', 1, 0, 'wefewf', '2026-05-18 09:50:49', '2026-05-25 08:21:24'),
(16, 7, NULL, 2, '432443', '3456546546', '2026-05-04', '2026-05-19 00:00:00', 2, 0, 'ff', '2026-05-18 11:02:11', '2026-05-25 08:21:27'),
(17, 7, NULL, 2, 'gg55', '234', '2026-05-18', '2026-06-06 00:00:00', 5, 0, 'bb', '2026-05-18 11:05:18', '2026-05-25 08:21:30'),
(18, 9, NULL, 2, '23456', '98765', '2026-04-06', '2026-08-07 00:00:00', 5, 0, 'nc', '2026-05-22 17:38:45', '2026-05-25 08:21:29'),
(19, 10, NULL, 2, 'ABC123', 'XXCXER', '2026-05-01', '2026-10-31 00:00:00', 2, 0, 'na', '2026-05-23 06:10:30', '2026-05-25 08:21:34'),
(20, 10, NULL, 2, 'ABG567H', 'ADH7658', '2026-05-22', '2026-05-30 00:00:00', 5, 1, '', '2026-05-23 07:32:42', '2026-05-25 08:21:32'),
(21, 10, NULL, 2, 'ADBHTY', 'ADBHYT', '2026-06-06', '2026-08-28 00:00:00', 3, 1, '', '2026-05-23 07:32:43', '2026-05-25 08:21:36'),
(22, 10, NULL, 2, 'KHJFT6435', 'VFD6578H', '2026-05-23', '2026-05-30 00:00:00', 1, 0, '', '2026-05-23 07:32:43', '2026-05-25 08:21:39'),
(23, 10, NULL, 2, 'POHJFT6435', 'YUH5678', '2026-05-30', '2026-10-09 00:00:00', 3, 1, '', '2026-05-23 07:44:45', '2026-05-25 08:21:38'),
(24, 9, NULL, 2, '12345', '223344', '2026-05-25', '2026-07-25 00:00:00', 2, 0, '', '2026-05-25 08:41:53', '2026-06-01 14:37:36'),
(25, 9, NULL, 2, 'kkkkkk', '11kkh', '2026-05-25', '2026-05-29 00:00:00', 2, 0, '', '2026-05-25 08:50:14', '2026-05-25 08:50:14'),
(26, 9, NULL, 2, 'er', '23', '2026-05-25', '2026-05-30 00:00:00', 2, 0, '', '2026-05-25 09:00:08', '2026-06-01 14:37:34'),
(27, 9, NULL, 2, '112233', '445566', '2026-06-01', '2026-05-25 00:00:00', 2, 1, '', '2026-05-25 09:20:28', '2026-05-25 09:20:28'),
(28, 9, NULL, 2, '112233', '445566', '2026-06-01', '2026-05-25 00:00:00', 2, 1, '', '2026-05-25 09:20:28', '2026-05-25 09:30:23'),
(29, 9, NULL, 2, '112233', '445566', '2026-06-01', '2026-05-25 00:00:00', 2, 1, '', '2026-05-25 09:20:28', '2026-05-25 09:30:21'),
(30, 9, NULL, 2, '112233', '445566', '2026-06-01', '2026-05-25 00:00:00', 2, 1, '', '2026-05-25 09:20:29', '2026-05-25 09:30:19'),
(31, 9, NULL, 2, '112233', '445566', '2026-06-01', '2026-05-25 00:00:00', 2, 1, '', '2026-05-25 09:20:29', '2026-05-25 09:30:15'),
(32, 9, NULL, 2, 'Akhil', 'inventory_id', '2026-05-25', '2026-07-31 00:00:00', 3, 1, '', '2026-05-25 09:29:07', '2026-05-25 09:31:16'),
(33, 14, 32, 2, 'Akhil', 'inventory_id', '2026-05-25', '2026-07-31 00:00:00', 3, 1, '', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(34, 15, 32, 2, 'Akhil', 'inventory_id', '2026-05-25', '2026-07-31 00:00:00', 3, 1, '', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(35, 16, 32, 2, 'Akhil', 'inventory_id', '2026-05-25', '2026-07-31 00:00:00', 3, 1, '', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(36, 17, 32, 2, 'Akhil', 'inventory_id', '2026-05-25', '2026-07-31 00:00:00', 3, 1, '', '2026-05-25 09:29:09', '2026-05-25 09:29:09'),
(37, 10, NULL, 2, '113366', '778899', '2026-05-25', '2026-08-20 00:00:00', 2, 1, '', '2026-05-25 09:37:42', '2026-05-25 10:00:10'),
(38, 12, 37, 2, '113366', '778899', '2026-05-25', '2026-08-20 00:00:00', 2, 1, '', '2026-05-25 09:37:42', '2026-05-25 10:00:06'),
(39, 13, 37, 2, '113366', '778899', '2026-05-25', '2026-08-20 00:00:00', 2, 1, '', '2026-05-25 09:37:42', '2026-05-25 10:00:03'),
(40, 34, NULL, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(41, 23, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(42, 24, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(43, 25, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(44, 26, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(45, 27, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(46, 28, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(47, 29, 40, 1, 'ABC123', 'XXCXER', '2026-05-01', '2027-03-01 00:00:00', 7, 1, '', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(48, 9, NULL, 2, 'S11111S', 'M11111M', '2026-05-27', '2026-06-26 00:00:00', 2, 1, '', '2026-05-27 07:26:32', '2026-06-01 14:38:01'),
(49, 14, 48, 2, 'S11111S', 'M11111M', '2026-05-27', '2026-06-26 00:00:00', 2, 1, '', '2026-05-27 07:26:32', '2026-06-01 14:38:06'),
(50, 15, 48, 2, 'S11111S', 'M11111M', '2026-05-27', '2026-06-26 00:00:00', 2, 1, '', '2026-05-27 07:26:32', '2026-06-01 14:38:10'),
(51, 16, 48, 2, 'S11111S', 'M11111M', '2026-05-27', '2026-06-26 00:00:00', 2, 1, '', '2026-05-27 07:26:32', '2026-06-01 14:38:13'),
(52, 17, 48, 2, 'S11111S', 'M11111M', '2026-05-27', '2026-06-26 00:00:00', 1, 1, '', '2026-05-27 07:26:32', '2026-06-01 14:37:09');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_coach_inventory`
--

CREATE TABLE `fdss_coach_inventory` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `inventory_unit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Active','Expired') DEFAULT 'Active',
  `warranty_replace_status` enum('warranty','replace','normal') DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_coach_inventory`
--

INSERT INTO `fdss_coach_inventory` (`id`, `coach_id`, `inventory_unit_id`, `user_id`, `status`, `warranty_replace_status`, `created_at`, `updated_at`) VALUES
(11, 5, 6, 2, 'Active', 'warranty', '2026-05-18 12:03:41', '2026-05-18 12:03:41'),
(12, 5, 4, 2, 'Active', 'warranty', '2026-05-18 12:04:23', '2026-05-18 12:04:23'),
(13, 3, 16, 2, 'Active', 'warranty', '2026-05-19 12:33:04', '2026-05-19 12:33:04'),
(14, 77, 18, 2, 'Active', 'warranty', '2026-05-22 17:40:51', '2026-05-22 17:40:51'),
(15, 76, 19, 2, 'Active', 'warranty', '2026-05-23 06:14:21', '2026-05-23 06:14:21'),
(16, 44, 20, 2, 'Active', 'warranty', '2026-05-23 07:32:42', '2026-05-23 07:32:42'),
(17, 69, 21, 2, 'Active', 'warranty', '2026-05-23 07:32:43', '2026-05-23 07:32:43'),
(18, 75, 22, 2, 'Active', 'warranty', '2026-05-23 07:44:01', '2026-05-23 07:44:01'),
(19, 77, 5, 2, 'Active', 'warranty', '2026-05-25 06:33:04', '2026-05-25 06:33:04'),
(20, 77, 23, 2, 'Active', 'warranty', '2026-05-25 06:35:00', '2026-05-25 06:35:00'),
(21, 74, 27, 2, 'Active', 'warranty', '2026-05-25 09:20:28', '2026-05-25 09:20:28'),
(22, 74, 28, 2, 'Active', 'warranty', '2026-05-25 09:20:28', '2026-05-25 09:20:28'),
(23, 74, 29, 2, 'Active', 'warranty', '2026-05-25 09:20:28', '2026-05-25 09:20:28'),
(24, 74, 30, 2, 'Active', 'warranty', '2026-05-25 09:20:29', '2026-05-25 09:20:29'),
(25, 74, 31, 2, 'Active', 'warranty', '2026-05-25 09:20:29', '2026-05-25 09:20:29'),
(26, 69, 32, 2, 'Active', 'warranty', '2026-05-25 09:29:07', '2026-05-25 09:29:07'),
(27, 69, 33, 2, 'Active', 'warranty', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(28, 69, 34, 2, 'Active', 'warranty', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(29, 69, 35, 2, 'Active', 'warranty', '2026-05-25 09:29:08', '2026-05-25 09:29:08'),
(30, 69, 36, 2, 'Active', 'warranty', '2026-05-25 09:29:09', '2026-05-25 09:29:09'),
(32, 73, 37, 2, 'Active', 'warranty', '2026-05-25 09:57:58', '2026-05-25 09:57:58'),
(33, 73, 38, 2, 'Active', 'warranty', '2026-05-25 09:57:59', '2026-05-25 09:57:59'),
(34, 73, 39, 2, 'Active', 'warranty', '2026-05-25 09:57:59', '2026-05-25 09:57:59'),
(35, 78, 40, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(36, 78, 41, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(37, 78, 42, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(38, 78, 43, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(39, 78, 44, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(40, 78, 45, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(41, 78, 46, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(42, 78, 47, 1, 'Active', 'warranty', '2026-05-26 12:21:30', '2026-05-26 12:21:30'),
(43, 79, 48, 2, 'Active', 'normal', '2026-05-27 07:26:32', '2026-05-30 10:36:21'),
(44, 79, 49, 2, 'Active', 'warranty', '2026-05-27 07:26:32', '2026-06-01 13:47:42'),
(45, 79, 50, 2, 'Expired', 'replace', '2026-05-27 07:26:32', '2026-06-01 13:47:53'),
(46, 79, 51, 2, 'Expired', 'normal', '2026-05-27 07:26:32', '2026-06-01 13:47:15'),
(47, 79, 52, 2, 'Expired', 'normal', '2026-05-27 07:26:32', '2026-06-01 13:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_coach_schedule`
--

CREATE TABLE `fdss_coach_schedule` (
  `schedule_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `train_info_id` bigint(20) DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `status` enum('Pending','Assigned','Completed','Warranty-claim/replacement') DEFAULT 'Pending',
  `auditor_id` int(11) DEFAULT NULL,
  `assignment_date_time` datetime DEFAULT NULL,
  `Inspection_Type` varchar(255) DEFAULT NULL,
  `priority` enum('Normal','High') DEFAULT 'Normal',
  `special_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL,
  `schedule_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_coach_schedule`
--

INSERT INTO `fdss_coach_schedule` (`schedule_id`, `coach_id`, `train_info_id`, `last_inspection_date`, `next_due_date`, `status`, `auditor_id`, `assignment_date_time`, `Inspection_Type`, `priority`, `special_remarks`, `created_at`, `updated_at`, `user_id`, `schedule_status`) VALUES
(17, 12, NULL, NULL, '2026-07-01', 'Assigned', 6, '2026-06-01 10:00:00', '1_month', 'Normal', '', '2026-06-01 13:42:45', '2026-06-01 13:42:45', 2, 0),
(18, 79, NULL, NULL, '2026-07-01', 'Warranty-claim/replacement', 6, '2026-06-01 10:00:00', '1_month', 'Normal', 'Done', '2026-06-01 13:45:43', '2026-06-01 13:51:29', 2, 0);

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
-- Table structure for table `fdss_Inventory_Management`
--

CREATE TABLE `fdss_Inventory_Management` (
  `inventory_id` int(11) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `category` enum('FDSS','FSDS','Primary','Secondary','FDSSPARA','FSDSPARA') NOT NULL,
  `status` enum('Working','Needs Check','Not Working') DEFAULT 'Working',
  `user_id` int(11) NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_Inventory_Management`
--

INSERT INTO `fdss_Inventory_Management` (`inventory_id`, `item_code`, `item_name`, `quantity`, `category`, `status`, `user_id`, `last_updated`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'INV-WC-001', 'Hooter', 32, 'Secondary', 'Working', 2, '2026-05-25 06:38:01', 'best produt', '2026-05-12 08:33:09', '2026-05-25 06:38:01'),
(2, 'INV-WC-002', 'Flasher light', 380, 'Primary', 'Working', 2, '2026-05-13 16:29:23', 'good', '2026-05-12 08:33:31', '2026-05-13 16:29:23'),
(3, 'INV-WC-003', 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 45, 'Primary', 'Needs Check', 2, '2026-05-13 16:29:15', 'best', '2026-05-12 08:33:52', '2026-05-13 16:29:15'),
(4, 'INV-WC-004', 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 22, 'Primary', 'Working', 2, '2026-05-13 16:29:07', 'good', '2026-05-12 08:34:12', '2026-05-13 16:29:07'),
(5, 'INV-WC-005', 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 90, 'Primary', 'Working', 2, '2026-05-13 16:29:02', 'good', '2026-05-12 08:34:28', '2026-05-13 16:29:02'),
(6, 'INV-WC-006', 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 2, 'Primary', 'Working', 2, '2026-05-14 09:47:42', 'good', '2026-05-12 08:34:45', '2026-05-15 16:15:07'),
(7, 'INV-WC-007', 'Heat detection test for LWLRRM (Engine shutdown when temp raise)', 13, 'Primary', 'Not Working', 2, '2026-05-13 16:31:00', 'good', '2026-05-12 08:35:06', '2026-05-18 11:05:19'),
(9, 'INV-WC-008', 'FDSS', 11, 'FDSS', 'Working', 2, '2026-05-23 07:06:07', 'na', '2026-05-22 17:38:16', '2026-05-27 07:26:32'),
(10, 'INV-WC-009', 'FSDS', 6, 'FSDS', 'Working', 2, '2026-05-23 07:06:12', 'NA', '2026-05-23 05:46:25', '2026-05-25 09:37:42'),
(12, 'INV-WC-011', 'Working Condition of Hooter', NULL, 'FSDSPARA', 'Working', 2, '2026-05-25 07:52:18', '', '2026-05-25 07:52:18', '2026-06-01 10:34:38'),
(13, 'INV-WC-012', 'Working Condition of Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', NULL, 'FSDSPARA', 'Working', 2, '2026-05-25 07:52:18', '', '2026-05-25 07:52:18', '2026-06-01 10:39:28'),
(14, 'INV-WC-013', 'Working Condition of Hooter', NULL, 'FDSSPARA', 'Working', 2, '2026-05-25 08:30:44', '', '2026-05-25 08:30:44', '2026-06-01 10:34:43'),
(15, 'INV-WC-014', 'Working Condition of Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', NULL, 'FDSSPARA', 'Working', 2, '2026-05-25 08:30:44', '', '2026-05-25 08:30:44', '2026-06-01 10:38:12'),
(16, 'INV-WC-015', 'Working Condition of Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', NULL, 'FDSSPARA', 'Working', 2, '2026-05-25 08:30:44', '', '2026-05-25 08:30:44', '2026-06-01 10:38:54'),
(17, 'INV-WC-016', 'Working Condition of Flasher light', NULL, 'FDSSPARA', 'Working', 2, '2026-05-25 08:30:45', '', '2026-05-25 08:30:45', '2026-06-01 10:38:06'),
(23, 'INV-WC-017', 'Working Condition of Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-06-01 10:38:31'),
(24, 'INV-WC-018', 'Flasher Light', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(25, 'INV-WC-019', 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(26, 'INV-WC-020', 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(27, 'INV-WC-021', 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(28, 'INV-WC-022', 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(29, 'INV-WC-023', 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', NULL, 'FDSSPARA', 'Working', 1, '2026-05-26 10:40:25', '', '2026-05-26 10:40:25', '2026-05-26 10:40:25'),
(30, 'INV-WC-024', 'Hooter', NULL, 'FSDSPARA', 'Working', 1, '2026-05-26 10:41:15', '', '2026-05-26 10:41:15', '2026-05-26 10:41:15'),
(31, 'INV-WC-025', 'Flasher light', NULL, 'FSDSPARA', 'Working', 1, '2026-05-26 10:41:15', '', '2026-05-26 10:41:15', '2026-05-26 10:41:15'),
(32, 'INV-WC-026', 'Smoke Sensor in Compartment', NULL, 'FSDSPARA', 'Working', 1, '2026-05-26 10:41:15', '', '2026-05-26 10:41:15', '2026-05-26 10:41:15'),
(33, 'INV-WC-027', 'Smoke Sensor in Lavatory', NULL, 'FSDSPARA', 'Working', 1, '2026-05-26 10:41:15', '', '2026-05-26 10:41:15', '2026-05-26 10:41:15'),
(34, '1A', 'FDSS', 1, 'FSDSPARA', 'Working', 1, '2026-05-26 10:41:57', '', '2026-05-26 10:41:57', '2026-06-01 09:51:18'),
(35, '1B', 'FSDS', NULL, 'FSDSPARA', 'Working', 1, '2026-05-26 10:42:48', '', '2026-05-26 10:42:48', '2026-06-01 09:51:25'),
(36, 'INV-WC-028', 'Working Condition of Flasher light', NULL, 'FSDSPARA', 'Working', 2, '2026-06-01 14:34:29', '', '2026-06-01 14:34:29', '2026-06-01 14:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_manufacturers`
--

CREATE TABLE `fdss_manufacturers` (
  `manufacturer_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email_id` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_manufacturers`
--

INSERT INTO `fdss_manufacturers` (`manufacturer_id`, `company_name`, `name`, `mobile_number`, `email_id`, `address`, `status`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Kings', '', '7830773698', 'akhil2@gmail.com', 'jkwefnew', 'Active', 2, '2026-05-14 05:49:48', '2026-05-14 05:49:48'),
(2, 'beatle team', '', NULL, NULL, NULL, 'Active', 2, '2026-05-14 10:45:22', '2026-05-14 10:48:31'),
(3, 'king', '', NULL, NULL, NULL, 'Active', 2, '2026-05-14 10:45:22', '2026-05-14 10:48:32'),
(4, 'Kings', '', NULL, NULL, NULL, 'Active', 2, '2026-05-14 10:45:22', '2026-05-14 10:48:34'),
(5, 'beatle test', '', NULL, NULL, NULL, 'Active', 2, '2026-05-14 10:45:23', '2026-05-14 10:48:37'),
(7, 'Sanork', 'Sidharth Jugran', '8000221818', 'sidharth.jugran2@gmail.com', '147, New KISHAN NAGAR EXTENSION\r\nSTREET - 2 LANE - 8, KISHAN NAGAR', 'Active', 1, '2026-05-26 09:55:17', '2026-05-26 09:55:17'),
(8, 'JK Exim', 'Anuj Sharma', '7567706549', 'anuj@beatleanalytics.com', 'Adhoiwala, Dehradun', 'Active', 1, '2026-05-26 09:56:07', '2026-05-26 09:56:07'),
(9, 'MS Advance', 'Akhil Gusain', '9854785489', 'akhil@beatleanlaytics.com', 'Dehradun', 'Active', 1, '2026-05-26 09:57:36', '2026-05-26 09:57:36'),
(10, 'Tayal Co.', 'Sarthak Bhatt', '7854785489', 'sarthak@beatleanalytics.com', 'Dehradun', 'Active', 1, '2026-05-26 09:58:25', '2026-05-26 09:58:25');

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
(1, 'DDN', 93, 'Active', '2026-04-06 08:20:26', '2026-05-19 10:46:22'),
(2, 'Haridwar', 38, 'Active', '2026-04-17 00:05:14', '2026-04-17 00:05:14');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_train_coach`
--

CREATE TABLE `fdss_train_coach` (
  `coach_id` int(11) NOT NULL,
  `train_info_id` varchar(10) DEFAULT NULL,
  `coach_no` varchar(50) NOT NULL,
  `Type` varchar(20) DEFAULT NULL,
  `coach_type` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `coach_status` enum('Detached','Intact') NOT NULL DEFAULT 'Intact',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `next_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `schedule_status` int(11) NOT NULL DEFAULT 0 COMMENT 'O means coach is free 1 means cach work is progress'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_train_coach`
--

INSERT INTO `fdss_train_coach` (`coach_id`, `train_info_id`, `coach_no`, `Type`, `coach_type`, `user_id`, `coach_status`, `status`, `next_inspection_date`, `created_at`, `updated_at`, `schedule_status`) VALUES
(1, '6', '22334', '', 'FSDS', 2, 'Intact', 'Active', '2026-06-25', '2026-05-12 08:41:48', '2026-05-19 10:28:41', 0),
(2, '6', '44556', '', 'FDSS', 2, 'Intact', 'Active', '2026-08-16', '2026-05-13 06:48:51', '2026-05-23 09:10:12', 1),
(3, '2', '77889', '', 'FDSS', 2, 'Intact', 'Active', '2026-06-21', '2026-05-13 08:42:22', '2026-05-19 10:59:17', 1),
(4, '6', 'SWLWACCN121655', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-14 07:52:02', '2026-05-18 13:00:52', 0),
(5, NULL, 'SELC55443', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(6, NULL, 'SELC55448', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(7, NULL, 'SELC55449', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(8, NULL, 'SELC55450', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(9, NULL, 'SELC55451', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(10, NULL, 'SELC55452', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(11, NULL, 'SELC55453', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(12, NULL, 'SELC55454', '', 'FDSS', 2, 'Intact', 'Active', '2026-07-01', '2026-05-18 10:16:35', '2026-06-01 13:42:45', 1),
(13, NULL, 'SELC55455', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(14, NULL, 'SELC55456', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(15, NULL, 'SELC55457', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(16, NULL, 'SELC55458', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(17, NULL, 'SELC55459', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(18, NULL, 'SELC55460', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(19, NULL, 'SELC55461', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(20, '6', 'SELC55462', '', 'FDSS', 2, 'Intact', 'Active', '2026-06-29', '2026-05-18 10:16:35', '2026-05-26 20:39:26', 1),
(21, '6', 'SELC55463', '', 'FSDS', 2, 'Intact', 'Active', '2026-06-15', '2026-05-18 10:16:35', '2026-05-23 10:19:45', 1),
(22, '2', 'SELC55464', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(23, '6', 'SELC55465', '', 'FDSS', 2, 'Intact', 'Active', '2026-06-15', '2026-05-18 10:16:35', '2026-05-26 20:37:23', 1),
(24, NULL, 'SELC55466', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(25, NULL, 'SELC55467', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(26, '6', 'SELC55468', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(27, '2', 'SELC55469', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(28, '6', 'SELC55470', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-23', '2026-05-19 10:16:35', '2026-05-22 06:22:01', 0),
(29, NULL, 'SELC55471', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-16', '2026-05-18 10:16:35', '2026-05-21 07:40:18', 0),
(30, NULL, 'LCWC55448', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(31, NULL, 'LCWC55449', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(32, NULL, 'LCWC55450', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(33, NULL, 'LCWC55451', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(34, NULL, 'LCWC55452', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(35, NULL, 'LCWC55453', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(36, NULL, 'LCWC55454', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(37, NULL, 'LCWC55455', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(38, NULL, 'LCWC55456', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(39, NULL, 'LCWC55457', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(40, NULL, 'LCWC55458', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(41, NULL, 'LCWC55459', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(42, NULL, 'LCWC55460', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(43, NULL, 'LCWC55461', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(44, '6', 'LCWC55462', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(45, '6', 'LCWC55463', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(46, '2', 'LCWC55464', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(47, '6', 'LCWC55465', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(48, NULL, 'LCWC55466', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(49, NULL, 'LCWC55467', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(50, '6', 'LCWC55468', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(51, '2', 'LCWC55469', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(52, '6', 'LCWC55470', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(53, NULL, 'LCWC55471', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(54, NULL, 'LCWC55472', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-29', '2026-05-17 18:30:00', '2026-05-23 06:23:21', 1),
(55, NULL, 'LCWC55473', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(56, NULL, 'LCWC55474', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(57, NULL, 'LCWC55475', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(58, NULL, 'LCWC55476', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(59, NULL, 'LCWC55477', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(60, NULL, 'LCWC55478', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(61, NULL, 'LCWC55479', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(62, NULL, 'LCWC55480', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(63, NULL, 'LCWC55481', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(64, NULL, 'LCWC55482', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(65, NULL, 'LCWC55483', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(66, NULL, 'LCWC55484', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(67, NULL, 'LCWC55485', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(68, '6', 'LCWC55486', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(69, '6', 'LCWC55487', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(70, '2', 'LCWC55488', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(71, '6', 'LCWC55489', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(72, NULL, 'LCWC55490', '', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(73, NULL, 'LCWC55491', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-18 18:30:00', 0),
(74, '7', 'LCWC55492', '', 'FSDS', 2, 'Intact', 'Active', '2026-06-19', '2026-05-17 18:30:00', '2026-05-27 07:11:33', 1),
(75, '2', 'LCWC55493', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-17 18:30:00', 0),
(76, NULL, 'LCWC55494', '', 'FDSS', 2, 'Intact', 'Active', '2026-05-21', '2026-05-18 18:30:00', '2026-05-23 07:41:36', 0),
(77, NULL, 'LCWC55495', 'SLERTY', 'FSDS', 2, 'Intact', 'Active', '2026-05-20', '2026-05-17 18:30:00', '2026-05-26 11:13:17', 0),
(78, '8', '242053', 'SW LWLRRM', 'FDSS', 1, 'Intact', 'Active', '2026-06-28', '2026-05-26 12:12:19', '2026-06-01 13:45:33', 0),
(79, NULL, '111111', 'SW LWLRRM', 'FDSS', 2, 'Intact', 'Active', '2026-07-01', '2026-05-27 07:25:50', '2026-06-01 13:45:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fdss_train_information`
--

CREATE TABLE `fdss_train_information` (
  `train_info_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `train_no` varchar(50) NOT NULL,
  `train_name` varchar(150) NOT NULL,
  `No_of_Coach` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_train_information`
--

INSERT INTO `fdss_train_information` (`train_info_id`, `user_id`, `train_no`, `train_name`, `No_of_Coach`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, '22895', 'rajdhani expree', 22, 'Active', '2026-05-12 08:23:28', '2026-05-12 08:25:27'),
(6, 2, '12345', 'express', 3, 'Active', '2026-05-12 08:28:53', '2026-05-12 08:28:53'),
(7, 2, '11223', 'DDN express', 0, 'Active', '2026-05-18 12:13:05', '2026-05-19 10:54:31'),
(8, 1, '12275', 'Demo Train 1', 0, 'Active', '2026-05-26 09:58:54', '2026-05-26 09:58:54'),
(9, 1, '12587', 'Demo Train 2', 0, 'Active', '2026-05-26 09:59:11', '2026-05-26 09:59:11'),
(10, 1, '22584', 'Demo Train 3', 0, 'Active', '2026-05-26 09:59:22', '2026-05-26 09:59:22'),
(11, 1, '96589', 'Demo Train 4', 0, 'Active', '2026-05-26 09:59:36', '2026-05-26 09:59:36'),
(12, 1, '88754', 'Demo Train 5', 0, 'Active', '2026-05-26 09:59:46', '2026-05-26 09:59:46');

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
  `station_id` int(11) DEFAULT NULL,
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
(2, 'kings', NULL, 'kings', 'kings@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, '', 'NA', 'dehradun', 'ORG_ADMIN', 1, '2026-04-10', '2026-08-12', 'Active', NULL, '2026-04-10 08:45:10', '2026-05-19 10:18:05'),
(4, 'admin', NULL, 'admin', 're@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ADMIN', 1, '2026-04-06', '2026-07-30', 'Active', NULL, '2026-04-06 08:32:59', '2026-05-06 11:42:08'),
(5, 'Golu', NULL, 'aaa', 'aakh@gmail.com', '$2y$10$7on.bGREoRIspZUXEmKDIuLimMMn9caghLWlzhC5e.LXEAh04iL5q', NULL, 'N/A', 'N/A', NULL, 'AUDITOR', 1, '2026-05-30', '2026-05-30', 'Inactive', 2, '2026-05-06 08:08:19', '2026-05-18 12:27:18'),
(6, 'akhil gusain', NULL, 'akhil', 'akhilgusain2@mail.com', '$2y$10$wZNR/Ffnno54swOy.G6NC.rbUgxdLf264IFsrk5MPqdSBTR6nT1ZG', NULL, '7830773698', 'Hr', NULL, 'AUDITOR', NULL, NULL, NULL, 'Active', 2, '2026-05-12 08:17:58', '2026-06-01 13:41:40'),
(8, 'AG', NULL, 'ag__80', 'ag@gmail.com', '$2y$10$r.1vL/kbHXPHPVEzddXAsefU599ubpBFp2ynWyeqLGC19g4xrr2EW', NULL, '9854587456', 'SE', NULL, 'AUDITOR', NULL, NULL, NULL, 'Active', 1, '2026-05-26 12:38:39', '2026-05-26 12:38:39');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_warranty_claim`
--

CREATE TABLE `fdss_warranty_claim` (
  `warranty_claim_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `defectiveCause` varchar(255) DEFAULT NULL,
  `otherObservation` varchar(255) DEFAULT NULL,
  `referenceNo` varchar(255) DEFAULT NULL,
  `suggestion` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_warranty_claim`
--

INSERT INTO `fdss_warranty_claim` (`warranty_claim_id`, `schedule_id`, `unit_id`, `defectiveCause`, `otherObservation`, `referenceNo`, `suggestion`, `status`, `created_at`) VALUES
(16, 18, 49, 'Hotter ', 'There are some minor bugs on this tool', 'REF:45678', 'Please replace this Toll', 'claim process', '2026-06-01 19:17:42');

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
-- Indexes for table `fdds_coach_inspection`
--
ALTER TABLE `fdds_coach_inspection`
  ADD PRIMARY KEY (`inspection_id`),
  ADD KEY `idx_schedule` (`schedule_id`),
  ADD KEY `idx_train_info` (`train_info_id`),
  ADD KEY `idx_auditor` (`auditor_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `fdds_inventory_unit`
--
ALTER TABLE `fdds_inventory_unit`
  ADD PRIMARY KEY (`unit_id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `manufacturer_id` (`manufacturer_id`);

--
-- Indexes for table `fdss_coach_inventory`
--
ALTER TABLE `fdss_coach_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coach_no` (`coach_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `inventory_unit_id` (`inventory_unit_id`);

--
-- Indexes for table `fdss_coach_schedule`
--
ALTER TABLE `fdss_coach_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_coach_no` (`coach_id`),
  ADD KEY `idx_auditor` (`auditor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_train_info` (`train_info_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  ADD PRIMARY KEY (`division_id`),
  ADD UNIQUE KEY `uk_division_zone` (`division_name`,`zone_id`),
  ADD KEY `idx_division_name` (`division_name`),
  ADD KEY `idx_division_zone` (`zone_id`);

--
-- Indexes for table `fdss_Inventory_Management`
--
ALTER TABLE `fdss_Inventory_Management`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `uk_item_code` (`item_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `fdss_manufacturers`
--
ALTER TABLE `fdss_manufacturers`
  ADD PRIMARY KEY (`manufacturer_id`);

--
-- Indexes for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  ADD PRIMARY KEY (`station_id`),
  ADD UNIQUE KEY `uk_station_division` (`station_name`,`division_id`),
  ADD KEY `idx_station_name` (`station_name`),
  ADD KEY `idx_station_division` (`division_id`);

--
-- Indexes for table `fdss_train_coach`
--
ALTER TABLE `fdss_train_coach`
  ADD PRIMARY KEY (`coach_id`),
  ADD UNIQUE KEY `uk_train_coach` (`train_info_id`,`coach_no`),
  ADD KEY `idx_train_info` (`train_info_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `fdss_warranty_claim`
--
ALTER TABLE `fdss_warranty_claim`
  ADD PRIMARY KEY (`warranty_claim_id`);

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
-- AUTO_INCREMENT for table `fdds_coach_inspection`
--
ALTER TABLE `fdds_coach_inspection`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `fdds_inventory_unit`
--
ALTER TABLE `fdds_inventory_unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `fdss_coach_inventory`
--
ALTER TABLE `fdss_coach_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `fdss_coach_schedule`
--
ALTER TABLE `fdss_coach_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `fdss_Inventory_Management`
--
ALTER TABLE `fdss_Inventory_Management`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `fdss_manufacturers`
--
ALTER TABLE `fdss_manufacturers`
  MODIFY `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fdss_train_coach`
--
ALTER TABLE `fdss_train_coach`
  MODIFY `coach_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  MODIFY `train_info_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `fdss_users`
--
ALTER TABLE `fdss_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fdss_warranty_claim`
--
ALTER TABLE `fdss_warranty_claim`
  MODIFY `warranty_claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `fdss_zones`
--
ALTER TABLE `fdss_zones`
  MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fdds_coach_inspection`
--
ALTER TABLE `fdds_coach_inspection`
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `fdss_coach_schedule` (`schedule_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_2` FOREIGN KEY (`auditor_id`) REFERENCES `fdss_users` (`user_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_3` FOREIGN KEY (`train_info_id`) REFERENCES `fdss_train_information` (`train_info_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_5` FOREIGN KEY (`coach_id`) REFERENCES `fdss_train_coach` (`coach_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_6` FOREIGN KEY (`unit_id`) REFERENCES `fdds_inventory_unit` (`unit_id`),
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_7` FOREIGN KEY (`inventory_id`) REFERENCES `fdss_Inventory_Management` (`inventory_id`);

--
-- Constraints for table `fdds_inventory_unit`
--
ALTER TABLE `fdds_inventory_unit`
  ADD CONSTRAINT `fdds_inventory_unit_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `fdss_Inventory_Management` (`inventory_id`),
  ADD CONSTRAINT `fdds_inventory_unit_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`),
  ADD CONSTRAINT `fdds_inventory_unit_ibfk_3` FOREIGN KEY (`manufacturer_id`) REFERENCES `fdss_manufacturers` (`manufacturer_id`);

--
-- Constraints for table `fdss_coach_inventory`
--
ALTER TABLE `fdss_coach_inventory`
  ADD CONSTRAINT `fdss_coach_inventory_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`),
  ADD CONSTRAINT `fdss_coach_inventory_ibfk_3` FOREIGN KEY (`inventory_unit_id`) REFERENCES `fdds_inventory_unit` (`unit_id`);

--
-- Constraints for table `fdss_coach_schedule`
--
ALTER TABLE `fdss_coach_schedule`
  ADD CONSTRAINT `fdss_coach_schedule_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `fdss_users` (`user_id`),
  ADD CONSTRAINT `fdss_coach_schedule_ibfk_2` FOREIGN KEY (`train_info_id`) REFERENCES `fdss_train_information` (`train_info_id`),
  ADD CONSTRAINT `fdss_coach_schedule_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`);

--
-- Constraints for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  ADD CONSTRAINT `fdss_divisions_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `fdss_zones` (`zone_id`);

--
-- Constraints for table `fdss_Inventory_Management`
--
ALTER TABLE `fdss_Inventory_Management`
  ADD CONSTRAINT `fdss_Inventory_Management_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`);

--
-- Constraints for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  ADD CONSTRAINT `fdss_stations_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `fdss_divisions` (`division_id`);

--
-- Constraints for table `fdss_train_coach`
--
ALTER TABLE `fdss_train_coach`
  ADD CONSTRAINT `fdss_train_coach_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`);

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
