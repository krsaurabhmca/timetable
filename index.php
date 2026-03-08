<?php
require_once 'config.php';

// Check if setup is complete
$classes_check = db_query("SELECT id FROM classes LIMIT 1");
$setup_complete = (mysqli_num_rows($classes_check) > 0);

require_once 'includes/header.php';
?>

<div class="fade-in">
    <?php if (!$setup_complete): ?>
    <div class="card" style="text-align: center; padding: 4rem 2rem;">
        <i class="fas fa-rocket" style="font-size: 4rem; color: var(--primary); margin-bottom: 2rem;"></i>
        <h1 class="card-title" style="font-size: 2rem;">Welcome to EduSchedule</h1>
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
    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        <div class="card">
            <h3 class="card-title">Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="<?php echo BASE_URL; ?>/view_timetable.php" class="btn btn-primary" style="justify-content: flex-start; padding: 1rem;">
                    <i class="fas fa-calendar-week"></i> View Detailed Routine
                </a>
                <a href="<?php echo BASE_URL; ?>/full_routine.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-table"></i> Full School Routine (All Classes)
                </a>
                <a href="<?php echo BASE_URL; ?>/analysis_report.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-chart-pie"></i> Analysis & Mapping Report
                </a>
                <a href="<?php echo BASE_URL; ?>/adjustments.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-user-clock"></i> Teacher Adjustments
                </a>
                <a href="<?php echo BASE_URL; ?>/wizard/step1.php" class="btn btn-secondary" style="justify-content: flex-start; color: var(--warning);">
                    <i class="fas fa-sync"></i> Re-run Setup Wizard
                </a>
                <hr style="border: none; border-top: 1px solid var(--border); margin: 0.5rem 0;">
                <form action="<?php echo BASE_URL; ?>/api/mock_data.php" method="POST" onsubmit="return confirm('This will clear existing data and load demo data. Proceed?')">
                    <button type="submit" name="populate" class="btn btn-mock" style="width: 100%; justify-content: flex-start;">
                        <i class="fas fa-database"></i> Load Mock Sample Data
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">School Insights</h3>
            <?php
    $teacher_count = mysqli_num_rows(db_query("SELECT id FROM teachers"));
    $class_count = mysqli_num_rows(db_query("SELECT id FROM classes"));
    $subject_count = mysqli_num_rows(db_query("SELECT id FROM subjects"));
    $today_adj_res = db_query("SELECT COUNT(*) as count FROM timetable_adjustments WHERE adjustment_date = '" . date('Y-m-d') . "'");
    $today_adj = mysqli_fetch_assoc($today_adj_res)['count'];
?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--primary);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $teacher_count; ?></div>
                        <div style="color: var(--text-muted); font-size: 0.875rem;">Active Teachers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-school"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $class_count; ?></div>
                        <div style="color: var(--text-muted); font-size: 0.875rem;">Total Classes</div>
                    </div>
                </div>
                <?php if ($today_adj > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(220, 38, 38, 0.1); color: var(--danger);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $today_adj; ?></div>
                        <div style="color: var(--text-muted); font-size: 0.875rem;">Substitutions Today</div>
                    </div>
                </div>
                <?php
    endif; ?>
            </div>
        </div>
    </div>
    <?php
endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
