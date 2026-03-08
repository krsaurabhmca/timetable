<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit();
}

// Verify token
$res = db_query("SELECT id FROM users WHERE reset_token = '$token' AND reset_token_expires > NOW()");
if (mysqli_num_rows($res) == 0) {
    $error = "The password reset link is invalid or has expired.";
}
else {
    $user = mysqli_fetch_assoc($res);
    $user_id = $user['id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password']) && !$error) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    }
    else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

        // Reset and clear token
        db_query("UPDATE users SET password = '$hashed_pass', reset_token = NULL, reset_token_expires = NULL WHERE id = $user_id");
        $success = "Password successfully reset!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - TimeGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 2rem; display: block; text-decoration: none; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; }
        p { color: #64748b; text-align: center; margin-bottom: 2rem; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; transition: all 0.3s; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn { width: 100%; padding: 0.85rem; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 1rem; }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .alert { padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; border: 1px solid transparent; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <a href="login.php" class="logo"><i class="fas fa-calendar-alt"></i> TimeGrid</a>
        <h1>Create New Password</h1>
        <p>Set a new password for your account.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php
endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?><br><br>
                <a href="login.php" class="btn">Proceed to Login</a>
            </div>
        <?php
else: ?>
            <?php if (!$error): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••">
                </div>
                <button type="submit" name="reset_password" class="btn">Update Password</button>
            </form>
            <?php
    endif; ?>
        <?php
endif; ?>
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Back to Login</a>
        </div>
    </div>
</body>
</html>
