<?php
require_once '../config.php';

// Handle Add Teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $name = db_escape($_POST['name']);
    $code = db_escape($_POST['employee_code']);

    if (empty($code)) {
        $count_res = db_query("SELECT MAX(id) as max_id FROM teachers WHERE org_id = '$org_id'");
        $count = mysqli_fetch_assoc($count_res)['max_id'] ?? 0;
        $code = "T-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    $limit = db_escape($_POST['weekly_limit']);
    $leisure = db_escape($_POST['leisure_per_day']);
    $max_subs = db_escape($_POST['max_subjects'] ?? 1);

    db_query("INSERT IGNORE INTO teachers (name, employee_code, weekly_limit, leisure_per_day, max_subjects, org_id) VALUES ('$name', '$code', '$limit', '$leisure', '$max_subs', '$org_id')");
}

// Handle Edit Teacher
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_teacher'])) {
    $id = db_escape($_POST['id']);
    $name = db_escape($_POST['name']);
    $code = db_escape($_POST['employee_code']);
    $limit = db_escape($_POST['weekly_limit']);
    $leisure = db_escape($_POST['leisure_per_day']);
    $max_subs = db_escape($_POST['max_subjects']);

    db_query("UPDATE teachers SET name='$name', employee_code='$code', weekly_limit='$limit', leisure_per_day='$leisure', max_subjects='$max_subs' WHERE id=$id AND org_id='$org_id'");
}

// Handle Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_assignment'])) {
    $teacher_id = db_escape($_POST['teacher_id']);
    $subject_id = db_escape($_POST['subject_id']);
    $class_ids = $_POST['class_ids'] ?? [];

    foreach ($class_ids as $class_id) {
        $class_id = db_escape($class_id);
        db_query("INSERT IGNORE INTO teacher_assignments (teacher_id, subject_id, class_id, org_id) VALUES ('$teacher_id', '$subject_id', '$class_id', '$org_id')");
    }
}

// Handle Teacher Status (Class Teacher)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_class_teacher'])) {
    $teacher_id = db_escape($_POST['teacher_id']);
    $class_id = db_escape($_POST['class_id']);

    // Clear previous mapping for this class if any
    db_query("UPDATE teachers SET is_class_teacher_of = NULL WHERE is_class_teacher_of = $class_id AND org_id = '$org_id'");
    db_query("UPDATE teachers SET is_class_teacher_of = $class_id WHERE id = $teacher_id AND org_id = '$org_id'");
}

if (isset($_GET['delete_teacher'])) {
    $id = db_escape($_GET['delete_teacher']);
    db_query("DELETE FROM teachers WHERE id = $id AND org_id = '$org_id'");
}

if (isset($_GET['delete_assignment'])) {
    $id = db_escape($_GET['delete_assignment']);
    db_query("DELETE FROM teacher_assignments WHERE id = $id AND org_id = '$org_id'");
}

$teachers = db_query("SELECT * FROM teachers WHERE org_id = '$org_id' ORDER BY name");
$classes = db_query("SELECT * FROM classes WHERE org_id = '$org_id' ORDER BY class_name");
$subjects = db_query("SELECT * FROM subjects WHERE org_id = '$org_id' ORDER BY subject_name");

// Get all mappings for JS filtering
$mappings_res = db_query("SELECT class_id, subject_id FROM class_subjects WHERE org_id = '$org_id'");
$mappings = [];
while ($m = mysqli_fetch_assoc($mappings_res)) {
    $mappings[$m['class_id']][] = (int)$m['subject_id'];
}
$mappings_json = json_encode($mappings);

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">General</div>
    </div>
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">Classes & Subs</div>
    </div>
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">Mapping</div>
    </div>
    <div class="step active">
        <div class="step-circle">4</div>
        <div class="step-label">Teachers</div>
    </div>
    <div class="step">
        <div class="step-circle">5</div>
        <div class="step-label">Constraints</div>
    </div>
    <div class="step">
        <div class="step-circle">6</div>
        <div class="step-label">Generate</div>
    </div>
