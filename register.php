<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $org_name = db_escape($_POST['org_name']);
    $full_name = db_escape($_POST['full_name']);
    $email = db_escape($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = db_query("SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Ye email pehle se registered hai!";
    }
    else {
        // Create organization
        $trial_days = 14;
        $trial_end = date('Y-m-d H:i:s', strtotime("+$trial_days days"));

        mysqli_query($conn, "INSERT INTO organizations (name, email, trial_ends_at) VALUES ('$org_name', '$email', '$trial_end')");
        $org_id = mysqli_insert_id($conn);

        // Populate default settings
        $defaults = [
            ['working_days', 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            ['periods_per_day', '8'],
            ['period_duration', '45'],
            ['break_after_period', '4']
        ];
        foreach ($defaults as $d) {
            mysqli_query($conn, "INSERT INTO settings (`key`, `org_id`, `value`) VALUES ('{$d[0]}', '$org_id', '{$d[1]}')");
        }

        // Create user
        mysqli_query($conn, "INSERT INTO users (org_id, full_name, email, password, role) VALUES ('$org_id', '$full_name', '$email', '$password', 'admin')");

        header("Location: login.php?msg=registered");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - TimeGrid Free Trial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4ade80; --bg: #f8fafc; --secondary: #000000; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem 0; }
        .card { background: white; padding: 3rem; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 500px; border: 1px solid #e2e8f0; }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--secondary); text-align: center; margin-bottom: 2rem; display: block; text-decoration: none; }
        .logo span { color: var(--primary); }
        h1 { font-size: 1.8rem; margin-bottom: 0.5rem; text-align: center; font-weight: 700; }
        p { color: #64748b; text-align: center; margin-bottom: 2.5rem; font-weight: 500; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.95rem; color: var(--secondary); }
        input { width: 100%; padding: 0.85rem 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; transition: all 0.3s; font-size: 1rem; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(74, 222, 128, 0.15); }
        .btn { width: 100%; padding: 1rem; background: var(--primary); color: var(--secondary); border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 1rem; font-size: 1.1rem; }
        .btn:hover { background: #22c55e; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(74, 222, 128, 0.3); }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.95rem; text-align: center; font-weight: 600; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .login-link { text-align: center; margin-top: 2rem; font-size: 1rem; color: #64748b; }
        .login-link a { color: var(--secondary); text-decoration: none; font-weight: 700; border-bottom: 2px solid var(--primary); padding-bottom: 2px; transition: all 0.3s; }
        .login-link a:hover { color: var(--primary); border-color: var(--secondary); }
        
        .trial-badge { display: inline-block; background: var(--secondary); color: var(--primary); padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 0.8rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <a href="index.php" class="logo">TIME<span>GRID</span></a>
        <div style="text-align: center;">
            <span class="trial-badge">14 DAYS FREE TRIAL</span>
        </div>
        <h1>Abhi Register Karein!</h1>
        <p>Apna account banaiye aur school routine ki tension khatam kijiye.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php
endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Institution / School Ka Naam</label>
                <input type="text" name="org_name" required placeholder="Jaise: St. Xavier High School">
            </div>
            <div class="form-group">
                <label>Administrator Ka Pura Naam</label>
                <input type="text" name="full_name" required placeholder="Jaise: Rahul Kumar">
            </div>
            <div class="form-group">
                <label>Work Email Address</label>
                <input type="email" name="email" required placeholder="admin@school.com">
            </div>
            <div class="form-group">
                <label>Ek Naya Password Rakhein</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" name="register" class="btn">Trial Start Karein <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></button>
            <div class="login-link">
                Pehle se account hai? <a href="login.php">Log In Karein</a>
            </div>
        </form>
    </div>
</body>
</html>
