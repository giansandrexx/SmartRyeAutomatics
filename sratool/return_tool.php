<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

require_once "../config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = "";
$error_message = "";
$search_results = null;
$search_performed = false;

if (isset($_POST['return_submit'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $condition = $_POST['condition'];
    $return_notes = trim($_POST['return_notes']);

    $return_date = date('Y-m-d');
    $return_time = date('H:i:s');

    $stmt = $conn->prepare("UPDATE borrowing_records SET actual_return_date = ?, actual_return_time = ?, condition_on_return = ?, notes = CONCAT(COALESCE(notes, ''), '\n[Return Note] ', ?), status = 'returned' WHERE transaction_id = ? AND status IN ('borrowed', 'overdue')");
    $stmt->bind_param("ssssi", $return_date, $return_time, $condition, $return_notes, $transaction_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $success_message = "Tool returned successfully!";
        } else {
            $error_message = "Error: Item already returned or transaction not found.";
        }
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

if (isset($_POST['search_submit']) || isset($_POST['return_submit'])) {
    $search_query = isset($_POST['search_query']) ? trim($_POST['search_query']) : '';
    
    if (!empty($search_query)) {
        $search_performed = true;
        $search_term = "%{$search_query}%";
        
        $stmt = $conn->prepare("
            SELECT * FROM borrowing_records 
            WHERE (borrower_name LIKE ? OR tool_name LIKE ?) 
            AND status IN ('borrowed', 'overdue')
            ORDER BY date_borrowed DESC, time_borrowed DESC
        ");
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $search_results = $stmt->get_result();
        $stmt->close();
    }
}

$summary = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'overdue' OR (expected_return_date IS NOT NULL AND expected_return_date < CURDATE()) THEN 1 ELSE 0 END) AS overdue_count,
        SUM(CASE WHEN expected_return_date = CURDATE() AND status = 'borrowed' THEN 1 ELSE 0 END) AS due_today_count,
        SUM(CASE WHEN status = 'borrowed' AND (expected_return_date IS NULL OR expected_return_date > CURDATE()) THEN 1 ELSE 0 END) AS on_track_count
    FROM borrowing_records
    WHERE status IN ('borrowed', 'overdue')
")->fetch_assoc();

if (!$search_performed) {
    $all_borrowed = $conn->query("
        SELECT * FROM borrowing_records 
        WHERE status IN ('borrowed', 'overdue')
        ORDER BY 
            CASE WHEN status = 'overdue' THEN 1
                 WHEN expected_return_date = CURDATE() THEN 2
                 ELSE 3 END,
            date_borrowed ASC, 
            time_borrowed ASC
        LIMIT 50
    ");
}

$conn->close();

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= strtoupper(mb_substr($p, 0, 1));
    }
    return $initials ?: '?';
}

function getDueBadge($row) {
    $today = date('Y-m-d');
    $isOverdue = ($row['status'] == 'overdue') || 
                 ($row['expected_return_date'] && $row['expected_return_date'] < $today);
    $isDueToday = ($row['expected_return_date'] == $today && !$isOverdue);
    $isDueSoon  = (!$isOverdue && !$isDueToday && $row['expected_return_date'] && 
                   (strtotime($row['expected_return_date']) - strtotime($today)) / 86400 <= 3);

    if ($isOverdue) {
        $daysOverdue = floor((strtotime($today) - strtotime($row['expected_return_date'])) / 86400);
        return [
            'class'    => 'overdue',
            'icon'     => 'fa-triangle-exclamation',
            'label'    => $daysOverdue . ' Day' . ($daysOverdue != 1 ? 's' : '') . ' Overdue',
            'sub'      => $row['expected_return_date'] ? 'Was due ' . date('M d', strtotime($row['expected_return_date'])) : '',
            'cardClass' => 'overdue',
        ];
    } elseif ($isDueToday) {
        return [
            'class'    => 'due-today',
            'icon'     => 'fa-clock',
            'label'    => 'Due Today',
            'sub'      => date('M d, Y'),
            'cardClass' => 'due-soon',
        ];
    } elseif ($isDueSoon) {
        $daysLeft = ceil((strtotime($row['expected_return_date']) - strtotime($today)) / 86400);
        return [
            'class'    => 'due-soon',
            'icon'     => 'fa-calendar',
            'label'    => 'Due in ' . $daysLeft . ' day' . ($daysLeft != 1 ? 's' : ''),
            'sub'      => $row['expected_return_date'] ? 'Due ' . date('M d', strtotime($row['expected_return_date'])) : '',
            'cardClass' => '',
        ];
    } else {
        return [
            'class'    => 'on-track',
            'icon'     => 'fa-check',
            'label'    => 'On Track',
            'sub'      => $row['expected_return_date'] ? 'Due ' . date('M d', strtotime($row['expected_return_date'])) : 'No due date',
            'cardClass' => '',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Tool - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/return.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<?php include 'navigation.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h2><i class="fa fa-rotate-left"></i> Return Tool</h2>
            <p>Process tool returns — click any item to expand the return form</p>
        </div>
        <span class="header-time">
            <span class="pulse-dot">●</span>
            <?php echo date('M d, Y · h:i A'); ?>
        </span>
    </div>

    <?php if ($success_message): ?>
        <div class="rt-alert rt-alert-success">
            <i class="fa fa-circle-check"></i>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="rt-alert rt-alert-danger">
            <i class="fa fa-circle-exclamation"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="search-section">
        <form method="POST" action="" class="search-form">
            <input
                type="text"
                name="search_query"
                class="search-input"
                placeholder="Search by borrower name or tool name…"
                value="<?php echo isset($_POST['search_query']) ? htmlspecialchars($_POST['search_query']) : ''; ?>"
            >
            <button type="submit" name="search_submit" class="btn-search">
                <i class="fa fa-magnifying-glass"></i> Search
            </button>
            <?php if ($search_performed): ?>
                <a href="return_tool.php" class="btn-clear">
                    <i class="fa fa-xmark"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$search_performed): ?>
    <div class="borrow-summary-bar">
        <span class="summary-chip summary-chip-total">
            <i class="fa fa-box"></i> <?php echo $summary['total']; ?> Total
        </span>
        <div class="summary-divider"></div>
        <span class="summary-chip summary-chip-overdue">
            <i class="fa fa-circle-exclamation"></i> <?php echo $summary['overdue_count']; ?> Overdue
        </span>
        <div class="summary-divider"></div>
        <span class="summary-chip summary-chip-duetoday">
            <i class="fa fa-clock"></i> <?php echo $summary['due_today_count']; ?> Due Today
        </span>
        <div class="summary-divider"></div>
        <span class="summary-chip summary-chip-ok">
            <i class="fa fa-check"></i> <?php echo $summary['on_track_count']; ?> On Track
        </span>
    </div>
    <?php endif; ?>

    <div class="items-section">
        <div class="section-header">
            <h3>
                <?php if ($search_performed): ?>
                    <i class="fa fa-magnifying-glass"></i> Search Results
                <?php else: ?>
                    <i class="fa fa-rotate-left"></i> Currently Borrowed Items
                <?php endif; ?>
            </h3>
            <?php
            $items_to_display = $search_performed ? $search_results : $all_borrowed;
            $count = $items_to_display ? $items_to_display->num_rows : 0;
            ?>
            <span class="section-count"><?php echo $count; ?> item<?php echo $count != 1 ? 's' : ''; ?></span>
        </div>

        <div class="items-list">
        <?php if ($items_to_display && $items_to_display->num_rows > 0):

            $items_to_display->data_seek(0);
            while ($row = $items_to_display->fetch_assoc()):
                $badge      = getDueBadge($row);
                $initials   = getInitials($row['borrower_name']);
                $borrowedTs = strtotime($row['date_borrowed'] . ' ' . $row['time_borrowed']);
                $searchVal  = isset($_POST['search_query']) ? htmlspecialchars($_POST['search_query']) : '';

                $autoOpen   = ($badge['cardClass'] === 'overdue') ? 'open' : '';
        ?>
            <div class="item-card <?php echo $badge['cardClass']; ?>">

                <div class="item-card-inner" role="button" tabindex="0" aria-expanded="<?php echo $autoOpen ? 'true' : 'false'; ?>">
                    <div class="item-avatar"><?php echo $initials; ?></div>

                    <div class="item-body">
                        <div class="item-top-row">
                            <span class="item-borrower-name"><?php echo htmlspecialchars($row['borrower_name']); ?></span>
                            <span class="item-tool-badge">
                                <i class="fa fa-wrench"></i>
                                <?php echo htmlspecialchars($row['tool_name']); ?>
                                <?php if ($row['quantity'] > 1): ?>
                                    <span class="item-qty">×<?php echo $row['quantity']; ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="item-meta-row">
                            <span class="meta-chip">
                                <i class="fa fa-hashtag"></i>
                                ID <strong>#<?php echo $row['transaction_id']; ?></strong>
                            </span>
                            <span class="meta-chip">
                                <i class="fa fa-calendar-plus"></i>
                                Borrowed <strong><?php echo date('M d, Y', $borrowedTs); ?></strong>
                            </span>
                            <span class="meta-chip">
                                <i class="fa fa-stopwatch"></i>
                                <span class="live" data-borrowed="<?php echo $borrowedTs; ?>">…</span>
                            </span>
                            <?php if ($row['notes']): ?>
                            <span class="meta-chip meta-chip-note">
                                <i class="fa fa-note-sticky"></i>
                                <?php echo htmlspecialchars(mb_strimwidth($row['notes'], 0, 40, '…')); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="item-status-col">
                        <span class="due-badge <?php echo $badge['class']; ?>">
                            <i class="fa <?php echo $badge['icon']; ?>"></i>
                            <?php echo $badge['label']; ?>
                        </span>
                        <?php if ($badge['sub']): ?>
                            <span class="due-date-text"><?php echo $badge['sub']; ?></span>
                        <?php endif; ?>
                        <span class="expand-chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>

                <div class="item-return-wrap <?php echo $autoOpen; ?>">
                    <div class="return-form-title">
                        <i class="fa fa-right-to-bracket"></i> Process Return
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="transaction_id" value="<?php echo $row['transaction_id']; ?>">
                        <input type="hidden" name="search_query" value="<?php echo $searchVal; ?>">

                        <div class="form-row">
                            <div>
                                <label class="form-label">Condition on Return <span style="color:#ef4444">*</span></label>
                                <select name="condition" class="form-select" required>
                                    <option value="">Select condition…</option>
                                    <option value="Good">Good — No damage</option>
                                    <option value="Fair">Fair — Minor wear</option>
                                    <option value="Needs Repair">Needs Repair</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Return Notes <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                                <input
                                    type="text"
                                    name="return_notes"
                                    class="form-control"
                                    placeholder="Any notes about the return…"
                                >
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="return_submit" class="btn-return">
                                <i class="fa fa-check"></i> Mark as Returned
                            </button>
                            <button type="button" class="btn-cancel-return">
                                <i class="fa fa-xmark"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php
            endwhile;
        else:
        ?>
            <div class="empty-state">
                <i class="fa fa-inbox"></i>
                <p>
                    <?php echo $search_performed
                        ? 'No borrowed items match your search.'
                        : 'No items are currently borrowed.'; ?>
                </p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script src="../js/motors.js"></script>
<script src="../js/return.js"></script>
</body>
</html>