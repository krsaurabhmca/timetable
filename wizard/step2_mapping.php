<?php
require_once '../config.php';

// Handle Mapping Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_mappings'])) {
    $class_id = db_escape($_POST['class_id']);
    $selected_subjects = $_POST['subject_ids'] ?? [];

    // Clear existing for this class
    db_query("DELETE FROM class_subjects WHERE class_id = $class_id AND org_id = '$org_id'");

    // Add new
    foreach ($selected_subjects as $sid) {
        $sid = db_escape($sid);
        db_query("INSERT INTO class_subjects (class_id, subject_id, org_id) VALUES ($class_id, $sid, '$org_id')");
    }
    $message = "Mappings updated successfully for the selected class.";
}

$classes = db_query("SELECT * FROM classes WHERE org_id = '$org_id' ORDER BY class_name");
$subjects_res = db_query("SELECT * FROM subjects WHERE org_id = '$org_id' ORDER BY subject_name");
$subjects = [];
while ($s = mysqli_fetch_assoc($subjects_res)) {
    $subjects[] = $s;
}

// Get all existing mappings for UI
$mappings_res = db_query("SELECT class_id, subject_id FROM class_subjects WHERE org_id = '$org_id'");
$mappings = [];
while ($m = mysqli_fetch_assoc($mappings_res)) {
    $mappings[$m['class_id']][] = (int)$m['subject_id'];
}
$mappings_json = json_encode($mappings);

require_once '../includes/header.php';
?>

<div class="stepper">
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">General</div></div>
    <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Classes & Subs</div></div>
    <div class="step active"><div class="step-circle">3</div><div class="step-label">Subject Mapping</div></div>
    <div class="step"><div class="step-circle">4</div><div class="step-label">Teachers</div></div>
    <div class="step"><div class="step-circle">5</div><div class="step-label">Constraints</div></div>
    <div class="step"><div class="step-circle">6</div><div class="step-label">Generate</div></div>
</div>

<div class="card fade-in">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; flex-wrap:wrap; gap:0.75rem;">
        <h2 class="card-title" style="margin:0;">Subject Mapping (Class-wise Subjects)</h2>
        <button onclick="loadDemo('auto_map_all', this)" class="btn btn-secondary"
            style="font-size:0.78rem; padding:7px 16px; background:#fefce8; border-color:#fde68a; color:#92400e;">
            <i class="fas fa-wand-magic-sparkles"></i> Auto-Map All Subjects → All Classes
        </button>
    </div>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Select a class to manage its applicable subjects. Only these subjects will appear for this class during teacher assignment.</p>

    <?php if (isset($message)): ?>
        <div class="alert" style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php
endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Class List -->
        <div style="border-right: 1px solid var(--border); padding-right: 2rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--text-main);">1. Select Class</h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php mysqli_data_seek($classes, 0);
while ($c = mysqli_fetch_assoc($classes)):
    $cid = $c['id'];
    $count = isset($mappings[$cid]) ? count($mappings[$cid]) : 0;
?>
                    <button type="button" class="class-btn btn btn-secondary" 
                            style="text-align: left; justify-content: space-between; width: 100%; border: 1px solid var(--border); display: flex; align-items: center;" 
                            onclick="selectClass(<?php echo $c['id']; ?>, '<?php echo $c['class_name']; ?>', this)">
                        <span><i class="fas fa-graduation-cap"></i> <?php echo $c['class_name']; ?></span>
                        <span class="subject-count-badge" style="background: <?php echo $count > 0 ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $count > 0 ? '#15803d' : '#b91c1c'; ?>; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            <?php echo $count; ?> Subs
                        </span>
                    </button>
                <?php
endwhile; ?>
            </div>
        </div>

        <div id="subject-selection-area" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; margin: 0; color: var(--text-main);">2. Select Subjects for <span id="target-class-name" style="color: var(--primary);"></span></h3>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: var(--primary); font-weight: 600;">
                    <input type="checkbox" id="select-all-subjects"> Select All Subjects
                </label>
            </div>
            
            <form method="POST">
                <input type="hidden" name="class_id" id="target-class-id">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border);">
                    <?php foreach ($subjects as $s): ?>
                        <label style="display: flex; align-items: center; gap: 0.75rem; background: white; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); cursor: pointer; transition: all 0.2s;">
                            <input type="checkbox" name="subject_ids[]" value="<?php echo $s['id']; ?>" class="subject-checkbox">
                            <span style="font-size: 0.9rem;"><?php echo $s['subject_name']; ?></span>
                        </label>
                    <?php
endforeach; ?>
                </div>
                
                <button type="submit" name="save_mappings" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                    <i class="fas fa-save"></i> Save Mapping for this Class
                </button>
            </form>
        </div>

        <div id="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); padding: 4rem 0;">
            <i class="fas fa-mouse-pointer" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>Please select a class from the left to manage subjects.</p>
        </div>
    </div>
