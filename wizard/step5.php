<?php
require_once '../config.php';

// Fetch all necessary data
$classes_res = db_query("SELECT * FROM classes WHERE org_id = '$org_id' ORDER BY class_name, section");
$classes = [];
while ($row = mysqli_fetch_assoc($classes_res))
    $classes[] = $row;

$teachers_res = db_query("SELECT * FROM teachers WHERE org_id = '$org_id'");
$teachers_data = [];
while ($row = mysqli_fetch_assoc($teachers_res))
    $teachers_data[$row['id']] = $row;

$settings_res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];

// Assignments
$assignments_res = db_query("SELECT * FROM teacher_assignments WHERE org_id = '$org_id'");
$class_assignments = []; // [class_id][subject_id] = [teacher_ids...]
while ($row = mysqli_fetch_assoc($assignments_res)) {
    $class_assignments[$row['class_id']][$row['subject_id']][] = $row['teacher_id'];
}

// Restrictions
$restrictions_res = db_query("SELECT * FROM teacher_restrictions WHERE org_id = '$org_id'");
$teacher_blocks = []; // [teacher_id][day][period] = true
while ($row = mysqli_fetch_assoc($restrictions_res)) {
    $teacher_blocks[$row['teacher_id']][$row['day_of_week']][$row['period_number']] = true;
}

$message = "";
$status = "info";

