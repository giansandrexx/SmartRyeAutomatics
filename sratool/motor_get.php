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

$error_message = "";

function generateSerialList($serial_range_str) {
    $serials = [];
    $parts   = preg_split('/[\n,]+/', $serial_range_str);
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        if (preg_match('/^([A-Za-z]+)(\d+)([A-Za-z]?)\s*-\s*([A-Za-z]+)(\d+)([A-Za-z]?)$/', $part, $m)) {
            $num_start = intval($m[2]);
            $num_end   = intval($m[5]);
            $pad_len   = strlen($m[2]);
            for ($i = $num_start; $i <= $num_end; $i++) {
                $serials[] = $m[1] . str_pad($i, $pad_len, '0', STR_PAD_LEFT) . $m[3];
            }
        } elseif (preg_match('/^([A-Za-z]+)(\d+)([A-Za-z]?)$/', $part, $m)) {
            $serials[] = $part;
        }
    }
    return $serials;
}

if (isset($_GET['ajax_serials']) && isset($_GET['motor_id'])) {
    header('Content-Type: application/json');
    $mid   = intval($_GET['motor_id']);
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    $mStmt = $conn->prepare("SELECT serial_number FROM motors WHERE id = ?");
    $mStmt->bind_param("i", $mid);
    $mStmt->execute();
    $mRes = $mStmt->get_result()->fetch_assoc();
    $mStmt->close();

    if (!$mRes) { echo json_encode([]); exit(); }

    $all      = generateSerialList($mRes['serial_number']);
    $filtered = array_values(array_filter($all, fn($s) => stripos($s, $query) !== false));

    $used  = [];
    $uStmt = $conn->prepare("SELECT serial_number FROM motor_records WHERE motor_id = ?");
    $uStmt->bind_param("i", $mid);
    $uStmt->execute();
    $uRes = $uStmt->get_result();
    while ($ur = $uRes->fetch_assoc()) {
        foreach (explode(',', $ur['serial_number']) as $us) $used[] = trim($us);
    }
    $uStmt->close();
    $conn->close();

    echo json_encode(array_map(fn($s) => [
        'serial'    => $s,
        'available' => !in_array($s, $used)
    ], array_slice($filtered, 0, 50)));
    exit();
}

$categories = [
    'motors' => [
        'label'       => 'Automation Motors',
        'icon'        => 'fa-cog',
        'has_serial'  => true,
        'table'       => 'motors',
        'record_table'=> 'motor_records',
        'name_field'  => 'motor_name',
        'id_field'    => 'id',
        'qty_field'   => 'quantity',
        'active_field'=> null,
    ],
    'video_intercom' => [
        'label'       => 'Video Intercom',
        'icon'        => 'fa-video',
        'has_serial'  => false,
        'table'       => 'video_intercom',
        'record_table'=> 'intercom_records',
        'name_field'  => 'item_name',
        'id_field'    => 'id',
        'qty_field'   => 'quantity',
        'active_field'=> null,
    ],
    'e_fences' => [
        'label'       => 'E-Fences',
        'icon'        => 'fa-shield-alt',
        'has_serial'  => false,
        'table'       => 'e_fences',
        'record_table'=> 'efence_records',
        'name_field'  => 'item_name',
        'id_field'    => 'id',
        'qty_field'   => 'quantity',
        'active_field'=> null,
    ],
    'consumables' => [
        'label'       => 'Consumables',
        'icon'        => 'fa-box-open',
        'has_serial'  => false,
        'table'       => 'consumables',
        'record_table'=> 'consumable_records',
        'name_field'  => 'item_name',
        'id_field'    => 'id',
        'qty_field'   => 'quantity',
        'active_field'=> null,
    ],
];