</div>

<div style="display: flex; justify-content: space-between; margin-top: 2rem;">
    <a href="step2.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="step3.php" class="btn btn-primary">Next: Manage Teachers <i class="fas fa-arrow-right"></i></a>
</div>

<script>
const mappings = <?php echo $mappings_json; ?>;

function selectClass(id, name, btn) {
    // UI Cleanup
    document.querySelectorAll('.class-btn').forEach(b => {
        b.style.background = 'white';
        b.style.borderColor = 'var(--border)';
        b.style.color = 'var(--text-main)';
        b.classList.remove('active-target');
    });
    btn.style.background = 'var(--primary)';
    btn.style.borderColor = 'var(--primary)';
    btn.style.color = 'white';
    btn.classList.add('active-target');

    // Set Form
    document.getElementById('empty-state').style.display = 'none';
    document.getElementById('subject-selection-area').style.display = 'block';
    document.getElementById('target-class-name').innerText = name;
    document.getElementById('target-class-id').value = id;

    // Reset Checkboxes
    const currentMappings = mappings[id] || [];
    const allBoxes = document.querySelectorAll('.subject-checkbox');
    let allChecked = true;

    allBoxes.forEach(cb => {
        const isMapped = currentMappings.includes(parseInt(cb.value));
        cb.checked = isMapped;
        if (!isMapped) allChecked = false;
        
        // Highlight logic
        const parent = cb.closest('label');
        if (cb.checked) {
            parent.style.borderColor = 'var(--primary)';
            parent.style.background = '#eff6ff';
        } else {
            parent.style.borderColor = 'var(--border)';
            parent.style.background = 'white';
        }
    });

    document.getElementById('select-all-subjects').checked = (allBoxes.length > 0 && allChecked);
}

function updateLiveBadge() {
    const activeBtn = document.querySelector('.class-btn.active-target');
    if (!activeBtn) return;
    const badge = activeBtn.querySelector('.subject-count-badge');
    const checkedCount = document.querySelectorAll('.subject-checkbox:checked').length;
    badge.innerText = checkedCount + ' Subs';
    badge.style.background = checkedCount > 0 ? '#dcfce7' : '#fee2e2';
    badge.style.color = checkedCount > 0 ? '#15803d' : '#b91c1c';
}

// Add real-time highlight to checkboxes
document.querySelectorAll('.subject-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const parent = this.closest('label');
        if (this.checked) {
            parent.style.borderColor = 'var(--primary)';
            parent.style.background = '#eff6ff';
        } else {
            parent.style.borderColor = 'var(--border)';
            parent.style.background = 'white';
        }
        
        // Update "Select All" state
        const allBoxes = document.querySelectorAll('.subject-checkbox');
        const checkedBoxes = document.querySelectorAll('.subject-checkbox:checked');
        document.getElementById('select-all-subjects').checked = (allBoxes.length === checkedBoxes.length);
        
        updateLiveBadge();
    });
});

// Select All Logic
document.getElementById('select-all-subjects').addEventListener('change', function() {
    const isChecked = this.checked;
    document.querySelectorAll('.subject-checkbox').forEach(cb => {
        cb.checked = isChecked;
        const parent = cb.closest('label');
        if (isChecked) {
            parent.style.borderColor = 'var(--primary)';
            parent.style.background = '#eff6ff';
        } else {
            parent.style.borderColor = 'var(--border)';
            parent.style.background = 'white';
        }
    });
    updateLiveBadge();
});
</script>

<?php require_once '../includes/footer.php'; ?>

<!-- Demo Loader -->
<div id="demo-toast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999;
    background:#1e293b; color:white; padding:12px 20px; border-radius:10px;
    box-shadow:0 8px 24px rgba(0,0,0,0.2); font-size:0.88rem; font-weight:600;
    align-items:center; gap:10px; min-width:280px;">
    <i class="fas fa-circle-notch fa-spin" id="demo-toast-icon"></i>
    <span id="demo-toast-msg">Loading...</span>
</div>
<script>
function loadDemo(action, btn) {
    const toast = document.getElementById('demo-toast');
    const toastMsg = document.getElementById('demo-toast-msg');
    const toastIcon = document.getElementById('demo-toast-icon');
    toastMsg.textContent = 'Auto-mapping all subjects to all classes...';
    toastIcon.className = 'fas fa-circle-notch fa-spin';
    toastIcon.style.color = 'white';
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
        toastMsg.textContent = data.message || 'Done!';
        setTimeout(() => { toast.style.display = 'none'; location.reload(); }, 1800);
    })
    .catch(() => {
        toastMsg.textContent = 'Request failed.';
        setTimeout(() => { toast.style.display = 'none'; if(btn) btn.disabled=false; }, 2500);
    });
}
</script>
