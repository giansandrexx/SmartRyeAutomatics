<?php
session_start();
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../log_helper.php';

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$canWrite = in_array($_SESSION['role'] ?? '', ['admin', 'moderator']);
$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown';
$uname    = $_SESSION['username'] ?? 'unknown';

function respond($data) {
    echo json_encode($data);
    exit;
}

function addLog($conn, $eventId, $userId, $userName, $type, $content) {
    $stmt = $conn->prepare("INSERT INTO event_logs (event_id, user_id, user_name, type, content) VALUES (?,?,?,?,?)");
    if (!$stmt) return;
    $stmt->bind_param('iisss', $eventId, $userId, $userName, $type, $content);
    $stmt->execute();
    $stmt->close();
}

function getEvent($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function dbFetch($conn, $sql, $types = '', $params = []) {
    if ($types && $params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

switch ($method) {

    case 'GET':

        if ($action === 'get_by_month') {
            $year   = (int)($_GET['year']  ?? date('Y'));
            $month  = (int)($_GET['month'] ?? date('n'));
            $prefix = sprintf('%04d-%02d%%', $year, $month);
            $rows   = dbFetch($conn,
                "SELECT * FROM events WHERE date LIKE ? ORDER BY date ASC, start_time ASC",
                's', [$prefix]);
            respond(['success' => true, 'data' => $rows]);
        }

        if ($action === 'get_logs') {
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) respond(['success' => false, 'message' => 'event_id required']);
            $conn->query("CREATE TABLE IF NOT EXISTS event_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT NOT NULL,
                user_id INT NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event_id (event_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $rows = dbFetch($conn,
                "SELECT * FROM event_logs WHERE event_id = ? ORDER BY created_at ASC",
                'i', [$eventId]);
            respond(['success' => true, 'data' => $rows]);
        }

        if ($action === 'search') {
            $q        = trim($_GET['q']        ?? '');
            $category = trim($_GET['category'] ?? '');
            $status   = trim($_GET['status']   ?? '');

            $sql    = "SELECT * FROM events WHERE 1=1";
            $types  = '';
            $params = [];

            if ($q !== '') {
                $like    = '%' . $q . '%';
                $sql    .= " AND (title LIKE ? OR assignee LIKE ? OR notes LIKE ? OR category LIKE ? OR status LIKE ?)";
                $types  .= 'sssss';
                $params  = array_merge($params, [$like, $like, $like, $like, $like]);
            }
            if ($category !== '') { $sql .= " AND category = ?"; $types .= 's'; $params[] = $category; }
            if ($status   !== '') { $sql .= " AND status = ?";   $types .= 's'; $params[] = $status; }

            $sql .= " ORDER BY date ASC, start_time ASC LIMIT 200";

            $rows = ($types !== '')
                ? dbFetch($conn, $sql, $types, $params)
                : dbFetch($conn, $sql);

            respond(['success' => true, 'data' => $rows]);
        }

        if ($action === 'get_by_date') {
            $date = $_GET['date'] ?? '';
            if (!$date) respond(['success' => false, 'message' => 'Date required']);
            $rows = dbFetch($conn,
                "SELECT * FROM events WHERE date = ? ORDER BY start_time ASC",
                's', [$date]);
            respond(['success' => true, 'data' => $rows]);
        }

        respond(['success' => false, 'message' => 'Unknown action']);
        break;

    case 'POST':
        if (!$canWrite) { http_response_code(403); respond(['success' => false, 'message' => 'Forbidden']); }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'add_comment') {
            $eventId = (int)($body['event_id'] ?? 0);
            $content = trim($body['content']   ?? '');
            if (!$eventId || $content === '') respond(['success' => false, 'message' => 'event_id and content required']);
            $conn->query("CREATE TABLE IF NOT EXISTS event_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT NOT NULL,
                user_id INT NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event_id (event_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            addLog($conn, $eventId, $userId, $userName, 'comment', $content);

            $ev = getEvent($conn, $eventId);
            logActivity($conn, $userId, $uname, $userName,
                'scheduling', 'Added Comment',
                "Commented on event: \"{$ev['title']}\" ({$ev['date']}) — \"{$content}\"");

            $rows = dbFetch($conn,
                "SELECT * FROM event_logs WHERE event_id = ? ORDER BY created_at ASC",
                'i', [$eventId]);
            respond(['success' => true, 'data' => $rows]);
        }

        $evTitle    = trim($body['title']    ?? '');
        $evDate     = trim($body['date']     ?? '');
        $evStart    = trim($body['start']    ?? '') ?: null;
        $evEnd      = trim($body['end']      ?? '') ?: null;
        $evCategory = trim($body['category'] ?? 'meeting');
        $evStatus   = trim($body['status']   ?? 'pending');
        $evAssignee = trim($body['assignee'] ?? '') ?: null;
        $evNotes    = trim($body['notes']    ?? '') ?: null;

        if (!$evTitle || !$evDate) respond(['success' => false, 'message' => 'Title and date required']);

        $validCats  = ['meeting','maintenance','training','inspection','other'];
        $validStats = ['pending','confirmed','completed','cancelled'];
        if (!in_array($evCategory, $validCats))  $evCategory = 'other';
        if (!in_array($evStatus,   $validStats)) $evStatus   = 'pending';

        $stmt = $conn->prepare(
            "INSERT INTO events (title, date, start_time, end_time, category, status, assignee, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssss', $evTitle, $evDate, $evStart, $evEnd, $evCategory, $evStatus, $evAssignee, $evNotes);
        $stmt->execute();
        $newId = (int)$conn->insert_id;
        $stmt->close();

        addLog($conn, $newId, $userId, $userName, 'created', "Event created by $userName");

        $timeStr = $evStart ? " at " . $evStart : "";
        $assignStr = $evAssignee ? ", Assigned to: $evAssignee" : "";
        logActivity($conn, $userId, $uname, $userName,
            'scheduling', 'Created Event',
            "Created event: \"$evTitle\" on $evDate$timeStr — Category: $evCategory, Status: $evStatus$assignStr");

        respond(['success' => true, 'data' => getEvent($conn, $newId)]);
        break;

    case 'PUT':
        if (!$canWrite) { http_response_code(403); respond(['success' => false, 'message' => 'Forbidden']); }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'update_status') {
            $id        = (int)($body['id']    ?? 0);
            $newStatus = trim($body['status'] ?? '');
            $validStats = ['pending','confirmed','completed','cancelled'];
            if (!$id || !in_array($newStatus, $validStats)) respond(['success' => false, 'message' => 'Invalid id or status']);

            $old  = getEvent($conn, $id);
            $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();

            addLog($conn, $id, $userId, $userName, 'status_change',
                "Status changed from {$old['status']} to $newStatus by $userName");

            logActivity($conn, $userId, $uname, $userName,
                'scheduling', 'Changed Event Status',
                "Event: \"{$old['title']}\" ({$old['date']}) — Status: {$old['status']} → $newStatus");

            respond(['success' => true, 'data' => getEvent($conn, $id)]);
        }

        $id         = (int)($body['id']       ?? 0);
        $evTitle    = trim($body['title']     ?? '');
        $evDate     = trim($body['date']      ?? '');
        $evStart    = trim($body['start']     ?? '') ?: null;
        $evEnd      = trim($body['end']       ?? '') ?: null;
        $evCategory = trim($body['category']  ?? 'meeting');
        $evStatus   = trim($body['status']    ?? 'pending');
        $evAssignee = trim($body['assignee']  ?? '') ?: null;
        $evNotes    = trim($body['notes']     ?? '') ?: null;

        if (!$id || !$evTitle || !$evDate) respond(['success' => false, 'message' => 'id, title and date required']);

        $validCats  = ['meeting','maintenance','training','inspection','other'];
        $validStats = ['pending','confirmed','completed','cancelled'];
        if (!in_array($evCategory, $validCats))  $evCategory = 'other';
        if (!in_array($evStatus,   $validStats)) $evStatus   = 'pending';

        $stmt = $conn->prepare(
            "UPDATE events SET title=?, date=?, start_time=?, end_time=?, category=?, status=?, assignee=?, notes=? WHERE id=?");
        $stmt->bind_param('ssssssssi', $evTitle, $evDate, $evStart, $evEnd, $evCategory, $evStatus, $evAssignee, $evNotes, $id);
        $stmt->execute();
        $stmt->close();

        addLog($conn, $id, $userId, $userName, 'edit', "Event updated by $userName");

        // System log
        $assignStr = $evAssignee ? ", Assigned to: $evAssignee" : "";
        logActivity($conn, $userId, $uname, $userName,
            'scheduling', 'Updated Event',
            "Updated event: \"$evTitle\" on $evDate — Category: $evCategory, Status: $evStatus$assignStr");

        respond(['success' => true, 'data' => getEvent($conn, $id)]);
        break;

    case 'DELETE':
        if (!$canWrite) { http_response_code(403); respond(['success' => false, 'message' => 'Forbidden']); }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respond(['success' => false, 'message' => 'ID required']);

        $event = getEvent($conn, $id);
        if (!$event) respond(['success' => false, 'message' => 'Event not found']);

        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        // System log
        logActivity($conn, $userId, $uname, $userName,
            'scheduling', 'Deleted Event',
            "Deleted event: \"{$event['title']}\" on {$event['date']} — Category: {$event['category']}");

        respond(['success' => true, 'affected' => $affected]);
        break;

    default:
        http_response_code(405);
        respond(['success' => false, 'message' => 'Method not allowed']);
}


