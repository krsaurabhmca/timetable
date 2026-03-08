<?php
require_once 'config.php';

// Fetch all teachers and their weekly loads
$teachers_res = db_query("SELECT t.*, 
                           (SELECT COUNT(*) FROM timetable WHERE teacher_id = t.id AND org_id = '$org_id') as current_load 
                           FROM teachers t WHERE t.org_id = '$org_id' ORDER BY name");
$teachers = [];
while ($row = mysqli_fetch_assoc($teachers_res))
    $teachers[] = $row;

// Fetch all classes and their subject coverage
$classes_res = db_query("SELECT * FROM classes WHERE org_id = '$org_id' ORDER BY class_name, section");
$classes = [];
while ($row = mysqli_fetch_assoc($classes_res))
    $classes[] = $row;

$settings_res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];
$sat_periods = (int)($settings['saturday_periods'] ?? 4);

$total_weekly_periods = 0;
foreach ($working_days as $day) {
    if ($day == 'Saturday')
        $total_weekly_periods += $sat_periods;
    else
        $total_weekly_periods += $periods_count;
}

require_once 'includes/header.php';
?>

<div class="fade-in">
    <h1 class="card-title" style="margin-bottom: 2rem;">Analysis Report</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
        <!-- Teacher Analysis -->
        <div class="card">
            <h2 class="card-title" style="font-size: 1.25rem;">
                <i class="fas fa-chalkboard-teacher" style="color: var(--primary);"></i> Teacher / Period Ratio
            </h2>
            <div class="table-responsive">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 0.75rem;">Teacher</th>
                            <th style="text-align: center; padding: 0.75rem;">Assigned</th>
                            <th style="text-align: center; padding: 0.75rem;">Limit</th>
                            <th style="text-align: left; padding: 0.75rem;">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $t):
    $ratio = $t['weekly_limit'] > 0 ? ($t['current_load'] / $t['weekly_limit']) * 100 : 0;
    $color = $ratio > 100 ? 'var(--danger)' : ($ratio > 80 ? 'var(--warning)' : 'var(--success)');
?>
                        <tr>
                            <td style="padding: 0.75rem;"><strong><?php echo $t['name']; ?></strong><br><small><?php echo $t['employee_code']; ?></small></td>
                            <td style="text-align: center;"><?php echo $t['current_load']; ?></td>
                            <td style="text-align: center;"><?php echo $t['weekly_limit']; ?></td>
                            <td style="padding: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo min(100, $ratio); ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
                                    </div>
                                    <span style="font-size: 0.8rem; font-weight: 600; min-width: 40px;"><?php echo round($ratio); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Class Analysis -->
        <div class="card">
            <h2 class="card-title" style="font-size: 1.25rem;">
                <i class="fas fa-school" style="color: var(--primary);"></i> Class & Subject Coverage
            </h2>
            <div class="table-responsive">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 0.75rem;">Class</th>
                            <th style="text-align: center; padding: 0.75rem;">Filled Slots</th>
                            <th style="text-align: center; padding: 0.75rem;">Total Possible</th>
                            <th style="text-align: center; padding: 0.75rem;">Subjects</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c):
    $cid = $c['id'];
    $filled = mysqli_fetch_assoc(db_query("SELECT COUNT(*) as count FROM timetable WHERE class_id = $cid AND org_id = '$org_id'"))['count'];
    $subs_count = mysqli_fetch_assoc(db_query("SELECT COUNT(DISTINCT subject_id) as count FROM teacher_assignments WHERE class_id = $cid AND org_id = '$org_id'"))['count'];
    $coverage = ($total_weekly_periods > 0) ? ($filled / $total_weekly_periods) * 100 : 0;
