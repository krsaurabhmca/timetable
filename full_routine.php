<?php
require_once 'config.php';

$settings_res = db_query("SELECT * FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];
$sat_periods = (int)($settings['saturday_periods'] ?? 4);

$classes_res = db_query("SELECT * FROM classes ORDER BY class_name");
$classes = [];
while ($row = mysqli_fetch_assoc($classes_res))
    $classes[] = $row;

$today_date = date('Y-m-d');
$today_day = date('l');
$adjustments = [];
if ($selected_day == $today_day) {
    $adj_res = db_query("SELECT ta.*, tea.name as proxy_name 
                        FROM timetable_adjustments ta
                        JOIN teachers tea ON ta.proxy_teacher_id = tea.id
                        WHERE ta.adjustment_date = '$today_date'");
    while ($adj = mysqli_fetch_assoc($adj_res)) {
        $adjustments[$adj['period_number']][$adj['class_id']] = $adj['proxy_name'];
    }
}

$timetable_res = db_query("SELECT t.*, s.subject_name, tea.name as teacher_name, c.class_name 
                           FROM timetable t 
                           JOIN subjects s ON t.subject_id = s.id 
                           JOIN teachers tea ON t.teacher_id = tea.id
                           JOIN classes c ON t.class_id = c.id
                           ORDER BY t.day_of_week, t.period_number, c.class_name");

$routine = []; // [day][period][class_id] = data
while ($row = mysqli_fetch_assoc($timetable_res)) {
    $routine[$row['day_of_week']][$row['period_number']][$row['class_id']] = $row;
}

$selected_day = $_GET['day'] ?? ($working_days[0] ?? 'Monday');

require_once 'includes/header.php';
?>

<div class="fade-in">
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="card-title" style="margin-bottom: 0;">Full School Routine</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Overview of all classes and periods</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <div class="btn-group" style="display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                <?php foreach ($working_days as $day): ?>
                    <a href="?day=<?php echo $day; ?>" 
                       class="btn <?php echo $selected_day == $day ? 'btn-primary' : ''; ?>" 
                       style="padding: 0.5rem 1rem; border-radius: 6px; <?php echo $selected_day != $day ? 'background:transparent; color: var(--text);' : ''; ?>">
                       <?php echo $day; ?>
                    </a>
                <?php
endforeach; ?>
            </div>
            <button onclick="window.print();" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow-x: auto; border: none;">
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr>
                    <th style="padding: 1rem; background: var(--primary); color: white; border-right: 1px solid rgba(255,255,255,0.1); position: sticky; left: 0; z-index: 10;">Class \ Period</th>
                    <?php
$current_day_periods = ($selected_day == 'Saturday') ? $sat_periods : $periods_count;
$lunch_after = (int)($settings['lunch_after_period'] ?? 0);

for ($p = 1; $p <= $current_day_periods; $p++):
?>
                        <th style="padding: 1rem; background: var(--primary); color: white; border-right: 1px solid rgba(255,255,255,0.1);">Period <?php echo $p; ?></th>
                        <?php if ($p == $lunch_after): ?>
                            <th style="padding: 1rem; background: #64748b; color: white;">LUNCH</th>
                        <?php
    endif; ?>
                    <?php
endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                <tr>
                    <td style="padding: 1rem; background: #f8fafc; font-weight: 600; border: 1px solid var(--border); position: sticky; left: 0; z-index: 5;">
                        <?php echo $class['class_name']; ?>
                    </td>
                    <?php for ($p = 1; $p <= $current_day_periods; $p++): ?>
                        <td style="padding: 0.5rem; border: 1px solid var(--border); min-width: 120px; vertical-align: top;">
                            <?php if (isset($routine[$selected_day][$p][$class['id']])):
            $item = $routine[$selected_day][$p][$class['id']];
            $color_idx = ($item['subject_id'] % 8) + 1;
?>
                                <div style="background: var(--bg-surface); padding: 8px; border-radius: 6px; border-left: 4px solid var(--sub-<?php echo $color_idx; ?>-color, #3b82f6); box-shadow: 0 1px 2px rgba(0,0,0,0.05); position: relative;">
                                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text);"><?php echo $item['subject_name']; ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                        <?php if (isset($adjustments[$p][$class['id']])): ?>
                                            <i class="fas fa-exchange-alt" style="color: var(--success);"></i> 
                                            <span style="color: var(--success); font-weight: 600;"><?php echo $adjustments[$p][$class['id']]; ?></span>
                                            <br><small style="text-decoration: line-through; opacity: 0.5;"><?php echo $item['teacher_name']; ?></small>
                                        <?php
            else: ?>
                                            <i class="fas fa-user-tie"></i> <?php echo $item['teacher_name']; ?>
                                        <?php
            endif; ?>
                                    </div>
                                </div>
                            <?php
        else: ?>
                                <div style="color: #cbd5e1; font-size: 0.75rem; text-align: center; padding: 10px;">Free</div>
                            <?php
        endif; ?>
                        </td>
                        <?php if ($p == $lunch_after): ?>
                            <td style="background: #f1f5f9; text-align: center; vertical-align: middle; font-weight: bold; color: #64748b; border: 1px solid var(--border);">LUNCH</td>
                        <?php
        endif; ?>
                    <?php
    endfor; ?>
                </tr>
                <?php
endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
:root {
    --sub-1-color: #3b82f6;
    --sub-2-color: #10b981;
    --sub-3-color: #f59e0b;
    --sub-4-color: #ef4444;
    --sub-5-color: #8b5cf6;
    --sub-6-color: #ec4899;
    --sub-7-color: #06b6d4;
    --sub-8-color: #f97316;
}
@media print {
    header, .btn, .btn-group, footer { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; padding: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    table { font-size: 10px; }
    th, td { padding: 4px !important; }
    body { background: white !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
