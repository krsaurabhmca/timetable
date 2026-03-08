<?php
require_once 'config.php';

$type = $_GET['type'] ?? 'class'; // class or teacher
$id = $_GET['id'] ?? null;

// Fetch Filters
$classes = db_query("SELECT * FROM classes ORDER BY class_name");
$teachers = db_query("SELECT * FROM teachers ORDER BY name");

$today_date = date('Y-m-d');
$today_day = date('l');
$adjustments = [];
if ($id) {
    if ($type == 'class') {
        $adj_res = db_query("SELECT ta.*, tea.name as proxy_name 
                            FROM timetable_adjustments ta
                            JOIN teachers tea ON ta.proxy_teacher_id = tea.id
                            WHERE ta.class_id = $id AND ta.adjustment_date = '$today_date'");
        while ($adj = mysqli_fetch_assoc($adj_res))
            $adjustments[$adj['day_of_week']][$adj['period_number']] = $adj;
    }
    else {
        // Teacher view: adjustments where this teacher is proxy or original
        $adj_res = db_query("SELECT ta.*, c.class_name, tea_orig.name as original_name, tea_proxy.name as proxy_name
                            FROM timetable_adjustments ta
                            JOIN classes c ON ta.class_id = c.id
                            JOIN teachers tea_orig ON ta.original_teacher_id = tea_orig.id
                            JOIN teachers tea_proxy ON ta.proxy_teacher_id = tea_proxy.id
                            WHERE (ta.original_teacher_id = $id OR ta.proxy_teacher_id = $id) AND ta.adjustment_date = '$today_date'");
        while ($adj = mysqli_fetch_assoc($adj_res))
            $adjustments[$adj['day_of_week']][$adj['period_number']] = $adj;
    }
}

$settings_res = db_query("SELECT * FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];
$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];

$current_view_title = "Select a Routine to View";
$schedule = [];

if ($id) {
    if ($type == 'class') {
        $res = db_query("SELECT t.*, s.subject_name, tea.name as teacher_name 
                        FROM timetable t 
                        JOIN subjects s ON t.subject_id = s.id 
                        JOIN teachers tea ON t.teacher_id = tea.id 
                        WHERE t.class_id = $id");
        $class_data = mysqli_fetch_assoc(db_query("SELECT * FROM classes WHERE id = $id"));
        $current_view_title = "Class: " . $class_data['class_name'];
    }
    else {
        $res = db_query("SELECT t.*, s.subject_name, c.class_name 
                        FROM timetable t 
                        JOIN subjects s ON t.subject_id = s.id 
                        JOIN classes c ON t.class_id = c.id 
                        WHERE t.teacher_id = $id");
        $teacher_data = mysqli_fetch_assoc(db_query("SELECT * FROM teachers WHERE id = $id"));
        $current_view_title = "Teacher: " . $teacher_data['name'];
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $schedule[$row['day_of_week']][$row['period_number']] = $row;
    }
}

require_once 'includes/header.php';
?>

<div class="fade-in">
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="card-title" style="margin-bottom: 0;"><?php echo $current_view_title; ?></h1>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <select onchange="location.href='?type=class&id='+this.value">
                <option value="">-- View Class Routine --</option>
                <?php mysqli_data_seek($classes, 0);
while ($c = mysqli_fetch_assoc($classes)): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo($type == 'class' && $id == $c['id']) ? 'selected' : ''; ?>>
                    <?php echo $c['class_name']; ?>
                </option>
                <?php
endwhile; ?>
            </select>
            
            <select onchange="location.href='?type=teacher&id='+this.value">
                <option value="">-- View Teacher Routine --</option>
                <?php mysqli_data_seek($teachers, 0);
while ($t = mysqli_fetch_assoc($teachers)): ?>
                <option value="<?php echo $t['id']; ?>" <?php echo($type == 'teacher' && $id == $t['id']) ? 'selected' : ''; ?>>
                    <?php echo $t['name']; ?>
                </option>
                <?php
endwhile; ?>
            </select>

            <button onclick="window.print();" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <?php if ($id): ?>
    <div class="card" style="padding: 0; overflow: hidden; border: none;">
        <div class="timetable-grid" style="grid-template-columns: 120px repeat(<?php echo count($working_days); ?>, 1fr);">
            <!-- Headers -->
            <div class="grid-header" style="background:var(--primary); color:white;">Period / Day</div>
            <?php foreach ($working_days as $day): ?>
            <div class="grid-header" style="background:var(--primary); color:white;"><?php echo $day; ?></div>
            <?php
    endforeach; ?>

            <!-- Rows -->
            <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                <div class="grid-header" style="display:flex; align-items:center; justify-content:center; background:#f1f5f9;">
                    <strong>Period <?php echo $p; ?></strong>
                </div>
                <?php foreach ($working_days as $day): ?>
                <div class="grid-cell">
                    <?php
            $item = $schedule[$day][$p] ?? null;
            $adj = $adjustments[$day][$p] ?? null;

            if ($item || $adj):
                $color_idx = $item ? ($item['subject_id'] % 8) + 1 : 9;
                $color_class = "sub-color-" . $color_idx;
?>
                    <div class="period-slot <?php echo $color_class; ?> <?php echo $adj ? 'adjustment' : ''; ?>" style="<?php echo($adj && $type == 'teacher' && $adj['original_teacher_id'] == $id) ? 'opacity: 0.5; border: 1px dashed red;' : ''; ?>">
                        <?php if ($adj && $type == 'class'): ?>
                            <div style="font-weight: 800; border-bottom: 1px solid rgba(0,0,0,0.1); margin-bottom: 4px; font-size: 0.8rem;"><i class="fas fa-exchange-alt"></i> Proxy</div>
                            <span class="subject"><?php echo $item['subject_name']; ?></span>
                            <span class="teacher" style="font-size: 0.72rem; color: var(--success); font-weight: 700;">
                                <?php echo $adj['proxy_name']; ?>
                            </span>
                        <?php
                elseif ($adj && $type == 'teacher' && $adj['proxy_teacher_id'] == $id): ?>
                            <div style="font-weight: 800; border-bottom: 1px solid rgba(0,0,0,0.1); margin-bottom: 4px; font-size: 0.8rem;"><i class="fas fa-user-shield"></i> Proxy Duty</div>
                            <span class="subject"><?php echo $adj['subject_name'] ?? 'Substitution'; ?></span>
                            <span class="teacher" style="font-size: 0.72rem; font-weight: 700;">
                                <?php echo $adj['class_name']; ?>
                            </span>
                        <?php
                elseif ($adj && $type == 'teacher' && $adj['original_teacher_id'] == $id): ?>
                            <span class="subject" style="text-decoration: line-through;"><?php echo $item['subject_name']; ?></span>
                            <span class="teacher" style="font-size: 0.72rem; color: #ef4444;">ABSENT</span>
                        <?php
                elseif ($item): ?>
                            <span class="subject"><?php echo $item['subject_name']; ?></span>
                            <span class="teacher" style="font-size: 0.72rem;">
                                <i class="fas fa-<?php echo($type == 'class') ? 'chalkboard-teacher' : 'school'; ?>"></i>
                                <?php echo($type == 'class') ? $item['teacher_name'] : $item['class_name']; ?>
                            </span>
                        <?php
                endif; ?>
                    </div>
                    <?php
            endif; ?>
                </div>
                <?php
        endforeach; ?>
            <?php
    endfor; ?>
        </div>
    </div>
    <?php
else: ?>
    <div class="card" style="text-align: center; padding: 4rem;">
        <i class="fas fa-search" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
        <p style="color: var(--text-muted);">Please select a class or teacher from the dropdown above to view the routine.</p>
    </div>
    <?php
endif; ?>
</div>

<style>
@media print {
    header, .btn, select, footer { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; padding: 0 !important; }
    .container { max-width: 100% !important; padding: 10px !important; }
    .timetable-grid { background: white !important; }
    .grid-header { background: #eee !important; color: black !important; -webkit-print-color-adjust: exact; }
    .grid-cell { min-height: 80px !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
