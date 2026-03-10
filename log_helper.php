<?php

function logActivity($conn, $user_id, $username, $full_name, $system_key, $action, $description = '') {
    $username    = $conn->real_escape_string($username);
    $full_name   = $conn->real_escape_string($full_name);
    $system_key  = $conn->real_escape_string($system_key);
    $action      = $conn->real_escape_string($action);
    $description = $conn->real_escape_string($description);
    $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip          = $conn->real_escape_string($ip);

    $conn->query("INSERT INTO system_logs (user_id, username, full_name, system_key, action, description, ip_address)
                  VALUES ($user_id, '$username', '$full_name', '$system_key', '$action', '$description', '$ip')");
}