<?php
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = db_escape($_POST['email']);
    $password = $_POST['password'];

    $res = db_query("SELECT u.*, o.name as org_name, o.subscription_status 
                     FROM users u 
                     JOIN organizations o ON u.org_id = o.id 
                     WHERE u.email = '$email'");

    if ($user = mysqli_fetch_assoc($res)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['org_id'] = $user['org_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['org_name'] = $user['org_name'];

            header("Location: dashboard.php");
            exit();
        }
        else {
            $error = "Invalid password.";
        }
    }
    else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TimeGrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4ade80; --bg: #f8fafc; --secondary: #000000; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 400px; border: 1px solid #e2e8f0; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--secondary); text-align: center; margin-bottom: 2rem; display: block; text-decoration: none; }
        .logo span { color: var(--primary); }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; text-align: center; font-weight: 700; }
        p { color: #64748b; text-align: center; margin-bottom: 2.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; transition: all 0.3s; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1); }
        .btn { width: 100%; padding: 0.85rem; background: var(--primary); color: var(--secondary); border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 1rem; }
        .btn:hover { background: #22c55e; transform: translateY(-1px); }
        .alert { padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; text-align: center; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="card">
        <a href="index.php" class="logo"><i class="fas fa-calendar-alt"></i> TimeGrid</a>
        <h1>Welcome Back</h1>
        <p>Log in to manage your routine</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php
endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
            <div class="alert alert-success">Registration successful! Please log in.</div>
        <?php
endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="admin@school.com">
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Forgot Password?</a>
                </div>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" name="login" class="btn">Log In</button>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Sign Up</a>
            </div>
        </form>
    </div>
</body>
</html>
