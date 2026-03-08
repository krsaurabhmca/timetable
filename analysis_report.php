<?php
require_once 'config.php';

// Fetch all teachers and their weekly loads
$teachers_res = db_query("SELECT t.*, 
                           (SELECT COUNT(*) FROM timetable WHERE teacher_id = t.id) as current_load 
                           FROM teachers t ORDER BY name");
$teachers = [];
while ($row = mysqli_fetch_assoc($teachers_res))
    $teachers[] = $row;

// Fetch all classes and their subject coverage
$classes_res = db_query("SELECT * FROM classes ORDER BY class_name, section");
$classes = [];
while ($row = mysqli_fetch_assoc($classes_res))
    $classes[] = $row;

$settings_res = db_query("SELECT * FROM settings");
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
    $filled = mysqli_fetch_assoc(db_query("SELECT COUNT(*) as count FROM timetable WHERE class_id = $cid"))['count'];
    $subs_count = mysqli_fetch_assoc(db_query("SELECT COUNT(DISTINCT subject_id) as count FROM teacher_assignments WHERE class_id = $cid"))['count'];
    $coverage = ($filled / $total_weekly_periods) * 100;
?>
                        <tr>
                            <td style="padding: 0.75rem;"><strong><?php echo $c['class_name'] . '-' . $c['section']; ?></strong></td>
                            <td style="text-align: center;"><?php echo $filled; ?></td>
                            <td style="text-align: center;"><?php echo $total_weekly_periods; ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="background:#f1f5f9;"><?php echo $subs_count; ?> Subjects</span>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subject Mapping Tool (Quick View) -->
    <div class="card" style="margin-top: 2rem;">
        <h2 class="card-title" style="font-size: 1.25rem;">
            <i class="fas fa-project-diagram" style="color: var(--primary);"></i> Subject Mapping in Classes
        </h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Below is the distribution of subjects across classes as currently assigned to teachers.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
            <?php foreach ($classes as $c):
    $cid = $c['id'];
    $mapped_subs = db_query("SELECT s.subject_name, t.name as teacher_name 
                                         FROM teacher_assignments ta 
                                         JOIN subjects s ON ta.subject_id = s.id 
                                         JOIN teachers t ON ta.teacher_id = t.id 
                                         WHERE ta.class_id = $cid");
?>
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 1rem;">
                <h4 style="margin: 0 0 10px 0; border-bottom: 1px solid var(--border); padding-bottom: 5px;">
                    Class <?php echo $c['class_name'] . '-' . $c['section']; ?>
                </h4>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <?php while ($ms = mysqli_fetch_assoc($mapped_subs)): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                        <span style="font-weight: 500;"><?php echo $ms['subject_name']; ?></span>
                        <span style="color: var(--text-muted);"><?php echo $ms['teacher_name']; ?></span>
                    </div>
                    <?php
    endwhile; ?>
                </div>
                <div style="margin-top: 10px; text-align: right;">
                    <a href="wizard/step3.php" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">
                        <i class="fas fa-edit"></i> Edit Mapping
                    </a>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
