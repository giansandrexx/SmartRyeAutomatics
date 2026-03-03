<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>SRA Overtime Monitoring</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/attendance.css">
    <link rel="stylesheet" href="css/overtime.css">
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
            <div class="user-text">
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

<nav class="nav-bar">
    <ul>
        <li><a href="index" class="<?= $current_page === 'index.php' ? 'active' : '' ?>"><i class="fa fa-address-card"></i> Attendance</a></li>
        <li><a href="overtime" class="<?= $current_page === 'overtime.php' ? 'active' : '' ?>"><i class="fa fa-briefcase"></i> Overtime Monitoring</a></li>
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
        <a href="index" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fa fa-address-card"></i> Attendance
        </a>
        <a href="overtime" class="<?= $current_page === 'overtime.php' ? 'active' : '' ?>">
            <i class="fa fa-briefcase"></i> Overtime Monitoring
        </a>
    </div>
    <div class="mobile-drawer-footer">
        <a href="../portal"><i class="fas fa-arrow-left"></i> Back to Portal</a>
        <a href="../sratool/logout" class="mobile-drawer-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="page-layout">

    <div class="ot-page-header">
        <div>
            <h2 class="ot-page-title"><i class="fas fa-route"></i> Overtime Trip Monitoring</h2>
            <p class="ot-page-sub">Track employee departure &amp; arrival times for field overtime eligibility</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-export-ot" id="exportOtBtn"><i class="fas fa-file-excel"></i> Export</button>
            <button class="btn-add-trip" id="addTripBtn"><i class="fas fa-plus"></i> Add Trip Entry</button>
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
                <input type="text" class="search-input" id="searchInput" placeholder="Search employee…">
            </div>
        </div>
    </div>

    <div id="loadingBar" class="loading-bar" style="display:none"><div class="loading-inner"></div></div>
    <div id="otContainer"></div>
</div>

<div class="modal-overlay" id="tripModal">
    <div class="modal-box modal-box-lg">
        <div class="modal-head">
            <h3 id="tripModalTitle"><i class="fas fa-route" style="margin-right:8px"></i>Add Trip Entry</h3>
            <button class="modal-close" id="tripModalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user" style="margin-right:5px;color:var(--blue-500)"></i>Employee *</label>
                    <select class="form-input" id="tEmp"></select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar" style="margin-right:5px;color:var(--blue-500)"></i>Date *</label>
                    <input type="date" class="form-input" id="tDate">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt" style="margin-right:5px;color:#ef4444"></i>Location / Destination *</label>
                <input type="text" class="form-input" id="tLocation" placeholder="e.g. Batangas Site, SM Sta. Rosa, Tagaytay…">
            </div>
            <div class="trip-times-grid">
                <div class="trip-time-card depart-card">
                    <div class="ttc-icon"><i class="fas fa-building"></i></div>
                    <div class="ttc-label">Departure from Office</div>
                    <input type="time" class="form-input ttc-input" id="tDepartOffice">
                </div>
                <div class="trip-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="trip-time-card arrive-card">
                    <div class="ttc-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="ttc-label">Arrival at Site</div>
                    <input type="time" class="form-input ttc-input" id="tArriveSite">
                </div>
                <div class="trip-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="trip-time-card depart-site-card">
                    <div class="ttc-icon"><i class="fas fa-flag-checkered"></i></div>
                    <div class="ttc-label">Departure from Site</div>
                    <input type="time" class="form-input ttc-input" id="tDepartSite">
                </div>
                <div class="trip-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="trip-time-card arrive-back-card">
                    <div class="ttc-icon"><i class="fas fa-home"></i></div>
                    <div class="ttc-label">Arrival back at Office</div>
                    <input type="time" class="form-input ttc-input" id="tArriveOffice">
                </div>
            </div>
            <div class="form-group" style="margin-top:18px">
                <label><i class="fas fa-gavel" style="margin-right:5px;color:var(--blue-500)"></i>OT Eligibility</label>
                <div class="eligibility-toggle" id="eligibilityToggle">
                    <button type="button" class="elig-btn" data-val="" id="eligPending"><i class="fas fa-clock"></i> Pending</button>
                    <button type="button" class="elig-btn elig-yes" data-val="1" id="eligYes"><i class="fas fa-check-circle"></i> Eligible</button>
                    <button type="button" class="elig-btn elig-no" data-val="0" id="eligNo"><i class="fas fa-times-circle"></i> Not Eligible</button>
                </div>
            </div>
            <div class="ot-hours-panel" id="otHoursPanel">
                <div class="ot-hours-label"><i class="fas fa-stopwatch"></i> Overtime Duration</div>
                <div class="ot-hours-inputs">
                    <div class="ot-unit-wrap">
                        <input type="number" class="form-input ot-unit-inp" id="tOtHours" min="0" max="24" value="0" placeholder="0">
                        <span class="ot-unit-label">Hours</span>
                    </div>
                    <div class="ot-sep">:</div>
                    <div class="ot-unit-wrap">
                        <input type="number" class="form-input ot-unit-inp" id="tOtMinutes" min="0" max="59" value="0" placeholder="0">
                        <span class="ot-unit-label">Minutes</span>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label><i class="fas fa-sticky-note" style="margin-right:5px;color:var(--text-muted)"></i>Notes (optional)</label>
                <input type="text" class="form-input" id="tNotes" placeholder="Additional remarks…">
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="tripCancelBtn">Cancel</button>
                <button class="btn-save" id="tripSaveBtn"><i class="fas fa-save"></i> Save Trip</button>
            </div>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
        <div class="confirm-title">Delete Trip?</div>
        <div class="confirm-msg" id="confirmMsg">This will permanently remove this trip entry.</div>
        <div class="confirm-btns">
            <button class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
            <button class="btn-del-confirm" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<script src="js/overtime.js"></script>

</body>
</html>