-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 18, 2026 at 05:22 PM
-- Server version: 10.11.17-MariaDB
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
  `coach_no` varchar(50) NOT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `inspection_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `zone_name` varchar(120) DEFAULT NULL,
  `station_name` varchar(120) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `tool_report` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fdds_inventory_unit`
--

CREATE TABLE `fdds_inventory_unit` (
  `unit_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `model_number` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `Warranty_expire` datetime DEFAULT NULL,
  `manufacturer_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fdds_inventory_unit`
--

INSERT INTO `fdds_inventory_unit` (`unit_id`, `inventory_id`, `user_id`, `serial_number`, `model_number`, `purchase_date`, `Warranty_expire`, `manufacturer_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 7, 2, '12344', '1234', '2026-05-12', '2026-05-21 00:00:00', 5, 'na', '2026-05-15 16:12:59', '2026-05-18 11:00:09'),
(4, 6, 2, '123456', '3463', '2026-05-12', NULL, 3, 'na', '2026-05-15 16:21:08', '2026-05-15 16:21:08'),
(5, 6, 2, '12424', '2444', '2026-05-13', NULL, 3, 'na', '2026-05-15 16:21:08', '2026-05-15 16:21:08'),
(6, 7, 2, '1234', '232', '2026-05-20', NULL, 5, 'f', '2026-05-15 16:40:54', '2026-05-15 16:40:54'),
(7, 7, 2, '5432', '4543', '2026-05-12', NULL, 5, 'f', '2026-05-15 16:40:54', '2026-05-15 16:40:54'),
(8, 7, 2, '12', '13321', '2026-05-06', '2026-05-07 00:00:00', 4, 'g', '2026-05-15 16:41:16', '2026-05-18 10:59:25'),
(9, 7, 2, '22', '1234', '2026-05-14', '2026-05-27 00:00:00', 1, 'g', '2026-05-15 16:41:16', '2026-05-18 11:05:29'),
(10, 7, 2, 'qwef', 'ert', '2026-04-29', '2026-05-22 00:00:00', 5, 'f', '2026-05-15 16:41:55', '2026-05-18 11:05:41'),
(11, 7, 2, 'we', 'etewrt', '2026-05-06', NULL, 5, 'f', '2026-05-15 16:41:55', '2026-05-15 16:41:55'),
(12, 7, 2, 'wewe', 'erter', '2026-05-27', NULL, 5, 'f', '2026-05-15 16:41:55', '2026-05-15 16:41:55'),
(13, 7, 2, '124132', '223443', '2026-05-14', NULL, 5, 'ff', '2026-05-15 16:44:13', '2026-05-15 16:44:13'),
(14, 7, 2, '24', '3435435', '2026-06-02', NULL, 5, 'gg', '2026-05-15 16:44:13', '2026-05-15 16:44:13'),
(15, 7, 2, '12345', '123', '2026-05-18', '2026-05-19 00:00:00', 1, 'wefewf', '2026-05-18 09:50:49', '2026-05-18 09:50:49'),
(16, 7, 2, '432443', '3456546546', '2026-05-04', '2026-05-19 00:00:00', 2, 'ff', '2026-05-18 11:02:11', '2026-05-18 11:02:11'),
(17, 7, 2, 'gg55', '234', '2026-05-18', '2026-06-06 00:00:00', 5, 'bb', '2026-05-18 11:05:18', '2026-05-18 11:05:18');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fdss_coach_schedule`
--

CREATE TABLE `fdss_coach_schedule` (
  `schedule_id` int(11) NOT NULL,
  `coach_no` varchar(50) NOT NULL,
  `train_info_id` bigint(20) DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `status` enum('Pending','Assigned','Completed') DEFAULT 'Pending',
  `auditor_id` int(11) DEFAULT NULL,
  `assignment_date_time` datetime DEFAULT NULL,
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

INSERT INTO `fdss_coach_schedule` (`schedule_id`, `coach_no`, `train_info_id`, `last_inspection_date`, `next_due_date`, `status`, `auditor_id`, `assignment_date_time`, `priority`, `special_remarks`, `created_at`, `updated_at`, `user_id`, `schedule_status`) VALUES
(1, '22334', 6, '2026-05-12', '2026-05-12', 'Assigned', 6, '2026-05-12 18:52:00', 'High', 'this ie best', '2026-05-12 11:23:02', '2026-05-12 11:23:02', 2, 0),
(2, '22334', 6, '2026-05-12', '2026-05-12', 'Assigned', 5, '2026-05-16 16:53:00', 'Normal', 'rfr', '2026-05-12 11:23:46', '2026-05-12 11:23:46', 2, 0),
(3, '22334', 6, '2026-05-13', '2026-05-14', 'Assigned', 6, '2026-05-13 20:44:00', 'High', '', '2026-05-12 12:14:47', '2026-05-12 12:19:53', 2, 0);

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
  `category` enum('Primary','Secondary') NOT NULL,
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
(1, 'INV-WC-001', 'Hooter', 32, 'Primary', 'Working', 2, '2026-05-13 16:29:28', 'best produt', '2026-05-12 08:33:09', '2026-05-13 16:29:28'),
(2, 'INV-WC-002', 'Flasher light', 380, 'Primary', 'Working', 2, '2026-05-13 16:29:23', 'good', '2026-05-12 08:33:31', '2026-05-13 16:29:23'),
(3, 'INV-WC-003', 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 45, 'Primary', 'Needs Check', 2, '2026-05-13 16:29:15', 'best', '2026-05-12 08:33:52', '2026-05-13 16:29:15'),
(4, 'INV-WC-004', 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 22, 'Primary', 'Working', 2, '2026-05-13 16:29:07', 'good', '2026-05-12 08:34:12', '2026-05-13 16:29:07'),
(5, 'INV-WC-005', 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 90, 'Primary', 'Working', 2, '2026-05-13 16:29:02', 'good', '2026-05-12 08:34:28', '2026-05-13 16:29:02'),
(6, 'INV-WC-006', 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 2, 'Primary', 'Working', 2, '2026-05-14 09:47:42', 'good', '2026-05-12 08:34:45', '2026-05-15 16:15:07'),
(7, 'INV-WC-007', 'Heat detection test for LWLRRM (Engine shutdown when temp raise)', 13, 'Primary', 'Not Working', 2, '2026-05-13 16:31:00', 'good', '2026-05-12 08:35:06', '2026-05-18 11:05:19');

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
(5, 'beatle test', '', NULL, NULL, NULL, 'Active', 2, '2026-05-14 10:45:23', '2026-05-14 10:48:37');

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
-- Table structure for table `fdss_train_coach`
--

CREATE TABLE `fdss_train_coach` (
  `coach_id` int(11) NOT NULL,
  `train_info_id` varchar(10) DEFAULT NULL,
  `coach_no` varchar(50) NOT NULL,
  `coach_type` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `coach_status` enum('Detached','Intact') NOT NULL DEFAULT 'Intact',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `next_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `schedule_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_train_coach`
