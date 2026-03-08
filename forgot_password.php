<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = db_escape($_POST['email']);

    // Check if email exists
    $res = db_query("SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($res) > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        db_query("UPDATE users SET reset_token = '$token', reset_token_expires = '$expires' WHERE email = '$email'");

        // In a real app, send actual email here
        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL . "/reset_password.php?token=$token";

        $success = "Recovery link generated! (Demo: <a href='$reset_link'>Click here to reset</a>)";
    }
    else {
        $error = "Email address not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TimeGrid</title>
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
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; word-break: break-all; }
    </style>
</head>
<body>
    <div class="card">
        <a href="login.php" class="logo"><i class="fas fa-calendar-alt"></i> TimeGrid</a>
        <h1>Reset Password</h1>
        <p>Enter your email to receive a password recovery link.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php
endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php
endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="admin@school.com">
            </div>
            <button type="submit" name="forgot_password" class="btn">Send Recovery Link</button>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                Remembered? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
