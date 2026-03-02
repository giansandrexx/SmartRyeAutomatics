<?php


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

if ($user_role != 'moderator') {

    $_SESSION['error_message'] = "Access denied. Only moderators can access this page.";
    header("Location: dashboard.php");
    exit();
}
?>