--

INSERT INTO `fdss_train_coach` (`coach_id`, `train_info_id`, `coach_no`, `coach_type`, `user_id`, `coach_status`, `status`, `next_inspection_date`, `created_at`, `updated_at`, `schedule_status`) VALUES
(1, '6', '22334', 'FSDS', 2, 'Intact', 'Active', '2026-05-14', '2026-05-12 08:41:48', '2026-05-13 09:32:45', 1),
(2, '6', '44556', 'FDSS', 2, 'Intact', 'Active', '2026-05-15', '2026-05-13 06:48:51', '2026-05-13 08:40:36', 0),
(3, '2', '77889', 'FDSS', 2, 'Intact', 'Active', '2026-05-30', '2026-05-13 08:42:22', '2026-05-13 08:42:22', 0),
(4, '6', 'SW LWACCN 121655', 'FSDS', 2, 'Intact', 'Active', '2026-06-25', '2026-05-14 07:52:02', '2026-05-14 07:53:59', 0),
(5, NULL, 'SELC55443', 'FDSS', 2, 'Intact', 'Active', '2026-05-22', '2026-05-18 10:16:35', '2026-05-18 10:26:53', 0);

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

--
-- Dumping data for table `fdss_train_information`
--

INSERT INTO `fdss_train_information` (`train_info_id`, `user_id`, `train_no`, `train_name`, `No_of_Coach`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, '22895', 'rajdhani expree', 22, 'Active', '2026-05-12 08:23:28', '2026-05-12 08:25:27'),
(6, 2, '12345', 'express', 3, 'Active', '2026-05-12 08:28:53', '2026-05-12 08:28:53');

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
(2, 'kings', NULL, 'kings', 'kings@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ORG_ADMIN', 1, '2026-04-10', '2026-08-12', 'Active', NULL, '2026-04-10 08:45:10', '2026-04-14 08:20:17'),
(4, 'admin', NULL, 'admin', 're@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ADMIN', 1, '2026-04-06', '2026-07-30', 'Active', NULL, '2026-04-06 08:32:59', '2026-05-06 11:42:08'),
(5, 'Golu', NULL, 'aaa', 'aakh@gmail.com', '$2y$10$7on.bGREoRIspZUXEmKDIuLimMMn9caghLWlzhC5e.LXEAh04iL5q', NULL, 'N/A', 'N/A', NULL, 'AUDITOR', 1, '2026-05-30', '2026-05-30', 'Active', 2, '2026-05-06 08:08:19', '2026-05-12 08:20:14'),
(6, 'akhil gusain', NULL, 'akhil_gusian_12', 'akhilgusain2@mail.com', '$2y$10$wZNR/Ffnno54swOy.G6NC.rbUgxdLf264IFsrk5MPqdSBTR6nT1ZG', NULL, '7830773698', 'Hr', NULL, 'AUDITOR', NULL, NULL, NULL, 'Active', 2, '2026-05-12 08:17:58', '2026-05-12 08:20:25');

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
  ADD KEY `user_id` (`user_id`);

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
  ADD KEY `idx_coach_no` (`coach_no`),
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
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fdds_inventory_unit`
--
ALTER TABLE `fdds_inventory_unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `fdss_coach_inventory`
--
ALTER TABLE `fdss_coach_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fdss_coach_schedule`
--
ALTER TABLE `fdss_coach_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `fdss_Inventory_Management`
--
ALTER TABLE `fdss_Inventory_Management`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fdss_manufacturers`
--
ALTER TABLE `fdss_manufacturers`
  MODIFY `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fdss_stations`
--
ALTER TABLE `fdss_stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fdss_train_coach`
--
ALTER TABLE `fdss_train_coach`
  MODIFY `coach_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  MODIFY `train_info_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fdss_users`
--
ALTER TABLE `fdss_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  ADD CONSTRAINT `fdds_coach_inspection_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `fdss_users` (`user_id`);

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