</div>

<div class="card fade-in">
    <h2 class="card-title">Add Teacher & Initial Configuration</h2>
    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Teacher Name" required>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Employee Code</label>
            <input type="text" name="employee_code" placeholder="T-001">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Weekly Class Limit</label>
            <input type="number" name="weekly_limit" value="30" min="1">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Min Leisure/Day</label>
            <input type="number" name="leisure_per_day" value="1" min="0">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Max Subjects</label>
            <input type="number" name="max_subjects" value="1" min="1">
        </div>
        <button type="submit" name="add_teacher" class="btn btn-primary" style="height: 44px;">
            <i class="fas fa-user-plus"></i> Add Teacher
        </button>
    </form>
</div>

<div class="card fade-in" style="animation-delay: 0.1s;">
    <h2 class="card-title">Assign Subjects & Classes</h2>
    <div class="alert" style="background: #f0f7ff; color: #0369a1; border: 1px solid #e0f2fe; font-size: 0.85rem; margin-bottom: 2rem; padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; border-left: 4px solid var(--primary);">
        <i class="fas fa-lightbulb" style="font-size: 1.2rem; color: var(--primary);"></i>
        <span><strong>Pro Tip:</strong> Subjects are filtered based on the <b>Class-Subject Mapping</b> you defined in Step 2.</span>
    </div>
    <form method="POST" id="assignmentForm" style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div class="form-group" style="margin-bottom: 0;">
            <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 700; color: var(--text-main);"><i class="fas fa-graduation-cap"></i> Select Classes (Multiple)</span>
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--primary); cursor: pointer; display: flex; align-items: center; gap: 5px; background: white; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <input type="checkbox" id="check_all_classes"> Select All
                </label>
            </label>
            <div id="class_checkbox_container" style="max-height: 150px; overflow-y: auto; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 15px; display: flex; flex-wrap: wrap; gap: 10px;">
                <?php mysqli_data_seek($classes, 0);
while ($row = mysqli_fetch_assoc($classes)): ?>
                <label style="display: flex; align-items: center; gap: 8px; padding: 8px 15px; cursor: pointer; border-radius: 8px; border: 1px solid #f1f5f9; transition: all 0.2s; background: #f8fafc; min-width: 90px; user-select: none;" class="class-card-label">
                    <input type="checkbox" name="class_ids[]" value="<?php echo $row['id']; ?>" class="class-checkbox">
                    <span style="font-size: 0.9rem; font-weight: 600;"><?php echo $row['class_name']; ?></span>
                </label>
                <?php
endwhile; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 150px; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 700; color: var(--text-main);"><i class="fas fa-chalkboard-teacher"></i> Select Teacher</label>
                <select name="teacher_id" required style="height: 45px; border-radius: 8px;">
                    <?php mysqli_data_seek($teachers, 0);
while ($row = mysqli_fetch_assoc($teachers)): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                    <?php
endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-weight: 700; color: var(--text-main);"><i class="fas fa-book"></i> Select Subject</label>
                <select name="subject_id" id="subject_selector" required style="height: 45px; border-radius: 8px;">
                    <option value="">-- Select Classes First --</option>
                    <?php mysqli_data_seek($subjects, 0);
while ($row = mysqli_fetch_assoc($subjects)): ?>
                    <option value="<?php echo $row['id']; ?>" data-all="1"><?php echo $row['subject_name']; ?></option>
                    <?php
endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_assignment" class="btn btn-primary" style="height: 45px; border-radius: 8px; font-weight: 700;">
                <i class="fas fa-link"></i> Assign
            </button>
        </div>
    </form>

    <script>
        const mappings = <?php echo $mappings_json; ?>;
        const subjectSelector = document.getElementById('subject_selector');
        const allSubjectOptions = Array.from(subjectSelector.options).slice(1);
        const checkAllClasses = document.getElementById('check_all_classes');
        const classCheckboxes = document.querySelectorAll('.class-checkbox');

        function updateSubjects() {
            const selectedClasses = Array.from(classCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedClasses.length === 0) {
                subjectSelector.innerHTML = '<option value="">-- Select Classes First --</option>';
                return;
            }

            // Find subjects that are mapped to EVERY selected class
            let allowedSubjects = null;
            selectedClasses.forEach(cid => {
                const classSubs = mappings[cid] || [];
                if (allowedSubjects === null) {
                    allowedSubjects = new Set(classSubs);
                } else {
                    allowedSubjects = new Set([...allowedSubjects].filter(x => classSubs.includes(x)));
                }
            });

            // If no mapping defined for anyone, show all but warning
            const hasMappingsDefined = selectedClasses.some(cid => mappings[cid] && mappings[cid].length > 0);
            
            subjectSelector.innerHTML = '<option value="">-- Choose Subject --</option>';
            allSubjectOptions.forEach(opt => {
                const sid = parseInt(opt.value);
                if (!hasMappingsDefined || (allowedSubjects && allowedSubjects.has(sid))) {
                    subjectSelector.appendChild(opt.cloneNode(true));
                }
            });
            
            if (subjectSelector.options.length <= 1) {
                subjectSelector.innerHTML = '<option value="">No common subjects mapped!</option>';
            }
        }

        classCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateSubjects();
                // Update Check All state
                const allChecked = Array.from(classCheckboxes).every(c => c.checked);
                checkAllClasses.checked = allChecked;

                // Visual highlight update
                const parent = cb.closest('.class-card-label');
                if(cb.checked) {
                    parent.style.borderColor = 'var(--primary)';
                    parent.style.background = '#eff6ff';
                } else {
                    parent.style.borderColor = '#f1f5f9';
                    parent.style.background = '#f8fafc';
                }
            });
        });

        checkAllClasses.addEventListener('change', function() {
            classCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                const parent = cb.closest('.class-card-label');
                if(this.checked) {
                    parent.style.borderColor = 'var(--primary)';
                    parent.style.background = '#eff6ff';
                } else {
                    parent.style.borderColor = '#f1f5f9';
                    parent.style.background = '#f8fafc';
                }
            });
            updateSubjects();
        });

        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const selectedClasses = Array.from(classCheckboxes).filter(cb => cb.checked);
            if (selectedClasses.length === 0) {
                e.preventDefault();
                alert('Please select at least one class!');
            }
        });
    </script>

    <div class="table-responsive">
        <table id="teachers-table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Code</th>
                    <th>Assignments (Subject @ Class)</th>
                    <th>Class Teacher of</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
$teacher_count = mysqli_num_rows($teachers);
$class_count = mysqli_num_rows($classes);
if ($teacher_count > 0 && $teacher_count <= $class_count):
?>
                <div class="alert" style="grid-column: 1 / -1; background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Critical Warning:</strong> You have <?php echo $teacher_count; ?> teachers for <?php echo $class_count; ?> classes. For smooth operation and leisure periods, you should have at least <?php echo $class_count + 1; ?> teachers.
                </div>
                <?php
endif; ?>

                <?php
