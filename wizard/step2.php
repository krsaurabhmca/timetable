<?php
require_once '../config.php';

// Handle Deletion
if (isset($_GET['delete_class'])) {
    $id = db_escape($_GET['delete_class']);
    db_query("DELETE FROM classes WHERE id = $id");
}
if (isset($_GET['delete_subject'])) {
    $id = db_escape($_GET['delete_subject']);
    db_query("DELETE FROM subjects WHERE id = $id");
}
if (isset($_GET['delete_mapping'])) {
    $id = db_escape($_GET['delete_mapping']);
    db_query("DELETE FROM class_subjects WHERE id = $id");
}

// Handle Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_class'])) {
        $name = db_escape($_POST['class_name']);
        db_query("INSERT INTO classes (class_name, section) VALUES ('$name', '')");
    }
    if (isset($_POST['add_subject'])) {
        $name = db_escape($_POST['subject_name']);
        $priority = db_escape($_POST['priority'] ?? 3);
        db_query("INSERT INTO subjects (subject_name, priority) VALUES ('$name', '$priority')");
    }
    if (isset($_POST['map_subject'])) {
        $class_id = db_escape($_POST['class_id']);
        $subject_id = db_escape($_POST['subject_id']);
        db_query("INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES ($class_id, $subject_id)");
    }
    // Handle Editing
    if (isset($_POST['edit_class'])) {
        $id = db_escape($_POST['id']);
        $name = db_escape($_POST['class_name']);
        db_query("UPDATE classes SET class_name = '$name' WHERE id = $id");
    }
    if (isset($_POST['edit_subject'])) {
        $id = db_escape($_POST['id']);
        $name = db_escape($_POST['subject_name']);
        $priority = db_escape($_POST['priority']);
        db_query("UPDATE subjects SET subject_name = '$name', priority = '$priority' WHERE id = $id");
    }
}

$classes = db_query("SELECT * FROM classes ORDER BY class_name");
$subjects = db_query("SELECT * FROM subjects ORDER BY subject_name");
$mappings = db_query("SELECT cs.*, c.class_name, c.section, s.subject_name 
                      FROM class_subjects cs 
                      JOIN classes c ON cs.class_id = c.id 
                      JOIN subjects s ON cs.subject_id = s.id 
                      ORDER BY c.class_name, s.subject_name");

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step completed">
        <div class="step-circle"><i class="fas fa-check"></i></div>
        <div class="step-label">General</div>
    </div>
    <div class="step active">
        <div class="step-circle">2</div>
        <div class="step-label">Classes & Subs</div>
    </div>
    <div class="step">
        <div class="step-circle">3</div>
        <div class="step-label">Mapping</div>
    </div>
    <div class="step">
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

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
    <!-- Classes Section -->
    <div class="card fade-in">
        <h2 class="card-title">Manage Classes</h2>
        <form method="POST" style="margin-bottom: 2rem;">
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" name="class_name" placeholder="Class Name (e.g. 10A, 9B)" required style="flex: 1;">
                <button type="submit" name="add_class" class="btn btn-primary"><i class="fas fa-plus"></i> Add Class</button>
            </div>
        </form>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($classes, 0);
$classes_counts = [];
$counts_res = db_query("SELECT class_id, COUNT(*) as count FROM class_subjects GROUP BY class_id");
while ($cr = mysqli_fetch_assoc($counts_res))
    $classes_counts[$cr['class_id']] = $cr['count'];

while ($row = mysqli_fetch_assoc($classes)):
    $count = $classes_counts[$row['id']] ?? 0;
?>
                    <tr>
                        <td style="display: flex; align-items: center; justify-content: space-between;">
                            <span><?php echo $row['class_name']; ?></span>
                            <span class="badge" style="background: <?php echo $count > 0 ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $count > 0 ? '#15803d' : '#b91c1c'; ?>; font-size: 0.7rem;">
                                <?php echo $count; ?> Subs
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="editClass(<?php echo $row['id']; ?>, '<?php echo $row['class_name']; ?>')" class="btn btn-secondary" style="padding: 5px 10px; color: var(--primary);">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete_class=<?php echo $row['id']; ?>" class="btn btn-secondary" style="color: var(--danger); padding: 5px 10px;" onclick="return confirm('Are you sure? This will delete all teacher assignments and timetable entries for this class.')">
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

    <!-- Subjects Section -->
    <div class="card fade-in" style="animation-delay: 0.1s;">
        <h2 class="card-title">Manage Subjects</h2>
        <form method="POST" style="margin-bottom: 2rem;">
            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="subject_name" placeholder="Subject Name" required style="flex: 2;">
                    <select name="priority" style="flex: 1;" title="Priority: 1 is High/Early, 5 is Low/Late">
                        <option value="1">Priority 1 (Extreme Early)</option>
                        <option value="2">Priority 2 (Early)</option>
                        <option value="3" selected>Priority 3 (Default)</option>
                        <option value="4">Priority 4 (Late)</option>
                        <option value="5">Priority 5 (Last Slots)</option>
                    </select>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary"><i class="fas fa-plus"></i> Add Subject</button>
            </div>
        </form>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($subjects, 0);
while ($row = mysqli_fetch_assoc($subjects)): ?>
                    <tr>
                        <td><?php echo $row['subject_name']; ?></td>
                        <td><span class="badge" style="background: #e0f2fe; color: #0369a1;">P<?php echo $row['priority'] ?? 3; ?></span></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="editSubject(<?php echo $row['id']; ?>, '<?php echo $row['subject_name']; ?>', '<?php echo $row['priority'] ?? 3; ?>')" class="btn btn-secondary" style="padding: 5px 10px; color: var(--primary);">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete_subject=<?php echo $row['id']; ?>" class="btn btn-secondary" style="color: var(--danger); padding: 5px 10px;" onclick="return confirm('Are you sure? This will delete all teacher assignments for this subject.')">
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
</div>

<!-- Edit Modals (Simplified as prompt/hidden forms for speed) -->
<form id="editForm" method="POST" style="display:none;">
    <input type="hidden" name="id" id="edit_id">
    <input type="hidden" name="class_name" id="edit_class_name">
    <input type="hidden" name="section" id="edit_section">
    <input type="hidden" name="subject_name" id="edit_subject_name">
    <input type="hidden" name="priority" id="edit_priority">
    <input type="hidden" name="edit_class" id="edit_submit_class">
    <input type="hidden" name="edit_subject" id="edit_submit_subject">
</form>

<script>
function editClass(id, name) {
    const newName = prompt("Edit Class Name:", name);
    if (newName === null) return;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_class_name').value = newName;
    document.getElementById('edit_submit_class').value = '1';
    document.getElementById('editForm').submit();
}

function editSubject(id, name, priority) {
    const newName = prompt("Edit Subject Name:", name);
    if (newName === null) return;
    const newPriority = prompt("Edit Priority (1-5):", priority);
    if (newPriority === null) return;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_subject_name').value = newName;
    document.getElementById('edit_priority').value = newPriority;
    document.getElementById('edit_submit_subject').value = '1';
    document.getElementById('editForm').submit();
}
</script>

<div style="display: flex; justify-content: space-between; margin-top: 2rem;">
    <a href="step1.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="step2_mapping.php" class="btn btn-primary">Next: Subject Mapping <i class="fas fa-arrow-right"></i></a>
</div>

<?php require_once '../includes/footer.php'; ?>
