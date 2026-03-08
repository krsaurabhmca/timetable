<?php
require_once '../config.php';

if (isset($_POST['populate'])) {
    // Clear existing data (optional but recommended for clean mock)
    db_query("SET FOREIGN_KEY_CHECKS = 0");
    db_query("TRUNCATE TABLE timetable");
    db_query("TRUNCATE TABLE teacher_restrictions");
    db_query("TRUNCATE TABLE teacher_assignments");
    db_query("TRUNCATE TABLE teachers");
    db_query("TRUNCATE TABLE subjects");
    db_query("TRUNCATE TABLE classes");
    db_query("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Mock Classes
    $classes_list = [
        ['10', 'A'], ['10', 'B'], ['9', 'A'], ['9', 'B'], ['8', 'A'], ['8', 'B']
    ];
    foreach ($classes_list as $c) {
        db_query("INSERT INTO classes (class_name, section) VALUES ('{$c[0]}', '{$c[1]}')");
    }

    // 2. Mock Subjects
    $subjects_list = [
        'Mathematics', 'English Language', 'Physics', 'Chemistry', 'Biology',
        'History', 'Geography', 'Physical Education', 'Computer Science'
    ];
    foreach ($subjects_list as $s) {
        db_query("INSERT INTO subjects (subject_name) VALUES ('$s')");
    }

    // 3. Mock Teachers
    $teachers_list = [
        ['John Smith', 'T101', 30],
        ['Sarah Connor', 'T102', 32],
        ['Mike Ross', 'T103', 28],
        ['Rachel Zane', 'T104', 30],
        ['Harvey Specter', 'T105', 25],
        ['Donna Paulsen', 'T106', 35]
    ];
    foreach ($teachers_list as $t) {
        db_query("INSERT INTO teachers (name, employee_code, weekly_limit, leisure_per_day) VALUES ('{$t[0]}', '{$t[1]}', {$t[2]}, 1)");
    }

    // 4. Assignments (Randomized)
    $res_t = db_query("SELECT id FROM teachers");
    $res_s = db_query("SELECT id FROM subjects");
    $res_c = db_query("SELECT id FROM classes");

    $t_ids = [];
    while ($r = mysqli_fetch_assoc($res_t))
        $t_ids[] = $r['id'];
    $s_ids = [];
    while ($r = mysqli_fetch_assoc($res_s))
        $s_ids[] = $r['id'];
    $c_ids = [];
    while ($r = mysqli_fetch_assoc($res_c))
        $c_ids[] = $r['id'];

    foreach ($c_ids as $cid) {
        foreach ($s_ids as $sid) {
            $tid = $t_ids[array_rand($t_ids)];
            db_query("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id) VALUES ($tid, $sid, $cid)");
        }
    }

    header("Location: ../index.php?mock=success");
    exit;
}
?>
