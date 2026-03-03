<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$employees = [];
$r = $conn->query("SELECT id, name, position, department, employment_type, daily_rate, phone, hire_date FROM employees WHERE is_active = 1 ORDER BY name");
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
    <title>SRA Payroll – Employees</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

        :root {
            --blue-900:#0d2f6e;--blue-800:#1245a8;--blue-600:#1a6ed8;
            --blue-500:#2196F3;--blue-400:#42a5f5;--blue-200:#bbdefb;
            --blue-100:#e3f2fd;--blue-50:#f0f7ff;
            --surface:#ffffff;--surface-2:#f6f9ff;--surface-3:#eef4ff;
            --border:#dce8fa;--border-light:#edf3fd;
            --text-primary:#0f1f3d;--text-secondary:#4a607d;--text-muted:#8fa3be;
            --green-700:#1b5e20;--green-500:#2e7d32;--green-100:#e8f5e9;
            --red-700:#c62828;--red-100:#fdecea;
            --amber-600:#d97706;--amber-100:#fef3c7;
            --shadow-sm:0 1px 3px rgba(13,47,110,0.08);
            --shadow-md:0 4px 16px rgba(13,47,110,0.10);
            --shadow-lg:0 12px 40px rgba(13,47,110,0.15);
            --shadow-glow:0 0 0 3px rgba(33,150,243,0.18);
            --radius-sm:6px;--radius-md:10px;--radius-lg:16px;--radius-xl:22px;
            --ease:cubic-bezier(0.4,0,0.2,1);
            --ease-spring:cubic-bezier(0.34,1.56,0.64,1);
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'DM Sans',sans-serif;background:var(--surface-2);min-height:100vh;color:var(--text-primary);line-height:1.5;}

        .top-header{background:linear-gradient(135deg,var(--blue-900) 0%,var(--blue-800) 50%,var(--blue-600) 100%);padding:0 32px;height:64px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:200;box-shadow:0 4px 20px rgba(13,47,110,0.35);}
        .top-header::after{content:'';position:absolute;inset:0;pointer-events:none;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
        .logo-section{display:flex;align-items:center;gap:14px;position:relative;z-index:1;}
        .logo-img{height:38px;filter:brightness(0) invert(1) drop-shadow(0 2px 4px rgba(0,0,0,0.3));}
        .system-title{color:white;font-size:18px;font-weight:600;margin:0;}
        .header-right{display:flex;align-items:center;gap:20px;position:relative;z-index:1;}
        .current-date{color:rgba(255,255,255,0.75);font-size:13px;}
        .user-info{display:flex;align-items:center;gap:10px;color:white;position:relative;}
        .user-icon{background:rgba(255,255,255,0.2);width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;border:1.5px solid rgba(255,255,255,0.3);}
        .user-name{font-size:14px;font-weight:500;}
        .user-role{font-size:11px;color:rgba(255,255,255,0.65);text-transform:uppercase;letter-spacing:0.05em;}
        .user-dropdown-wrap{position:relative;}
        .user-dropdown-toggle{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);cursor:pointer;color:white;font-size:11px;padding:5px 8px;border-radius:var(--radius-sm);transition:background 0.2s;line-height:1;}
        .user-dropdown-toggle:hover{background:rgba(255,255,255,0.25);}
        .user-dropdown-toggle i{transition:transform 0.25s var(--ease);}
        .user-dropdown-toggle.open i{transform:rotate(180deg);}
        .user-dropdown-menu{display:none;position:absolute;top:calc(100% + 12px);right:0;background:var(--surface);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);min-width:200px;z-index:9999;overflow:hidden;border:1px solid var(--border);}
        .user-dropdown-menu.open{display:block;animation:dropIn 0.2s var(--ease);}
        @keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(0.97);}to{opacity:1;transform:translateY(0) scale(1);}}
        .dropdown-item{display:flex;align-items:center;gap:10px;padding:11px 16px;font-size:13.5px;color:var(--text-secondary);text-decoration:none;transition:background 0.15s;}
        .dropdown-item:hover{background:var(--blue-50);color:var(--blue-600);}
        .dropdown-item i{width:16px;font-size:13px;color:var(--text-muted);}
        .dropdown-item:hover i{color:var(--blue-500);}
        .dropdown-item-danger{color:#e53935;}
        .dropdown-item-danger:hover{background:#fff5f5;color:#c62828;}
        .dropdown-divider{height:1px;background:var(--border-light);margin:3px 0;}

        .nav-bar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 32px;position:sticky;top:64px;z-index:100;box-shadow:var(--shadow-sm);}
        .nav-bar ul{list-style:none;display:flex;gap:4px;margin:0;padding:0;}
        .nav-bar ul li a{display:flex;align-items:center;gap:7px;padding:14px 16px;font-size:13px;font-weight:600;color:var(--text-secondary);text-decoration:none;border-bottom:3px solid transparent;transition:all 0.2s var(--ease);margin-bottom:-1px;}
        .nav-bar ul li a:hover{color:var(--blue-600);background:var(--blue-50);}
        .nav-bar ul li a.active{color:var(--blue-600);border-bottom-color:var(--blue-500);}
        .nav-bar ul li a i{font-size:12px;}

        .page-layout{max-width:1400px;margin:0 auto;padding:28px 32px;animation:pageIn 0.4s var(--ease);}
        @keyframes pageIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

        .top-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:24px;}
        .top-bar-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
        .search-wrap{position:relative;}
        .search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px;pointer-events:none;}
        .search-input{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-xl);padding:9px 14px 9px 32px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-primary);outline:none;width:240px;transition:border-color 0.2s,box-shadow 0.2s;}
        .search-input:focus{border-color:var(--blue-500);box-shadow:var(--shadow-glow);}
        .search-input::placeholder{color:var(--text-muted);}
        .dept-select{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-xl);padding:9px 16px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-primary);outline:none;cursor:pointer;transition:border-color 0.2s;}
        .dept-select:focus{border-color:var(--blue-500);}
        .btn-add{background:linear-gradient(135deg,var(--blue-600),var(--blue-500));color:white;border:none;padding:9px 18px;border-radius:var(--radius-xl);font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;gap:7px;box-shadow:0 4px 14px rgba(33,150,243,0.35);transition:all 0.2s var(--ease);}
        .btn-add:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(33,150,243,0.45);}
        .emp-count{font-size:13px;color:var(--text-muted);padding:9px 0;}
        .emp-count strong{color:var(--text-primary);}

        .emp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
        .emp-card{background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border-light);box-shadow:var(--shadow-sm);padding:20px;display:flex;align-items:flex-start;gap:14px;transition:all 0.2s var(--ease);position:relative;}
        .emp-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);border-color:var(--blue-200);}
        .emp-avatar{width:46px;height:46px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:white;flex-shrink:0;background:linear-gradient(135deg,var(--blue-800),var(--blue-500));}
        .emp-info{flex:1;min-width:0;}
        .emp-name{font-size:15px;font-weight:700;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .emp-position{font-size:12.5px;color:var(--text-secondary);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .emp-meta{display:flex;align-items:center;gap:6px;margin-top:8px;flex-wrap:wrap;}
        .emp-dept{font-size:11.5px;color:var(--text-muted);}
        .emp-dot{color:var(--border);font-size:10px;}
        .emp-rate{font-size:12px;font-weight:600;font-family:'DM Mono',monospace;color:var(--blue-600);}
        .emp-actions{display:flex;gap:6px;flex-shrink:0;}
        .act-btn{width:30px;height:30px;border-radius:50%;border:1.5px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--text-muted);transition:all 0.2s var(--ease);}
        .act-btn.edit:hover{border-color:var(--blue-400);background:var(--blue-50);color:var(--blue-600);}
        .act-btn.del:hover{border-color:#ef9a9a;background:#fff5f5;color:#e53935;}

        .no-results{text-align:center;padding:60px 20px;color:var(--text-muted);grid-column:1/-1;}
        .no-results i{font-size:40px;margin-bottom:12px;display:block;color:var(--blue-200);}

        .modal-overlay{position:fixed;inset:0;background:rgba(9,30,66,0.55);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px;}
        .modal-overlay.open{display:flex;animation:bgIn 0.25s var(--ease);}
        @keyframes bgIn{from{opacity:0;}to{opacity:1;}}
        .modal-box{background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;animation:mIn 0.28s var(--ease-spring);border:1px solid var(--border-light);}
        @keyframes mIn{from{transform:translateY(-20px) scale(0.96);opacity:0;}to{transform:translateY(0) scale(1);opacity:1;}}
        .modal-head{background:linear-gradient(135deg,var(--blue-900),var(--blue-800));color:white;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;border-radius:var(--radius-lg) var(--radius-lg) 0 0;}
        .modal-head h3{font-size:16px;font-weight:700;margin:0;color:white;display:flex;align-items:center;gap:8px;}
        .modal-close{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;}
        .modal-close:hover{background:rgba(255,255,255,0.3);transform:rotate(90deg);}
        .modal-body{padding:22px;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
        .form-group{display:flex;flex-direction:column;gap:5px;}
        .form-group label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-secondary);}
        .form-group label span{color:#e53935;}
        .form-input{padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--radius-md);font-size:13.5px;font-family:'DM Sans',sans-serif;color:var(--text-primary);background:var(--surface-2);transition:border-color 0.2s,box-shadow 0.2s;outline:none;width:100%;}
        .form-input:focus{border-color:var(--blue-500);box-shadow:var(--shadow-glow);background:var(--surface);}
        .annual-hint{font-size:11.5px;color:var(--text-muted);margin-top:3px;font-style:italic;}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1.5px solid var(--border-light);}
        .btn-cancel{background:var(--surface);border:1.5px solid var(--border);padding:9px 22px;border-radius:var(--radius-xl);font-size:13px;font-weight:500;cursor:pointer;color:var(--text-secondary);font-family:'DM Sans',sans-serif;transition:all 0.2s;}
        .btn-cancel:hover{border-color:var(--text-muted);}
        .btn-save{background:linear-gradient(135deg,var(--blue-600),var(--blue-500));color:white;border:none;padding:9px 24px;border-radius:var(--radius-xl);font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;font-family:'DM Sans',sans-serif;box-shadow:0 4px 14px rgba(33,150,243,0.35);transition:all 0.2s;}
        .btn-save:hover{transform:translateY(-1px);}

        .confirm-overlay{position:fixed;inset:0;background:rgba(9,30,66,0.55);backdrop-filter:blur(4px);z-index:10000;display:none;align-items:center;justify-content:center;}
        .confirm-overlay.open{display:flex;}
        .confirm-box{background:var(--surface);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:28px;max-width:360px;width:90%;text-align:center;animation:mIn 0.25s var(--ease-spring);}
        .confirm-icon{font-size:36px;color:#e53935;margin-bottom:12px;}
        .confirm-title{font-size:17px;font-weight:700;color:var(--text-primary);margin-bottom:6px;}
        .confirm-msg{font-size:13.5px;color:var(--text-secondary);margin-bottom:22px;line-height:1.5;}
        .confirm-btns{display:flex;gap:10px;justify-content:center;}
        .btn-del-confirm{background:linear-gradient(135deg,#c62828,#e53935);color:white;border:none;padding:9px 24px;border-radius:var(--radius-xl);font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}

        #sra-toast{position:fixed;bottom:28px;right:28px;z-index:99999;background:var(--blue-900);color:white;padding:12px 20px;border-radius:var(--radius-md);font-size:13.5px;box-shadow:var(--shadow-lg);display:none;align-items:center;gap:10px;border:1px solid rgba(255,255,255,0.15);}
        #sra-toast.show{display:flex;animation:toastIn 0.3s var(--ease-spring);}
        @keyframes toastIn{from{transform:translateY(20px);opacity:0;}to{transform:translateY(0);opacity:1;}}
        #sra-toast i{color:#4ade80;}
        #sra-toast.error i{color:#f87171;}

        @media(max-width:1100px){.emp-grid{grid-template-columns:repeat(2,1fr);}}
        @media(max-width:700px){.emp-grid{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}.top-header{height:auto;padding:14px 20px;flex-direction:column;gap:12px;}.page-layout{padding:16px;}.nav-bar{padding:0 16px;}}
    </style>
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
        <li>
            <a href="employees" class="<?= ($current_page == 'employees.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Employees
            </a>
        </li>
    </ul>
</nav>

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
            $full     = htmlspecialchars($emp['name']);
            $initials = getInitials($emp['name']);
            $pos      = htmlspecialchars($emp['position'] ?? '—');
            $dept     = htmlspecialchars($emp['department'] ?? '—');
            $rate     = $emp['daily_rate'] > 0 ? '&#8369;' . number_format($emp['daily_rate'],0) . '/day' : '&#8369;0/day';
        ?>
        <div class="emp-card"
             data-name="<?= strtolower($emp['name']) ?>"
             data-dept="<?= htmlspecialchars($emp['department'] ?? '') ?>">
            <div class="emp-avatar"><?= $initials ?></div>
            <div class="emp-info">
                <div class="emp-name"><?= $full ?></div>
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
            <div class="form-row">
                <div class="form-group" style="grid-column:1/-1">
                    <label>Full Name <span>*</span></label>
                    <input type="text" class="form-input" id="fName" placeholder="e.g. Juan Dela Cruz">
                </div>
            </div>
            <div class="form-row">
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
            </div>
            <div class="form-row">
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
                    </select>
                </div>
            </div>
            <div class="form-row">
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

<script>
    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    dropBtn.addEventListener('click', () => { dropBtn.classList.toggle('open'); dropMenu.classList.toggle('open'); });
    document.addEventListener('click', e => {
        if (!dropBtn.contains(e.target) && !dropMenu.contains(e.target)) { dropBtn.classList.remove('open'); dropMenu.classList.remove('open'); }
    });

    const empModal   = document.getElementById('empModal');
    const confirmOvl = document.getElementById('confirmOverlay');
    const grid       = document.getElementById('empGrid');

    function showToast(msg, isError = false) {
        const t = document.getElementById('sra-toast');
        const ic = t.querySelector('i');
        document.getElementById('toastMsg').textContent = msg;
        t.className = isError ? 'error' : '';
        ic.className = isError ? 'fas fa-times-circle' : 'fas fa-check-circle';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function updateCount() {
        const visible = grid.querySelectorAll('.emp-card:not([style*="display: none"])').length;
        const total   = grid.querySelectorAll('.emp-card').length;
        document.getElementById('empCount').innerHTML = `Showing <strong>${visible}</strong> of <strong>${total}</strong> employees`;
    }
    updateCount();

    document.getElementById('searchInput').addEventListener('input', filterGrid);
    document.getElementById('deptFilter').addEventListener('change', filterGrid);

    function filterGrid() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const d = document.getElementById('deptFilter').value.toLowerCase();
        grid.querySelectorAll('.emp-card').forEach(card => {
            const nameMatch = card.dataset.name.includes(q);
            const deptMatch = !d || card.dataset.dept.toLowerCase() === d;
            card.style.display = (nameMatch && deptMatch) ? '' : 'none';
        });
        updateCount();
    }

    document.getElementById('fRate').addEventListener('input', function() {
        const rate   = parseFloat(this.value) || 0;
        const annual = rate * 313;
        document.getElementById('annualHint').textContent = rate > 0 ? `Annual ≈ ₱${annual.toLocaleString()}` : '';
    });

    function openModal(mode, data = {}) {
        document.getElementById('fId').value       = data.id || '';
        document.getElementById('fName').value     = data.name || '';
        document.getElementById('fPhone').value    = data.phone || '';
        document.getElementById('fDept').value     = data.dept || '';
        document.getElementById('fPosition').value = data.position || '';
        document.getElementById('fEmpType').value  = data.emptype || 'Full Time';
        document.getElementById('fRate').value     = data.rate || '';
        document.getElementById('fHire').value     = data.hire || '';
        document.getElementById('annualHint').textContent = data.rate > 0 ? `Annual ≈ ₱${(parseFloat(data.rate) * 313).toLocaleString()}` : '';
        const isEdit = mode === 'edit';
        document.getElementById('modalTitle').innerHTML = isEdit
            ? '<i class="fas fa-user-edit"></i> Edit Employee'
            : '<i class="fas fa-user-plus"></i> Add Employee';
        document.getElementById('saveBtnText').textContent = isEdit ? 'Update' : 'Save';
        empModal.classList.add('open');
    }

    function closeModal() { empModal.classList.remove('open'); }

    document.getElementById('addEmpBtn').addEventListener('click', () => openModal('add'));
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('cancelEmpBtn').addEventListener('click', closeModal);
    empModal.addEventListener('click', e => { if (e.target === empModal) closeModal(); });

    grid.addEventListener('click', e => {
        const editBtn = e.target.closest('.act-btn.edit');
        const delBtn  = e.target.closest('.act-btn.del');
        if (editBtn) {
            openModal('edit', {
                id:       editBtn.dataset.id,
                name:     editBtn.dataset.name,
                phone:    editBtn.dataset.phone,
                dept:     editBtn.dataset.dept,
                position: editBtn.dataset.position,
                emptype:  editBtn.dataset.emptype,
                rate:     editBtn.dataset.rate,
                hire:     editBtn.dataset.hire,
            });
        }
        if (delBtn) {
            document.getElementById('confirmMsg').textContent = `Remove ${delBtn.dataset.name} from the payroll system?`;
            document.getElementById('confirmDeleteBtn').dataset.id   = delBtn.dataset.id;
            document.getElementById('confirmDeleteBtn').dataset.name = delBtn.dataset.name;
            confirmOvl.classList.add('open');
        }
    });

    document.getElementById('cancelDeleteBtn').addEventListener('click', () => confirmOvl.classList.remove('open'));
    confirmOvl.addEventListener('click', e => { if (e.target === confirmOvl) confirmOvl.classList.remove('open'); });

    document.getElementById('saveEmpBtn').addEventListener('click', async () => {
        const id       = document.getElementById('fId').value;
        const name     = document.getElementById('fName').value.trim();
        const phone    = document.getElementById('fPhone').value.trim();
        const dept     = document.getElementById('fDept').value;
        const position = document.getElementById('fPosition').value.trim();
        const emptype  = document.getElementById('fEmpType').value;
        const rate     = document.getElementById('fRate').value;
        const hire     = document.getElementById('fHire').value;

        if (!name || !dept || !position) { showToast('Please fill in all required fields.', true); return; }

        const action = id ? 'edit_employee' : 'add_employee';
        const res = await fetch(`employees_api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, name, phone, department: dept, position, employment_type: emptype, daily_rate: rate, hire_date: hire })
        });
        const data = await res.json();
        if (data.success) {
            showToast(id ? 'Employee updated successfully.' : 'Employee added successfully.');
            closeModal();
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Something went wrong.', true);
        }
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        const id = this.dataset.id;
        const res = await fetch(`employees_api.php?action=delete_employee`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Employee removed successfully.');
            confirmOvl.classList.remove('open');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Something went wrong.', true);
        }
    });
</script>
</body>

</html>
