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

$employee_id = (int)($_GET['employee_id'] ?? 0);
if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
    exit();
}

$emp_r = $conn->prepare("SELECT id, name, department, daily_rate FROM employees WHERE id = ? AND is_active = 1");
$emp_r->bind_param("i", $employee_id);
$emp_r->execute();
$emp = $emp_r->get_result()->fetch_assoc();
$emp_r->close();

if (!$emp) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}

$dept  = strtolower(trim($emp['department'] ?? ''));
$today = new DateTime('today');

if ($dept === 'field') {
    $dow      = (int)$today->format('N');
    $monday   = (clone $today)->modify('-' . ($dow - 1) . ' days');
    $saturday = (clone $monday)->modify('+5 days');
    $date_from = $monday->format('Y-m-d');
    $date_to   = $saturday->format('Y-m-d');
} else {
    $day = (int)$today->format('j');
    $yr  = $today->format('Y');
    $mo  = $today->format('m');
    if ($day <= 15) {
        $date_from = "$yr-$mo-01";
        $date_to   = "$yr-$mo-15";
    } else {
        $last_day  = (int)$today->format('t');
        $date_from = "$yr-$mo-16";
        $date_to   = "$yr-$mo-$last_day";
    }
}

$att_r = $conn->prepare("
    SELECT att_date,
           TIME_FORMAT(time_in,  '%H:%i') AS time_in,
           TIME_FORMAT(time_out, '%H:%i') AS time_out
    FROM attendance
    WHERE emp_id = ? AND att_date BETWEEN ? AND ?
    ORDER BY att_date ASC
");
$att_r->bind_param("iss", $employee_id, $date_from, $date_to);
$att_r->execute();
$att_rows = $att_r->get_result()->fetch_all(MYSQLI_ASSOC);
$att_r->close();

$att_map = [];
foreach ($att_rows as $row) {
    $att_map[$row['att_date']] = ['in' => $row['time_in'], 'out' => $row['time_out']];
}

$ot_r = $conn->prepare("
    SELECT SUM(ot_morning + ot_afternoon) AS total_ot
    FROM overtime
    WHERE emp_id = ? AND week_start BETWEEN ? AND ?
");
$ot_r->bind_param("iss", $employee_id, $date_from, $date_to);
$ot_r->execute();
$ot_row   = $ot_r->get_result()->fetch_assoc();
$ot_r->close();
$ot_hours = round((float)($ot_row['total_ot'] ?? 0), 2);

$cutoff_in  = ($dept === 'field') ? (7 * 60) : (8 * 60);
$cutoff_out = 17 * 60;

$days_worked       = 0;
$absent_days       = 0;
$late_minutes      = 0;
$undertime_minutes = 0;

$cursor = new DateTime($date_from);
$end    = new DateTime($date_to);

while ($cursor <= $end) {
    $dow_num    = (int)$cursor->format('N');
    $date_key   = $cursor->format('Y-m-d');
    $is_past    = $cursor < $today;
    $is_workday = ($dept === 'field') ? ($dow_num <= 6) : ($dow_num <= 5);

    if ($is_workday) {
        $rec = $att_map[$date_key] ?? null;
        if ($rec && ($rec['in'] || $rec['out'])) {
            $days_worked++;

            if (!empty($rec['in'])) {
                list($h, $m)  = array_map('intval', explode(':', $rec['in']));
                $in_min       = $h * 60 + $m;
                $late         = max(0, $in_min - $cutoff_in);
                $late_minutes += $late;
            }

            if (!empty($rec['out'])) {
                list($h, $m)        = array_map('intval', explode(':', $rec['out']));
                $out_min            = $h * 60 + $m;
                $under              = max(0, $cutoff_out - $out_min);
                $undertime_minutes += $under;
            }
        } elseif ($is_past) {
            $absent_days++;
        }
    }

    $cursor->modify('+1 day');
}

echo json_encode([
    'success'           => true,
    'date_from'         => $date_from,
    'date_to'           => $date_to,
    'days_worked'       => $days_worked,
    'absent_days'       => $absent_days,
    'late_minutes'      => $late_minutes,
    'undertime_minutes' => $undertime_minutes,
    'ot_hours'          => $ot_hours,
    'department'        => $emp['department'],
    'period_label'      => ($dept === 'field') ? 'Weekly (Mon–Sat)' : 'Semi-Monthly',
]);

$conn->close();
