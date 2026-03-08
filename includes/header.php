<?php require_once __DIR__ . '/session.php';
require_login(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeGrid - Premium Academic Time Table System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="nav-container">
            <a href="<?php echo BASE_URL; ?>/index.php" class="logo">
                <i class="fas fa-calendar-alt"></i>
                TIME<span>GRID</span>
            </a>
            <div class="nav-links">
                <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem; margin-right: 1rem;">
                    <i class="fas fa-university"></i> <?php echo $_SESSION['org_name']; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-secondary"><i class="fas fa-home"></i></a>
                <a href="<?php echo BASE_URL; ?>/full_routine.php" class="btn btn-secondary">Full Routine</a>
                <a href="<?php echo BASE_URL; ?>/analysis_report.php" class="btn btn-secondary">Analysis</a>
                <a href="<?php echo BASE_URL; ?>/settings.php" class="btn btn-secondary" title="Settings"><i class="fas fa-cog"></i> Settings</a>
                <a href="<?php echo BASE_URL; ?>/wizard/step1.php" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Setup Wizard
                </a>
                <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-secondary" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </nav>
    </header>
    <main class="container">
