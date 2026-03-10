-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/

-- Host: 127.0.0.1:3306
-- Generation Time: Mar 05, 2026 at 03:17 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `srasystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `att_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`emp_id`,`att_date`),
  UNIQUE KEY `unique_attendance` (`emp_id`,`att_date`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `emp_id`, `att_date`, `time_in`, `time_out`, `updated_at`) VALUES
(190, 28, '2026-03-01', '07:00:00', '17:00:00', '2026-03-05 00:15:29'),
(194, 56, '2026-03-02', '07:00:00', '17:00:00', '2026-03-05 08:22:20'),
(195, 56, '2026-03-03', '07:00:00', '17:00:00', '2026-03-05 08:22:59'),
(196, 56, '2026-03-04', '08:30:00', '17:00:00', '2026-03-05 08:22:45');

-- --------------------------------------------------------

--
-- Table structure for table `borrowing_records`
--

DROP TABLE IF EXISTS `borrowing_records`;
CREATE TABLE IF NOT EXISTS `borrowing_records` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `borrower_name` varchar(100) DEFAULT NULL,
  `tool_name` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `date_borrowed` date DEFAULT NULL,
  `time_borrowed` time DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `actual_return_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `condition_on_return` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `borrowing_records`
--

INSERT INTO `borrowing_records` (`transaction_id`, `borrower_name`, `tool_name`, `quantity`, `date_borrowed`, `time_borrowed`, `expected_return_date`, `actual_return_date`, `actual_return_time`, `status`, `condition_on_return`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Gian', 'Hammer', 1, '2026-02-11', '06:41:02', NULL, '2026-02-11', '06:45:09', 'returned', 'Good', '[Return Note]', 4, '2026-02-11 06:41:02', '2026-02-11 06:45:09'),
(2, 'Adan', 'Safety Boots', 1, '2026-02-11', '06:42:00', NULL, '2026-02-23', '10:17:19', 'returned', 'Good', '\n[Return Note] ', 4, '2026-02-11 06:42:00', '2026-02-23 10:17:19'),
(3, 'Gian', 'Ladder', 1, '2026-02-11', '06:53:20', NULL, '2026-02-18', '10:12:11', 'returned', 'Good', '[Return Note]', 4, '2026-02-11 06:53:20', '2026-02-20 13:23:27'),
(4, 'John', 'Drill', 1, '2026-02-11', '07:17:51', '2026-02-10', '2026-02-16', '08:54:29', 'returned', 'Fair', '[Return Note]', 4, '2026-02-11 07:17:51', '2026-02-20 13:23:32'),
(5, 'Justin', 'Safety Boots', 2, '2026-02-11', '15:41:52', '2026-02-20', '2026-02-11', '07:42:20', 'returned', 'Fair', '[Return Note]', 3, '2026-02-11 07:41:52', '2026-02-20 13:23:35'),
(6, 'Adan', 'Drill', 1, '2026-02-11', '15:45:07', NULL, '2026-02-23', '11:43:12', 'returned', 'Damaged', '\n[Return Note] ', 3, '2026-02-11 07:45:07', '2026-02-23 11:43:12'),
(7, 'Justin', 'Drill', 2, '2026-02-16', '08:49:33', '2026-02-17', '2026-02-24', '08:27:52', 'returned', 'Good', 'For project\n[Return Note] ', 3, '2026-02-16 00:49:33', '2026-02-24 08:27:52'),
(8, 'Gian', 'Safety Boots', 1, '2026-02-16', '08:52:37', NULL, '2026-02-23', '09:40:38', 'returned', 'Good', '\n[Return Note] ', 3, '2026-02-16 00:52:37', '2026-02-23 09:40:38'),
(9, 'John', 'Screw Driver', 1, '2026-02-16', '14:58:38', '2026-02-16', '2026-02-19', '11:32:16', 'returned', 'Good', 'Need for new project\n[Return Note] ', 8, '2026-02-16 14:58:38', '2026-02-20 13:23:47'),
(11, 'Adan', 'Ladder', 2, '2026-02-23', '11:42:20', '2026-02-24', '2026-02-27', '10:15:29', 'returned', 'Good', 'for project\n[Return Note] ', 14, '2026-02-23 11:42:20', '2026-02-27 10:15:29'),
(12, 'Gian', 'Hammer', 5, '2026-02-23', '13:01:59', '2026-02-24', NULL, NULL, 'borrowed', NULL, '', 14, '2026-02-23 13:01:59', '2026-02-23 13:01:59'),
(13, 'Josh', 'Screw Driver', 1, '2026-02-23', '13:36:07', '2026-02-25', NULL, NULL, 'borrowed', NULL, '', 14, '2026-02-23 13:36:07', '2026-02-23 13:36:07'),
(14, 'Mark', 'Hammer', 1, '2026-02-24', '10:25:27', '2026-02-25', NULL, NULL, 'borrowed', NULL, '', 14, '2026-02-24 10:25:27', '2026-02-24 10:25:27'),
(15, 'Ivan', 'Safety Gloves', 3, '2026-02-24', '10:26:10', '2026-02-24', NULL, NULL, 'borrowed', NULL, 'For work project', 14, '2026-02-24 10:26:10', '2026-02-24 10:26:10'),
(16, 'Josh', 'Hammer', 1, '2026-02-24', '10:38:00', NULL, NULL, NULL, 'borrowed', NULL, '', 14, '2026-02-24 10:38:00', '2026-02-24 10:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `cash_advances`
--

DROP TABLE IF EXISTS `cash_advances`;
CREATE TABLE IF NOT EXISTS `cash_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `date_given` date NOT NULL,
  `notes` text,
  `status` enum('pending','deducted','cancelled') NOT NULL DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consumables`
--

DROP TABLE IF EXISTS `consumables`;
CREATE TABLE IF NOT EXISTS `consumables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(150) DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `unit` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consumables`
--

INSERT INTO `consumables` (`id`, `item_name`, `quantity`, `unit`, `created_at`, `updated_at`) VALUES
(1, 'Welding Rod Stainless', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:30'),
(2, 'Welding Rod', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:28'),
(3, 'Sand Paper', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:26'),
(4, 'Sand Paper', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:20'),
(5, 'Sand Paper', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:18'),
(6, 'Cutting Disc', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:16'),
(7, 'Grinding Disc', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:15'),
(8, 'Buffing Disc', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:13'),
(9, 'Sicut Disc', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:10'),
(10, 'Chalkstone', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:08'),
(11, 'Bi Fold Stainless Wheel', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-27 11:17:52'),
(12, 'Sliding Gate Wheel', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:06'),
(13, 'Gate Hinges Size 150 x 9', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:03'),
(14, 'Black Screw', 0, 'box', '2026-02-16 01:20:14', '2026-02-16 02:14:30'),
(15, 'Pillow Block 208', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:15:00'),
(16, 'Pillow Block 205', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:14:57'),
(17, 'Nema Box', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:14:54'),
(18, 'Junction Box', 0, 'pcs', '2026-02-16 01:20:14', '2026-02-16 02:14:52'),
(19, 'PVC Pipe 1/2', 0, 'm', '2026-02-16 01:20:14', '2026-02-16 02:14:50'),
(20, 'PVC Pipe 3/4', 0, 'm', '2026-02-16 01:20:14', '2026-02-16 02:14:47'),
(21, 'White Molding 3/4', 0, 'm', '2026-02-16 01:20:14', '2026-02-16 02:14:44'),
(22, 'White Molding 1/2', 0, 'm', '2026-02-16 01:20:14', '2026-02-16 02:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `consumable_records`
--

DROP TABLE IF EXISTS `consumable_records`;
CREATE TABLE IF NOT EXISTS `consumable_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `project_name` varchar(255) NOT NULL,
  `issued_by` varchar(255) NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `date_acquired` date NOT NULL,
  `notes` text,
  `recorded_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `efence_records`
--

DROP TABLE IF EXISTS `efence_records`;
CREATE TABLE IF NOT EXISTS `efence_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `project_name` varchar(255) NOT NULL,
  `issued_by` varchar(255) NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `date_acquired` date NOT NULL,
  `notes` text,
  `recorded_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `department` enum('Field','Office') NOT NULL DEFAULT 'Field',
  `color` varchar(100) DEFAULT '135deg,#1245a8,#42a5f5',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `basic_salary` decimal(10,2) DEFAULT '0.00',
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full Time','Part Time','Contractual') DEFAULT 'Full Time',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `phone` varchar(20) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `name`, `department`, `color`, `is_active`, `created_at`, `basic_salary`, `position`, `employment_type`, `daily_rate`, `phone`, `hire_date`) VALUES
(1, NULL, 'Adan Cris Estalane', 'Office', '135deg,#1245a8,#42a5f5', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(2, NULL, 'Reyvan Faltado', 'Field', '135deg,#2e7d32,#66bb6a', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(3, NULL, 'Norberto Nolada Jr.', 'Field', '135deg,#6a1b9a,#ab47bc', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(4, NULL, 'Serafin Estalane', 'Field', '135deg,#c62828,#ef5350', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(5, NULL, 'Rhandel Moong', 'Field', '135deg,#e65100,#ffa726', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(6, NULL, 'Christian Nacion', 'Field', '135deg,#00695c,#26a69a', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(7, NULL, 'Danny Rosales', 'Field', '135deg,#283593,#5c6bc0', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(8, NULL, 'John Mar Dionson', 'Field', '135deg,#4a148c,#8e24aa', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(9, NULL, 'John Christian', 'Field', '135deg,#1b5e20,#388e3c', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(10, NULL, 'Larry Marasigan', 'Field', '135deg,#b71c1c,#d32f2f', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(11, NULL, 'Ross Valencia Salonga', 'Field', '135deg,#37474f,#607d8b', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(12, NULL, 'Edelberto Marasigan', 'Field', '135deg,#880e4f,#c2185b', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(13, NULL, 'Ronaldo Estalane', 'Field', '135deg,#0d47a1,#1976d2', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(14, NULL, 'John Leonard Duenas', 'Field', '135deg,#01579b,#0288d1', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(15, NULL, 'Jefferson Magcawas', 'Field', '135deg,#006064,#00838f', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(16, NULL, 'Ronniel Asuzano', 'Field', '135deg,#1b5e20,#2e7d32', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(17, NULL, 'Jhun Gatdula', 'Field', '135deg,#33691e,#558b2f', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(18, NULL, 'Isole Eric Mojar', 'Field', '135deg,#827717,#9e9d24', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(19, NULL, 'Mark Justine Abacsa', 'Field', '135deg,#e65100,#ef6c00', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(20, NULL, 'Marc Bryan Catoy', 'Field', '135deg,#bf360c,#e64a19', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(21, NULL, 'Lovely Grace Carillo', 'Field', '135deg,#880e4f,#ad1457', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(22, NULL, 'Andreus Estalane', 'Field', '135deg,#4a148c,#6a1b9a', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(23, NULL, 'Joel Cantos', 'Field', '135deg,#1a237e,#283593', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(24, NULL, 'Gerald Aranas', 'Field', '135deg,#311b92,#4527a0', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(25, NULL, 'Bryan Macatangay', 'Field', '135deg,#006064,#00838f', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(26, NULL, 'Vince Darrel Tyrone', 'Field', '135deg,#37474f,#546e7a', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(27, NULL, 'Arnel Gaerlan', 'Field', '135deg,#263238,#37474f', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(28, NULL, 'Alvin Nabor', 'Field', '135deg,#1b5e20,#388e3c', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(29, NULL, 'EJ', 'Field', '135deg,#0d47a1,#1565c0', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(30, NULL, 'Sammy Bambao', 'Field', '135deg,#4e342e,#6d4c41', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(31, NULL, 'Ken Noriega', 'Field', '135deg,#212121,#424242', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(32, NULL, 'Sharon', 'Office', '135deg,#c2185b,#e91e63', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(33, NULL, 'Armie', 'Office', '135deg,#7b1fa2,#9c27b0', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(34, NULL, 'Mary Rose', 'Office', '135deg,#0097a7,#00bcd4', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(35, NULL, 'Diana', 'Office', '135deg,#00796b,#009688', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(36, NULL, 'Szchel', 'Office', '135deg,#1976d2,#2196f3', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(37, NULL, 'Mark Daniel', 'Office', '135deg,#303f9f,#3f51b5', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(38, NULL, 'Maria Abacan', 'Office', '135deg,#c62828,#f44336', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(39, NULL, 'Hannah Joy', 'Office', '135deg,#6a1b9a,#9c27b0', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(40, NULL, 'Bianca', 'Office', '135deg,#00838f,#00bcd4', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(41, NULL, 'Andrea', 'Office', '135deg,#2e7d32,#4caf50', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(42, NULL, 'Shanelle', 'Office', '135deg,#e65100,#ff9800', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(43, NULL, 'Mia', 'Office', '135deg,#880e4f,#e91e63', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(44, NULL, 'Cyrene', 'Office', '135deg,#1a237e,#3f51b5', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(45, NULL, 'Juliet', 'Office', '135deg,#4e342e,#795548', 1, '2026-02-25 13:30:44', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(46, NULL, 'Gian', 'Field', '135deg,#1245a8,#42a5f5', 0, '2026-02-25 14:05:23', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(48, NULL, 'Jose De Leon', 'Field', '135deg,#4a148c,#8e24aa', 1, '2026-02-25 14:32:15', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(52, NULL, 'User', 'Office', '135deg,#1245a8,#42a5f5', 0, '2026-03-03 10:17:21', 0.00, 'Programmer', 'Full Time', 500.00, '09611975696', '2026-03-03'),
(53, '123', 'Gian', 'Field', '135deg,#e65100,#ffa726', 0, '2026-03-04 10:42:18', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(54, '1234', 'Pogi', 'Field', '135deg,#880e4f,#c2185b', 0, '2026-03-04 10:42:56', 0.00, NULL, 'Full Time', 0.00, NULL, NULL),
(55, NULL, 'Pogi', 'Field', '135deg,#1245a8,#42a5f5', 0, '2026-03-04 10:43:51', 0.00, 'Programmer', 'Full Time', 1.00, '123', NULL),
(56, 'EMP1', 'Gian', 'Office', '135deg,#6a1b9a,#ab47bc', 1, '2026-03-04 11:00:58', 0.00, 'Programmer', 'Full Time', 100.00, '09611975696', '2026-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `recurrence_type` enum('none','daily','weekly','monthly') NOT NULL DEFAULT 'none',
  `recurrence_end` date DEFAULT NULL,
  `recurrence_group` varchar(36) DEFAULT NULL,
  `assignee` varchar(100) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recurrence` (`recurrence_group`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `date`, `start_time`, `end_time`, `category`, `status`, `recurrence_type`, `recurrence_end`, `recurrence_group`, `assignee`, `notes`, `created_at`, `updated_at`) VALUES