mysqli_data_seek($teachers, 0);
while ($t = mysqli_fetch_assoc($teachers)):
    $tid = $t['id'];
    $assignments = db_query("SELECT ta.*, s.subject_name, c.class_name, c.section 
                                          FROM teacher_assignments ta 
                                          JOIN subjects s ON ta.subject_id = s.id 
                                          JOIN classes c ON ta.class_id = c.id 
                                          WHERE ta.teacher_id = $tid AND ta.org_id = '$org_id'");
?>
                <tr>
                    <td><strong><?php echo $t['name']; ?></strong><br><small>Limit: <?php echo $t['weekly_limit']; ?>/wk | Max Sub: <?php echo $t['max_subjects'] ?? 1; ?></small></td>
                    <td><?php echo $t['employee_code']; ?></td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php while ($a = mysqli_fetch_assoc($assignments)): ?>
                            <span class="badge" style="background:#f8fafc; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-book-open" style="color: var(--primary); font-size: 0.7rem;"></i>
                                <span><?php echo $a['subject_name']; ?> <b style="color: #64748b;">@</b> <?php echo $a['class_name']; ?></span>
                                <a href="?delete_assignment=<?php echo $a['id']; ?>" style="color:var(--danger); margin-left:4px; opacity: 0.6; transition: 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6" onclick="return confirm('Delete this assignment?')"><i class="fas fa-times-circle"></i></a>
                            </span>
                        <?php
    endwhile; ?>
                        </div>
                    </td>
                    <td>
                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="teacher_id" value="<?php echo $t['id']; ?>">
                            <div style="position: relative; flex: 1;">
                                <select name="class_id" style="padding: 4px 10px; font-size: 0.85rem; height: 36px; width: 100%; border-radius: 6px;">
                                    <option value="">-- None --</option>
                                    <?php mysqli_data_seek($classes, 0);
    while ($c = mysqli_fetch_assoc($classes)): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo($t['is_class_teacher_of'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo $c['class_name']; ?>
                                    </option>
                                    <?php
    endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" name="set_class_teacher" class="btn btn-secondary" style="padding: 0; width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; background: <?php echo $t['is_class_teacher_of'] ? '#dcfce7' : '#f1f5f9'; ?>; border-color: <?php echo $t['is_class_teacher_of'] ? '#bbf7d0' : '#e2e8f0'; ?>; color: <?php echo $t['is_class_teacher_of'] ? '#166534' : '#64748b'; ?>;" title="Save Class Teacher Role">
                                <i class="fas <?php echo $t['is_class_teacher_of'] ? 'fa-id-badge' : 'fa-save'; ?>"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <button onclick='editTeacher(<?php echo json_encode($t); ?>)' class="btn btn-secondary" style="padding: 5px 10px; color: var(--primary);">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete_teacher=<?php echo $t['id']; ?>" class="btn btn-secondary" style="color: var(--danger); padding: 5px 10px;" onclick="return confirm('Delete this teacher and all their assignments?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php
endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Teacher Modal (Simplified) -->
<form id="editTeacherForm" method="POST" style="display:none;">
    <input type="hidden" name="id" id="et_id">
    <input type="hidden" name="name" id="et_name">
    <input type="hidden" name="employee_code" id="et_code">
    <input type="hidden" name="weekly_limit" id="et_limit">
    <input type="hidden" name="leisure_per_day" id="et_leisure">
    <input type="hidden" name="max_subjects" id="et_max">
    <input type="hidden" name="edit_teacher" value="1">
</form>

<script>
function editTeacher(t) {
    const name = prompt("Edit Teacher Name:", t.name);
    if (!name) return;
    const code = prompt("Edit Employee Code:", t.employee_code);
    const limit = prompt("Weekly Limit:", t.weekly_limit);
    const leisure = prompt("Min Leisure/Day:", t.leisure_per_day);
    const max = prompt("Max Unique Subjects:", t.max_subjects);
    
    document.getElementById('et_id').value = t.id;
    document.getElementById('et_name').value = name;
    document.getElementById('et_code').value = code || '';
    document.getElementById('et_limit').value = limit || 30;
    document.getElementById('et_leisure').value = leisure || 1;
    document.getElementById('et_max').value = max || 1;
    document.getElementById('editTeacherForm').submit();
}
</script>

<div style="display: flex; justify-content: space-between; margin-top: 2rem;">
    <a href="step2_mapping.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="step4.php" class="btn btn-primary">Next: Availability & Restrictions <i class="fas fa-arrow-right"></i></a>
</div>

<?php require_once '../includes/footer.php'; ?>