// Support Direct Generation from Dashboard
if (isset($_GET['direct_gen']) || isset($_POST['generate'])) {
    // Clear existing timetable
    db_query("DELETE FROM timetable WHERE is_adjustment = FALSE AND org_id = '$org_id'");

    $timetable_data = [];
    $teacher_load = []; // [teacher_id] = weekly_count
    $teacher_daily_load = []; // [teacher_id][day] = count
    $teacher_busy = []; // [day][period][teacher_id] = true
    $teacher_subjects_used = []; // [teacher_id][] = subject_id

    $success = true;

    $sat_periods = (int)($settings['saturday_periods'] ?? 4);
    $max_cont = (int)($settings['max_continuous_periods'] ?? 2);
    $sched_type = $settings['schedule_type'] ?? 'different';
    $restrict_ct = $settings['restrict_class_teacher_1st_period'] ?? 'no';
    $lunch_after = (int)($settings['lunch_after_period'] ?? 0);

    // Fetch Priorities
    $priority_res = db_query("SELECT id, priority FROM subjects WHERE org_id = '$org_id'");
    $subject_priorities = [];
    while ($prow = mysqli_fetch_assoc($priority_res)) {
        $subject_priorities[$prow['id']] = $prow['priority'] ?? 3;
    }

    $failure_log = [
        'teacher_busy' => 0, 'weekly_limit' => 0, 'leisure_per_day' => 0,
        'max_subjects' => 0, 'restricted' => 0, 'continuous_limit' => 0
    ];

    $best_timetable = [];
    $best_filled_count = -1;
    $generation_days = ($sched_type == 'same') ? [$working_days[0]] : $working_days;

    // Calculate total slots for threshold
    $total_slots_target = 0;
    foreach ($working_days as $d) {
        $slots_per_day = ($d === 'Saturday') ? $sat_periods : $periods_count;
        $total_slots_target += count($classes) * $slots_per_day;
    }

    $best_filled_count = 0;
    $best_timetable = [];
    $best_failure_log = [];

    // GLOBAL MULTI-PASS ATTEMPT
    for ($pass = 0; $pass < 30; $pass++) {
        $current_timetable = [];
        $teacher_load = [];
        $teacher_daily_load = [];
        $teacher_busy = []; // [day][period][tid]
        $teacher_subjects_used = []; // [tid][]
        $current_failure_log = [
            'teacher_busy' => 0,
            'weekly_limit' => 0,
            'leisure_per_day' => 0,
            'max_subjects' => 0,
            'restricted' => 0,
            'continuous_limit' => 0
        ];

        // Randomize processing order for fairness
        $shuffled_classes = $classes;
        shuffle($shuffled_classes);
        $shuffled_days = $generation_days;
        shuffle($shuffled_days);

        foreach ($shuffled_days as $day) {
            $day_periods = ($day === 'Saturday') ? $sat_periods : $periods_count;
            $class_day_subjects = [];
            $continuous_count = [];

            // Process periods in random or sequential order? Sequential is better for continuity constraints.
            for ($p = 1; $p <= $day_periods; $p++) {
                foreach ($shuffled_classes as $class) {
                    $cid = $class['id'];
                    $assigned = false;

                    // Fetch class teacher data for 1st period constraint
                    $ct_res = db_query("SELECT id FROM teachers WHERE is_class_teacher_of = $cid AND org_id = '$org_id' LIMIT 1");
                    $ct_data = mysqli_fetch_assoc($ct_res);
                    $ct_id = $ct_data ? $ct_data['id'] : null;

                    $class_subs_res = db_query("SELECT DISTINCT subject_id FROM teacher_assignments WHERE class_id = $cid AND org_id = '$org_id'");
                    $subjects_list = [];
                    while ($srow = mysqli_fetch_assoc($class_subs_res))
                        $subjects_list[] = $srow['subject_id'];

                    if (empty($subjects_list))
                        continue;

                    $subject_order = $subjects_list;
                    usort($subject_order, function ($a, $b) use ($subject_priorities) {
                        $pA = $subject_priorities[$a] ?? 3;
                        $pB = $subject_priorities[$b] ?? 3;
                        if ($pA == $pB)
                            return rand(-1, 1);
                        return $pA - $pB;
                    });

                    // Add more entropy for later attempts
                    if ($pass > 0)
                        shuffle($subject_order);

                    foreach ($subject_order as $sid) {
                        // NEW CONSTRAINT: Prevent same subject on same day (unless continuous)
                        $is_repeat_today = isset($class_day_subjects[$cid][$sid]);
                        $is_prev_p = ($is_repeat_today && $class_day_subjects[$cid][$sid] == $p - 1);

                        // If it's a repeat but NOT a continuous period (Double Period), try to avoid it
                        if ($is_repeat_today && !$is_prev_p) {
                            // We allow repeats only if we've exhausted other options in later passes
                            // In early passes/attempts, we strictly force variety
                            if ($pass < 15) {
                                continue;
                            }
                        }

                        // CONSTRAINT: Max Continuous
                        if ($is_prev_p) {
                            if (($continuous_count[$cid][$sid] ?? 1) >= $max_cont) {
                                $current_failure_log['continuous_limit']++;
                                continue;
                            }
                        }
                        elseif ($is_repeat_today) {
                            // Absolute limit for repeats per day even in desperate passes
                            if (($continuous_count[$cid][$sid] ?? 1) >= 3) {
                                continue;
                            }
                        }

                        if ($p == 1 && $restrict_ct == 'yes' && $ct_id) {
                            $is_ct_subject = in_array($ct_id, $class_assignments[$cid][$sid] ?? []);
                            if (!$is_ct_subject)
                                continue;
                        }

                        $potential_teachers = $class_assignments[$cid][$sid] ?? [];
                        if ($p == 1 && $restrict_ct == 'yes' && $ct_id) {
                            $potential_teachers = array_intersect($potential_teachers, [$ct_id]);
                        }

                        // Sort potential teachers by remaining capacity (Most capacity first)
                        usort($potential_teachers, function ($a, $b) use ($teacher_load, $teachers_data) {
                            $capA = $teachers_data[$a]['weekly_limit'] - ($teacher_load[$a] ?? 0);
                            $capB = $teachers_data[$b]['weekly_limit'] - ($teacher_load[$b] ?? 0);
                            if ($capA == $capB)
                                return rand(-1, 1);
                            return $capB - $capA;
                        });

                        foreach ($potential_teachers as $tid) {
                            if (isset($teacher_blocks[$tid][$day][$p])) {
                                $current_failure_log['restricted']++;
                                continue;
                            }
                            if (isset($teacher_busy[$day][$p][$tid])) {
                                $current_failure_log['teacher_busy']++;
                                continue;
                            }

                            $multiplier = ($sched_type == 'same') ? count($working_days) : 1;
                            $current_weekly = $teacher_load[$tid] ?? 0;
                            if ($current_weekly + $multiplier > $teachers_data[$tid]['weekly_limit']) {
                                $current_failure_log['weekly_limit']++;
                                continue;
                            }

                            $current_daily = $teacher_daily_load[$tid][$day] ?? 0;
                            $max_allowed_daily = $day_periods - $teachers_data[$tid]['leisure_per_day'];
                            if ($current_daily + 1 > $max_allowed_daily) {
                                $current_failure_log['leisure_per_day']++;
                                continue;
                            }

                            if (!in_array($sid, $teacher_subjects_used[$tid] ?? [])) {
                                if (count($teacher_subjects_used[$tid] ?? []) >= $teachers_data[$tid]['max_subjects']) {
                                    $current_failure_log['max_subjects']++;
                                    continue;
                                }
                            }

                            if (isset($class_day_subjects[$cid][$sid]) && $class_day_subjects[$cid][$sid] == $p - 1) {
                                // Find teacher from PREVIOUS slot in THIS specific generation attempt
                                $prev_tid = null;
                                foreach ($current_timetable as $slot) {
                                    if ($slot['class_id'] == $cid && $slot['day'] == $day && $slot['period'] == $p - 1) {
                                        $prev_tid = $slot['teacher_id'];
                                        break;
                                    }
                                }
                                if ($prev_tid && $prev_tid != $tid)
                                    continue;
                            }

                            // ASSIGN TO CURRENT ATTEMPT
                            $current_timetable[] = [
                                'class_id' => $cid, 'teacher_id' => $tid, 'subject_id' => $sid, 'day' => $day, 'period' => $p
                            ];
                            $teacher_busy[$day][$p][$tid] = true;
                            $teacher_load[$tid] = ($teacher_load[$tid] ?? 0) + $multiplier;
                            $teacher_daily_load[$tid][$day] = ($teacher_daily_load[$tid][$day] ?? 0) + 1;
                            if (!in_array($sid, $teacher_subjects_used[$tid] ?? []))
                                $teacher_subjects_used[$tid][] = $sid;

                            if (isset($class_day_subjects[$cid][$sid]) && $class_day_subjects[$cid][$sid] == $p - 1)
                                $continuous_count[$cid][$sid]++;
                            else
                                $continuous_count[$cid][$sid] = 1;

                            $class_day_subjects[$cid][$sid] = $p;
                            $assigned = true;
                            break;
                        }
                        if ($assigned)
                            break;
                    }
                }
            }
        }

        // Check if this attempt is the best so far
        $filled = count($current_timetable);
        if ($filled > $best_filled_count) {
            $best_filled_count = $filled;
            $best_timetable = $current_timetable;
            $best_failure_log = $current_failure_log;
            // If near perfect (99% of non-lunch slots), stop early
            if ($best_filled_count >= $total_slots_target * 0.99)
                break;
        }
    }

    $timetable_data = $best_timetable;
    $failure_log = $best_failure_log;

    // Handle 'same' schedule replication
    if ($sched_type == 'same') {
        $first_day = $working_days[0];
        $base_data = $timetable_data;
        $timetable_data = [];
        foreach ($working_days as $day) {
            foreach ($base_data as $slot) {
                // Adjust periods if Saturday is shorter
                $day_limit = ($day === 'Saturday') ? $sat_periods : $periods_count;
                if ($slot['period'] <= $day_limit) {
                    $new_slot = $slot;
                    $new_slot['day'] = $day;
                    $timetable_data[] = $new_slot;
                }
            }
        }
    }

    // Batch Insert
    foreach ($timetable_data as $row) {
        $cid = $row['class_id'];
        $tid = $row['teacher_id'];
        $sid = $row['subject_id'];
        $day = $row['day'];
        $period = $row['period'];
        db_query("INSERT INTO timetable (class_id, teacher_id, subject_id, day_of_week, period_number, org_id) 
                  VALUES ($cid, $tid, $sid, '$day', $period, '$org_id')");
    }

    // Failure Analysis
    $total_required = $total_slots_target;
    $filled_count = count($timetable_data);

    // For 'same' schedule, the replicated data is what we count
    // No need for extra math, $timetable_data already contains all replicated rows
    $filled_count = count($timetable_data);

    $success_rate = ($total_required > 0) ? round(($filled_count / $total_required) * 100, 1) : 0;

    $message = "Timetable generated with $success_rate% coverage ($filled_count / $total_required slots).";
    $status = $success_rate > 90 ? "success" : "warning";

    // Store failure stats in session for display
    $_SESSION['gen_stats'] = [
        'total' => $total_required,
        'filled' => $filled_count,
        'failed' => $total_required - $filled_count,
        'rate' => $success_rate,
        'reasons' => $failure_log ?? []
    ];
}

$gen_stats = $_SESSION['gen_stats'] ?? null;
unset($_SESSION['gen_stats']);

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">General</div></div>
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Classes & Subs</div></div>
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Mapping</div></div>
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Teachers</div></div>
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Constraints</div></div>
    <div class="step active"><div class="step-circle">6</div><div class="step-label">Generate</div></div>
</div>

<div class="card fade-in" style="text-align: center; padding: 4rem 2rem;">
    <?php if ($message): ?>
        <div class="alert" style="background: <?php echo $status == 'success' ? '#dcfce7' : '#fff7ed'; ?>; color: <?php echo $status == 'success' ? '#166534' : '#9a3412'; ?>; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid currentColor; text-align: left;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <i class="fas <?php echo $status == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>" style="font-size: 1.5rem;"></i>
                <div>
                    <strong style="display: block; font-size: 1.1rem;"><?php echo $message; ?></strong>
                </div>
            </div>

            <?php if ($gen_stats && $gen_stats['failed'] > 0): ?>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); margin-top: 1rem;">
                    <h3 style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                        <i class="fas fa-stethoscope"></i> Diagnosis: Why did it fail?
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php
        $reasons = $gen_stats['reasons'];
        $total_fails = array_sum($reasons);
        if ($total_fails > 0):
            foreach ($reasons as $key => $count):
                if ($count == 0)
                    continue;
                $label = ucwords(str_replace('_', ' ', $key));
                $percent = round(($count / $total_fails) * 100);
