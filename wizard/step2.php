<?php
require_once '../config.php';

// Handle Deletion
if (isset($_GET['delete_class'])) {
    $id = db_escape($_GET['delete_class']);
    db_query("DELETE FROM classes WHERE id = $id AND org_id = '$org_id'");
}
if (isset($_GET['delete_subject'])) {
    $id = db_escape($_GET['delete_subject']);
    db_query("DELETE FROM subjects WHERE id = $id AND org_id = '$org_id'");
}
if (isset($_GET['delete_mapping'])) {
    $id = db_escape($_GET['delete_mapping']);
    db_query("DELETE FROM class_subjects WHERE id = $id AND org_id = '$org_id'");
}

// Handle Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_class'])) {
        $name = db_escape($_POST['class_name']);
        db_query("INSERT IGNORE INTO classes (class_name, section, org_id) VALUES ('$name', '', '$org_id')");
    }
    if (isset($_POST['add_subject'])) {
        $name = db_escape($_POST['subject_name']);
        $priority = db_escape($_POST['priority'] ?? 3);
        $color = db_escape($_POST['color'] ?? '');
        db_query("INSERT IGNORE INTO subjects (subject_name, priority, color, org_id) VALUES ('$name', '$priority', '$color', '$org_id')");
    }
    if (isset($_POST['map_subject'])) {
        $class_id = db_escape($_POST['class_id']);
        $subject_id = db_escape($_POST['subject_id']);
        db_query("INSERT IGNORE INTO class_subjects (class_id, subject_id, org_id) VALUES ($class_id, $subject_id, '$org_id')");
    }
    // Handle Editing
    if (isset($_POST['edit_class'])) {
        $id = db_escape($_POST['id']);
        $name = db_escape($_POST['class_name']);
        db_query("UPDATE classes SET class_name = '$name' WHERE id = $id AND org_id = '$org_id'");
    }
    if (isset($_POST['edit_subject'])) {
        $id = db_escape($_POST['id']);
        $name = db_escape($_POST['subject_name']);
        $priority = db_escape($_POST['priority']);
        $color = db_escape($_POST['color'] ?? '');
        db_query("UPDATE subjects SET subject_name = '$name', priority = '$priority', color = '$color' WHERE id = $id AND org_id = '$org_id'");
    }
}

