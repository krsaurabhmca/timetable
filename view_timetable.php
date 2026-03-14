<?php
require_once 'config.php';

// ─── Parameters ────────────────────────────────────────────────────────────────
$view = $_GET['view'] ?? 'day';          // day | class | teacher | period
$id   = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$day  = $_GET['day']  ?? null;
$period_no = isset($_GET['period']) ? (int)$_GET['period'] : null;

// ─── Settings ──────────────────────────────────────────────────────────────────
$settings_res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
$settings = [];
if ($settings_res) while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$working_days   = explode(',', $settings['working_days'] ?? 'Monday');
$periods_count  = (int)($settings['periods_per_day'] ?? 6);
$sat_periods    = (int)($settings['saturday_periods'] ?? 4);
$lunch_after    = (int)($settings['lunch_after_period'] ?? 0);

// Default day/period
if (!$day)   $day       = $working_days[0];
if (!$period_no) $period_no = 1;

// ─── Fetch Master Lists ────────────────────────────────────────────────────────
$classes_res  = db_query("SELECT * FROM classes  WHERE org_id = '$org_id' ORDER BY class_name");
$teachers_res = db_query("SELECT * FROM teachers WHERE org_id = '$org_id' ORDER BY name");
$classes_list  = [];
$teachers_list = [];
if ($classes_res)  while ($r = mysqli_fetch_assoc($classes_res))  $classes_list[]  = $r;
if ($teachers_res) while ($r = mysqli_fetch_assoc($teachers_res)) $teachers_list[] = $r;

// ─── Today's adjustments (used in day & class views) ──────────────────────────
$today_date = date('Y-m-d');
$today_day  = date('l');
$adjustments = [];

// ─── Build schedule data per view ─────────────────────────────────────────────
$schedule = [];
$view_title = '';
$view_subtitle = '';

