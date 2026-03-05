<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$filter_emp    = (int)($_GET['emp'] ?? 0);
$filter_period = $conn->real_escape_string($_GET['period'] ?? '');

$employees = [];
$r = $conn->query("SELECT id, name, department FROM employees WHERE is_active=1 ORDER BY name");
if ($r) { while ($row = $r->fetch_assoc()) { $employees[] = $row; } }

$where = '1';
if ($filter_emp)    $where .= " AND pe.employee_id = $filter_emp";
if ($filter_period) $where .= " AND DATE_FORMAT(pe.date_from,'%Y-%m') = '$filter_period'";

$payslips = [];
$r = $conn->query("SHOW TABLES LIKE 'payroll_entries'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("
        SELECT pe.*, e.name as emp_name, e.department, e.position
        FROM payroll_entries pe
        JOIN employees e ON e.id = pe.employee_id
        WHERE $where
        ORDER BY pe.date_from DESC, pe.created_at DESC
    ");
    if ($r) { while ($row = $r->fetch_assoc()) { $payslips[] = $row; } }
}

$periods = [];
$r2 = $conn->query("SHOW TABLES LIKE 'payroll_entries'");
if ($r2 && $r2->num_rows > 0) {
    $r2 = $conn->query("SELECT DISTINCT DATE_FORMAT(date_from,'%Y-%m') as period, DATE_FORMAT(date_from,'%M %Y') as label FROM payroll_entries ORDER BY period DESC");
    if ($r2) { while ($row = $r2->fetch_assoc()) { $periods[] = $row; } }
}

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
    <link rel="stylesheet" href="../srapayroll/css/process.css">
    <link rel="stylesheet" href="../srapayroll/css/payslips.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">

    <div class="page-header">
        <h2>Payslips</h2>
        <p>View and export employee payslips from processed payroll entries.</p>
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
                <select class="filter-select" name="period" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Periods</option>
                    <?php foreach ($periods as $p): ?>
                    <option value="<?= $p['period'] ?>" <?= $filter_period===$p['period']?'selected':'' ?>>
                        <?= htmlspecialchars($p['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="results-count">
            <i class="fas fa-file-invoice-dollar"></i>
            <?= count($payslips) ?> payslip<?= count($payslips)!=1?'s':'' ?> found
        </div>
    </div>

    <?php if (empty($payslips)): ?>
    <div class="table-wrap">
        <div class="table-head-bar">
            <h3><i class="fas fa-file-invoice-dollar"></i> Payslip Records</h3>
        </div>
        <div class="empty-state">
            <i class="fas fa-file-invoice-dollar"></i>
            <p>No payslips found</p>
            <span>Process a payroll run to generate payslips.</span>
        </div>
    </div>
    <?php else: ?>
    <div class="payslip-grid">
        <?php foreach ($payslips as $ps): ?>
        <div class="payslip-card">
            <div class="payslip-card-head">
                <div class="emp-info">
                    <div class="emp-name"><?= htmlspecialchars($ps['emp_name']) ?></div>
                    <div class="emp-dept"><?= htmlspecialchars($ps['department'] ?? '—') ?><?= !empty($ps['position']) ? ' · ' . htmlspecialchars($ps['position']) : '' ?></div>
                </div>
                <div class="period-badge">
                    <?= date('M d', strtotime($ps['date_from'])) ?> – <?= date('M d, Y', strtotime($ps['date_to'])) ?>
                </div>
            </div>
            <div class="payslip-card-body">
                <div class="ps-row"><span class="lbl">Daily Rate</span><span class="val">₱<?= number_format($ps['daily_rate'],2) ?></span></div>
                <div class="ps-row"><span class="lbl">Days Worked</span><span class="val"><?= $ps['days_worked'] ?> days</span></div>
                <div class="ps-row"><span class="lbl">Basic Pay</span><span class="val pos">₱<?= number_format($ps['basic_pay'],2) ?></span></div>
                <?php if ($ps['ot_pay'] > 0): ?>
                <div class="ps-row"><span class="lbl">Overtime Pay</span><span class="val pos">₱<?= number_format($ps['ot_pay'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['absent_deduction'] > 0): ?>
                <div class="ps-row"><span class="lbl">Absent Deduction</span><span class="val neg">– ₱<?= number_format($ps['absent_deduction'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['late_deduction'] > 0): ?>
                <div class="ps-row"><span class="lbl">Late Deduction</span><span class="val neg">– ₱<?= number_format($ps['late_deduction'],2) ?></span></div>
                <?php endif; ?>
                <div class="ps-gross">
                    <span class="lbl">Gross Pay</span>
                    <span class="val">₱<?= number_format($ps['gross_pay'],2) ?></span>
                </div>
                <?php if ($ps['sss'] > 0): ?>
                <div class="ps-row"><span class="lbl">SSS</span><span class="val neg">– ₱<?= number_format($ps['sss'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['philhealth'] > 0): ?>
                <div class="ps-row"><span class="lbl">PhilHealth</span><span class="val neg">– ₱<?= number_format($ps['philhealth'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['pagibig'] > 0): ?>
                <div class="ps-row"><span class="lbl">Pag-IBIG</span><span class="val neg">– ₱<?= number_format($ps['pagibig'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['cash_advance'] > 0): ?>
                <div class="ps-row"><span class="lbl">Cash Advance</span><span class="val neg">– ₱<?= number_format($ps['cash_advance'],2) ?></span></div>
                <?php endif; ?>
                <?php if ($ps['other_deductions'] > 0): ?>
                <div class="ps-row"><span class="lbl">Other Deductions</span><span class="val neg">– ₱<?= number_format($ps['other_deductions'],2) ?></span></div>
                <?php endif; ?>
                <div class="ps-net">
                    <span class="lbl">NET PAY</span>
                    <span class="val">₱<?= number_format($ps['net_pay'],2) ?></span>
                </div>
            </div>
            <div class="payslip-card-foot">
                <div class="foot-date">
                    <i class="fas fa-calendar-check"></i>
                    <?= date('M d, Y', strtotime($ps['created_at'])) ?>
                </div>
                <button class="btn-print" onclick="openPrint(<?= htmlspecialchars(json_encode($ps)) ?>)">
                    <i class="fas fa-print"></i> Export
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<div class="print-overlay" id="printOverlay">
    <div class="print-box">
        <div class="print-modal-head">
            <h3><i class="fas fa-print"></i> Export Payslip</h3>
            <button class="modal-close" onclick="closePrint()"><i class="fas fa-times"></i></button>
        </div>
        <div class="print-modal-body">
            <div id="printArea"></div>
        </div>
        <div class="print-actions">
            <button class="btn-cancel" onclick="closePrint()">Cancel</button>
            <button class="btn-do-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print / Save PDF
            </button>
        </div>
    </div>
</div>

<script src="../srapayroll/js/payslips.js"></script>
</body>
</html>