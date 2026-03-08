<?php
require_once 'config.php';
require_once 'includes/session.php';
require_login();
$org_id = $_SESSION['org_id'];

// Check if setup is complete
$classes_check = db_query("SELECT id FROM classes WHERE org_id = '$org_id' LIMIT 1");
$setup_complete = (mysqli_num_rows($classes_check) > 0);

require_once 'includes/header.php';
?>

<div class="fade-in">
    <?php if (!$setup_complete): ?>
    <div class="card" style="text-align: center; padding: 4rem 2rem;">
        <i class="fas fa-rocket" style="font-size: 4rem; color: var(--primary); margin-bottom: 2rem;"></i>
        <h1 class="card-title" style="font-size: 2rem;">Welcome to TimeGrid</h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            It looks like you haven't set up your school timetable yet. Our smart wizard will guide you through 
            assigning teachers, subjects, and constraints to generate a perfect routine.
        </p>
        <a href="<?php echo BASE_URL; ?>/wizard/step1.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
            Get Started with Wizard <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php
else: ?>
    <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <!-- Stats Row -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <?php
    $teacher_count = mysqli_num_rows(db_query("SELECT id FROM teachers WHERE org_id = '$org_id'"));
    $class_count = mysqli_num_rows(db_query("SELECT id FROM classes WHERE org_id = '$org_id'"));
    $subject_count = mysqli_num_rows(db_query("SELECT id FROM subjects WHERE org_id = '$org_id'"));
    $total_slots_res = db_query("SELECT COUNT(*) as count FROM timetable WHERE org_id = '$org_id'");
    $allocated_slots = mysqli_fetch_assoc($total_slots_res)['count'] ?? 0;

    // Calculate Capacity
    $settings_res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
    $set = [];
    while ($r = mysqli_fetch_assoc($settings_res))
        $set[$r['key']] = $r['value'];
    $working_days = isset($set['working_days']) ? explode(',', $set['working_days']) : [];
    $periods = (int)($set['periods_per_day'] ?? 0);
    $sat_periods = (int)($set['saturday_periods'] ?? 4);
    $total_capacity = 0;
    foreach ($working_days as $d) {
        $total_capacity += $class_count * ($d == 'Saturday' ? $sat_periods : $periods);
    }
    $coverage = $total_capacity > 0 ? round(($allocated_slots / $total_capacity) * 100, 1) : 0;
