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

    db_query("INSERT INTO timetable_adjustments (day_of_week, period_number, class_id, subject_id, original_teacher_id, proxy_teacher_id, adjustment_date) 
              VALUES ('$day', $period, $class_id, $subject_id, $original_id, $proxy_id, '$adj_date') 
              ON DUPLICATE KEY UPDATE proxy_teacher_id = $proxy_id");

    $msg = "Substitution saved successfully for Period $period.";
}

// Get already made adjustments for this date
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
$periods_count = (int)$settings['periods_per_day'];

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

<style>
    .sub-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    .period-card { 
        background: white; border: 1px solid var(--border); border-radius: 16px; 
        padding: 1.5rem; display: flex; align-items: center; gap: 2rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .period-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    .period-num { 
        background: var(--primary); color: white; width: 50px; height: 50px; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 12px; font-weight: 800; font-size: 1.2rem;
    }
    .absent-block { color: #991b1b; background: #fee2e2; padding: 1rem; border-radius: 12px; flex: 1; border: 1px dashed #fecaca; }
    .swap-icon { color: var(--text-muted); font-size: 1.5rem; }
    .proxy-block { flex: 1.5; }
    .teacher-pill { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
    .pill-absent { background: #fee2e2; color: #991b1b; }
    .pill-proxy { background: #dcfce7; color: #166534; }
</style>

<div class="fade-in">
    <div class="card" style="border-bottom: 4px solid var(--primary);">
        <h1 class="card-title" style="margin-bottom: 1.5rem;"><i class="fas fa-user-shield"></i> Substitution Manager</h1>
        <form method="GET" style="display: grid; grid-template-columns: 200px 1fr; gap: 1.5rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-calendar-day"></i> Select Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" onchange="this.form.submit()" style="border-radius: 10px;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-user-slash"></i> Who is Absent today?</label>
                <select name="teacher_id" onchange="this.form.submit()" style="border-radius: 10px; padding: 10px 15px;">
                    <option value="">-- Choose Absent Teacher --</option>
                    <?php foreach ($teachers_list as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo($absent_teacher_id == $t['id']) ? 'selected' : ''; ?>>
                        <?php echo $t['name']; ?> (<?php echo $t['employee_code']; ?>)
                    </option>
                    <?php
endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($absent_teacher_id): ?>
        <div style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 1rem; opacity: 0.8;">
                Substitution list for <b><?php echo $teachers_list[$absent_teacher_id]['name']; ?></b> on <?php echo $day_name; ?>, <?php echo date('d M', strtotime($date)); ?>
            </h2>
            <div class="sub-grid">
                <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                    <div class="period-card">
                        <div class="period-num"><?php echo $p; ?></div>
                        
                        <?php if (isset($absent_routine[$p])):
            $item = $absent_routine[$p];
            $current_proxy = $daily_adjustments[$p][$absent_teacher_id] ?? null;

            $free_teachers_sql = "SELECT * FROM teachers 
                                                 WHERE id NOT IN (
                                                     SELECT teacher_id FROM timetable 
                                                     WHERE day_of_week = '$day_name' AND period_number = $p
                                                 ) 
                                                 AND id NOT IN (
                                                     SELECT teacher_id FROM teacher_restrictions 
                                                     WHERE day_of_week = '$day_name' AND period_number = $p
                                                 )
                                                 AND id != $absent_teacher_id";
            $free_res = db_query($free_teachers_sql);
?>
                            <div class="absent-block">
                                <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; opacity: 0.7;">Was Scheduled:</div>
                                <div style="font-weight: 700; font-size: 1.1rem;"><?php echo $item['subject_name']; ?> @ <?php echo $item['class_name']; ?></div>
                            </div>

                            <div class="swap-icon"><i class="fas fa-arrow-right"></i></div>

                            <div class="proxy-block">
                                <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                    <input type="hidden" name="class_id" value="<?php echo $item['cid']; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo $item['subject_id']; ?>">
                                    <input type="hidden" name="original_teacher_id" value="<?php echo $absent_teacher_id; ?>">
                                    <input type="hidden" name="period" value="<?php echo $p; ?>">
                                    <input type="hidden" name="day" value="<?php echo $day_name; ?>">
                                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                                    
                                    <select name="proxy_teacher_id" style="flex: 1; border-radius: 10px; height: 45px; background: <?php echo $current_proxy ? '#f0fdf4' : 'white'; ?>; border-color: <?php echo $current_proxy ? '#22c55e' : 'var(--border)'; ?>;" required>
                                        <option value="">-- Select Available Substitute --</option>
                                        <?php while ($ft = mysqli_fetch_assoc($free_res)): ?>
                                        <option value="<?php echo $ft['id']; ?>" <?php echo($current_proxy == $ft['id']) ? 'selected' : ''; ?>>
                                            <?php echo $ft['name']; ?> (Free)
                                        </option>
                                        <?php
            endwhile; ?>
                                    </select>
                                    
                                    <button type="submit" name="make_adjustment" class="btn btn-primary" style="padding: 10px 20px; border-radius: 10px;">
                                        <i class="fas <?php echo $current_proxy ? 'fa-check-circle' : 'fa-exchange-alt'; ?>"></i>
                                        <?php echo $current_proxy ? 'Update' : 'Assign'; ?>
                                    </button>
                                </form>
                                <?php if ($current_proxy): ?>
                                    <div style="font-size: 0.8rem; margin-top: 8px; color: var(--success); font-weight: 600;">
                                        <i class="fas fa-info-circle"></i> Assigned to: <?php echo $teachers_list[$current_proxy]['name']; ?>
                                    </div>
                                <?php
            endif; ?>
                            </div>
                        <?php
        else: ?>
                            <div style="flex: 1; text-align: center; color: var(--text-muted); font-style: italic;">
                                Free/Leisure Period (No routine assigned)
                            </div>
                        <?php
        endif; ?>
                    </div>
                <?php
    endfor; ?>
            </div>
        </div>
    <?php
else: ?>
        <div style="text-align: center; padding: 4rem 2rem; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 20px;">
            <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3>No Teacher Selected</h3>
            <p style="color: var(--text-muted);">Please select a teacher from the dropdown above to manage their substitutions for <?php echo $day_name; ?>.</p>
        </div>
    <?php
endif; ?>

    <?php if (isset($msg)): ?>
    <div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: #166534; color: white; padding: 1rem 2rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000; display: flex; align-items: center; gap: 10px; animation: slideUp 0.3s ease-out;">
        <i class="fas fa-check-circle" style="font-size: 1.4rem;"></i>
        <span><?php echo $msg; ?></span>
    </div>
    <script>setTimeout(() => document.getElementById('toast').style.display='none', 4000);</script>
    <style>@keyframes slideUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }</style>
    <?php
endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
