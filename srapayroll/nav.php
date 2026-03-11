
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
<?php $cp = basename($_SERVER['PHP_SELF']); ?>
<nav class="nav-bar">
<ul>
    <li><a href="dashboard"       class="<?= $cp=='dashboard.php'?'active':'' ?>"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
    <li><a href="employees"       class="<?= $cp=='employees.php'?'active':'' ?>"><i class="fas fa-users"></i> Employees</a></li>
    <li><a href="process_payroll" class="<?= $cp=='process_payroll.php'?'active':'' ?>"><i class="fas fa-money-check-alt"></i> Process Payroll</a></li>
    <li><a href="attendance"      class="<?= $cp=='attendance.php'?'active':'' ?>"><i class="fas fa-calendar-check"></i> View Attendance</a></li>
    <li><a href="cash_advance"    class="<?= $cp=='cash_advance.php'?'active':'' ?>"><i class="fas fa-money-bill-wave"></i> Cash Advance</a></li>
    <li><a href="payslips"        class="<?= $cp=='payslips.php'?'active':'' ?>"><i class="fas fa-file-invoice-dollar"></i> Payslips</a></li>
</ul>
</nav>
