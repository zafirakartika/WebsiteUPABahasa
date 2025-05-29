-- Database: upa_bahasa
-- Fixed schema for UPA Bahasa UPNVJ

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `upa_bahasa`
--

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
(27, 5, 'user_registration', 'New admin registered: aniwijaya@admin.upnvj.ac.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 14:44:13');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `user_id`, `current_session`, `total_sessions`, `final_test_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 24, 24, '2025-05-29', 'completed', '2025-05-23 15:24:41', '2025-05-26 03:07:16');

-- --------------------------------------------------------

--
-- Table structure for table `elpt_registrations`
--

CREATE TABLE `elpt_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_date` date NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `payment_status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `billing_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elpt_registrations`
--

INSERT INTO `elpt_registrations` (`id`, `user_id`, `test_date`, `purpose`, `payment_status`, `billing_number`, `created_at`, `updated_at`) VALUES
(1, 4, '2025-05-26', 'Skripsi/Tesis/Tugas Akhir', 'confirmed', 'ELPT-20250523-8724', '2025-05-25 03:05:13', '2025-05-26 03:05:13'),
(2, 2, '2025-05-29', 'Skripsi/Tesis/Tugas Akhir', 'confirmed', 'ELPT-20250526-8491', '2025-05-26 02:32:22', '2025-05-26 02:48:48');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elpt_results`
--

INSERT INTO `elpt_results` (`id`, `user_id`, `registration_id`, `listening_score`, `structure_score`, `reading_score`, `test_date`, `created_at`) VALUES
(3, 4, 1, 195, 180, 192, '2025-05-26', '2025-05-26 03:12:06');

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
(8, 'maintenance_mode', '0', 'Maintenance mode flag (0=off, 1=on)', '2025-05-23 04:42:25', '2025-05-23 04:42:25');

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
(2, 'Budi Santoso', 'budi@student.upnvj.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2110511032', 'student', 'Hubungan Internasional', 'Fakultas Ilmu Sosial dan Ilmu Politik', 'S1', '081234567890', 1, '2025-05-23 04:42:25', '2025-05-26 02:59:51'),
(4, 'Zafira Kartika', 'zafira@student.upnvj.ac.id', '$2y$10$XJOsFYXMiIbhYgfIPLb8X.s/xOItl/9Y/1bQTzM1vjfmtouEr220u', '2210501071', 'student', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'D3', '081261460362', 1, '2025-05-25 15:42:50', '2025-05-26 02:43:53'),
(5, 'Ani Wijaya', 'aniwijaya@admin.upnvj.ac.id', '$2y$10$NJMVJnr4ZZODh5Bm8YT6CeQyrSqueuU7X2LuTBKScCiWA3L8bjxtW', NULL, 'admin', NULL, NULL, NULL, NULL, 1, '2025-05-29 14:44:13', '2025-05-29 14:44:13');

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
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `elpt_registrations`
--
ALTER TABLE `elpt_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `elpt_results`
--
ALTER TABLE `elpt_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `idx_total_score` (`total_score`),
  ADD KEY `idx_test_date` (`test_date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `elpt_registrations`
--
ALTER TABLE `elpt_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `elpt_results`
--
ALTER TABLE `elpt_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
