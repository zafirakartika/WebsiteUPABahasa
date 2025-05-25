-- Database: upa_bahasa
-- Fixed schema for UPA Bahasa UPNVJ

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `elpt_registrations`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `elpt_results`
-- --------------------------------------------------------

CREATE TABLE `elpt_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `listening_score` int(11) NOT NULL,
  `structure_score` int(11) NOT NULL,
  `reading_score` int(11) NOT NULL,
  `total_score` int(11) GENERATED ALWAYS AS (`listening_score` + `structure_score` + `reading_score`) STORED,
  `is_passed` tinyint(1) GENERATED ALWAYS AS (CASE WHEN (`listening_score` + `structure_score` + `reading_score`) >= 450 THEN 1 ELSE 0 END) STORED,
  `test_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `courses`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `certificates`
-- --------------------------------------------------------

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `certificate_type` enum('elpt','course_completion') DEFAULT 'elpt',
  `issue_date` date NOT NULL,
  `download_count` int(11) DEFAULT 0,
  `certificate_file` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `file_uploads`
-- --------------------------------------------------------

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_type` enum('image','document','other') DEFAULT 'other',
  `upload_purpose` enum('payment_proof','profile_picture','certificate','course_material','other') DEFAULT 'other',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `icon` varchar(50) DEFAULT 'info-circle',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Indexes for dumped tables
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `idx_nim` (`nim`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

ALTER TABLE `elpt_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

ALTER TABLE `elpt_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `idx_total_score` (`total_score`),
  ADD KEY `idx_test_date` (`test_date`);

ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_result_id` (`result_id`),
  ADD KEY `idx_certificate_number` (`certificate_number`),
  ADD KEY `idx_issue_date` (`issue_date`);

ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_upload_purpose` (`upload_purpose`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

-- --------------------------------------------------------
-- AUTO_INCREMENT for dumped tables
-- --------------------------------------------------------

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `elpt_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `elpt_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- --------------------------------------------------------
-- Constraints for dumped tables
-- --------------------------------------------------------

ALTER TABLE `elpt_registrations`
  ADD CONSTRAINT `elpt_registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `elpt_results`
  ADD CONSTRAINT `elpt_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `elpt_results_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `elpt_registrations` (`id`) ON DELETE CASCADE;

ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`result_id`) REFERENCES `elpt_results` (`id`) ON DELETE CASCADE;

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Insert default system settings
-- --------------------------------------------------------

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('elpt_fee', '75000', 'ELPT test fee in IDR'),
('course_fee', '850000', 'Course preparation fee in IDR'),
('min_passing_score', '450', 'Minimum score to pass ELPT'),
('max_participants_per_session', '30', 'Maximum participants per test session'),
('admin_registration_code', 'ADMIN123', 'Code required for admin registration'),
('app_name', 'UPA Bahasa UPNVJ', 'Application name'),
('app_version', '1.0.0', 'Application version'),
('maintenance_mode', '0', 'Maintenance mode flag (0=off, 1=on)');

-- --------------------------------------------------------
-- Insert default admin user
-- --------------------------------------------------------

INSERT INTO `users` (`name`, `email`, `password`, `nim`, `role`, `program`, `faculty`, `level`, `is_active`) VALUES
('Admin UPA Bahasa', 'admin@upabahasa.upnvj.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', NULL, NULL, NULL, 1);

-- Password for admin is: password

-- --------------------------------------------------------
-- Insert demo student users
-- --------------------------------------------------------

INSERT INTO `users` (`name`, `email`, `password`, `nim`, `role`, `program`, `faculty`, `level`, `is_active`) VALUES
('Budi Santoso', 'budi@student.upnvj.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2110511032', 'student', 'Hubungan Internasional', 'Fakultas Ilmu Sosial dan Ilmu Politik', 'S1', 1),
('Zafira Kartika', 'zafira@student.upnvj.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2210501071', 'student', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'D3', 1);

-- Password for both students is: password

COMMIT;