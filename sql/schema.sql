CREATE DATABASE IF NOT EXISTS timetable_db;
USE timetable_db;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT
);

-- Classes Table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    UNIQUE KEY (class_name, section)
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE
);

-- Teachers Table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    employee_code VARCHAR(20) UNIQUE,
    weekly_limit INT DEFAULT 30,
    leisure_per_day INT DEFAULT 1,
    is_class_teacher_of INT DEFAULT NULL,
    FOREIGN KEY (is_class_teacher_of) REFERENCES classes(id) ON DELETE SET NULL
);

-- Teacher Subject Assignments
CREATE TABLE IF NOT EXISTS teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    subject_id INT,
    class_id INT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Teacher Restrictions (Periods they can't teach)
CREATE TABLE IF NOT EXISTS teacher_restrictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    day_of_week VARCHAR(15), -- Monday, Tuesday, etc.
    period_number INT,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- The Generated Timetable
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    teacher_id INT,
    subject_id INT,
    day_of_week VARCHAR(15),
    period_number INT,
    is_adjustment BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Absentee Logs for adjustments
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    absent_date DATE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Initial Settings
INSERT IGNORE INTO settings (`key`, `value`) VALUES 
('working_days', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'),
('periods_per_day', '8'),
('period_duration', '45'),
('break_after_period', '4');
