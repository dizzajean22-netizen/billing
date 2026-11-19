-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 05:24 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital_billing`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_records`
--

CREATE TABLE `archived_records` (
  `id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `original_id` int(11) NOT NULL,
  `record_snapshot` longtext NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_by_name` varchar(150) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `restored_by` int(11) DEFAULT NULL,
  `restored_by_name` varchar(150) DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`id`, `admin_id`, `admin_name`, `action_type`, `module`, `record_id`, `reason`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:23:19'),
(2, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:23:48'),
(3, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:23:54'),
(4, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:47'),
(5, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:47'),
(6, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:48'),
(7, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:48'),
(8, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:48'),
(9, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:49'),
(10, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:49'),
(11, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:49'),
(12, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:30:50'),
(13, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:37:10'),
(14, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:37:11'),
(15, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:37:11'),
(16, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:39:29'),
(17, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:40:08'),
(18, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:40:15'),
(19, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:41:26'),
(20, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:41:45'),
(21, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:45:04'),
(22, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-16 22:51:14'),
(23, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 00:35:21'),
(24, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 06:51:27'),
(25, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 06:51:47'),
(26, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 06:58:35'),
(27, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 06:58:36'),
(28, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 06:58:36'),
(29, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 07:13:05'),
(30, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 07:53:35'),
(31, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 07:54:33'),
(32, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 07:54:41'),
(33, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 14:28:35'),
(34, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 18:57:38'),
(35, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:00:57'),
(36, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:03:01'),
(37, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:03:02'),
(38, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:03:33'),
(39, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:16:24'),
(40, 1, 'System Admin', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:16:44'),
(41, 1, 'Dizza Jean Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-17 19:33:39'),
(42, 1, 'Dizza Jean Saturno Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-18 08:01:59'),
(43, 1, 'Dizza Jean Saturno Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-18 08:05:49'),
(44, 1, 'Dizza Jean Saturno Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-18 08:05:51'),
(45, 1, 'Dizza Jean Saturno Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-18 18:38:27'),
(46, 1, 'Dizza Jean Saturno Mangubat', 'VIEW', 'MONITORING', NULL, 'Viewed Administrator Monitoring page', NULL, '::1', '2026-11-19 12:23:22');

-- --------------------------------------------------------

--
-- Table structure for table `billing_categories`
--

CREATE TABLE `billing_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#0f766e',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `billing_categories`
--

INSERT INTO `billing_categories` (`id`, `category_name`, `category_code`, `description`, `color`, `created_at`) VALUES
(1, 'Laboratory Services', 'LAB', 'Blood tests, urinalysis, and other lab procedures', '#28a745', '2026-11-16 11:36:41'),
(2, 'Diagnostic Services', 'DIAG', 'X-rays, ultrasounds, CT scans, and imaging', '#ffc107', '2026-11-16 11:36:41'),
(3, 'Consultation Services', 'CONS', 'Doctor consultations and check-ups', '#6f42c1', '2026-11-16 11:36:41'),
(4, 'Pharmacy', 'PHARM', 'Medications and prescription drugs', '#007bff', '2026-11-16 11:36:41'),
(5, 'Room & Board', 'ROOM', 'Ward, private, and ICU accommodation charges', '#fd7e14', '2026-11-16 11:36:41'),
(6, 'Procedure', 'SURG', 'Surgical and minor procedures', '#dc3545', '2026-11-16 11:36:41'),
(7, 'Other', 'MISC', 'Miscellaneous charges', '#6c757d', '2026-11-16 11:36:41'),
(8, 'Emergency Assessment & Triage', 'EMERG-AT', 'ER consultation, triage assessment, initial medical assessment', '#dc2626', '2026-11-16 21:14:57'),
(9, 'Emergency Treatment & Procedures', 'EMERG-TP', 'Wound cleaning, dressing, suturing, splint application', '#ef4444', '2026-11-16 21:14:57'),
(10, 'Respiratory Emergency Services', 'EMERG-RES', 'Oxygen therapy, nebulization, respiratory assessment', '#f97316', '2026-11-16 21:14:57'),
(11, 'Cardiac Emergency Services', 'EMERG-CAR', 'ECG/EKG, cardiac monitoring, CPR', '#f59e0b', '2026-11-16 21:14:57'),
(12, 'Emergency Medication & IV Services', 'EMERG-MED', 'Medication administration, IV insertion, IV fluid administration', '#eab308', '2026-11-16 21:14:57'),
(13, 'Emergency Diagnostic & Monitoring', 'EMERG-DIAG', 'Blood sugar testing, pulse oximetry, vital signs monitoring', '#84cc16', '2026-11-16 21:14:57'),
(14, 'Emergency Nursing & Patient Care', 'EMERG-NUR', 'Emergency nursing care, patient observation, stabilization', '#22c55e', '2026-11-16 21:14:57'),
(15, 'Emergency Pediatric Services', 'EMERG-PED', 'Pediatric emergency assessment and treatment', '#06b6d4', '2026-11-16 21:14:57'),
(16, 'Emergency OB-GYN Services', 'EMERG-OB', 'Pregnancy-related emergency assessment', '#0ea5e9', '2026-11-16 21:14:57'),
(17, 'Emergency Referral & Transfer', 'EMERG-REF', 'Patient transfer, ambulance reception, referral coordination', '#6366f1', '2026-11-16 21:14:57'),
(18, 'Surgical Services', 'SURGS', 'Minor/major surgery, OR fees, anesthesia, recovery, equipment, supplies', '#b91c1c', '2026-11-16 21:14:57'),
(19, 'Professional Fees', 'PROF', 'Surgeon, anesthesiologist, attending physician, specialist fees', '#7c3aed', '2026-11-16 21:14:57'),
(20, 'Emergency Services', 'EMERG', 'ER assessment, treatment, monitoring, and nursing care', '#dc2626', '2026-11-16 21:28:26');

-- --------------------------------------------------------

--
-- Table structure for table `billing_records`
--

CREATE TABLE `billing_records` (
  `id` int(11) NOT NULL,
  `patient_db_id` int(11) DEFAULT NULL,
  `patient_id` varchar(20) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_description` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `predicted_category` varchar(100) DEFAULT NULL,
  `actual_category` varchar(100) DEFAULT NULL,
  `matched_rule` varchar(150) DEFAULT NULL,
  `matched_rule_id` int(11) DEFAULT NULL,
  `matched_keyword` varchar(100) DEFAULT NULL,
  `patient_explanation` text DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `charge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `insurance_coverage` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Processing','Paid') NOT NULL DEFAULT 'Pending',
  `date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `is_voided` tinyint(1) DEFAULT 0,
  `void_reason` text DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `billing_records`
--

INSERT INTO `billing_records` (`id`, `patient_db_id`, `patient_id`, `patient_name`, `service_id`, `service_description`, `category`, `predicted_category`, `actual_category`, `matched_rule`, `matched_rule_id`, `matched_keyword`, `patient_explanation`, `processing_time_ms`, `quantity`, `amount`, `total_amount`, `charge_amount`, `insurance_coverage`, `discount`, `amount_paid`, `status`, `date`, `notes`, `remarks`, `created_by`, `created_at`, `updated_at`, `is_archived`, `is_voided`, `void_reason`, `voided_by`, `voided_at`) VALUES
(1, 1, 'P00001', 'Juan Dela Cruz', NULL, 'Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'Consultation Services – Doctor consultations and check-ups', 12, 1, 500.00, 500.00, 500.00, 0.00, 0.00, 500.00, 'Paid', '2026-11-16', 'Initial charges', '[Payment Nov 16, 2026 4:31 PM] Cash — ₱500.00 (Balance: ₱0.00)', 2, '2026-11-16 11:36:41', '2026-11-16 23:31:18', 0, 0, NULL, NULL, NULL),
(2, 1, 'P00001', 'Juan Dela Cruz', NULL, 'Complete Blood Count (CBC)', 'Laboratory Services', 'Laboratory Services', NULL, 'CBC Test Rule', 1, 'cbc', 'Complete Blood Count — a test that checks your blood cells.', 8, 1, 350.00, 350.00, 350.00, 0.00, 0.00, 350.00, 'Paid', '2026-11-16', NULL, '[Payment Nov 16, 2026 4:31 PM] Cash — ₱350.00 (Balance: ₱0.00)', 2, '2026-11-16 11:36:41', '2026-11-16 23:31:18', 0, 0, NULL, NULL, NULL),
(3, 2, 'P00002', 'Maria Clara', NULL, 'Chest X-Ray', 'Diagnostic Services', 'Diagnostic Services', NULL, 'X-Ray Rule', 4, 'x-ray', 'An imaging test that uses radiation to take pictures of bones and internal organs.', 10, 1, 600.00, 600.00, 600.00, 0.00, 0.00, 600.00, 'Paid', '2026-11-16', 'Fully paid', NULL, 3, '2026-11-16 11:36:41', '2026-11-16 11:36:41', 0, 0, NULL, NULL, NULL),
(4, 3, 'P00003', 'Pedro Penduko', NULL, 'Ward Room (per day)', 'Room & Board', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Room & Board – Ward accommodation', 6, 3, 1500.00, 4500.00, 4500.00, 0.00, 0.00, 2000.00, 'Processing', '2026-11-16', 'Partial payment received', NULL, 3, '2026-11-16 11:36:41', '2026-11-16 11:36:41', 0, 0, NULL, NULL, NULL),
(5, 4, 'P00004', 'Angelina Mendoza Gomez', 73, 'Private Room', 'Room Charges', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation in a room intended for one patient.', 11, 1, 25000.00, 24722.22, 0.00, 0.00, 0.00, 24722.22, 'Paid', '2026-11-16', NULL, '[Discount: Employee] -₱277.78\n[Payment Nov 17, 2026 7:42 AM] Cash — ₱24,722.22 (Balance: ₱0.00)', NULL, '2026-11-16 14:49:24', '2026-11-17 14:42:36', 0, 0, NULL, NULL, NULL),
(6, 5, 'P00005', 'Adrian Cueto Panganiban', 42, 'General Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A doctor checks your health and discusses your medical concerns.', 5, 1, 1200.00, 960.00, 0.00, 0.00, 0.00, 960.00, 'Paid', '2026-11-16', NULL, '[Discount: Senior Citizen] -₱240.00\n[Payment Nov 16, 2026 8:40 AM] Cash — ₱960.00 (Balance: ₱0.00)', NULL, '2026-11-16 15:05:11', '2026-11-16 15:40:01', 0, 0, NULL, NULL, NULL),
(7, 5, 'P00006', 'Adrian Cueto Panganiban', 42, 'General Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A doctor checks your health and discusses your medical concerns.', 2, 1, 1200.00, 831.43, 0.00, 0.00, 0.00, 631.43, 'Processing', '2026-11-16', NULL, '[VAT Exempt 12%] -₱128.57\n[Discount: Senior Citizen] -₱240.00\n[Payment Nov 16, 2026 8:51 AM] Cash — ₱631.43 (Balance: ₱200.00)', NULL, '2026-11-16 15:05:22', '2026-11-16 17:02:55', 0, 0, NULL, NULL, NULL),
(8, 7, 'P00007', 'Joana  Mae Manalo Lopez', 38, '2D Echo', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses ultrasound to show the structure and movement of the heart.', 4, 1, 4000.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:17:53', '2026-11-16 16:17:53', 0, 0, NULL, NULL, NULL),
(9, 8, 'P00008', 'Shane Sandoval Mariano', 21, 'Pregnancy Test', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Test that checks for the presence of a pregnancy hormone.', 1, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:20:00', '2026-11-16 16:20:00', 0, 0, NULL, NULL, NULL),
(10, 9, 'P00009', 'Daniela Santos Vidal', 22, 'Dengue NS1', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that helps detect an early dengue infection.', 3, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:23:34', '2026-11-16 16:23:34', 0, 0, NULL, NULL, NULL),
(11, 10, 'P00010', 'maria heleyna cassanova manalo', 13, 'CBC', 'Laboratory Services', 'Laboratory Services', NULL, 'CBC Test Rule', 1, 'cbc', 'Blood test that checks red blood cells, white blood cells, and platelets.', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-06', NULL, NULL, NULL, '2026-11-16 16:48:56', '2026-11-16 16:48:56', 0, 0, NULL, NULL, NULL),
(12, 11, 'P00011', 'kelsey villalobos', 31, 'BUN', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'bun', 'Blood test that helps evaluate kidney function.', 2, 1, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:50:54', '2026-11-16 16:50:54', 0, 0, NULL, NULL, NULL),
(13, 12, 'P00012', 'Rona Mae Torres Villanueva', 16, 'Blood Chemistry', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood tests that measure different substances in the blood.', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:52:07', '2026-11-16 16:52:07', 0, 0, NULL, NULL, NULL),
(14, 13, 'P00013', 'kathyrin mendoza', 67, 'Cotton', 'Medical Supplies', NULL, NULL, NULL, NULL, NULL, 'Medical material used for cleaning or applying solutions.', 1, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 1000.00, 'Paid', '2026-11-16', NULL, '[Payment Nov 16, 2026 4:27 PM] Cash — ₱1,000.00 (Balance: ₱0.00)', NULL, '2026-11-16 16:52:33', '2026-11-16 23:27:16', 0, 0, NULL, NULL, NULL),
(15, 14, 'P00014', 'princess ibay', 22, 'Dengue NS1', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that helps detect an early dengue infection.', 1, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-16', NULL, NULL, NULL, '2026-11-16 16:55:34', '2026-11-16 16:55:34', 0, 0, NULL, NULL, NULL),
(16, 13, 'P00013', 'kathyrin mendoza', 1, 'Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'Consultation Services – Doctor consultations and check-ups', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 500.00, 'Paid', '2026-11-16', '', '[Payment Nov 16, 2026 4:27 PM] Cash — ₱500.00 (Balance: ₱0.00)', NULL, '2026-11-16 16:57:09', '2026-11-16 23:27:16', 0, 0, NULL, NULL, NULL),
(17, 15, 'P00015', 'cristopher juan', 31, 'BUN', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'bun', 'Blood test that helps evaluate kidney function.', 2, 1, 5000.00, 3463.29, 0.00, 0.00, 0.00, 3463.29, 'Paid', '2026-11-16', NULL, '[VAT Exempt 12%] -₱535.71\n[Discount: PWD] -₱1,000.00\n[Insurance: Intellicare] -₱1.00\n[Payment Nov 17, 2026 5:32 AM] Cash — ₱3,463.29 (Balance: ₱0.00)', NULL, '2026-11-16 17:00:05', '2026-11-17 12:32:16', 0, 0, NULL, NULL, NULL),
(18, 16, 'P00016', 'gian delaluna', 38, '2D Echo', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses ultrasound to show the structure and movement of the heart.', 2, 1, 7000.00, 7000.00, 0.00, 0.00, 0.00, 7000.00, 'Paid', '2026-11-16', NULL, '[Payment Nov 16, 2026 4:02 PM] Cash — ₱7,000.00 (Balance: ₱0.00)', NULL, '2026-11-16 17:03:23', '2026-11-16 23:02:44', 0, 0, NULL, NULL, NULL),
(19, 16, 'P00015', 'gian delaluna', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 12, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 500.00, 'Paid', '2026-11-16', '', '[Payment Nov 16, 2026 4:02 PM] Cash — ₱500.00 (Balance: ₱0.00)', NULL, '2026-11-16 17:35:54', '2026-11-16 23:02:44', 0, 0, NULL, NULL, NULL),
(20, 13, 'P00012', 'kathyrin mendoza', 38, '2D Echo', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses ultrasound to show the structure and movement of the heart.', 26, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 800.00, 'Paid', '2026-11-16', '', '[Payment Nov 16, 2026 4:29 PM] Cash — ₱800.00 (Balance: ₱0.00)', NULL, '2026-11-16 23:29:34', '2026-11-16 23:29:59', 0, 0, NULL, NULL, NULL),
(21, 4, 'P00004', 'Angelina Mendoza Gomez', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 10, 1, 2000.00, 1977.78, 0.00, 0.00, 0.00, 1977.78, 'Processing', '2026-11-17', '', '[Discount: Employee] -₱22.22\n[Payment Nov 17, 2026 7:42 AM] Cash — ₱1,977.78 (Balance: ₱0.00)', NULL, '2026-11-17 08:06:27', '2026-11-17 14:42:36', 0, 0, NULL, NULL, NULL),
(22, 16, 'P00015', 'gian delaluna', 101, 'CPR', 'Other', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cpr', 'Cardiopulmonary Resuscitation — an emergency procedure to restart the heart and breathing.', 3, 1, 500.00, 446.43, 0.00, 0.00, 0.00, 60.00, 'Processing', '2026-11-17', '', '[VAT Exempt 12%] -₱53.57\n[Payment Nov 17, 2026 1:08 AM] Cash — ₱60.00 (Balance: ₱386.43)', NULL, '2026-11-17 08:06:48', '2026-11-17 08:08:16', 0, 0, NULL, NULL, NULL),
(23, 16, 'P00015', 'gian delaluna', 90, 'Triage Assessment', 'Emergency Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'triage assessment', 'A quick check to decide how urgently you need care in the emergency room. A quick check to decide how urgently you need care in the emergency room.', 4, 1, 800.00, 714.28, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-17', '', '[VAT Exempt 12%] -₱85.72', NULL, '2026-11-17 08:06:57', '2026-11-17 08:08:16', 0, 0, NULL, NULL, NULL),
(24, 17, 'P00017', 'Maria Fernandez', 189, 'Vital Signs Monitoring', 'Emergency Services', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'vital signs monitoring', 'Medical staff check your basic body signs to monitor your condition.', 13, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 13:51:10', '2026-11-18 13:51:10', 0, 0, NULL, NULL, NULL),
(25, 18, 'P00018', 'Angela Mae Cruz', 100, 'Cardiac Monitoring', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cardiac monitoring', 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.', 2, 1, 2500.00, 2500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:11:00', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(26, 19, 'P00019', 'Jeremiah Santos De Castro', 22, 'Dengue NS1', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that helps detect an early dengue infection.', 6, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:13:08', '2026-11-18 14:13:08', 0, 0, NULL, NULL, NULL),
(27, 20, 'P00020', 'Mark Anthony Reyes', 47, 'Dental Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A dental examination to assess teeth and oral health.', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-19', NULL, NULL, NULL, '2026-11-18 14:14:16', '2026-11-18 14:14:16', 0, 0, NULL, NULL, NULL),
(28, 21, 'P00021', 'Darwin Velasquez Miranda', 35, 'CT Scan', 'Diagnostic Services', 'Diagnostic Services', NULL, 'CT Scan Rule', 6, 'ct scan', 'Combines X-ray images to create detailed pictures of internal body structures.', 1, 1, 6000.00, 6000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:15:14', '2026-11-18 14:15:14', 0, 0, NULL, NULL, NULL),
(29, 22, 'P00022', 'Sofia Martinez', 107, 'Vital Signs Monitoring', 'Emergency Diagnostic & Monitoring', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'vital signs monitoring', 'Medical staff check your basic body signs to monitor your condition.', 11, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-20', NULL, NULL, NULL, '2026-11-18 14:16:08', '2026-11-18 15:05:02', 0, 0, NULL, NULL, NULL),
(30, 23, 'P00023', 'John Michael Santos', 102, 'Medication Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'medication administration', 'Providing prescribed medications through various routes (oral, IV, etc.) as part of your treatment.', 4, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:17:24', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(31, 24, 'P00024', 'Andrea Rain Cantos Duapa', 100, 'Cardiac Monitoring', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cardiac monitoring', 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.', 1, 1, 3500.00, 3500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:18:29', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(32, 25, 'P00025', 'Nicole Andrea Flores', 89, 'Emergency Consultation', 'Emergency Assessment & Triage', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A prompt medical evaluation by a doctor for an urgent health issue.', 2, 1, 1200.00, 1200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:18:48', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(33, 26, 'P00026', 'Ma. Elaine Maranan Ramos', 104, 'IV Fluid Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'iv fluid administration', 'Delivering fluids and electrolytes directly into your vein to keep you hydrated and support your body\'s functions.', 2, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:21:08', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(34, 27, 'P00027', 'Sean Mathew Valdez', 111, 'Pediatric Emergency Assessment', 'Emergency Pediatric Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', 2, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:21:51', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(35, 28, 'P00028', 'Kyle Sebastian Torres', 96, 'Oxygen Therapy', 'Respiratory Emergency Services', 'Respiratory Emergency Services', NULL, 'Respiratory Emergency Rule', 13, 'oxygen therapy', 'This service provides extra oxygen to help you breathe more comfortably.', 4, 1, 2300.00, 2300.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:23:02', '2026-11-18 15:05:02', 0, 0, NULL, NULL, NULL),
(36, 29, 'P00029', 'Allysa Kate Manalo', 113, 'Pregnancy-Related Emergency Assessment', 'Emergency OB-GYN Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'An urgent medical check-up for pregnant women to ensure the safety of both the mother and the baby.', 2, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:23:48', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(37, 30, 'P00030', 'Joshua Makiling Flores', 1, 'Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A standard medical check-up to discuss your health concerns with a doctor.', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:25:01', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(38, 31, 'P00031', 'Christian Herrera', 57, 'Maintenance Medication', 'Pharmacy', 'Pharmacy', NULL, 'Pharmacy Rule', 8, 'medication', 'Medicines prescribed for ongoing health conditions.', 2, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:25:33', '2026-11-18 14:25:33', 0, 0, NULL, NULL, NULL),
(39, 32, 'P00032', 'Lori Mae Quintos Salvador', 91, 'Initial Medical Assessment', 'Emergency Assessment & Triage', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'initial medical assessment', 'A comprehensive first evaluation by medical staff to determine your immediate healthcare needs.', 1, 1, 1400.00, 1400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:26:57', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(40, 33, 'P00033', 'Chloe Soriano', 193, 'Minor Surgery', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'minor surgery', 'This charge covers a minor operation performed to treat your medical condition.', 2, 1, 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:27:35', '2026-11-18 14:27:35', 0, 0, NULL, NULL, NULL),
(41, 34, 'P00034', 'Samantha A. De Castro', 18, 'Creatinine', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'creatinine', 'Blood test that helps assess kidney function.', 1, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:31:23', '2026-11-18 14:31:23', 0, 0, NULL, NULL, NULL),
(42, 35, 'P00035', 'Denise Soriano Padilla', 11, 'Private Room (per day)', 'Room & Board', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation in a room intended for one patient.', 3, 1, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:32:41', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(43, 36, 'P00036', 'Dianne T. Alferez', 113, 'Pregnancy-Related Emergency Assessment', 'Emergency OB-GYN Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'An urgent medical check-up for pregnant women to ensure the safety of both the mother and the baby.', 2, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:32:57', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(44, 37, 'P00037', 'Vincent Lee Cabrera', 3, 'Complete Blood Count (CBC)', 'Laboratory Services', 'Laboratory Services', NULL, 'CBC Test Rule', 1, 'cbc', 'Blood test that checks red blood cells, white blood cells, and platelets.', 2, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:36:51', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(45, 38, 'P00038', 'Katrina H. alcantara', 115, 'Ambulance Reception', 'Emergency Referral & Transfer', 'Emergency Referral & Transfer', NULL, 'Emergency Transfer Rule', 20, 'ambulance reception', 'The initial intake and stabilization of a patient arriving via ambulance at the emergency department.', 3, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:38:55', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(46, 39, 'P00039', 'Alexis A. Sy', 5, 'Fasting Blood Sugar (FBS)', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'fbs', 'Blood test that measures blood sugar after fasting.', 1, 1, 950.00, 950.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:41:37', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(47, 40, 'P00040', 'Aira Mae B. Apolonia', 104, 'IV Fluid Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'iv fluid administration', 'Delivering fluids and electrolytes directly into your vein to keep you hydrated and support your body\'s functions.', 3, 1, 1300.00, 1300.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:43:08', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(48, 42, 'P00042', 'Maureen L. Legaspi', 76, 'Room and Board', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Hospital accommodation and basic room-related services.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:44:16', '2026-11-18 14:44:16', 0, 0, NULL, NULL, NULL),
(49, 43, 'P00043', 'Mary Jane Avendaño Casanova', 38, '2D Echo', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses ultrasound to show the structure and movement of the heart.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:47:19', '2026-11-18 14:47:19', 0, 0, NULL, NULL, NULL),
(50, 44, 'P00044', 'Jerome K. Abad', 97, 'Nebulization', 'Respiratory Emergency Services', 'Respiratory Emergency Services', NULL, 'Respiratory Emergency Rule', 13, 'nebulization', 'A treatment that turns liquid medicine into a mist you inhale to help open up your airways.', 3, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:47:35', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(51, 45, 'P00045', 'Jellly Ann Mercado Piliin', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 3, 1, 1800.00, 1800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:49:15', '2026-11-18 14:49:15', 0, 0, NULL, NULL, NULL),
(52, 46, 'P00046', 'Bea Cabrera Herrera', 215, 'Cardiologist\'s Fee', 'Professional Fees', NULL, NULL, NULL, NULL, NULL, 'This is the fee for the doctor who evaluates and treats heart-related conditions.', 1, 1, 4000.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:50:48', '2026-11-18 14:50:48', 0, 0, NULL, NULL, NULL),
(53, 47, 'P00047', 'Rafael T. Abad', 37, 'ECG / EKG', 'Diagnostic Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'ecg', 'Records the electrical activity of the heart.', 2, 1, 899.00, 899.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:52:54', '2026-11-18 14:52:54', 0, 0, NULL, NULL, NULL),
(54, 48, 'P00048', 'Patrick J. Salazar', 6, 'Chest X-Ray', 'Diagnostic Services', 'Diagnostic Services', NULL, 'X-Ray Rule', 4, 'x-ray', 'Creates an image of the chest, including the lungs and heart.', 1, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:53:28', '2026-11-18 14:53:28', 0, 0, NULL, NULL, NULL),
(55, 49, 'P00049', 'Zander M. Ford', 181, 'Wound Suturing', 'Emergency Services', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'suturing', 'This service closes your wound with stitches to help it heal.', 3, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:55:09', '2026-11-18 14:55:09', 0, 0, NULL, NULL, NULL),
(56, 50, 'P00050', 'Hannah A. Fahardo', 91, 'Initial Medical Assessment', 'Emergency Assessment & Triage', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'initial medical assessment', 'A comprehensive first evaluation by medical staff to determine your immediate healthcare needs.', 3, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:55:17', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(57, 51, 'P00051', 'Calix G. Hernandez', 99, 'ECG / EKG', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'ecg', 'Records the electrical activity of the heart.', 1, 1, 3800.00, 3800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:56:36', '2026-11-18 14:56:36', 0, 0, NULL, NULL, NULL),
(58, 52, 'P00052', 'Rhea S. Macapagal', 103, 'IV Insertion', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'iv insertion', 'The placement of a small tube into your vein to deliver fluids or medication.', 2, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:57:01', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(59, 53, 'P00053', 'Katrina Chua Cabrera', 75, 'ICU Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation and monitoring in the Intensive Care Unit.', 2, 1, 15000.00, 15000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 14:59:14', '2026-11-18 14:59:14', 0, 0, NULL, NULL, NULL),
(60, 54, 'P00054', 'Sophia N. Robles', 116, 'Referral Coordination', 'Emergency Referral & Transfer', 'Emergency Referral & Transfer', NULL, 'Emergency Transfer Rule', 20, 'referral coordination', 'Arranging for you to see a specialist or receive care at another facility.', 3, 1, 850.00, 850.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:01:50', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(61, 55, 'P00055', 'Daniela Marasigan Buendia', 78, 'Physical Therapy', 'Other', NULL, NULL, NULL, NULL, NULL, 'Treatment that helps improve movement, strength, and physical function.', 3, 1, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:04:24', '2026-11-18 15:04:24', 0, 0, NULL, NULL, NULL),
(62, 56, 'P00056', 'Francis B. Ortega', 112, 'Pediatric Emergency Treatment', 'Emergency Pediatric Services', 'Emergency Pediatric Services', NULL, 'Pediatric Emergency Rule', 18, 'pediatric emergency', 'Urgent medical care provided to infants, children, and adolescents for acute illnesses or injuries.', 8, 1, 1800.00, 1800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:08:29', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(63, 57, 'P00057', 'Mary Ann Pacleb Mercado', 193, 'Minor Surgery', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'minor surgery', 'This charge covers a minor operation performed to treat your medical condition.', 1, 1, 1200.00, 1200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:10:04', '2026-11-18 15:10:04', 0, 0, NULL, NULL, NULL),
(64, 58, 'P00058', 'Gehlee Evangelista', 43, 'Follow-up Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A return appointment to review your condition or treatment progress.', 1, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:10:19', '2026-11-18 15:10:19', 0, 0, NULL, NULL, NULL),
(65, 59, 'P00059', 'Fiona Jumarang De Luna', 110, 'Patient Stabilization', 'Emergency Nursing & Patient Care', 'Emergency Nursing & Patient Care', NULL, 'Emergency Nursing Rule', 17, 'patient stabilization', 'This service provides immediate medical care to help keep your condition from getting worse.', 2, 1, 700.00, 700.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:13:11', '2026-11-18 15:13:11', 0, 0, NULL, NULL, NULL),
(66, 60, 'P00060', 'Jasper Jean Y. Mariano', 194, 'Major Surgery', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'major surgery', 'This charge covers a major operation performed by the surgical team to treat your condition.', 3, 1, 30000.00, 30000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:14:49', '2026-11-18 15:14:49', 0, 0, NULL, NULL, NULL),
(67, 61, 'P00061', 'Keifer H. De Villa', 33, 'X-Ray', 'Diagnostic Services', 'Diagnostic Services', NULL, 'X-Ray Rule', 4, 'x-ray', 'Creates pictures of bones and certain body structures using X-rays.', 1, 1, 400.00, 400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:16:00', '2026-11-18 15:16:00', 0, 0, NULL, NULL, NULL),
(68, 62, 'P00062', 'Jayvee A. Hernandez', 89, 'Emergency Consultation', 'Emergency Assessment & Triage', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A prompt medical evaluation by a doctor for an urgent health issue.', 1, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:18:03', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(69, 63, 'P00063', 'Angel Diane Casanova Atajar', 113, 'Pregnancy-Related Emergency Assessment', 'Emergency OB-GYN Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'An urgent medical check-up for pregnant women to ensure the safety of both the mother and the baby.', 2, 1, 950.00, 950.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:19:08', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(70, 64, 'P00064', 'James R. Matalog', 100, 'Cardiac Monitoring', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cardiac monitoring', 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.', 2, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:25:25', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(71, 65, 'P00065', 'Alexis Gomez Digno', 192, 'Patient Stabilization', 'Emergency Services', 'Emergency Nursing & Patient Care', NULL, 'Emergency Nursing Rule', 17, 'patient stabilization', 'This service provides immediate medical care to help keep your condition from getting worse.', 12, 1, 950.00, 950.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:28:13', '2026-11-18 15:28:13', 0, 0, NULL, NULL, NULL),
(72, 66, 'P00066', 'Ethan C. Ontangco', 99, 'ECG / EKG', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'ecg', 'Records the electrical activity of the heart.', 5, 1, 8000.00, 8000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:34:26', '2026-11-18 15:34:26', 0, 0, NULL, NULL, NULL),
(73, 67, 'P00067', 'Paulo H. Marasigan', 108, 'Emergency Nursing Care', 'Emergency Nursing & Patient Care', 'Emergency Nursing & Patient Care', NULL, 'Emergency Nursing Rule', 17, 'emergency nursing care', 'This covers nursing care and assistance provided while you receive emergency treatment.', 8, 1, 2500.00, 2500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:36:00', '2026-11-18 15:36:00', 0, 0, NULL, NULL, NULL),
(74, 68, 'P00068', 'Edward Apolonia Dimaano', 93, 'Wound Dressing (ER)', 'Emergency Treatment & Procedures', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'wound dressing', 'Cleaning and covering a wound in the emergency room to prevent infection and promote healing.', 14, 1, 1300.00, 1300.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:36:13', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(75, 69, 'P00069', 'Ysabel G. Ortega', 111, 'Pediatric Emergency Assessment', 'Emergency Pediatric Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', 4, 1, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:37:23', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(76, 70, 'P00070', 'Marwin I. Bantilan', 102, 'Medication Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'medication administration', 'Providing prescribed medications through various routes (oral, IV, etc.) as part of your treatment.', 4, 1, 950.00, 950.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:37:41', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(77, 71, 'P00071', 'Camille J. Estrada', 89, 'Emergency Consultation', 'Emergency Assessment & Triage', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A prompt medical evaluation by a doctor for an urgent health issue.', 4, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-26', NULL, NULL, NULL, '2026-11-18 15:39:03', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(78, 72, 'P00072', 'Erika J. Bautista', 203, 'Surgical Drain Care', 'Surgical Services', 'Procedure', NULL, 'Minor Surgery Rule', 10, 'surgical', 'This service involves checking and caring for the device that removes extra fluid from your surgical area.', 4, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:40:07', '2026-11-18 15:40:07', 0, 0, NULL, NULL, NULL),
(79, 73, 'P00073', 'Anthony Guererro Lapid', 81, 'Rehabilitation Therapy', 'Other', NULL, NULL, NULL, NULL, NULL, 'Treatment that supports recovery of physical abilities and daily functions.', 18, 1, 2500.00, 2500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:41:38', '2026-11-18 15:41:38', 0, 0, NULL, NULL, NULL),
(80, 74, 'P00074', 'kenneth D. Miranda', 182, 'Bleeding Control', 'Emergency Services', NULL, NULL, NULL, NULL, NULL, 'This service helps stop or control bleeding from an injury.', 3, 1, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:42:42', '2026-11-18 15:42:42', 0, 0, NULL, NULL, NULL),
(81, 75, 'P00075', 'Keanna G. Garcia', 97, 'Nebulization', 'Respiratory Emergency Services', 'Respiratory Emergency Services', NULL, 'Respiratory Emergency Rule', 13, 'nebulization', 'A treatment that turns liquid medicine into a mist you inhale to help open up your airways.', 2, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:42:42', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(82, 76, 'P00076', 'Byron Sevilla Ramirez', 26, 'Platelet Count', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that measures the number of platelets in the blood.', 4, 1, 150.00, 150.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:44:00', '2026-11-18 15:44:00', 0, 0, NULL, NULL, NULL),
(83, 77, 'P00077', 'Alexandra F. Diokno', 12, 'Wound Dressing', 'Procedure', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'wound dressing', 'Cleaning and applying a protective covering to a wound to help it heal.', 3, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:44:57', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(84, 78, 'P00078', 'Benedict Garcia Jimenez', 96, 'Oxygen Therapy', 'Respiratory Emergency Services', 'Respiratory Emergency Services', NULL, 'Respiratory Emergency Rule', 13, 'oxygen therapy', 'This service provides extra oxygen to help you breathe more comfortably.', 6, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:45:33', '2026-11-18 15:45:33', 0, 0, NULL, NULL, NULL),
(85, 79, 'P00079', 'Yuan Morada Dastas', 99, 'ECG / EKG', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'ecg', 'Records the electrical activity of the heart.', 10, 1, 4000.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:47:10', '2026-11-18 15:47:10', 0, 0, NULL, NULL, NULL),
(86, 80, 'P00080', 'Ethan Cuenca Fernandez', 197, 'Surgical Equipment Usage', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'surgical equipment', 'This charge covers the use of medical equipment needed during your operation.', 4, 1, 4000.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:48:19', '2026-11-18 15:48:19', 0, 0, NULL, NULL, NULL),
(87, 81, 'P00081', 'Briana R. Desepeda', 102, 'Medication Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'medication administration', 'Providing prescribed medications through various routes (oral, IV, etc.) as part of your treatment.', 16, 1, 900.00, 900.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:49:29', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(88, 82, 'P00082', 'Claire Ann E. Maligaya', 61, 'Syringe', 'Room & Board', NULL, NULL, NULL, NULL, NULL, 'A medical device used to measure and administer medication.', 8, 1, 350.00, 350.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:51:09', '2026-11-18 15:51:09', 0, 0, NULL, NULL, NULL),
(89, 83, 'P00083', 'Rhianna Sanggalang Quierra', 35, 'CT Scan', 'Diagnostic Services', 'Diagnostic Services', NULL, 'CT Scan Rule', 6, 'ct scan', 'Combines X-ray images to create detailed pictures of internal body structures.', 4, 1, 5500.00, 5500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:54:26', '2026-11-18 15:54:26', 0, 0, NULL, NULL, NULL),
(90, 84, 'P00084', 'Roman H. Casanova', 214, 'OB-GYN Fee', 'Professional Fees', 'Professional Fees', NULL, 'Professional Fee Rule', 22, 'ob-gyn fee', 'This is the fee for the doctor who provides care related to women\'s health and pregnancy.', 4, 1, 700.00, 700.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 15:56:01', '2026-11-18 15:56:01', 0, 0, NULL, NULL, NULL),
(91, 85, 'P00085', 'Hanna Mendoza De Villa', 34, 'MRI Scan', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses magnets and radio waves to create detailed pictures inside the body.', 7, 1, 1100.00, 1100.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:08:25', '2026-11-18 16:08:25', 0, 0, NULL, NULL, NULL),
(92, 86, 'P00086', 'Toni Flores Malabanan', 211, 'Attending Physician Fee', 'Professional Fees', 'Professional Fees', NULL, 'Professional Fee Rule', 22, 'attending physician', 'This is the fee for the doctor who oversees your medical treatment.', 19, 1, 3500.00, 3500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:19:27', '2026-11-18 16:19:27', 0, 0, NULL, NULL, NULL),
(93, 87, 'P00087', 'Vincent K. Pascual', 100, 'Cardiac Monitoring', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cardiac monitoring', 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.', 4, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:19:56', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(94, 88, 'P00088', 'Ericka D. Selim', 36, 'Ultrasound', 'Diagnostic Services', 'Diagnostic Services', NULL, 'Ultrasound Rule', 5, 'ultrasound', 'Uses sound waves to show pictures of organs and tissues inside the body.', 3, 1, 3500.00, 3500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:21:58', '2026-11-18 16:21:58', 0, 0, NULL, NULL, NULL),
(95, 89, 'P00089', 'Carmina P. Salunguit', 208, 'Surgeon\'s Fee', 'Professional Fees', NULL, NULL, NULL, NULL, NULL, 'The professional fee paid to the doctor who performs your surgery.', 6, 1, 25000.00, 25000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:22:49', '2026-11-18 16:22:49', 0, 0, NULL, NULL, NULL),
(96, 90, 'P00090', 'Daniella G. Geronimo', 89, 'Emergency Consultation', 'Emergency Assessment & Triage', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A prompt medical evaluation by a doctor for an urgent health issue.', 4, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:23:51', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(97, 91, 'P00091', 'Realyn Mercado Caisip', 115, 'Ambulance Reception', 'Emergency Referral & Transfer', 'Emergency Referral & Transfer', NULL, 'Emergency Transfer Rule', 20, 'ambulance reception', 'The initial intake and stabilization of a patient arriving via ambulance at the emergency department.', 23, 1, 750.00, 750.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:27:55', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(98, 92, 'P00092', 'Lara R. Marasigan', 38, '2D Echo', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses ultrasound to show the structure and movement of the heart.', 5, 1, 980.00, 980.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:35:50', '2026-11-18 16:35:50', 0, 0, NULL, NULL, NULL),
(99, 93, 'P00093', 'Joy-joy Miranda Morales', 219, 'Medical Follow-Up Fee', 'Professional Fees', 'Consultation Services', NULL, 'Consultation Rule', 7, 'follow-up', 'This is the fee for the doctor who checks your condition during a return visit.', 5, 1, 400.00, 400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:40:11', '2026-11-18 16:40:11', 0, 0, NULL, NULL, NULL),
(100, 94, 'P00094', 'Duqe Marasigan Santos', 103, 'IV Insertion', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'iv insertion', 'The placement of a small tube into your vein to deliver fluids or medication.', 15, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:41:56', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(101, 95, 'P00095', 'Harvy O. Panganiban', 112, 'Pediatric Emergency Treatment', 'Emergency Pediatric Services', 'Emergency Pediatric Services', NULL, 'Pediatric Emergency Rule', 18, 'pediatric emergency', 'Urgent medical care provided to infants, children, and adolescents for acute illnesses or injuries.', 5, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:43:09', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(102, 96, 'P00096', 'Binene Divereno Santiago', 43, 'Follow-up Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A return appointment to review your condition or treatment progress.', 5, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:43:29', '2026-11-18 16:43:29', 0, 0, NULL, NULL, NULL),
(103, 97, 'P00097', 'Amelia Gomez De Padua', 90, 'Triage Assessment', 'Emergency Assessment & Triage', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'triage assessment', 'A quick check to decide how urgently you need care in the emergency room.', 3, 1, 1800.00, 1800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:44:44', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(104, 98, 'P00098', 'Ma. Sheryl Pinedo Guevarra', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 10, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:45:28', '2026-11-18 16:45:28', 0, 0, NULL, NULL, NULL),
(105, 99, 'P00099', 'kharyl Heniva Puerto', 94, 'Suturing', 'Emergency Treatment & Procedures', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'suturing', 'Closing a wound with stitches to help it heal properly and prevent infection.', 3, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:47:13', '2026-11-18 17:23:01', 0, 0, NULL, NULL, NULL),
(106, 100, 'P00100', 'Robbie N. Calingasan', 73, 'Private Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation in a room intended for one patient.', 4, 1, 30000.00, 30000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:48:57', '2026-11-18 16:48:57', 0, 0, NULL, NULL, NULL),
(107, 101, 'P00101', 'Angelo Trillianes Sabado', 101, 'CPR', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cpr', 'Cardiopulmonary Resuscitation — an emergency procedure to restart the heart and breathing.', 5, 1, 4500.00, 4500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:51:15', '2026-11-18 16:51:15', 0, 0, NULL, NULL, NULL),
(108, 102, 'P00102', 'Angelica Cruz Mendoza', 43, 'Follow-up Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A return appointment to review your condition or treatment progress.', 4, 1, 700.00, 700.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:51:56', '2026-11-18 16:51:56', 0, 0, NULL, NULL, NULL),
(109, 103, 'P00103', 'Francis Elmo Magalona', 111, 'Pediatric Emergency Assessment', 'Emergency Pediatric Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:52:38', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(110, 104, 'P00104', 'Nicole Ramirez Soriano', 95, 'Splint Application', 'Emergency Treatment & Procedures', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'splint application', 'This service supports an injured bone or body part to help prevent unnecessary movement.', 3, 1, 4500.00, 4500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:54:08', '2026-11-18 16:54:08', 0, 0, NULL, NULL, NULL),
(111, 105, 'P00105', 'Beatrice Dueñas Ong', 114, 'Patient Transfer', 'Emergency Referral & Transfer', 'Emergency Referral & Transfer', NULL, 'Emergency Transfer Rule', 20, 'patient transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', 5, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:54:25', '2026-11-18 17:05:16', 0, 0, NULL, NULL, NULL),
(112, 106, 'P00106', 'miguelito Beto Diverra', 48, 'Dermatology Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A consultation for skin, hair, and nail concerns.', 3, 1, 1400.00, 1400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 16:56:17', '2026-11-18 16:56:17', 0, 0, NULL, NULL, NULL),
(113, 107, 'P00107', 'Jasmin Flores Digno', 29, 'SGPT / ALT', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that helps assess liver function.', 6, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:07:51', '2026-11-18 17:07:51', 0, 0, NULL, NULL, NULL),
(114, 108, 'P00108', 'Trisia marie P. Hernandez', 43, 'Follow-up Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A return appointment to review your condition or treatment progress.', 4, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:09:11', '2026-11-18 17:09:11', 0, 0, NULL, NULL, NULL),
(115, 109, 'P00109', 'Blaire U. Velasquez', 112, 'Pediatric Emergency Treatment', 'Emergency Pediatric Services', 'Emergency Pediatric Services', NULL, 'Pediatric Emergency Rule', 18, 'pediatric emergency', 'Urgent medical care provided to infants, children, and adolescents for acute illnesses or injuries.', 6, 1, 850.00, 850.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:09:17', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(116, 110, 'P00110', 'Jericho Castro Secreto', 207, 'Post-Operative Care', 'Surgical Services', NULL, NULL, NULL, NULL, NULL, 'This covers medical care provided after your operation to support your recovery.', 10, 1, 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:11:24', '2026-11-18 17:11:24', 0, 0, NULL, NULL, NULL),
(117, 111, 'P00111', 'Mary Grace malagar Gerrero', 107, 'Vital Signs Monitoring', 'Emergency Diagnostic & Monitoring', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'vital signs monitoring', 'Medical staff check your basic body signs to monitor your condition.', 2, 1, 2000.00, 2000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:12:34', '2026-11-18 17:12:34', 0, 0, NULL, NULL, NULL),
(118, 112, 'P00112', 'Claire Ber Santos Benitez', 75, 'ICU Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation and monitoring in the Intensive Care Unit.', 4, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:14:08', '2026-11-18 17:14:08', 0, 0, NULL, NULL, NULL),
(119, 113, 'P00113', 'Kevin Dela Cuesta Baldrias', 111, 'Pediatric Emergency Assessment', 'Emergency Pediatric Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', 6, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:14:33', '2026-11-18 17:14:33', 0, 0, NULL, NULL, NULL),
(120, 114, 'P00114', 'Paloma Bueño Buena', 215, 'Cardiologist\'s Fee', 'Professional Fees', NULL, NULL, NULL, NULL, NULL, 'This is the fee for the doctor who evaluates and treats heart-related conditions.', 4, 1, 1200.00, 1200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:15:37', '2026-11-18 17:15:37', 0, 0, NULL, NULL, NULL);
INSERT INTO `billing_records` (`id`, `patient_db_id`, `patient_id`, `patient_name`, `service_id`, `service_description`, `category`, `predicted_category`, `actual_category`, `matched_rule`, `matched_rule_id`, `matched_keyword`, `patient_explanation`, `processing_time_ms`, `quantity`, `amount`, `total_amount`, `charge_amount`, `insurance_coverage`, `discount`, `amount_paid`, `status`, `date`, `notes`, `remarks`, `created_by`, `created_at`, `updated_at`, `is_archived`, `is_voided`, `void_reason`, `voided_by`, `voided_at`) VALUES
(121, 115, 'P00115', 'Michael Castillo Rivera', 22, 'Dengue NS1', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that helps detect an early dengue infection.', 3, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:15:44', '2026-11-18 17:15:44', 0, 0, NULL, NULL, NULL),
(122, 116, 'P00116', 'Louise Navarro Domingo', 217, 'Radiologist\'s Fee', 'Professional Fees', NULL, NULL, NULL, NULL, NULL, 'This is the fee for the doctor who examines and interprets your X-ray or other imaging results.', 6, 1, 900.00, 900.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:16:55', '2026-11-18 17:16:55', 0, 0, NULL, NULL, NULL),
(123, 117, 'P00117', 'Genie Lee Sefano', 97, 'Nebulization', 'Respiratory Emergency Services', 'Respiratory Emergency Services', NULL, 'Respiratory Emergency Rule', 13, 'nebulization', 'A treatment that turns liquid medicine into a mist you inhale to help open up your airways.', 3, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:17:20', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(124, 118, 'P00118', 'Kings Greek Weñas', 77, 'Isolation Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'A room designed to help prevent the spread of certain infections.', 5, 1, 4000.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:18:58', '2026-11-18 17:18:58', 0, 0, NULL, NULL, NULL),
(125, 119, 'P00119', 'Ashley Jane Villanueva Calanog', 37, 'ECG / EKG', 'Diagnostic Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'ecg', 'Records the electrical activity of the heart.', 4, 1, 890.00, 890.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:20:07', '2026-11-18 17:20:07', 0, 0, NULL, NULL, NULL),
(126, 120, 'P00120', 'Mitzi Quezon Dela Cruz', 46, 'OB-GYN Consultation', 'Consultation Services', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'A consultation focused on women\'s reproductive health.', 2, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:20:48', '2026-11-18 17:20:48', 0, 0, NULL, NULL, NULL),
(127, 121, 'P00121', 'Rose Ann D. Fernandez', 78, 'Physical Therapy', 'Other', NULL, NULL, NULL, NULL, NULL, 'Treatment that helps improve movement, strength, and physical function.', 2, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:21:30', '2026-11-18 17:21:30', 0, 0, NULL, NULL, NULL),
(128, 122, 'P00122', 'Margarette Mendoza Deverro', 106, 'Pulse Oximetry', 'Emergency Diagnostic & Monitoring', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'pulse oximetry', 'This service covers Pulse Oximetry. Please ask your nurse or doctor for more details.', 3, 1, 700.00, 700.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:21:42', '2026-11-18 17:21:42', 0, 0, NULL, NULL, NULL),
(129, 123, 'P00123', 'Andrea Kin Deverra Santos', 105, 'Blood Sugar Testing', 'Emergency Diagnostic & Monitoring', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'blood sugar testing', 'This service covers Blood Sugar Testing. Please ask your nurse or doctor for more details.', 2, 1, 900.00, 900.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:22:49', '2026-11-18 17:22:49', 0, 0, NULL, NULL, NULL),
(130, 124, 'P00124', 'Patrick Jumarang Garcia', 208, 'Surgeon\'s Fee', 'Professional Fees', NULL, NULL, NULL, NULL, NULL, 'The professional fee paid to the doctor who performs your surgery.', 3, 1, 7000.00, 7000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:23:20', '2026-11-18 17:23:20', 0, 0, NULL, NULL, NULL),
(131, 125, 'P00125', 'Wendy Jane Fano Villalobos', 112, 'Pediatric Emergency Treatment', 'Emergency Pediatric Services', 'Emergency Pediatric Services', NULL, 'Pediatric Emergency Rule', 18, 'pediatric emergency', 'Urgent medical care provided to infants, children, and adolescents for acute illnesses or injuries.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:24:04', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(132, 126, 'P00126', 'Daniel Ford Estrada', 178, 'Emergency Room Assessment', 'Emergency Services', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'This is the initial check performed by the medical staff to understand your condition and determine what care you need.', 5, 1, 2800.00, 2800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:24:15', '2026-11-18 17:24:15', 0, 0, NULL, NULL, NULL),
(133, 127, 'P00127', 'Sarah fen Fuentos Hernandez', 21, 'Pregnancy Test', 'Laboratory Services', 'Emergency OB-GYN Services', NULL, 'OB-GYN Emergency Rule', 19, 'pregnancy', 'Test that checks for the presence of a pregnancy hormone.', 18, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:25:37', '2026-11-18 17:25:37', 0, 0, NULL, NULL, NULL),
(134, 128, 'P00128', 'Anthony James Vergara Manalo', 114, 'Patient Transfer', 'Emergency Referral & Transfer', 'Emergency Referral & Transfer', NULL, 'Emergency Transfer Rule', 20, 'patient transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', 5, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:25:52', '2026-11-18 17:25:52', 0, 0, NULL, NULL, NULL),
(135, 129, 'P00129', 'Juan Dorin Gomez', 94, 'Suturing', 'Emergency Treatment & Procedures', 'Emergency Treatment & Procedures', NULL, 'Emergency Procedure Rule', 12, 'suturing', 'Closing a wound with stitches to help it heal properly and prevent infection.', 3, 1, 400.00, 400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:27:38', '2026-11-18 17:27:38', 0, 0, NULL, NULL, NULL),
(136, 130, 'P00130', 'Taña Elño Mitsubishi', 194, 'Major Surgery', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'major surgery', 'This charge covers a major operation performed by the surgical team to treat your condition.', 4, 1, 15000.00, 15000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:27:48', '2026-11-18 17:27:48', 0, 0, NULL, NULL, NULL),
(137, 131, 'P00131', 'Belinda Reyes Ilagan', 192, 'Patient Stabilization', 'Emergency Services', 'Emergency Nursing & Patient Care', NULL, 'Emergency Nursing Rule', 17, 'patient stabilization', 'This service provides immediate medical care to help keep your condition from getting worse.', 4, 1, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:28:36', '2026-11-18 17:28:36', 0, 0, NULL, NULL, NULL),
(138, 132, 'P00132', 'Krisquen Lapid Cruz', 100, 'Cardiac Monitoring', 'Cardiac Emergency Services', 'Cardiac Emergency Services', NULL, 'Cardiac Emergency Rule', 14, 'cardiac monitoring', 'Continuous monitoring of the heart\'s electrical activity to detect any abnormalities or changes.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:29:07', '2026-11-18 17:33:55', 0, 0, NULL, NULL, NULL),
(139, 133, 'P00133', 'Noreen Marcos Cariño', 17, 'Lipid Profile', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'lipid profile', 'Blood test that measures cholesterol and other fats in the blood.', 5, 1, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:29:50', '2026-11-18 17:29:50', 0, 0, NULL, NULL, NULL),
(140, 134, 'P00134', 'Joseph Maranan Borromeo', 5, 'Fasting Blood Sugar (FBS)', 'Laboratory Services', 'Laboratory Services', NULL, 'Blood Chemistry', 3, 'fbs', 'Blood test that measures blood sugar after fasting.', 6, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:31:05', '2026-11-18 17:31:05', 0, 0, NULL, NULL, NULL),
(141, 135, 'P00135', 'Weather Van Brilliantes', 36, 'Ultrasound', 'Diagnostic Services', 'Diagnostic Services', NULL, 'Ultrasound Rule', 5, 'ultrasound', 'Uses sound waves to show pictures of organs and tissues inside the body.', 2, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:31:15', '2026-11-18 17:31:15', 0, 0, NULL, NULL, NULL),
(142, 136, 'P00136', 'Carlo Buendia Morales', 39, 'Mammography', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Uses specialized X-ray imaging to examine breast tissue.', 4, 1, 1700.00, 1700.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:32:30', '2026-11-18 17:32:30', 0, 0, NULL, NULL, NULL),
(143, 137, 'P00137', 'Christine Aquino Gonzales', 14, 'Fecalysis', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Stool test used to check for digestive problems or intestinal parasites.', 4, 1, 150.00, 150.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:33:27', '2026-11-18 17:33:27', 0, 0, NULL, NULL, NULL),
(144, 138, 'P00138', 'Allysa Jane P. Venzon', 19, 'Uric Acid', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that measures uric acid levels.', 6, 1, 200.00, 200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:35:16', '2026-11-18 17:35:16', 0, 0, NULL, NULL, NULL),
(145, 139, 'P00139', 'John Vic Maligaya Villaranda', 72, 'Semi-Private Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation in a room shared with a limited number of patients.', 3, 1, 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:37:04', '2026-11-18 17:37:04', 0, 0, NULL, NULL, NULL),
(146, 140, 'P00140', 'Princess Jane Panganiban Marasigan', 90, 'Triage Assessment', 'Emergency Assessment & Triage', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'triage assessment', 'A quick check to decide how urgently you need care in the emergency room.', 3, 1, 1300.00, 1300.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-21', NULL, NULL, NULL, '2026-11-18 17:40:29', '2026-11-18 17:40:29', 0, 0, NULL, NULL, NULL),
(147, 141, 'P00141', 'Jun-jun Soriano Italia', 7, 'Abdominal Ultrasound', 'Diagnostic Services', 'Diagnostic Services', NULL, 'Ultrasound Rule', 5, 'ultrasound', 'Uses sound waves to show pictures of organs and tissues inside the body.', 3, 1, 1200.00, 1200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:42:35', '2026-11-18 17:42:35', 0, 0, NULL, NULL, NULL),
(148, 142, 'P00142', 'Adrian Torres Lopez', 73, 'Private Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation in a room intended for one patient.', 20, 1, 45000.00, 45000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:42:55', '2026-11-18 17:42:55', 0, 0, NULL, NULL, NULL),
(149, 143, 'P00143', 'Xandra Collino Dueñas', 102, 'Medication Administration', 'Emergency Medication & IV Services', 'Emergency Medication & IV Services', NULL, 'Emergency Medication Rule', 15, 'medication administration', 'This service covers Medication Administration. Please ask your nurse or doctor for more details.', 7, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:44:25', '2026-11-18 17:44:25', 0, 0, NULL, NULL, NULL),
(150, 144, 'P00144', 'Angela Santos Mendoza', 113, 'Pregnancy-Related Emergency Assessment', 'Emergency OB-GYN Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'This service covers Pregnancy-Related Emergency Assessment. Please ask your nurse or doctor for more details.', 4, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:45:39', '2026-11-18 17:45:39', 0, 0, NULL, NULL, NULL),
(151, 145, 'P00145', 'Vincent Feno Dela cruz', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 5, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:46:00', '2026-11-18 17:46:00', 0, 0, NULL, NULL, NULL),
(152, 146, 'P00146', 'Cristina Santos Faa', 105, 'Blood Sugar Testing', 'Emergency Diagnostic & Monitoring', 'Emergency Diagnostic & Monitoring', NULL, 'Emergency Diagnostic Rule', 16, 'blood sugar testing', 'This service covers Blood Sugar Testing. Please ask your nurse or doctor for more details.', 5, 1, 1200.00, 1200.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:47:06', '2026-11-18 17:47:06', 0, 0, NULL, NULL, NULL),
(153, 147, 'P00147', 'Cherryfeh Andal Maneja', 21, 'Pregnancy Test', 'Laboratory Services', 'Emergency OB-GYN Services', NULL, 'OB-GYN Emergency Rule', 19, 'pregnancy', 'Test that checks for the presence of a pregnancy hormone.', 17, 1, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:49:05', '2026-11-18 17:49:05', 0, 0, NULL, NULL, NULL),
(154, 148, 'P00148', 'Lovely Bituin Craso', 75, 'ICU Room', 'Procedure', 'Room & Board', NULL, 'Room Charge Rule', 9, 'room', 'Accommodation and monitoring in the Intensive Care Unit.', 3, 1, 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:51:32', '2026-11-18 17:51:32', 0, 0, NULL, NULL, NULL),
(155, 149, 'P00149', 'Nhel Jean Lopez Maneja', 194, 'Major Surgery', 'Surgical Services', 'Surgical Services', NULL, 'Surgical Services Rule', 21, 'major surgery', 'This charge covers a major operation performed by the surgical team to treat your condition.', 3, 1, 15000.00, 5392.86, 0.00, 0.00, 0.00, 5392.86, 'Processing', '2026-11-18', NULL, '[VAT Exempt 12%] -₱1,607.14\n[Discount: PWD] -₱3,000.00\n[Insurance: PhilHealth] -₱5,000.00\n[Payment Nov 18, 2026 11:08 AM] Guarantee Letter — ₱5,392.86 (Balance: ₱0.00)\n[GL Info] Agency: DSWD | Ref: GL-2026-001234 | Approved: ₱10,392.86', NULL, '2026-11-18 17:53:01', '2026-11-18 18:08:55', 0, 0, NULL, NULL, NULL),
(156, 150, 'P00150', 'Marian Pamintuan Responde', 28, 'Electrolytes', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Blood test that measures important minerals such as sodium and potassium.', 4, 1, 400.00, 400.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:54:40', '2026-11-18 17:54:40', 0, 0, NULL, NULL, NULL),
(157, 151, 'P00151', 'Jenny Maneja Sano', 89, 'Emergency Consultation', 'Emergency Assessment & Triage', 'Consultation Services', NULL, 'Consultation Rule', 7, 'consultation', 'This service covers Emergency Consultation. Please ask your nurse or doctor for more details.', 3, 1, 1500.00, 1500.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:55:24', '2026-11-18 17:55:24', 0, 0, NULL, NULL, NULL),
(158, 152, 'P00152', 'Joanna H. Morada', 14, 'Fecalysis', 'Laboratory Services', NULL, NULL, NULL, NULL, NULL, 'Stool test used to check for digestive problems or intestinal parasites.', 6, 1, 150.00, 150.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 17:55:44', '2026-11-18 17:55:44', 0, 0, NULL, NULL, NULL),
(159, 153, 'P00153', 'Luna Fueños Morato', 113, 'Pregnancy-Related Emergency Assessment', 'Emergency OB-GYN Services', 'Emergency Assessment & Triage', NULL, 'Emergency Consultation Rule', 11, 'emergency assessment', 'This service covers Pregnancy-Related Emergency Assessment. Please ask your nurse or doctor for more details.', 6, 1, 800.00, 800.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 18:00:34', '2026-11-18 18:00:34', 0, 0, NULL, NULL, NULL),
(160, 154, 'P00154', 'Ryan Mangubat Bang', 40, 'Bone Density Test', 'Diagnostic Services', NULL, NULL, NULL, NULL, NULL, 'Measures bone strength and helps assess bone health.', 5, 1, 1000.00, 1000.00, 0.00, 0.00, 0.00, 0.00, 'Pending', '2026-11-18', NULL, NULL, NULL, '2026-11-18 18:01:47', '2026-11-18 18:01:47', 0, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `discount_types`
--

CREATE TABLE `discount_types` (
  `id` int(11) NOT NULL,
  `discount_name` varchar(150) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('percent','fixed') NOT NULL DEFAULT 'percent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discount_types`
--

INSERT INTO `discount_types` (`id`, `discount_name`, `discount_percentage`, `description`, `created_at`, `payment_type`) VALUES
(1, 'Senior Citizen', 20.00, '20% discount for senior citizens with valid ID', '2026-11-16 11:36:41', 'percent'),
(2, 'PWD', 20.00, '20% discount for persons with disability', '2026-11-16 11:36:41', 'percent'),
(3, 'Employee', 10.00, '10% discount for hospital employees', '2026-11-16 11:36:41', 'percent'),
(4, 'Indigent', 50.00, '50% discount for indigent patients', '2026-11-16 11:36:41', 'percent');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_discounts`
--

CREATE TABLE `insurance_discounts` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_discounts`
--

INSERT INTO `insurance_discounts` (`id`, `name`, `discount_type`, `discount_value`, `is_active`, `created_at`) VALUES
(1, 'None', 'percent', 0.00, 1, '2026-11-16 11:36:41'),
(2, 'PhilHealth', 'percent', 20.00, 1, '2026-11-16 11:36:41'),
(3, 'Senior Citizen', 'percent', 20.00, 1, '2026-11-16 11:36:41'),
(4, 'PWD', 'percent', 20.00, 1, '2026-11-16 11:36:41'),
(5, 'HMO / Private', 'percent', 10.00, 1, '2026-11-16 11:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_providers`
--

CREATE TABLE `insurance_providers` (
  `id` int(11) NOT NULL,
  `provider_name` varchar(150) NOT NULL,
  `coverage_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `coverage_amount` decimal(12,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_type` enum('percent','fixed') NOT NULL DEFAULT 'percent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_providers`
--

INSERT INTO `insurance_providers` (`id`, `provider_name`, `coverage_percentage`, `coverage_amount`, `description`, `created_at`, `payment_type`) VALUES
(1, 'PhilHealth', 20.00, 0.00, 'Government health insurance', '2026-11-16 11:36:41', 'percent'),
(2, 'Maxicare', 80.00, 0.00, 'Private HMO', '2026-11-16 11:36:41', 'percent'),
(3, 'Intellicare', 75.00, 0.00, 'Private HMO', '2026-11-16 11:36:41', 'percent'),
(4, 'Medicard', 70.00, 0.00, 'Private HMO', '2026-11-16 11:36:41', 'percent'),
(5, 'Senior Citizen', 20.00, 0.00, 'Senior citizen discount', '2026-11-16 11:36:41', 'percent');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-bell',
  `color` varchar(20) DEFAULT '#14b8a6',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `icon`, `color`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, NULL, 'fa-user-plus', '#14b8a6', 'New patient registered: Juan Dela Cruz', 'patients.php', 1, '2026-11-16 11:36:41'),
(2, NULL, 'fa-file-circle-plus', '#14b8a6', 'New billing record added for Maria Clara — ₱600.00', 'billing_records.php', 1, '2026-11-16 11:36:41'),
(3, NULL, 'fa-money-bill-wave', '#10b981', '₱600.00 received from Maria Clara — 1 bill fully paid', 'billing_records.php', 1, '2026-11-16 11:36:41'),
(4, NULL, 'fa-info-circle', '#14b8a6', 'Welcome to the Patient-Centered Hospital Billing System.', '', 1, '2026-11-16 11:36:41'),
(5, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angelina Mendoza Gomez — ₱25,000.00', NULL, 1, '2026-11-16 14:49:24'),
(6, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Adrian Cueto Panganiban — ₱1,200.00', NULL, 1, '2026-11-16 15:05:11'),
(7, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Adrian Cueto Panganiban — ₱1,200.00', NULL, 1, '2026-11-16 15:05:22'),
(8, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Joana  Mae Manalo Lopez — ₱4,000.00', NULL, 1, '2026-11-16 16:17:53'),
(9, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Shane Sandoval Mariano — ₱200.00', NULL, 1, '2026-11-16 16:20:00'),
(10, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Daniela Santos Vidal — ₱800.00', NULL, 1, '2026-11-16 16:23:34'),
(11, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: maria heleyna cassanova manalo — ₱500.00', NULL, 1, '2026-11-16 16:48:56'),
(12, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: kelsey villalobos — ₱600.00', NULL, 1, '2026-11-16 16:50:54'),
(13, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Rona Mae Torres Villanueva — ₱500.00', NULL, 1, '2026-11-16 16:52:07'),
(14, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: kathyrin mendoza — ₱1,000.00', NULL, 1, '2026-11-16 16:52:33'),
(15, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: princess ibay — ₱800.00', NULL, 1, '2026-11-16 16:55:34'),
(16, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for kathyrin mendoza — ₱500.00', NULL, 1, '2026-11-16 16:57:09'),
(17, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: cristopher juan — ₱5,000.00', NULL, 1, '2026-11-16 17:00:05'),
(18, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: gian delaluna — ₱7,000.00', NULL, 1, '2026-11-16 17:03:23'),
(19, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for gian delaluna — ₱500.00', NULL, 1, '2026-11-16 17:35:54'),
(20, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for kathyrin mendoza — ₱800.00', NULL, 1, '2026-11-16 23:29:34'),
(21, 2, 'fa-money-bill-wave', '#10b981', '₱850.00 received from Juan Dela Cruz — 2 bill(s) updated, 2 fully paid', 'billing_records.php', 1, '2026-11-16 23:31:18'),
(22, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for Angelina Mendoza Gomez — ₱2,000.00', NULL, 1, '2026-11-17 08:06:27'),
(23, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for gian delaluna — ₱500.00', NULL, 1, '2026-11-17 08:06:48'),
(24, NULL, 'fa-file-circle-plus', '#14b8a6', 'Added 1 transaction for gian delaluna — ₱800.00', NULL, 1, '2026-11-17 08:06:57'),
(25, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Maria Fernandez — ₱1,000.00', NULL, 0, '2026-11-18 13:51:10'),
(26, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angela Mae Cruz — ₱2,500.00', NULL, 0, '2026-11-18 14:11:00'),
(27, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jeremiah Santos De Castro — ₱800.00', NULL, 0, '2026-11-18 14:13:08'),
(28, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Mark Anthony Reyes — ₱500.00', NULL, 0, '2026-11-18 14:14:16'),
(29, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Darwin Velasquez Miranda — ₱6,000.00', NULL, 0, '2026-11-18 14:15:14'),
(30, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Sofia Martinez — ₱800.00', NULL, 0, '2026-11-18 14:16:08'),
(31, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: John Michael Santos — ₱1,500.00', NULL, 0, '2026-11-18 14:17:24'),
(32, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Andrea Rain Cantos Duapa — ₱3,500.00', NULL, 0, '2026-11-18 14:18:29'),
(33, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Nicole Andrea Flores — ₱1,200.00', NULL, 0, '2026-11-18 14:18:48'),
(34, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ma. Elaine Maranan Ramos — ₱1,000.00', NULL, 0, '2026-11-18 14:21:08'),
(35, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Sean Mathew Valdez — ₱1,000.00', NULL, 0, '2026-11-18 14:21:51'),
(36, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Kyle Sebastian Torres — ₱2,300.00', NULL, 0, '2026-11-18 14:23:02'),
(37, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Allysa Kate Manalo — ₱1,500.00', NULL, 0, '2026-11-18 14:23:48'),
(38, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Joshua Makiling Flores — ₱500.00', NULL, 0, '2026-11-18 14:25:01'),
(39, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Christian Herrera — ₱1,000.00', NULL, 0, '2026-11-18 14:25:33'),
(40, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Lori Mae Quintos Salvador — ₱1,400.00', NULL, 0, '2026-11-18 14:26:57'),
(41, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Chloe Soriano — ₱10,000.00', NULL, 0, '2026-11-18 14:27:35'),
(42, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Samantha A. De Castro — ₱200.00', NULL, 0, '2026-11-18 14:31:23'),
(43, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Denise Soriano Padilla — ₱3,000.00', NULL, 0, '2026-11-18 14:32:41'),
(44, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Dianne T. Alferez — ₱800.00', NULL, 0, '2026-11-18 14:32:57'),
(45, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Vincent Lee Cabrera — ₱500.00', NULL, 0, '2026-11-18 14:36:51'),
(46, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Katrina H. alcantara — ₱500.00', NULL, 0, '2026-11-18 14:38:55'),
(47, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Alexis A. Sy — ₱950.00', NULL, 0, '2026-11-18 14:41:37'),
(48, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Aira Mae B. Apolonia — ₱1,300.00', NULL, 0, '2026-11-18 14:43:08'),
(49, NULL, 'fa-user-plus', '#14b8a6', 'New patient registered: Chloey V. Jala', NULL, 0, '2026-11-18 14:43:29'),
(50, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Maureen L. Legaspi — ₱1,500.00', NULL, 0, '2026-11-18 14:44:16'),
(51, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Mary Jane Avendaño Casanova — ₱1,500.00', NULL, 0, '2026-11-18 14:47:19'),
(52, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jerome K. Abad — ₱500.00', NULL, 0, '2026-11-18 14:47:35'),
(53, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jellly Ann Mercado Piliin — ₱1,800.00', NULL, 0, '2026-11-18 14:49:15'),
(54, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Bea Cabrera Herrera — ₱4,000.00', NULL, 0, '2026-11-18 14:50:48'),
(55, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Rafael T. Abad — ₱899.00', NULL, 0, '2026-11-18 14:52:54'),
(56, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Patrick J. Salazar — ₱800.00', NULL, 0, '2026-11-18 14:53:28'),
(57, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Zander M. Ford — ₱500.00', NULL, 0, '2026-11-18 14:55:09'),
(58, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Hannah A. Fahardo — ₱500.00', NULL, 0, '2026-11-18 14:55:17'),
(59, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Calix G. Hernandez — ₱3,800.00', NULL, 0, '2026-11-18 14:56:36'),
(60, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Rhea S. Macapagal — ₱1,000.00', NULL, 0, '2026-11-18 14:57:01'),
(61, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Katrina Chua Cabrera — ₱15,000.00', NULL, 0, '2026-11-18 14:59:14'),
(62, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Sophia N. Robles — ₱850.00', NULL, 0, '2026-11-18 15:01:50'),
(63, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Daniela Marasigan Buendia — ₱3,000.00', NULL, 0, '2026-11-18 15:04:24'),
(64, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Francis B. Ortega — ₱1,800.00', NULL, 0, '2026-11-18 15:08:29'),
(65, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Mary Ann Pacleb Mercado — ₱1,200.00', NULL, 0, '2026-11-18 15:10:04'),
(66, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Gehlee Evangelista — ₱800.00', NULL, 0, '2026-11-18 15:10:19'),
(67, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Fiona Jumarang De Luna — ₱700.00', NULL, 0, '2026-11-18 15:13:11'),
(68, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jasper Jean Y. Mariano — ₱30,000.00', NULL, 0, '2026-11-18 15:14:49'),
(69, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Keifer H. De Villa — ₱400.00', NULL, 0, '2026-11-18 15:16:00'),
(70, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jayvee A. Hernandez — ₱800.00', NULL, 0, '2026-11-18 15:18:03'),
(71, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angel Diane Casanova Atajar — ₱950.00', NULL, 0, '2026-11-18 15:19:08'),
(72, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: James R. Matalog — ₱800.00', NULL, 0, '2026-11-18 15:25:25'),
(73, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Alexis Gomez Digno — ₱950.00', NULL, 0, '2026-11-18 15:28:13'),
(74, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ethan C. Ontangco — ₱8,000.00', NULL, 0, '2026-11-18 15:34:26'),
(75, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Paulo H. Marasigan — ₱2,500.00', NULL, 0, '2026-11-18 15:36:00'),
(76, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Edward Apolonia Dimaano — ₱1,300.00', NULL, 0, '2026-11-18 15:36:13'),
(77, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ysabel G. Ortega — ₱3,000.00', NULL, 0, '2026-11-18 15:37:23'),
(78, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Marwin I. Bantilan — ₱950.00', NULL, 0, '2026-11-18 15:37:41'),
(79, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Camille J. Estrada — ₱1,500.00', NULL, 0, '2026-11-18 15:39:03'),
(80, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Erika J. Bautista — ₱500.00', NULL, 0, '2026-11-18 15:40:07'),
(81, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Anthony Guererro Lapid — ₱2,500.00', NULL, 0, '2026-11-18 15:41:38'),
(82, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: kenneth D. Miranda — ₱3,000.00', NULL, 0, '2026-11-18 15:42:42'),
(83, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Keanna G. Garcia — ₱800.00', NULL, 0, '2026-11-18 15:42:42'),
(84, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Byron Sevilla Ramirez — ₱150.00', NULL, 0, '2026-11-18 15:44:00'),
(85, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Alexandra F. Diokno — ₱500.00', NULL, 0, '2026-11-18 15:44:57'),
(86, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Benedict Garcia Jimenez — ₱1,500.00', NULL, 0, '2026-11-18 15:45:33'),
(87, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Yuan Morada Dastas — ₱4,000.00', NULL, 0, '2026-11-18 15:47:10'),
(88, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ethan Cuenca Fernandez — ₱4,000.00', NULL, 0, '2026-11-18 15:48:19'),
(89, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Briana R. Desepeda — ₱900.00', NULL, 0, '2026-11-18 15:49:29'),
(90, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Claire Ann E. Maligaya — ₱350.00', NULL, 0, '2026-11-18 15:51:09'),
(91, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Rhianna Sanggalang Quierra — ₱5,500.00', NULL, 0, '2026-11-18 15:54:26'),
(92, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Roman H. Casanova — ₱700.00', NULL, 0, '2026-11-18 15:56:01'),
(93, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Hanna Mendoza De Villa — ₱1,100.00', NULL, 0, '2026-11-18 16:08:25'),
(94, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Toni Flores Malabanan — ₱3,500.00', NULL, 0, '2026-11-18 16:19:27'),
(95, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Vincent K. Pascual — ₱5,000.00', NULL, 0, '2026-11-18 16:19:56'),
(96, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ericka D. Selim — ₱3,500.00', NULL, 0, '2026-11-18 16:21:58'),
(97, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Carmina P. Salunguit — ₱25,000.00', NULL, 0, '2026-11-18 16:22:49'),
(98, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Daniella G. Geronimo — ₱1,000.00', NULL, 0, '2026-11-18 16:23:51'),
(99, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Realyn Mercado Caisip — ₱750.00', NULL, 0, '2026-11-18 16:27:55'),
(100, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Lara R. Marasigan — ₱980.00', NULL, 0, '2026-11-18 16:35:50'),
(101, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Joy-joy Miranda Morales — ₱400.00', NULL, 0, '2026-11-18 16:40:11'),
(102, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Duqe Marasigan Santos — ₱1,000.00', NULL, 0, '2026-11-18 16:41:56'),
(103, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Harvy O. Panganiban — ₱1,500.00', NULL, 0, '2026-11-18 16:43:09'),
(104, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Binene Divereno Santiago — ₱800.00', NULL, 0, '2026-11-18 16:43:29'),
(105, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Amelia Gomez De Padua — ₱1,800.00', NULL, 0, '2026-11-18 16:44:44'),
(106, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ma. Sheryl Pinedo Guevarra — ₱1,500.00', NULL, 0, '2026-11-18 16:45:28'),
(107, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: kharyl Heniva Puerto — ₱1,000.00', NULL, 0, '2026-11-18 16:47:13'),
(108, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Robbie N. Calingasan — ₱30,000.00', NULL, 0, '2026-11-18 16:48:57'),
(109, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angelo Trillianes Sabado — ₱4,500.00', NULL, 0, '2026-11-18 16:51:15'),
(110, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angelica Cruz Mendoza — ₱700.00', NULL, 0, '2026-11-18 16:51:56'),
(111, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Francis Elmo Magalona — ₱1,500.00', NULL, 0, '2026-11-18 16:52:38'),
(112, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Nicole Ramirez Soriano — ₱4,500.00', NULL, 0, '2026-11-18 16:54:08'),
(113, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Beatrice Dueñas Ong — ₱1,000.00', NULL, 0, '2026-11-18 16:54:25'),
(114, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: miguelito Beto Diverra — ₱1,400.00', NULL, 0, '2026-11-18 16:56:17'),
(115, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jasmin Flores Digno — ₱200.00', NULL, 0, '2026-11-18 17:07:51'),
(116, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Trisia marie P. Hernandez — ₱800.00', NULL, 0, '2026-11-18 17:09:11'),
(117, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Blaire U. Velasquez — ₱850.00', NULL, 0, '2026-11-18 17:09:17'),
(118, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jericho Castro Secreto — ₱10,000.00', NULL, 0, '2026-11-18 17:11:24'),
(119, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Mary Grace malagar Gerrero — ₱2,000.00', NULL, 0, '2026-11-18 17:12:34'),
(120, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Claire Ber Santos Benitez — ₱5,000.00', NULL, 0, '2026-11-18 17:14:08'),
(121, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Kevin Dela Cuesta Baldrias — ₱800.00', NULL, 0, '2026-11-18 17:14:33'),
(122, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Paloma Bueño Buena — ₱1,200.00', NULL, 0, '2026-11-18 17:15:37'),
(123, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Michael Castillo Rivera — ₱800.00', NULL, 0, '2026-11-18 17:15:44'),
(124, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Louise Navarro Domingo — ₱900.00', NULL, 0, '2026-11-18 17:16:55'),
(125, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Genie Lee Sefano — ₱5,000.00', NULL, 0, '2026-11-18 17:17:20'),
(126, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Kings Greek Weñas — ₱4,000.00', NULL, 0, '2026-11-18 17:18:58'),
(127, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ashley Jane Villanueva Calanog — ₱890.00', NULL, 0, '2026-11-18 17:20:07'),
(128, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Mitzi Quezon Dela Cruz — ₱800.00', NULL, 0, '2026-11-18 17:20:48'),
(129, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Rose Ann D. Fernandez — ₱5,000.00', NULL, 0, '2026-11-18 17:21:30'),
(130, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Margarette Mendoza Deverro — ₱700.00', NULL, 0, '2026-11-18 17:21:42'),
(131, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Andrea Kin Deverra Santos — ₱900.00', NULL, 0, '2026-11-18 17:22:49'),
(132, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Patrick Jumarang Garcia — ₱7,000.00', NULL, 0, '2026-11-18 17:23:20'),
(133, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Wendy Jane Fano Villalobos — ₱1,500.00', NULL, 0, '2026-11-18 17:24:04'),
(134, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Daniel Ford Estrada — ₱2,800.00', NULL, 0, '2026-11-18 17:24:15'),
(135, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Sarah fen Fuentos Hernandez — ₱200.00', NULL, 0, '2026-11-18 17:25:37'),
(136, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Anthony James Vergara Manalo — ₱1,000.00', NULL, 0, '2026-11-18 17:25:52'),
(137, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Juan Dorin Gomez — ₱400.00', NULL, 0, '2026-11-18 17:27:38'),
(138, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Taña Elño Mitsubishi — ₱15,000.00', NULL, 0, '2026-11-18 17:27:48'),
(139, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Belinda Reyes Ilagan — ₱600.00', NULL, 0, '2026-11-18 17:28:36'),
(140, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Krisquen Lapid Cruz — ₱1,500.00', NULL, 0, '2026-11-18 17:29:07'),
(141, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Noreen Marcos Cariño — ₱600.00', NULL, 0, '2026-11-18 17:29:50'),
(142, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Joseph Maranan Borromeo — ₱200.00', NULL, 0, '2026-11-18 17:31:05'),
(143, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Weather Van Brilliantes — ₱1,000.00', NULL, 0, '2026-11-18 17:31:15'),
(144, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Carlo Buendia Morales — ₱1,700.00', NULL, 0, '2026-11-18 17:32:30'),
(145, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Christine Aquino Gonzales — ₱150.00', NULL, 0, '2026-11-18 17:33:27'),
(146, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Allysa Jane P. Venzon — ₱200.00', NULL, 0, '2026-11-18 17:35:16'),
(147, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: John Vic Maligaya Villaranda — ₱10,000.00', NULL, 0, '2026-11-18 17:37:04'),
(148, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Princess Jane Panganiban Marasigan — ₱1,300.00', NULL, 0, '2026-11-18 17:40:29'),
(149, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jun-jun Soriano Italia — ₱1,200.00', NULL, 0, '2026-11-18 17:42:35'),
(150, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Adrian Torres Lopez — ₱45,000.00', NULL, 0, '2026-11-18 17:42:55'),
(151, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Xandra Collino Dueñas — ₱1,500.00', NULL, 0, '2026-11-18 17:44:25'),
(152, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Angela Santos Mendoza — ₱5,000.00', NULL, 0, '2026-11-18 17:45:39'),
(153, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Vincent Feno Dela cruz — ₱1,000.00', NULL, 0, '2026-11-18 17:46:00'),
(154, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Cristina Santos Faa — ₱1,200.00', NULL, 0, '2026-11-18 17:47:06'),
(155, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Cherryfeh Andal Maneja — ₱500.00', NULL, 0, '2026-11-18 17:49:05'),
(156, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Lovely Bituin Craso — ₱10,000.00', NULL, 0, '2026-11-18 17:51:32'),
(157, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Nhel Jean Lopez Maneja — ₱15,000.00', NULL, 0, '2026-11-18 17:53:01'),
(158, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Marian Pamintuan Responde — ₱400.00', NULL, 0, '2026-11-18 17:54:40'),
(159, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Jenny Maneja Sano — ₱1,500.00', NULL, 0, '2026-11-18 17:55:24'),
(160, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Joanna H. Morada — ₱150.00', NULL, 0, '2026-11-18 17:55:44'),
(161, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Luna Fueños Morato — ₱800.00', NULL, 0, '2026-11-18 18:00:34'),
(162, NULL, 'fa-user-plus', '#14b8a6', 'New patient + bill: Ryan Mangubat Bang — ₱1,000.00', NULL, 0, '2026-11-18 18:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 2, '971f8959de16c9f1f3c4a3d960dc3f15a52a098943a5ac7bb0cb6bee787e7ca0', '2026-11-17 09:28:10', 1, '2026-11-17 15:28:10'),
(2, 2, '71531923f25306b7f5d122dc335ef5d840c63f59f7a43ff1a367057c3bc6c1e8', '2026-11-17 09:29:22', 0, '2026-11-17 15:29:22');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_code` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `patient_type` enum('Outpatient','Inpatient','Emergency') NOT NULL DEFAULT 'Outpatient',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_code`, `full_name`, `patient_type`, `date_of_birth`, `gender`, `contact_number`, `address`, `notes`, `created_at`) VALUES
(1, 'P00001', 'Juan Dela Cruz', 'Outpatient', '1985-03-15', 'Male', '09171234567', '123 Main St, Manila', 'Allergic to penicillin', '2026-11-16 11:36:41'),
(2, 'P00002', 'Maria Clara', 'Inpatient', '1990-07-22', 'Female', '09181234567', '456 Rizal Ave, Quezon City', NULL, '2026-11-16 11:36:41'),
(3, 'P00003', 'Pedro Penduko', 'Emergency', '1975-11-30', 'Male', '09191234567', '789 Mabini St, Pasig', 'Hypertension', '2026-11-16 11:36:41'),
(4, 'P00004', 'Angelina Mendoza Gomez', 'Outpatient', '2001-11-08', 'Female', '09068872398', 'Brgy. Dacanlao, Calaca City, Batangas', NULL, '2026-11-16 14:49:24'),
(5, 'P00005', 'Adrian Cueto Panganiban', 'Inpatient', '2003-02-23', 'Male', '09277580235', 'Brgy. Dila, Calaca City, Batangas', NULL, '2026-11-16 15:05:11'),
(7, 'P00006', 'Joana  Mae Manalo Lopez', 'Emergency', '2006-05-09', 'Female', '09677238961', 'Poblacion 5, Calaca City, Batangas', NULL, '2026-11-16 16:17:53'),
(8, 'P00007', 'Shane Sandoval Mariano', 'Inpatient', '1999-12-13', NULL, NULL, 'Brgy. Matipok, Calaca City, Batangas', NULL, '2026-11-16 16:20:00'),
(9, 'P00008', 'Daniela Santos Vidal', 'Inpatient', '2001-04-30', 'Female', '09058903547', 'Brgy. Pantay, Calaca City, Batangas', NULL, '2026-11-16 16:23:34'),
(10, 'P00009', 'maria heleyna cassanova manalo', 'Inpatient', '1997-04-18', 'Female', '09135231645', 'quezon city', NULL, '2026-11-16 16:48:56'),
(11, 'P00010', 'kelsey villalobos', 'Emergency', '1988-08-16', 'Female', '09316454646', 'balayan', NULL, '2026-11-16 16:50:54'),
(12, 'P00011', 'Rona Mae Torres Villanueva', 'Outpatient', '1997-08-16', 'Female', '09265587436', 'Poblacion 1, Calaca City, Batangas', NULL, '2026-11-16 16:52:07'),
(13, 'P00012', 'kathyrin mendoza', 'Inpatient', '1982-11-21', 'Female', '09463451243', 'calaca city', NULL, '2026-11-16 16:52:33'),
(14, 'P00013', 'princess ibay', 'Emergency', '1980-11-16', 'Female', '09136954236', 'camastilisan', NULL, '2026-11-16 16:55:34'),
(15, 'P00014', 'cristopher juan', 'Inpatient', '1989-11-16', 'Male', '89163595846', 'balayan', NULL, '2026-11-16 17:00:05'),
(16, 'P00015', 'gian delaluna', 'Inpatient', '1993-11-16', 'Male', '09431263645', 'calaca city', NULL, '2026-11-16 17:03:23'),
(17, 'P00017', 'Maria Fernandez', 'Inpatient', NULL, 'Female', '09365876555', 'Dacanlao, City of calaca', NULL, '2026-11-18 13:51:10'),
(18, 'P00018', 'Angela Mae Cruz', 'Outpatient', '2026-11-18', 'Female', '09126465643', 'Puting Bato East', NULL, '2026-11-18 14:11:00'),
(19, 'P00019', 'Jeremiah Santos De Castro', 'Outpatient', '2002-11-18', 'Male', '09254786534', 'Brgy. Timbain, Calaca City, Batangas', NULL, '2026-11-18 14:13:08'),
(20, 'P00020', 'Mark Anthony Reyes', 'Outpatient', '1967-11-14', 'Male', '09463256464', 'Puting Kahoy', NULL, '2026-11-18 14:14:16'),
(21, 'P00021', 'Darwin Velasquez Miranda', 'Outpatient', '2003-09-21', 'Male', '09267546981', 'Brgy. Caloocan, Balayan, Batangas', NULL, '2026-11-18 14:15:14'),
(22, 'P00022', 'Sofia Martinez', 'Outpatient', '2000-02-14', 'Female', '09464643236', 'Nasugbo', NULL, '2026-11-18 14:16:08'),
(23, 'P00023', 'John Michael Santos', 'Outpatient', '2005-08-18', 'Male', '09464326464', 'Puting Bato East', NULL, '2026-11-18 14:17:24'),
(24, 'P00024', 'Andrea Rain Cantos Duapa', 'Outpatient', '1998-11-06', 'Female', '09057612398', 'Poblacion 2, Calaca City, Batangas', NULL, '2026-11-18 14:18:29'),
(25, 'P00025', 'Nicole Andrea Flores', 'Outpatient', '1999-10-18', 'Female', '09364256245', 'Camastilisan, Calaca City', NULL, '2026-11-18 14:18:48'),
(26, 'P00026', 'Ma. Elaine Maranan Ramos', 'Outpatient', '1999-03-26', NULL, '09776529687', 'Brgy. Salong, Calaca City, Batangas', NULL, '2026-11-18 14:21:08'),
(27, 'P00027', 'Sean Mathew Valdez', 'Outpatient', '1988-09-07', 'Male', '09464223564', 'Balayan', NULL, '2026-11-18 14:21:51'),
(28, 'P00028', 'Kyle Sebastian Torres', 'Outpatient', '2005-01-06', 'Male', '09277098451', 'Brgy. Lanatan, Balayan, Batangas', NULL, '2026-11-18 14:23:02'),
(29, 'P00029', 'Allysa Kate Manalo', 'Outpatient', '2003-05-28', 'Female', '09465315446', 'Puting Bato East', NULL, '2026-11-18 14:23:48'),
(30, 'P00030', 'Joshua Makiling Flores', 'Outpatient', '2008-09-07', 'Male', '09342217694', 'Brgy. Matipok, Calaca City, Batangas', NULL, '2026-11-18 14:25:01'),
(31, 'P00031', 'Christian Herrera', 'Outpatient', '1991-02-18', 'Male', '09462358664', 'Puting Bato West', NULL, '2026-11-18 14:25:33'),
(32, 'P00032', 'Lori Mae Quintos Salvador', 'Outpatient', '1998-05-23', 'Female', '09972258756', 'Brgy. Lumbang Calzada, Calaca City, Batangas', NULL, '2026-11-18 14:26:57'),
(33, 'P00033', 'Chloe Soriano', 'Outpatient', '1993-11-05', 'Female', '09462359875', 'Balayan', NULL, '2026-11-18 14:27:35'),
(34, 'P00034', 'Samantha A. De Castro', 'Outpatient', '2006-08-23', 'Female', '09171186423', 'Brgy. Pantay, Calaca City, Batangas', NULL, '2026-11-18 14:31:23'),
(35, 'P00035', 'Denise Soriano Padilla', 'Outpatient', '1998-08-14', 'Female', '09264538954', 'Talisay, Calaca City', NULL, '2026-11-18 14:32:41'),
(36, 'P00036', 'Dianne T. Alferez', 'Outpatient', '2002-11-01', 'Female', '09559670813', 'Brgy. Pantay, Calaca City, Batangas', NULL, '2026-11-18 14:32:57'),
(37, 'P00037', 'Vincent Lee Cabrera', 'Outpatient', '2004-04-13', 'Male', '09164352956', 'Salong, Calaca City', NULL, '2026-11-18 14:36:51'),
(38, 'P00038', 'Katrina H. alcantara', 'Outpatient', '2002-10-07', NULL, '09245865742', 'Salong, Calaca City', NULL, '2026-11-18 14:38:55'),
(39, 'P00039', 'Alexis A. Sy', 'Outpatient', '1993-11-08', 'Male', '09854268534', 'Madalunot, Calaca City', NULL, '2026-11-18 14:41:37'),
(40, 'P00040', 'Aira Mae B. Apolonia', 'Outpatient', '2005-01-11', 'Female', '09452367512', 'Brgy. Sampaga, Balayan, Batangas', NULL, '2026-11-18 14:43:08'),
(41, 'P00041', 'Chloey V. Jala', 'Outpatient', '2006-11-05', 'Female', '09837489389', 'Brgy. Cahil, Calaca City, Batangas', NULL, '2026-11-18 14:43:29'),
(42, 'P00042', 'Maureen L. Legaspi', 'Outpatient', '1987-11-25', 'Female', '09425362984', 'Salong, Calaca City', NULL, '2026-11-18 14:44:16'),
(43, 'P00043', 'Mary Jane Avendaño Casanova', 'Outpatient', '2001-11-22', 'Female', '09553357854', 'Brgy. Durungao, Balayan, Batangas', NULL, '2026-11-18 14:47:19'),
(44, 'P00044', 'Jerome K. Abad', 'Outpatient', '1986-06-07', 'Male', '09154239656', 'Puting Bato West, Calaca City', NULL, '2026-11-18 14:47:35'),
(45, 'P00045', 'Jellly Ann Mercado Piliin', 'Outpatient', '2009-04-15', 'Female', '09355872061', 'Brgy. Tanggoy, Balayan, Batangas', NULL, '2026-11-18 14:49:15'),
(46, 'P00046', 'Bea Cabrera Herrera', 'Outpatient', '2001-03-09', 'Female', '09675534198', 'Caybunga, Balayan, Batangas', NULL, '2026-11-18 14:50:48'),
(47, 'P00047', 'Rafael T. Abad', 'Outpatient', '2000-01-13', 'Male', '09468895126', 'Pooc, Balayan, Batangas', NULL, '2026-11-18 14:52:54'),
(48, 'P00048', 'Patrick J. Salazar', 'Outpatient', '1994-04-18', 'Male', '09452882663', 'Lumbang Calzada, Calaca City', NULL, '2026-11-18 14:53:28'),
(49, 'P00049', 'Zander M. Ford', 'Outpatient', '2004-02-17', 'Male', '09656909869', 'Camastilisan, Calaca City, Batangas', NULL, '2026-11-18 14:55:09'),
(50, 'P00050', 'Hannah A. Fahardo', 'Outpatient', '2007-04-10', 'Female', '09643586855', 'Dacanlao, Calaca City', NULL, '2026-11-18 14:55:17'),
(51, 'P00051', 'Calix G. Hernandez', 'Outpatient', '2006-06-16', 'Male', '09973145966', 'Niyugan, Calaca City, Batangas', NULL, '2026-11-18 14:56:36'),
(52, 'P00052', 'Rhea S. Macapagal', 'Outpatient', '2002-11-09', 'Female', '09635882433', 'Lumbang Na Bata, Calaca City', NULL, '2026-11-18 14:57:01'),
(53, 'P00053', 'Katrina Chua Cabrera', 'Outpatient', '2007-09-18', 'Female', '09772984531', 'Baclaran, Balayan, Batangas', NULL, '2026-11-18 14:59:14'),
(54, 'P00054', 'Sophia N. Robles', 'Outpatient', '2003-12-24', 'Female', '09553905871', 'Baclas, Calaca City, Batangas', NULL, '2026-11-18 15:01:50'),
(55, 'P00055', 'Daniela Marasigan Buendia', 'Outpatient', '2003-10-19', 'Female', '09059785513', 'Madalunot, Calaca City, Batangas', NULL, '2026-11-18 15:04:24'),
(56, 'P00056', 'Francis B. Ortega', 'Outpatient', '1994-10-14', 'Male', '09154538764', 'Madalunot, Calaca City', NULL, '2026-11-18 15:08:29'),
(57, 'P00057', 'Mary Ann Pacleb Mercado', 'Outpatient', '2005-12-01', 'Female', '09677982541', 'Sinisian, Calaca City, Batangas', NULL, '2026-11-18 15:10:04'),
(58, 'P00058', 'Gehlee Evangelista', 'Outpatient', '2000-12-22', 'Male', '09652356578', 'Calauang, Calaca City', NULL, '2026-11-18 15:10:19'),
(59, 'P00059', 'Fiona Jumarang De Luna', 'Outpatient', '2005-02-18', 'Female', '09086152853', 'Barangay 11, Balayan, Batangas', NULL, '2026-11-18 15:13:11'),
(60, 'P00060', 'Jasper Jean Y. Mariano', 'Outpatient', '2004-01-18', 'Female', '09051179945', 'Brgy. Dacanlao, Calaca City, Batangas', NULL, '2026-11-18 15:14:49'),
(61, 'P00061', 'Keifer H. De Villa', 'Outpatient', '2009-05-30', 'Male', '09239671392', 'Barangay 1, Balayan, Batangas', NULL, '2026-11-18 15:16:00'),
(62, 'P00062', 'Jayvee A. Hernandez', 'Outpatient', '2004-01-24', 'Male', '09105569281', 'Balimbing, Calaca City, Batangas', NULL, '2026-11-18 15:18:03'),
(63, 'P00063', 'Angel Diane Casanova Atajar', 'Outpatient', '2003-07-29', 'Female', '09914428719', 'Matipok, Calaca City, Batangas', NULL, '2026-11-18 15:19:08'),
(64, 'P00064', 'James R. Matalog', 'Outpatient', '1998-10-09', 'Male', '09977162294', 'Cahil, Calaca City, Batangas', NULL, '2026-11-18 15:25:25'),
(65, 'P00065', 'Alexis Gomez Digno', 'Outpatient', '2008-07-13', 'Male', '09932076983', 'Munting Coral, Calaca City, Batangas', NULL, '2026-11-18 15:28:13'),
(66, 'P00066', 'Ethan C. Ontangco', 'Outpatient', '2000-09-21', 'Male', '09458763542', 'Lumbang Na Bata, Calaca City', NULL, '2026-11-18 15:34:26'),
(67, 'P00067', 'Paulo H. Marasigan', 'Outpatient', '1996-12-18', 'Male', '09568754965', 'Munting Coral, Calaca City', NULL, '2026-11-18 15:36:00'),
(68, 'P00068', 'Edward Apolonia Dimaano', 'Outpatient', '1998-08-28', 'Male', '09357691024', 'Dalig, Balayan, Batangas', NULL, '2026-11-18 15:36:12'),
(69, 'P00069', 'Ysabel G. Ortega', 'Outpatient', '2003-03-30', 'Female', '09642356485', 'Quisumbing, Calaca City', NULL, '2026-11-18 15:37:23'),
(70, 'P00070', 'Marwin I. Bantilan', 'Outpatient', '2009-04-18', NULL, '09658139271', 'Loma, Calaca City, Batangas', NULL, '2026-11-18 15:37:41'),
(71, 'P00071', 'Camille J. Estrada', 'Outpatient', '1988-04-07', 'Female', '09452865749', 'Timbain, Calaca City', NULL, '2026-11-18 15:39:03'),
(72, 'P00072', 'Erika J. Bautista', 'Outpatient', '2000-09-10', 'Female', '09558172538', 'Coral ni Bacal, Calaca City, Batangas', NULL, '2026-11-18 15:40:07'),
(73, 'P00073', 'Anthony Guererro Lapid', 'Outpatient', '1987-12-20', 'Male', '09239716498', 'Coral ni Lopez, Calaca City, Batangas', NULL, '2026-11-18 15:41:38'),
(74, 'P00074', 'kenneth D. Miranda', 'Outpatient', '1997-12-24', 'Male', '09345785672', 'Munting Coral, Calaca City', NULL, '2026-11-18 15:42:42'),
(75, 'P00075', 'Keanna G. Garcia', 'Outpatient', '2001-03-28', 'Female', '09078162294', 'Sambat, Balayan, Batangas', NULL, '2026-11-18 15:42:42'),
(76, 'P00076', 'Byron Sevilla Ramirez', 'Outpatient', '2011-04-27', 'Male', NULL, 'Langgangan, Balayan, Batangas', NULL, '2026-11-18 15:44:00'),
(77, 'P00077', 'Alexandra F. Diokno', 'Outpatient', '1981-01-09', 'Female', '09168752375', 'Sinisian, Calaca City', NULL, '2026-11-18 15:44:57'),
(78, 'P00078', 'Benedict Garcia Jimenez', 'Outpatient', '2003-02-14', 'Male', '09452871956', 'Carenahan, Balayan, Batangas', NULL, '2026-11-18 15:45:33'),
(79, 'P00079', 'Yuan Morada Dastas', 'Outpatient', '2009-05-28', 'Male', '09218735620', 'Talisay, Calaca City, Batangas', NULL, '2026-11-18 15:47:10'),
(80, 'P00080', 'Ethan Cuenca Fernandez', 'Outpatient', '2007-01-29', 'Male', '09817264993', 'Tamayo, Calaca City, Batangas', NULL, '2026-11-18 15:48:19'),
(81, 'P00081', 'Briana R. Desepeda', 'Outpatient', '1999-09-09', 'Female', '09467928136', 'Quisumbing, Calaca City, Batangas', NULL, '2026-11-18 15:49:29'),
(82, 'P00082', 'Claire Ann E. Maligaya', 'Outpatient', '2003-12-19', NULL, '09661927384', 'Caluangan, Calaca City, Batangas', NULL, '2026-11-18 15:51:09'),
(83, 'P00083', 'Rhianna Sanggalang Quierra', 'Outpatient', '2000-11-18', 'Female', '09946178801', 'Barangay 2, Balayan, Batangas', NULL, '2026-11-18 15:54:26'),
(84, 'P00084', 'Roman H. Casanova', 'Outpatient', '2010-03-08', 'Male', '09178849182', 'Gumamela, Balayan, Batangas', NULL, '2026-11-18 15:56:01'),
(85, 'P00085', 'Hanna Mendoza De Villa', 'Outpatient', '2001-03-26', NULL, '09279168274', 'Brgy. Camastilisan, Calaca City, Batangas', NULL, '2026-11-18 16:08:25'),
(86, 'P00086', 'Toni Flores Malabanan', 'Outpatient', '2015-06-30', 'Female', '09652938104', 'Poblacion 6, Calaca City, Batangas', NULL, '2026-11-18 16:19:27'),
(87, 'P00087', 'Vincent K. Pascual', 'Outpatient', '1976-09-25', 'Male', '09563745572', 'Coral Ni Bakal, Calaca City', NULL, '2026-11-18 16:19:56'),
(88, 'P00088', 'Ericka D. Selim', 'Outpatient', '1985-07-14', 'Female', '09658742346', 'Coral Ni Lopez, Calaca City', NULL, '2026-11-18 16:21:58'),
(89, 'P00089', 'Carmina P. Salunguit', 'Outpatient', '1998-12-03', 'Female', '09367139281', 'Makina, Calaca City, Batangas', NULL, '2026-11-18 16:22:49'),
(90, 'P00090', 'Daniella G. Geronimo', 'Outpatient', '1986-08-14', 'Female', '09635842351', 'Cahil, Calaca City', NULL, '2026-11-18 16:23:51'),
(91, 'P00091', 'Realyn Mercado Caisip', 'Outpatient', '1999-07-31', 'Female', '09550221843', 'Puting Bato East, Calaca City, Batangas', NULL, '2026-11-18 16:27:55'),
(92, 'P00092', 'Lara R. Marasigan', 'Outpatient', '2000-01-27', 'Female', '09926187253', 'Lumbang na Matanda, Calaca City, Batangas', NULL, '2026-11-18 16:35:50'),
(93, 'P00093', 'Joy-joy Miranda Morales', 'Outpatient', '2001-02-17', 'Female', '09217369182', 'Taklang Anak, Calaca City, Batangas', NULL, '2026-11-18 16:40:11'),
(94, 'P00094', 'Duqe Marasigan Santos', 'Outpatient', NULL, 'Male', '09163258468', 'Madalunot, Calaca City', NULL, '2026-11-18 16:41:56'),
(95, 'P00095', 'Harvy O. Panganiban', 'Outpatient', '1998-08-27', 'Male', '09716258493', 'Bagong Tubig, Calaca City, Batangas', NULL, '2026-11-18 16:43:08'),
(96, 'P00096', 'Binene Divereno Santiago', 'Outpatient', '1974-08-14', 'Female', '09468854721', 'Coral Ni Lopez, Calaca City', NULL, '2026-11-18 16:43:29'),
(97, 'P00097', 'Amelia Gomez De Padua', 'Outpatient', '2000-04-27', 'Female', '09812638473', 'Poblacion 4, Calaca City, Batangas', NULL, '2026-11-18 16:44:44'),
(98, 'P00098', 'Ma. Sheryl Pinedo Guevarra', 'Outpatient', '1986-07-04', 'Female', '09857642153', 'Dila, Calaca City', NULL, '2026-11-18 16:45:28'),
(99, 'P00099', 'kharyl Heniva Puerto', 'Outpatient', '1976-07-06', 'Female', '09652365235', 'Bisaya, Calaca City', NULL, '2026-11-18 16:47:13'),
(100, 'P00100', 'Robbie N. Calingasan', 'Outpatient', '2008-12-05', 'Male', '09972116532', 'Magabe, Balayan, Batangas', NULL, '2026-11-18 16:48:57'),
(101, 'P00101', 'Angelo Trillianes Sabado', 'Outpatient', '1980-02-13', 'Male', '09653665365', 'Cahil, Calaca City', NULL, '2026-11-18 16:51:15'),
(102, 'P00102', 'Angelica Cruz Mendoza', 'Outpatient', '2001-06-27', 'Female', '09647328736', 'Bambang, Calaca City, Batangas', NULL, '2026-11-18 16:51:56'),
(103, 'P00103', 'Francis Elmo Magalona', 'Outpatient', '1992-11-18', 'Male', '09567841265', 'Lumbang Na Matanda, Calaca City', NULL, '2026-11-18 16:52:38'),
(104, 'P00104', 'Nicole Ramirez Soriano', 'Outpatient', '2017-06-18', 'Female', '09663762387', 'Patugo, Balayan, Batangas', NULL, '2026-11-18 16:54:08'),
(105, 'P00105', 'Beatrice Dueñas Ong', 'Outpatient', '1994-08-26', 'Male', '09657824536', 'Matipok, Calaca City', NULL, '2026-11-18 16:54:25'),
(106, 'P00106', 'miguelito Beto Diverra', 'Outpatient', '2000-12-02', 'Male', '09695223615', 'Munting Coral, Calaca City', NULL, '2026-11-18 16:56:17'),
(107, 'P00107', 'Jasmin Flores Digno', 'Outpatient', '2007-11-03', 'Female', '09813652636', 'San Piro, Balayan, Batangas', NULL, '2026-11-18 17:07:51'),
(108, 'P00108', 'Trisia marie P. Hernandez', 'Outpatient', '1995-02-13', 'Female', '09652435523', 'Dila, Calaca City', NULL, '2026-11-18 17:09:11'),
(109, 'P00109', 'Blaire U. Velasquez', 'Outpatient', '2009-03-18', 'Female', '09186362564', 'Brgy. Madalunot, Calaca City, Batangas', NULL, '2026-11-18 17:09:17'),
(110, 'P00110', 'Jericho Castro Secreto', 'Outpatient', '2000-11-20', 'Male', '09165376387', 'Brgy. Bisaya, Calaca City, Batangas', NULL, '2026-11-18 17:11:24'),
(111, 'P00111', 'Mary Grace malagar Gerrero', 'Outpatient', '1961-11-12', 'Female', '09455232654', 'Loma, Calaca City', NULL, '2026-11-18 17:12:34'),
(112, 'P00112', 'Claire Ber Santos Benitez', 'Outpatient', '1989-07-19', 'Female', '09652344153', 'Matipok, Calaca City', NULL, '2026-11-18 17:14:08'),
(113, 'P00113', 'Kevin Dela Cuesta Baldrias', 'Outpatient', '1999-07-23', 'Male', '09271636827', 'Dao, Balayan, Batangas', NULL, '2026-11-18 17:14:33'),
(114, 'P00114', 'Paloma Bueño Buena', 'Outpatient', '1995-11-27', 'Female', '09865748624', 'Pantay, Calaca City', NULL, '2026-11-18 17:15:37'),
(115, 'P00115', 'Michael Castillo Rivera', 'Outpatient', '2003-07-19', 'Male', '09172837627', 'Poblacion 2, Calaca City, Batangas', NULL, '2026-11-18 17:15:44'),
(116, 'P00116', 'Louise Navarro Domingo', 'Outpatient', '2001-12-09', 'Female', '09174385097', 'Balimbing, Calaca City, Batangas', NULL, '2026-11-18 17:16:55'),
(117, 'P00117', 'Genie Lee Sefano', 'Outpatient', '1976-11-14', 'Female', '09718645289', 'Sinisian, Calaca City', NULL, '2026-11-18 17:17:20'),
(118, 'P00118', 'Kings Greek Weñas', 'Outpatient', '1987-03-15', 'Male', '09852248674', 'Niyugan, Calaca City', NULL, '2026-11-18 17:18:58'),
(119, 'P00119', 'Ashley Jane Villanueva Calanog', 'Outpatient', '1995-11-02', 'Female', '09271287615', 'Cahil, Calaca, Batangas', NULL, '2026-11-18 17:20:07'),
(120, 'P00120', 'Mitzi Quezon Dela Cruz', 'Outpatient', '2003-07-12', 'Female', '09635828464', 'San Rafael, Calaca City', NULL, '2026-11-18 17:20:48'),
(121, 'P00121', 'Rose Ann D. Fernandez', 'Outpatient', '2003-03-19', 'Female', '09136984792', 'Calantas, Calaca City, Batangas', NULL, '2026-11-18 17:21:30'),
(122, 'P00122', 'Margarette Mendoza Deverro', 'Outpatient', '1995-11-19', 'Female', '09651234645', 'Puting Bato West, Calaca City', NULL, '2026-11-18 17:21:42'),
(123, 'P00123', 'Andrea Kin Deverra Santos', 'Outpatient', '1988-06-12', 'Female', '09625346645', 'Puting Bato East, Calaca City', NULL, '2026-11-18 17:22:49'),
(124, 'P00124', 'Patrick Jumarang Garcia', 'Outpatient', '2006-03-18', 'Male', '09183727384', 'Brgy. Dila, Calaca City, Batangas', NULL, '2026-11-18 17:23:20'),
(125, 'P00125', 'Wendy Jane Fano Villalobos', 'Outpatient', '1987-08-26', 'Female', '09652364543', 'Makina, Calaca City', NULL, '2026-11-18 17:24:04'),
(126, 'P00126', 'Daniel Ford Estrada', 'Outpatient', '2000-12-31', 'Male', '09387469287', 'Brgy. Caloocan, Balayan, Batangas', NULL, '2026-11-18 17:24:15'),
(127, 'P00127', 'Sarah fen Fuentos Hernandez', 'Outpatient', '2005-11-20', 'Female', '09645431646', 'Makina, Calaca City', NULL, '2026-11-18 17:25:37'),
(128, 'P00128', 'Anthony James Vergara Manalo', 'Outpatient', '2009-10-29', 'Male', '09467237298', 'Calan, Balayan, Batangas', NULL, '2026-11-18 17:25:52'),
(129, 'P00129', 'Juan Dorin Gomez', 'Outpatient', '1997-11-18', 'Male', '09746827648', 'Barangay 9, Balayan, Batangas', NULL, '2026-11-18 17:27:38'),
(130, 'P00130', 'Taña Elño Mitsubishi', 'Outpatient', '2007-02-23', 'Female', '09654236585', 'Makina, Calaca City', NULL, '2026-11-18 17:27:48'),
(131, 'P00131', 'Belinda Reyes Ilagan', 'Outpatient', '2009-02-17', 'Female', '09753649284', 'Calzada, Balayan, Batangas', NULL, '2026-11-18 17:28:36'),
(132, 'P00132', 'Krisquen Lapid Cruz', 'Outpatient', '1983-03-12', 'Female', '09567421186', 'Sinisian, Calaca City', NULL, '2026-11-18 17:29:07'),
(133, 'P00133', 'Noreen Marcos Cariño', 'Outpatient', '2001-08-23', 'Female', '09873872563', 'Taklang Anak, Calaca City, Batangas', NULL, '2026-11-18 17:29:50'),
(134, 'P00134', 'Joseph Maranan Borromeo', 'Outpatient', '2006-01-30', 'Male', '09962874868', 'Coral ni Lopez, Calaca City, Batangas', NULL, '2026-11-18 17:31:05'),
(135, 'P00135', 'Weather Van Brilliantes', 'Outpatient', '1987-11-12', 'Female', '09615426231', 'Lumbang Na Bata, Calaca City', NULL, '2026-11-18 17:31:15'),
(136, 'P00136', 'Carlo Buendia Morales', 'Outpatient', '1999-10-04', 'Male', '09639827496', 'Munting Coral, Calaca City, Batangas', NULL, '2026-11-18 17:32:30'),
(137, 'P00137', 'Christine Aquino Gonzales', 'Outpatient', '2000-11-18', 'Female', '09264847563', 'Brgy. Timbain, Calaca City, Batangas', NULL, '2026-11-18 17:33:27'),
(138, 'P00138', 'Allysa Jane P. Venzon', 'Outpatient', '2004-03-17', 'Female', '09173648768', 'Brgy. Salong, Calaca City, Batangas', NULL, '2026-11-18 17:35:16'),
(139, 'P00139', 'John Vic Maligaya Villaranda', 'Outpatient', '2001-06-28', 'Male', '09287587628', 'Gimalas, Balayan, Batangas', NULL, '2026-11-18 17:37:04'),
(140, 'P00140', 'Princess Jane Panganiban Marasigan', 'Outpatient', '1988-04-13', 'Female', '09642434613', 'Pantay, Calaca City', NULL, '2026-11-18 17:40:29'),
(141, 'P00141', 'Jun-jun Soriano Italia', 'Outpatient', '1984-03-14', 'Male', '09354528665', 'Niyugan, Calaca City', NULL, '2026-11-18 17:42:35'),
(142, 'P00142', 'Adrian Torres Lopez', 'Outpatient', '2000-01-28', 'Male', '09716374687', 'Brgy. Matipok, Calaca City, Batangas', NULL, '2026-11-18 17:42:55'),
(143, 'P00143', 'Xandra Collino Dueñas', 'Outpatient', '1992-03-05', 'Female', '09587298865', 'Matipok, Calaca City', NULL, '2026-11-18 17:44:25'),
(144, 'P00144', 'Angela Santos Mendoza', 'Outpatient', '1976-08-06', 'Female', NULL, 'Cahil, City of Calaca', NULL, '2026-11-18 17:45:39'),
(145, 'P00145', 'Vincent Feno Dela cruz', 'Outpatient', '1997-11-20', 'Male', '09637585745', 'Niyugan, Calaca City', NULL, '2026-11-18 17:46:00'),
(146, 'P00146', 'Cristina Santos Faa', 'Outpatient', '1978-12-23', 'Female', '09154458764', 'Matipok, Calaca City', NULL, '2026-11-18 17:47:06'),
(147, 'P00147', 'Cherryfeh Andal Maneja', 'Outpatient', '2003-11-12', 'Female', '09154365324', 'Talisay, Calaca City', NULL, '2026-11-18 17:49:05'),
(148, 'P00148', 'Lovely Bituin Craso', 'Outpatient', '2000-06-07', 'Female', '09635245421', 'Puting Bato West, Calaca City', NULL, '2026-11-18 17:51:32'),
(149, 'P00149', 'Nhel Jean Lopez Maneja', 'Outpatient', '2005-07-17', 'Female', '09654231542', 'Matipok, Calaca City', NULL, '2026-11-18 17:53:01'),
(150, 'P00150', 'Marian Pamintuan Responde', 'Outpatient', '2001-08-27', 'Female', '09279168367', 'Brgy. Quisumbing, Balayan, Batangas', NULL, '2026-11-18 17:54:40'),
(151, 'P00151', 'Jenny Maneja Sano', 'Outpatient', '1985-10-27', 'Female', '09163453561', 'Puting Bato West, Calaca City', NULL, '2026-11-18 17:55:24'),
(152, 'P00152', 'Joanna H. Morada', 'Outpatient', '2007-05-29', 'Female', '09572528746', 'Brgy. Pantay, Calaca City, Batangas', NULL, '2026-11-18 17:55:44'),
(153, 'P00153', 'Luna Fueños Morato', 'Outpatient', '1980-09-10', 'Female', '09465213434', 'Madalunot, Calaca City', NULL, '2026-11-18 18:00:34'),
(154, 'P00154', 'Ryan Mangubat Bango', 'Outpatient', '1991-01-29', 'Male', '09613531313', 'Madalunot, Calaca City', NULL, '2026-11-18 18:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `rules`
--

CREATE TABLE `rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `keywords` text NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rules`
--

INSERT INTO `rules` (`id`, `rule_name`, `category_id`, `keywords`, `priority`, `is_active`, `created_at`) VALUES
(1, 'CBC Test Rule', 1, 'cbc, complete blood count, hemogram', 10, 1, '2026-11-16 11:36:41'),
(2, 'Urinalysis Rule', 1, 'urinalysis, urinalysis test, urine test', 9, 1, '2026-11-16 11:36:41'),
(3, 'Blood Chemistry', 1, 'fbs, rbs, lipid profile, creatinine, bun, cholesterol', 8, 1, '2026-11-16 11:36:41'),
(4, 'X-Ray Rule', 2, 'x-ray, xray, radiograph, chest xray', 10, 1, '2026-11-16 11:36:41'),
(5, 'Ultrasound Rule', 2, 'ultrasound, utz, sonogram, abdominal ultrasound', 9, 1, '2026-11-16 11:36:41'),
(6, 'CT Scan Rule', 2, 'ct scan, ct-scan, computed tomography', 8, 1, '2026-11-16 11:36:41'),
(7, 'Consultation Rule', 3, 'consultation, consult, check-up, checkup, follow-up', 10, 1, '2026-11-16 11:36:41'),
(8, 'Pharmacy Rule', 4, 'medicine, medication, drug, tablet, capsule', 7, 1, '2026-11-16 11:36:41'),
(9, 'Room Charge Rule', 5, 'room, ward, private room, icu, accommodation', 6, 1, '2026-11-16 11:36:41'),
(10, 'Minor Surgery Rule', 6, 'surgery, surgical, operation, excision, biopsy', 9, 1, '2026-11-16 11:36:41'),
(11, 'Emergency Consultation Rule', 8, 'emergency consultation, er consult, emergency assessment, triage assessment, initial medical assessment', 10, 1, '2026-11-16 21:14:57'),
(12, 'Emergency Procedure Rule', 9, 'wound cleaning, wound dressing, suturing, stitches, splint application, splinting', 9, 1, '2026-11-16 21:14:57'),
(13, 'Respiratory Emergency Rule', 10, 'oxygen therapy, nebulization, nebulizer, respiratory assessment', 9, 1, '2026-11-16 21:14:57'),
(14, 'Cardiac Emergency Rule', 11, 'ecg, ekg, electrocardiogram, cardiac monitoring, cpr, cardiopulmonary resuscitation', 10, 1, '2026-11-16 21:14:57'),
(15, 'Emergency Medication Rule', 12, 'medication administration, iv insertion, iv fluid administration, intravenous', 8, 1, '2026-11-16 21:14:57'),
(16, 'Emergency Diagnostic Rule', 13, 'blood sugar testing, pulse oximetry, vital signs monitoring', 8, 1, '2026-11-16 21:14:57'),
(17, 'Emergency Nursing Rule', 14, 'emergency nursing care, patient observation, patient stabilization', 7, 1, '2026-11-16 21:14:57'),
(18, 'Pediatric Emergency Rule', 15, 'pediatric emergency, pediatric assessment, pediatric treatment', 9, 1, '2026-11-16 21:14:57'),
(19, 'OB-GYN Emergency Rule', 16, 'pregnancy, obstetric, gynecological, ob-gyn emergency', 9, 1, '2026-11-16 21:14:57'),
(20, 'Emergency Transfer Rule', 17, 'patient transfer, ambulance reception, referral coordination', 7, 1, '2026-11-16 21:14:57'),
(21, 'Surgical Services Rule', 18, 'minor surgery, major surgery, operating room, or fee, anesthesia, recovery room, surgical equipment, surgical supplies, post-surgical', 10, 1, '2026-11-16 21:14:57'),
(22, 'Professional Fee Rule', 19, 'surgeon fee, anesthesiologist fee, assistant surgeon, attending physician, specialist fee, pediatrician fee, ob-gyn fee, cardiologist fee', 10, 1, '2026-11-16 21:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `semantic_dictionary`
--

CREATE TABLE `semantic_dictionary` (
  `id` int(11) NOT NULL,
  `technical_term` varchar(150) NOT NULL,
  `patient_explanation` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `synonym_terms` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semantic_dictionary`
--

INSERT INTO `semantic_dictionary` (`id`, `technical_term`, `patient_explanation`, `category`, `synonym_terms`, `created_at`) VALUES
(1, 'CBC', 'Blood test that checks red blood cells, white blood cells, and platelets.', 'Laboratory Services', 'complete blood count, hemogram', '2026-11-16 11:36:41'),
(2, 'FBS', 'Blood test that measures blood sugar after fasting.', 'Laboratory Services', 'fasting blood sugar, blood glucose', '2026-11-16 11:36:41'),
(3, 'Urinalysis', 'Urine test used to check for signs of infection and other health concerns.', 'Laboratory Services', 'urine test, UA', '2026-11-16 11:36:41'),
(4, 'X-Ray', 'Creates pictures of bones and certain body structures using X-rays.', 'Diagnostic Services', 'radiograph, xray', '2026-11-16 11:36:41'),
(5, 'Ultrasound', 'Uses sound waves to show pictures of organs and tissues inside the body.', 'Diagnostic Services', 'UTZ, sonogram', '2026-11-16 11:36:41'),
(6, 'BP Monitoring', 'Blood Pressure Monitoring — regular checks of your blood pressure.', 'Consultation Services', 'blood pressure, BP', '2026-11-16 11:36:41'),
(7, 'IV Fluids', 'Intravenous Fluids — liquids given through a needle to keep you hydrated.', 'Pharmacy', 'IV, drip, intravenous', '2026-11-16 11:36:41'),
(8, 'ECG', 'Electrocardiogram — records the electrical activity of your heart.', 'Diagnostic Services', 'EKG, electrocardiogram', '2026-11-16 11:36:41'),
(9, 'Cardiology Consultation', 'A consultation focused on heart and blood vessel health.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(10, 'Dental Consultation', 'A dental examination to assess teeth and oral health.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(11, 'Dermatology Consultation', 'A consultation for skin, hair, and nail concerns.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(12, 'Follow-up Consultation', 'A return appointment to review your condition or treatment progress.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(13, 'General Consultation', 'A doctor checks your health and discusses your medical concerns.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(14, 'Internal Medicine Consultation', 'A consultation for diagnosing and managing health conditions in adults.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(15, 'OB-GYN Consultation', 'A consultation focused on women\'s reproductive health.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(16, 'Orthopedic Consultation', 'A consultation for bone, joint, and muscle concerns.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(17, 'Pediatric Consultation', 'A medical appointment focused on children\'s health.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(18, 'Specialist Consultation', 'An appointment with a doctor who specializes in a particular medical field.', 'Consultation Services', NULL, '2026-11-16 14:30:36'),
(19, '2D Echo', 'Uses ultrasound to show the structure and movement of the heart.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(20, 'Bone Density Test', 'Measures bone strength and helps assess bone health.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(21, 'Chest X-Ray', 'Creates an image of the chest, including the lungs and heart.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(22, 'CT Scan', 'Combines X-ray images to create detailed pictures of internal body structures.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(24, 'Mammography', 'Uses specialized X-ray imaging to examine breast tissue.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(25, 'MRI Scan', 'Uses magnets and radio waves to create detailed pictures inside the body.', 'Diagnostic Services', NULL, '2026-11-16 14:30:36'),
(26, 'Blood Chemistry', 'Blood tests that measure different substances in the blood.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(27, 'Blood Typing', 'Test that identifies a person\'s blood group and Rh factor.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(28, 'BUN', 'Blood test that helps evaluate kidney function.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(29, 'Creatinine', 'Blood test that helps assess kidney function.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(30, 'Dengue NS1', 'Blood test that helps detect an early dengue infection.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(31, 'Electrolytes', 'Blood test that measures important minerals such as sodium and potassium.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(32, 'ESR', 'Blood test that helps identify inflammation in the body.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(33, 'Fecalysis', 'Stool test used to check for digestive problems or intestinal parasites.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(34, 'HbA1c', 'Blood test that measures average blood sugar over approximately three months.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(35, 'Hemoglobin', 'Blood test that measures the oxygen-carrying protein in red blood cells.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(36, 'Lipid Profile', 'Blood test that measures cholesterol and other fats in the blood.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(37, 'Platelet Count', 'Blood test that measures the number of platelets in the blood.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(38, 'Pregnancy Test', 'Test that checks for the presence of a pregnancy hormone.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(39, 'SGOT / AST', 'Blood test that helps assess liver function.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(40, 'SGPT / ALT', 'Blood test that helps assess liver function.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(41, 'Uric Acid', 'Blood test that measures uric acid levels.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(42, 'WBC Count', 'Blood test that measures white blood cells.', 'Laboratory Services', NULL, '2026-11-16 14:30:36'),
(43, 'Alcohol', 'An antiseptic solution used for cleaning skin.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(44, 'Bandage', 'Material used to cover and support an injured area.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(45, 'Catheter', 'A medical tube used for draining or delivering fluids.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(46, 'Cotton', 'Medical material used for cleaning or applying solutions.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(47, 'Disposable Gloves', 'Protective gloves used during medical procedures.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(48, 'Gauze', 'A medical material used to cover or protect wounds.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(49, 'IV Set', 'Equipment used to deliver fluids or medication through a vein.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(50, 'Medical Tape', 'Adhesive tape used to secure dressings or medical devices.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(51, 'Surgical Mask', 'A protective mask used to help reduce the spread of germs.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(52, 'Syringe', 'A medical device used to measure and administer medication.', 'Medical Supplies', NULL, '2026-11-16 14:30:36'),
(53, 'Antibiotics', 'Medicines used to treat certain bacterial infections.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(54, 'Fever Medication', 'Medicines used to help reduce fever.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(55, 'Injectable Medication', 'Medication administered through an injection by authorized healthcare personnel.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(56, 'Intravenous Medication', 'Medication administered directly into a vein by healthcare personnel.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(57, 'Maintenance Medication', 'Medicines prescribed for ongoing health conditions.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(58, 'Nebulization Medication', 'Medication used with a nebulizer to help deliver medicine through breathing.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(59, 'Pain Relievers', 'Medicines used to relieve pain.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(60, 'Prescription Medication', 'Medicines prescribed by a healthcare professional.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(61, 'Vitamins and Supplements', 'Products used to provide additional vitamins or nutrients when appropriate.', 'Pharmacy / Medication', NULL, '2026-11-16 14:30:36'),
(62, 'Deluxe Room', 'Accommodation in a room with additional facilities or amenities.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(63, 'General Ward', 'Accommodation in a shared hospital room.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(64, 'ICU Room', 'Accommodation and monitoring in the Intensive Care Unit.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(65, 'Isolation Room', 'A room designed to help prevent the spread of certain infections.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(66, 'Private Room', 'Accommodation in a room intended for one patient.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(67, 'Room and Board', 'Hospital accommodation and basic room-related services.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(68, 'Semi-Private Room', 'Accommodation in a room shared with a limited number of patients.', 'Room Charges', NULL, '2026-11-16 14:30:36'),
(69, 'Occupational Therapy', 'Therapy that helps patients perform daily activities more independently.', 'Therapy Services', NULL, '2026-11-16 14:30:36'),
(70, 'Physical Therapy', 'Treatment that helps improve movement, strength, and physical function.', 'Therapy Services', NULL, '2026-11-16 14:30:36'),
(71, 'Rehabilitation Therapy', 'Treatment that supports recovery of physical abilities and daily functions.', 'Therapy Services', NULL, '2026-11-16 14:30:36'),
(72, 'Respiratory Therapy', 'Treatment that helps improve breathing and lung function.', 'Therapy Services', NULL, '2026-11-16 14:30:36'),
(73, 'Speech Therapy', 'Therapy that helps improve communication and swallowing abilities.', 'Therapy Services', NULL, '2026-11-16 14:30:36'),
(74, 'Triage', 'A quick check to decide how urgently you need care in the emergency room.', 'Emergency Assessment & Triage', 'triage assessment, ER triage', '2026-11-16 21:14:57'),
(77, 'CPR', 'Cardiopulmonary Resuscitation — an emergency procedure to restart the heart and breathing.', 'Cardiac Emergency Services', 'cardiopulmonary resuscitation', '2026-11-16 21:14:57'),
(78, 'Operating Room', 'The special, sterile room where a surgery is performed.', 'Surgical Services', 'OR, OR fee', '2026-11-16 21:14:57'),
(79, 'Anesthesia', 'Medicine given during surgery so you do not feel pain.', 'Surgical Services', 'anesthetic, anesthesiologist', '2026-11-16 21:14:57'),
(80, 'Surgeon\'s Fee', 'The professional fee paid to the doctor who performs your surgery.', 'Professional Fees', 'surgeon professional fee', '2026-11-16 21:14:57'),
(82, 'ECG / EKG', 'Records the electrical activity of the heart.', 'Diagnostic Services', NULL, '2026-11-16 21:56:27'),
(83, 'Bleeding Control', 'This service helps stop or control bleeding from an injury.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(84, 'Burn Treatment', 'This service provides care to protect the burned area and support healing.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(85, 'ECG Recording', 'This test records your heart\'s electrical activity to help doctors check your heart condition.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(86, 'Emergency Medication Administration', 'This charge covers the service of giving you medicine needed for your emergency treatment.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(87, 'Emergency Nursing Care', 'This covers nursing care and assistance provided while you receive emergency treatment.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(88, 'Emergency Observation Fee', 'This charge covers the time medical staff monitor your condition to check if it improves or requires further treatment.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(89, 'Emergency Room Assessment', 'This is the initial check performed by the medical staff to understand your condition and determine what care you need.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(90, 'IV Insertion Service', 'This service places a small tube into your vein so fluids or medicines can be given.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(91, 'Nebulization Therapy', 'This treatment turns medicine into a mist that you breathe into your lungs.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(92, 'Oxygen Therapy', 'This service provides extra oxygen to help you breathe more comfortably.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(93, 'Patient Stabilization', 'This service provides immediate medical care to help keep your condition from getting worse.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(94, 'Splint Application', 'This service supports an injured bone or body part to help prevent unnecessary movement.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(95, 'Vital Signs Monitoring', 'Medical staff check your basic body signs to monitor your condition.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(96, 'Wound Cleaning', 'This service cleans your wound to help prevent infection.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(97, 'Wound Suturing', 'This service closes your wound with stitches to help it heal.', 'Emergency Services', NULL, '2026-11-16 21:56:27'),
(98, 'Anesthesiologist\'s Fee', 'This is the professional fee for the doctor who manages your anesthesia during surgery.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(99, 'Assistant Surgeon\'s Fee', 'This is the fee for the doctor who assists the main surgeon during your operation.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(100, 'Attending Physician Fee', 'This is the fee for the doctor who oversees your medical treatment.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(101, 'Cardiologist\'s Fee', 'This is the fee for the doctor who evaluates and treats heart-related conditions.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(102, 'Medical Certificate Fee', 'This covers the preparation of a medical certificate based on your medical assessment.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(103, 'Medical Evaluation Fee', 'This is the fee for the doctor\'s evaluation of your health condition.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(104, 'Medical Follow-Up Fee', 'This is the fee for the doctor who checks your condition during a return visit.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(105, 'OB-GYN Fee', 'This is the fee for the doctor who provides care related to women\'s health and pregnancy.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(106, 'Orthopedic Doctor\'s Fee', 'This is the fee for the doctor who checks and treats problems involving your bones, joints, and muscles.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(107, 'Pathologist\'s Fee', 'This is the fee for the doctor who examines laboratory samples to help identify medical conditions.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(108, 'Pediatrician\'s Fee', 'This is the fee for the doctor who provides medical care for children.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(109, 'Professional Interpretation Fee', 'This is the fee for the doctor\'s explanation and interpretation of your medical results.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(110, 'Radiologist\'s Fee', 'This is the fee for the doctor who examines and interprets your X-ray or other imaging results.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(111, 'Specialist Consultation Fee', 'This is the fee for consulting a doctor who specializes in a particular medical field.', 'Professional Fees', NULL, '2026-11-16 21:56:27'),
(112, 'Anesthesia Supplies', 'This covers the materials used to support anesthesia during your operation.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(113, 'Major Surgery', 'This charge covers a major operation performed by the surgical team to treat your condition.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(114, 'Minor Surgery', 'This charge covers a minor operation performed to treat your medical condition.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(115, 'Operating Room Cleaning', 'This covers cleaning the operating room after surgery to prepare it for future use.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(116, 'Operating Room Fee', 'This is the charge for using the hospital\'s operating room during your surgery.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(117, 'Post-Operative Care', 'This covers medical care provided after your operation to support your recovery.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(118, 'Post-Surgical Monitoring', 'Medical staff monitor your condition after surgery to check your recovery.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(119, 'Recovery Room Fee', 'This is the charge for using the recovery room while medical staff monitor you after surgery.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(120, 'Sterilization Service', 'This service ensures that reusable surgical instruments are properly cleaned and sterilized.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(121, 'Surgical Drain Care', 'This service involves checking and caring for the device that removes extra fluid from your surgical area.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(122, 'Surgical Dressing', 'This service covers the cleaning and covering of your surgical wound to help protect it.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(123, 'Surgical Equipment Usage', 'This charge covers the use of medical equipment needed during your operation.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(124, 'Surgical Instrument Usage', 'This charge covers the use of the instruments needed by the surgical team.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(125, 'Surgical Room Preparation', 'This covers the preparation of the room and equipment before your operation.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(126, 'Surgical Supplies', 'This covers the supplies used by the medical team during your surgery.', 'Surgical Services', NULL, '2026-11-16 21:56:27'),
(127, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, NULL, '2026-11-18 17:05:16'),
(128, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, NULL, '2026-11-18 17:05:16'),
(129, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, NULL, '2026-11-18 17:11:58'),
(130, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, NULL, '2026-11-18 17:11:58'),
(131, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, NULL, '2026-11-18 17:17:54'),
(132, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, NULL, '2026-11-18 17:17:54'),
(133, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, NULL, '2026-11-18 17:23:00'),
(134, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, NULL, '2026-11-18 17:23:00'),
(135, 'Suturing', 'Closing a wound with stitches to help it heal properly and prevent infection.', NULL, NULL, '2026-11-18 17:23:00'),
(136, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, NULL, '2026-11-18 17:30:41'),
(137, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, NULL, '2026-11-18 17:30:41'),
(138, 'Suturing', 'Closing a wound with stitches to help it heal properly and prevent infection.', NULL, NULL, '2026-11-18 17:30:41');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--
UPDATE billing_records
SET actual_category = category
WHERE (actual_category IS NULL OR actual_category = '')
  AND category IS NOT NULL
  AND category <> ''
  AND predicted_category IS NOT NULL
  AND predicted_category <> '';
CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `patient_explanation` text DEFAULT NULL,
  `default_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `patient_explanation`, `default_price`, `price`, `description`, `category_id`, `is_active`, `created_at`) VALUES
(1, 'Consultation', NULL, NULL, 500.00, 'General doctor consultation', 3, 1, '2026-11-16 11:36:41'),
(2, 'Specialist Consultation', 'An appointment with a doctor who specializes in a particular medical field.', NULL, 800.00, 'Specialist doctor consultation', 3, 1, '2026-11-16 11:36:41'),
(3, 'Complete Blood Count (CBC)', NULL, NULL, 350.00, 'Blood test measuring cells', 1, 1, '2026-11-16 11:36:41'),
(4, 'Urinalysis', 'Urine test used to check for signs of infection and other health concerns.', 150.00, 150.00, 'Urine test used to check for signs of infection and other health concerns.', 1, 1, '2026-11-16 11:36:41'),
(5, 'Fasting Blood Sugar (FBS)', NULL, NULL, 200.00, 'Blood sugar test', 1, 1, '2026-11-16 11:36:41'),
(6, 'Chest X-Ray', 'Creates an image of the chest, including the lungs and heart.', NULL, 600.00, 'Chest imaging', 2, 1, '2026-11-16 11:36:41'),
(7, 'Abdominal Ultrasound', NULL, NULL, 1200.00, 'Abdominal imaging', 2, 1, '2026-11-16 11:36:41'),
(8, 'Paracetamol 500mg', NULL, NULL, 25.00, 'Pain reliever tablet', 4, 1, '2026-11-16 11:36:41'),
(9, 'Amoxicillin 500mg', NULL, NULL, 45.00, 'Antibiotic capsule', 4, 1, '2026-11-16 11:36:41'),
(10, 'Ward Room (per day)', NULL, NULL, 1500.00, 'Standard ward accommodation', 5, 1, '2026-11-16 11:36:41'),
(11, 'Private Room (per day)', NULL, NULL, 3000.00, 'Private room accommodation', 5, 1, '2026-11-16 11:36:41'),
(12, 'Wound Dressing', NULL, NULL, 250.00, 'Minor procedure — wound cleaning and dressing', 6, 1, '2026-11-16 11:36:41'),
(13, 'CBC', 'Blood test that checks red blood cells, white blood cells, and platelets.', 200.00, 200.00, 'Blood test that checks red blood cells, white blood cells, and platelets.', 1, 1, '2026-11-16 14:05:49'),
(14, 'Fecalysis', 'Stool test used to check for digestive problems or intestinal parasites.', 150.00, 150.00, 'Stool test used to check for digestive problems or intestinal parasites.', 1, 1, '2026-11-16 14:05:49'),
(15, 'FBS', 'Blood test that measures blood sugar after fasting.', 150.00, 150.00, 'Blood test that measures blood sugar after fasting.', 1, 1, '2026-11-16 14:05:49'),
(16, 'Blood Chemistry', 'Blood tests that measure different substances in the blood.', 500.00, 500.00, 'Blood tests that measure different substances in the blood.', 1, 1, '2026-11-16 14:05:49'),
(17, 'Lipid Profile', 'Blood test that measures cholesterol and other fats in the blood.', 600.00, 600.00, 'Blood test that measures cholesterol and other fats in the blood.', 1, 1, '2026-11-16 14:05:49'),
(18, 'Creatinine', 'Blood test that helps assess kidney function.', 200.00, 200.00, 'Blood test that helps assess kidney function.', 1, 1, '2026-11-16 14:05:49'),
(19, 'Uric Acid', 'Blood test that measures uric acid levels.', 200.00, 200.00, 'Blood test that measures uric acid levels.', 1, 1, '2026-11-16 14:05:49'),
(20, 'Hemoglobin', 'Blood test that measures the oxygen-carrying protein in red blood cells.', 150.00, 150.00, 'Blood test that measures the oxygen-carrying protein in red blood cells.', 1, 1, '2026-11-16 14:05:49'),
(21, 'Pregnancy Test', 'Test that checks for the presence of a pregnancy hormone.', 200.00, 200.00, 'Test that checks for the presence of a pregnancy hormone.', 1, 1, '2026-11-16 14:05:49'),
(22, 'Dengue NS1', 'Blood test that helps detect an early dengue infection.', 800.00, 800.00, 'Blood test that helps detect an early dengue infection.', 1, 1, '2026-11-16 14:05:49'),
(23, 'HbA1c', 'Blood test that measures average blood sugar over approximately three months.', 500.00, 500.00, 'Blood test that measures average blood sugar over approximately three months.', 1, 1, '2026-11-16 14:05:49'),
(24, 'Blood Typing', 'Test that identifies a person\'s blood group and Rh factor.', 150.00, 150.00, 'Test that identifies a person\'s blood group and Rh factor.', 1, 1, '2026-11-16 14:05:49'),
(25, 'ESR', 'Blood test that helps identify inflammation in the body.', 200.00, 200.00, 'Blood test that helps identify inflammation in the body.', 1, 1, '2026-11-16 14:05:49'),
(26, 'Platelet Count', 'Blood test that measures the number of platelets in the blood.', 150.00, 150.00, 'Blood test that measures the number of platelets in the blood.', 1, 1, '2026-11-16 14:05:49'),
(27, 'WBC Count', 'Blood test that measures white blood cells.', 150.00, 150.00, 'Blood test that measures white blood cells.', 1, 1, '2026-11-16 14:05:49'),
(28, 'Electrolytes', 'Blood test that measures important minerals such as sodium and potassium.', 400.00, 400.00, 'Blood test that measures important minerals such as sodium and potassium.', 1, 1, '2026-11-16 14:05:49'),
(29, 'SGPT / ALT', 'Blood test that helps assess liver function.', 200.00, 200.00, 'Blood test that helps assess liver function.', 1, 1, '2026-11-16 14:05:49'),
(30, 'SGOT / AST', 'Blood test that helps assess liver function.', 200.00, 200.00, 'Blood test that helps assess liver function.', 1, 1, '2026-11-16 14:05:49'),
(31, 'BUN', 'Blood test that helps evaluate kidney function.', 200.00, 200.00, 'Blood test that helps evaluate kidney function.', 1, 1, '2026-11-16 14:05:49'),
(33, 'X-Ray', 'Creates pictures of bones and certain body structures using X-rays.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(34, 'MRI Scan', 'Uses magnets and radio waves to create detailed pictures inside the body.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(35, 'CT Scan', 'Combines X-ray images to create detailed pictures of internal body structures.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(36, 'Ultrasound', 'Uses sound waves to show pictures of organs and tissues inside the body.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(37, 'ECG / EKG', 'Records the electrical activity of the heart.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(38, '2D Echo', 'Uses ultrasound to show the structure and movement of the heart.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(39, 'Mammography', 'Uses specialized X-ray imaging to examine breast tissue.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(40, 'Bone Density Test', 'Measures bone strength and helps assess bone health.', NULL, 0.00, NULL, 2, 1, '2026-11-16 14:05:49'),
(42, 'General Consultation', 'A doctor checks your health and discusses your medical concerns.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(43, 'Follow-up Consultation', 'A return appointment to review your condition or treatment progress.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(44, 'Pediatric Consultation', 'A medical appointment focused on children\'s health.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(45, 'Internal Medicine Consultation', 'A consultation for diagnosing and managing health conditions in adults.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(46, 'OB-GYN Consultation', 'A consultation focused on women\'s reproductive health.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(47, 'Dental Consultation', 'A dental examination to assess teeth and oral health.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(48, 'Dermatology Consultation', 'A consultation for skin, hair, and nail concerns.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(49, 'Cardiology Consultation', 'A consultation focused on heart and blood vessel health.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(50, 'Orthopedic Consultation', 'A consultation for bone, joint, and muscle concerns.', NULL, 0.00, NULL, 3, 1, '2026-11-16 14:05:50'),
(52, 'Prescription Medication', 'Medicines prescribed by a healthcare professional.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(53, 'Antibiotics', 'Medicines used to treat certain bacterial infections.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(54, 'Pain Relievers', 'Medicines used to relieve pain.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(55, 'Fever Medication', 'Medicines used to help reduce fever.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(56, 'Vitamins and Supplements', 'Products used to provide additional vitamins or nutrients when appropriate.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(57, 'Maintenance Medication', 'Medicines prescribed for ongoing health conditions.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(58, 'Injectable Medication', 'Medication administered through an injection by authorized healthcare personnel.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(59, 'Intravenous Medication', 'Medication administered directly into a vein by healthcare personnel.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(60, 'Nebulization Medication', 'Medication used with a nebulizer to help deliver medicine through breathing.', NULL, 0.00, NULL, 4, 1, '2026-11-16 14:05:50'),
(61, 'Syringe', 'A medical device used to measure and administer medication.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(62, 'IV Set', 'Equipment used to deliver fluids or medication through a vein.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(63, 'Disposable Gloves', 'Protective gloves used during medical procedures.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(64, 'Surgical Mask', 'A protective mask used to help reduce the spread of germs.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(65, 'Gauze', 'A medical material used to cover or protect wounds.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(66, 'Bandage', 'Material used to cover and support an injured area.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(67, 'Cotton', 'Medical material used for cleaning or applying solutions.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(68, 'Alcohol', 'An antiseptic solution used for cleaning skin.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(69, 'Medical Tape', 'Adhesive tape used to secure dressings or medical devices.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(70, 'Catheter', 'A medical tube used for draining or delivering fluids.', NULL, 0.00, NULL, 5, 1, '2026-11-16 14:05:50'),
(71, 'General Ward', 'Accommodation in a shared hospital room.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(72, 'Semi-Private Room', 'Accommodation in a room shared with a limited number of patients.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(73, 'Private Room', 'Accommodation in a room intended for one patient.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(74, 'Deluxe Room', 'Accommodation in a room with additional facilities or amenities.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(75, 'ICU Room', 'Accommodation and monitoring in the Intensive Care Unit.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(76, 'Room and Board', 'Hospital accommodation and basic room-related services.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(77, 'Isolation Room', 'A room designed to help prevent the spread of certain infections.', NULL, 0.00, NULL, 6, 1, '2026-11-16 14:05:50'),
(78, 'Physical Therapy', 'Treatment that helps improve movement, strength, and physical function.', NULL, 0.00, NULL, 7, 1, '2026-11-16 14:05:50'),
(79, 'Occupational Therapy', 'Therapy that helps patients perform daily activities more independently.', NULL, 0.00, NULL, 7, 1, '2026-11-16 14:05:50'),
(80, 'Speech Therapy', 'Therapy that helps improve communication and swallowing abilities.', NULL, 0.00, NULL, 7, 1, '2026-11-16 14:05:50'),
(81, 'Rehabilitation Therapy', 'Treatment that supports recovery of physical abilities and daily functions.', NULL, 0.00, NULL, 7, 1, '2026-11-16 14:05:50'),
(82, 'Respiratory Therapy', 'Treatment that helps improve breathing and lung function.', NULL, 0.00, NULL, 7, 1, '2026-11-16 14:05:50'),
(89, 'Emergency Consultation', NULL, NULL, 0.00, 'ER doctor consultation', 8, 1, '2026-11-16 21:14:57'),
(90, 'Triage Assessment', NULL, NULL, 0.00, 'Initial urgency assessment', 8, 1, '2026-11-16 21:14:57'),
(91, 'Initial Medical Assessment', NULL, NULL, 0.00, 'Initial medical evaluation', 8, 1, '2026-11-16 21:14:57'),
(92, 'Wound Cleaning', NULL, NULL, 0.00, 'Cleaning of wound', 9, 1, '2026-11-16 21:14:57'),
(93, 'Wound Dressing (ER)', NULL, NULL, 0.00, 'Dressing of wound in ER', 9, 1, '2026-11-16 21:14:57'),
(94, 'Suturing', 'Closing a wound with stitches to help it heal properly and prevent infection.', NULL, 0.00, 'Stitching of wound', 9, 1, '2026-11-16 21:14:57'),
(95, 'Splint Application', NULL, NULL, 0.00, 'Application of splint for fracture', 9, 1, '2026-11-16 21:14:57'),
(96, 'Oxygen Therapy', NULL, NULL, 0.00, 'Oxygen administration', 10, 1, '2026-11-16 21:14:57'),
(97, 'Nebulization', NULL, NULL, 0.00, 'Nebulizer treatment', 10, 1, '2026-11-16 21:14:57'),
(98, 'Respiratory Assessment', NULL, NULL, 0.00, 'Respiratory evaluation', 10, 1, '2026-11-16 21:14:57'),
(99, 'ECG / EKG', NULL, NULL, 0.00, 'Electrocardiogram', 11, 1, '2026-11-16 21:14:57'),
(100, 'Cardiac Monitoring', NULL, NULL, 0.00, 'Continuous heart monitoring', 11, 1, '2026-11-16 21:14:57'),
(101, 'CPR', 'Cardiopulmonary Resuscitation — an emergency procedure to restart the heart and breathing.', NULL, 0.00, 'Cardiopulmonary resuscitation', 11, 1, '2026-11-16 21:14:57'),
(102, 'Medication Administration', NULL, NULL, 0.00, 'Administration of ER medication', 12, 1, '2026-11-16 21:14:57'),
(103, 'IV Insertion', NULL, NULL, 0.00, 'Insertion of IV line', 12, 1, '2026-11-16 21:14:57'),
(104, 'IV Fluid Administration', NULL, NULL, 0.00, 'IV fluid therapy', 12, 1, '2026-11-16 21:14:57'),
(105, 'Blood Sugar Testing', NULL, NULL, 0.00, 'Point-of-care blood sugar test', 13, 1, '2026-11-16 21:14:57'),
(106, 'Pulse Oximetry', NULL, NULL, 0.00, 'Oxygen saturation monitoring', 13, 1, '2026-11-16 21:14:57'),
(107, 'Vital Signs Monitoring', NULL, NULL, 0.00, 'BP, HR, RR, Temp monitoring', 13, 1, '2026-11-16 21:14:57'),
(108, 'Emergency Nursing Care', NULL, NULL, 0.00, 'ER nursing care', 14, 1, '2026-11-16 21:14:57'),
(109, 'Patient Observation', NULL, NULL, 0.00, 'Continuous observation', 14, 1, '2026-11-16 21:14:57'),
(110, 'Patient Stabilization', NULL, NULL, 0.00, 'Stabilization of patient', 14, 1, '2026-11-16 21:14:57'),
(111, 'Pediatric Emergency Assessment', 'A rapid medical check-up for infants, children, and teenagers who need immediate attention for an illness or injury.', NULL, 0.00, 'ER assessment for pediatric patient', 15, 1, '2026-11-16 21:14:57'),
(112, 'Pediatric Emergency Treatment', NULL, NULL, 0.00, 'ER treatment for pediatric patient', 15, 1, '2026-11-16 21:14:57'),
(113, 'Pregnancy-Related Emergency Assessment', NULL, NULL, 0.00, 'OB-GYN ER assessment', 16, 1, '2026-11-16 21:14:57'),
(114, 'Patient Transfer', 'Moving a patient from one area or facility to another to receive appropriate medical care.', NULL, 0.00, 'Transfer to another facility', 17, 1, '2026-11-16 21:14:57'),
(115, 'Ambulance Reception', NULL, NULL, 0.00, 'Ambulance reception fee', 17, 1, '2026-11-16 21:14:57'),
(116, 'Referral Coordination', NULL, NULL, 0.00, 'Coordination of referral', 17, 1, '2026-11-16 21:14:57'),
(178, 'Emergency Room Assessment', 'This is the initial check performed by the medical staff to understand your condition and determine what care you need.', NULL, 0.00, 'Initial ER check', 20, 1, '2026-11-16 21:29:09'),
(179, 'Emergency Observation Fee', 'This charge covers the time medical staff monitor your condition to check if it improves or requires further treatment.', NULL, 0.00, 'ER observation', 20, 1, '2026-11-16 21:29:09'),
(180, 'Wound Cleaning', 'This service cleans your wound to help prevent infection.', NULL, 0.00, 'Wound cleaning', 20, 1, '2026-11-16 21:29:09'),
(181, 'Wound Suturing', 'This service closes your wound with stitches to help it heal.', NULL, 0.00, 'Wound stitching', 20, 1, '2026-11-16 21:29:09'),
(182, 'Bleeding Control', 'This service helps stop or control bleeding from an injury.', NULL, 0.00, 'Stop bleeding', 20, 1, '2026-11-16 21:29:09'),
(183, 'Burn Treatment', 'This service provides care to protect the burned area and support healing.', NULL, 0.00, 'Burn care', 20, 1, '2026-11-16 21:29:09'),
(184, 'Splint Application', 'This service supports an injured bone or body part to help prevent unnecessary movement.', NULL, 0.00, 'Splint for injury', 20, 1, '2026-11-16 21:29:09'),
(185, 'Oxygen Therapy', 'This service provides extra oxygen to help you breathe more comfortably.', NULL, 0.00, 'Oxygen administration', 20, 1, '2026-11-16 21:29:09'),
(186, 'Nebulization Therapy', 'This treatment turns medicine into a mist that you breathe into your lungs.', NULL, 0.00, 'Nebulizer treatment', 20, 1, '2026-11-16 21:29:09'),
(187, 'IV Insertion Service', 'This service places a small tube into your vein so fluids or medicines can be given.', NULL, 0.00, 'IV line insertion', 20, 1, '2026-11-16 21:29:09'),
(188, 'Emergency Medication Administration', 'This charge covers the service of giving you medicine needed for your emergency treatment.', NULL, 0.00, 'ER medication', 20, 1, '2026-11-16 21:29:09'),
(189, 'Vital Signs Monitoring', 'Medical staff check your basic body signs to monitor your condition.', NULL, 0.00, 'Vital signs check', 20, 1, '2026-11-16 21:29:09'),
(190, 'ECG Recording', 'This test records your heart\'s electrical activity to help doctors check your heart condition.', NULL, 0.00, 'Heart tracing', 20, 1, '2026-11-16 21:29:09'),
(191, 'Emergency Nursing Care', 'This covers nursing care and assistance provided while you receive emergency treatment.', NULL, 0.00, 'ER nursing', 20, 1, '2026-11-16 21:29:09'),
(192, 'Patient Stabilization', 'This service provides immediate medical care to help keep your condition from getting worse.', NULL, 0.00, 'Patient stabilization', 20, 1, '2026-11-16 21:29:09'),
(193, 'Minor Surgery', 'This charge covers a minor operation performed to treat your medical condition.', NULL, 0.00, 'Minor operation', 18, 1, '2026-11-16 21:29:09'),
(194, 'Major Surgery', 'This charge covers a major operation performed by the surgical team to treat your condition.', NULL, 0.00, 'Major operation', 18, 1, '2026-11-16 21:29:09'),
(195, 'Operating Room Fee', 'This is the charge for using the hospital\'s operating room during your surgery.', NULL, 0.00, 'OR use', 18, 1, '2026-11-16 21:29:09'),
(196, 'Surgical Room Preparation', 'This covers the preparation of the room and equipment before your operation.', NULL, 0.00, 'OR preparation', 18, 1, '2026-11-16 21:29:09'),
(197, 'Surgical Equipment Usage', 'This charge covers the use of medical equipment needed during your operation.', NULL, 0.00, 'Surgical equipment', 18, 1, '2026-11-16 21:29:09'),
(198, 'Surgical Supplies', 'This covers the supplies used by the medical team during your surgery.', NULL, 0.00, 'Surgical supplies', 18, 1, '2026-11-16 21:29:09'),
(199, 'Sterilization Service', 'This service ensures that reusable surgical instruments are properly cleaned and sterilized.', NULL, 0.00, 'Instrument sterilization', 18, 1, '2026-11-16 21:29:09'),
(200, 'Recovery Room Fee', 'This is the charge for using the recovery room while medical staff monitor you after surgery.', NULL, 0.00, 'Recovery room', 18, 1, '2026-11-16 21:29:09'),
(201, 'Post-Surgical Monitoring', 'Medical staff monitor your condition after surgery to check your recovery.', NULL, 0.00, 'Post-op monitoring', 18, 1, '2026-11-16 21:29:09'),
(202, 'Surgical Dressing', 'This service covers the cleaning and covering of your surgical wound to help protect it.', NULL, 0.00, 'Surgical wound dressing', 18, 1, '2026-11-16 21:29:09'),
(203, 'Surgical Drain Care', 'This service involves checking and caring for the device that removes extra fluid from your surgical area.', NULL, 0.00, 'Drain care', 18, 1, '2026-11-16 21:29:09'),
(204, 'Operating Room Cleaning', 'This covers cleaning the operating room after surgery to prepare it for future use.', NULL, 0.00, 'OR cleaning', 18, 1, '2026-11-16 21:29:09'),
(205, 'Anesthesia Supplies', 'This covers the materials used to support anesthesia during your operation.', NULL, 0.00, 'Anesthesia materials', 18, 1, '2026-11-16 21:29:09'),
(206, 'Surgical Instrument Usage', 'This charge covers the use of the instruments needed by the surgical team.', NULL, 0.00, 'Surgical instruments', 18, 1, '2026-11-16 21:29:09'),
(207, 'Post-Operative Care', 'This covers medical care provided after your operation to support your recovery.', NULL, 0.00, 'Post-op care', 18, 1, '2026-11-16 21:29:09'),
(208, 'Surgeon\'s Fee', 'The professional fee paid to the doctor who performs your surgery.', NULL, 0.00, 'Surgeon fee', 19, 1, '2026-11-16 21:29:09'),
(209, 'Assistant Surgeon\'s Fee', 'This is the fee for the doctor who assists the main surgeon during your operation.', NULL, 0.00, 'Assistant surgeon fee', 19, 1, '2026-11-16 21:29:09'),
(210, 'Anesthesiologist\'s Fee', 'This is the professional fee for the doctor who manages your anesthesia during surgery.', NULL, 0.00, 'Anesthesiologist fee', 19, 1, '2026-11-16 21:29:09'),
(211, 'Attending Physician Fee', 'This is the fee for the doctor who oversees your medical treatment.', NULL, 0.00, 'Attending physician fee', 19, 1, '2026-11-16 21:29:09'),
(212, 'Specialist Consultation Fee', 'This is the fee for consulting a doctor who specializes in a particular medical field.', NULL, 0.00, 'Specialist consult', 19, 1, '2026-11-16 21:29:09'),
(213, 'Pediatrician\'s Fee', 'This is the fee for the doctor who provides medical care for children.', NULL, 0.00, 'Pediatrician fee', 19, 1, '2026-11-16 21:29:09'),
(214, 'OB-GYN Fee', 'This is the fee for the doctor who provides care related to women\'s health and pregnancy.', NULL, 0.00, 'OB-GYN fee', 19, 1, '2026-11-16 21:29:09'),
(215, 'Cardiologist\'s Fee', 'This is the fee for the doctor who evaluates and treats heart-related conditions.', NULL, 0.00, 'Cardiologist fee', 19, 1, '2026-11-16 21:29:09'),
(216, 'Orthopedic Doctor\'s Fee', 'This is the fee for the doctor who checks and treats problems involving your bones, joints, and muscles.', NULL, 0.00, 'Orthopedic fee', 19, 1, '2026-11-16 21:29:09'),
(217, 'Radiologist\'s Fee', 'This is the fee for the doctor who examines and interprets your X-ray or other imaging results.', NULL, 0.00, 'Radiologist fee', 19, 1, '2026-11-16 21:29:09'),
(218, 'Pathologist\'s Fee', 'This is the fee for the doctor who examines laboratory samples to help identify medical conditions.', NULL, 0.00, 'Pathologist fee', 19, 1, '2026-11-16 21:29:09'),
(219, 'Medical Follow-Up Fee', 'This is the fee for the doctor who checks your condition during a return visit.', NULL, 0.00, 'Follow-up consult', 19, 1, '2026-11-16 21:29:09'),
(220, 'Medical Evaluation Fee', 'This is the fee for the doctor\'s evaluation of your health condition.', NULL, 0.00, 'Medical evaluation', 19, 1, '2026-11-16 21:29:09'),
(221, 'Medical Certificate Fee', 'This covers the preparation of a medical certificate based on your medical assessment.', NULL, 0.00, 'Med cert fee', 19, 1, '2026-11-16 21:29:09'),
(222, 'Professional Interpretation Fee', 'This is the fee for the doctor\'s explanation and interpretation of your medical results.', NULL, 0.00, 'Results interpretation', 19, 1, '2026-11-16 21:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `has_default_price` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `has_default_price`, `is_active`, `created_at`) VALUES
(1, 'Laboratory Services', 1, 1, '2026-11-16 14:01:07'),
(2, 'Diagnostic Services', 0, 1, '2026-11-16 14:01:07'),
(3, 'Consultation Services', 0, 1, '2026-11-16 14:01:07'),
(4, 'Pharmacy / Medication', 0, 1, '2026-11-16 14:01:07'),
(5, 'Medical Supplies', 0, 1, '2026-11-16 14:01:07'),
(6, 'Room Charges', 0, 1, '2026-11-16 14:01:07'),
(7, 'Therapy Services', 0, 1, '2026-11-16 14:01:07'),
(8, 'Emergency Services', 0, 1, '2026-11-16 14:01:07'),
(9, 'Surgical Services', 0, 1, '2026-11-16 14:01:07'),
(10, 'Professional Fees', 0, 1, '2026-11-16 14:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `survey_results`
--

CREATE TABLE `survey_results` (
  `id` int(11) NOT NULL,
  `metric` varchar(50) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_results`
--

INSERT INTO `survey_results` (`id`, `metric`, `score`, `created_at`) VALUES
(1, 'readability', 4.55, '2026-11-16 11:36:41'),
(2, 'transparency', 92.30, '2026-11-16 11:36:41'),
(3, 'understanding', 88.10, '2026-11-16 11:36:41'),
(4, 'consistency', 94.50, '2026-11-16 11:36:41'),
(5, 'accuracy', 91.00, '2026-11-16 11:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `role` enum('Admin','Billing','Front Desk Receptionist') NOT NULL DEFAULT 'Admin',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `recovery_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `full_name`, `username`, `password`, `email`, `contact_number`, `position`, `role`, `status`, `profile_picture`, `created_at`, `updated_at`, `is_archived`, `reset_token`, `reset_expires`, `reset_code`, `recovery_code`) VALUES
(1, 'Dizza Jean', 'Saturno', 'Mangubat', 'Dizza Jean Saturno Mangubat', 'admin', '$2y$12$oIYQeHA59nW.Sdj/cO2aH.VmAcqVtPAyP9OvqKM0tOKYNU3hwPkdC', 'dizzajean22@gmail.com', '09171234567', 'Administrator', 'Admin', 'Active', NULL, '2026-11-16 11:36:41', '2026-11-17 20:08:28', 0, NULL, '2026-11-17 13:23:28', '333733', NULL),
(2, 'Janina', '', 'Ilao', 'Janina Ilao', 'frontdesk', '$2y$12$Bl2nuv51uVMfLDgg0ajPgOTc26EqjSRDkEScdcuQiS8BXJWir65by', 'iedjeanjanina@gmail.com', '09181234567', 'Front Desk Receptionist', 'Front Desk Receptionist', 'Active', NULL, '2026-11-16 11:36:41', '2026-11-17 19:58:23', 0, '383febdbea3449942912c7531a48903c636adfb3f2bd5c51a1e1421d273a2971', '2026-11-17 13:37:58', NULL, NULL),
(3, 'Melanie', '', 'Alag', 'Melanie Alag', 'billing', '$2y$12$X0mrTNN2JL4vxo3EP5uNUOGUHWs98bmAWn.eifbnAW1rY6KYfb8Dq', 'eliberrycutie@gmail.com', '09191234567', 'Billing Staff', 'Billing', 'Active', NULL, '2026-11-16 11:36:41', '2026-11-17 19:59:02', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `voided_transactions`
--

CREATE TABLE `voided_transactions` (
  `id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `voided_by` int(11) NOT NULL,
  `voided_by_name` varchar(150) DEFAULT NULL,
  `reason` text NOT NULL,
  `original_snapshot` longtext DEFAULT NULL,
  `voided_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archived_records`
--
ALTER TABLE `archived_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_original` (`original_id`),
  ADD KEY `idx_active` (`restored_at`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `billing_categories`
--
ALTER TABLE `billing_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- Indexes for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_billing_patient` (`patient_db_id`),
  ADD KEY `idx_billing_status` (`status`),
  ADD KEY `idx_billing_date` (`date`),
  ADD KEY `idx_billing_patient_id` (`patient_id`);

--
-- Indexes for table `discount_types`
--
ALTER TABLE `discount_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insurance_discounts`
--
ALTER TABLE `insurance_discounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_read` (`is_read`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token_2` (`token`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD KEY `idx_patients_code` (`patient_code`),
  ADD KEY `idx_patients_name` (`full_name`),
  ADD KEY `idx_patients_type` (`patient_type`);

--
-- Indexes for table `rules`
--
ALTER TABLE `rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_rules_active` (`is_active`),
  ADD KEY `idx_rules_priority` (`priority` DESC);

--
-- Indexes for table `semantic_dictionary`
--
ALTER TABLE `semantic_dictionary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_semantic_term` (`technical_term`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_service_per_category` (`category_id`,`service_name`),
  ADD KEY `idx_services_name` (`service_name`),
  ADD KEY `idx_services_active` (`is_active`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `survey_results`
--
ALTER TABLE `survey_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_survey_metric` (`metric`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`);

--
-- Indexes for table `voided_transactions`
--
ALTER TABLE `voided_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_billing` (`billing_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archived_records`
--
ALTER TABLE `archived_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `billing_categories`
--
ALTER TABLE `billing_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `billing_records`
--
ALTER TABLE `billing_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `discount_types`
--
ALTER TABLE `discount_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `insurance_discounts`
--
ALTER TABLE `insurance_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `rules`
--
ALTER TABLE `rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `semantic_dictionary`
--
ALTER TABLE `semantic_dictionary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `survey_results`
--
ALTER TABLE `survey_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `voided_transactions`
--
ALTER TABLE `voided_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD CONSTRAINT `billing_records_ibfk_1` FOREIGN KEY (`patient_db_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `billing_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rules`
--
ALTER TABLE `rules`
  ADD CONSTRAINT `rules_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `billing_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `billing_categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

