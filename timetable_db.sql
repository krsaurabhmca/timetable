-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 05:11 PM
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
-- Database: `timetable_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `absent_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `org_id`, `class_name`, `section`) VALUES
(1, NULL, '10', 'A'),
(2, NULL, '10', 'B'),
(5, NULL, '8', 'A'),
(6, NULL, '8', 'B'),
(3, NULL, '9', 'A'),
(4, NULL, '9', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `org_id`, `class_id`, `subject_id`) VALUES
(89, NULL, 12, 5),
(90, NULL, 12, 4),
(91, NULL, 12, 9),
(92, NULL, 12, 2),
(93, NULL, 12, 7),
(94, NULL, 12, 10),
(95, NULL, 12, 6),
(96, NULL, 12, 1),
(97, NULL, 12, 3),
(116, NULL, 13, 5),
(117, NULL, 13, 4),
(118, NULL, 13, 9),
(119, NULL, 13, 2),
(120, NULL, 13, 7),
(121, NULL, 13, 10),
(122, NULL, 13, 6),
(123, NULL, 13, 1),
(124, NULL, 13, 3),
(125, NULL, 10, 5),
(126, NULL, 10, 4),
(127, NULL, 10, 9),
(128, NULL, 10, 2),
(129, NULL, 10, 7),
(130, NULL, 10, 10),
(131, NULL, 10, 6),
(132, NULL, 10, 1),
(133, NULL, 10, 3),
(134, NULL, 11, 5),
(135, NULL, 11, 4),
(136, NULL, 11, 9),
(137, NULL, 11, 2),
(138, NULL, 11, 7),
(139, NULL, 11, 10),
(140, NULL, 11, 6),
(141, NULL, 11, 1),
(142, NULL, 11, 3),
(152, NULL, 9, 5),
(153, NULL, 9, 4),
(154, NULL, 9, 9),
(155, NULL, 9, 2),
(156, NULL, 9, 7),
(157, NULL, 9, 10),
(158, NULL, 9, 6),
(159, NULL, 9, 1),
(160, NULL, 9, 3),
(161, NULL, 8, 5),
(162, NULL, 8, 4),
(163, NULL, 8, 9),
(164, NULL, 8, 7),
(165, NULL, 8, 10),
(166, NULL, 8, 6),
(167, NULL, 8, 1),
(168, NULL, 8, 3),
(169, 7, 19, 14),
(170, 7, 19, 13),
(171, 7, 21, 14),
(172, 7, 21, 13),
(173, 7, 20, 14),
(174, 7, 20, 13);

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscription_status` enum('trial','active','expired') DEFAULT 'trial',
  `trial_ends_at` datetime DEFAULT NULL,
  `subscription_ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `email`, `subscription_status`, `trial_ends_at`, `subscription_ends_at`, `created_at`) VALUES
(1, 'OfferPlant Tech nologies', 'myofferplant@gmail.com', 'trial', '2026-03-22 15:25:46', NULL, '2026-03-08 14:25:46'),
(2, 'OfferPlant Technologies', 'ask@offerplant.com', 'trial', '2026-03-22 15:27:13', NULL, '2026-03-08 14:27:13'),
(7, 'OfferPlant Tech nologies', 'hi@offerplant.com', 'trial', '2026-03-22 15:30:24', NULL, '2026-03-08 14:30:24');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(50) NOT NULL,
  `org_id` int(11) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `org_id`, `value`) VALUES
('break_after_period', 0, '4'),
('break_after_period', 7, '4'),
('lunch_after_period', 0, '4'),
('lunch_after_period', 7, '4'),
('max_continuous_periods', 0, '1'),
('max_continuous_periods', 7, '1'),
('periods_per_day', 0, '7'),
('periods_per_day', 7, '8'),
('period_duration', 0, '45'),
('period_duration', 7, '45'),
('restrict_class_teacher_1st_period', 0, 'yes'),
('restrict_class_teacher_1st_period', 7, 'no'),
('saturday_periods', 0, '5'),
('saturday_periods', 7, '4'),
('schedule_type', 0, 'different'),
('schedule_type', 7, 'different'),
('working_days', 0, 'Monday,Tuesday,Wednesday,Thursday,Saturday,Sunday'),
('working_days', 7, 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) NOT NULL,
  `priority` int(11) DEFAULT 3,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `org_id`, `subject_name`, `priority`, `color`) VALUES
(1, NULL, 'Mathematics', 3, NULL),
(2, NULL, 'English Language', 3, NULL),
(3, NULL, 'Physics', 3, NULL),
(4, NULL, 'Chemistry', 3, NULL),
(5, NULL, 'Biology', 3, NULL),
(6, NULL, 'History', 3, NULL),
(7, NULL, 'Geography', 3, NULL),
(8, NULL, 'Physical Education', 3, NULL),
(9, NULL, 'Computer Science', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `weekly_limit` int(11) DEFAULT 30,
  `leisure_per_day` int(11) DEFAULT 1,
  `is_class_teacher_of` int(11) DEFAULT NULL,
  `max_subjects` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `org_id`, `name`, `employee_code`, `weekly_limit`, `leisure_per_day`, `is_class_teacher_of`, `max_subjects`) VALUES
