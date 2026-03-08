<?php
require_once '../config.php';

// Handle Restriction Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_restrictions'])) {
    $teacher_id = db_escape($_POST['teacher_id']);
    // Clear existing for this teacher
    db_query("DELETE FROM teacher_restrictions WHERE teacher_id = $teacher_id");

    if (isset($_POST['blocked_slots'])) {
        foreach ($_POST['blocked_slots'] as $slot) {
            list($day, $period) = explode('|', $slot);
            $day = db_escape($day);
            $period = db_escape($period);
            db_query("INSERT INTO teacher_restrictions (teacher_id, day_of_week, period_number) VALUES ($teacher_id, '$day', $period)");
        }
    }
}

$teachers = db_query("SELECT * FROM teachers ORDER BY name");
$settings_res = db_query("SELECT * FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res)) {
    $settings[$row['key']] = $row['value'];
}
$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">General</div>
    </div>
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">Classes & Subs</div>
    </div>
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">Mapping</div>
    </div>
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">Teachers</div>
    </div>
    <div class="step active">
        <div class="step-circle">5</div>
        <div class="step-label">Constraints</div>
    </div>
    <div class="step">
        <div class="step-circle">6</div>
        <div class="step-label">Generate</div>
    </div>
</div>

<div class="card fade-in">
    <h2 class="card-title">Teacher Availability Restrictions</h2>
    <p style="color:var(--text-muted); margin-bottom: 1.5rem;">Mark the periods where a teacher is NOT available (e.g., part-time teachers, external duties).</p>
    
    <div class="accordion" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php while ($t = mysqli_fetch_assoc($teachers)):
    $tid = $t['id'];
    $restrictions = [];
    $res_res = db_query("SELECT * FROM teacher_restrictions WHERE teacher_id = $tid");
    while ($r = mysqli_fetch_assoc($res_res)) {
        $restrictions[] = $r['day_of_week'] . '|' . $r['period_number'];
    }
?>
        <details class="card" style="padding: 1rem; margin-bottom: 0;">
            <summary style="font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-user"></i> <?php echo $t['name']; ?></span>
                <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted);">
                    Click to manage restrictions (<?php echo count($restrictions); ?> blocked)
                </span>
            </summary>
            
            <form method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="teacher_id" value="<?php echo $tid; ?>">
                <div class="table-responsive">
                    <table style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                                <th style="text-align: center;">P<?php echo $p; ?></th>
                                <?php
    endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($working_days as $day): ?>
                            <tr>
                                <td><strong><?php echo $day; ?></strong></td>
                                <?php for ($p = 1; $p <= $periods_count; $p++):
            $slot_id = $day . '|' . $p;
            $checked = in_array($slot_id, $restrictions) ? 'checked' : '';
?>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="blocked_slots[]" value="<?php echo $slot_id; ?>" <?php echo $checked; ?>>
                                </td>
                                <?php
        endfor; ?>
                            </tr>
                            <?php
    endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem; text-align: right;">
                    <button type="submit" name="save_restrictions" class="btn btn-secondary">
                        <i class="fas fa-save"></i> Save for <?php echo $t['name']; ?>
                    </button>
                </div>
            </form>
        </details>
        <?php
endwhile; ?>
    </div>
</div>

<div style="display: flex; justify-content: space-between; margin-top: 2rem;">
    <a href="step3.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="step5.php" class="btn btn-primary">Generate Final Timetable <i class="fas fa-magic"></i></a>
</div>

<?php require_once '../includes/footer.php'; ?>
