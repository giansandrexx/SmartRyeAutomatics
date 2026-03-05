<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$total_employees   = 0;
$monthly_payroll   = 0;
$completed_runs    = 0;
$total_deductions  = 0;
$pending_ca_amount = 0;
$recent_runs       = [];

$r = $conn->query("SELECT COUNT(*) as total FROM employees WHERE is_active = 1");
if ($r) { $total_employees = $r->fetch_assoc()['total']; }

$r = $conn->query("SHOW TABLES LIKE 'payroll_entries'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("SELECT COALESCE(SUM(net_pay),0) as total FROM payroll_entries");
    if ($r) { $monthly_payroll = $r->fetch_assoc()['total']; }

    $r = $conn->query("SELECT COUNT(*) as total FROM payroll_entries");
    if ($r) { $completed_runs = $r->fetch_assoc()['total']; }

    $r = $conn->query("SELECT COALESCE(SUM(total_deductions),0) as total FROM payroll_entries WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
    if ($r) { $total_deductions = $r->fetch_assoc()['total']; }

    $r = $conn->query("
        SELECT pe.*, e.name as emp_name, e.department
        FROM payroll_entries pe
        JOIN employees e ON e.id = pe.employee_id
        ORDER BY pe.created_at DESC
        LIMIT 5
    ");
    if ($r) { while ($row = $r->fetch_assoc()) { $recent_runs[] = $row; } }
}

$r = $conn->query("SHOW TABLES LIKE 'cash_advances'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM cash_advances WHERE status = 'pending'");
    if ($r) { $pending_ca_amount = $r->fetch_assoc()['total']; }
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
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">

    <div class="page-header">
        <h2>Payroll Dashboard</h2>
        <p>Overview of your payroll operations and workforce summary.</p>
    </div>

    <div class="stats-grid">

        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Employees</div>
                <div class="stat-value"><?php echo number_format($total_employees); ?></div>
                <div class="stat-sub positive"></div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Net Payroll</div>
                <div class="stat-value">&#8369;<?php echo number_format($monthly_payroll, 0); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-info-circle"></i>
                    Sum of all net pays
                </div>
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-info">
                <div class="stat-label">Payroll Entries</div>
                <div class="stat-value"><?php echo number_format($completed_runs); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-history"></i>
                    All time
                </div>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-minus-circle"></i></div>
            <div class="stat-info">
                <div class="stat-label">Deductions This Month</div>
                <div class="stat-value">&#8369;<?php echo number_format($total_deductions, 0); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('F Y'); ?>
                </div>
            </div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending Cash Advances</div>
                <div class="stat-value">&#8369;<?php echo number_format($pending_ca_amount, 0); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-exclamation-circle"></i>
                    Undeducted advances
                </div>
            </div>
        </div>

    </div>

    <div class="quick-actions">
        <a href="process_payroll" class="qa-card">
            <div class="qa-icon"><i class="fas fa-play-circle"></i></div>
            <div class="qa-text">
                <div class="qa-title">New Payroll Run</div>
                <div class="qa-desc">Process payroll for a period</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
        <a href="employees" class="qa-card">
            <div class="qa-icon"><i class="fas fa-user-cog"></i></div>
            <div class="qa-text">
                <div class="qa-title">Manage Employees</div>
                <div class="qa-desc">Contact, manage and add employee</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
        <a href="payslips" class="qa-card">
            <div class="qa-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="qa-text">
                <div class="qa-title">Payslips</div>
                <div class="qa-desc">Export and view payslips</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
        <a href="cash_advance" class="qa-card">
            <div class="qa-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="qa-text">
                <div class="qa-title">Cash Advances</div>
                <div class="qa-desc">Track and manage advances</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
    </div>

    <div class="section-card">
        <div class="section-head">
            <h3><i class="fas fa-list-alt"></i> Recent Payroll Entries</h3>
            <a href="process_payroll" class="btn-new-run"><i class="fas fa-plus"></i> New Entry</a>
        </div>

        <?php if (empty($recent_runs)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No Payroll Entries Yet</p>
            <span>Process your first payroll entry to see records here.</span>
        </div>
        <?php else: ?>
        <table class="runs-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Period</th>
                    <th>Days Worked</th>
                    <th>Gross Pay</th>
                    <th>Net Pay</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_runs as $run): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($run['emp_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($run['department'] ?? '—'); ?></td>
                    <td><?php echo date('M d', strtotime($run['date_from'])) . ' – ' . date('M d, Y', strtotime($run['date_to'])); ?></td>
                    <td><?php echo $run['days_worked']; ?> days</td>
                    <td>&#8369;<?php echo number_format($run['gross_pay'], 2); ?></td>
                    <td><strong style="color:var(--green-500);">&#8369;<?php echo number_format($run['net_pay'], 2); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($run['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
<script src="../srapayroll/js/dashboard.js"></script>
</body>

</html>