(1, NULL, 'John Smith', 'T101', 30, 1, NULL, 1),
(2, NULL, 'Sarah Connor', 'T102', 32, 1, NULL, 1),
(3, NULL, 'Mike Ross', 'T103', 28, 1, NULL, 1),
(4, NULL, 'Rachel Zane', 'T104', 30, 1, NULL, 1),
(5, NULL, 'Harvey Specter', 'T105', 25, 1, NULL, 1),
(6, NULL, 'Donna Paulsen', 'T106', 35, 1, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `org_id`, `teacher_id`, `subject_id`, `class_id`) VALUES
(1, NULL, 6, 1, 1),
(2, NULL, 1, 2, 1),
(3, NULL, 3, 3, 1),
(4, NULL, 1, 4, 1),
(5, NULL, 1, 5, 1),
(6, NULL, 3, 6, 1),
(7, NULL, 3, 7, 1),
(8, NULL, 3, 8, 1),
(9, NULL, 5, 9, 1),
(10, NULL, 4, 1, 2),
(11, NULL, 1, 2, 2),
(12, NULL, 3, 3, 2),
(13, NULL, 4, 4, 2),
(14, NULL, 4, 5, 2),
(15, NULL, 6, 6, 2),
(16, NULL, 6, 7, 2),
(17, NULL, 1, 8, 2),
(18, NULL, 1, 9, 2),
(19, NULL, 2, 1, 3),
(20, NULL, 6, 2, 3),
(21, NULL, 1, 3, 3),
(22, NULL, 1, 4, 3),
(23, NULL, 1, 5, 3),
(24, NULL, 4, 6, 3),
(25, NULL, 6, 7, 3),
(26, NULL, 6, 8, 3),
(27, NULL, 4, 9, 3),
(28, NULL, 6, 1, 4),
(29, NULL, 1, 2, 4),
(30, NULL, 1, 3, 4),
(31, NULL, 6, 4, 4),
(32, NULL, 4, 5, 4),
(33, NULL, 1, 6, 4),
(34, NULL, 4, 7, 4),
(35, NULL, 4, 8, 4),
(36, NULL, 2, 9, 4),
(37, NULL, 4, 1, 5),
(38, NULL, 3, 2, 5),
(39, NULL, 6, 3, 5),
(40, NULL, 5, 4, 5),
(41, NULL, 6, 5, 5),
(42, NULL, 4, 6, 5),
(43, NULL, 5, 7, 5),
(44, NULL, 2, 8, 5),
(45, NULL, 1, 9, 5),
(46, NULL, 6, 1, 6),
(47, NULL, 5, 2, 6),
(48, NULL, 6, 3, 6),
(49, NULL, 5, 4, 6),
(50, NULL, 6, 5, 6),
(51, NULL, 3, 6, 6),
(52, NULL, 6, 7, 6),
(53, NULL, 6, 8, 6),
(54, NULL, 5, 9, 6);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_restrictions`
--

CREATE TABLE `teacher_restrictions` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(15) DEFAULT NULL,
  `period_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(15) DEFAULT NULL,
  `period_number` int(11) DEFAULT NULL,
  `is_adjustment` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_adjustments`
--

CREATE TABLE `timetable_adjustments` (
  `id` int(11) NOT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `period_number` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `original_teacher_id` int(11) DEFAULT NULL,
  `proxy_teacher_id` int(11) DEFAULT NULL,
  `adjustment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_adjustments`
--

INSERT INTO `timetable_adjustments` (`id`, `day_of_week`, `period_number`, `class_id`, `subject_id`, `original_teacher_id`, `proxy_teacher_id`, `adjustment_date`, `created_at`) VALUES
(1, 'Thursday', 1, 8, 10, 13, 8, '2026-03-12', '2026-03-08 02:36:51'),
(2, 'Thursday', 2, 13, 10, 13, 15, '2026-03-12', '2026-03-08 02:36:59'),
(3, 'Thursday', 3, 12, 10, 13, 11, '2026-03-12', '2026-03-08 02:37:03'),
(4, 'Thursday', 4, 9, 10, 13, 17, '2026-03-12', '2026-03-08 02:37:08'),
(5, 'Thursday', 5, 13, 10, 13, 16, '2026-03-12', '2026-03-08 02:37:11'),
(6, 'Thursday', 6, 9, 10, 13, 12, '2026-03-12', '2026-03-08 02:37:16'),
(7, 'Thursday', 6, 9, 10, 13, 12, '2026-03-12', '2026-03-08 02:41:03'),
(8, 'Wednesday', 2, 11, 1, 17, 13, '2026-03-11', '2026-03-08 02:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `org_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `org_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 7, 'James Conner', 'hi@offerplant.com', '$2y$10$w7k2Zm60g6ZCy4BMa8uORudZzWfUUL6tywQVPvZF6DFsrGrsrBDpy', 'admin', '2026-03-08 14:30:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_org_class` (`org_id`,`class_name`,`section`),
  ADD KEY `idx_org_classes` (`org_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_id` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_org_class_subjects` (`org_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`,`org_id`),
  ADD KEY `idx_org_settings` (`org_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_org_subject` (`org_id`,`subject_name`),
  ADD KEY `idx_org_subjects` (`org_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_org_teacher_code` (`org_id`,`employee_code`),
  ADD KEY `is_class_teacher_of` (`is_class_teacher_of`),
  ADD KEY `idx_org_teachers` (`org_id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `teacher_restrictions`
--
ALTER TABLE `teacher_restrictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_org_timetable` (`org_id`);

--
-- Indexes for table `timetable_adjustments`
--
ALTER TABLE `timetable_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `org_id` (`org_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `teacher_restrictions`
--
ALTER TABLE `teacher_restrictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_adjustments`
--
ALTER TABLE `timetable_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`is_class_teacher_of`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_restrictions`
--
ALTER TABLE `teacher_restrictions`
  ADD CONSTRAINT `teacher_restrictions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
