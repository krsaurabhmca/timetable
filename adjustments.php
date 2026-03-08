<?php
require_once 'config.php';

$absent_teacher_id = $_GET['teacher_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');
$day_name = date('l', strtotime($date));

// Handle Adjustment Assignment
if (isset($_POST['make_adjustment'])) {
    $class_id = db_escape($_POST['class_id']);
    $subject_id = db_escape($_POST['subject_id']);
    $proxy_id = db_escape($_POST['proxy_teacher_id']);
    $original_id = db_escape($_POST['original_teacher_id']);
    $period = db_escape($_POST['period']);
    $adj_date = db_escape($_POST['date']);
    $day = db_escape($_POST['day']);

    if (!empty($proxy_id)) {
        db_query("INSERT INTO timetable_adjustments (day_of_week, period_number, class_id, subject_id, original_teacher_id, proxy_teacher_id, adjustment_date) 
                  VALUES ('$day', $period, $class_id, $subject_id, $original_id, $proxy_id, '$adj_date') 
                  ON DUPLICATE KEY UPDATE proxy_teacher_id = $proxy_id");
        $msg = "Substitution assigned successfully.";
    }
    else {
        db_query("DELETE FROM timetable_adjustments WHERE adjustment_date = '$adj_date' AND original_teacher_id = $original_id AND period_number = $period");
        $msg = "Substitution removed.";
    }
}

// Get adjustments for this date
$existing_adj_res = db_query("SELECT * FROM timetable_adjustments WHERE adjustment_date = '$date'");
$daily_adjustments = [];
while ($adj = mysqli_fetch_assoc($existing_adj_res)) {
    $daily_adjustments[$adj['period_number']][$adj['original_teacher_id']] = $adj['proxy_teacher_id'];
}

$teachers_res = db_query("SELECT * FROM teachers ORDER BY name");
$teachers_list = [];
while ($t = mysqli_fetch_assoc($teachers_res))
    $teachers_list[$t['id']] = $t;

$settings_res = db_query("SELECT * FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$is_sat = ($day_name === 'Saturday');
$periods_count = (int)($is_sat ? ($settings['saturday_periods'] ?? 4) : $settings['periods_per_day']);
$lunch_after = (int)($settings['lunch_after_period'] ?? 0);

$absent_routine = [];
if ($absent_teacher_id) {
    $res = db_query("SELECT t.*, s.subject_name, c.class_name, c.id as cid 
                    FROM timetable t 
                    JOIN subjects s ON t.subject_id = s.id 
                    JOIN classes c ON t.class_id = c.id 
                    WHERE t.teacher_id = $absent_teacher_id AND t.day_of_week = '$day_name'");
    while ($row = mysqli_fetch_assoc($res)) {
        $absent_routine[$row['period_number']] = $row;
    }
}

require_once 'includes/header.php';
?>

<div class="fade-in">
    <div class="card" style="border-left: 5px solid var(--primary); padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem;">
            <div style="flex: 1; min-width: 300px;">
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;"><i class="fas fa-user-clock"></i> Substitution Center</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Manage daily teacher absences and assign proxy faculty.</p>
            </div>
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Date</label>
                    <input type="date" name="date" value="<?php echo $date; ?>" onchange="this.form.submit()" style="height: 42px; width: 160px; font-weight: 600;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Absent Faculty</label>
                    <select name="teacher_id" onchange="this.form.submit()" style="height: 42px; min-width: 250px; font-weight: 600;">
                        <option value="">-- Select Absent Teacher --</option>
                        <?php foreach ($teachers_list as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo($absent_teacher_id == $t['id']) ? 'selected' : ''; ?>>
                            <?php echo $t['name']; ?>
                        </option>
                        <?php
endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($absent_teacher_id): ?>
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                <?php if ($p == $lunch_after + 1 && $lunch_after > 0): ?>
                    <div style="background: #f1f5f9; padding: 0.75rem; text-align: center; border-radius: 8px; font-weight: 800; color: var(--text-muted); letter-spacing: 2px; font-size: 0.8rem; border: 1px dashed var(--border);">LUNCH BREAK</div>
                <?php
        endif; ?>

                <?php
        $is_busy = isset($absent_routine[$p]);
        $current_proxy = $daily_adjustments[$p][$absent_teacher_id] ?? null;
?>
                
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 1.5rem; margin-bottom: 0.75rem; <?php echo !$is_busy ? 'opacity: 0.6; background: #fafafa;' : ''; ?>">
                    <div style="width: 45px; height: 45px; background: <?php echo $is_busy ? 'var(--primary)' : '#e2e8f0'; ?>; color: white; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 800; font-size: 1.1rem; flex-shrink: 0;">
                        <?php echo $p; ?>
                    </div>

                    <div style="flex: 1;">
                        <?php if ($is_busy): ?>
                            <div style="font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: var(--danger);">Absent For:</div>
                            <div style="font-weight: 700; font-size: 1rem; color: var(--text-main);"><?php echo $absent_routine[$p]['subject_name']; ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;"><i class="fas fa-graduation-cap"></i> Class <?php echo $absent_routine[$p]['class_name']; ?></div>
                        <?php
        else: ?>
                            <div style="font-weight: 600; color: var(--text-muted); font-style: italic;">Leisure Period</div>
                        <?php
        endif; ?>
                    </div>

                    <?php if ($is_busy): ?>
                        <div style="width: 300px;">
                            <form method="POST" style="display: flex; gap: 8px;">
                                <input type="hidden" name="class_id" value="<?php echo $absent_routine[$p]['cid']; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo $absent_routine[$p]['subject_id']; ?>">
                                <input type="hidden" name="original_teacher_id" value="<?php echo $absent_teacher_id; ?>">
                                <input type="hidden" name="period" value="<?php echo $p; ?>">
                                <input type="hidden" name="day" value="<?php echo $day_name; ?>">
                                <input type="hidden" name="date" value="<?php echo $date; ?>">

                                <select name="proxy_teacher_id" required style="height: 38px; font-size: 0.85rem; border-radius: 8px; <?php echo $current_proxy ? 'border-color: var(--success); background: #f0fdf4;' : ''; ?>">
                                    <option value="">-- Assign Proxy --</option>
                                    <?php
            $free_res = db_query("SELECT id, name FROM teachers WHERE id NOT IN (SELECT teacher_id FROM timetable WHERE day_of_week='$day_name' AND period_number=$p) AND id != $absent_teacher_id ORDER BY name");
            while ($ft = mysqli_fetch_assoc($free_res)):
?>
                                    <option value="<?php echo $ft['id']; ?>" <?php echo($current_proxy == $ft['id']) ? 'selected' : ''; ?>>
                                        <?php echo $ft['name']; ?>
                                    </option>
                                    <?php
            endwhile; ?>
                                </select>
                                <button type="submit" name="make_adjustment" class="btn <?php echo $current_proxy ? 'btn-success' : 'btn-primary'; ?>" style="height: 38px; width: 38px; padding: 0; border-radius: 8px;" title="<?php echo $current_proxy ? 'Update' : 'Save'; ?>">
                                    <i class="fas <?php echo $current_proxy ? 'fa-check' : 'fa-save'; ?>"></i>
                                </button>
                                <?php if ($current_proxy): ?>
                                    <button type="submit" name="make_adjustment" onclick="this.form.proxy_teacher_id.value=''" class="btn btn-secondary" style="height: 38px; width: 38px; padding: 0; border-radius: 8px; color: var(--danger);" title="Remove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php
            endif; ?>
                            </form>
                        </div>
                    <?php
        else: ?>
                        <div style="color: var(--success); font-weight: 600; font-size: 0.8rem; background: #f0fdf4; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-coffee"></i> Faculty Free
                        </div>
                    <?php
        endif; ?>
                </div>
            <?php
    endfor; ?>
        </div>
    <?php
else: ?>
        <div style="text-align: center; padding: 5rem 2rem; background: white; border-radius: 16px; border: 1px dashed var(--border);">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fas fa-user-ninja" style="font-size: 2.5rem; color: #cbd5e1;"></i>
            </div>
            <h3 style="font-weight: 800; color: var(--text-main);">Search Absence</h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0.5rem auto 1.5rem;">Select a teacher from the dropdown above to manage their classroom duties for today.</p>
        </div>
    <?php
endif; ?>
</div>

<?php if (isset($msg)): ?>
<div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: var(--text-main); color: white; padding: 0.75rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 0.9rem;">
    <i class="fas fa-check-circle" style="color: var(--success);"></i>
    <span><?php echo $msg; ?></span>
</div>
<script>setTimeout(() => document.getElementById('toast').style.opacity='0', 3000);</script>
<?php
endif; ?>

<?php require_once 'includes/footer.php'; ?>
