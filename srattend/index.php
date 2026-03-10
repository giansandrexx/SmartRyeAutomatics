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
    <title>SRA Attendance</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/attendance.css">
    <link rel="stylesheet" href="../sratool/css/dashboard.css">
    <link rel="stylesheet" href="../sratool/css/base.css">
    <link rel="stylesheet" href="../sratool/css/portal.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">SRA Attendance</h1>
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
        <button class="mobile-hamburger-btn" id="mobileHamburgerBtn">
            <span></span><span></span><span></span>
        </button>
    </div>
</div>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>

<nav class="nav-bar">
    <ul>
        <li>
            <a href="index" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fa fa-address-card"></i> Attendance
            </a>
        </li>
        <li>
            <a href="overtime" class="<?= ($current_page == 'overtime.php') ? 'active' : '' ?>">
                <i class="fa fa-briefcase"></i> Overtime Monitoring
            </a>
        </li>
    </ul>
</nav>

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
        <a href="index" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
            <i class="fa fa-address-card"></i> Attendance
        </a>
        <a href="overtime" class="<?= ($current_page == 'overtime.php') ? 'active' : '' ?>">
            <i class="fa fa-briefcase"></i> Overtime Monitoring
        </a>
    </div>
    <div class="mobile-drawer-footer">
        <a href="../portal"><i class="fas fa-arrow-left"></i> Back to Portal</a>
        <a href="../sratool/logout" class="mobile-drawer-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="page-layout">

    <div class="att-page-header">
        <div>
            <h2 class="att-page-title"><i class="fas fa-address-card"></i> Attendance Monitoring</h2>
            <p class="att-page-sub">Track employee daily time-in &amp; time-out records</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-export" id="exportBtn"><i class="fas fa-file-excel"></i> Export</button>
        </div>
    </div>

    <div class="top-bar">
        <div class="week-nav">
            <button class="nav-btn" id="prevWeekBtn"><i class="fas fa-chevron-left"></i></button>
            <div class="week-label" id="weekLabel"></div>
            <button class="nav-btn" id="nextWeekBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="top-bar-right">
            <div class="dept-tabs">
                <button class="dept-tab active" data-dept="">All</button>
                <button class="dept-tab" data-dept="Field"><i class="fas fa-hard-hat"></i> Field</button>
                <button class="dept-tab" data-dept="Office"><i class="fas fa-building"></i> Office</button>
            </div>
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search by name…">
            </div>
        </div>
    </div>

    <div id="loadingBar" class="loading-bar" style="display:none">
        <div class="loading-inner"></div>
    </div>

    <div id="empContainer"></div>
</div>

<div class="modal-overlay" id="empModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Add Employee</h3>
            <button class="modal-close" id="modalCloseBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div class="form-group full">
                    <label>Employee ID *</label>
                    <input type="text" class="form-input" id="fEmpId" placeholder="e.g. EMP-0001">
                </div>
                <div class="form-group full">
                    <label>Full Name *</label>
                    <input type="text" class="form-input" id="fName" placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group edit-hidden">
                    <label>Phone</label>
                    <input type="text" class="form-input" id="fPhone" placeholder="+63 9XX XXX XXXX">
                </div>
                <div class="form-group edit-hidden">
                    <label>Department *</label>
                    <select class="form-input" id="fDept">
                        <option value="">— Select —</option>
                        <option value="Field">Field</option>
                        <option value="Office">Office</option>
                    </select>
                </div>
                <div class="form-group edit-hidden">
                    <label>Position *</label>
                    <input type="text" class="form-input" id="fPosition" placeholder="e.g. Engineer">
                </div>
                <div class="form-group edit-hidden">
                    <label>Employment Type</label>
                    <select class="form-input" id="fEmpType">
                        <option value="Full Time">Full Time</option>
                        <option value="Part Time">Part Time</option>
                        <option value="Contractual">Contractual</option>
                        <option value="Probationary">Probationary</option>
                    </select>
                </div>
                <div class="form-group edit-hidden">
                    <label>Hire Date</label>
                    <input type="date" class="form-input" id="fHireDate">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="cancelEmpBtn">Cancel</button>
                <button class="btn-save" id="saveEmpBtn"><i class="fas fa-save"></i> Save</button>
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

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<script src="js/attendance.js"></script>
</body>
</html>
