<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

require_once '../config.php';
require_once '../log_helper.php';

header('Content-Type: application/json');

if ($conn->connect_error) { echo json_encode(['success' => false, 'message' => 'DB error']); exit(); }

$action = $_GET['action'] ?? '';

$uid   = $_SESSION['user_id'];
$uname = $_SESSION['username']  ?? 'unknown';
$uful  = $_SESSION['full_name'] ?? 'Unknown User';

function ok($data = []) { echo json_encode(array_merge(['success' => true], $data)); exit(); }
function fail($msg = 'Error') { echo json_encode(['success' => false, 'message' => $msg]); exit(); }
function body() { return json_decode(file_get_contents('php://input'), true) ?? []; }
function esc($conn, $v) { return $v === null || $v === '' ? null : $conn->real_escape_string($v); }
function val($conn, $v) { return $v === null || $v === '' ? 'NULL' : "'" . $conn->real_escape_string($v) . "'"; }

switch ($action) {

    case 'list_employees':
        $res = $conn->query("SELECT * FROM employees ORDER BY name ASC");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        break;

    case 'add_employee':
        $b = body();
        $sql = "INSERT INTO employees (
            employee_id, name, hire_date, position, department, employment_type,
            phone, address, date_of_birth, status, sex, driver_license,
            sss, philhealth, hdmf, tin, nbi_validity,
            contact_person_name, contact_person_number, contact_person_address,
            basic_salary, daily_rate, is_active
        ) VALUES (
            ".val($conn,$b['employee_id']).",".val($conn,$b['name']).",".val($conn,$b['hire_date']).",
            ".val($conn,$b['position']).",".val($conn,$b['department']).",".val($conn,$b['employment_type']).",
            ".val($conn,$b['phone']).",".val($conn,$b['address']).",".val($conn,$b['date_of_birth']).",
            ".val($conn,$b['status']).",".val($conn,$b['sex']).",".val($conn,$b['driver_license']).",
            ".val($conn,$b['sss']).",".val($conn,$b['philhealth']).",".val($conn,$b['hdmf']).",
            ".val($conn,$b['tin']).",".val($conn,$b['nbi_validity']).",
            ".val($conn,$b['contact_person_name']).",".val($conn,$b['contact_person_number']).",".val($conn,$b['contact_person_address']).",
            ".floatval($b['basic_salary'] ?? 0).",".floatval($b['daily_rate'] ?? 0).", 1
        )";
        if ($conn->query($sql)) {
            $newId = $conn->insert_id;
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Added Employee',
                "Added new employee: {$b['name']} (ID: {$b['employee_id']}, Position: {$b['position']})");
            ok(['id' => $newId]);
        } else fail($conn->error);
        break;

    case 'update_employee':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');

        $old = $conn->query("SELECT name, employee_id FROM employees WHERE id=$id")->fetch_assoc();

        $isActive = ($b['status'] ?? 'Active') === 'Active' ? 1 : 0;
        $sql = "UPDATE employees SET
            employee_id=".val($conn,$b['employee_id']).",
            name=".val($conn,$b['name']).",
            hire_date=".val($conn,$b['hire_date']).",
            position=".val($conn,$b['position']).",
            department=".val($conn,$b['department']).",
            employment_type=".val($conn,$b['employment_type']).",
            phone=".val($conn,$b['phone']).",
            address=".val($conn,$b['address']).",
            date_of_birth=".val($conn,$b['date_of_birth']).",
            status=".val($conn,$b['status']).",
            sex=".val($conn,$b['sex']).",
            driver_license=".val($conn,$b['driver_license']).",
            sss=".val($conn,$b['sss']).",
            philhealth=".val($conn,$b['philhealth']).",
            hdmf=".val($conn,$b['hdmf']).",
            tin=".val($conn,$b['tin']).",
            nbi_validity=".val($conn,$b['nbi_validity']).",
            contact_person_name=".val($conn,$b['contact_person_name']).",
            contact_person_number=".val($conn,$b['contact_person_number']).",
            contact_person_address=".val($conn,$b['contact_person_address']).",
            basic_salary=".floatval($b['basic_salary'] ?? 0).",
            daily_rate=".floatval($b['daily_rate'] ?? 0).",
            is_active=$isActive
            WHERE id=$id";
        if ($conn->query($sql)) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Updated Employee',
                "Updated employee: {$b['name']} (ID: {$b['employee_id']}, Status: {$b['status']}, Position: {$b['position']})");
            ok();
        } else fail($conn->error);
        break;

    case 'delete_employee':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');

        // Get name before deleting
        $emp = $conn->query("SELECT name, employee_id FROM employees WHERE id=$id")->fetch_assoc();

        if ($conn->query("DELETE FROM employees WHERE id=$id")) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Deleted Employee',
                "Deleted employee: {$emp['name']} (ID: {$emp['employee_id']})");
            ok();
        } else fail($conn->error);
        break;

    case 'list_unemployed':
        $res = $conn->query("SELECT * FROM unemployed_employees ORDER BY name ASC");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        break;

    case 'add_unemployed':
        $b = body();
        $sql = "INSERT INTO unemployed_employees (
            employee_id, name, position, phone, address,
            date_of_birth, start_date, end_date, status
        ) VALUES (
            ".val($conn,$b['employee_id']).",".val($conn,$b['name']).",".val($conn,$b['position']).",
            ".val($conn,$b['phone']).",".val($conn,$b['address']).",".val($conn,$b['date_of_birth']).",
            ".val($conn,$b['start_date']).",".val($conn,$b['end_date']).",".val($conn,$b['status'])."
        )";
        if ($conn->query($sql)) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Added Unemployed Record',
                "Added unemployed record: {$b['name']} — Status: {$b['status']}");
            ok(['id' => $conn->insert_id]);
        } else fail($conn->error);
        break;

    case 'update_unemployed':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');
        $sql = "UPDATE unemployed_employees SET
            employee_id=".val($conn,$b['employee_id']).",
            name=".val($conn,$b['name']).",
            position=".val($conn,$b['position']).",
            phone=".val($conn,$b['phone']).",
            address=".val($conn,$b['address']).",
            date_of_birth=".val($conn,$b['date_of_birth']).",
            start_date=".val($conn,$b['start_date']).",
            end_date=".val($conn,$b['end_date']).",
            status=".val($conn,$b['status'])."
            WHERE id=$id";
        if ($conn->query($sql)) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Updated Unemployed Record',
                "Updated unemployed record: {$b['name']} — Status: {$b['status']}");
            ok();
        } else fail($conn->error);
        break;

    case 'delete_unemployed':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');

        $rec = $conn->query("SELECT name FROM unemployed_employees WHERE id=$id")->fetch_assoc();

        if ($conn->query("DELETE FROM unemployed_employees WHERE id=$id")) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Deleted Unemployed Record',
                "Deleted unemployed record: {$rec['name']}");
            ok();
        } else fail($conn->error);
        break;

    case 'list_contracts':
        $res = $conn->query("SELECT * FROM employee_contracts ORDER BY end_date ASC");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        break;

    case 'add_contract':
        $b = body();
        $sql = "INSERT INTO employee_contracts (name, position, start_date, end_date)
                VALUES (".val($conn,$b['name']).",".val($conn,$b['position']).",".val($conn,$b['start_date']).",".val($conn,$b['end_date']).")";
        if ($conn->query($sql)) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Added Contract',
                "Added contract for: {$b['name']} ({$b['position']}) — {$b['start_date']} to {$b['end_date']}");
            ok(['id' => $conn->insert_id]);
        } else fail($conn->error);
        break;

    case 'update_contract':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');
        $sql = "UPDATE employee_contracts SET
            name=".val($conn,$b['name']).",
            position=".val($conn,$b['position']).",
            start_date=".val($conn,$b['start_date']).",
            end_date=".val($conn,$b['end_date'])."
            WHERE id=$id";
        if ($conn->query($sql)) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Updated Contract',
                "Updated contract for: {$b['name']} ({$b['position']}) — ends {$b['end_date']}");
            ok();
        } else fail($conn->error);
        break;

    case 'delete_contract':
        $b = body();
        $id = intval($b['id']);
        if (!$id) fail('Invalid ID');

        $con = $conn->query("SELECT name, position FROM employee_contracts WHERE id=$id")->fetch_assoc();

        if ($conn->query("DELETE FROM employee_contracts WHERE id=$id")) {
            logActivity($conn, $uid, $uname, $uful,
                'employee_info', 'Deleted Contract',
                "Deleted contract for: {$con['name']} ({$con['position']})");
            ok();
        } else fail($conn->error);
        break;

    default:
        fail('Unknown action');
}

$conn->close();
