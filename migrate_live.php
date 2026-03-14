<?php
/**
 * TimeGrid — Live Database Structure Updater
 * Run this ONCE on the live server: https://timegrid.offerplant.com/migrate_live.php
 * Safe to run: uses ALTER TABLE ... ADD COLUMN IF NOT EXISTS (no data loss)
 */
require_once 'config.php';

$log = [];
$errors = [];

function run($conn, $label, $sql) {
    global $log, $errors;
    $r = mysqli_query($conn, $sql);
    if ($r) {
        $log[] = "✅ $label";
    } else {
        $err = mysqli_error($conn);
        // Skip "Duplicate column" or "already exists" errors — they're safe
        if (stripos($err, 'Duplicate column') !== false || stripos($err, 'already exists') !== false) {
            $log[] = "⏭️  $label (already exists — skipped)";
        } else {
            $errors[] = "❌ $label: $err";
        }
    }
}

// ═══════════════════════════════════════════════════════════
//  1. users — add reset_token columns (forgot password flow)
// ═══════════════════════════════════════════════════════════
run($conn, 'users: add reset_token column',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(100) NULL AFTER role");
run($conn, 'users: add reset_token_expires column',
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL AFTER reset_token");

// ═══════════════════════════════════════════════════════════
//  2. timetable_adjustments — add org_id (multi-tenancy)
// ═══════════════════════════════════════════════════════════
run($conn, 'timetable_adjustments: add org_id column',
    "ALTER TABLE timetable_adjustments ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER id");
run($conn, 'timetable_adjustments: add index on org_id',
    "ALTER TABLE timetable_adjustments ADD INDEX IF NOT EXISTS idx_org_adj (org_id)");
run($conn, 'timetable_adjustments: add index on adjustment_date',
    "ALTER TABLE timetable_adjustments ADD INDEX IF NOT EXISTS idx_adj_date (adjustment_date)");

// ═══════════════════════════════════════════════════════════
//  3. class_subjects — fix unique key to be org-scoped
// ═══════════════════════════════════════════════════════════
// Drop old non-scoped unique key if it exists
$ck = mysqli_query($conn, "SHOW INDEX FROM class_subjects WHERE Key_name = 'class_id'");
if ($ck && mysqli_num_rows($ck) > 0) {
    run($conn, 'class_subjects: drop old unique key (class_id, subject_id)',
        "ALTER TABLE class_subjects DROP INDEX class_id");
    run($conn, 'class_subjects: add org-scoped unique key',
        "ALTER TABLE class_subjects ADD UNIQUE KEY idx_org_class_sub (org_id, class_id, subject_id)");
} else {
    $log[] = "⏭️  class_subjects: unique key already org-scoped";
}

// ═══════════════════════════════════════════════════════════
//  4. teacher_assignments — add org-scoped unique key
// ═══════════════════════════════════════════════════════════
$ck2 = mysqli_query($conn, "SHOW INDEX FROM teacher_assignments WHERE Key_name = 'idx_org_ta_unique'");
if ($ck2 && mysqli_num_rows($ck2) == 0) {
    run($conn, 'teacher_assignments: add org-scoped unique key',
        "ALTER TABLE teacher_assignments ADD UNIQUE KEY idx_org_ta_unique (org_id, teacher_id, subject_id, class_id)");
} else {
    $log[] = "⏭️  teacher_assignments: unique key already exists";
}

// ═══════════════════════════════════════════════════════════
//  5. organizations — ensure email is unique (safety)
// ═══════════════════════════════════════════════════════════
$ck3 = mysqli_query($conn, "SHOW INDEX FROM organizations WHERE Key_name = 'email'");
if ($ck3 && mysqli_num_rows($ck3) == 0) {
    run($conn, 'organizations: add unique key on email',
        "ALTER TABLE organizations ADD UNIQUE KEY email (email)");
} else {
    $log[] = "⏭️  organizations.email unique key already exists";
}

// ═══════════════════════════════════════════════════════════
//  6. attendance_logs — add org_id if missing
// ═══════════════════════════════════════════════════════════
run($conn, 'attendance_logs: add org_id column',
    "ALTER TABLE attendance_logs ADD COLUMN IF NOT EXISTS org_id INT NULL AFTER id");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TimeGrid — DB Migration</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 720px; margin: 60px auto; padding: 0 20px; color: #1e293b; }
        h1 { color: #4ade80; }
        .log-box { background: #0f172a; color: #e2e8f0; padding: 24px; border-radius: 12px; font-family: monospace; font-size: 0.9rem; line-height: 1.8; }
        .errors { background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 12px; margin-top: 16px; font-family: monospace; font-size: 0.9rem; line-height: 1.8; }
        .done { background: #dcfce7; color: #166534; padding: 16px 24px; border-radius: 12px; margin-top: 16px; font-weight: 700; font-size: 1rem; }
        .warn { background: #fef9c3; color: #854d0e; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #eab308; }
    </style>
</head>
<body>
    <h1>⚙️ TimeGrid — Live DB Migration</h1>
    <div class="warn">⚠️ <strong>Delete this file after running.</strong> Do not leave it publicly accessible.</div>

    <div class="log-box">
        <?php foreach ($log as $l) echo htmlspecialchars($l) . "<br>"; ?>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <strong>Errors:</strong><br>
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
    </div>
    <?php endif; ?>

    <div class="done">
        ✅ Migration complete! <?php echo count($log); ?> steps processed, <?php echo count($errors); ?> error(s).
        <br><br>
        <strong>⚠️ Please delete <code>migrate_live.php</code> from your server now.</strong>
    </div>
</body>
</html>
