-- Update settings table for multi-tenancy
ALTER TABLE settings DROP PRIMARY KEY;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS org_id INT DEFAULT 0 AFTER `key`;
ALTER TABLE settings ADD PRIMARY KEY (`key`, `org_id`);

-- Add Organizations table
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    subscription_status ENUM('trial', 'active', 'expired') DEFAULT 'trial',
    trial_ends_at DATETIME,
    subscription_ends_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Add org_id to existing tables
ALTER TABLE classes ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE subjects ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE teacher_assignments ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE teacher_restrictions ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE timetable ADD COLUMN IF NOT EXISTS org_id INT AFTER id;
ALTER TABLE attendance_logs ADD COLUMN IF NOT EXISTS org_id INT AFTER id;

-- Create indexes for performance
CREATE INDEX idx_org_settings ON settings(org_id);
CREATE INDEX idx_org_classes ON classes(org_id);
CREATE INDEX idx_org_subjects ON subjects(org_id);
CREATE INDEX idx_org_teachers ON teachers(org_id);
CREATE INDEX idx_org_timetable ON timetable(org_id);