// ══════════════════════════════════════════════════════════════════
//  VIEW: CLASS-WISE  (full week for one class)
// ══════════════════════════════════════════════════════════════════
if ($view === 'class' && $id) {
    $_cr = db_query("SELECT * FROM classes WHERE id = $id AND org_id = '$org_id'");
    $class_row = $_cr ? mysqli_fetch_assoc($_cr) : [];
    $view_title    = 'Class Routine';
    $view_subtitle = $class_row['class_name'] ?? '';

    $res = db_query("SELECT t.*, s.subject_name, s.color, tea.name AS teacher_name
                     FROM timetable t
                     JOIN subjects s  ON t.subject_id = s.id
                     JOIN teachers tea ON t.teacher_id = tea.id
                     WHERE t.class_id = $id AND t.org_id = '$org_id'");
    if ($res) while ($r = mysqli_fetch_assoc($res))
        $schedule[$r['day_of_week']][$r['period_number']] = $r;

    // Today adjustments for this class
    $adj_res = db_query("SELECT ta.*, tea.name AS proxy_name
                         FROM timetable_adjustments ta
                         JOIN teachers tea ON ta.proxy_teacher_id = tea.id
                         WHERE ta.class_id = $id AND ta.adjustment_date = '$today_date' AND ta.org_id = '$org_id'");
    if ($adj_res) while ($adj = mysqli_fetch_assoc($adj_res))
        $adjustments[$adj['day_of_week']][$adj['period_number']] = $adj;

// ══════════════════════════════════════════════════════════════════
//  VIEW: TEACHER-WISE  (full week for one teacher)
// ══════════════════════════════════════════════════════════════════
} elseif ($view === 'teacher' && $id) {
    $_tr = db_query("SELECT * FROM teachers WHERE id = $id AND org_id = '$org_id'");
    $teacher_row = $_tr ? mysqli_fetch_assoc($_tr) : [];
    $view_title    = 'Teacher Routine';
    $view_subtitle = $teacher_row['name'] ?? '';

    $res = db_query("SELECT t.*, s.subject_name, s.color, c.class_name
                     FROM timetable t
                     JOIN subjects s ON t.subject_id = s.id
                     JOIN classes  c ON t.class_id   = c.id
                     WHERE t.teacher_id = $id AND t.org_id = '$org_id'");
    if ($res) while ($r = mysqli_fetch_assoc($res))
        $schedule[$r['day_of_week']][$r['period_number']] = $r;

    // Adjustments where this teacher is proxy or original
    $adj_res = db_query("SELECT ta.*, c.class_name,
                                tea_orig.name  AS original_name,
                                tea_proxy.name AS proxy_name
                         FROM timetable_adjustments ta
                         JOIN classes  c         ON ta.class_id           = c.id
                         JOIN teachers tea_orig  ON ta.original_teacher_id = tea_orig.id
                         JOIN teachers tea_proxy ON ta.proxy_teacher_id    = tea_proxy.id
                         WHERE (ta.original_teacher_id = $id OR ta.proxy_teacher_id = $id)
                           AND ta.adjustment_date = '$today_date'
                           AND ta.org_id = '$org_id'");
    if ($adj_res) while ($adj = mysqli_fetch_assoc($adj_res))
        $adjustments[$adj['day_of_week']][$adj['period_number']] = $adj;

// ══════════════════════════════════════════════════════════════════
//  VIEW: DAY-WISE  (all classes for a selected day)
// ══════════════════════════════════════════════════════════════════
} elseif ($view === 'day') {
    $view_title    = 'Day-wise Routine';
    $view_subtitle = $day;

    $res = db_query("SELECT t.*, s.subject_name, s.color, tea.name AS teacher_name, c.class_name
                     FROM timetable t
                     JOIN subjects  s   ON t.subject_id = s.id
                     JOIN teachers  tea ON t.teacher_id = tea.id
                     JOIN classes   c   ON t.class_id   = c.id
                     WHERE t.day_of_week = '$day' AND t.org_id = '$org_id'
                     ORDER BY t.period_number, c.class_name");
    if ($res) while ($r = mysqli_fetch_assoc($res))
        $schedule[$r['period_number']][$r['class_id']] = $r;

    if ($day === $today_day) {
        $adj_res = db_query("SELECT ta.*, tea.name AS proxy_name
                             FROM timetable_adjustments ta
                             JOIN teachers tea ON ta.proxy_teacher_id = tea.id
                             WHERE ta.adjustment_date = '$today_date' AND ta.org_id = '$org_id'");
        if ($adj_res) while ($adj = mysqli_fetch_assoc($adj_res))
            $adjustments[$adj['period_number']][$adj['class_id']] = $adj['proxy_name'];
    }

// ══════════════════════════════════════════════════════════════════
//  VIEW: PERIOD-WISE  (all classes for a specific period)
// ══════════════════════════════════════════════════════════════════
} elseif ($view === 'period') {
    $view_title    = 'Period-wise Routine';
    $view_subtitle = "Period $period_no";

    $res = db_query("SELECT t.*, s.subject_name, s.color, tea.name AS teacher_name, c.class_name
                     FROM timetable t
                     JOIN subjects  s   ON t.subject_id = s.id
                     JOIN teachers  tea ON t.teacher_id = tea.id
                     JOIN classes   c   ON t.class_id   = c.id
                     WHERE t.period_number = $period_no AND t.org_id = '$org_id'
                     ORDER BY t.day_of_week, c.class_name");
    if ($res) while ($r = mysqli_fetch_assoc($res))
        $schedule[$r['day_of_week']][$r['class_id']] = $r;
}

require_once 'includes/header.php';
?>

<style>
/* ── View-mode Tabs ─────────────────────────────────────── */
.view-tabs {
    display: flex;
    gap: 6px;
    background: #f1f5f9;
    padding: 5px;
    border-radius: 10px;
    flex-wrap: wrap;
}
.view-tab {
    padding: 8px 16px;
    border-radius: 7px;
    font-weight: 600;
    font-size: 0.82rem;
    text-decoration: none;
    color: var(--text-muted);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    border: none;
    background: transparent;
    cursor: pointer;
}
.view-tab:hover  { background: white; color: var(--text-main); box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.view-tab.active { background: white; color: var(--secondary); box-shadow: 0 2px 8px rgba(0,0,0,0.10); }

/* ── Filter bar ─────────────────────────────────────────── */
.filter-bar {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}
.filter-bar select {
    width: auto;
    min-width: 180px;
    padding: 0.5rem 0.85rem;
    border-radius: 8px;
    font-size: 0.85rem;
    border: 1.5px solid var(--border);
    background: white;
    transition: border-color 0.2s;
}
.filter-bar select:focus { border-color: var(--primary); outline: none; }

/* ── Day pill selector ──────────────────────────────────── */
.day-pills {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.day-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    background: white;
    transition: all 0.18s;
}
.day-pill:hover  { border-color: var(--primary); color: #000; }
.day-pill.active { background: var(--primary); color: #000; border-color: var(--primary); box-shadow: 0 2px 8px rgba(74,222,128,0.35); }

/* ── Period pill selector ───────────────────────────────── */
.period-pills {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
.period-pill {
    padding: 6px 13px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    background: white;
    transition: all 0.18s;
}
.period-pill:hover  { border-color: #8b5cf6; color: #000; }
.period-pill.active { background: #8b5cf6; color: white; border-color: #8b5cf6; box-shadow: 0 2px 8px rgba(139,92,246,0.35); }

/* ── Table styles ───────────────────────────────────────── */
.rt-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.rt-table th {
    padding: 0.9rem 0.75rem;
    background: var(--secondary);
    color: white;
    font-size: 0.78rem;
    text-align: center;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-right: 1px solid rgba(255,255,255,0.08);
}
.rt-table th.sticky-col { position: sticky; left: 0; z-index: 10; text-align: left; padding-left: 1rem; }
.rt-table td { border: 1px solid #f1f5f9; padding: 0.45rem; vertical-align: top; min-width: 130px; }
.rt-table td.row-header {
    position: sticky; left: 0; z-index: 5;
    background: #f8fafc;
    font-weight: 700;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    border-right: 2px solid var(--border);
    min-width: 130px;
    vertical-align: middle;
}
.rt-table tr:hover td { background: #fafbff; }
.rt-table tr:hover td.row-header { background: #f1f5f9; }

/* ── Cell card ──────────────────────────────────────────── */
.cell-card {
    padding: 7px 9px;
    border-radius: 7px;
    border-left: 3px solid;
    font-size: 0.78rem;
    transition: transform 0.15s, box-shadow 0.15s;
    position: relative;
}
.cell-card:hover { transform: scale(1.01); box-shadow: 0 3px 10px rgba(0,0,0,0.08); }
.cell-card .c-subject { font-weight: 700; color: var(--text-main); font-size: 0.82rem; }
.cell-card .c-meta    { font-size: 0.7rem; color: var(--text-muted); margin-top: 3px; }
.cell-card .c-badge   { display: inline-block; font-size: 0.6rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; margin-top: 3px; }
.cell-free  { text-align: center; color: #c8d6e4; font-size: 0.72rem; padding: 8px; font-style: italic; }

/* ── Lunch separator ────────────────────────────────────── */
.lunch-col { background: #f8fafc !important; text-align: center; color: #94a3b8; font-weight: 700; font-size: 0.7rem; writing-mode: vertical-rl; padding: 4px; }

/* ── Page header ────────────────────────────────────────── */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.header-meta { display: flex; flex-direction: column; gap: 2px; }
.header-meta h1 { margin: 0; font-size: 1.4rem; font-weight: 800; }
.header-meta p  { margin: 0; color: var(--text-muted); font-size: 0.85rem; }
.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }

/* ── Empty state ────────────────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 2rem; }
.empty-state i { font-size: 3.5rem; color: #e2e8f0; margin-bottom: 1rem; display: block; }
.empty-state p { color: var(--text-muted); font-size: 0.95rem; }

/* Color helpers used for cell-card border+bg */
.cc-1  { border-left-color:#3b82f6; background:#eff6ff; }
.cc-2  { border-left-color:#10b981; background:#ecfdf5; }
.cc-3  { border-left-color:#f59e0b; background:#fffbeb; }
.cc-4  { border-left-color:#ef4444; background:#fef2f2; }
.cc-5  { border-left-color:#8b5cf6; background:#f5f3ff; }
.cc-6  { border-left-color:#ec4899; background:#fdf2f8; }
.cc-7  { border-left-color:#06b6d4; background:#ecfeff; }
.cc-8  { border-left-color:#f97316; background:#fff7ed; }

/* Adjustment badge */
.badge-proxy   { background:#dcfce7; color:#166534; }
.badge-absent  { background:#fee2e2; color:#991b1b; }
.badge-duty    { background:#dbeafe; color:#1e40af; }

@media print {
    header, .view-tabs, .filter-bar, .day-pills, .period-pills,
    .header-actions, .btn, footer { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; padding: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
    body { background: white !important; }
    .rt-table { font-size: 9px; }
    .rt-table th, .rt-table td { padding: 3px 4px !important; }
}
</style>

<div class="fade-in">

<!-- ═══════════ TOP CARD: HEADER + TABS ═══════════ -->
<div class="card">
    <div class="page-header">
        <div class="header-meta">
            <h1><i class="fas fa-table-cells" style="color:var(--primary);"></i>
                <?php echo $view_title ?: 'Routine Viewer'; ?>
                <?php if ($view_subtitle): ?>
                    <span style="font-size:0.9rem;font-weight:500;color:var(--text-muted);margin-left:8px;"><?php echo htmlspecialchars($view_subtitle); ?></span>
                <?php endif; ?>
            </h1>
            <p>Switch between Day-wise, Class-wise, Teacher-wise, and Period-wise views</p>
        </div>
        <div class="header-actions">
            <button onclick="window.print();" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <!-- View Mode Tabs -->
    <div style="margin-top:1.25rem;">
        <div class="view-tabs">
            <a href="?view=day&day=<?php echo urlencode($day); ?>"
               class="view-tab <?php echo $view==='day'     ? 'active' : ''; ?>">
                <i class="fas fa-calendar-day"></i> Day-wise
            </a>
            <a href="?view=class<?php echo $id && $view==='class' ? '&id='.$id : ''; ?>"
               class="view-tab <?php echo $view==='class'   ? 'active' : ''; ?>">
                <i class="fas fa-school"></i> Class-wise
            </a>
            <a href="?view=teacher<?php echo $id && $view==='teacher' ? '&id='.$id : ''; ?>"
               class="view-tab <?php echo $view==='teacher' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i> Teacher-wise
            </a>
            <a href="?view=period&period=<?php echo $period_no; ?>"
               class="view-tab <?php echo $view==='period'  ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Period-wise
            </a>
        </div>
    </div>

    <!-- ── Filter bar depending on view ── -->
    <div style="margin-top:1rem;" class="filter-bar">

        <?php if ($view === 'day'): ?>
            <span style="font-size:0.82rem;font-weight:600;color:var(--text-muted);">Select Day:</span>
            <div class="day-pills">
                <?php foreach ($working_days as $d): ?>
                    <a href="?view=day&day=<?php echo urlencode($d); ?>"
                       class="day-pill <?php echo $day===$d ? 'active' : ''; ?>">
                        <?php echo $d; ?>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($view === 'class'): ?>
            <span style="font-size:0.82rem;font-weight:600;color:var(--text-muted);">Select Class:</span>
            <select onchange="location.href='?view=class&id='+this.value">
                <option value="">-- Choose a class --</option>
                <?php foreach ($classes_list as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($id==$c['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($view === 'teacher'): ?>
            <span style="font-size:0.82rem;font-weight:600;color:var(--text-muted);">Select Teacher:</span>
            <select onchange="location.href='?view=teacher&id='+this.value">
                <option value="">-- Choose a teacher --</option>
                <?php foreach ($teachers_list as $t): ?>
                    <option value="<?php echo $t['id']; ?>" <?php echo ($id==$t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($view === 'period'): ?>
            <span style="font-size:0.82rem;font-weight:600;color:var(--text-muted);">Select Period:</span>
            <div class="period-pills">
                <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                    <a href="?view=period&period=<?php echo $p; ?>"
                       class="period-pill <?php echo $period_no===$p ? 'active' : ''; ?>">
                        P<?php echo $p; ?>
                    </a>
                    <?php if ($p === $lunch_after && $lunch_after > 0): ?>
                        <span style="padding:6px 8px;font-size:0.7rem;color:#94a3b8;font-weight:700;align-self:center;">LUNCH</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════ CONTENT CARD ═══════════ -->
<div class="card" style="padding:0; overflow:hidden; border:none;">

<?php
// ────────────────────────────────────────────────────────
//  HELPER: render a single period cell card
// ────────────────────────────────────────────────────────
function renderCell($item, $adj = null, $view_type = 'class', $entity_id = null) {
    if (!$item && !$adj) {
        echo '<div class="cell-free">—</div>';
        return;
    }
    $color_idx = $item ? (($item['subject_id'] % 8) + 1) : 8;
    $cc = "cc-$color_idx";
    echo '<div class="cell-card ' . $cc . '">';
    if ($item) {
        echo '<div class="c-subject">' . htmlspecialchars($item['subject_name']) . '</div>';
    }
    // Meta line
    echo '<div class="c-meta">';
    if ($view_type === 'class') {
        // Show teacher name
        if ($adj) {
            echo '<i class="fas fa-exchange-alt"></i> ' . htmlspecialchars($adj['proxy_name'] ?? '');
            echo '<span class="c-badge badge-proxy">PROXY</span>';
        } else {
            echo '<i class="fas fa-user-tie"></i> ' . htmlspecialchars($item['teacher_name'] ?? '');
        }
    } elseif ($view_type === 'teacher') {
        // Show class name
        echo '<i class="fas fa-school"></i> ' . htmlspecialchars($item['class_name'] ?? '');
        if ($adj) {
            if ($adj['proxy_teacher_id'] == $entity_id) {
                echo '<span class="c-badge badge-duty">PROXY DUTY</span>';
            } elseif ($adj['original_teacher_id'] == $entity_id) {
                echo '<span class="c-badge badge-absent">ABSENT</span>';
            }
        }
    } elseif ($view_type === 'day') {
        // Show teacher name
        echo '<i class="fas fa-user-tie"></i> ' . htmlspecialchars($item['teacher_name'] ?? '');
    } elseif ($view_type === 'period') {
        // Show teacher name
        echo '<i class="fas fa-user-tie"></i> ' . htmlspecialchars($item['teacher_name'] ?? '');
    }
    echo '</div>';
    echo '</div>';
}
?>

<?php
// ═══════════════════════════════════════════════════════
//  RENDER: CLASS-WISE TABLE  (Periods as columns, Days as rows)
// ═══════════════════════════════════════════════════════
if ($view === 'class' && $id):
    $current_day_periods_list = [];
    foreach ($working_days as $wd)
        $current_day_periods_list[$wd] = ($wd === 'Saturday') ? $sat_periods : $periods_count;
?>
<div style="overflow-x:auto; padding:1rem;">
<table class="rt-table">
    <thead>
        <tr>
            <th class="sticky-col" style="min-width:110px;">Day</th>
            <?php for ($p = 1; $p <= $periods_count; $p++): ?>
                <th>Period <?php echo $p; ?></th>
                <?php if ($p === $lunch_after && $lunch_after > 0): ?>
                    <th style="background:#475569;font-size:0.65rem;letter-spacing:0.1em;">LUNCH</th>
                <?php endif; ?>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($working_days as $wd):
            $day_periods = $current_day_periods_list[$wd];
        ?>
        <tr>
            <td class="row-header">
                <div style="font-size:0.9rem;"><?php echo $wd; ?></div>
                <?php if ($wd === $today_day): ?>
                    <div style="font-size:0.6rem;margin-top:2px;background:var(--primary);color:#000;padding:1px 5px;border-radius:4px;display:inline-block;font-weight:700;">TODAY</div>
                <?php endif; ?>
            </td>
            <?php for ($p = 1; $p <= $periods_count; $p++):
                $item = $schedule[$wd][$p] ?? null;
                $adj  = ($wd === $today_day) ? ($adjustments[$wd][$p] ?? null) : null;
            ?>
            <td>
                <?php if ($p <= $day_periods): renderCell($item, $adj, 'class', $id); else: ?>
                    <div class="cell-free" style="color:#e2e8f0;">–</div>
                <?php endif; ?>
            </td>
            <?php if ($p === $lunch_after && $lunch_after > 0): ?>
                <td class="lunch-col">LUNCH</td>
            <?php endif; ?>
            <?php endfor; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php
// ═══════════════════════════════════════════════════════
//  RENDER: TEACHER-WISE TABLE  (Days as columns, Periods as rows)
// ═══════════════════════════════════════════════════════
elseif ($view === 'teacher' && $id):
?>
<div style="overflow-x:auto; padding:1rem;">
<table class="rt-table">
    <thead>
        <tr>
            <th class="sticky-col" style="min-width:100px;">Period</th>
            <?php foreach ($working_days as $wd): ?>
                <th><?php echo $wd; ?><?php if ($wd===$today_day): ?> <span style="font-size:0.6rem;background:#4ade80;color:#000;padding:1px 4px;border-radius:3px;">TODAY</span><?php endif; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($p = 1; $p <= $periods_count; $p++):
            if ($p === $lunch_after && $lunch_after > 0): ?>
            <tr>
                <td colspan="<?php echo count($working_days)+1; ?>" style="background:#f8fafc;text-align:center;color:#94a3b8;font-weight:700;font-size:0.72rem;padding:6px;border:1px solid #f1f5f9;">
                    🍽 LUNCH BREAK
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="row-header" style="text-align:center;">
                <div style="font-size:0.9rem;font-weight:800;">P<?php echo $p; ?></div>
                <div style="font-size:0.6rem;color:var(--text-muted);">Period <?php echo $p; ?></div>
            </td>
            <?php foreach ($working_days as $wd):
                $item = $schedule[$wd][$p] ?? null;
                $adj  = ($wd === $today_day) ? ($adjustments[$wd][$p] ?? null) : null;
                // Saturday period limit
                $day_max = ($wd === 'Saturday') ? $sat_periods : $periods_count;
            ?>
            <td>
                <?php if ($p <= $day_max): renderCell($item, $adj, 'teacher', $id); else: ?>
                    <div class="cell-free" style="color:#e2e8f0;">–</div>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endfor; ?>
    </tbody>
</table>
</div>

<?php
// ═══════════════════════════════════════════════════════
//  RENDER: DAY-WISE TABLE  (Periods as columns, Classes as rows)
// ═══════════════════════════════════════════════════════
elseif ($view === 'day'):
    $day_period_max = ($day === 'Saturday') ? $sat_periods : $periods_count;
?>
<div style="overflow-x:auto; padding:1rem;">
<table class="rt-table">
    <thead>
        <tr>
            <th class="sticky-col" style="min-width:130px;">Class</th>
            <?php for ($p = 1; $p <= $day_period_max; $p++): ?>
                <th>Period <?php echo $p; ?></th>
                <?php if ($p === $lunch_after && $lunch_after > 0): ?>
                    <th style="background:#475569;font-size:0.65rem;letter-spacing:0.1em;">LUNCH</th>
                <?php endif; ?>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($classes_list as $class): ?>
        <tr>
            <td class="row-header">
                <div><?php echo htmlspecialchars($class['class_name']); ?></div>
            </td>
            <?php for ($p = 1; $p <= $day_period_max; $p++):
                $item = $schedule[$p][$class['id']] ?? null;
                $proxy = $adjustments[$p][$class['id']] ?? null;
            ?>
            <td>
                <?php if ($item): ?>
                    <?php
                    // Build a minimal adj object if proxy exists
                    $adj_fake = $proxy ? ['proxy_name' => $proxy] : null;
                    renderCell($item, $adj_fake, 'day', null);
                    ?>
                <?php else: ?>
                    <div class="cell-free">—</div>
                <?php endif; ?>
            </td>
            <?php if ($p === $lunch_after && $lunch_after > 0): ?>
                <td class="lunch-col">LUNCH</td>
            <?php endif; ?>
            <?php endfor; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php
// ═══════════════════════════════════════════════════════
//  RENDER: PERIOD-WISE TABLE  (Days as columns, Classes as rows)
// ═══════════════════════════════════════════════════════
elseif ($view === 'period'):
?>
<div style="overflow-x:auto; padding:1rem;">
<table class="rt-table">
    <thead>
        <tr>
            <th class="sticky-col" style="min-width:130px;">Class</th>
            <?php foreach ($working_days as $wd): ?>
                <th><?php echo $wd; ?><?php if ($wd===$today_day): ?> <span style="font-size:0.6rem;background:#4ade80;color:#000;padding:1px 4px;border-radius:3px;">TODAY</span><?php endif; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($classes_list as $class):
            // Check if this period is within the day limit for Saturday
        ?>
        <tr>
            <td class="row-header"><?php echo htmlspecialchars($class['class_name']); ?></td>
            <?php foreach ($working_days as $wd):
                $day_max = ($wd === 'Saturday') ? $sat_periods : $periods_count;
                $item = ($period_no <= $day_max) ? ($schedule[$wd][$class['id']] ?? null) : null;
            ?>
            <td>
                <?php if ($period_no <= $day_max): renderCell($item, null, 'period', null);
                else: ?>
                    <div class="cell-free" style="color:#e2e8f0;">–</div>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php
// ═══════════════════════════════════════════════════════
//  RENDER: Empty / No selection states
// ═══════════════════════════════════════════════════════
else: ?>
<div class="empty-state">
    <i class="fas fa-arrow-up-from-bracket"></i>
    <p>Please select a <strong><?php echo ucfirst($view); ?></strong> from the filter above to view the routine.</p>
</div>
<?php endif; ?>

</div><!-- /content card -->

</div><!-- /fade-in -->

<?php require_once 'includes/footer.php'; ?>
