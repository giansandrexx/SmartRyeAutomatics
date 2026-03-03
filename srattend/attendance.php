<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'srasystem');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {

    if ($action === 'employees') {
        $dept = $_GET['dept'] ?? '';
        if ($dept) {
            $stmt = $conn->prepare("SELECT id, name, department, color FROM employees WHERE is_active=1 AND department=? ORDER BY name");
            $stmt->bind_param("s", $dept);
        } else {
            $stmt = $conn->prepare("SELECT id, name, department, color FROM employees WHERE is_active=1 ORDER BY department, name");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $employees = [];
        while ($row = $result->fetch_assoc()) { $employees[] = $row; }
        $stmt->close();
        echo json_encode($employees);
        exit;
    }

    if ($action === 'week') {
        $week_start = $_GET['week_start'] ?? date('Y-m-d');
        $week_end   = date('Y-m-d', strtotime($week_start . ' +5 days'));
        $stmt = $conn->prepare("
            SELECT emp_id, att_date,
                   TIME_FORMAT(time_in,  '%H:%i') AS time_in,
                   TIME_FORMAT(time_out, '%H:%i') AS time_out
            FROM attendance
            WHERE att_date BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $week_start, $week_end);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['emp_id']][$row['att_date']] = [
                'in'  => $row['time_in'],
                'out' => $row['time_out'],
            ];
        }
        $stmt->close();
        echo json_encode($data);
        exit;
    }

    if ($action === 'overtime') {
        $week_start = $_GET['week_start'] ?? date('Y-m-d');
        $stmt = $conn->prepare("SELECT emp_id, ot_morning, ot_afternoon FROM overtime WHERE week_start = ?");
        $stmt->bind_param("s", $week_start);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['emp_id']] = ['m' => $row['ot_morning'], 'a' => $row['ot_afternoon']];
        }
        $stmt->close();
        echo json_encode($data);
        exit;
    }
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['success' => false, 'message' => 'Invalid JSON']); exit; }

    if ($action === 'save_attendance') {
        $emp_id   = (int)($body['emp_id']   ?? 0);
        $att_date = $body['att_date']  ?? '';
        $time_in  = $body['time_in']   ?: null;
        $time_out = $body['time_out']  ?: null;
        if (!$emp_id || !$att_date) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
        $stmt = $conn->prepare("
            INSERT INTO attendance (emp_id, att_date, time_in, time_out)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out)
        ");
        $stmt->bind_param("isss", $emp_id, $att_date, $time_in, $time_out);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_overtime') {
        $emp_id     = (int)($body['emp_id']     ?? 0);
        $week_start = $body['week_start'] ?? '';
        $ot_m       = (float)($body['ot_morning']   ?? 0);
        $ot_a       = (float)($body['ot_afternoon'] ?? 0);
        if (!$emp_id || !$week_start) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
        $stmt = $conn->prepare("
            INSERT INTO overtime (emp_id, week_start, ot_morning, ot_afternoon)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE ot_morning = VALUES(ot_morning), ot_afternoon = VALUES(ot_afternoon)
        ");
        $stmt->bind_param("isdd", $emp_id, $week_start, $ot_m, $ot_a);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_employee') {
        $name  = trim($body['name']  ?? '');
        $dept  = $body['dept']  ?? 'Field';
        $color = $body['color'] ?? '135deg,#1245a8,#42a5f5';
        if (!$name) { echo json_encode(['success' => false, 'message' => 'Name required']); exit; }
        $stmt = $conn->prepare("INSERT INTO employees (name, department, color) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $dept, $color);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $new_id]);
        exit;
    }

    if ($action === 'edit_employee') {
        $id   = (int)($body['id']   ?? 0);
        $name = trim($body['name'] ?? '');
        $dept = $body['dept'] ?? 'Field';
        if (!$id || !$name) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
        $stmt = $conn->prepare("UPDATE employees SET name = ?, department = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $dept, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_employee') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }
        $stmt = $conn->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();