$classes = db_query("SELECT * FROM classes WHERE org_id = '$org_id' ORDER BY class_name");
$subjects = db_query("SELECT * FROM subjects WHERE org_id = '$org_id' ORDER BY subject_name");
$mappings = db_query("SELECT cs.*, c.class_name, c.section, s.subject_name 
                      FROM class_subjects cs 
                      JOIN classes c ON cs.class_id = c.id 
                      JOIN subjects s ON cs.subject_id = s.id 
                      WHERE cs.org_id = '$org_id'
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <h2 class="card-title" style="margin:0;">Manage Classes</h2>
            <button onclick="loadDemo('load_demo_classes', this)" class="btn btn-secondary"
                style="font-size:0.78rem; padding:6px 14px; background:#f0fdf4; border-color:#bbf7d0; color:#15803d;">
                <i class="fas fa-wand-magic-sparkles"></i> Load Demo Classes (V–X)
            </button>
        </div>
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
$counts_res = db_query("SELECT class_id, COUNT(*) as count FROM class_subjects WHERE org_id = '$org_id' GROUP BY class_id");
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <h2 class="card-title" style="margin:0;">Manage Subjects</h2>
            <button onclick="loadDemo('load_demo_subjects', this)" class="btn btn-secondary"
                style="font-size:0.78rem; padding:6px 14px; background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;">
                <i class="fas fa-wand-magic-sparkles"></i> Load CBSE Subjects
            </button>
        </div>
        <form method="POST" style="margin-bottom: 2rem;">
            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="text" name="subject_name" placeholder="Subject Name" required style="flex: 2;">
                    <div title="Pick Subject Color" style="display: flex; align-items: center; background: white; border: 1px solid var(--border); border-radius: 8px; padding: 2px 8px; height: 38px;">
                        <input type="color" name="color" value="#3b82f6" style="width: 30px; height: 30px; border: none; background: none; cursor: pointer;">
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <select name="priority" style="flex: 1;" title="Priority: 1 is High/Early, 5 is Low/Late">
                        <option value="1">Priority 1 (Extreme Early)</option>
                        <option value="2">Priority 2 (Early)</option>
                        <option value="3" selected>Priority 3 (Default)</option>
                        <option value="4">Priority 4 (Late)</option>
                        <option value="5">Priority 5 (Last Slots)</option>
                    </select>
                    <button type="submit" name="add_subject" class="btn btn-primary" style="flex: 1;"><i class="fas fa-plus"></i> Add Subject</button>
                </div>
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
while ($row = mysqli_fetch_assoc($subjects)):
    $p = $row['priority'] ?? 3;
    $p_label = ($p <= 2) ? 'High' : (($p >= 4) ? 'Low' : 'Normal');
    $p_color = ($p <= 2) ? '#ef4444' : (($p >= 4) ? '#64748b' : '#3b82f6');
    $sub_color = !empty($row['color']) ? $row['color'] : 'var(--primary)';
?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 12px; height: 12px; border-radius: 3px; background: <?php echo $sub_color; ?>; box-shadow: 0 0 0 2px white, 0 0 0 3px <?php echo $sub_color; ?>44;"></div>
                                <div style="font-weight: 600;"><?php echo $row['subject_name']; ?></div>
                            </div>
                            <div style="font-size: 0.7rem; color: <?php echo $p_color; ?>; font-weight: 700; text-transform: uppercase; margin-left: 22px;">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i> <?php echo $p_label; ?> Priority
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <span class="badge" title="Edit Priority" onclick="openEditSubject(<?php echo $row['id']; ?>, '<?php echo $row['subject_name']; ?>', '<?php echo $p; ?>', '<?php echo $row['color']; ?>')" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-weight: 800; cursor: pointer;">P<?php echo $p; ?></span>
                                <button onclick="openEditSubject(<?php echo $row['id']; ?>, '<?php echo $row['subject_name']; ?>', '<?php echo $p; ?>', '<?php echo $row['color']; ?>')" class="btn btn-secondary" style="padding: 5px 10px; color: var(--primary);">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete_subject=<?php echo $row['id']; ?>" class="btn btn-secondary" style="color: var(--danger); padding: 5px 10px;" onclick="return confirm('Are you sure?')">
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

<!-- Edit Subject Modal -->
<div id="subjectModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Subject</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <form id="editSubjectForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_sub_id">
                
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" name="subject_name" id="edit_sub_name" required>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" id="edit_sub_priority">
                        <option value="1">Priority 1 (Extreme Early)</option>
                        <option value="2">Priority 2 (Early)</option>
                        <option value="3">Priority 3 (Default)</option>
                        <option value="4">Priority 4 (Late)</option>
                        <option value="5">Priority 5 (Last Slots)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subject Color</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="color" id="edit_sub_color">
                        <span id="color_hex_display" style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">#000000</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="edit_subject" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Demo Loader JS ─────────────────────────────────── -->
<div id="demo-toast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999;
    background:#1e293b; color:white; padding:12px 20px; border-radius:10px;
    box-shadow:0 8px 24px rgba(0,0,0,0.2); font-size:0.88rem; font-weight:600;
    align-items:center; gap:10px; min-width:280px;">
    <i class="fas fa-circle-notch fa-spin" id="demo-toast-icon"></i>
    <span id="demo-toast-msg">Loading...</span>
</div>

<script>
function loadDemo(action, btn) {
    const labels = {
        load_demo_classes:  'Loading Demo Classes (V–X)...',
        load_demo_subjects: 'Loading CBSE Subjects...',
    };
    const toast   = document.getElementById('demo-toast');
    const toastMsg = document.getElementById('demo-toast-msg');
    const toastIcon = document.getElementById('demo-toast-icon');

    toastMsg.textContent = labels[action] || 'Loading...';
    toastIcon.className = 'fas fa-circle-notch fa-spin';
    toast.style.display = 'flex';
    if (btn) btn.disabled = true;

    fetch('../api/demo_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=' + action
    })
    .then(r => r.json())
    .then(data => {
        toastIcon.className = data.success ? 'fas fa-check-circle' : 'fas fa-times-circle';
        toastIcon.style.color = data.success ? '#4ade80' : '#ef4444';
        toastMsg.textContent  = data.message || (data.success ? 'Done!' : 'Error');
        setTimeout(() => { toast.style.display = 'none'; location.reload(); }, 1800);
    })
    .catch(() => {
        toastMsg.textContent = 'Request failed. Check server.';
        toastIcon.className  = 'fas fa-times-circle';
        toastIcon.style.color = '#ef4444';
        setTimeout(() => { toast.style.display = 'none'; if (btn) btn.disabled = false; }, 2500);
    });
}
</script>
<script>
function editClass(id, name) {
    const newName = prompt("Edit Class Name:", name);
    if (!newName) return;
    
    // Quick class edit still uses prompt as requested only for Subject
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="class_name" value="${newName}">
        <input type="hidden" name="edit_class" value="1">
    `;
    document.body.appendChild(form);
    form.submit();
}

function openEditSubject(id, name, priority, color) {
    document.getElementById('edit_sub_id').value = id;
    document.getElementById('edit_sub_name').value = name;
    document.getElementById('edit_sub_priority').value = priority;
    document.getElementById('edit_sub_color').value = color || '#3b82f6';
    document.getElementById('color_hex_display').innerText = color || '#3b82f6';
    
    document.getElementById('subjectModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('subjectModal').style.display = 'none';
}

// Update hex display when color picker changes
document.getElementById('edit_sub_color').addEventListener('input', function(e) {
    document.getElementById('color_hex_display').innerText = e.target.value.toUpperCase();
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('subjectModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<div style="display: flex; justify-content: space-between; margin-top: 2rem;">
    <a href="step1.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="step2_mapping.php" class="btn btn-primary">Next: Subject Mapping <i class="fas fa-arrow-right"></i></a>
</div>

<?php require_once '../includes/footer.php'; ?>
