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
            $item_name = $conn->real_escape_string($_POST['item_name']);
            $quantity  = (int)$_POST['quantity'];
            $sql = "INSERT INTO video_intercom (item_name, quantity) VALUES ('$item_name', $quantity)";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Added Video Intercom Item',
                    "Added: \"$item_name\" — Qty: $quantity");
            }

        } elseif ($_POST['action'] === 'edit') {
            $id        = (int)$_POST['id'];
            $item_name = $conn->real_escape_string($_POST['item_name']);
            $quantity  = (int)$_POST['quantity'];
            $old       = $conn->query("SELECT item_name, quantity FROM video_intercom WHERE id=$id")->fetch_assoc();
            $sql = "UPDATE video_intercom SET item_name='$item_name', quantity=$quantity WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Edited Video Intercom Item',
                    "Edited: \"{$old['item_name']}\" → \"$item_name\" | Qty: {$old['quantity']} → $quantity");
            }

        } elseif ($_POST['action'] === 'delete') {
            $id  = (int)$_POST['id'];
            $old = $conn->query("SELECT item_name, quantity FROM video_intercom WHERE id=$id")->fetch_assoc();
            $sql = "DELETE FROM video_intercom WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Deleted Video Intercom Item',
                    "Deleted: \"{$old['item_name']}\" (was {$old['quantity']} units)");
            }

        } elseif ($_POST['action'] === 'adjust') {
            $id         = (int)$_POST['id'];
            $adjustment = (int)$_POST['adjustment'];
            $old        = $conn->query("SELECT item_name, quantity FROM video_intercom WHERE id=$id")->fetch_assoc(); // ← ADDED
            $sql = "UPDATE video_intercom SET quantity = quantity + $adjustment WHERE id=$id";
            if ($conn->query($sql)) { // ← CHANGED
                $newQty    = $old['quantity'] + $adjustment;
                $direction = $adjustment >= 0 ? "+$adjustment" : "$adjustment";
                logActivity($conn, $uid, $uname, $uful, 'tool_room', 'Adjusted Video Intercom Stock',
                    "Adjusted \"{$old['item_name']}\": {$old['quantity']} → $newQty ($direction)");
            }
        }
        header("Location: intercom.php");
        exit();
    }
}

$items        = $conn->query("SELECT * FROM video_intercom ORDER BY item_name ASC");
$total_items  = $conn->query("SELECT COUNT(*) as count FROM video_intercom")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM video_intercom WHERE quantity = 0")->fetch_assoc()['count'];
$in_stock     = $conn->query("SELECT COUNT(*) as count FROM video_intercom WHERE quantity > 0")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Intercom - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/consumables.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<?php include 'navigation.php'; ?>

<div class="main-content">

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Items</h3>
                <div class="stat-number"><?php echo $total_items; ?></div>
            </div>
            <div class="stat-circle circle-blue" style="--percent: 100%;">
                <div class="circle-inner">
                    <i class="fas fa-video"></i>
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
        <h2><i class="fas fa-video"></i> Video Intercom Inventory</h2>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>

    <div class="table-container">
        <?php if ($items->num_rows > 0): ?>
            <table class="consumables-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $items->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                            <td><strong style="font-size:16px;"><?php echo $row['quantity']; ?></strong></td>
                            <td>
                                <?php if ($row['quantity'] == 0): ?>
                                    <span class="stock-badge stock-out">Out of Stock</span>
                                <?php else: ?>
                                    <span class="stock-badge stock-good">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-adjust" onclick="openAdjustModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>', <?php echo $row['quantity']; ?>)">
                                        <i class="fas fa-sliders-h"></i> Adjust
                                    </button>
                                    <button class="btn-action btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>', <?php echo $row['quantity']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteItem(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>')">
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
                <i class="fas fa-video"></i>
                <p>No video intercom items in inventory</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Item</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form id="itemForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId">
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" name="item_name" id="itemName" required>
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
                        <input type="number" name="adjustment" id="adjustmentAmount" class="qty-input" value="0">
                        <button type="button" class="btn-qty" onclick="adjustQuantity(1)">+1</button>
                        <button type="button" class="btn-qty" onclick="adjustQuantity(10)">+10</button>
                    </div>
                    <small style="color:#666;display:block;margin-top:5px;">Use negative values to decrease stock</small>
                </div>
                <div class="form-group">
                    <label>New Stock: <strong id="newStock">0</strong></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn-submit">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../sratool/js/delete-confirm.js"></script>
<script src="../sratool/js/intercom.js"></script>

</body>
</html>