?>
                            <div style="padding: 10px; border-radius: 8px; background: #f8fafc;">
                                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;"><?php echo $label; ?></div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary);"><?php echo $count; ?> blocks</div>
                                <div style="height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 5px;">
                                    <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--primary); border-radius: 2px;"></div>
                                </div>
                            </div>
                        <?php
            endforeach;
        endif; ?>
                    </div>

                    <div style="margin-top: 1.5rem; padding: 1rem; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                        <h4 style="font-size: 0.85rem; color: #1e40af; margin-bottom: 0.5rem;"><i class="fas fa-lightbulb"></i> Recommended Fixes:</h4>
                        <ul style="font-size: 0.8rem; color: #1e40af; padding-left: 1.2rem; margin: 0;">
                            <?php if ($reasons['teacher_busy'] > 0): ?><li><strong>Staffing Issue:</strong> You don't have enough teachers. Hire more or increase "Max Subjects" per teacher in Step 4.</li><?php
        endif; ?>
                            <?php if ($reasons['weekly_limit'] > 0): ?><li><strong>Overload:</strong> Go to Step 4 and increase the <strong>Weekly Period Limit</strong> for your teachers.</li><?php
        endif; ?>
                            <?php if ($reasons['restricted'] > 0): ?><li><strong>Schedule Clash:</strong> Some teachers have too many "Off-Periods" blocked in Step 5. Clear some restrictions.</li><?php
        endif; ?>
                            <?php if ($reasons['continuous_limit'] > 0): ?><li><strong>Constraint Conflict:</strong> Try increasing "Max Continuous Periods" in Step 1.</li><?php
        endif; ?>
                        </ul>
                    </div>
                </div>
            <?php
    endif; ?>
        </div>
    <?php
