<?php
require_once 'config.php';

echo "<h2>Starting Saas Migration...</h2>";

// 1. Update settings table
echo "Updating settings table schema...<br>";

// Check if org_id column exists
$res = mysqli_query($conn, "SHOW COLUMNS FROM settings LIKE 'org_id'");
if (mysqli_num_rows($res) == 0) {
    mysqli_query($conn, "ALTER TABLE settings ADD COLUMN org_id INT DEFAULT 0 AFTER `key`") or die(mysqli_error($conn));
}

// Check if primary key is already composite
$res = mysqli_query($conn, "SHOW INDEX FROM settings WHERE Key_name = 'PRIMARY'");
$pk_cols = [];
while ($row = mysqli_fetch_assoc($res)) {
    $pk_cols[] = $row['Column_name'];
}

if (count($pk_cols) == 1 && $pk_cols[0] == 'key') {
    echo "Converting primary key to composite (key, org_id)...<br>";
    mysqli_query($conn, "ALTER TABLE settings DROP PRIMARY KEY") or die(mysqli_error($conn));
    mysqli_query($conn, "ALTER TABLE settings ADD PRIMARY KEY (`key`, `org_id`)") or die(mysqli_error($conn));
}

// 2. Add other tables and columns
$other_queries = [
    "CREATE TABLE IF NOT EXISTS organizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        subscription_status ENUM('trial', 'active', 'expired') DEFAULT 'trial',
        trial_ends_at DATETIME,
        subscription_ends_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff') DEFAULT 'admin',
        reset_token VARCHAR(100) NULL,
        reset_token_expires DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
    )",
    "ALTER TABLE classes ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE subjects ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE class_subjects ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE teachers ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE teacher_assignments ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE teacher_restrictions ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE timetable ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "ALTER TABLE attendance_logs ADD COLUMN IF NOT EXISTS org_id INT AFTER id",
    "CREATE INDEX IF NOT EXISTS idx_org_settings ON settings(org_id)",
    "CREATE INDEX IF NOT EXISTS idx_org_classes ON classes(org_id)",
    "CREATE INDEX IF NOT EXISTS idx_org_subjects ON subjects(org_id)",
    "CREATE INDEX IF NOT EXISTS idx_org_class_subjects ON class_subjects(org_id)",
    "CREATE INDEX IF NOT EXISTS idx_org_teachers ON teachers(org_id)",
    "CREATE INDEX IF NOT EXISTS idx_org_timetable ON timetable(org_id)"
];

foreach ($other_queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: " . substr($q, 0, 50) . "...<br>";
    }
    else {
        echo "Skipped/Error: " . mysqli_error($conn) . "<br>";
    }
}

// 3. Fix Unique Constraints for SaaS (scoped by org_id)
echo "Updating unique constraints for multi-tenancy...<br>";

// Classes
$res = mysqli_query($conn, "SHOW INDEX FROM classes WHERE Key_name = 'class_name'");
if (mysqli_num_rows($res) > 0) {
    echo "Updating classes unique key...<br>";
    mysqli_query($conn, "ALTER TABLE classes DROP INDEX class_name");
    mysqli_query($conn, "ALTER TABLE classes ADD UNIQUE KEY idx_org_class (org_id, class_name, section)");
}

// Subjects
$res = mysqli_query($conn, "SHOW INDEX FROM subjects WHERE Key_name = 'subject_name'");
if (mysqli_num_rows($res) > 0) {
    echo "Updating subjects unique key...<br>";
    mysqli_query($conn, "ALTER TABLE subjects DROP INDEX subject_name");
    mysqli_query($conn, "ALTER TABLE subjects ADD UNIQUE KEY idx_org_subject (org_id, subject_name)");
}

// Teachers
$res = mysqli_query($conn, "SHOW INDEX FROM teachers WHERE Key_name = 'employee_code'");
if (mysqli_num_rows($res) > 0) {
    echo "Updating teachers unique key...<br>";
    mysqli_query($conn, "ALTER TABLE teachers DROP INDEX employee_code");
    mysqli_query($conn, "ALTER TABLE teachers ADD UNIQUE KEY idx_org_teacher_code (org_id, employee_code)");
}

echo "<h3>Migration Complete!</h3>";
?>
