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

if ($action === 'employees') {
    $dept = $_GET['dept'] ?? '';
    if ($dept) {
        $stmt = $conn->prepare("SELECT id, employee_id, name, department, position, employment_type, color FROM employees WHERE is_active = 1 AND department = ? ORDER BY name");
        $stmt->bind_param("s", $dept);
    } else {
        $stmt = $conn->prepare("SELECT id, employee_id, name, department, position, employment_type, color FROM employees WHERE is_active = 1 ORDER BY department, name");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        if (empty($r['color'])) $r['color'] = '135deg,#1245a8,#42a5f5';
        $rows[] = $r;
    }
    $stmt->close();
    echo json_encode($rows);
    exit;
}

if ($action === 'month') {
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));

    $date_from = sprintf('%04d-%02d-01', $year, $month);
    $date_to   = date('Y-m-t', strtotime($date_from));

    $stmt = $conn->prepare("
        SELECT emp_id, att_date,
               TIME_FORMAT(time_in,  '%H:%i') AS time_in,
               TIME_FORMAT(time_out, '%H:%i') AS time_out
        FROM attendance
        WHERE att_date BETWEEN ? AND ?
        ORDER BY att_date ASC, emp_id ASC
    ");
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $emp  = (int)$row['emp_id'];
        $date = $row['att_date'];
        if (!isset($data[$emp][$date])) {
            $data[$emp][$date] = ['in' => null, 'out' => null];
        }
        if (!empty($row['time_in']))  $data[$emp][$date]['in']  = $row['time_in'];
        if (!empty($row['time_out'])) $data[$emp][$date]['out'] = $row['time_out'];
    }
    $stmt->close();
    echo json_encode($data);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();