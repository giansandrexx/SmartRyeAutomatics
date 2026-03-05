<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/attendance.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <button class="mobile-drawer-close" id="mobileDrawerClose"><i class="fas fa-times"></i></button>
    <div class="mobile-drawer-header">
        <div class="mobile-drawer-user-icon"><i class="fas fa-user"></i></div>
        <div>
            <div class="mobile-drawer-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
            <div class="mobile-drawer-user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></div>
        </div>
    </div>
    <div class="mobile-drawer-links">
        <a href="dashboard"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="employees"><i class="fas fa-users"></i> Employees</a>
        <a href="payroll"><i class="fas fa-money-bill-wave"></i> Payroll</a>
        <a href="attendance" class="active"><i class="fas fa-address-card"></i> Attendance</a>
        <a href="reports"><i class="fas fa-file-alt"></i> Reports</a>
    </div>
    <div class="mobile-drawer-footer">
        <a href="../portal"><i class="fas fa-arrow-left"></i> Back to Portal</a>
        <a href="../sratool/logout" class="mobile-drawer-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="page-layout">

    <div class="av-page-header">
        <div>
            <h2 class="av-page-title"></i> Attendance</h2>
            <p class="av-page-sub">Monthly view of employee attendance records</p>
        </div>
    </div>

    <div class="av-controls">
        <div class="av-month-nav">
            <button class="av-nav-btn" id="prevMonthBtn"><i class="fas fa-chevron-left"></i></button>
            <div class="av-month-label" id="monthLabel"></div>
            <button class="av-nav-btn" id="nextMonthBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="av-controls-right">
            <div class="av-dept-tabs">
                <button class="av-dept-tab active" data-dept="">All</button>
                <button class="av-dept-tab" data-dept="Field"><i class="fas fa-hard-hat"></i> Field</button>
                <button class="av-dept-tab" data-dept="Office"><i class="fas fa-building"></i> Office</button>
            </div>
            <div class="av-search-wrap">
                <i class="fas fa-search av-search-icon"></i>
                <input type="text" class="av-search-input" id="searchInput" placeholder="Search employee…">
            </div>
        </div>
    </div>

    <div id="loadingBar" class="av-loading-bar" style="display:none">
        <div class="av-loading-inner"></div>
    </div>

    <div class="av-grid-wrap">
        <div class="av-table-container">
            <table class="av-table" id="attTable">
                <thead id="attHead"></thead>
                <tbody id="attBody"></tbody>
            </table>
        </div>
    </div>

    <div class="av-legend">
        <div class="av-legend-item"><span class="av-badge present"></span> Present</div>
        <div class="av-legend-item"><span class="av-badge absent"></span> Absent</div>
        <div class="av-legend-item"><span class="av-badge late"></span> Late</div>
        <div class="av-legend-item"><span class="av-badge undertime"></span> Undertime</div>
        <div class="av-legend-item"><span class="av-badge weekend"></span> Weekend</div>
        <div class="av-legend-item"><span class="av-dot-legend"></span> = no record</div>
    </div>

</div>

<div class="av-popover" id="avPopover">
    <div class="av-pop-arrow"></div>
    <div class="av-pop-name" id="popName"></div>
    <div class="av-pop-date" id="popDate"></div>
    <div class="av-pop-rows" id="popRows"></div>
</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<script src="js/attendance.js"></script>
</body>

</html>