(139, 'Test', '2026-03-03', '07:00:00', '17:00:00', 'other', 'pending', 'none', NULL, NULL, 'User', 'Testing...', '2026-03-03 10:16:15', '2026-03-03 10:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `event_logs`
--

DROP TABLE IF EXISTS `event_logs`;
CREATE TABLE IF NOT EXISTS `event_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_logs`
--

INSERT INTO `event_logs` (`id`, `event_id`, `user_id`, `user_name`, `type`, `content`, `created_at`) VALUES
(2, 15, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:24:16'),
(3, 16, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:24:28'),
(4, 16, 14, 'System Administrator', 'status_change', 'Status changed from pending to confirmed by System Administrator', '2026-02-19 03:25:01'),
(5, 17, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:28:35'),
(6, 17, 14, 'System Administrator', 'edit', 'Event updated by System Administrator', '2026-02-19 03:28:41'),
(7, 18, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(8, 19, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(9, 20, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(10, 21, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(11, 22, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(12, 23, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(13, 24, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(14, 25, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(15, 26, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(16, 27, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(17, 28, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(18, 29, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(19, 30, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:35:39'),
(20, 18, 14, 'System Administrator', 'edit', 'Event updated by System Administrator', '2026-02-19 03:36:31'),
(21, 18, 14, 'System Administrator', 'edit', 'Updated all occurrences by System Administrator (0 events)', '2026-02-19 03:37:10'),
(22, 31, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:46:28'),
(23, 32, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:46:39'),
(24, 33, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:46:46'),
(25, 34, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(26, 35, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(27, 36, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(28, 37, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(29, 38, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(30, 39, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:05'),
(31, 40, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(32, 41, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(33, 42, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(34, 43, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(35, 44, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(36, 45, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(37, 46, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(38, 47, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(39, 48, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(40, 49, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(41, 50, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(42, 51, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(43, 52, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(44, 53, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(45, 54, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(46, 55, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(47, 56, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(48, 57, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(49, 58, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(50, 59, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(51, 60, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(52, 61, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(53, 62, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(54, 63, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(55, 64, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(56, 65, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(57, 66, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(58, 67, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(59, 68, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(60, 69, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(61, 70, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 03:49:53'),
(62, 71, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(63, 72, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(64, 73, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(65, 74, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(66, 75, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(67, 76, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(68, 77, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(69, 78, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(70, 79, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(71, 80, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(72, 81, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(73, 82, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(74, 83, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(75, 84, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(76, 85, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(77, 86, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(78, 87, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(79, 88, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(80, 89, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(81, 90, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(82, 91, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(83, 92, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(84, 93, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(85, 94, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(86, 95, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(87, 96, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(88, 97, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(89, 98, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(90, 99, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(91, 100, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(92, 101, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(93, 102, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(94, 103, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(95, 104, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(96, 105, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(97, 106, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(98, 107, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(99, 108, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(100, 109, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(101, 110, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(102, 111, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(103, 112, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:12'),
(104, 113, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(105, 114, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(106, 115, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(107, 116, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(108, 117, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(109, 118, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(110, 119, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(111, 120, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(112, 121, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(113, 122, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(114, 123, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:00:13'),
(115, 72, 14, 'System Administrator', 'edit', 'Event updated by System Administrator', '2026-02-19 05:00:41'),
(116, 71, 14, 'System Administrator', 'edit', 'Updated all occurrences by System Administrator (0 events)', '2026-02-19 05:00:54'),
(117, 124, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:21:43'),
(118, 125, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 05:35:30'),
(119, 126, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 07:49:26'),
(120, 127, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 07:50:01'),
(121, 128, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 07:52:10'),
(122, 128, 14, 'System Administrator', 'edit', 'Event updated by System Administrator', '2026-02-19 07:52:26'),
(123, 129, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 08:15:15'),
(124, 130, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 08:15:38'),
(125, 131, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 08:16:06'),
(126, 132, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 08:16:37'),
(127, 133, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-19 08:16:53'),
(128, 133, 14, 'System Administrator', 'edit', 'Event updated by System Administrator', '2026-02-19 08:17:49'),
(129, 134, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-20 02:10:53'),
(130, 135, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-20 02:20:56'),
(131, 128, 14, 'System Administrator', 'status_change', 'Status changed from pending to completed by System Administrator', '2026-02-24 02:28:54'),
(132, 127, 14, 'System Administrator', 'status_change', 'Status changed from pending to completed by System Administrator', '2026-02-24 02:29:14'),
(133, 136, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-27 01:39:10'),
(134, 137, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-27 02:10:39'),
(135, 138, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-02-27 02:10:53'),
(136, 139, 14, 'System Administrator', 'created', 'Event created by System Administrator', '2026-03-03 02:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `e_fences`
--

DROP TABLE IF EXISTS `e_fences`;
CREATE TABLE IF NOT EXISTS `e_fences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `e_fences`
--

INSERT INTO `e_fences` (`id`, `item_name`, `quantity`) VALUES
(1, 'E-Fence 1', 1),
(2, 'E-Fence 2', 7),
(3, 'E-Fence 3', 7);

-- --------------------------------------------------------

--
-- Table structure for table `intercom_records`
--

DROP TABLE IF EXISTS `intercom_records`;
CREATE TABLE IF NOT EXISTS `intercom_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `project_name` varchar(255) NOT NULL,
  `issued_by` varchar(255) NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `date_acquired` date NOT NULL,
  `notes` text,
  `recorded_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motors`
--

DROP TABLE IF EXISTS `motors`;
CREATE TABLE IF NOT EXISTS `motors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `motor_name` varchar(150) NOT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `motors`
--

INSERT INTO `motors` (`id`, `motor_name`, `serial_number`, `quantity`, `is_active`, `created_at`) VALUES
(1, 'Remote BLACK', 'RM02601-RM026372', 372, 1, '2026-02-20 06:25:08'),
(2, 'Remote WHITE', 'RMW02601-RMW026120', 120, 1, '2026-02-20 06:25:08'),
(3, 'Swing Motor 3000', 'SW302601A-SW302650B', 100, 1, '2026-02-20 06:25:08'),
(4, 'Swing Motor 6000', 'SW602601A-SW602635B', 70, 1, '2026-02-20 06:25:08'),
(5, 'Panel 3000', 'PN302601-PN302650', 50, 1, '2026-02-20 06:25:08'),
(6, 'Panel 6000', 'PN602601-PN602635', 35, 1, '2026-02-20 06:25:08'),
(7, 'Receiver', 'RC02601-RC026174', 174, 1, '2026-02-20 06:25:08'),
(8, 'Swing Motor (JET) 3000', 'SWJET302601-SWJET302608', 8, 1, '2026-02-20 06:25:08'),
(9, 'Swing Motor (JET) 6000', 'SWJET602601-SWJET602601', 1, 1, '2026-02-20 06:25:08'),
(10, 'Sliding Motor 600', 'SL602601-SL602605', 5, 1, '2026-02-20 06:25:08'),
(11, 'Sliding Motor 800', 'SL802601-SL802620', 20, 1, '2026-02-20 06:25:08'),
(12, 'Sliding Motor 1000', 'SL1002601-SL1002630', 30, 1, '2026-02-20 06:25:08'),
(13, 'Sliding Motor 1200', 'SL1202601-SL1202620', 20, 1, '2026-02-20 06:25:08'),
(14, 'Sliding Motor 2500', 'SL2502601-SL2502615', 15, 1, '2026-02-20 06:25:08'),
(15, 'Sliding Motor (Dynamos) 2500', 'SLD2502601-SLD2502604', 4, 1, '2026-02-20 06:25:08'),
(16, 'Sliding Motor (Dynamos) 1000', 'SLD1002601-SLD1002603', 3, 1, '2026-02-20 06:25:08'),
(17, 'Sliding Motor (Dynamos) 1800', 'SLD1802601-SLD1802601', 1, 1, '2026-02-20 06:25:08'),
(18, 'Garage Door Motor 1000', 'GD1002601-GD1002612', 20, 1, '2026-02-20 06:25:08'),
(19, 'Garage Door Motor 1200', 'GD1202601-GD1202622', 22, 1, '2026-02-20 06:25:08'),
(20, 'Garage Door Motor 700', 'GD702601-GD702612', 12, 1, '2026-02-20 06:25:08'),
(21, 'Swing Gate Sensor', 'PS02601-PS02685', 85, 1, '2026-02-20 06:25:08'),
(22, 'Sliding Gate 800 Sensor', 'PS02686-PS026115', 30, 1, '2026-02-20 06:25:08'),
(23, 'Warning Light', 'WL02601-WL02628', 20, 1, '2026-02-20 06:25:08'),
(25, 'Example', 'RM101-RM105,RM106-RM110,RM111-RM120', 17, 1, '2026-02-20 07:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `motor_records`
--

DROP TABLE IF EXISTS `motor_records`;
CREATE TABLE IF NOT EXISTS `motor_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `motor_id` int NOT NULL,
  `motor_name` varchar(150) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `project_name` varchar(255) NOT NULL DEFAULT '',
  `issued_by` varchar(150) NOT NULL,
  `received_by` varchar(150) NOT NULL,
  `date_acquired` date NOT NULL,
  `notes` text,
  `recorded_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_motor` (`motor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime`
--

DROP TABLE IF EXISTS `overtime`;
CREATE TABLE IF NOT EXISTS `overtime` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `week_start` date NOT NULL,
  `ot_morning` decimal(4,1) DEFAULT '0.0',
  `ot_afternoon` decimal(4,1) DEFAULT '0.0',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_week` (`emp_id`,`week_start`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `overtime`
--

INSERT INTO `overtime` (`id`, `emp_id`, `week_start`, `ot_morning`, `ot_afternoon`, `updated_at`) VALUES
(1, 1, '2026-02-22', 0.0, 0.0, '2026-02-25 14:35:05'),
(5, 46, '2026-02-22', 0.0, 0.0, '2026-02-25 14:57:44'),
(10, 28, '2026-03-02', 0.0, 0.0, '2026-03-05 00:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_trips`
--

DROP TABLE IF EXISTS `overtime_trips`;
CREATE TABLE IF NOT EXISTS `overtime_trips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `trip_date` date NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `depart_office` time DEFAULT NULL,
  `arrive_site` time DEFAULT NULL,
  `depart_site` time DEFAULT NULL,
  `arrive_office` time DEFAULT NULL,
  `is_eligible` tinyint(1) DEFAULT NULL COMMENT 'NULL=pending, 1=eligible, 0=not eligible',
  `ot_hours` int DEFAULT '0',
  `ot_minutes` int DEFAULT '0',
  `notes` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`emp_id`,`trip_date`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `overtime_trips`
--

INSERT INTO `overtime_trips` (`id`, `emp_id`, `trip_date`, `location`, `depart_office`, `arrive_site`, `depart_site`, `arrive_office`, `is_eligible`, `ot_hours`, `ot_minutes`, `notes`, `updated_at`) VALUES
(1, 46, '2026-02-25', 'Bolbok', '07:00:00', '08:00:00', '16:00:00', '17:00:00', 0, 0, 0, '', '2026-02-25 16:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_entries`
--

DROP TABLE IF EXISTS `payroll_entries`;
CREATE TABLE IF NOT EXISTS `payroll_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `days_worked` decimal(5,2) NOT NULL DEFAULT '0.00',
  `absent_days` decimal(5,2) NOT NULL DEFAULT '0.00',
  `late_minutes` decimal(8,2) NOT NULL DEFAULT '0.00',
  `ot_hours` decimal(5,2) NOT NULL DEFAULT '0.00',
  `basic_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `daily_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `basic_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ot_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `absent_deduction` decimal(12,2) NOT NULL DEFAULT '0.00',
  `late_deduction` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gross_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sss` decimal(12,2) NOT NULL DEFAULT '0.00',
  `philhealth` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pagibig` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_advance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(12,2) NOT NULL DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_date_from` (`date_from`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_entries`
--

INSERT INTO `payroll_entries` (`id`, `employee_id`, `date_from`, `date_to`, `days_worked`, `absent_days`, `late_minutes`, `ot_hours`, `basic_salary`, `daily_rate`, `basic_pay`, `ot_pay`, `absent_deduction`, `late_deduction`, `gross_pay`, `sss`, `philhealth`, `pagibig`, `cash_advance`, `other_deductions`, `total_deductions`, `net_pay`, `remarks`, `created_by`, `created_at`) VALUES
(2, 56, '2026-03-01', '2026-03-15', 3.00, 0.00, 20.00, 0.00, 0.00, 100.00, 300.00, 0.00, 0.00, 50.00, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 250.00, '', 14, '2026-03-05 10:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

DROP TABLE IF EXISTS `payroll_settings`;
CREATE TABLE IF NOT EXISTS `payroll_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'sss', '0', '2026-03-05 10:55:53'),
(2, 'philhealth', '0', '2026-03-05 10:55:53'),
(3, 'pagibig', '0', '2026-03-05 10:55:53'),
(4, 'office_days', '15', '2026-03-05 10:54:56'),
(5, 'fabrication_days', '6', '2026-03-05 10:54:56'),
(6, 'late_rate', '2.5', '2026-03-05 10:54:56'),
(7, 'grace_period', '0', '2026-03-05 10:54:56'),
(8, 'ot_rate', '150', '2026-03-05 10:54:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`, `updated_at`) VALUES
(14, 'moderator', '$2y$10$IRhfW6NJnds2FTnOgtfN5uwdkwllAXuuIlbNjo0fefM7nsd0Q0xZ.', 'System Administrator', 'moderator@sra.com', 'moderator', '2026-02-19 10:15:14', '2026-03-02 14:18:04'),
(11, 'user', '$2y$10$m/DlM.Op29E0LWE4KqcazOcV6bNunJBLwvXpuoOFK522QifhyNgnu', 'User Account', 'user@sra.com', 'user', '2026-02-16 15:28:04', '2026-03-02 14:18:30'),
(12, 'sratoolroom', '$2y$10$GFa.K9LSSCOFjjEnyTMc.et.r2NG1AkCr4PuMahHEkURPkhymdi2q', 'Tool Room Administrator', 'toolroom@sra.com', 'admin', '2026-02-16 15:28:40', '2026-03-02 14:23:02'),
(13, 'srahr', '$2y$10$XyriLu4OWFDrl0Fx/EsfDe4NhggIHW.yueHJm8Z6XKTI6bM4EQoD6', 'Human Resources', 'hrsra@sra.com', 'admin', '2026-02-16 16:09:14', '2026-03-02 14:20:09'),
(16, 'Gian', '$2y$10$EqITv/C7JmvcZNaKN2fFieL8ylhm5nIfOudmIwXsmJWl3E0JS642S', 'Gian Sandrex', 'gian@gmail.com', 'moderator', '2026-02-27 13:44:32', '2026-02-27 13:44:32');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `system_key` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_system` (`user_id`,`system_key`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `system_key`) VALUES
(1, 11, 'tool_room'),
(8, 12, 'tool_room'),
(5, 13, 'scheduling'),
(6, 13, 'attendance'),
(7, 13, 'payroll');

-- --------------------------------------------------------

--
-- Table structure for table `video_intercom`
--

DROP TABLE IF EXISTS `video_intercom`;
CREATE TABLE IF NOT EXISTS `video_intercom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `video_intercom`
--

INSERT INTO `video_intercom` (`id`, `item_name`, `quantity`, `created_at`) VALUES
(23, 'Video Intercom 2', 7, '2026-02-20 07:38:28'),
(21, 'Video Intercom 1', 2, '2026-02-20 07:30:32'),
(22, 'Video Intercom 3', 77, '2026-02-20 07:38:21');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `overtime`
--
ALTER TABLE `overtime`
  ADD CONSTRAINT `overtime_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `overtime_trips`
--
ALTER TABLE `overtime_trips`
  ADD CONSTRAINT `overtime_trips_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  ADD CONSTRAINT `payroll_entries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
