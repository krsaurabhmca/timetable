<?php
require_once 'config.php';
require_once 'includes/session.php';
require_login();
$org_id = $_SESSION['org_id'];

// ── Setup check ──────────────────────────────────────────
$classes_check  = db_query("SELECT id FROM classes WHERE org_id = '$org_id' LIMIT 1");
$setup_complete = ($classes_check && mysqli_num_rows($classes_check) > 0);

// ── Stats ────────────────────────────────────────────────
$teacher_count = 0; $class_count = 0; $subject_count = 0; $allocated_slots = 0;
$working_days  = []; $periods = 0; $sat_periods = 4; $coverage = 0; $total_capacity = 0;
$today_absences = 0;

if ($setup_complete) {
    $r = db_query("SELECT id FROM teachers WHERE org_id = '$org_id'");
    $teacher_count = $r ? mysqli_num_rows($r) : 0;
    $r = db_query("SELECT id FROM classes WHERE org_id = '$org_id'");
    $class_count   = $r ? mysqli_num_rows($r) : 0;
    $r = db_query("SELECT id FROM subjects WHERE org_id = '$org_id'");
    $subject_count = $r ? mysqli_num_rows($r) : 0;

    $r = db_query("SELECT COUNT(*) as c FROM timetable WHERE org_id = '$org_id'");
    $allocated_slots = ($r && $row = mysqli_fetch_assoc($r)) ? (int)$row['c'] : 0;

    $sr = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
    $set = [];
    if ($sr) while ($row = mysqli_fetch_assoc($sr)) $set[$row['key']] = $row['value'];
    $working_days = isset($set['working_days']) ? explode(',', $set['working_days']) : [];
    $periods      = (int)($set['periods_per_day'] ?? 0);
    $sat_periods  = (int)($set['saturday_periods'] ?? 4);
    foreach ($working_days as $d)
        $total_capacity += $class_count * ($d == 'Saturday' ? $sat_periods : $periods);
    $coverage = $total_capacity > 0 ? round(($allocated_slots / $total_capacity) * 100, 1) : 0;

    // Today's absences from timetable_adjustments
    $ar = db_query("SELECT COUNT(*) as c FROM timetable_adjustments WHERE adjustment_date = '" . date('Y-m-d') . "' AND org_id = '$org_id'");
    $today_absences = ($ar && $row = mysqli_fetch_assoc($ar)) ? (int)$row['c'] : 0;
}

$today_day = date('l');   // e.g. "Saturday"

require_once 'includes/header.php';
?>

<style>
/* ── Dashboard layout ─────────────────────────────────── */
.db-wrap   { display: grid; grid-template-columns: 1fr 320px; gap: 1.25rem; align-items: start; }
.db-main   { display: flex; flex-direction: column; gap: 1.25rem; }
.db-side   { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Stat cards ───────────────────────────────────────── */
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.85rem; }
.stat-box  {
    background: white; border-radius: 12px; padding: 1rem 1.1rem;
    border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border-left: 4px solid;
    display: flex; flex-direction: column; gap: 4px;
    transition: transform 0.18s, box-shadow 0.18s;
}
.stat-box:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
.stat-label { font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em; }
.stat-value { font-size: 1.6rem; font-weight: 800; color: var(--text-main); line-height: 1; }
.stat-sub   { font-size: 0.7rem; font-weight: 500; color: var(--text-muted); }

/* ── Section headers ──────────────────────────────────── */
.section-hd {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 0.85rem;
}
.section-hd h3 { margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
.section-hd h3 i { color: var(--primary); }

/* ── Quick link cards ─────────────────────────────────── */
.ql-grid   { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.65rem; }
.ql-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.65rem; }

.ql-card {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 10px; border: 1.5px solid var(--border);
    background: white; text-decoration: none; color: var(--text-main);
    transition: all 0.18s; position: relative; overflow: hidden;
}
.ql-card::before {
    content: ''; position: absolute; inset: 0;
    background: var(--ql-color, var(--primary));
    opacity: 0; transition: opacity 0.18s;
}
.ql-card:hover { border-color: var(--ql-color, var(--primary)); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
.ql-card:hover::before { opacity: 0.04; }
.ql-icon {
    width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; background: var(--ql-bg, #f1f5f9); color: var(--ql-color, #475569);
}
.ql-text {}
.ql-title { font-size: 0.82rem; font-weight: 700; line-height: 1.2; }
.ql-desc  { font-size: 0.68rem; color: var(--text-muted); margin-top: 2px; line-height: 1.3; }

/* ── Routine sub-links ────────────────────────────────── */
.routine-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.65rem; }
.rt-card {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 0.4rem; padding: 1rem 0.5rem;
    border-radius: 12px; border: 1.5px solid var(--border);
    background: white; text-decoration: none; color: var(--text-main);
    text-align: center; transition: all 0.2s;
}
.rt-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
.rt-card i    { font-size: 1.4rem; }
.rt-card span { font-size: 0.75rem; font-weight: 700; }
.rt-card small{ font-size: 0.62rem; color: var(--text-muted); }

/* ── Coverage bar ─────────────────────────────────────── */
.progress-bar { height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; margin-top: 6px; }
.progress-fill { height: 100%; border-radius: 99px; background: var(--primary); transition: width 0.6s ease; }

/* ── Side card ────────────────────────────────────────── */
.info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; }
.info-row:last-child { border: none; }
.info-key { color: var(--text-muted); font-weight: 500; }
.info-val { font-weight: 700; color: var(--text-main); }

