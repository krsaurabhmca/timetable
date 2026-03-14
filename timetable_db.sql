-- ============================================================
-- TimeGrid — Complete Database Schema
-- Version: 2.0 (Multi-tenant SaaS)
-- Updated: 2026-03-14
-- Deploy: Import into MySQL/MariaDB 10.4+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ────────────────────────────────────────────────────────────
--  ORGANIZATIONS  (one row per school / institution)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `organizations` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(255) NOT NULL,
  `email`               VARCHAR(255) NOT NULL,
  `subscription_status` ENUM('trial','active','expired') DEFAULT 'trial',
  `trial_ends_at`       DATETIME DEFAULT NULL,
  `subscription_ends_at`DATETIME DEFAULT NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  USERS  (admin & staff accounts, linked to org)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`                   INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`               INT(11) DEFAULT NULL,
  `full_name`            VARCHAR(100) NOT NULL,
  `email`                VARCHAR(255) NOT NULL,
  `password`             VARCHAR(255) NOT NULL,
  `role`                 ENUM('admin','staff') DEFAULT 'admin',
  `reset_token`          VARCHAR(100) NULL,
  `reset_token_expires`  DATETIME NULL,
  `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `org_id` (`org_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  SETTINGS  (per-org key-value configuration)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `key`    VARCHAR(50) NOT NULL,
  `org_id` INT(11)     NOT NULL DEFAULT 0,
  `value`  TEXT DEFAULT NULL,
  PRIMARY KEY (`key`, `org_id`),
  KEY `idx_org_settings` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  CLASSES  (school sections e.g. Class V-A)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `classes` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`     INT(11) DEFAULT NULL,
  `class_name` VARCHAR(50) NOT NULL,
  `section`    VARCHAR(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_class` (`org_id`, `class_name`, `section`),
  KEY `idx_org_classes` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  SUBJECTS  (CBSE / custom subject registry)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`       INT(11) DEFAULT NULL,
  `subject_name` VARCHAR(100) NOT NULL,
  `priority`     INT(11) DEFAULT 3,
  `color`        VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_subject` (`org_id`, `subject_name`),
  KEY `idx_org_subjects` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  CLASS_SUBJECTS  (which subjects belong to which class)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `class_subjects` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`     INT(11) DEFAULT NULL,
  `class_id`   INT(11) DEFAULT NULL,
  `subject_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_class_sub` (`org_id`, `class_id`, `subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_org_class_subjects` (`org_id`),
  CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`)   REFERENCES `classes`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  TEACHERS
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `teachers` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`              INT(11) DEFAULT NULL,
  `name`                VARCHAR(100) NOT NULL,
  `employee_code`       VARCHAR(20)  DEFAULT NULL,
  `weekly_limit`        INT(11) DEFAULT 30,
  `leisure_per_day`     INT(11) DEFAULT 1,
  `is_class_teacher_of` INT(11) DEFAULT NULL,
  `max_subjects`        INT(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_teacher_code` (`org_id`, `employee_code`),
  KEY `is_class_teacher_of` (`is_class_teacher_of`),
  KEY `idx_org_teachers` (`org_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`is_class_teacher_of`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  TEACHER_ASSIGNMENTS  (teacher → subject → class)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `teacher_assignments` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`     INT(11) DEFAULT NULL,
  `teacher_id` INT(11) DEFAULT NULL,
  `subject_id` INT(11) DEFAULT NULL,
  `class_id`   INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_org_ta_unique` (`org_id`, `teacher_id`, `subject_id`, `class_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `class_id`   (`class_id`),
  CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`class_id`)   REFERENCES `classes`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  TEACHER_RESTRICTIONS  (blocked period/day per teacher)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `teacher_restrictions` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`        INT(11) DEFAULT NULL,
  `teacher_id`    INT(11) DEFAULT NULL,
  `day_of_week`   VARCHAR(15) DEFAULT NULL,
  `period_number` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_org_restrictions` (`org_id`),
  CONSTRAINT `teacher_restrictions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  TIMETABLE  (generated timetable entries)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `timetable` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`        INT(11) DEFAULT NULL,
  `class_id`      INT(11) DEFAULT NULL,
  `teacher_id`    INT(11) DEFAULT NULL,
  `subject_id`    INT(11) DEFAULT NULL,
  `day_of_week`   VARCHAR(15) DEFAULT NULL,
  `period_number` INT(11) DEFAULT NULL,
  `is_adjustment` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `class_id`       (`class_id`),
  KEY `teacher_id`     (`teacher_id`),
  KEY `subject_id`     (`subject_id`),
  KEY `idx_org_timetable` (`org_id`),
  CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`)   REFERENCES `classes`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  TIMETABLE_ADJUSTMENTS  (daily substitutions)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `timetable_adjustments` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`              INT(11) DEFAULT NULL,
  `day_of_week`         VARCHAR(20) DEFAULT NULL,
  `period_number`       INT(11) DEFAULT NULL,
  `class_id`            INT(11) DEFAULT NULL,
  `subject_id`          INT(11) DEFAULT NULL,
  `original_teacher_id` INT(11) DEFAULT NULL,
  `proxy_teacher_id`    INT(11) DEFAULT NULL,
  `adjustment_date`     DATE DEFAULT NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org_adj`  (`org_id`),
  KEY `idx_adj_date` (`adjustment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ────────────────────────────────────────────────────────────
--  ATTENDANCE_LOGS  (teacher absence tracking)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `org_id`      INT(11) DEFAULT NULL,
  `teacher_id`  INT(11) DEFAULT NULL,
  `absent_date` DATE DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_org_attendance` (`org_id`),
  CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
--  DEFAULT SEED — org_id=0 fallback settings (local dev)
-- ============================================================
INSERT IGNORE INTO `settings` (`key`, `org_id`, `value`) VALUES
('working_days',                    0, 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'),
('periods_per_day',                 0, '8'),
('saturday_periods',                0, '4'),
('period_duration',                 0, '45'),
('lunch_after_period',              0, '4'),
('max_continuous_periods',          0, '2'),
('schedule_type',                   0, 'different'),
('restrict_class_teacher_1st_period',0,'no'),
('break_after_period',              0, '4');