?>
                        <tr>
                            <td style="padding: 0.75rem;"><strong><?php echo $c['class_name']; ?></strong></td>
                            <td style="text-align: center;"><?php echo $filled; ?></td>
                            <td style="text-align: center;"><?php echo $total_weekly_periods; ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="background:#f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;"><?php echo $subs_count; ?> Subjects</span>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subject Wise Analysis -->
    <div class="card" style="margin-top: 2rem;">
        <h2 class="card-title" style="font-size: 1.25rem;">
            <i class="fas fa-book-open" style="color: var(--primary);"></i> Subject-Wise Distribution
        </h2>
        <div class="table-responsive">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.75rem;">Subject Name</th>
                        <th style="text-align: center; padding: 0.75rem;">Classes Covered</th>
                        <th style="text-align: center; padding: 0.75rem;">Total Periods / Week</th>
                        <th style="text-align: center; padding: 0.75rem;">Global Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$subjects_res = db_query("SELECT s.*, 
                                                (SELECT COUNT(DISTINCT class_id) FROM teacher_assignments WHERE subject_id = s.id AND org_id = '$org_id') as class_count,
                                                (SELECT COUNT(*) FROM timetable WHERE subject_id = s.id AND org_id = '$org_id') as total_periods
                                                FROM subjects s WHERE s.org_id = '$org_id' ORDER BY total_periods DESC");

$allocated_total_res = db_query("SELECT COUNT(*) as count FROM timetable WHERE org_id = '$org_id'");
$grand_total_allocated = mysqli_fetch_assoc($allocated_total_res)['count'] ?? 1;

while ($s = mysqli_fetch_assoc($subjects_res)):
    $share = ($grand_total_allocated > 0) ? round(($s['total_periods'] / $grand_total_allocated) * 100, 1) : 0;
?>
                    <tr>
                        <td style="padding: 0.75rem; font-weight: 700; color: var(--text-main);"><?php echo $s['subject_name']; ?></td>
                        <td style="text-align: center;">
                            <span style="background: #eff6ff; color: var(--primary); padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem;">
                                <?php echo $s['class_count']; ?> Classes
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 600;"><?php echo $s['total_periods']; ?> Slots</td>
                        <td style="text-align: center;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <div style="width: 60px; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $share; ?>%; height: 100%; background: var(--primary);"></div>
                                </div>
                                <span style="font-weight: 700; color: var(--text-muted); font-size: 0.8rem;"><?php echo $share; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php
endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Per Class Subject Distribution -->
    <div class="card" style="margin-top: 2rem;">
        <h2 class="card-title" style="font-size: 1.25rem;">
            <i class="fas fa-chart-pie" style="color: var(--primary);"></i> Per-Class Subject Analysis
        </h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Weekly period distribution and load share for each class.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <?php foreach ($classes as $c):
    $cid = $c['id'];
    // Get subjects and their counts for this class
    $class_subs = db_query("SELECT s.subject_name, tea.name as teacher_name, s.color,
                                       (SELECT COUNT(*) FROM timetable WHERE class_id = $cid AND subject_id = s.id AND org_id = '$org_id') as count
                                       FROM teacher_assignments ta
                                       JOIN subjects s ON ta.subject_id = s.id
                                       JOIN teachers tea ON ta.teacher_id = tea.id
                                       WHERE ta.class_id = $cid AND ta.org_id = '$org_id'
                                       ORDER BY count DESC");
?>
            <div class="card" style="padding: 1rem; border: 1px solid #e2e8f0; background: #fff;">
                <h4 style="margin: 0 0 12px 0; border-bottom: 2px solid var(--primary); padding-bottom: 8px; font-size: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    Class <?php echo $c['class_name']; ?>
                    <span style="font-size: 0.7rem; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; color: #64748b; font-weight: 500;">
                        Max: <?php echo $total_weekly_periods; ?> Periods
                    </span>
                </h4>
                
                <div class="table-responsive">
                    <table style="width: 100%; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <th style="text-align: left; padding: 4px 0;">Subject</th>
                                <th style="text-align: center; padding: 4px 0;">Qty</th>
                                <th style="text-align: right; padding: 4px 0;">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
    $filled_in_class = 0;
    while ($cs = mysqli_fetch_assoc($class_subs)):
        $filled_in_class += $cs['count'];
        $share = ($total_weekly_periods > 0) ? round(($cs['count'] / $total_weekly_periods) * 100, 1) : 0;
        $sub_dot = !empty($cs['color']) ? $cs['color'] : '#cbd5e1';
?>
                            <tr>
                                <td style="padding: 6px 0;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <div style="width: 8px; height: 8px; border-radius: 2px; background: <?php echo $sub_dot; ?>;"></div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo $cs['subject_name']; ?></div>
                                            <div style="font-size: 0.7rem; color: #94a3b8;"><?php echo $cs['teacher_name']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: var(--text-main);"><?php echo $cs['count']; ?></td>
                                <td style="text-align: right;">
                                    <span style="font-weight: 700; color: var(--primary);"><?php echo $share; ?>%</span>
                                </td>
                            </tr>
                            <?php
    endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 1px solid #e2e8f0; font-weight: 700;">
                                <td style="padding-top: 8px;">Efficiency</td>
                                <td style="text-align: center; padding-top: 8px;"><?php echo $filled_in_class; ?></td>
                                <td style="text-align: right; padding-top: 8px; color: <?php echo($filled_in_class == $total_weekly_periods) ? 'var(--success)' : '#f59e0b'; ?>;">
                                    <?php echo round(($filled_in_class / $total_weekly_periods) * 100); ?>%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

