<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once "../config.php";
require_once "../log_helper.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uid   = $_SESSION['user_id'];
$uname = $_SESSION['username']  ?? 'unknown';
$uful  = $_SESSION['full_name'] ?? 'Unknown User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $motor_name    = $conn->real_escape_string($_POST['motor_name']);
            $serial_number = $conn->real_escape_string($_POST['serial_number']);
            $quantity      = (int)$_POST['quantity'];
            $sql = "INSERT INTO motors (motor_name, serial_number, quantity) VALUES ('$motor_name', '$serial_number', $quantity)";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Added Motor',
                    "Added motor: \"$motor_name\" | Serial: $serial_number | Qty: $quantity");
            }

        } elseif ($_POST['action'] === 'edit') {
            $id            = (int)$_POST['id'];
            $motor_name    = $conn->real_escape_string($_POST['motor_name']);
            $serial_number = $conn->real_escape_string($_POST['serial_number']);
            $quantity      = (int)$_POST['quantity'];
            $old           = $conn->query("SELECT motor_name, quantity FROM motors WHERE id=$id")->fetch_assoc();
            $sql = "UPDATE motors SET motor_name='$motor_name', serial_number='$serial_number', quantity=$quantity WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Edited Motor',
                    "Edited motor: \"{$old['motor_name']}\" → \"$motor_name\" | Qty: {$old['quantity']} → $quantity | Serial: $serial_number");
            }

        } elseif ($_POST['action'] === 'delete') {
            $id  = (int)$_POST['id'];
            $old = $conn->query("SELECT motor_name, quantity FROM motors WHERE id=$id")->fetch_assoc();
            $sql = "DELETE FROM motors WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Deleted Motor',
                    "Deleted motor: \"{$old['motor_name']}\" (was {$old['quantity']} units)");
            }

        } elseif ($_POST['action'] === 'adjust') {
            $id         = (int)$_POST['id'];
            $adjustment = (int)$_POST['adjustment'];
            $old        = $conn->query("SELECT motor_name, quantity FROM motors WHERE id=$id")->fetch_assoc();

            $sql = "UPDATE motors SET quantity = quantity + $adjustment WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                $newQty    = $old['quantity'] + $adjustment;
                $direction = $adjustment >= 0 ? "+$adjustment" : "$adjustment";
                $extra     = '';

                if ($adjustment > 0 && !empty($_POST['new_serial_range'])) {
                    $new_range = $conn->real_escape_string(trim($_POST['new_serial_range']));
                    $sql2 = "UPDATE motors SET serial_number = CONCAT(serial_number, ',', '$new_range') WHERE id=$id";
                    $conn->query($sql2);
                    $extra = " | New serial range: $new_range";
                }

                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Adjusted Motor Stock',
                    "Adjusted \"{$old['motor_name']}\": {$old['quantity']} → $newQty ($direction)$extra");
            }
        }
        header("Location: motors.php");
        exit();
    }
}

