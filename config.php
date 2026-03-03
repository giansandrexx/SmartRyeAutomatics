<?php
date_default_timezone_set('Asia/Manila');

define('DB_HOST', 'yamabiko.proxy.rlwy.net');
define('DB_USER', 'root');
define('DB_PASS', 'lZcnXJljGzWWDUuTMBASqvSTOYpoyQOD');
define('DB_NAME', 'railway');
define('DB_PORT', 27491);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");
