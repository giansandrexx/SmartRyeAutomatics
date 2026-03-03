<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$full_name = $_SESSION['full_name'];
$user_id   = $_SESSION['user_id'];

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_permissions = [];

if ($user_role === 'moderator') {

    $user_permissions = ['tool_room', 'scheduling', 'attendance', 'payroll'];
} else {
    require_once 'config.php';
    if (!$conn->connect_error) {
        $res = $conn->query("SELECT system_key FROM user_permissions WHERE user_id = $user_id");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $user_permissions[] = $r['system_key'];
            }
        }
        $conn->close();
    }
}

function canAccess($key, $perms) {
    return in_array($key, $perms);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Rye Management Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="sratool/img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sratool/css/portal.css">
    <link rel="stylesheet" href="sratool/css/responsive.css">
</head>
<body>

    <div class="top-header">
        <div class="logo-section">
            <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
            <h1 class="system-title">Smart Rye Automatics Management Portal</h1>
        </div>
        <div class="header-right">
            <div class="current-date">
                <?php echo date('l, jS F Y'); ?>
            </div>
            <div class="user-info">
                <div class="user-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                </div>
                <div class="user-dropdown-wrap">
                    <button class="user-dropdown-toggle" id="userDropdownBtn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <?php if ($user_role === 'moderator'): ?>
                        <a href="account_manage" class="dropdown-item">
                            <i class="fas fa-users-cog"></i> Account Management
                        </a>
                        <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="?logout=1" class="dropdown-item dropdown-item-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h1>Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p>Select a management system to access</p>
        </div>

        <div class="systems-grid">

            <?php if (canAccess('tool_room', $user_permissions)): ?>
            <div class="system-card">
                <div class="system-card-header">
                    <div class="system-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>SRA Tool Room</h3>
                </div>
                <div class="system-card-body">
                    <p class="system-description">Tool borrowing, returns, inventory &amp; overdue monitoring.</p>
                    <a href="sratool/dashboard"
                       class="system-btn"
                       data-navigate="sratool/dashboard"
                       data-room-name="Tool Room">
                        Access Tool Room <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (canAccess('scheduling', $user_permissions)): ?>
            <div class="system-card">
                <div class="system-card-header">
                    <div class="system-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>SRA Event Scheduling</h3>
                </div>
                <div class="system-card-body">
                    <p class="system-description">Appointments, resource planning &amp; team coordination.</p>
                    <a href="scheduling/scheduling"
                       class="system-btn"
                       data-navigate="srahr/dashboard"
                       data-room-name="SRA Scheduling">
                        Access Scheduling <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (canAccess('attendance', $user_permissions)): ?>
            <div class="system-card">
                <div class="system-card-header">
                    <div class="system-icon">
                        <i class="fa fa-address-book"></i>
                    </div>
                    <h3>SRA Attendance</h3>
                </div>
                <div class="system-card-body">
                    <p class="system-description">Attendance monitoring, work hours tracking &amp; leave administration.</p>
                    <a href="srattend/index"
                       class="system-btn"
                       data-navigate="srattend/index"
                       data-room-name="SRA Attendance">
                        Access Attendance <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (canAccess('payroll', $user_permissions)): ?>
            <div class="system-card">
                <div class="system-card-header">
                    <div class="system-icon">
                        <i class="fa fa-calculator"></i>
                    </div>
                    <h3>SRA Payroll</h3>
                </div>
                <div class="system-card-body">
                    <p class="system-description">Automate salaries, deductions, and payslips with ease.</p>
                    <a href="srapayroll/dashboard"
                       class="system-btn"
                       data-navigate="srapayroll/dashboard"
                       data-room-name="SRA Payroll">
                        Access Payroll <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($user_permissions)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 60px 20px; color: #94a3b8;">
                <i class="fas fa-lock" style="font-size: 48px; margin-bottom: 16px; display:block;"></i>
                <h3 style="color:#64748b;">No Systems Assigned</h3>
                <p>You have not been granted access to any system yet.<br>Please contact your administrator.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/portal_transition.js"></script>
    <script src="js/dropdown.js"></script>
</body>
</html>

