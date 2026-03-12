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
    <title>Employee Information</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/employee.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">Employee Information</h1>
    </div>
    <div class="header-right">
        <div class="current-date" id="headerDate"></div>
        <div class="user-info">
            <div class="user-icon"><i class="fas fa-user"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?></div>
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

<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="nav-bar">
    <ul>
        <li><a href="index" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Employee Info</a></li>
    </ul>
</nav>

<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <button class="mobile-drawer-close" id="mobileDrawerClose"><i class="fas fa-times"></i></button>
    <div class="mobile-drawer-header">
        <div class="mobile-drawer-user-icon"><i class="fas fa-user"></i></div>
        <div>
            <div class="mobile-drawer-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></div>
            <div class="mobile-drawer-user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?></div>
        </div>
    </div>
    <div class="mobile-drawer-links">
        <a href="index" class="active"><i class="fas fa-id-card"></i> Employee Info</a>
    </div>
    <div class="mobile-drawer-footer">
        <a href="../portal"><i class="fas fa-arrow-left"></i> Back to Portal</a>
        <a href="../sratool/logout" class="mobile-drawer-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="page-layout">

    <div class="page-header">
        <div>
            <h2 class="page-title-text"><i class="fas fa-id-card"></i> Employee Information</h2>
            <p class="page-sub">Manage employee records, inactive staff &amp; contracts</p>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">Active Employees</div>
                <div class="stat-value" id="statActive">0</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-user-slash"></i></div>
            <div>
                <div class="stat-label">Unemployed Records</div>
                <div class="stat-value" id="statInactive">0</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-file-contract"></i></div>
            <div>
                <div class="stat-label">Contracts</div>
                <div class="stat-value" id="statContract">0</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="stat-label">Expiring Contracts</div>
                <div class="stat-value" id="statExpiring">0</div>
            </div>
        </div>
    </div>

    <div id="nbiAlertBanner" class="nbi-alert-banner" style="display:none">
    <div class="nbi-alert-inner">
        <div class="nbi-alert-icon"><i class="fas fa-id-card"></i></div>
        <div class="nbi-alert-content">
            <div class="nbi-alert-title">NBI Validity Alert</div>
            <div class="nbi-alert-list" id="nbiAlertList"></div>
        </div>
        <button class="nbi-alert-close" onclick="document.getElementById('nbiAlertBanner').style.display='none'"><i class="fas fa-times"></i></button>
    </div>
</div>

    <div class="tab-bar">
        <button class="tab-btn active" data-tab="employees">
            <i class="fas fa-users"></i> Employees
            <span class="tab-count" id="countEmp">0</span>
        </button>
        <button class="tab-btn" data-tab="unemployed">
            <i class="fas fa-user-slash"></i> Unemployed
            <span class="tab-count" id="countUnemp">0</span>
        </button>
        <button class="tab-btn" data-tab="contracts">
            <i class="fas fa-file-contract"></i> Contracts
            <span class="tab-count" id="countContract">0</span>
        </button>
    </div>

    <div class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Search by name, position…">
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn-add" id="addBtn" onclick="openEmpModal(null)"><i class="fas fa-plus"></i> Add Employee</button>
            <button class="btn-add" id="addUnempBtn" style="display:none" onclick="openUnempModal(null)"><i class="fas fa-plus"></i> Add Record</button>
            <button class="btn-add" id="addContractBtn" style="display:none" onclick="openContractModal(null)"><i class="fas fa-plus"></i> Add Contract</button>
        </div>
    </div>

    <div id="loadingBar" class="loading-bar"><div class="loading-inner"></div></div>

<div class="tab-panel" data-panel="employees">
    <div id="empCardGrid" class="emp-card-grid"></div>
    <div id="empPagination" class="emp-pagination"></div>
