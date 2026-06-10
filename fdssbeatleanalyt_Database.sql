-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 10, 2026 at 02:05 PM
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
-- Table structure for table `app_version`
--

CREATE TABLE `app_version` (
  `id` int(11) NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_version`
--

INSERT INTO `app_version` (`id`, `version`, `created_at`, `updated_at`) VALUES
(1, '1.0.0', '2025-12-04 13:35:51', '2025-12-04 13:35:51');

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
  `tool_name` varchar(255) DEFAULT NULL,
  `Serial_No` varchar(11) DEFAULT NULL,
  `Conditions` varchar(255) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `inventory_parameter_id` int(11) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdds_coach_inspection`
--

INSERT INTO `fdds_coach_inspection` (`inspection_id`, `schedule_id`, `train_info_id`, `coach_id`, `auditor_id`, `user_id`, `inventory_id`, `tool_name`, `Serial_No`, `Conditions`, `unit_id`, `inventory_parameter_id`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(57, 21, 14, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'yes', 89, NULL, 'normal', 'NA', '2026-06-08 17:23:38', '2026-06-08 17:24:54'),
(58, 21, 14, 80, 6, 2, 46, 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', 'SN-45678', 'no', 90, NULL, 'normal', 'issue in Heat detection', '2026-06-08 17:23:47', '2026-06-08 17:25:08'),
(59, 21, 14, 80, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SN-45678', 'no', 91, NULL, 'warranty', 'Done', '2026-06-08 17:24:39', '2026-06-08 17:24:39'),
(60, 21, 14, 80, 6, 2, 44, 'Hooter', 'SN-45678', 'no', 92, NULL, 'replace', 'Detach', '2026-06-08 17:25:04', '2026-06-08 17:25:04'),
(61, 21, 14, 80, 6, 2, 50, 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'SN-45678', 'no', 93, NULL, 'normal', 'Faukty', '2026-06-08 17:25:26', '2026-06-08 17:25:26'),
(62, 21, 14, 80, 6, 2, 48, 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 'SN-45678', 'yes', 94, NULL, 'normal', 'Working', '2026-06-08 17:25:47', '2026-06-08 17:25:47'),
(63, 21, 14, 80, 6, 2, 49, 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 'SN-45678', 'yes', 95, NULL, 'normal', 'Working', '2026-06-08 17:25:56', '2026-06-08 17:25:56'),
(64, 21, 14, 80, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SENEW2020', 'yes', 105, NULL, 'normal', 'Working', '2026-06-08 17:31:02', '2026-06-08 17:31:02'),
(65, 21, 14, 80, 6, 2, 44, 'Hooter', 'SE-556677', 'yes', 72, NULL, 'normal', 'Working', '2026-06-08 17:31:31', '2026-06-08 17:31:31'),
(66, 22, 13, 81, 6, 2, 45, 'Flasher light', 'SN-783077', 'yes', 96, NULL, 'normal', 'Working', '2026-06-08 17:33:05', '2026-06-08 17:33:05'),
(67, 22, 13, 81, 6, 2, 46, 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', 'SN-783077', 'yes', 97, NULL, 'normal', 'Working', '2026-06-08 17:33:11', '2026-06-08 17:33:11'),
(68, 22, 13, 81, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SN-783077', 'yes', 98, NULL, 'normal', 'Working', '2026-06-08 17:33:19', '2026-06-08 17:33:19'),
(69, 22, 13, 81, 6, 2, 44, 'Hooter', 'SN-783077', 'yes', 99, NULL, 'normal', 'Done', '2026-06-08 17:33:54', '2026-06-08 17:33:54'),
(70, 22, 13, 81, 6, 2, 50, 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'SN-783077', 'yes', 100, NULL, 'normal', 'Working', '2026-06-08 17:34:01', '2026-06-08 17:34:01'),
(71, 22, 13, 81, 6, 2, 50, 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'SN-783077', 'yes', 100, NULL, 'normal', 'Working', '2026-06-08 17:34:09', '2026-06-08 17:34:09'),
(72, 22, 13, 81, 6, 2, 48, 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 'SN-783077', 'yes', 101, NULL, 'normal', 'Working', '2026-06-08 17:34:18', '2026-06-08 17:34:18'),
(73, 22, 13, 81, 6, 2, 49, 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 'SN-783077', 'yes', 102, NULL, 'normal', 'Working', '2026-06-08 17:34:24', '2026-06-08 17:34:24'),
(74, 23, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'yes', 89, NULL, 'normal', 'Done', '2026-06-08 17:37:35', '2026-06-08 17:37:35'),
(75, 23, NULL, 80, 6, 2, 46, 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', 'SN-45678', 'yes', 90, NULL, 'normal', 'Working', '2026-06-08 17:39:09', '2026-06-08 17:39:09'),
(76, 23, NULL, 80, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SENEW2020', 'yes', 105, NULL, 'normal', 'Working', '2026-06-08 17:39:21', '2026-06-08 17:39:21'),
(77, 23, NULL, 80, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SENEW2020', 'yes', 105, NULL, 'normal', 'Working', '2026-06-08 17:40:03', '2026-06-08 17:40:03'),
(78, 23, NULL, 80, 6, 2, 44, 'Hooter', 'SE-556677', 'yes', 72, NULL, 'normal', 'Working', '2026-06-08 17:40:12', '2026-06-08 17:40:12'),
(79, 23, NULL, 80, 6, 2, 50, 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'SN-45678', 'yes', 93, NULL, 'normal', 'Working', '2026-06-08 17:41:48', '2026-06-08 17:41:48'),
(80, 23, NULL, 80, 6, 2, 48, 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 'SN-45678', 'yes', 94, NULL, 'normal', 'Done', '2026-06-08 17:41:54', '2026-06-08 17:41:54'),
(81, 23, NULL, 80, 6, 2, 49, 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 'SN-45678', 'yes', 95, NULL, 'normal', 'Doen', '2026-06-08 17:42:00', '2026-06-08 17:42:00'),
(82, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'yes', 89, NULL, 'normal', NULL, '2026-06-09 06:42:43', '2026-06-09 06:42:43'),
(83, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'yes', 89, NULL, 'normal', 'All good', '2026-06-09 12:27:09', '2026-06-09 12:27:09'),
(84, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'no', 89, NULL, 'normal', NULL, '2026-06-09 12:27:17', '2026-06-09 12:27:17'),
(85, 24, NULL, 80, 6, 2, 46, 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', 'SN-45678', 'yes', 90, NULL, 'normal', 'All good', '2026-06-09 12:27:31', '2026-06-09 12:27:31'),
(86, 24, NULL, 80, 6, 2, 47, 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 'SENEW2020', 'yes', 105, NULL, 'normal', NULL, '2026-06-09 12:27:37', '2026-06-09 12:27:37'),
(87, 24, NULL, 80, 6, 2, 44, 'Hooter', 'SE-556677', 'no', 72, NULL, 'normal', 'Loose wire, reconnected it.', '2026-06-09 12:28:07', '2026-06-09 12:28:07'),
(88, 24, NULL, 80, 6, 2, 50, 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 'SN-45678', 'no', 93, NULL, 'warranty', 'Disconnect', '2026-06-09 12:29:51', '2026-06-09 12:29:51'),
(89, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'no', 89, NULL, 'normal', NULL, '2026-06-09 12:42:04', '2026-06-09 12:42:04'),
(90, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'yes', 89, NULL, 'normal', NULL, '2026-06-09 12:42:47', '2026-06-09 12:42:47'),
(91, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'no', 89, NULL, 'normal', 'All is well', '2026-06-09 12:43:50', '2026-06-09 12:43:50'),
(92, 24, NULL, 80, 6, 2, 45, 'Flasher light', 'SN-45678', 'no', 89, NULL, 'warranty', 'Xxxx', '2026-06-09 12:46:23', '2026-06-09 12:46:23'),
(93, 24, NULL, 80, 6, 2, 44, 'Hooter', 'SE-556677', 'yes', 72, NULL, 'normal', NULL, '2026-06-09 12:47:16', '2026-06-09 12:47:16');

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
  `use_status` int(11) NOT NULL DEFAULT 0 COMMENT '0 means not in use 1 means use in coach',
  `notes` text DEFAULT NULL,
  `Token_id` varchar(255) DEFAULT NULL,
  `unit_status` enum('warranty_claim_process','Working','Replacement','Not_working') NOT NULL DEFAULT 'Working',
  `Category` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fdds_inventory_unit`
--

INSERT INTO `fdds_inventory_unit` (`unit_id`, `inventory_id`, `inventory_parameter_id`, `user_id`, `serial_number`, `model_number`, `purchase_date`, `Warranty_expire`, `manufacturer_id`, `use_status`, `notes`, `Token_id`, `unit_status`, `Category`, `created_at`, `updated_at`) VALUES
(71, 45, NULL, 2, 'SE-87654', 'MO-56483', '2026-06-01', '2026-07-23 00:00:00', 2, 1, '', NULL, 'Working', 'FDSS', '2026-06-07 19:43:12', '2026-06-09 12:46:37'),
(72, 44, NULL, 2, 'SE-556677', 'MO-556345', '2026-05-01', '2026-09-24 00:00:00', 2, 1, '', NULL, 'Working', 'FDSS', '2026-06-07 20:00:46', '2026-06-08 17:31:22'),
(74, 44, NULL, 2, 'SE-3344', 'Mo-9988', '2026-06-01', '2026-08-13 00:00:00', 1, 0, '', NULL, 'Working', 'FDSS', '2026-06-07 20:16:56', '2026-06-07 20:16:56'),
(75, 44, NULL, 2, 'SE-3678', 'MO-863323', '2026-06-01', '2026-08-13 00:00:00', 1, 0, '', NULL, 'Working', 'FDSS', '2026-06-07 20:17:57', '2026-06-07 20:17:57'),
(76, 44, NULL, 2, 'SE-56373', 'Mo-52473', '2026-06-08', '2026-09-03 00:00:00', 1, 0, '', NULL, 'Working', 'FDSS', '2026-06-07 20:19:40', '2026-06-07 20:19:40'),
(77, 44, NULL, 2, 'SE-6733', 'Mo-2378', '2026-06-01', '2026-07-02 00:00:00', 3, 0, '', NULL, 'Working', 'FDSS', '2026-06-07 20:22:10', '2026-06-07 20:22:10'),
(78, 51, NULL, 2, 'SN-7y666', 'MGH-98654', '2026-06-01', '2026-04-23 00:00:00', 3, 0, '', 'EA03F1294EB962D1', 'Working', 'FSDS', '2026-06-07 21:26:06', '2026-06-07 21:26:06'),
(79, 52, NULL, 2, 'SN-7y666', 'MGH-98654', '2026-06-01', '2026-04-23 00:00:00', 3, 0, '', 'EA03F1294EB962D1', 'Working', 'FSDS', '2026-06-07 21:26:06', '2026-06-07 21:26:06'),
(80, 54, NULL, 2, 'SN-7y666', 'MGH-98654', '2026-06-01', '2026-04-23 00:00:00', 3, 0, '', 'EA03F1294EB962D1', 'Working', 'FSDS', '2026-06-07 21:26:06', '2026-06-07 21:26:06'),
(81, 55, NULL, 2, 'SN-7y666', 'MGH-98654', '2026-06-01', '2026-04-23 00:00:00', 3, 0, '', 'EA03F1294EB962D1', 'Working', 'FSDS', '2026-06-07 21:26:07', '2026-06-07 21:26:07'),
(82, 45, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-07-23 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:26', '2026-06-08 12:17:40'),
(83, 46, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:26', '2026-06-07 21:27:26'),
(84, 47, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:26', '2026-06-07 21:27:26'),
(85, 44, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:27', '2026-06-07 21:27:27'),
(86, 50, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:27', '2026-06-07 21:27:27'),
(87, 48, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:27', '2026-06-07 21:27:27'),
(88, 49, NULL, 2, 'SE-56553', 'MGD-3555', '2026-06-10', '2026-09-03 00:00:00', 2, 0, '', '709584053C18028B', 'Working', 'FDSS', '2026-06-07 21:27:28', '2026-06-07 21:27:28'),
(89, 45, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 0, '', '0AC9F8CF8B4C8319', 'warranty_claim_process', 'FDSS', '2026-06-07 21:36:37', '2026-06-09 12:46:23'),
(90, 46, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 1, '', '0AC9F8CF8B4C8319', 'Working', 'FDSS', '2026-06-07 21:36:38', '2026-06-08 08:01:06'),
(91, 47, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 0, '', '0AC9F8CF8B4C8319', 'warranty_claim_process', 'FDSS', '2026-06-07 21:36:38', '2026-06-08 17:29:35'),
(92, 44, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 0, '', '0AC9F8CF8B4C8319', 'warranty_claim_process', 'FDSS', '2026-06-07 21:36:38', '2026-06-08 17:31:22'),
(93, 50, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 1, '', '0AC9F8CF8B4C8319', 'warranty_claim_process', 'FDSS', '2026-06-07 21:36:39', '2026-06-09 12:29:51'),
(94, 48, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 1, '', '0AC9F8CF8B4C8319', 'Working', 'FDSS', '2026-06-07 21:36:39', '2026-06-08 08:01:07'),
(95, 49, NULL, 2, 'SN-45678', 'MO-34577', '2026-06-05', '2026-08-21 00:00:00', 2, 1, '', '0AC9F8CF8B4C8319', 'Working', 'FDSS', '2026-06-07 21:36:39', '2026-06-08 08:01:07'),
(96, 45, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:40', '2026-06-08 06:44:40'),
(97, 46, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:40', '2026-06-08 06:44:40'),
(98, 47, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:41', '2026-06-08 06:44:41'),
(99, 44, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:42', '2026-06-08 06:44:42'),
(100, 50, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:42', '2026-06-08 06:44:42'),
(101, 48, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:43', '2026-06-08 06:44:43'),
(102, 49, NULL, 2, 'SN-783077', 'MD-456789', '2026-05-06', '2026-11-19 00:00:00', 1, 1, '', '8F22A0389690EA15', 'Working', 'FDSS', '2026-06-08 06:44:44', '2026-06-08 06:44:44'),
(103, 45, NULL, 2, 'SE-20098', 'Mo-3267', '2026-05-05', '2026-07-17 00:00:00', 1, 0, '', NULL, 'Working', 'FDSS', '2026-06-08 12:13:15', '2026-06-08 12:14:01'),
(104, 45, NULL, 2, 'AKHIL3454', 'AKHIL', '2026-06-01', '2026-09-03 00:00:00', 2, 0, '', NULL, 'Working', 'FDSS', '2026-06-08 12:19:00', '2026-06-08 12:19:00'),
(105, 47, NULL, 2, 'SENEW2020', 'MO_2026', '2026-06-02', '2026-07-31 00:00:00', 2, 1, '', NULL, 'Working', 'FDSS', '2026-06-08 17:29:25', '2026-06-08 17:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `fdss_coach_inventory`
--

CREATE TABLE `fdss_coach_inventory` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) DEFAULT NULL,
  `inventory_unit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'normal',
  `warranty_replace_status` enum('warranty','replace','normal') DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_coach_inventory`
--

INSERT INTO `fdss_coach_inventory` (`id`, `coach_id`, `inventory_unit_id`, `user_id`, `status`, `warranty_replace_status`, `created_at`, `updated_at`) VALUES
(65, 81, 96, 2, 'Active', 'normal', '2026-06-08 06:44:40', '2026-06-08 06:44:40'),
(66, 81, 97, 2, 'Active', 'normal', '2026-06-08 06:44:41', '2026-06-08 06:44:41'),
(67, 81, 98, 2, 'Active', 'normal', '2026-06-08 06:44:41', '2026-06-08 06:44:41'),
(68, 81, 99, 2, 'Active', 'normal', '2026-06-08 06:44:42', '2026-06-08 06:44:42'),
(69, 81, 100, 2, 'Active', 'normal', '2026-06-08 06:44:42', '2026-06-08 06:44:42'),
(70, 81, 101, 2, 'Active', 'normal', '2026-06-08 06:44:43', '2026-06-08 06:44:43'),
(71, 81, 102, 2, 'Active', 'normal', '2026-06-08 06:44:44', '2026-06-08 06:44:44'),
(72, 80, 92, 2, 'Inactive', 'replace', '2026-06-08 08:01:04', '2026-06-08 17:31:22'),
(73, 80, 89, 2, 'Inactive', 'warranty', '2026-06-08 08:01:05', '2026-06-09 12:46:37'),
(74, 80, 90, 2, 'Active', 'normal', '2026-06-08 08:01:06', '2026-06-08 08:01:06'),
(75, 80, 91, 2, 'Inactive', 'warranty', '2026-06-08 08:01:06', '2026-06-08 18:37:41'),
(76, 80, 94, 2, 'Active', 'normal', '2026-06-08 08:01:07', '2026-06-08 08:01:07'),
(77, 80, 95, 2, 'Active', 'normal', '2026-06-08 08:01:07', '2026-06-08 08:01:07'),
(78, 80, 93, 2, 'Active', 'warranty', '2026-06-08 08:01:08', '2026-06-09 12:29:51'),
(91, 80, 105, 2, 'Active', 'normal', '2026-06-08 17:29:35', '2026-06-08 17:29:35'),
(92, 80, 72, 2, 'Active', 'normal', '2026-06-08 17:31:22', '2026-06-08 17:31:22'),
(93, 80, 71, 2, 'Active', 'normal', '2026-06-09 12:46:37', '2026-06-09 12:46:37');

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
(21, 80, 14, NULL, '2026-07-10', 'Completed', 6, '2026-06-10 10:00:00', '1_month', 'Normal', 'Completed', '2026-06-08 08:06:16', '2026-06-08 17:31:51', 2, 0),
(22, 81, 13, NULL, '2026-07-09', 'Completed', 6, '2026-06-09 10:00:00', '1_month', 'Normal', 'Done', '2026-06-08 17:20:20', '2026-06-08 17:36:09', 2, 0),
(23, 80, NULL, NULL, '2026-06-08', 'Completed', 6, '2026-06-08 23:06:00', 'Round Trip', 'Normal', 'Complete', '2026-06-08 17:36:44', '2026-06-08 17:42:34', 2, 0),
(24, 80, NULL, NULL, '2026-06-09', 'Assigned', 6, '2026-06-09 11:59:00', 'Round Trip', 'Normal', '', '2026-06-09 06:29:57', '2026-06-09 06:29:57', 2, 0),
(25, 81, 13, NULL, '2026-06-09', 'Assigned', 6, '2026-06-09 18:27:00', 'Round Trip', 'Normal', '', '2026-06-09 12:57:12', '2026-06-09 12:57:12', 2, 0);

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
  `status` enum('Working','Needs Check','Not Working') DEFAULT NULL,
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
(44, 'INV-WC-001', 'Hooter', 9, 'FDSS', 'Working', 2, '2026-06-07 18:01:36', '', '2026-06-07 18:01:36', '2026-06-08 06:44:42'),
(45, 'INV-WC-002', 'Flasher light', 6, 'FDSS', 'Working', 2, '2026-06-07 19:10:51', '', '2026-06-07 19:10:51', '2026-06-08 12:19:01'),
(46, 'INV-WC-003', 'Heat detection test for LWLRRM( Engine shutdown when temp raise)', 3, 'FDSS', 'Working', 2, '2026-06-07 19:11:00', '', '2026-06-07 19:11:00', '2026-06-08 06:44:41'),
(47, 'INV-WC-004', 'Heat Sensor in Genset area in case of LWLRRM/kitchen room incase of LWCBAC', 4, 'FDSS', 'Working', 2, '2026-06-07 19:11:07', '', '2026-06-07 19:11:07', '2026-06-08 17:29:25'),
(48, 'INV-WC-005', 'Smoke Sensor in Guard area in LWLRRM/ store room in LWCBAC', 3, 'FDSS', 'Working', 2, '2026-06-07 19:11:15', '', '2026-06-07 19:11:15', '2026-06-08 06:44:44'),
(49, 'INV-WC-006', 'Smoke Sensor inside Genset area in case of LWLRRM / PP end side in LWCBAC', 3, 'FDSS', 'Working', 2, '2026-06-07 19:11:20', '', '2026-06-07 19:11:20', '2026-06-08 06:44:44'),
(50, 'INV-WC-007', 'Smoke Sensor in Crew area incase of LWLRRM/one each at manager/c&w cabin', 3, 'FDSS', 'Working', 2, '2026-06-07 19:11:28', '', '2026-06-07 19:11:28', '2026-06-08 06:44:43'),
(51, 'INV-WC-008', 'Flasher light', 1, 'FSDS', 'Working', 2, '2026-06-07 19:11:42', '', '2026-06-07 19:11:42', '2026-06-07 21:26:06'),
(52, 'INV-WC-009', 'Hooter', 1, 'FSDS', 'Working', 2, '2026-06-07 19:11:48', '', '2026-06-07 19:11:48', '2026-06-07 21:26:06'),
(54, 'INV-WC-011', 'Smoke Sensor in Compartment', 1, 'FSDS', 'Working', 2, '2026-06-07 19:12:04', '', '2026-06-07 19:12:04', '2026-06-07 21:26:07'),
(55, 'INV-WC-012', 'Smoke Sensor in Lavotory', 1, 'FSDS', 'Working', 2, '2026-06-07 19:12:11', '', '2026-06-07 19:12:11', '2026-06-07 21:26:07');

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
  `coach_status` enum('Detached','Intact') DEFAULT NULL,
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
(80, NULL, '55432', 'SWLCHNA', 'FDSS', 2, 'Intact', 'Active', '2026-07-10', '2026-06-07 21:42:06', '2026-06-08 18:40:33', 0),
(81, '13', '667334', 'SWLCHDG', 'FDSS', 2, 'Intact', 'Active', '2026-07-09', '2026-06-07 21:42:35', '2026-06-08 18:15:18', 0);

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
(13, 2, '21010', 'Delhi Express', 0, 'Active', '2026-06-07 21:41:01', '2026-06-07 21:41:01'),
(14, 2, '33445', 'DDn Express way', 0, 'Active', '2026-06-07 21:41:21', '2026-06-07 21:41:21');

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
  `profile` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_users`
--

INSERT INTO `fdss_users` (`user_id`, `user_name`, `user_code`, `username`, `email`, `password_hash`, `full_name`, `phone`, `designation`, `address`, `role`, `station_id`, `start_date`, `end_date`, `profile`, `status`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 'beatle', NULL, 'beatle', 'beatle@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ORG_ADMIN', 1, '2026-04-06', '2026-07-30', '', 'Active', NULL, '2026-04-06 08:32:59', '2026-06-02 09:26:50'),
(2, 'kings', NULL, 'kings', 'kings@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, '', 'NA', 'dehradun', 'ORG_ADMIN', 1, '2026-04-10', '2026-06-30', '', 'Active', NULL, '2026-04-10 08:45:10', '2026-06-08 19:17:40'),
(4, 'admin', NULL, 'admin', 're@gmail.com', '$2y$10$1ayjmt0x/GsO9P9roIU0Fuci5hWqMywFZr6FYSxSKMAA1BCvwJs1y', NULL, NULL, NULL, NULL, 'ADMIN', 1, '2026-04-06', '2026-07-30', NULL, 'Active', NULL, '2026-04-06 08:32:59', '2026-06-08 20:13:44'),
(5, 'Golu', NULL, 'aaa', 'aakh@gmail.com', '$2y$10$7on.bGREoRIspZUXEmKDIuLimMMn9caghLWlzhC5e.LXEAh04iL5q', NULL, 'N/A', 'N/A', NULL, 'AUDITOR', 1, '2026-05-30', '2026-05-30', '', 'Inactive', 2, '2026-05-06 08:08:19', '2026-05-18 12:27:18'),
(6, 'akhil gusain', NULL, 'akhil', 'akhilgusain2@mail.com', '$2y$10$wZNR/Ffnno54swOy.G6NC.rbUgxdLf264IFsrk5MPqdSBTR6nT1ZG', NULL, '7830773698', 'Hr', NULL, 'AUDITOR', NULL, NULL, NULL, '', 'Active', 2, '2026-05-12 08:17:58', '2026-06-01 13:41:40'),
(8, 'AG', NULL, 'ag__80', 'ag@gmail.com', '$2y$10$r.1vL/kbHXPHPVEzddXAsefU599ubpBFp2ynWyeqLGC19g4xrr2EW', NULL, '9854587456', 'SE', NULL, 'AUDITOR', NULL, NULL, NULL, '', 'Active', 1, '2026-05-26 12:38:39', '2026-05-26 12:38:39');

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
  `POH_Date` datetime DEFAULT NULL,
  `Production_unit` varchar(10) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `claim_complete_date` date DEFAULT NULL,
  `Remark` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `complete_remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fdss_warranty_claim`
--

INSERT INTO `fdss_warranty_claim` (`warranty_claim_id`, `schedule_id`, `unit_id`, `defectiveCause`, `otherObservation`, `referenceNo`, `suggestion`, `POH_Date`, `Production_unit`, `status`, `claim_complete_date`, `Remark`, `created_at`, `complete_remark`) VALUES
(31, 21, 91, 'Hdjdjnx', 'Hsjdnbd d hdbd d', 'Jsjdjs s djb', 'Hdbdbd. D d', '2026-06-08 00:00:00', '1', 'claim process', NULL, NULL, '2026-06-08 22:54:39', NULL),
(32, 24, 93, 'Wiring burnt', 'Not repaired', '1234', 'Replace', '2026-06-09 00:00:00', 'Xxx', 'claim process', NULL, NULL, '2026-06-09 17:59:51', NULL),
(33, 24, 89, 'All burnt', 'To be replaced', '1234', 'Replace', '2026-06-09 00:00:00', 'Zzz', 'claim process', NULL, NULL, '2026-06-09 18:16:23', NULL);

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
-- Indexes for table `app_version`
--
ALTER TABLE `app_version`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `app_version`
--
ALTER TABLE `app_version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fdds_coach_inspection`
--
ALTER TABLE `fdds_coach_inspection`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `fdds_inventory_unit`
--
ALTER TABLE `fdds_inventory_unit`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `fdss_coach_inventory`
--
ALTER TABLE `fdss_coach_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `fdss_coach_schedule`
--
ALTER TABLE `fdss_coach_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `fdss_divisions`
--
ALTER TABLE `fdss_divisions`
  MODIFY `division_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `fdss_Inventory_Management`
--
ALTER TABLE `fdss_Inventory_Management`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

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
  MODIFY `coach_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `fdss_train_information`
--
ALTER TABLE `fdss_train_information`
  MODIFY `train_info_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `fdss_users`
--
ALTER TABLE `fdss_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fdss_warranty_claim`
--
ALTER TABLE `fdss_warranty_claim`
  MODIFY `warranty_claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
