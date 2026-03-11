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
require_once "../log_helper.php";

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$uid   = $_SESSION['user_id'];                    
$uname = $_SESSION['username']  ?? 'unknown';
$uful  = $_SESSION['full_name'] ?? 'Unknown User'; 

if ($method === 'GET') {

    if ($action === 'employees') {
        $dept = $_GET['dept'] ?? '';
        if ($dept) {
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, position, color FROM employees WHERE is_active=1 AND department=? ORDER BY hire_date ASC");
            $stmt->bind_param("s", $dept);
        } else {
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, position, color FROM employees WHERE is_active=1 ORDER BY hire_date ASC");
        }
        $stmt->execute();
        $rows = [];
        $res  = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $stmt->close();
        echo json_encode($rows);
        exit;
    }

    if ($action === 'trips') {
        $week_start = $_GET['week_start'] ?? date('Y-m-d');
        $week_end   = date('Y-m-d', strtotime($week_start . ' +5 days'));
        $stmt = $conn->prepare("
            SELECT emp_id, trip_date, location,
                   TIME_FORMAT(depart_office, '%H:%i') AS depart_office,
                   TIME_FORMAT(arrive_site,   '%H:%i') AS arrive_site,
                   TIME_FORMAT(depart_site,   '%H:%i') AS depart_site,
                   TIME_FORMAT(arrive_office, '%H:%i') AS arrive_office,
                   is_eligible, ot_hours, ot_minutes, notes
            FROM overtime_trips WHERE trip_date BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $week_start, $week_end);
        $stmt->execute();
        $data = [];
        $res  = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $data[$r['emp_id']][$r['trip_date']] = [
                'location'      => $r['location'],
                'depart_office' => $r['depart_office'],
                'arrive_site'   => $r['arrive_site'],
                'depart_site'   => $r['depart_site'],
                'arrive_office' => $r['arrive_office'],
                'is_eligible'   => $r['is_eligible'],
                'ot_hours'      => (int)$r['ot_hours'],
                'ot_minutes'    => (int)$r['ot_minutes'],
                'notes'         => $r['notes'],
            ];
        }
        $stmt->close();
        echo json_encode($data);
        exit;
    }
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['success' => false, 'message' => 'Invalid JSON']); exit; }

    if ($action === 'save_trip') {
        $emp_id        = (int)($body['emp_id']        ?? 0);
        $trip_date     = $body['trip_date']     ?? '';
        $location      = trim($body['location'] ?? '');
        $depart_office = $body['depart_office'] ?: null;
        $arrive_site   = $body['arrive_site']   ?: null;
        $depart_site   = $body['depart_site']   ?: null;
        $arrive_office = $body['arrive_office'] ?: null;
        $is_eligible   = isset($body['is_eligible']) && $body['is_eligible'] !== '' ? (int)$body['is_eligible'] : null;
        $ot_hours      = (int)($body['ot_hours']   ?? 0);
        $ot_minutes    = (int)($body['ot_minutes'] ?? 0);
        $notes         = trim($body['notes'] ?? '');

        if (!$emp_id || !$trip_date) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }

        $empRow = $conn->query("SELECT name FROM employees WHERE id=$emp_id")->fetch_assoc();
        $empName = $empRow['name'] ?? "Employee #$emp_id";

        $stmt = $conn->prepare("
            INSERT INTO overtime_trips
                (emp_id, trip_date, location, depart_office, arrive_site, depart_site, arrive_office, is_eligible, ot_hours, ot_minutes, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                location      = VALUES(location),
                depart_office = VALUES(depart_office),
                arrive_site   = VALUES(arrive_site),
                depart_site   = VALUES(depart_site),
                arrive_office = VALUES(arrive_office),
                is_eligible   = VALUES(is_eligible),
                ot_hours      = VALUES(ot_hours),
                ot_minutes    = VALUES(ot_minutes),
                notes         = VALUES(notes)
        ");
        $stmt->bind_param("issssssiiss",
            $emp_id, $trip_date, $location,
            $depart_office, $arrive_site, $depart_site, $arrive_office,
            $is_eligible, $ot_hours, $ot_minutes, $notes
        );
        if ($stmt->execute()) {
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Saved Overtime Trip',
                "Employee: $empName | Date: $trip_date | Location: $location | OT: {$ot_hours}h {$ot_minutes}m");
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_trip') {
        $emp_id    = (int)($body['emp_id']    ?? 0);
        $trip_date = $body['trip_date'] ?? '';
        if (!$emp_id || !$trip_date) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }

        $empRow  = $conn->query("SELECT name FROM employees WHERE id=$emp_id")->fetch_assoc();
        $empName = $empRow['name'] ?? "Employee #$emp_id";

        $stmt = $conn->prepare("DELETE FROM overtime_trips WHERE emp_id=? AND trip_date=?");
        $stmt->bind_param("is", $emp_id, $trip_date);
        if ($stmt->execute()) { 
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Deleted Overtime Trip',
                "Employee: $empName | Date: $trip_date");
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();