@media (max-width: 1024px) {
    .db-wrap   { grid-template-columns: 1fr; }
    .stat-grid { grid-template-columns: repeat(2, 1fr); }
    .routine-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="fade-in">

<?php if (!$setup_complete): ?>
<!-- ═══ FIRST-TIME SETUP PROMPT ═══ -->
<div class="card" style="text-align:center; padding:4rem 2rem;">
    <i class="fas fa-rocket" style="font-size:4rem; color:var(--primary); margin-bottom:1.5rem; display:block;"></i>
    <h1 class="card-title" style="font-size:2rem;">Welcome to TimeGrid</h1>
    <p style="color:var(--text-muted); margin-bottom:2rem; max-width:560px; margin-left:auto; margin-right:auto; line-height:1.7;">
        It looks like you haven't set up your school timetable yet. Our smart wizard will guide you through
        adding teachers, subjects, and constraints to auto-generate a perfect routine.
    </p>
    <a href="<?php echo BASE_URL; ?>/wizard/step1.php" class="btn btn-primary" style="padding:1rem 2.5rem; font-size:1rem;">
        Get Started with Wizard &nbsp;<i class="fas fa-arrow-right"></i>
    </a>
</div>

<?php else: ?>
<!-- ═══ MAIN DASHBOARD ═══ -->

<!-- ── Stat Row ── -->
<div class="stat-grid" style="margin-bottom:1.25rem;">
    <div class="stat-box" style="border-left-color: var(--primary);">
        <div class="stat-label">Faculty</div>
        <div class="stat-value"><?php echo $teacher_count; ?></div>
        <div class="stat-sub">Teachers on roll</div>
    </div>
    <div class="stat-box" style="border-left-color: #8b5cf6;">
        <div class="stat-label">Classes</div>
        <div class="stat-value"><?php echo $class_count; ?></div>
        <div class="stat-sub">Active sections</div>
    </div>
    <div class="stat-box" style="border-left-color: #06b6d4;">
        <div class="stat-label">Subjects</div>
        <div class="stat-value"><?php echo $subject_count; ?></div>
        <div class="stat-sub">Mapped subjects</div>
    </div>
    <div class="stat-box" style="border-left-color: var(--success);">
        <div class="stat-label">Coverage</div>
        <div class="stat-value"><?php echo $coverage; ?>%</div>
        <div class="stat-sub"><?php echo $allocated_slots; ?> / <?php echo $total_capacity; ?> slots</div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?php echo min($coverage,100); ?>%;"></div></div>
    </div>
</div>

<div class="db-wrap">
<div class="db-main">

    <!-- ── ROUTINE VIEWS ── -->
    <div class="card" style="padding:1.25rem;">
        <div class="section-hd">
            <h3><i class="fas fa-table-cells"></i> Routine Viewer</h3>
            <a href="<?php echo BASE_URL; ?>/view_timetable.php" style="font-size:0.75rem; color:var(--primary); font-weight:600; text-decoration:none;">
                Open Full Viewer <i class="fas fa-arrow-right" style="font-size:0.65rem;"></i>
            </a>
        </div>
        <div class="routine-grid">
            <a href="<?php echo BASE_URL; ?>/view_timetable.php?view=day&day=<?php echo urlencode($today_day); ?>"
               class="rt-card" style="border-color:#bfdbfe; background:#eff6ff;">
                <i class="fas fa-calendar-day" style="color:#3b82f6;"></i>
                <span>Day-wise</span>
                <small>All classes by day</small>
            </a>
            <a href="<?php echo BASE_URL; ?>/view_timetable.php?view=class"
               class="rt-card" style="border-color:#bbf7d0; background:#f0fdf4;">
                <i class="fas fa-school" style="color:#22c55e;"></i>
                <span>Class-wise</span>
                <small>One class full week</small>
            </a>
            <a href="<?php echo BASE_URL; ?>/view_timetable.php?view=teacher"
               class="rt-card" style="border-color:#ddd6fe; background:#f5f3ff;">
                <i class="fas fa-chalkboard-teacher" style="color:#8b5cf6;"></i>
                <span>Teacher-wise</span>
                <small>One teacher full week</small>
            </a>
            <a href="<?php echo BASE_URL; ?>/view_timetable.php?view=period&period=1"
               class="rt-card" style="border-color:#fed7aa; background:#fff7ed;">
                <i class="fas fa-clock" style="color:#f97316;"></i>
                <span>Period-wise</span>
                <small>All classes by period</small>
            </a>
        </div>
    </div>

    <!-- ── QUICK ACCESS ── -->
    <div class="card" style="padding:1.25rem;">
        <div class="section-hd">
            <h3><i class="fas fa-bolt"></i> Quick Access</h3>
            <a href="<?php echo BASE_URL; ?>/wizard/step5.php?direct_gen=1"
               class="btn btn-primary"
               style="font-size:0.72rem; padding:6px 12px; border-radius:6px;"
               onclick="return confirm('Regenerate entire timetable now?')">
                <i class="fas fa-rotate"></i> Re-generate
            </a>
        </div>
        <div class="ql-grid">
            <!-- Master Timetable -->
            <a href="<?php echo BASE_URL; ?>/full_routine.php?day=<?php echo urlencode($today_day); ?>"
               class="ql-card" style="--ql-color:#3b82f6; --ql-bg:#eff6ff;">
                <div class="ql-icon"><i class="fas fa-table-list"></i></div>
                <div class="ql-text">
                    <div class="ql-title">Master Timetable</div>
                    <div class="ql-desc">Full school — all classes & days</div>
                </div>
            </a>
            <!-- Today PDF -->
            <a href="<?php echo BASE_URL; ?>/print_routine.php?day=<?php echo urlencode($today_day); ?>" target="_blank"
               class="ql-card" style="--ql-color:#ef4444; --ql-bg:#fef2f2;">
                <div class="ql-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="ql-text">
                    <div class="ql-title">Today's PDF</div>
                    <div class="ql-desc"><?php echo $today_day; ?> routine export</div>
                </div>
            </a>
            <!-- Substitution -->
            <a href="<?php echo BASE_URL; ?>/adjustments.php"
               class="ql-card" style="--ql-color:#f59e0b; --ql-bg:#fffbeb;">
                <div class="ql-icon"><i class="fas fa-user-clock"></i></div>
                <div class="ql-text">
                    <div class="ql-title">Daily Substitution</div>
                    <div class="ql-desc">Manage proxy &amp; absent teachers</div>
                </div>
                <?php if ($today_absences > 0): ?>
                <span style="position:absolute;top:8px;right:8px;background:#ef4444;color:white;font-size:0.6rem;font-weight:800;padding:2px 6px;border-radius:99px;"><?php echo $today_absences; ?></span>
                <?php endif; ?>
            </a>
            <!-- Analysis -->
            <a href="<?php echo BASE_URL; ?>/analysis_report.php"
               class="ql-card" style="--ql-color:#22c55e; --ql-bg:#f0fdf4;">
                <div class="ql-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="ql-text">
                    <div class="ql-title">System Analysis</div>
                    <div class="ql-desc">Workload &amp; coverage reports</div>
                </div>
            </a>
            <!-- Settings -->
            <a href="<?php echo BASE_URL; ?>/settings.php"
               class="ql-card" style="--ql-color:#64748b; --ql-bg:#f8fafc;">
                <div class="ql-icon"><i class="fas fa-cog"></i></div>
                <div class="ql-text">
                    <div class="ql-title">Settings</div>
                    <div class="ql-desc">Periods, days, lunch breaks</div>
                </div>
            </a>
            <!-- Wizard -->
            <a href="<?php echo BASE_URL; ?>/wizard/step1.php"
               class="ql-card" style="--ql-color:#8b5cf6; --ql-bg:#f5f3ff;">
                <div class="ql-icon"><i class="fas fa-magic"></i></div>
                <div class="ql-text">
                    <div class="ql-title">Setup Wizard</div>
                    <div class="ql-desc">Edit teachers, subjects &amp; classes</div>
                </div>
            </a>
        </div>
    </div>

    <!-- ── PRINT / EXPORT SHORTCUTS ── -->
    <div class="card" style="padding:1.25rem;">
        <div class="section-hd">
            <h3><i class="fas fa-print"></i> Print &amp; Export</h3>
        </div>
        <div class="ql-grid-3">
            <?php foreach ($working_days as $d): ?>
            <a href="<?php echo BASE_URL; ?>/print_routine.php?day=<?php echo urlencode($d); ?>" target="_blank"
               class="ql-card" style="--ql-color:#0f172a; --ql-bg:#f8fafc; padding:0.65rem 0.85rem;">
                <div class="ql-icon" style="width:30px;height:30px;font-size:0.8rem;background:#f1f5f9;color:#334155;">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="ql-text">
                    <div class="ql-title" style="font-size:0.78rem;"><?php echo $d; ?></div>
                    <div class="ql-desc">Routine PDF</div>
                </div>
                <?php if ($d === $today_day): ?>
                <span style="position:absolute;top:6px;right:6px;background:var(--primary);color:#000;font-size:0.55rem;font-weight:800;padding:1px 5px;border-radius:99px;">TODAY</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /db-main -->

<!-- ── SIDEBAR ── -->
<div class="db-side">

    <!-- Live Status -->
    <div class="card" style="padding:1.25rem;">
        <h3 class="card-title" style="font-size:1rem; margin-bottom:1rem; display:flex; align-items:center; gap:6px;">
            <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $today_absences > 0 ? '#ef4444' : '#22c55e'; ?>;display:inline-block;box-shadow:0 0 0 3px <?php echo $today_absences > 0 ? '#fee2e2' : '#dcfce7'; ?>;"></span>
            Live Status
        </h3>
        <div>
            <div class="info-row">
                <span class="info-key">Today</span>
                <span class="info-val"><?php echo $today_day; ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Working Days</span>
                <span class="info-val"><?php echo count($working_days); ?> days/week</span>
            </div>
            <div class="info-row">
                <span class="info-key">Periods/Day</span>
                <span class="info-val"><?php echo $periods; ?> periods</span>
            </div>
            <div class="info-row">
                <span class="info-key">Slots Filled</span>
                <span class="info-val"><?php echo $allocated_slots; ?> / <?php echo $total_capacity; ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Today's Proxies</span>
                <span class="info-val" style="color:<?php echo $today_absences > 0 ? '#ef4444' : '#22c55e'; ?>;">
                    <?php echo $today_absences > 0 ? $today_absences . ' active' : 'All clear'; ?>
                </span>
            </div>
        </div>

        <?php if ($today_absences > 0): ?>
        <a href="<?php echo BASE_URL; ?>/adjustments.php" class="btn btn-secondary"
           style="width:100%;margin-top:1rem;font-size:0.78rem;border-color:#fecaca;background:#fff1f2;color:#ef4444;justify-content:center;">
            <i class="fas fa-user-clock"></i> View Substitutions
        </a>
        <?php else: ?>
        <div style="margin-top:1rem;padding:10px;background:#f0fdf4;border-radius:8px;border-left:3px solid #22c55e;font-size:0.78rem;color:#166534;font-weight:600;">
            <i class="fas fa-circle-check"></i> No absences today — Routine is stable
        </div>
        <?php endif; ?>
    </div>

    <!-- Routine Config Summary -->
    <div class="card" style="padding:1.25rem;">
        <h3 class="card-title" style="font-size:1rem; margin-bottom:1rem;"><i class="fas fa-sliders" style="color:var(--primary);"></i> Routine Config</h3>
        <div>
            <?php foreach ($working_days as $d):
                $day_periods = ($d === 'Saturday') ? $sat_periods : $periods;
            ?>
            <div class="info-row">
                <span class="info-key" style="<?php echo $d===$today_day?'color:var(--primary);font-weight:700;':''?>"><?php echo $d; ?></span>
                <span class="info-val"><?php echo $day_periods; ?> periods</span>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo BASE_URL; ?>/settings.php" class="btn btn-secondary"
           style="width:100%;margin-top:1rem;font-size:0.78rem;justify-content:center;">
            <i class="fas fa-pen-to-square"></i> Edit Settings
        </a>
    </div>

    <!-- Dev Tools -->
    <form action="<?php echo BASE_URL; ?>/api/mock_data.php" method="POST"
          onsubmit="return confirm('This will clear existing data and load demo data. Proceed?')">
        <button type="submit" name="populate" class="btn btn-secondary"
                style="width:100%;font-size:0.75rem;background:#f8fafc;color:var(--text-muted);border:1px dashed #cbd5e1;">
            <i class="fas fa-database"></i> Load Mock Sample Data
        </button>
    </form>

</div><!-- /db-side -->

</div><!-- /db-wrap -->

<?php endif; ?>
</div><!-- /fade-in -->

<?php require_once 'includes/footer.php'; ?>