if (isset($_POST['get_submit'])) {
    $category      = $_POST['category'] ?? '';
    $item_id       = intval($_POST['item_id']);
    $quantity      = intval($_POST['quantity']);
    $project_name  = trim($_POST['project_name']);
    $issued_by     = trim($_POST['issued_by']);
    $received_by   = trim($_POST['received_by']);
    $date_acquired = $_POST['date_acquired'];
    $notes         = trim($_POST['notes']);
    $recorded_by   = $_SESSION['user_id'];

    $cat = $categories[$category] ?? null;

    if (!$cat) {
        $error_message = "Invalid category selected.";
    } elseif (empty($item_id) || empty($project_name) || empty($issued_by) || empty($received_by) || empty($date_acquired) || $quantity < 1) {
        $error_message = "Please fill in all required fields.";
    } else {
        $itemStmt = $conn->prepare("SELECT * FROM {$cat['table']} WHERE {$cat['id_field']} = ?");
        $itemStmt->bind_param("i", $item_id);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();
        $itemStmt->close();

        if (!$item) {
            $error_message = "Selected item not found.";
        } elseif ($item[$cat['qty_field']] < $quantity) {
            $error_message = "Not enough stock. Available: {$item[$cat['qty_field']]} unit(s).";
        } else {
            $item_name = $item[$cat['name_field']];

            if ($cat['has_serial']) {
                $entered_serials_raw = trim($_POST['entered_serials'] ?? '');
                $entered_serials     = array_filter(array_map('trim', explode(',', $entered_serials_raw)));

                if (count($entered_serials) !== $quantity) {
                    $error_message = "You entered " . count($entered_serials) . " serial(s) but quantity is {$quantity}. They must match.";
                } else {
                    $valid_serials = generateSerialList($item['serial_number']);
                    $uStmt = $conn->prepare("SELECT serial_number FROM {$cat['record_table']} WHERE motor_id = ?");
                    $uStmt->bind_param("i", $item_id);
                    $uStmt->execute();
                    $uRes = $uStmt->get_result();
                    $used_serials = [];
                    while ($ur = $uRes->fetch_assoc()) {
                        foreach (explode(',', $ur['serial_number']) as $us) $used_serials[] = trim($us);
                    }
                    $uStmt->close();

                    $invalid = array_filter($entered_serials, fn($s) => !in_array($s, $valid_serials));
                    $already = array_filter($entered_serials, fn($s) => in_array($s, $used_serials));

                    if (!empty($invalid)) {
                        $error_message = "Invalid serial(s): " . implode(', ', $invalid);
                    } elseif (!empty($already)) {
                        $error_message = "Already used serial(s): " . implode(', ', $already);
                    } else {
                        $serials_str = implode(', ', $entered_serials);
                        $stmt = $conn->prepare("INSERT INTO {$cat['record_table']} (motor_id, motor_name, serial_number, quantity, project_name, issued_by, received_by, date_acquired, notes, recorded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ississsssi", $item_id, $item_name, $serials_str, $quantity, $project_name, $issued_by, $received_by, $date_acquired, $notes, $recorded_by);
                        if ($stmt->execute()) {
                            $upd = $conn->prepare("UPDATE {$cat['table']} SET quantity = quantity - ? WHERE id = ?");
                            $upd->bind_param("ii", $quantity, $item_id);
                            $upd->execute();
                            $upd->close();
                            $stmt->close();
                            $conn->close();
                            header("Location: motor_get.php?success=1&cat=" . urlencode($category));
                            exit();
                        } else {
                            $error_message = "Error saving record: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO {$cat['record_table']} (item_id, item_name, quantity, project_name, issued_by, received_by, date_acquired, notes, recorded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isisssssi", $item_id, $item_name, $quantity, $project_name, $issued_by, $received_by, $date_acquired, $notes, $recorded_by);
                if ($stmt->execute()) {
                    $upd = $conn->prepare("UPDATE {$cat['table']} SET quantity = quantity - ? WHERE id = ?");
                    $upd->bind_param("ii", $quantity, $item_id);
                    $upd->execute();
                    $upd->close();
                    $stmt->close();
                    $conn->close();
                    header("Location: motor_get.php?success=1&cat=" . urlencode($category));
                    exit();
                } else {
                    $error_message = "Error saving record: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$items_by_category = [];
foreach ($categories as $key => $cat) {
    $res = $conn->query("SELECT * FROM {$cat['table']} ORDER BY {$cat['name_field']} ASC");
    $items_by_category[$key] = [];
    if ($res) while ($r = $res->fetch_assoc()) $items_by_category[$key][] = $r;
}

$search_query     = isset($_POST['search_query']) ? trim($_POST['search_query']) : '';
$search_performed = !empty($search_query);
$history_rows     = [];

$record_tables = [
    'motors'         => ['motor_records',     'motor_name', true,  'Automation Motors'],
    'video_intercom' => ['intercom_records',   'item_name',  false, 'Video Intercom'],
    'e_fences'       => ['efence_records',     'item_name',  false, 'E-Fences'],
    'consumables'    => ['consumable_records', 'item_name',  false, 'Consumables'],
];

foreach ($record_tables as $cat_key => [$rtable, $nfield, $has_serial, $cat_label]) {
    $check = $conn->query("SHOW TABLES LIKE '$rtable'");
    if (!$check || $check->num_rows === 0) continue;

    if ($search_performed) {
        $st = "%{$search_query}%";
        if ($has_serial) {
            $q = $conn->prepare("SELECT *, '$cat_label' AS category_label FROM $rtable WHERE $nfield LIKE ? OR project_name LIKE ? OR issued_by LIKE ? OR received_by LIKE ? OR date_acquired LIKE ? OR serial_number LIKE ? OR notes LIKE ? ORDER BY date_acquired DESC, created_at DESC");
            $q->bind_param("sssssss", $st, $st, $st, $st, $st, $st, $st);
        } else {
            $q = $conn->prepare("SELECT *, '$cat_label' AS category_label FROM $rtable WHERE $nfield LIKE ? OR project_name LIKE ? OR issued_by LIKE ? OR received_by LIKE ? OR date_acquired LIKE ? OR notes LIKE ? ORDER BY date_acquired DESC, created_at DESC");
            $q->bind_param("ssssss", $st, $st, $st, $st, $st, $st);
        }
        $q->execute();
        $res = $q->get_result();
        $q->close();
    } else {
        $res = $conn->query("SELECT *, '$cat_label' AS category_label FROM $rtable ORDER BY date_acquired DESC, created_at DESC LIMIT 50");
    }
    if ($res) while ($r = $res->fetch_assoc()) {
        $r['_has_serial'] = $has_serial;
        $r['_name_field'] = $nfield;
        $history_rows[] = $r;
    }
}

usort($history_rows, fn($a, $b) => strtotime($b['created_at'] ?? $b['date_acquired']) - strtotime($a['created_at'] ?? $a['date_acquired']));
if (!$search_performed) $history_rows = array_slice($history_rows, 0, 50);

$conn->close();

$cat_icons_php = [
    'Automation Motors' => 'fa-cog',
    'Video Intercom'    => 'fa-video',
    'E-Fences'          => 'fa-shield-alt',
    'Consumables'       => 'fa-box-open',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Acquisition - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/motor_get.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<?php include 'navigation.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h2><i class="fas fa-boxes"></i>Record Acquisition</h2>
        <p>Record acquisitions across all categories</p>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Record saved successfully!</span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="form-section">
        <div class="form-card">
            <div class="form-card-header">
                <h3><i class="fas fa-plus-circle"></i> Record Item Acquisition</h3>
                <p>Select a category, then fill in the details below</p>
            </div>

            <form method="POST" action="" id="mainForm">
                <input type="hidden" name="category" id="categoryInput" value="">
                <input type="hidden" name="item_id"  id="itemIdInput"   value="">

                <div class="form-step">
                    <div class="form-step-label">
                        <div class="step-dot">1</div>
                        <div class="step-line"></div>
                    </div>
                    <div class="form-step-body">
                        <div class="form-step-title"><i class="fas fa-th-large"></i> Category</div>
                        <div class="category-grid">
                            <?php foreach ($categories as $key => $cat): ?>
                                <button type="button" class="cat-btn" id="catBtn_<?php echo $key; ?>" onclick="selectCategory('<?php echo $key; ?>')">
                                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                                    <?php echo $cat['label']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-step">
                    <div class="form-step-label">
                        <div class="step-dot">2</div>
                        <div class="step-line"></div>
                    </div>
                    <div class="form-step-body">
                        <div class="form-step-title"><i class="fas fa-folder-open"></i> Project</div>
                        <div class="form-group">
                            <label class="form-label">Project Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-folder-open input-icon"></i>
                                <input type="text" name="project_name" class="form-control with-icon"
                                    placeholder="Enter project name..."
                                    value="<?php echo isset($_POST['project_name']) ? htmlspecialchars($_POST['project_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-step">
                    <div class="form-step-label">
                        <div class="step-dot">3</div>
                        <div class="step-line"></div>
                    </div>
                    <div class="form-step-body">
                        <div class="form-step-title"><i class="fas fa-box input-icon-step" id="stepItemIcon"></i> <span id="stepItemLabel">Item</span> &amp; Quantity</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Item <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="fas fa-box input-icon" id="itemSelectIcon"></i>
                                    <select id="itemSelect" class="form-control with-icon" onchange="onItemChange(this)" disabled>
                                        <option value="">— Select a category first —</option>
                                    </select>
                                </div>
                                <small id="stockHint" class="item-stock-hint"></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantity <span class="required">*</span></label>
                                <div class="input-wrapper">
                                    <i class="fas fa-sort-numeric-up input-icon"></i>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control with-icon"
                                        placeholder="1" min="1"
                                        value="<?php echo isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; ?>"
                                        required oninput="updateCounter()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-step serial-section" id="serialSection">
                    <div class="form-step-label">
                        <div class="step-dot">4</div>
                        <div class="step-line"></div>
                    </div>
                    <div class="form-step-body">
                        <div class="form-step-title"><i class="fas fa-barcode"></i> Serial Numbers</div>
                        <div class="form-group">
                            <label class="form-label">Serial Number(s) <span class="required">*</span></label>
                            <div class="serial-wrapper">
                                <div class="serial-input-area" id="serialInputArea" onclick="document.getElementById('serialTextInput').focus()">
                                    <input type="text" id="serialTextInput" class="serial-text-input"
                                        placeholder="Type a serial number..."
                                        autocomplete="off"
                                        oninput="fetchSuggestions()"
                                        onkeydown="handleSerialKey(event)"
                                        disabled>
                                </div>
                                <div class="serial-dropdown" id="serialDropdown"></div>
                            </div>
                            <div class="serial-counter" id="serialCounter">Select a motor first.</div>
                            <input type="hidden" name="entered_serials" id="entered_serials">
                        </div>
                    </div>
                </div>

                <div class="form-step">
                    <div class="form-step-label">
                        <div class="step-dot" id="detailStepDot">4</div>
                    </div>
                    <div class="form-step-body">
                        <div class="form-step-title"><i class="fas fa-info-circle"></i> Acquisition Details</div>
                        <div class="field-card">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Date Acquired <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-calendar-alt input-icon"></i>
                                        <input type="date" name="date_acquired" class="form-control with-icon"
                                            value="<?php echo isset($_POST['date_acquired']) ? htmlspecialchars($_POST['date_acquired']) : date('Y-m-d'); ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Notes <span class="optional">(Optional)</span></label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-sticky-note input-icon"></i>
                                        <input type="text" name="notes" class="form-control with-icon"
                                            placeholder="Add any additional notes..."
                                            value="<?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Issued By <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <input type="text" name="issued_by" class="form-control with-icon"
                                            placeholder="Name of person issuing..."
                                            value="<?php echo isset($_POST['issued_by']) ? htmlspecialchars($_POST['issued_by']) : ''; ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Received By <span class="required">*</span></label>
                                    <div class="input-wrapper">
                                        <i class="fas fa-user-check input-icon"></i>
                                        <input type="text" name="received_by" class="form-control with-icon"
                                            placeholder="Name of person receiving..."
                                            value="<?php echo isset($_POST['received_by']) ? htmlspecialchars($_POST['received_by']) : ''; ?>"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <span class="form-actions-hint">
                        <i class="fas fa-shield-alt"></i> All required fields must be filled
                    </span>
                    <button type="submit" name="get_submit" class="btn-submit" onclick="return prepareSubmit()">
                        <i class="fas fa-save"></i> Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="history-section">
        <div class="section-header">
            <h3><i class="fas fa-history"></i> Acquisition History</h3>
        </div>
        <div class="search-section">
            <form method="POST" action="" class="search-form">
                <input type="text" name="search_query" class="search-input"
                    placeholder="Search by item, project, issued by, received by, date (YYYY-MM-DD), serial, notes..."
                    value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <?php if ($search_performed): ?>
                    <a href="motor_get.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
            <?php if ($search_performed): ?>
                <small style="color:#888; margin-top:6px; display:block;">
                    <i class="fas fa-info-circle"></i> Showing results for: <strong><?php echo htmlspecialchars($search_query); ?></strong>
                    &nbsp;&mdash;&nbsp;<?php echo count($history_rows); ?> record(s) found
                </small>
            <?php endif; ?>
        </div>

        <div class="items-list">
            <?php if (!empty($history_rows)): foreach ($history_rows as $row):
                $item_name        = $row[$row['_name_field']] ?? ($row['motor_name'] ?? $row['item_name'] ?? '—');
                $date_formatted   = date('M d, Y', strtotime($row['date_acquired']));
                $logged_formatted = isset($row['created_at']) ? date('M d, Y h:i A', strtotime($row['created_at'])) : '—';
                $cat_icon         = $cat_icons_php[$row['category_label']] ?? 'fa-box';
            ?>
                <div class="item-card">
                    <div class="item-cat-bar">
                        <span class="cat-badge">
                            <i class="fas <?php echo $cat_icon; ?>"></i>
                            <?php echo htmlspecialchars($row['category_label']); ?>
                        </span>
                    </div>
                    <div class="item-project-header">
                        <i class="fas fa-folder-open"></i>
                        <span><?php echo htmlspecialchars($row['project_name']); ?></span>
                    </div>
                    <div class="item-header">
                        <div class="item-title">
                            <h4><?php echo htmlspecialchars($item_name); ?></h4>
                            <?php if ($row['_has_serial'] && !empty($row['serial_number'])): ?>
                                <span class="serial-tag">
                                    <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($row['serial_number']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-acquired">ACQUIRED</span>
                    </div>
                    <div class="item-details">
                        <div class="detail-item">
                            <i class="fas fa-boxes"></i>
                            <span>Qty: <strong><?php echo $row['quantity']; ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Date: <strong><?php echo $date_formatted; ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <span>Logged: <strong><?php echo $logged_formatted; ?></strong></span>
                        </div>
                    </div>
                    <div class="transfer-row">
                        <div class="transfer-person">
                            <i class="fas fa-user-tie"></i>
                            <div>
                                <span class="transfer-label">Issued by</span>
                                <span class="transfer-name"><?php echo htmlspecialchars($row['issued_by']); ?></span>
                            </div>
                        </div>
                        <i class="fas fa-arrow-right transfer-arrow"></i>
                        <div class="transfer-person">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <span class="transfer-label">Received by</span>
                                <span class="transfer-name"><?php echo htmlspecialchars($row['received_by']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($row['notes'])): ?>
                        <div class="notes-box">
                            <i class="fas fa-sticky-note"></i>
                            <span><?php echo nl2br(htmlspecialchars($row['notes'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h4>No Records Found</h4>
                    <p><?php echo $search_performed ? 'No records match your search "' . htmlspecialchars($search_query) . '".' : 'No acquisition records yet. Add one above!'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="alertToast" class="toast-wrap">
    <div id="alertToastInner" class="toast-inner">
        <div class="toast-icon-box">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="toast-body">
            <div id="alertToastTitle" class="toast-title"></div>
            <div id="alertToastMsg" class="toast-msg"></div>
        </div>
        <button onclick="closeToast()" class="toast-close">&times;</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const itemsByCategory = <?php
    $out = [];
    foreach ($items_by_category as $key => $rows) {
        $cat = $categories[$key];
        $out[$key] = array_map(function($r) use ($cat) {
            return [
                'id'         => $r[$cat['id_field']],
                'name'       => $r[$cat['name_field']],
                'quantity'   => intval($r[$cat['qty_field']]),
                'serial'     => $r['serial_number'] ?? '',
                'has_serial' => $cat['has_serial'],
            ];
        }, $rows);
    }
    echo json_encode($out);
?>;

const categoryIcons = {
    motors:         'fa-cog',
    video_intercom: 'fa-video',
    e_fences:       'fa-shield-alt',
    consumables:    'fa-box-open',
};

const categoryLabels = {
    motors:         'Automation Motors',
    video_intercom: 'Video Intercom',
    e_fences:       'E-Fences',
    consumables:    'Consumables',
};

let selectedCategory = null;
let selectedSerials  = [];
let currentMotorId   = null;
let fetchTimer       = null;
let toastTimer       = null;

function showToast(title, message) {
    clearTimeout(toastTimer);
    document.getElementById('alertToastTitle').textContent = title;
    document.getElementById('alertToastMsg').innerHTML = message;
    const toast = document.getElementById('alertToast');
    const inner = document.getElementById('alertToastInner');
    inner.style.animation = 'none';
    toast.classList.add('visible');
    void inner.offsetWidth;
    inner.style.animation = 'toastIn 0.3s cubic-bezier(.34,1.56,.64,1) both';
    toastTimer = setTimeout(() => closeToast(), 5000);
}

function closeToast() {
    const inner = document.getElementById('alertToastInner');
    inner.style.animation = 'toastOut 0.25s ease forwards';
    setTimeout(() => document.getElementById('alertToast').classList.remove('visible'), 250);
}

function selectCategory(key) {
    selectedCategory = key;
    document.getElementById('categoryInput').value = key;

    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('catBtn_' + key).classList.add('active');

    document.getElementById('itemSelectIcon').className = 'fas ' + categoryIcons[key] + ' input-icon';
    document.getElementById('stepItemLabel').textContent = categoryLabels[key];

    const sel = document.getElementById('itemSelect');
    sel.innerHTML = '<option value="">— Select ' + categoryLabels[key] + ' item —</option>';
    (itemsByCategory[key] || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name + ' (Stock: ' + item.quantity + ')';
        opt.dataset.stock     = item.quantity;
        opt.dataset.serial    = item.serial;
        opt.dataset.hasSerial = item.has_serial ? '1' : '0';
        sel.appendChild(opt);
    });
    sel.disabled = false;

    selectedSerials = [];
    currentMotorId  = null;
    document.getElementById('itemIdInput').value = '';
    document.getElementById('stockHint').innerHTML = '';
    document.getElementById('serialSection').classList.remove('visible');
    document.getElementById('serialTextInput').disabled = true;
    document.getElementById('detailStepDot').textContent = '4';
    renderChips();
}

function onItemChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!sel.value) {
        document.getElementById('itemIdInput').value = '';
        document.getElementById('stockHint').innerHTML = '';
        document.getElementById('serialSection').classList.remove('visible');
        document.getElementById('detailStepDot').textContent = '4';
        return;
    }

    const stock     = parseInt(opt.dataset.stock) || 0;
    const hasSerial = opt.dataset.hasSerial === '1';

    document.getElementById('itemIdInput').value = sel.value;

    if (stock === 0) {
        document.getElementById('stockHint').innerHTML = '<span class="hint-out"><i class="fas fa-exclamation-circle"></i> Out of stock</span>';
    } else {
        document.getElementById('stockHint').innerHTML = '<span class="hint-in"><i class="fas fa-box"></i> Available stock: <strong>' + stock + '</strong></span>';
    }

    if (hasSerial) {
        currentMotorId = sel.value;
        selectedSerials = [];
        renderChips();
        document.getElementById('serialSection').classList.add('visible');
        document.getElementById('detailStepDot').textContent = '5';
        const sInput = document.getElementById('serialTextInput');
        sInput.disabled = false;
        sInput.placeholder = 'Type a serial number...';
        updateCounter();
    } else {
        currentMotorId = null;
        selectedSerials = [];
        document.getElementById('serialSection').classList.remove('visible');
        document.getElementById('detailStepDot').textContent = '4';
        document.getElementById('serialCounter').textContent = '';
    }
}

function fetchSuggestions() {
    clearTimeout(fetchTimer);
    if (!currentMotorId) return;
    const q = document.getElementById('serialTextInput').value.trim();
    if (!q.length) { document.getElementById('serialDropdown').classList.remove('open'); return; }
    fetchTimer = setTimeout(() => {
        fetch(`motor_get.php?ajax_serials=1&motor_id=${currentMotorId}&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => renderDropdown(data))
            .catch(() => {});
    }, 200);
}

function renderDropdown(items) {
    const dropdown = document.getElementById('serialDropdown');
    dropdown.innerHTML = '';
    if (!items.length) { dropdown.classList.remove('open'); return; }
    items.forEach(item => {
        if (selectedSerials.includes(item.serial)) return;
        const div = document.createElement('div');
        div.className = 'serial-dropdown-item' + (item.available ? '' : ' used');
        div.innerHTML = `<span>${item.serial}</span><span class="${item.available ? 'badge-avail' : 'badge-used'}">${item.available ? 'Available' : 'Used'}</span>`;
        if (item.available) div.onclick = () => addSerial(item.serial);
        dropdown.appendChild(div);
    });
    dropdown.classList.add('open');
}

function addSerial(serial) {
    const qty = parseInt(document.getElementById('quantityInput').value) || 1;
    if (selectedSerials.includes(serial)) return;
    if (selectedSerials.length >= qty) {
        showToast('Limit Reached', `You can only add <strong>${qty}</strong> serial number(s) to match the quantity.`);
        return;
    }
    selectedSerials.push(serial);
    renderChips();
    document.getElementById('serialTextInput').value = '';
    document.getElementById('serialDropdown').classList.remove('open');
    updateCounter();
}

function removeSerial(serial) {
    selectedSerials = selectedSerials.filter(s => s !== serial);
    renderChips();
    updateCounter();
}

function renderChips() {
    const area = document.getElementById('serialInputArea');
    area.querySelectorAll('.serial-tag-chip').forEach(c => c.remove());
    selectedSerials.forEach(s => {
        const chip = document.createElement('span');
        chip.className = 'serial-tag-chip';
        chip.innerHTML = `${s} <span class="chip-remove" onclick="removeSerial('${s}')">&times;</span>`;
        area.insertBefore(chip, document.getElementById('serialTextInput'));
    });
}

function updateCounter() {
    const counter = document.getElementById('serialCounter');
    if (!currentMotorId) return;
    const qty   = parseInt(document.getElementById('quantityInput').value) || 1;
    const added = selectedSerials.length;
    if (added === qty) {
        counter.textContent = `✔ ${added} of ${qty} serial number(s) added.`;
        counter.className = 'serial-counter match';
    } else {
        counter.textContent = `${added} of ${qty} serial number(s) added. ${qty - added} more needed.`;
        counter.className = added > qty ? 'serial-counter mismatch' : 'serial-counter';
    }
}

function handleSerialKey(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const first = document.querySelector('.serial-dropdown-item:not(.used)');
        if (first) first.click();
    }
    if (e.key === 'Escape') document.getElementById('serialDropdown').classList.remove('open');
}

document.addEventListener('click', e => {
    const area     = document.getElementById('serialInputArea');
    const dropdown = document.getElementById('serialDropdown');
    if (!area.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
    }
});

function prepareSubmit() {
    if (!selectedCategory) {
        showToast('No Category Selected', 'Please select a category first.');
        return false;
    }
    if (!document.getElementById('itemIdInput').value) {
        showToast('No Item Selected', 'Please select an item from the list.');
        return false;
    }
    if (selectedCategory === 'motors') {
        document.getElementById('entered_serials').value = selectedSerials.join(',');
        const qty = parseInt(document.getElementById('quantityInput').value) || 1;
        if (selectedSerials.length !== qty) {
            showToast('Serial Mismatch', `Serial count <strong>${selectedSerials.length}</strong> must match quantity <strong>${qty}</strong>.`);
            return false;
        }
    }
    return true;
}
</script>
</body>

</html>
