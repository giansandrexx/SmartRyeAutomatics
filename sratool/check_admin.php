<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

if ($user_role != 'admin') {

    $_SESSION['error_message'] = "Access denied. You don't have permission to access this page.";
    header("Location: dashboard.php");
    exit();
}

?>