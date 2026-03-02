<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once __DIR__ . '/../config.php';

$total_employees  = 0;
$active_employees = 0;
$monthly_payroll  = 0;
$completed_runs   = 0;
$pending_drafts   = 0;
$recent_runs      = [];

$r = $conn->query("SELECT COUNT(*) as total FROM employees WHERE is_active = 1");
if ($r) { $total_employees = $r->fetch_assoc()['total']; }

$r = $conn->query("SELECT COALESCE(SUM(basic_salary),0) as total FROM employees WHERE is_active = 1");
if ($r) { $monthly_payroll = $r->fetch_assoc()['total']; }

$r = $conn->query("SHOW TABLES LIKE 'payroll_runs'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("SELECT COUNT(*) as total FROM payroll_runs WHERE status = 'completed'");
    if ($r) { $completed_runs = $r->fetch_assoc()['total']; }
    $r = $conn->query("SELECT COUNT(*) as total FROM payroll_runs WHERE status = 'draft'");
    if ($r) { $pending_drafts = $r->fetch_assoc()['total']; }
    $r = $conn->query("SELECT * FROM payroll_runs ORDER BY created_at DESC LIMIT 5");
    if ($r) { while ($row = $r->fetch_assoc()) { $recent_runs[] = $row; } }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll – Dashboard</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="srapayroll/css/dashboard.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">SRA Payroll</h1>
    </div>
    <div class="header-right">
        <div class="current-date" id="headerDate"></div>
        <div class="user-info">
            <div class="user-icon"><i class="fas fa-user"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></div>
            </div>
            <div class="user-dropdown-wrap">
                <button class="user-dropdown-toggle" id="userDropdownBtn"><i class="fas fa-chevron-down"></i></button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="../portal" class="dropdown-item"><i class="fas fa-arrow-left"></i> Back to Portal</a>
                    <div class="dropdown-divider"></div>
                    <a href="../sratool/logout" class="dropdown-item dropdown-item-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="nav-bar">
    <ul>
        <li>
            <a href="dashboard" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </li>
    </ul>
</nav>

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
                <div class="stat-sub positive">
                </div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-info">
                <div class="stat-label">Monthly Payroll</div>
                <div class="stat-value"><?php echo $monthly_payroll > 0 ? '&#8369;' . number_format($monthly_payroll, 0) : '&#8369;0'; ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-info-circle"></i>
                    Estimated from salaries
                </div>
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
            <div class="stat-info">
                <div class="stat-label">Completed Runs</div>
                <div class="stat-value"><?php echo number_format($completed_runs); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-history"></i>
                    All time
                </div>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-label">Pending Drafts</div>
                <div class="stat-value"><?php echo number_format($pending_drafts); ?></div>
                <div class="stat-sub neutral">
                    <i class="fas fa-hourglass-half"></i>
                    Awaiting processing
                </div>
            </div>
        </div>

    </div>

    <div class="quick-actions">
        <a href="payroll-run" class="qa-card">
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
                <div class="qa-desc">Salaries, deductions & benefits</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
        <a href="reports" class="qa-card">
            <div class="qa-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="qa-text">
                <div class="qa-title">Payroll Reports</div>
                <div class="qa-desc">Export and view summaries</div>
            </div>
            <i class="fas fa-chevron-right qa-arrow"></i>
        </a>
    </div>

    <div class="section-card">
        <div class="section-head">
            <h3><i class="fas fa-list-alt"></i> Recent Payroll Runs</h3>
            <a href="payroll-run" class="btn-new-run"><i class="fas fa-plus"></i> New Run</a>
        </div>

        <?php if (empty($recent_runs)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No payroll runs yet</p>
            <span>Start your first payroll run to see records here.</span>
        </div>
        <?php else: ?>
        <table class="runs-table">
            <thead>
                <tr>
                    <th>Run Name</th>
                    <th>Period</th>
                    <th>Employees</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_runs as $run): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($run['run_name'] ?? '—'); ?></strong></td>
                    <td><?php echo htmlspecialchars($run['period'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($run['employee_count'] ?? '—'); ?></td>
                    <td>&#8369;<?php echo number_format($run['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <?php
                        $status = $run['status'] ?? 'draft';
                        $badge_class = 'badge-draft'; $icon = 'fa-file-alt';
                        if ($status === 'completed') { $badge_class = 'badge-completed'; $icon = 'fa-check-circle'; }
                        elseif ($status === 'processing') { $badge_class = 'badge-processing'; $icon = 'fa-spinner'; }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><i class="fas <?php echo $icon; ?>"></i> <?php echo ucfirst($status); ?></span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($run['created_at'])); ?></td>
                    <td><a href="payroll-run?id=<?php echo (int)$run['id']; ?>" style="color:var(--blue-600);font-size:12px;font-weight:600;text-decoration:none;"><i class="fas fa-eye"></i> View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');
    btn.addEventListener('click', () => {
        btn.classList.toggle('open');
        menu.classList.toggle('open');
    });
    document.addEventListener('click', e => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            btn.classList.remove('open');
            menu.classList.remove('open');
        }
    });
</script>
</body>

</html>
