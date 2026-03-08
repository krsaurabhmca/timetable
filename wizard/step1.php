<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $working_days = isset($_POST['working_days']) ? implode(',', $_POST['working_days']) : '';
    $periods_per_day = db_escape($_POST['periods_per_day']);
    $saturday_periods = db_escape($_POST['saturday_periods'] ?? 4);
    $period_duration = db_escape($_POST['period_duration']);
    $max_cont = db_escape($_POST['max_continuous_periods'] ?? 2);
    $sched_type = db_escape($_POST['schedule_type'] ?? 'different');
    $restrict_ct = db_escape($_POST['restrict_class_teacher_1st_period'] ?? 'no');
    $lunch_after = db_escape($_POST['lunch_after_period'] ?? 0);

    db_query("UPDATE settings SET value = '$working_days' WHERE `key` = 'working_days' AND org_id = '$org_id'");
    db_query("UPDATE settings SET value = '$periods_per_day' WHERE `key` = 'periods_per_day' AND org_id = '$org_id'");
    db_query("INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('saturday_periods', '$org_id', '$saturday_periods') ON DUPLICATE KEY UPDATE value = '$saturday_periods'");
    db_query("UPDATE settings SET value = '$period_duration' WHERE `key` = 'period_duration' AND org_id = '$org_id'");
    db_query("INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('max_continuous_periods', '$org_id', '$max_cont') ON DUPLICATE KEY UPDATE value = '$max_cont'");
    db_query("INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('schedule_type', '$org_id', '$sched_type') ON DUPLICATE KEY UPDATE value = '$sched_type'");
    db_query("INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('restrict_class_teacher_1st_period', '$org_id', '$restrict_ct') ON DUPLICATE KEY UPDATE value = '$restrict_ct'");
    db_query("INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('lunch_after_period', '$org_id', '$lunch_after') ON DUPLICATE KEY UPDATE value = '$lunch_after'");

    header("Location: step2.php");
    exit;
}

// Fetch current settings
$settings = [];
$res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
while ($row = mysqli_fetch_assoc($res)) {
    $settings[$row['key']] = $row['value'];
}
$current_days = explode(',', $settings['working_days'] ?? '');

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step active">
        <div class="step-circle">1</div>
        <div class="step-label">General</div>
    </div>
    <div class="step">
        <div class="step-circle">2</div>
        <div class="step-label">Master Data</div>
    </div>
    <div class="step">
        <div class="step-circle">3</div>
        <div class="step-label">Teachers</div>
    </div>
    <div class="step">
        <div class="step-circle">4</div>
        <div class="step-label">Constraints</div>
    </div>
    <div class="step">
        <div class="step-circle">5</div>
        <div class="step-label">Generate</div>
    </div>
</div>

<div class="fade-in">
    <div class="card" style="margin-bottom: 2rem;">
        <h2 class="card-title" style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-cog" style="color: var(--primary);"></i> Step 1: Configuration
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: -10px; margin-bottom: 20px;">Define the basic structure of your school week.</p>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                
                <!-- Working Days Section -->
                <div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.25rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-alt" style="color: var(--primary);"></i> Academic Week
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px;">
                        <?php
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $day):
    $checked = in_array($day, $current_days);
?>
                        <label style="display: flex; align-items: center; gap: 6px; padding: 8px; border-radius: 6px; border: 1px solid <?php echo $checked ? 'var(--primary)' : '#e2e8f0'; ?>; background: <?php echo $checked ? '#eff6ff' : 'white'; ?>; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                            <input type="checkbox" name="working_days[]" value="<?php echo $day; ?>" 
                            <?php echo $checked ? 'checked' : ''; ?> style="accent-color: var(--primary);"> 
                            <?php echo $day; ?>
                        </label>
                        <?php
endforeach; ?>
                    </div>
                </div>

                <!-- Daily Schedule Section -->
                <div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.25rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock" style="color: var(--primary);"></i> Day Structure
                    </h3>
                    <div style="display: grid; gap: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.85rem; font-weight: 600;">Standard Periods/Day</label>
                            <input type="number" name="periods_per_day" value="<?php echo $settings['periods_per_day']; ?>" min="1" max="15" style="width: 70px; padding: 6px;">
                        </div>
                        <div id="sat_group" style="display: <?php echo in_array('Saturday', $current_days) ? 'flex' : 'none'; ?>; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.85rem; font-weight: 600;">Periods (Saturday)</label>
                            <input type="number" name="saturday_periods" value="<?php echo $settings['saturday_periods'] ?? 4; ?>" min="1" max="15" style="width: 70px; padding: 6px;">
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.85rem; font-weight: 600;">Duration (Mins)</label>
                            <input type="number" name="period_duration" value="<?php echo $settings['period_duration']; ?>" min="15" max="120" style="width: 70px; padding: 6px;">
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.85rem; font-weight: 600;">Lunch After Period</label>
                            <select name="lunch_after_period" style="width: 120px; padding: 6px;">
                                <option value="0">None</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo($settings['lunch_after_period'] ?? '') == $i ? 'selected' : ''; ?>>After P<?php echo $i; ?></option>
                                <?php
endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Logic Controls Section -->
                <div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.25rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-brain" style="color: var(--primary);"></i> Generation Logic
                    </h3>
                    <div style="display: grid; gap: 12px;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Routine Consistency</label>
                            <select name="schedule_type" style="width: 100%; padding: 8px;">
                                <option value="different" <?php echo($settings['schedule_type'] ?? 'different') == 'different' ? 'selected' : ''; ?>>Different every day</option>
                                <option value="same" <?php echo($settings['schedule_type'] ?? '') == 'same' ? 'selected' : ''; ?>>Uniform (Same every day)</option>
                            </select>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <label style="font-size: 0.85rem; font-weight: 600;">Max Continuous</label>
                            <input type="number" name="max_continuous_periods" value="<?php echo $settings['max_continuous_periods'] ?? 2; ?>" min="1" max="5" style="width: 70px; padding: 6px;">
                        </div>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                            <input type="checkbox" name="restrict_class_teacher_1st_period" value="yes" <?php echo($settings['restrict_class_teacher_1st_period'] ?? 'no') == 'yes' ? 'checked' : ''; ?> style="accent-color: var(--primary);">
                            <span>Force Class Teacher in 1st Period</span>
                        </label>
                    </div>
                </div>

            </div>

            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-weight: 700; border-radius: 12px;">
                    Save Settings & Continue <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('input[name="working_days[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            // Visual feedback
            const label = this.closest('label');
            if(this.checked) {
                label.style.borderColor = 'var(--primary)';
                label.style.background = '#eff6ff';
            } else {
                label.style.borderColor = '#e2e8f0';
                label.style.background = 'white';
            }

            // Toggle Saturday periods
            const isSat = this.value === 'Saturday';
            const satChecked = Array.from(document.querySelectorAll('input[name="working_days[]"]:checked'))
                .some(i => i.value === 'Saturday');
            document.getElementById('sat_group').style.display = satChecked ? 'flex' : 'none';
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