?>
                <div class="card" style="padding: 1rem; border-left: 4px solid var(--primary);">
                    <div style="color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Faculty</div>
                    <div style="font-size: 1.4rem; font-weight: 800; display: flex; align-items: baseline; gap: 4px;">
                        <?php echo $teacher_count; ?> 
                        <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">Staff</span>
                    </div>
                </div>
                <div class="card" style="padding: 1rem; border-left: 4px solid var(--success);">
                    <div style="color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Coverage</div>
                    <div style="font-size: 1.4rem; font-weight: 800; display: flex; align-items: baseline; gap: 4px;">
                        <?php echo $coverage; ?>% 
                        <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);"><?php echo $allocated_slots; ?>/<?php echo $total_capacity; ?></span>
                    </div>
                </div>
                <div class="card" style="padding: 1rem; border-left: 4px solid #8b5cf6;">
                    <div style="color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Classes</div>
                    <div style="font-size: 1.4rem; font-weight: 800; display: flex; align-items: baseline; gap: 4px;">
                        <?php echo $class_count; ?> 
                        <span style="font-size: 0.75rem; font-weight: 500; color: var(--text-muted);">Units</span>
                    </div>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 class="card-title" style="margin: 0; font-size: 1.1rem; color: var(--text-main);">Quick Access</h3>
                    <a href="<?php echo BASE_URL; ?>/wizard/step5.php?direct_gen=1" class="btn btn-primary" 
                       style="font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);" 
                       onclick="return confirm('Regenerate entire timetable now?')">
                        <i class="fas fa-bolt"></i> Fast Re-generate
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                    <a href="<?php echo BASE_URL; ?>/print_routine.php" target="_blank" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem; border: 1px solid #fecaca; background: #fff1f2; border-radius: 10px; transition: all 0.2s;">
                        <i class="fas fa-file-pdf" style="color: var(--danger); width: 20px;"></i> 
                        <span style="font-size: 0.85rem; font-weight: 600;">Today's Routine (PDF)</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/view_timetable.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem; border: 1px solid #f1f5f9; background: #fff; border-radius: 10px; transition: all 0.2s;">
                        <i class="fas fa-calendar-day" style="color: var(--primary); width: 20px;"></i> 
                        <span style="font-size: 0.85rem; font-weight: 600;">Individual Schedules</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/full_routine.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem; border: 1px solid #f1f5f9; background: #fff; border-radius: 10px; transition: all 0.2s;">
                        <i class="fas fa-table-list" style="color: var(--primary); width: 20px;"></i> 
                        <span style="font-size: 0.85rem; font-weight: 600;">Master Timetable</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/adjustments.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem; border: 1px solid #f1f5f9; background: #fff; border-radius: 10px; transition: all 0.2s;">
                        <i class="fas fa-user-clock" style="color: var(--danger); width: 20px;"></i> 
                        <span style="font-size: 0.85rem; font-weight: 600;">Daily Substitution</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/analysis_report.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem; border: 1px solid #f1f5f9; background: #fff; border-radius: 10px; transition: all 0.2s;">
                        <i class="fas fa-chart-pie" style="color: var(--success); width: 20px;"></i> 
                        <span style="font-size: 0.85rem; font-weight: 600;">System Analysis</span>
                    </a>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="card" style="padding: 1.25rem;">
                <h3 class="card-title" style="margin-bottom: 1rem; font-size: 1.1rem; color: var(--text-main);">Live Status</h3>
                <?php
    $today_adj_res = db_query("SELECT COUNT(*) as count FROM attendance_logs WHERE absent_date = '" . date('Y-m-d') . "' AND org_id = '$org_id'");
    $today_adj = mysqli_fetch_assoc($today_adj_res)['count'] ?? 0;
?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                        <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Routine Config</div>
                        <div style="font-weight: 700; color: #334155; margin-top: 4px; font-size: 0.9rem;">
                            <?php echo count($working_days); ?> Working Days | <?php echo $periods; ?> Periods
                        </div>
                    </div>
                    
                    <?php if ($today_adj > 0): ?>
                    <div style="background: #fff1f2; padding: 12px; border-radius: 8px; border-left: 4px solid var(--danger);">
                        <div style="font-size: 0.65rem; color: #991b1b; text-transform: uppercase; font-weight: 700;">Alerts</div>
                        <div style="font-weight: 700; color: #991b1b; margin-top: 4px; font-size: 0.9rem;"><?php echo $today_adj; ?> Active Absences</div>
                    </div>
                    <?php
    else: ?>
                    <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; border-left: 4px solid var(--success);">
                        <div style="font-size: 0.65rem; color: #166534; text-transform: uppercase; font-weight: 700;">Health</div>
                        <div style="font-weight: 700; color: #166534; margin-top: 4px; font-size: 0.9rem;">Optimal Stability</div>
                    </div>
                    <?php
    endif; ?>

                    <div style="margin-top: 0.5rem; padding: 1rem; background: #fafafa; border-radius: 8px; border: 1px dashed #e2e8f0; text-align: center;">
                        <a href="<?php echo BASE_URL; ?>/wizard/step1.php" class="btn btn-secondary" style="font-size: 0.75rem; height: 32px; width: 100%; border-radius: 6px; background: white;">
                            <i class="fas fa-sliders"></i> Modify System
                        </a>
                    </div>
                </div>
            </div>
            
            <form action="<?php echo BASE_URL; ?>/api/mock_data.php" method="POST" onsubmit="return confirm('This will clear existing data and load demo data. Proceed?')">
                <button type="submit" name="populate" class="btn btn-secondary" style="width: 100%; font-size: 0.75rem; border: none; background: #f1f5f9; color: var(--text-muted);">
                    <i class="fas fa-database"></i> Load Mock Sample
                </button>
            </form>
        </div>
    </div>
    <?php
endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
