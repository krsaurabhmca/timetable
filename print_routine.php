<?php
require_once 'config.php';

$settings_res = db_query("SELECT * FROM settings WHERE org_id = '$org_id'");
$settings = [];
if ($settings_res) while ($row = mysqli_fetch_assoc($settings_res))
    $settings[$row['key']] = $row['value'];

$working_days = explode(',', $settings['working_days']);
$periods_count = (int)$settings['periods_per_day'];
$sat_periods = (int)($settings['saturday_periods'] ?? 4);

$classes_data = db_query("SELECT c.*, t.name as class_teacher_name, t.id as class_teacher_id 
                         FROM classes c 
                         LEFT JOIN teachers t ON t.is_class_teacher_of = c.id AND t.org_id = '$org_id'
                         WHERE c.org_id = '$org_id'
                         ORDER BY c.class_name");
$classes = [];
if ($classes_data) while ($row = mysqli_fetch_assoc($classes_data)) {
    $classes[] = $row;
}

$selected_day = $_GET['day'] ?? date('l');
// Validate day
if (!in_array($selected_day, $working_days)) {
    $selected_day = $working_days[0] ?? 'Monday';
}

$today_date = date('Y-m-d');
$adjustments = [];
// Only fetch adjustments if the selected day matches today's actual day
if ($selected_day == date('l')) {
    $adj_res = db_query("SELECT ta.*, tea.name as proxy_name 
                        FROM timetable_adjustments ta
                        JOIN teachers tea ON ta.proxy_teacher_id = tea.id
                        WHERE ta.adjustment_date = '$today_date' AND ta.org_id = '$org_id'");
    if ($adj_res) while ($adj = mysqli_fetch_assoc($adj_res)) {
        $adjustments[$adj['period_number']][$adj['class_id']] = $adj['proxy_name'];
    }
}

$timetable_res = db_query("SELECT t.*, s.subject_name, s.color, tea.name as teacher_name, c.class_name 
                           FROM timetable t 
                           JOIN subjects s ON t.subject_id = s.id 
                           JOIN teachers tea ON t.teacher_id = tea.id
                           JOIN classes c ON t.class_id = c.id
                           WHERE t.day_of_week = '$selected_day' AND t.org_id = '$org_id'
                           ORDER BY t.period_number, c.class_name");

$routine = [];

if ($timetable_res) while ($row = mysqli_fetch_assoc($timetable_res)) {
    $routine[$row['period_number']][$row['class_id']] = $row;
}

$current_day_periods = ($selected_day == 'Saturday') ? $sat_periods : $periods_count;
$lunch_after = (int)$settings['lunch_after_period'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Routine - <?php echo $selected_day; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            color: #1e293b;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #eee;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            margin: 5px 0;
            color: #64748b;
            font-weight: 600;
        }

        .badge-day {
            background: #2563eb;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: center;
            word-wrap: break-word;
        }

        th {
            background: #f1f5f9;
            font-size: 11px;
            text-transform: uppercase;
            color: #475569;
        }

        .class-col {
            width: 100px;
            background: #f8fafc;
            font-weight: 700;
            text-align: left;
        }

        .subject-name {
            font-weight: 800;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .teacher-name {
            font-size: 9px;
            color: #64748b;
        }

        .proxy-mark {
            background: #fef2f2;
            border: 1px dashed #ef4444;
            padding: 4px;
            border-radius: 4px;
            margin-top: 4px;
        }

        .proxy-label {
            color: #ef4444;
            font-weight: 900;
            font-size: 8px;
            text-transform: uppercase;
            display: block;
        }

        .lunch-cell {
            background: #f1f5f9;
            color: #94a3b8;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 2px;
        }

        .ct-badge {
            font-size: 8px;
            background: #dcfce7;
            color: #166534;
            padding: 1px 3px;
            border-radius: 3px;
            margin-left: 2px;
        }

        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }

        .control-panel {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #64748b; color: white; }
    </style>
</head>
<body>

    <div class="no-print control-panel">
        <div>
            <a href="full_routine.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to System</a>
            <span style="margin-left: 15px; font-weight: 600; color: #64748b;">PDF Preview Mode</span>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> Export to PDF / Print
        </button>
    </div>

    <div class="header">
        <h1><?php echo strtoupper($settings['institute_name'] ?? $_SESSION['org_name'] ?? 'Academic Institute'); ?></h1>
        <p>
            Master Class Routine &bull; <span class="badge-day"><?php echo $selected_day; ?></span> 
            <?php if ($selected_day == date('l')): ?>
                &bull; Date: <?php echo date('d M, Y'); ?>
            <?php
endif; ?>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Class / Period</th>
                <?php for ($p = 1; $p <= $current_day_periods; $p++): ?>
                    <th>Period <?php echo $p; ?></th>
                    <?php if ($p == $lunch_after): ?>
                        <th style="width: 40px;">LUNCH</th>
                    <?php
    endif; ?>
                <?php
endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $class): ?>
                <tr>
                    <td class="class-col">
                        <?php echo $class['class_name']; ?>
                        <?php if ($class['class_teacher_name']): ?>
                            <div style="font-size: 8px; color: #94a3b8; font-weight: 500;">CT: <?php echo $class['class_teacher_name']; ?></div>
                        <?php
    endif; ?>
                    </td>
                    <?php for ($p = 1; $p <= $current_day_periods; $p++): ?>
                        <td>
                            <?php if (isset($routine[$p][$class['id']])):
            $item = $routine[$p][$class['id']];
            $proxy_name = $adjustments[$p][$class['id']] ?? null;
            $is_ct = ($class['class_teacher_id'] == $item['teacher_id']);
            $sub_color = $item['color'] ?? '#3b82f6';
?>
                                <div class="subject-name" style="color: <?php echo $sub_color; ?>;">
                                    <?php echo $item['subject_name']; ?>
                                </div>
                                <div class="teacher-name">
                                    <i class="fas <?php echo $is_ct ? 'fa-user-graduate' : 'fa-user-tie'; ?>" style="font-size: 8px;"></i>
                                    <?php echo $item['teacher_name']; ?>
                                    <?php if ($is_ct): ?><span class="ct-badge">CT</span><?php
            endif; ?>
                                </div>

                                <?php if ($proxy_name): ?>
                                    <div class="proxy-mark">
                                        <span class="proxy-label">ARRANGEMENT DONE</span>
                                        <div style="font-size: 9px; font-weight: 700; color: #1e293b;">
                                            <i class="fas fa-user-clock"></i> <?php echo $proxy_name; ?>
                                        </div>
                                    </div>
                                <?php
            endif; ?>
                            <?php
        else: ?>
                                <span style="color: #cbd5e1; font-size: 9px; font-style: italic;">-- Free --</span>
                            <?php
        endif; ?>
                        </td>

                        <?php if ($p == $lunch_after): ?>
                            <td class="lunch-cell">LUNCH</td>
                        <?php
        endif; ?>
                    <?php
    endfor; ?>
                </tr>
            <?php
endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
        <div>Generated on: <?php echo date('d-m-Y H:i'); ?></div>
        <div style="font-weight: 700;">Authorised Signatory</div>
    </div>

    <script>
        // Auto trigger print if specifically asked for PDF
        <?php if (isset($_GET['autoprint'])): ?>
        window.onload = () => window.print();
        <?php
endif; ?>
    </script>
</body>
</html>
