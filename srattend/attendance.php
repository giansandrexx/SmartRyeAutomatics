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
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, color, phone, position, employment_type, daily_rate, hire_date FROM employees WHERE is_active=1 AND department=? ORDER BY name");
            $stmt->bind_param("s", $dept);
        } else {
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, color, phone, position, employment_type, daily_rate, hire_date FROM employees WHERE is_active=1 ORDER BY department, name");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            if (empty($row['color'])) $row['color'] = '135deg,#1245a8,#42a5f5';
            $employees[] = $row;
        }
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
            ORDER BY att_date ASC
        ");
        $stmt->bind_param("ss", $week_start, $week_end);
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

    if ($action === 'overtime') {
        $week_start = $_GET['week_start'] ?? date('Y-m-d');
        $stmt = $conn->prepare("SELECT emp_id, ot_morning, ot_afternoon FROM overtime WHERE week_start = ?");
        $stmt->bind_param("s", $week_start);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[(int)$row['emp_id']] = ['m' => $row['ot_morning'], 'a' => $row['ot_afternoon']];
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
        $att_date = trim($body['att_date']  ?? '');
        $time_in  = !empty($body['time_in'])  ? trim($body['time_in'])  : null;
        $time_out = !empty($body['time_out']) ? trim($body['time_out']) : null;

        if (!$emp_id || !$att_date) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        $empRow  = $conn->query("SELECT name FROM employees WHERE id=$emp_id")->fetch_assoc();
        $empName = $empRow['name'] ?? "Employee #$emp_id"; // ← ADDED

        if ($time_in === null && $time_out === null) {
            $del = $conn->prepare("DELETE FROM attendance WHERE emp_id = ? AND att_date = ?");
            $del->bind_param("is", $emp_id, $att_date);
            if ($del->execute()) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'attendance', 'Deleted Attendance',
                    "Employee: $empName | Date: $att_date");
            }
            $del->close();
            echo json_encode(['success' => true, 'action' => 'deleted']);
            exit;
        }

        $chk = $conn->prepare("SELECT id FROM attendance WHERE emp_id = ? AND att_date = ?");
        $chk->bind_param("is", $emp_id, $att_date);
        $chk->execute();
        $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();

        if ($exists) {
            if ($time_in !== null && $time_out !== null) {
                $stmt = $conn->prepare("UPDATE attendance SET time_in = ?, time_out = ? WHERE emp_id = ? AND att_date = ?");
                $stmt->bind_param("ssis", $time_in, $time_out, $emp_id, $att_date);
            } elseif ($time_in !== null) {
                $stmt = $conn->prepare("UPDATE attendance SET time_in = ? WHERE emp_id = ? AND att_date = ?");
                $stmt->bind_param("sis", $time_in, $emp_id, $att_date);
            } else {
                $stmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE emp_id = ? AND att_date = ?");
                $stmt->bind_param("sis", $time_out, $emp_id, $att_date);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO attendance (emp_id, att_date, time_in, time_out) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $emp_id, $att_date, $time_in, $time_out);
        }

        $stmt->execute();
        if ($stmt->error) {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
            $stmt->close();
            exit;
        }
        $logAction = $exists ? 'Updated Attendance' : 'Added Attendance';
        logActivity($conn, $uid, $uname, $uful, 'attendance', $logAction,
            "Employee: $empName | Date: $att_date | In: " . ($time_in ?? '-') . " | Out: " . ($time_out ?? '-'));
        $stmt->close();
        echo json_encode(['success' => true, 'emp_id' => $emp_id, 'att_date' => $att_date, 'action' => $exists ? 'updated' : 'inserted']);
        exit;
    }

    if ($action === 'save_overtime') {
        $emp_id     = (int)($body['emp_id']       ?? 0);
        $week_start = trim($body['week_start']    ?? '');
        $ot_m       = (float)($body['ot_morning']   ?? 0);
        $ot_a       = (float)($body['ot_afternoon'] ?? 0);
        if (!$emp_id || !$week_start) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }

        $empRow  = $conn->query("SELECT name FROM employees WHERE id=$emp_id")->fetch_assoc();
        $empName = $empRow['name'] ?? "Employee #$emp_id";

        $stmt = $conn->prepare("INSERT INTO overtime (emp_id, week_start, ot_morning, ot_afternoon) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE ot_morning = VALUES(ot_morning), ot_afternoon = VALUES(ot_afternoon)");
        $stmt->bind_param("isdd", $emp_id, $week_start, $ot_m, $ot_a);
        if ($stmt->execute()) {
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Saved Overtime',
                "Employee: $empName | Week: $week_start | Morning OT: {$ot_m}h | Afternoon OT: {$ot_a}h");
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_employee') {
        $employee_id     = trim($body['employee_id']    ?? '');
        $name            = trim($body['name']           ?? '');
        $dept            = trim($body['dept']           ?? '');
        $color           = $body['color']               ?? '135deg,#1245a8,#42a5f5';
        $phone           = trim($body['phone']          ?? '');
        $position        = trim($body['position']       ?? '');
        $employment_type = $body['employment_type']     ?? 'Full Time';
        $daily_rate      = (float)($body['daily_rate']  ?? 0);
        $hire_date       = !empty($body['hire_date'])   ? $body['hire_date'] : null;

        if (!$employee_id) { echo json_encode(['success' => false, 'message' => 'Employee ID required']); exit; }
        if (!$name)        { echo json_encode(['success' => false, 'message' => 'Name required']); exit; }
        if (!$dept)        { echo json_encode(['success' => false, 'message' => 'Department required']); exit; }
        if (!$position)    { echo json_encode(['success' => false, 'message' => 'Position required']); exit; }

        $chk = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
        $chk->bind_param("s", $employee_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Employee ID already exists']); exit; }
        $chk->close();

        $stmt = $conn->prepare("INSERT INTO employees (employee_id, name, department, color, phone, position, employment_type, daily_rate, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssds", $employee_id, $name, $dept, $color, $phone, $position, $employment_type, $daily_rate, $hire_date);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Added Employee',
                "Added: $name (ID: $employee_id) | Dept: $dept | Position: $position | Type: $employment_type");
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $new_id]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Insert failed']);
        }
        exit;
    }

    if ($action === 'edit_employee') {
        $id          = (int)($body['id']         ?? 0);
        $employee_id = trim($body['employee_id'] ?? '');
        $name        = trim($body['name']        ?? '');

        if (!$id || !$employee_id || !$name) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
        }

        $chk = $conn->prepare("SELECT id FROM employees WHERE employee_id = ? AND id != ?");
        $chk->bind_param("si", $employee_id, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Employee ID already in use']); exit; }
        $chk->close();

        $old = $conn->query("SELECT name, employee_id FROM employees WHERE id=$id")->fetch_assoc();

        $stmt = $conn->prepare("UPDATE employees SET employee_id=?, name=? WHERE id=?");
        $stmt->bind_param("ssi", $employee_id, $name, $id);
        if ($stmt->execute()) {
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Edited Employee',
                "Edited: \"{$old['name']}\" → \"$name\" | ID: {$old['employee_id']} → $employee_id");
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_employee') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id']); exit; }

        $old = $conn->query("SELECT name, employee_id FROM employees WHERE id=$id")->fetch_assoc();

        $stmt = $conn->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logActivity($conn, $uid, $uname, $uful, 'attendance', 'Deactivated Employee',
                "Deactivated: \"{$old['name']}\" (ID: {$old['employee_id']})");
        }
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();