</div>

    <div class="tab-panel" data-panel="unemployed" style="display:none">
        <div class="table-wrap">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Phone</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="unempTbody"></tbody>
            </table>
        </div>
    </div>

    <div class="tab-panel" data-panel="contracts" style="display:none">
        <div class="table-wrap">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contractTbody"></tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal-overlay" id="empModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-id-card"></i> <span id="empModalTitle">Add Employee</span></h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="empId">

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-user"></i> Basic Information</div>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>Employee No *</label>
                        <input type="text" class="form-input" id="fEmpNo" placeholder="e.g. EMP-0001">
                    </div>
                    <div class="form-group">
                        <label>Starting Date</label>
                        <input type="date" class="form-input" id="fHireDate">
                    </div>
                    <div class="form-group full">
                        <label>Full Name *</label>
                        <input type="text" class="form-input" id="fName" placeholder="e.g. Juan Dela Cruz">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" class="form-input" id="fPosition" placeholder="e.g. Engineer">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select class="form-input" id="fDept">
                            <option value="Field">Field</option>
                            <option value="Office">Office</option>
                        </select>
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
                        <label>Status</label>
                        <select class="form-input" id="fStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select class="form-input" id="fSex">
                            <option value="">— Select —</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" class="form-input" id="fDob">
                    </div>
                    <div class="form-group">
                        <label>Phone / Telephone</label>
                        <input type="text" class="form-input" id="fPhone" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="form-group full">
                        <label>Address</label>
                        <input type="text" class="form-input" id="fAddress" placeholder="Full address">
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-id-badge"></i> Government IDs</div>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>SSS No.</label>
                        <input type="text" class="form-input" id="fSSS" placeholder="XX-XXXXXXX-X">
                    </div>
                    <div class="form-group">
                        <label>PhilHealth No.</label>
                        <input type="text" class="form-input" id="fPhilhealth" placeholder="XX-XXXXXXXXX-X">
                    </div>
                    <div class="form-group">
                        <label>HDMF / Pag-ibig <span class="optional">(optional)</span></label>
                        <input type="text" class="form-input" id="fHDMF" placeholder="XXXX-XXXX-XXXX">
                    </div>
                    <div class="form-group">
                        <label>TIN <span class="optional">(optional)</span></label>
                        <input type="text" class="form-input" id="fTIN" placeholder="XXX-XXX-XXX">
                    </div>
                    <div class="form-group">
                        <label>NBI Validity</label>
                        <input type="date" class="form-input" id="fNBI">
                    </div>
                    <div class="form-group">
                        <label>Driver's License <span class="optional">(optional)</span></label>
                        <input type="text" class="form-input" id="fDriverLicense" placeholder="License No.">
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-phone-alt"></i> Emergency Contact Person</div>
                <div class="modal-grid">
                    <div class="form-group">
                        <label>Contact Name</label>
                        <input type="text" class="form-input" id="fCPName" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" class="form-input" id="fCPNumber" placeholder="+63 9XX XXX XXXX">
                    </div>
                    <div class="form-group full">
                        <label>Contact Address</label>
                        <input type="text" class="form-input" id="fCPAddress" placeholder="Address">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('empModal')">Cancel</button>
            <button class="btn-save" id="saveEmpBtn"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="unempModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-user-slash"></i> <span id="unempModalTitle">Add Unemployed Record</span></h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="unempId">
            <div class="modal-grid">
                <div class="form-group">
                    <label>Employee No</label>
                    <input type="text" class="form-input" id="uEmpNo" placeholder="e.g. EMP-0001">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-input" id="uStatus">
                        <option value="Resigned">Resigned</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Retired">Retired</option>
                        <option value="AWOL">AWOL</option>
                        <option value="End of Contract">End of Contract</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Full Name *</label>
                    <input type="text" class="form-input" id="uName" placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" class="form-input" id="uPosition" placeholder="Last position held">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-input" id="uPhone" placeholder="+63 9XX XXX XXXX">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" class="form-input" id="uDob">
                </div>
                <div class="form-group">
                    <label>Starting Date</label>
                    <input type="date" class="form-input" id="uStartDate">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" class="form-input" id="uEndDate">
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <input type="text" class="form-input" id="uAddress" placeholder="Full address">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('unempModal')">Cancel</button>
            <button class="btn-save" id="saveUnempBtn"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="contractModal">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-head">
            <h3><i class="fas fa-file-contract"></i> <span id="contractModalTitle">Add Contract</span></h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="contractId">
            <div class="modal-grid">
                <div class="form-group full">
                    <label>Full Name *</label>
                    <input type="text" class="form-input" id="cName" placeholder="Employee name">
                </div>
                <div class="form-group full">
                    <label>Position</label>
                    <input type="text" class="form-input" id="cPosition" placeholder="Position / Role">
                </div>
                <div class="form-group">
                    <label>Start Date of Contract</label>
                    <input type="date" class="form-input" id="cStartDate">
                </div>
                <div class="form-group">
                    <label>End Date of Contract</label>
                    <input type="date" class="form-input" id="cEndDate">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('contractModal')">Cancel</button>
            <button class="btn-save" id="saveContractBtn"><i class="fas fa-save"></i> Save</button>
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
            <button class="btn-del-confirm" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toast-msg"></span></div>

<div class="modal-overlay" id="viewModal">
    <div class="modal-box vm-box">
        <div class="modal-head">
            <h3><i class="fas fa-id-card"></i> Employee Details</h3>
            <button class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="viewModalContent"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeViewModal()">Close</button>
            <button class="btn-save" id="viewModalEditBtn"><i class="fas fa-edit"></i> Edit</button>
        </div>
    </div>
</div>

<script src="js/employee.js"></script>
</body>
</html>