$motors       = $conn->query("SELECT * FROM motors ORDER BY motor_name ASC");
$total_items  = $conn->query("SELECT COUNT(*) as count FROM motors")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM motors WHERE quantity = 0")->fetch_assoc()['count'];
$in_stock     = $conn->query("SELECT COUNT(*) as count FROM motors WHERE quantity > 0")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Motors - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/consumables.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        @keyframes toastIn {
            from { opacity:0; transform:translateY(16px) scale(0.96); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        @keyframes toastOut {
            from { opacity:1; transform:translateY(0) scale(1); }
            to   { opacity:0; transform:translateY(10px) scale(0.96); }
        }
    </style>
</head>
<body>

<?php include 'navigation.php'; ?>

<div class="main-content">

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Motors</h3>
                <div class="stat-number"><?php echo $total_items; ?></div>
            </div>
            <div class="stat-circle circle-blue" style="--percent: 100%;">
                <div class="circle-inner">
                    <i class="fas fa-cog"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>In Stock</h3>
                <div class="stat-number"><?php echo $in_stock; ?></div>
            </div>
            <div class="stat-circle circle-green" style="--percent: <?php echo $total_items > 0 ? round(($in_stock / $total_items) * 100) : 0; ?>%;">
                <div class="circle-inner">
                    <?php echo $total_items > 0 ? round(($in_stock / $total_items) * 100) : 0; ?>%
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3>Out of Stock</h3>
                <div class="stat-number"><?php echo $out_of_stock; ?></div>
            </div>
            <div class="stat-circle circle-purple" style="--percent: <?php echo $total_items > 0 ? round(($out_of_stock / $total_items) * 100) : 0; ?>%;">
                <div class="circle-inner">
                    <?php echo $total_items > 0 ? round(($out_of_stock / $total_items) * 100) : 0; ?>%
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <h2><i class="fas fa-cog"></i> Automation Motors Inventory</h2>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Motor
        </button>
    </div>

    <div class="table-container">
        <?php if ($motors->num_rows > 0): ?>
            <table class="consumables-table">
                <thead>
                    <tr>
                        <th>Motor Name</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $motors->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['motor_name']); ?></strong></td>
                            <td><strong style="font-size: 16px;"><?php echo $row['quantity']; ?></strong></td>
                            <td>
                                <?php if ($row['quantity'] == 0): ?>
                                    <span class="stock-badge stock-out">Out of Stock</span>
                                <?php else: ?>
                                    <span class="stock-badge stock-good">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-adjust" onclick="openAdjustModal(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo htmlspecialchars($row['motor_name'], ENT_QUOTES); ?>',
                                        <?php echo $row['quantity']; ?>,
                                        '<?php echo htmlspecialchars($row['serial_number'], ENT_QUOTES); ?>'
                                    )">
                                        <i class="fas fa-sliders-h"></i> Adjust
                                    </button>
                                    <button class="btn-action btn-edit" onclick="openEditModal(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo htmlspecialchars($row['motor_name'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['serial_number'], ENT_QUOTES); ?>',
                                        <?php echo $row['quantity']; ?>
                                    )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteItem(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo htmlspecialchars($row['motor_name'], ENT_QUOTES); ?>'
                                    )">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-cog"></i>
                <p>No motors in inventory</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Motor</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form id="itemForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId">
                <div class="form-group">
                    <label>Motor Name *</label>
                    <input type="text" name="motor_name" id="itemName" required>
                </div>
                <div class="form-group">
                    <label>Serial Number Range *
                        <small style="color:#888; font-weight:normal;">(e.g. RM02601-RM026372)</small>
                    </label>
                    <input type="text" name="serial_number" id="itemSerial" required placeholder="e.g. RM02601-RM026372">
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" id="quantity" min="0" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="adjustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Adjust Stock</h3>
            <button class="close" onclick="closeAdjustModal()">&times;</button>
        </div>
        <form id="adjustForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="id" id="adjustId">
                <input type="hidden" name="new_serial_range" id="newSerialRangeHidden">
                <div class="form-group">
                    <label id="adjustItemName" style="font-size:16px;color:#333;font-weight:600;"></label>
                </div>
                <div class="form-group">
                    <label>Current Stock: <strong id="currentStock">0</strong></label>
                </div>
                <div class="form-group">
                    <label>Adjustment Amount</label>
                    <div class="adjustment-controls">
                        <button type="button" class="btn-qty" onclick="adjustQuantity(-10)">-10</button>
                        <button type="button" class="btn-qty" onclick="adjustQuantity(-1)">-1</button>
                        <input type="number" name="adjustment" id="adjustmentAmount" class="qty-input" value="0" oninput="onAdjustmentChange()">
                        <button type="button" class="btn-qty" onclick="adjustQuantity(1)">+1</button>
                        <button type="button" class="btn-qty" onclick="adjustQuantity(10)">+10</button>
                    </div>
                    <small style="color:#666;display:block;margin-top:5px;">Use negative values to decrease stock</small>
                </div>
                <div class="form-group">
                    <label>New Stock Total: <strong id="newStock">0</strong></label>
                </div>
                <div class="serial-range-section" id="serialRangeSection">
                    <div class="serial-info-box" id="serialInfoBox"></div>
                    <div class="form-group" style="margin-bottom:6px;">
                        <label style="font-weight:600;">
                            <i class="fas fa-barcode"></i>
                            New Serial Range for Added Units
                            <span style="color:#dc3545;">*</span>
                        </label>
                        <small style="color:#666;display:block;margin-bottom:8px;">
                            Enter the starting and ending number for the new batch of serials.
                        </small>
                        <div class="range-row">
                            <span class="prefix-label" id="serialPrefixDisplay">RM0</span>
                            <input type="number" id="rangeStart" placeholder="start" min="1" oninput="updateRangePreview()">
                            <span class="dash">–</span>
                            <span class="prefix-label" id="serialPrefixDisplay2">RM0</span>
                            <input type="number" id="rangeEnd" placeholder="end" min="1" oninput="updateRangePreview()">
                        </div>
                        <div class="range-preview" id="rangePreview"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn-submit" onclick="return prepareAdjustSubmit()">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<div id="alertToast" style="position:fixed;bottom:28px;right:28px;z-index:9999;display:none;flex-direction:column;gap:4px;max-width:360px;">
    <div id="alertToastInner" style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.18),0 2px 8px rgba(0,0,0,0.10);border-left:4px solid #ef4444;padding:16px 18px 14px 16px;display:flex;gap:12px;align-items:flex-start;">
        <div style="width:32px;height:32px;border-radius:8px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <i class="fas fa-triangle-exclamation" style="color:#ef4444;font-size:14px;"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div id="alertToastTitle" style="font-weight:700;font-size:13.5px;color:#1a1a2e;margin-bottom:3px;"></div>
            <div id="alertToastMsg" style="font-size:12.5px;color:#555;line-height:1.5;"></div>
        </div>
        <button onclick="closeToast()" style="background:none;border:none;cursor:pointer;color:#aaa;font-size:16px;line-height:1;padding:0;margin-top:-2px;flex-shrink:0;">&times;</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../sratool/js/delete-confirm.js"></script>
<script src="../sratool/js/motor.js"></script>
<script src="../js/motors.js"></script>

</body>
</html>
