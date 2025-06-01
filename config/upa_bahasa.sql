-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 01, 2025 at 09:43 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `upa_bahasa`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupExpiredPaymentDeadlines` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE reg_id INT;
    DECLARE expired_cursor CURSOR FOR 
        SELECT id FROM elpt_registrations 
        WHERE payment_status = 'confirmed' 
        AND payment_proof_deadline IS NOT NULL 
        AND NOW() > payment_proof_deadline
        AND payment_proof_uploaded_at IS NULL;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Check if auto cleanup is enabled
    IF (SELECT setting_value FROM system_settings WHERE setting_key = 'auto_cleanup_expired_deadlines') = '1' THEN
        
        OPEN expired_cursor;
        
        read_loop: LOOP
            FETCH expired_cursor INTO reg_id;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Reset the registration to pending and clear deadlines
            UPDATE elpt_registrations 
            SET payment_status = 'pending',
                registration_confirmed_at = NULL,
                payment_proof_deadline = NULL
            WHERE id = reg_id;
            
            -- Log the cleanup action
            INSERT INTO activity_logs (user_id, activity_type, description, ip_address) 
            VALUES (
                (SELECT user_id FROM elpt_registrations WHERE id = reg_id),
                'payment_deadline_expired',
                CONCAT('Payment deadline expired for registration ID: ', reg_id, '. Status reset to pending.'),
                'system'
            );
            
        END LOOP;
        
        CLOSE expired_cursor;
        
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 4, 'user_registration', 'New student registered: zafira@student.upnvj.ac.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-25 15:42:50'),
(2, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:10:41'),
(3, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:12:50'),
(4, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:17:03'),
(5, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:18:10'),
(6, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:21:57'),
(7, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:28:26'),
(8, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:30:48'),
(9, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:31:06'),
(10, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:32:16'),
(11, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:32:52'),
(12, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:33:13'),
(13, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:36:13'),
(14, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:43:48'),
(15, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:45:36'),
(16, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:46:12'),
(17, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:47:00'),
(18, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:47:38'),
(19, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 05:47:58'),
(20, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 06:27:20'),
(21, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 06:29:20'),
(22, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 06:54:53'),
(23, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 13:19:17'),
(24, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 13:50:43'),
(25, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 14:11:53'),
(26, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 14:43:07'),
(27, 5, 'user_registration', 'New admin registered: aniwijaya@admin.upnvj.ac.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 14:44:13'),
(28, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 09:40:08'),
(29, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 10:32:19'),
(30, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 10:32:28'),
(31, 4, 'certificate_download', 'Downloaded certificate for result ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 10:35:07'),
(32, 6, 'user_registration', 'New student registered: budi@student.upnvj.ac.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 11:27:52'),
(33, 7, 'user_registration', 'New student registered: sitiaenun@student.upnvj.ac.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 14:10:16'),
(34, 4, 'certificate_download', 'Downloaded certificate for result ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 14:37:51'),
(35, 4, 'certificate_download', 'Downloaded certificate for result ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 14:38:31'),
(36, 4, 'certificate_download', 'Downloaded certificate for result ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 14:43:37'),
(37, 8, 'user_registration', 'New student registered: frank@student.upnvj.ac.id', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 03:53:34'),
(38, 8, 'certificate_download', 'Downloaded certificate for result ID: 6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 17:15:39'),
(39, 8, 'certificate_download', 'Downloaded certificate for result ID: 6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 17:55:55'),
(40, 8, 'course_payment_upload', 'Uploaded payment proof for course ID: 4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 19:41:30'),
(41, 8, 'certificate_download', 'Downloaded certificate for result ID: 6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 20:45:15'),
(42, 8, 'course_payment_upload', 'Uploaded payment proof for course ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 06:56:12'),
(43, 4, 'course_payment_upload', 'Uploaded payment proof for course ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 07:24:05'),
(44, 4, 'course_payment_upload', 'Uploaded payment proof for course ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 07:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_session` int(11) DEFAULT 1,
  `total_sessions` int(11) DEFAULT 24,
  `final_test_date` date DEFAULT NULL,
  `status` enum('active','completed','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_status` enum('pending','payment_uploaded','confirmed','active','completed') DEFAULT 'pending',
  `billing_number` varchar(50) DEFAULT NULL,
  `payment_proof_file` varchar(255) DEFAULT NULL,
  `payment_proof_uploaded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `user_id`, `current_session`, `total_sessions`, `final_test_date`, `status`, `created_at`, `updated_at`, `payment_status`, `billing_number`, `payment_proof_file`, `payment_proof_uploaded_at`) VALUES
(3, 4, 0, 24, '2025-07-01', 'active', '2025-05-30 10:29:27', '2025-06-01 07:26:32', '', 'COURSE-20250530-0003', 'uploads/payment_proofs/2025/06/course_payment_3_1748762758.png', '2025-06-01 07:25:58'),
(5, 8, 1, 24, '2025-07-03', 'active', '2025-06-01 06:50:49', '2025-06-01 06:56:46', '', NULL, 'uploads/payment_proofs/2025/06/course_payment_5_1748760972.png', '2025-06-01 06:56:12');

-- --------------------------------------------------------

--
-- Table structure for table `elpt_registrations`
--

CREATE TABLE `elpt_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `time_slot` varchar(20) DEFAULT NULL,
  `payment_status` enum('pending','confirmed','payment_uploaded','payment_verified','rejected') DEFAULT 'pending',
  `billing_number` varchar(50) DEFAULT NULL,
  `payment_proof_file` varchar(255) DEFAULT NULL,
  `payment_proof_uploaded_at` timestamp NULL DEFAULT NULL,
  `payment_proof_deadline` timestamp NULL DEFAULT NULL,
  `registration_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elpt_registrations`
--

INSERT INTO `elpt_registrations` (`id`, `user_id`, `test_date`, `purpose`, `time_slot`, `payment_status`, `billing_number`, `payment_proof_file`, `payment_proof_uploaded_at`, `payment_proof_deadline`, `registration_confirmed_at`, `created_at`, `updated_at`) VALUES
(6, 6, '2025-06-03', 'Skripsi/Tesis/Tugas Akhir', 'pagi', 'payment_verified', 'ELPT-20250530-4620', 'uploads/payment_proofs/2025/05/payment_6_1748612012.png', '2025-05-30 13:33:32', '2025-05-30 14:32:56', '2025-05-30 13:32:56', '2025-05-30 13:32:28', '2025-05-30 13:46:08'),
(7, 4, '2025-06-03', 'Lamar Beasiswa', 'siang', 'payment_verified', 'ELPT-20250530-4482', 'uploads/payment_proofs/2025/05/payment_7_1748615942.pdf', '2025-05-30 14:39:02', NULL, '2025-05-30 14:03:55', '2025-05-30 14:03:55', '2025-05-30 14:40:12'),
(9, 8, '2025-06-03', 'Yudisium', 'siang', 'payment_verified', 'ELPT-20250531-6574', 'uploads/payment_proofs/2025/06/payment_9_1748724137.png', '2025-05-31 20:42:17', NULL, '2025-05-31 03:54:16', '2025-05-31 03:54:16', '2025-05-31 20:42:53'),
(10, 7, '2025-06-03', 'Lamar Beasiswa', 'pagi', 'payment_verified', 'ELPT-20250531-3388', 'uploads/payment_proofs/2025/05/payment_10_1748707485.png', '2025-05-31 16:04:45', NULL, '2025-05-31 15:51:48', '2025-05-31 15:51:48', '2025-05-31 18:27:31');

-- --------------------------------------------------------

--
-- Table structure for table `elpt_results`
--

CREATE TABLE `elpt_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `listening_score` int(11) NOT NULL,
  `structure_score` int(11) NOT NULL,
  `reading_score` int(11) NOT NULL,
  `total_score` int(11) GENERATED ALWAYS AS (`listening_score` + `structure_score` + `reading_score`) STORED,
  `is_passed` tinyint(1) GENERATED ALWAYS AS (case when `listening_score` + `structure_score` + `reading_score` >= 450 then 1 else 0 end) STORED,
  `test_date` date NOT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elpt_results`
--

INSERT INTO `elpt_results` (`id`, `user_id`, `registration_id`, `listening_score`, `structure_score`, `reading_score`, `test_date`, `certificate_number`, `created_at`) VALUES
(5, 4, 7, 200, 200, 200, '2025-06-03', 'G25-01/TF-M-TPT09/2025/0001', '2025-05-30 14:34:23'),
(6, 8, 9, 240, 235, 210, '2025-06-03', 'G25-06/TF-M-TPT09/2025/0006', '2025-05-31 16:58:02');

--
-- Triggers `elpt_results`
--
DELIMITER $$
CREATE TRIGGER `generate_certificate_number` BEFORE INSERT ON `elpt_results` FOR EACH ROW BEGIN
    IF (NEW.listening_score + NEW.structure_score + NEW.reading_score) >= 450 THEN
        -- Generate certificate number using a sequence-like approach
        SET NEW.certificate_number = CONCAT('G25-', LPAD((SELECT IFNULL(MAX(id), 0) + 1 FROM elpt_results), 2, '0'), '/TF-M-TPT09/', YEAR(NEW.test_date), '/', LPAD((SELECT IFNULL(MAX(id), 0) + 1 FROM elpt_results), 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `sms_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_proofs`
--

CREATE TABLE `payment_proofs` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `status` enum('uploaded','verified','rejected') DEFAULT 'uploaded',
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_status_view`
-- (See below for the actual view)
--
CREATE TABLE `payment_status_view` (
`registration_id` int(11)
,`user_id` int(11)
,`student_name` varchar(255)
,`nim` varchar(10)
,`email` varchar(255)
,`test_date` date
,`time_slot` varchar(20)
,`purpose` varchar(100)
,`billing_number` varchar(50)
,`payment_status` enum('pending','confirmed','payment_uploaded','payment_verified','rejected')
,`registration_confirmed_at` timestamp
,`payment_proof_deadline` timestamp
,`payment_proof_uploaded_at` timestamp
,`registered_at` timestamp
,`proof_id` int(11)
,`proof_file_name` varchar(255)
,`proof_file_path` varchar(500)
,`proof_status` enum('uploaded','verified','rejected')
,`proof_notes` text
,`verified_at` timestamp
,`verified_by_name` varchar(255)
,`is_deadline_expired` int(1)
,`minutes_until_deadline` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'elpt_fee', '75000', 'ELPT test fee in IDR', '2025-05-23 04:42:25', '2025-05-25 14:36:55'),
(2, 'course_fee', '850000', 'Course preparation fee in IDR', '2025-05-23 04:42:25', '2025-05-25 14:37:10'),
(3, 'min_passing_score', '450', 'Minimum score to pass ELPT', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(4, 'max_participants_per_session', '30', 'Maximum participants per test session', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(5, 'admin_registration_code', 'ADMIN123', 'Code required for admin registration', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(6, 'app_name', 'UPA Bahasa UPNVJ', 'Application name', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(7, 'app_version', '1.0.0', 'Application version', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(8, 'maintenance_mode', '0', 'Maintenance mode flag (0=off, 1=on)', '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(9, 'max_participants_per_slot', '30', 'Maximum participants per time slot', '2025-05-30 09:19:16', '2025-05-30 09:19:16'),
(10, 'time_slot_buffer_minutes', '30', 'Buffer time between slots in minutes', '2025-05-30 09:19:16', '2025-05-30 09:19:16'),
(11, 'payment_deadline_hours', '1', 'Payment proof upload deadline in hours after registration confirmation', '2025-05-30 11:15:32', '2025-05-30 11:15:32'),
(12, 'max_payment_file_size', '5242880', 'Maximum payment proof file size in bytes (5MB)', '2025-05-30 11:15:32', '2025-05-30 11:15:32'),
(13, 'allowed_payment_file_types', 'jpg,jpeg,png,pdf', 'Allowed file extensions for payment proof', '2025-05-30 11:15:32', '2025-05-30 11:15:32'),
(14, 'test_location', 'Gd. RA Kartini Lt. 3 ruang 301/302/303', 'Test location information for notifications', '2025-05-30 11:15:32', '2025-05-30 11:19:37'),
(15, 'auto_cleanup_expired_deadlines', '1', 'Automatically reset expired payment deadlines (1=enabled, 0=disabled)', '2025-05-30 11:15:32', '2025-05-30 11:15:32'),
(16, 'payment_notification_enabled', '1', 'Send email notifications for payment status changes (1=enabled, 0=disabled)', '2025-05-30 11:15:32', '2025-05-30 11:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(10) DEFAULT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `program` varchar(100) DEFAULT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `level` enum('D3','S1','S2','S3') DEFAULT NULL,
  `no_telpon` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `nim`, `role`, `program`, `faculty`, `level`, `no_telpon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin UPA Bahasa', 'admin@upabahasa.upnvj.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', NULL, NULL, NULL, NULL, 1, '2025-05-23 04:42:25', '2025-05-23 04:42:25'),
(4, 'Zafira Kartika', 'zafira@student.upnvj.ac.id', '$2y$10$XJOsFYXMiIbhYgfIPLb8X.s/xOItl/9Y/1bQTzM1vjfmtouEr220u', '2210501071', 'student', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'D3', '081261460362', 1, '2025-05-25 15:42:50', '2025-05-26 02:43:53'),
(5, 'Ani Wijaya', 'aniwijaya@admin.upnvj.ac.id', '$2y$10$NJMVJnr4ZZODh5Bm8YT6CeQyrSqueuU7X2LuTBKScCiWA3L8bjxtW', NULL, 'admin', NULL, NULL, NULL, NULL, 1, '2025-05-29 14:44:13', '2025-05-29 14:44:13'),
(6, 'Budi Doremi', 'budi@student.upnvj.ac.id', '$2y$10$.aunrgaK0GR4EVTEbIkuy.u1AMy0J.Sn4L/wwje34IsN8lLX6wTn2', '2110511032', 'student', 'Hubungan Internasional', 'Fakultas Ilmu Sosial dan Ilmu Politik', 'S1', '081234567890', 1, '2025-05-30 11:27:52', '2025-05-30 11:29:51'),
(7, 'Siti Aenun', 'sitiaenun@student.upnvj.ac.id', '$2y$10$4q7/LYlN3Ww1AfvbGNLlyuG8LX7cv41CwUFl91mqSGFz5.8FCyGm2', '2210501055', 'student', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'D3', NULL, 1, '2025-05-30 14:10:16', '2025-05-30 14:10:16'),
(8, 'Frank Ocean', 'frank@student.upnvj.ac.id', '$2y$10$ui8DprhK3mU838K1l97us.Xg86axq7ZU69M3nSsFm76pgA20Sw2.q', '2210501111', 'student', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'D3', NULL, 1, '2025-05-31 03:53:34', '2025-05-31 03:53:34');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_registration_slots`
-- (See below for the actual view)
--
CREATE TABLE `v_registration_slots` (
`id` int(11)
,`user_id` int(11)
,`test_date` date
,`time_slot` varchar(20)
,`purpose` varchar(100)
,`payment_status` enum('pending','confirmed','payment_uploaded','payment_verified','rejected')
,`billing_number` varchar(50)
,`student_name` varchar(255)
,`nim` varchar(10)
,`program` varchar(100)
,`faculty` varchar(100)
,`created_at` timestamp
,`time_range` varchar(20)
,`slot_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `payment_status_view`
--
DROP TABLE IF EXISTS `payment_status_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_status_view`  AS SELECT `r`.`id` AS `registration_id`, `r`.`user_id` AS `user_id`, `u`.`name` AS `student_name`, `u`.`nim` AS `nim`, `u`.`email` AS `email`, `r`.`test_date` AS `test_date`, `r`.`time_slot` AS `time_slot`, `r`.`purpose` AS `purpose`, `r`.`billing_number` AS `billing_number`, `r`.`payment_status` AS `payment_status`, `r`.`registration_confirmed_at` AS `registration_confirmed_at`, `r`.`payment_proof_deadline` AS `payment_proof_deadline`, `r`.`payment_proof_uploaded_at` AS `payment_proof_uploaded_at`, `r`.`created_at` AS `registered_at`, `pp`.`id` AS `proof_id`, `pp`.`file_name` AS `proof_file_name`, `pp`.`file_path` AS `proof_file_path`, `pp`.`status` AS `proof_status`, `pp`.`notes` AS `proof_notes`, `pp`.`verified_at` AS `verified_at`, `v`.`name` AS `verified_by_name`, CASE WHEN `r`.`payment_proof_deadline` is not null AND current_timestamp() > `r`.`payment_proof_deadline` AND `r`.`payment_status` = 'confirmed' THEN 1 ELSE 0 END AS `is_deadline_expired`, CASE WHEN `r`.`payment_proof_deadline` is not null AND `r`.`payment_status` = 'confirmed' THEN timestampdiff(MINUTE,current_timestamp(),`r`.`payment_proof_deadline`) ELSE NULL END AS `minutes_until_deadline` FROM (((`elpt_registrations` `r` join `users` `u` on(`r`.`user_id` = `u`.`id`)) left join `payment_proofs` `pp` on(`r`.`id` = `pp`.`registration_id`)) left join `users` `v` on(`pp`.`verified_by` = `v`.`id`)) WHERE `u`.`role` = 'student' ORDER BY `r`.`created_at` AS `DESCdesc` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `v_registration_slots`
--
DROP TABLE IF EXISTS `v_registration_slots`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_registration_slots`  AS SELECT `r`.`id` AS `id`, `r`.`user_id` AS `user_id`, `r`.`test_date` AS `test_date`, `r`.`time_slot` AS `time_slot`, `r`.`purpose` AS `purpose`, `r`.`payment_status` AS `payment_status`, `r`.`billing_number` AS `billing_number`, `u`.`name` AS `student_name`, `u`.`nim` AS `nim`, `u`.`program` AS `program`, `u`.`faculty` AS `faculty`, `r`.`created_at` AS `created_at`, CASE WHEN `r`.`time_slot` = 'pagi' AND dayofweek(`r`.`test_date`) = 7 THEN '07:00-09:30' WHEN `r`.`time_slot` = 'pagi' THEN '09:30-12:00' WHEN `r`.`time_slot` = 'siang' AND dayofweek(`r`.`test_date`) = 7 THEN '09:30-12:00' WHEN `r`.`time_slot` = 'siang' THEN '13:00-15:30' WHEN `r`.`time_slot` = 'sore' THEN '13:00-15:30' ELSE `r`.`time_slot` END AS `time_range`, count(0) over ( partition by `r`.`test_date`,`r`.`time_slot`) AS `slot_count` FROM (`elpt_registrations` `r` join `users` `u` on(`r`.`user_id` = `u`.`id`)) WHERE `r`.`payment_status` in ('pending','confirmed','payment_uploaded','payment_verified') ORDER BY `r`.`test_date` ASC, `r`.`time_slot` ASC, `r`.`created_at` ASC  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_activity` (`user_id`,`activity_type`),
  ADD KEY `idx_activity_date` (`activity_type`,`created_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_final_test_date` (`final_test_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_billing_number` (`billing_number`);

--
-- Indexes for table `elpt_registrations`
--
ALTER TABLE `elpt_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_date_slot` (`test_date`,`time_slot`),
  ADD KEY `idx_payment_status_new` (`payment_status`),
  ADD KEY `idx_payment_deadline` (`payment_proof_deadline`),
  ADD KEY `idx_confirmed_at` (`registration_confirmed_at`);

--
-- Indexes for table `elpt_results`
--
ALTER TABLE `elpt_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `idx_total_score` (`total_score`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_certificate_number` (`certificate_number`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_registration_id` (`registration_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration_id` (`registration_id`),
  ADD KEY `idx_verified_by` (`verified_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `idx_nim` (`nim`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `elpt_registrations`
--
ALTER TABLE `elpt_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `elpt_results`
--
ALTER TABLE `elpt_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elpt_registrations`
--
ALTER TABLE `elpt_registrations`
  ADD CONSTRAINT `elpt_registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elpt_results`
--
ALTER TABLE `elpt_results`
  ADD CONSTRAINT `elpt_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `elpt_results_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `elpt_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `elpt_registrations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_proofs`
--
ALTER TABLE `payment_proofs`
  ADD CONSTRAINT `payment_proofs_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `elpt_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_proofs_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `cleanup_expired_payments` ON SCHEDULE EVERY 1 HOUR STARTS '2025-05-30 18:15:33' ON COMPLETION NOT PRESERVE ENABLE DO CALL CleanupExpiredPaymentDeadlines()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
