<?php
require_once '../config.php';

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];
header('Content-Type: application/json');

// ═══════════════════════════════════════════════════════════════
//  ACTION: Load Demo Classes (V – X with Sections A & B)
// ═══════════════════════════════════════════════════════════════
if ($action === 'load_demo_classes') {
    $demo_classes = [
        'Class V-A',  'Class V-B',
        'Class VI-A', 'Class VI-B',
        'Class VII-A','Class VII-B',
        'Class VIII-A','Class VIII-B',
        'Class IX-A', 'Class IX-B',
        'Class X-A',  'Class X-B',
    ];
    $inserted = 0;
    foreach ($demo_classes as $cn) {
        $cn = db_escape($cn);
        $r = db_query("INSERT IGNORE INTO classes (class_name, section, org_id) VALUES ('$cn', '', '$org_id')");
        if ($r && mysqli_affected_rows($GLOBALS['conn']) > 0) $inserted++;
    }
    echo json_encode(['success' => true, 'inserted' => $inserted, 'message' => "$inserted new classes added."]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: Load Demo CBSE Subjects
// ═══════════════════════════════════════════════════════════════
if ($action === 'load_demo_subjects') {
    // [name, priority, color]
    $demo_subjects = [
        ['Mathematics',          1, '#3b82f6'],
        ['Science',              1, '#10b981'],
        ['English Language',     2, '#8b5cf6'],
        ['English Literature',   2, '#a78bfa'],
        ['Hindi',                2, '#f59e0b'],
        ['Social Studies',       3, '#06b6d4'],
        ['History & Civics',     3, '#0891b2'],
        ['Geography',            3, '#0e7490'],
        ['Physics',              1, '#2563eb'],
        ['Chemistry',            1, '#16a34a'],
        ['Biology',              1, '#15803d'],
        ['Computer Science',     3, '#7c3aed'],
        ['Physical Education',   4, '#ef4444'],
        ['Art & Craft',          5, '#ec4899'],
        ['Moral Science / Value',5, '#f97316'],
    ];
    $inserted = 0;
    foreach ($demo_subjects as [$name, $priority, $color]) {
        $n = db_escape($name);
        $r = db_query("INSERT IGNORE INTO subjects (subject_name, priority, color, org_id) VALUES ('$n', $priority, '$color', '$org_id')");
        if ($r && mysqli_affected_rows($GLOBALS['conn']) > 0) $inserted++;
    }
    echo json_encode(['success' => true, 'inserted' => $inserted, 'message' => "$inserted new subjects added."]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: Auto-Map ALL Subjects to ALL Classes
// ═══════════════════════════════════════════════════════════════
if ($action === 'auto_map_all') {
    $classes_res  = db_query("SELECT id FROM classes  WHERE org_id = '$org_id'");
    $subjects_res = db_query("SELECT id FROM subjects WHERE org_id = '$org_id'");

    $class_ids   = [];
    $subject_ids = [];
    if ($classes_res)  while ($r = mysqli_fetch_assoc($classes_res))  $class_ids[]   = $r['id'];
    if ($subjects_res) while ($r = mysqli_fetch_assoc($subjects_res)) $subject_ids[] = $r['id'];

    $inserted = 0;
    foreach ($class_ids as $cid) {
        foreach ($subject_ids as $sid) {
            $r = db_query("INSERT IGNORE INTO class_subjects (class_id, subject_id, org_id) VALUES ($cid, $sid, '$org_id')");
            if ($r && mysqli_affected_rows($GLOBALS['conn']) > 0) $inserted++;
        }
    }
    echo json_encode(['success' => true, 'inserted' => $inserted, 'message' => "$inserted subject-class mappings created."]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ACTION: Load Demo Teachers + Auto-Assign to Subjects
// ═══════════════════════════════════════════════════════════════
if ($action === 'load_demo_teachers') {
    // [Name, Code, WeeklyLimit, Subjects they teach]
    $demo_teachers = [
        ['Rajesh Kumar Sharma',   'T-001', 30, ['Mathematics']],
        ['Priya Nair',            'T-002', 28, ['Mathematics', 'Physics']],
        ['Anita Singh',           'T-003', 30, ['Science', 'Biology']],
        ['Sanjay Mehta',          'T-004', 28, ['Physics', 'Chemistry']],
        ['Deepa Verma',           'T-005', 30, ['Chemistry', 'Biology']],
        ['Rahul Gupta',           'T-006', 30, ['English Language', 'English Literature']],
        ['Sunita Joshi',          'T-007', 28, ['English Language', 'Hindi']],
        ['Amit Pandey',           'T-008', 30, ['Hindi']],
        ['Kavita Rao',            'T-009', 28, ['Social Studies', 'History & Civics']],
        ['Mohan Das',             'T-010', 30, ['History & Civics', 'Geography']],
        ['Rekha Sharma',          'T-011', 28, ['Geography', 'Social Studies']],
        ['Suresh Patel',          'T-012', 30, ['Computer Science']],
        ['Meena Iyer',            'T-013', 25, ['Physical Education']],
        ['Pooja Tiwari',          'T-014', 20, ['Art & Craft']],
        ['Ravi Shankar',          'T-015', 20, ['Moral Science / Value']],
    ];

    // Fetch existing subject IDs by name
    $sub_map = [];
    $subs_res = db_query("SELECT id, subject_name FROM subjects WHERE org_id = '$org_id'");
    if ($subs_res) while ($s = mysqli_fetch_assoc($subs_res))
        $sub_map[$s['subject_name']] = $s['id'];

    // Fetch all class IDs
    $class_ids = [];
    $cr = db_query("SELECT id FROM classes WHERE org_id = '$org_id'");
    if ($cr) while ($c = mysqli_fetch_assoc($cr)) $class_ids[] = $c['id'];

    $teachers_added   = 0;
    $assignments_made = 0;

    foreach ($demo_teachers as [$name, $code, $limit, $subjects]) {
        $n = db_escape($name);
        $c = db_escape($code);

        // Insert teacher
        db_query("INSERT IGNORE INTO teachers (name, employee_code, weekly_limit, leisure_per_day, max_subjects, org_id)
                  VALUES ('$n', '$c', $limit, 1, " . count($subjects) . ", '$org_id')");

        if (mysqli_affected_rows($GLOBALS['conn']) > 0) $teachers_added++;

        // Get this teacher's ID
        $tid_res = db_query("SELECT id FROM teachers WHERE employee_code = '$c' AND org_id = '$org_id' LIMIT 1");
        if (!$tid_res) continue;
        $tid_row = mysqli_fetch_assoc($tid_res);
        if (!$tid_row) continue;
        $tid = $tid_row['id'];

        // Assign to all matching subjects × all classes
        foreach ($subjects as $sub_name) {
            if (!isset($sub_map[$sub_name])) continue;
            $sid = $sub_map[$sub_name];
            foreach ($class_ids as $cid) {
                $r = db_query("INSERT IGNORE INTO teacher_assignments (teacher_id, subject_id, class_id, org_id)
                               VALUES ($tid, $sid, $cid, '$org_id')");
                if ($r && mysqli_affected_rows($GLOBALS['conn']) > 0) $assignments_made++;
            }
        }
    }

    // Auto-assign first teacher as class teacher for each class (in order)
    $teachers_res = db_query("SELECT id FROM teachers WHERE org_id = '$org_id' ORDER BY id LIMIT " . count($class_ids));
    $tids = [];
    if ($teachers_res) while ($t = mysqli_fetch_assoc($teachers_res)) $tids[] = $t['id'];
    foreach ($class_ids as $i => $cid) {
        if (!isset($tids[$i])) break;
        $tid = $tids[$i];
        // Clear existing class teacher assignment for this class
        db_query("UPDATE teachers SET is_class_teacher_of = NULL WHERE is_class_teacher_of = $cid AND org_id = '$org_id'");
        db_query("UPDATE teachers SET is_class_teacher_of = $cid WHERE id = $tid AND org_id = '$org_id'");
    }

    echo json_encode([
        'success'          => true,
        'teachers_added'   => $teachers_added,
        'assignments_made' => $assignments_made,
        'message'          => "$teachers_added teachers added, $assignments_made subject-class assignments created."
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