endif; ?>

    <i class="fas fa-gears" style="font-size: 4rem; color: var(--primary); margin-bottom: 2rem;"></i>
    <h1 class="card-title">Final Generation</h1>
    
    <?php
$teacher_count = count($teachers_data);
$class_count = count($classes);
if ($teacher_count > 0 && $teacher_count <= $class_count):
?>
        <div class="alert" style="background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: left;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Critical Warning:</strong> 
            You have <?php echo $teacher_count; ?> teachers for <?php echo $class_count; ?> classes. 
            For smooth operation and mandatory leisure periods, you should have at least **<?php echo $class_count + 1; ?>** teachers. 
            Generation might leave some periods "Free".
        </div>
    <?php
endif; ?>

    <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
        Our algorithm will now process all your classes, subjects, teacher limits, and restrictions to create an optimized schedule.
    </p>

    <div class="card" style="background: #f8fafc; border: 1px dashed var(--border); margin-bottom: 2rem; text-align: left; padding: 1.5rem;">
        <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-main);"><i class="fas fa-question-circle"></i> Why might some periods be blank?</h3>
        <ul style="font-size: 0.85rem; color: var(--text-muted); padding-left: 1.2rem;">
            <li>No teacher was available for any of that class's subjects at that time.</li>
            <li>All teachers for that class's subjects reached their daily/weekly class limits.</li>
            <li>Teacher restrictions (blocked periods) prevented any assignment.</li>
            <li>Continuous period limits (e.g., max 2 periods of English) were reached.</li>
        </ul>
    </div>

    <form method="POST">
        <?php $has_routine = mysqli_num_rows(db_query("SELECT id FROM timetable WHERE org_id = '$org_id' LIMIT 1")) > 0; ?>
        <button type="submit" name="generate" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1.25rem;" 
                onclick="return <?php echo $has_routine ? "confirm('Are you sure you want to REGENERATE? This will delete the current routine and create a new one.')" : "true"; ?>;">
            <i class="fas fa-wand-sparkles"></i> <?php echo $has_routine ? 'Regenerate Routine' : 'Generate Now'; ?>
        </button>
    </form>

    <?php if (mysqli_num_rows(db_query("SELECT id FROM timetable WHERE org_id = '$org_id' LIMIT 1")) > 0): ?>
    <div style="margin-top: 3rem; display: flex; justify-content: center; gap: 1rem;">
        <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary">Go to Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/view_timetable.php" class="btn btn-success" style="background: var(--success); color: white;">
            <i class="fas fa-eye"></i> View Routine
        </a>
    </div>
    <?php
endif; ?>
</div>

<div style="display: flex; justify-content: flex-start; margin-top: 2rem;">
    <a href="step4.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Constraints</a>
</div>

<?php require_once '../includes/footer.php'; ?>
