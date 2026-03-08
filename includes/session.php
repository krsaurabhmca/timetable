<?php

function is_logged_in()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['org_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

function get_org_id()
{
    return $_SESSION['org_id'] ?? null;
}

function get_user_name()
{
    return $_SESSION['user_name'] ?? 'User';
}
?>
