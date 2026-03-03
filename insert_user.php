<?php
require 'config.php';

$username = 'moderator';
$password = 'moderator';
$full_name = 'System Administrator';
$role = 'moderator';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hashed_password, $full_name, $role);

if ($stmt->execute()) {
    echo "User added successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
