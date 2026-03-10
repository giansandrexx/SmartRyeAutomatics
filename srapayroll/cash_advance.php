<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$conn->query("CREATE TABLE IF NOT EXISTS cash_advances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_given DATE NOT NULL,
    notes TEXT,
    status ENUM('pending','deducted','cancelled') NOT NULL DEFAULT 'pending',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $employee_id = (int)$_POST['employee_id'];
        $amount      = (float)$_POST['amount'];
        $date_given  = $conn->real_escape_string($_POST['date_given']);
        $notes       = $conn->real_escape_string($_POST['notes'] ?? '');

        if ($employee_id && $amount > 0 && $date_given) {
            $stmt = $conn->prepare("INSERT INTO cash_advances (employee_id, amount, date_given, notes, created_by) VALUES (?,?,?,?,?)");
            $stmt->bind_param("idssi", $employee_id, $amount, $date_given, $notes, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $message = "Cash advance added successfully.";
                $message_type = 'success';
            } else {
                $message = "Error: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Please fill in all required fields.";
            $message_type = 'error';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['ca_id'];
        $conn->query("DELETE FROM cash_advances WHERE id=$id");
        $message = "Record deleted.";
        $message_type = 'success';
    }
}

$employees = [];
$r = $conn->query("SELECT id, name, department FROM employees WHERE is_active=1 ORDER BY name");
if ($r) { while ($row = $r->fetch_assoc()) { $employees[] = $row; } }

$filter_emp    = (int)($_GET['emp'] ?? 0);
$filter_status = $conn->real_escape_string($_GET['status'] ?? '');

$where = '1';
if ($filter_emp)    $where .= " AND ca.employee_id = $filter_emp";
if ($filter_status) $where .= " AND ca.status = '$filter_status'";

$advances = [];
$r = $conn->query("SELECT ca.*, e.name as emp_name, e.department
    FROM cash_advances ca
    JOIN employees e ON e.id = ca.employee_id
    WHERE $where
    ORDER BY ca.date_given DESC, ca.created_at DESC");
if ($r) { while ($row = $r->fetch_assoc()) { $advances[] = $row; } }

$stats = ['total'=>0, 'pending'=>0, 'pending_amount'=>0];
$rs = $conn->query("SELECT COUNT(*) as total,
    SUM(status='pending') as pending,
    SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as pending_amount
    FROM cash_advances");
if ($rs) { $stats = $rs->fetch_assoc(); }

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/advance.css">
    <link rel="stylesheet" href="css/process.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">

    <div class="page-header">
        <h2>Cash Advances</h2>
        <p>Track and manage employee cash advances and deductions.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <i class="fas fa-<?= $message_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-label">Total Records</div>
            <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Pending (Not Deducted)</div>
            <div class="stat-value amber"><?= number_format($stats['pending'] ?? 0) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Total Pending Amount</div>
            <div class="stat-value red">₱<?= number_format($stats['pending_amount'] ?? 0, 2) ?></div>
        </div>
    </div>

    <div class="top-bar">
        <div class="top-bar-left">
            <form method="GET" id="filterForm" style="display:contents">
                <select class="filter-select" name="emp" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $filter_emp==$emp['id']?'selected':'' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select class="filter-select" name="status" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Status</option>
                    <option value="pending"   <?= $filter_status==='pending'   ?'selected':'' ?>>Pending</option>
                    <option value="deducted"  <?= $filter_status==='deducted'  ?'selected':'' ?>>Deducted</option>
                    <option value="cancelled" <?= $filter_status==='cancelled' ?'selected':'' ?>>Cancelled</option>
                </select>
            </form>
        </div>
        <button class="btn-add" id="addBtn">
            <i class="fas fa-plus"></i> Add Cash Advance
        </button>
    </div>

    <div class="ca-table-wrap">
        <div class="ca-table-head">
            <h3><i class="fas fa-wallet"></i> Cash Advance Records</h3>
        </div>
        <?php if (empty($advances)): ?>
        <div class="empty-state">
            <i class="fas fa-wallet"></i>
            <p>No Cash Advances Found</p>
            <span>Add a new record using the button above.</span>
        </div>
        <?php else: ?>
        <table class="ca-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Amount</th>
                    <th>Date Given</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($advances as $ca):
                    $badge = [
                        'pending'   => ['color' => '#f59e0b', 'icon' => 'fa-clock',       'label' => 'Pending'],
                        'deducted'  => ['color' => '#10b981', 'icon' => 'fa-check-circle', 'label' => 'Deducted'],
                        'cancelled' => ['color' => '#ef4444', 'icon' => 'fa-times-circle', 'label' => 'Cancelled'],
                    ];
                    $b = $badge[$ca['status']] ?? $badge['pending'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ca['emp_name']) ?></strong></td>
                    <td><?= htmlspecialchars($ca['department']) ?></td>
                    <td><strong>₱<?= number_format($ca['amount'], 2) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($ca['date_given'])) ?></td>
                    <td><?= htmlspecialchars($ca['notes'] ?: '—') ?></td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:<?= $b['color'] ?>22;color:<?= $b['color'] ?>;">
                            <i class="fas <?= $b['icon'] ?>"></i> <?= $b['label'] ?>
                        </span>
                    </td>
                    <td>
                        <button class="act-btn del" title="Delete"
                            data-id="<?= $ca['id'] ?>"
                            data-name="<?= htmlspecialchars($ca['emp_name']) ?>"
                            data-amount="₱<?= number_format($ca['amount'],2) ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-plus-circle"></i> Add Cash Advance</h3>
            <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Employee <span class="req">*</span></label>
                    <select class="fc" name="employee_id" required>
                        <option value="">— Select Employee —</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> — <?= htmlspecialchars($emp['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₱) <span class="req">*</span></label>
                    <input type="number" class="fc" name="amount" min="1" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Date Given <span class="req">*</span></label>
                    <input type="date" class="fc" name="date_given" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="fc" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="modalCancel">Cancel</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
        <div class="confirm-title">Delete Record?</div>
        <div class="confirm-msg" id="confirmMsg"></div>
        <div class="confirm-btns">
            <button class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
            <form method="POST" id="deleteForm" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="ca_id" id="deleteId">
                <button type="submit" class="btn-del-confirm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </div>
    </div>
</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<script src="js/advance.js"></script>
<script>
<?php if ($message && $message_type === 'success'): ?>
const toast = document.getElementById('sra-toast');
document.getElementById('toastMsg').textContent = <?= json_encode($message) ?>;
toast.classList.add('show');
setTimeout(() => toast.classList.remove('show'), 3000);
<?php endif; ?>
</script>
</body>
</html>
