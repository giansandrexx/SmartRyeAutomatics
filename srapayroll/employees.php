<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$employees = [];
$r = $conn->query("SELECT id, employee_id, name, position, department, employment_type, daily_rate, phone, hire_date FROM employees WHERE is_active = 1 ORDER BY name");
if ($r) { while ($row = $r->fetch_assoc()) { $employees[] = $row; } }

$conn->close();

function getInitials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0],0,1) . substr($parts[count($parts)-1],0,1));
    }
    return strtoupper(substr($name,0,2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/employees.css">

</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">

    <div class="top-bar">
        <div class="top-bar-left">
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search employees...">
            </div>
            <select class="dept-select" id="deptFilter">
                <option value="">All Departments</option>
                <option value="Field">Field</option>
                <option value="Office">Office</option>
            </select>
            <div class="emp-count" id="empCount"></div>
        </div>
        <button class="btn-add" id="addEmpBtn"><i class="fas fa-plus"></i> Add Employee</button>
    </div>

    <div class="emp-grid" id="empGrid">
        <?php foreach ($employees as $emp):
            $full       = htmlspecialchars($emp['name']);
            $initials   = getInitials($emp['name']);
            $pos        = htmlspecialchars($emp['position'] ?? '—');
            $dept       = htmlspecialchars($emp['department'] ?? '—');
            $empId      = htmlspecialchars($emp['employee_id'] ?? '');
            $rate       = $emp['daily_rate'] > 0 ? '&#8369;' . number_format($emp['daily_rate'],0) . '/day' : '&#8369;0/day';
        ?>
        <div class="emp-card"
             data-name="<?= strtolower($emp['name']) ?>"
             data-dept="<?= htmlspecialchars($emp['department'] ?? '') ?>">
            <div class="emp-avatar"><?= $initials ?></div>
            <div class="emp-info">
                <div class="emp-name-row">
                    <span class="emp-name"><?= $full ?></span>
                    <?php if ($empId): ?>
                    <span class="emp-id-chip"><?= $empId ?></span>
                    <?php endif; ?>
                </div>
                <div class="emp-position"><?= $pos ?></div>
                <div class="emp-meta">
                    <span class="emp-dept"><?= $dept ?></span>
                    <span class="emp-dot">•</span>
                    <span class="emp-rate"><?= $rate ?></span>
                </div>
            </div>
            <div class="emp-actions">
                <button class="act-btn edit" title="Edit"
                    data-id="<?= $emp['id'] ?>"
                    data-empid="<?= $empId ?>"
                    data-name="<?= htmlspecialchars($emp['name']) ?>"
                    data-phone="<?= htmlspecialchars($emp['phone'] ?? '') ?>"
                    data-dept="<?= htmlspecialchars($emp['department'] ?? '') ?>"
                    data-position="<?= htmlspecialchars($emp['position'] ?? '') ?>"
                    data-emptype="<?= htmlspecialchars($emp['employment_type'] ?? 'Full Time') ?>"
                    data-rate="<?= $emp['daily_rate'] ?>"
                    data-hire="<?= htmlspecialchars($emp['hire_date'] ?? '') ?>">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="act-btn del" title="Remove"
                    data-id="<?= $emp['id'] ?>"
                    data-name="<?= $full ?>">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($employees)): ?>
        <div class="no-results"><i class="fas fa-users"></i><p>No employees found.</p></div>
        <?php endif; ?>
    </div>

</div>

<div class="modal-overlay" id="empModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Add Employee</h3>
            <button class="modal-close" id="modalCloseBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fId">
            <div class="modal-grid">
                <div class="form-group full">
                    <label>Employee ID <span>*</span></label>
                    <input type="text" class="form-input" id="fEmpId" placeholder="e.g. EMP-0001">
                </div>
                <div class="form-group full">
                    <label>Full Name <span>*</span></label>
                    <input type="text" class="form-input" id="fName" placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-input" id="fPhone" placeholder="+63 9XX XXX XXXX">
                </div>
                <div class="form-group">
                    <label>Department <span>*</span></label>
                    <select class="form-input" id="fDept">
                        <option value="">— Select —</option>
                        <option value="Field">Field</option>
                        <option value="Office">Office</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Position <span>*</span></label>
                    <input type="text" class="form-input" id="fPosition" placeholder="e.g. Engineer">
                </div>
                <div class="form-group">
                    <label>Employment Type</label>
                    <select class="form-input" id="fEmpType">
                        <option value="Full Time">Full Time</option>
                        <option value="Part Time">Part Time</option>
                        <option value="Contractual">Contractual</option>
                        <option value="Probationary">Probationary</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Daily Rate (&#8369;) <span>*</span></label>
                    <input type="number" class="form-input" id="fRate" placeholder="0.00" min="0" step="0.01">
                    <div class="annual-hint" id="annualHint"></div>
                </div>
                <div class="form-group">
                    <label>Hire Date</label>
                    <input type="date" class="form-input" id="fHire">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="cancelEmpBtn">Cancel</button>
                <button class="btn-save" id="saveEmpBtn"><i class="fas fa-save"></i> <span id="saveBtnText">Save</span></button>
            </div>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
        <div class="confirm-title">Remove Employee?</div>
        <div class="confirm-msg" id="confirmMsg"></div>
        <div class="confirm-btns">
            <button class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
            <button class="btn-del-confirm" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Remove</button>
        </div>
    </div>
</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<script src="../srapayroll/js/employee.js"></script>
</body>

</html>
