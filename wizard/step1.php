<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $working_days = implode(',', $_POST['working_days']);
    $periods_per_day = db_escape($_POST['periods_per_day']);
    $saturday_periods = db_escape($_POST['saturday_periods'] ?? 4);
    $period_duration = db_escape($_POST['period_duration']);
    $max_cont = db_escape($_POST['max_continuous_periods'] ?? 2);
    $sched_type = db_escape($_POST['schedule_type'] ?? 'different');
    $restrict_ct = db_escape($_POST['restrict_class_teacher_1st_period'] ?? 'no');

    $lunch_after = db_escape($_POST['lunch_after_period'] ?? 0);

    db_query("UPDATE settings SET value = '$working_days' WHERE `key` = 'working_days'");
    db_query("UPDATE settings SET value = '$periods_per_day' WHERE `key` = 'periods_per_day'");
    db_query("INSERT INTO settings (`key`, `value`) VALUES ('saturday_periods', '$saturday_periods') ON DUPLICATE KEY UPDATE value = '$saturday_periods'");
    db_query("UPDATE settings SET value = '$period_duration' WHERE `key` = 'period_duration'");
    db_query("INSERT INTO settings (`key`, `value`) VALUES ('max_continuous_periods', '$max_cont') ON DUPLICATE KEY UPDATE value = '$max_cont'");
    db_query("INSERT INTO settings (`key`, `value`) VALUES ('schedule_type', '$sched_type') ON DUPLICATE KEY UPDATE value = '$sched_type'");
    db_query("INSERT INTO settings (`key`, `value`) VALUES ('restrict_class_teacher_1st_period', '$restrict_ct') ON DUPLICATE KEY UPDATE value = '$restrict_ct'");
    db_query("INSERT INTO settings (`key`, `value`) VALUES ('lunch_after_period', '$lunch_after') ON DUPLICATE KEY UPDATE value = '$lunch_after'");

    header("Location: step2.php");
    exit;
}

// Fetch current settings
$settings = [];
$res = db_query("SELECT * FROM settings");
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

<div class="card fade-in">
    <h2 class="card-title">Step 1: General Settings</h2>
    <form method="POST">
        <div class="form-group">
            <label>Working Days</label>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $day):
?>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                    <input type="checkbox" name="working_days[]" value="<?php echo $day; ?>" 
                    <?php echo in_array($day, $current_days) ? 'checked' : ''; ?>> 
                    <?php echo $day; ?>
                </label>
                <?php
endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="periods_per_day">Number of Periods per Day (Default)</label>
            <input type="number" name="periods_per_day" id="periods_per_day" value="<?php echo $settings['periods_per_day']; ?>" min="1" max="15">
        </div>

        <div class="form-group" id="saturday_periods_group" <?php echo !in_array('Saturday', $current_days) ? 'style="display:none;"' : ''; ?>>
            <label for="saturday_periods">Number of Periods on Saturday</label>
            <input type="number" name="saturday_periods" id="saturday_periods" value="<?php echo $settings['saturday_periods'] ?? 4; ?>" min="1" max="15">
        </div>

        <div class="form-group">
            <label for="period_duration">Period Duration (Minutes)</label>
            <input type="number" name="period_duration" id="period_duration" value="<?php echo $settings['period_duration']; ?>" min="15" max="120">
        </div>

        <div class="form-group">
            <label for="max_continuous_periods">Max Continuous Periods for a Subject</label>
            <input type="number" name="max_continuous_periods" id="max_continuous_periods" value="<?php echo $settings['max_continuous_periods'] ?? 2; ?>" min="1" max="5">
            <small style="color: var(--text-muted);">Limits how many times a subject can repeat back-to-back in a class.</small>
        </div>

        <div class="form-group">
            <label for="schedule_type">Routine Type</label>
            <select name="schedule_type" id="schedule_type">
                <option value="different" <?php echo($settings['schedule_type'] ?? 'different') == 'different' ? 'selected' : ''; ?>>Different Schedule every day</option>
                <option value="same" <?php echo($settings['schedule_type'] ?? '') == 'same' ? 'selected' : ''; ?>>Same Schedule every day</option>
            </select>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="restrict_class_teacher_1st_period" value="yes" <?php echo($settings['restrict_class_teacher_1st_period'] ?? 'no') == 'yes' ? 'checked' : ''; ?>>
                Restrict 1st Period to Class Teacher
            </label>
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">If enabled, the first period of every day will be assigned to the teacher who is the "Class Teacher" of that class.</small>
        </div>

        <div class="form-group">
            <label for="lunch_after_period">Lunch Break After Period</label>
            <select name="lunch_after_period" id="lunch_after_period">
                <option value="0">No Lunch Break</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo($settings['lunch_after_period'] ?? '') == $i ? 'selected' : ''; ?>>After Period <?php echo $i; ?></option>
                <?php
endfor; ?>
            </select>
            <small style="color: var(--text-muted);">A "LUNCH" slot will be inserted into the routine after this period.</small>
        </div>

        <script>
            document.querySelectorAll('input[name="working_days[]"]').forEach(cb => {
                cb.addEventListener('change', function() {
                    const satChecked = Array.from(document.querySelectorAll('input[name="working_days[]"]:checked'))
                        .some(i => i.value === 'Saturday');
                    document.getElementById('saturday_periods_group').style.display = satChecked ? 'block' : 'none';
                });
            });
        </script>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">
                Save & Next <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
