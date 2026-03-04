<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once "../config.php";

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['success' => false, 'message' => 'Invalid request']); exit(); }

if ($action === 'add_employee') {
    $employee_id = trim($body['employee_id']    ?? '');
    $name        = trim($body['name']           ?? '');
    $phone       = trim($body['phone']          ?? '');
    $dept        = $body['department']          ?? '';
    $pos         = trim($body['position']       ?? '');
    $emptype     = $body['employment_type']     ?? 'Full Time';
    $rate        = (float)($body['daily_rate']  ?? 0);
    $hire        = $body['hire_date']           ?: null;

    if (!$employee_id) { echo json_encode(['success' => false, 'message' => 'Employee ID required']); exit(); }
    if (!$name || !$dept || !$pos) { echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit(); }

    $chk = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
    $chk->bind_param("s", $employee_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Employee ID already exists']); exit(); }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO employees (employee_id, name, phone, department, position, employment_type, daily_rate, hire_date, is_active) VALUES (?,?,?,?,?,?,?,?,1)");
    $stmt->bind_param("ssssssds", $employee_id, $name, $phone, $dept, $pos, $emptype, $rate, $hire);
    $stmt->execute();
    $new_id = $conn->insert_id;
    $stmt->close();
    echo json_encode(['success' => true, 'id' => $new_id]);
    exit();
}

if ($action === 'edit_employee') {
    $id          = (int)($body['id']            ?? 0);
    $employee_id = trim($body['employee_id']    ?? '');
    $name        = trim($body['name']           ?? '');
    $phone       = trim($body['phone']          ?? '');
    $dept        = $body['department']          ?? '';
    $pos         = trim($body['position']       ?? '');
    $emptype     = $body['employment_type']     ?? 'Full Time';
    $rate        = (float)($body['daily_rate']  ?? 0);
    $hire        = $body['hire_date']           ?: null;

    if (!$id || !$employee_id || !$name || !$dept || !$pos) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit();
    }

    $chk = $conn->prepare("SELECT id FROM employees WHERE employee_id = ? AND id != ?");
    $chk->bind_param("si", $employee_id, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Employee ID already in use']); exit(); }
    $chk->close();

    $stmt = $conn->prepare("UPDATE employees SET employee_id=?, name=?, phone=?, department=?, position=?, employment_type=?, daily_rate=?, hire_date=? WHERE id=?");
    $stmt->bind_param("ssssssdsi", $employee_id, $name, $phone, $dept, $pos, $emptype, $rate, $hire, $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'delete_employee') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id']); exit(); }
    $stmt = $conn->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();