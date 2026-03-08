<?php
require_once 'config.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$org_id = $_SESSION['org_id'];

$success_msg = '';
$error_msg = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = db_escape($_POST['full_name']);
    $email = db_escape($_POST['email']);

    // Check if email already exists for another user
    $check_email = db_query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if (mysqli_num_rows($check_email) > 0) {
        $error_msg = "Email already in use by another account.";
    }
    else {
        $res = db_query("UPDATE users SET full_name = '$full_name', email = '$email' WHERE id = $user_id");
        if ($res) {
            $_SESSION['user_name'] = $full_name;
            $success_msg = "Profile updated successfully!";
        }
        else {
            $error_msg = "Failed to update profile.";
        }
    }
}

// Handle School Info Update
if (isset($_POST['update_school'])) {
    $school_name = db_escape($_POST['school_name']);
    $school_email = db_escape($_POST['school_email']);

    $res = db_query("UPDATE organizations SET name = '$school_name', email = '$school_email' WHERE id = $org_id");
    if ($res) {
        $_SESSION['org_name'] = $school_name;
        $success_msg = "School information updated successfully!";
    }
    else {
        $error_msg = "Failed to update school information.";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error_msg = "New passwords do not match.";
    }
    else {
        $res = db_query("SELECT password FROM users WHERE id = $user_id");
        $user = mysqli_fetch_assoc($res);

        if (password_verify($current_pass, $user['password'])) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password = '$hashed_pass' WHERE id = $user_id");
            $success_msg = "Password changed successfully!";
        }
        else {
            $error_msg = "Current password is incorrect.";
        }
    }
}

// Fetch current data
$user_data = mysqli_fetch_assoc(db_query("SELECT * FROM users WHERE id = $user_id"));
$org_data = mysqli_fetch_assoc(db_query("SELECT * FROM organizations WHERE id = $org_id"));
?>

<div class="fade-in">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 800; color: var(--text-main); margin:0;">Settings</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Manage your account and organization details</p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert" style="background: #dcfce7; color: #166534; padding: 1.25rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
            <span><?php echo $success_msg; ?></span>
        </div>
    <?php
endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert" style="background: #fef2f2; color: #b91c1c; padding: 1.25rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 1.25rem;"></i>
            <span><?php echo $error_msg; ?></span>
        </div>
    <?php
endif; ?>

    <div class="grid" style="display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem;">
        <!-- Sidebar Navigation -->
        <aside>
            <div class="card" style="padding: 10px; position: sticky; top: 100px;">
                <button onclick="switchTab('profile')" class="tab-btn active" id="btn-profile" style="width: 100%; text-align: left; padding: 12px 16px; border-radius: 8px; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 600; color: var(--text-muted); transition: all 0.2s;">
                    <i class="fas fa-user-circle" style="font-size: 1.1rem; width: 20px;"></i> My Profile
                </button>
                <button onclick="switchTab('school')" class="tab-btn" id="btn-school" style="width: 100%; text-align: left; padding: 12px 16px; border-radius: 8px; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 600; color: var(--text-muted); transition: all 0.2s;">
                    <i class="fas fa-university" style="font-size: 1.1rem; width: 20px;"></i> School Info
                </button>
                <button onclick="switchTab('security')" class="tab-btn" id="btn-security" style="width: 100%; text-align: left; padding: 12px 16px; border-radius: 8px; border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 600; color: var(--text-muted); transition: all 0.2s;">
                    <i class="fas fa-shield-alt" style="font-size: 1.1rem; width: 20px;"></i> Security
                </button>
            </div>
        </aside>

        <!-- Dynamic Content Area -->
        <div id="settings-content">
            <!-- Profile Section -->
            <div id="profile-section" class="settings-tab">
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-user-edit" style="color: var(--primary); margin-right: 10px;"></i> Personal Information</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                        <div style="margin-top: 2rem;">
                            <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 10px 25px;">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- School Section -->
            <div id="school-section" class="settings-tab" style="display: none;">
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-hotel" style="color: var(--primary); margin-right: 10px;"></i> Organization Details</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>School / College Name</label>
                            <input type="text" name="school_name" value="<?php echo htmlspecialchars($org_data['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="school_email" value="<?php echo htmlspecialchars($org_data['email']); ?>" required>
                        </div>
                        <div style="margin-top: 2rem;">
                            <button type="submit" name="update_school" class="btn btn-primary" style="padding: 10px 25px;">Update School Info</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Section -->
            <div id="security-section" class="settings-tab" style="display: none;">
                <div class="card" style="margin-bottom: 2rem;">
                    <h2 class="card-title"><i class="fas fa-key" style="color: var(--primary); margin-right: 10px;"></i> Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required placeholder="••••••••">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required placeholder="••••••••">
                            </div>
                        </div>
                        <div style="margin-top: 2rem;">
                            <button type="submit" name="change_password" class="btn btn-primary" style="padding: 10px 25px;">Change Password</button>
                        </div>
                    </form>
                </div>

                <div class="card" style="border: 1px solid #fee2e2; background: #fffafb;">
                    <h2 class="card-title" style="color: #991b1b;"><i class="fas fa-trash-alt" style="margin-right: 10px;"></i> Reset / Danger Zone</h2>
                    <p style="color: #b91c1c; font-size: 0.9rem; margin-bottom: 1.5rem;">Resetting your password via admin or clearing timetable data is usually done here. If you forgot your password, you can trigger a recovery email.</p>
                    <a href="forgot_password.php" class="btn btn-secondary" style="color: #991b1b; border-color: #fecaca; text-decoration: none;">
                        <i class="fas fa-life-ring"></i> Reset My Password Via Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-btn.active {
        background: #f1f5f9 !important;
        color: var(--primary) !important;
    }
    .tab-btn:hover:not(.active) {
        background: #f8fafc !important;
        color: var(--text-main) !important;
    }
</style>

<script>
function switchTab(tabId) {
    // Hide all tabs
    document.querySelectorAll('.settings-tab').forEach(tab => tab.style.display = 'none');
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show target tab
    document.getElementById(tabId + '-section').style.display = 'block';
    // Activate target button
    document.getElementById('btn-' + tabId).classList.add('active');
    
    // Smooth scroll to top of content
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Check for URL hash to switch tab
window.onload = function() {
    const hash = window.location.hash.replace('#', '');
    if (hash && ['profile', 'school', 'security'].includes(hash)) {
        switchTab(hash);
    }
};
</script>

<?php require_once 'includes/footer.php'; ?>
