<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tool Borrowing Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/portal.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/dropdown.css">
</head>
<body>

 <div class="top-header">
        <div class="logo-section">
            <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
            <h1 class="system-title">Tool Room Management System</h1>
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
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                </div>
                <div class="user-dropdown-wrap">
                    <button class="user-dropdown-toggle" id="userDropdownBtn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="../portal" class="dropdown-item">
                            <i class="fas fa-arrow-left"></i> Back to Portal
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../portal?logout=1" class="dropdown-item dropdown-item-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>

<nav class="nav-bar">
    <ul>
        <li>
            <a href="dashboard" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="borrow_tool" class="<?= ($current_page == 'borrow_tool.php') ? 'active' : '' ?>">
                <i class="fas fa-hand-holding"></i> Borrow Tool
            </a>
        </li>

        <?php if ($user_role == 'admin' || $user_role == 'moderator'): ?>
        <li>
            <a href="return_tool" class="<?= ($current_page == 'return_tool.php') ? 'active' : '' ?>">
                <i class="fas fa-undo"></i> Return Tool
            </a>
        </li>
        <li>
            <a href="records" class="<?= ($current_page == 'records.php') ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Records & Reports
            </a>
        </li>
        <li class="dropdown" id="stocksDropdown">
            <a href="#" id="stocksToggle"
               class="dropdown-toggle-link <?= in_array($current_page, ['consumables.php','motors.php','intercom.php','efence.php']) ? 'active' : '' ?>"
               aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-boxes"></i> Stocks <i class="fas fa-caret-down caret-icon"></i>
            </a>
            <ul class="dropdown-menu" id="stocksMenu" role="menu">
                <li><a href="consumables" class="<?= ($current_page == 'consumables.php') ? 'active' : '' ?>"><i class="fas fa-box-open"></i> Consumables</a></li>
                <li><a href="motors" class="<?= ($current_page == 'motors.php') ? 'active' : '' ?>"><i class="fas fa-cog"></i> Automations & Accessories</a></li>
                <li><a href="intercom" class="<?= ($current_page == 'intercom.php') ? 'active' : '' ?>"><i class="fas fa-video"></i> Video Intercom</a></li>
                <li><a href="efence" class="<?= ($current_page == 'efence.php') ? 'active' : '' ?>"><i class="fas fa-shield-alt"></i> E-Fences</a></li>
            </ul>
        </li>
        <li>
            <a href="motor_get" class="<?= ($current_page == 'motor_get.php') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Record Acquisition
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>



<script src="../js/dropdown.js"></script>