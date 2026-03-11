<?php
session_start();
require_once 'sratool/check_moderator.php';
require_once 'config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$filter_system = isset($_GET['system']) ? $conn->real_escape_string($_GET['system']) : '';
$filter_user   = isset($_GET['user'])   ? $conn->real_escape_string($_GET['user'])   : '';
$filter_date   = isset($_GET['date'])   ? $conn->real_escape_string($_GET['date'])   : '';
$filter_search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 25;
$offset   = ($page - 1) * $per_page;

$where = ["system_key NOT IN ('payroll', 'account', 'system_logs')"];
if ($filter_system) $where[] = "system_key = '$filter_system'";
if ($filter_user)   $where[] = "(username LIKE '%$filter_user%' OR full_name LIKE '%$filter_user%')";
if ($filter_date)   $where[] = "DATE(created_at) = '$filter_date'";
if ($filter_search) $where[] = "(action LIKE '%$filter_search%' OR description LIKE '%$filter_search%')";

$where_sql = 'WHERE ' . implode(' AND ', $where);

$total_count = $conn->query("SELECT COUNT(*) as cnt FROM system_logs $where_sql")->fetch_assoc()['cnt'];
$total_pages = ceil($total_count / $per_page);

$logs = $conn->query("SELECT * FROM system_logs $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

$stats = [];
$stat_res = $conn->query("SELECT system_key, COUNT(*) as cnt FROM system_logs WHERE system_key NOT IN ('payroll', 'account', 'system_logs') GROUP BY system_key");
while ($s = $stat_res->fetch_assoc()) $stats[$s['system_key']] = $s['cnt'];

$recent = $conn->query("SELECT COUNT(*) as cnt FROM system_logs WHERE created_at >= NOW() - INTERVAL 24 HOUR AND system_key NOT IN ('payroll', 'account', 'system_logs')")->fetch_assoc()['cnt'];

$conn->close();

$system_labels = [
    'tool_room'     => 'Tool Room',
    'scheduling'    => 'Scheduling',
    'attendance'    => 'Attendance',
    'employee_info' => 'Employee Info',
];

$system_icons = [
    'tool_room'     => 'fa-tools',
    'scheduling'    => 'fa-calendar-alt',
    'attendance'    => 'fa-address-book',
    'employee_info' => 'fa-id-card',
];

$system_colors = [
    'tool_room'     => '#3b82f6',
    'scheduling'    => '#8b5cf6',
    'attendance'    => '#10b981',
    'employee_info' => '#ec4899',
];

$qparams = array_filter(['system'=>$filter_system,'user'=>$filter_user,'date'=>$filter_date,'search'=>$filter_search]);
$qstring = $qparams ? '&' . http_build_query($qparams) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    <link rel="icon" type="image/png" sizes="32x32" href="sratool/img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sratool/css/portal.css">
    <link rel="stylesheet" href="sratool/css/consumables.css">
    <link rel="stylesheet" href="sratool/css/dashboard.css">
    <link rel="stylesheet" href="sratool/css/responsive.css">
    <style>
        :root { --indigo: #6366f1; --indigo-dark: #4f46e5; }

        .log-filters {
            background: #fff;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        .log-filters .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 160px;
        }
        .log-filters label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .log-filters select,
        .log-filters input  { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 14px; color: #1e293b; background: #f8fafc; }
        .log-filters select:focus,
        .log-filters input:focus  { outline: none; border-color: var(--indigo); background: #fff; }
        .btn-filter { background: var(--indigo); color: #fff; border: none; border-radius: 8px; padding: 9px 20px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 7px; }
        .btn-filter:hover { background: var(--indigo-dark); }
        .btn-clear { background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; padding: 9px 16px; font-size: 14px; font-weight: 500; cursor: pointer; }
        .btn-clear:hover { background: #e2e8f0; }

        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        .log-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
        .log-table tr:hover td { background: #f8fafc; }
        .log-table tr:last-child td { border-bottom: none; }

        .sys-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #6366f1, #8b5cf6); flex-shrink: 0; }
        .user-cell-text { line-height: 1.3; }
        .user-cell-text small { color: #94a3b8; font-size: 12px; }
        .action-text { font-weight: 600; color: #1e293b; }
        .desc-text { color: #64748b; font-size: 13px; margin-top: 2px; }
        .log-date { color: #64748b; font-size: 13px; white-space: nowrap; }

        .empty-logs { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-logs i { font-size: 48px; margin-bottom: 14px; display: block; }

        .pagination-wrap { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 14px 14px; }
        .pagination-info { font-size: 13px; color: #64748b; }
        .pag-btns { display: flex; gap: 4px; }
        .pag-btn { padding: 6px 12px; border: 1.5px solid #e2e8f0; background: #fff; border-radius: 7px; font-size: 13px; color: #475569; text-decoration: none; }
        .pag-btn:hover { background: #f1f5f9; }
        .pag-btn.active { background: var(--indigo); color: #fff; border-color: var(--indigo); }
        .pag-btn.disabled { opacity: .4; pointer-events: none; }

        .stats-mini { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .stat-mini-card { background: #fff; border-radius: 12px; padding: 16px 20px; flex: 1; min-width: 150px; box-shadow: 0 2px 10px rgba(0,0,0,.06); display: flex; align-items: center; gap: 14px; }
        .stat-mini-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; flex-shrink: 0; }
        .stat-mini-info h4 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1; }
        .stat-mini-info p  { font-size: 12px; color: #64748b; margin: 3px 0 0; }

        .table-container { border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
        .table-header { background: #fff; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .table-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
        .log-count-badge { background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        .btn-export { background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; }
        .btn-export:hover { background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>

<div class="top-header">
    <div class="logo-section">
        <img src="https://smartrye.com.ph/ams/public/backend/images/logo-sra.png" alt="Logo" class="logo-img">
        <h1 class="system-title">System Logs</h1>
    </div>
    <div class="header-right">
        <div class="current-date"><?php echo date('l, jS F Y'); ?></div>
        <div class="user-info">
            <div class="user-icon"><i class="fas fa-user"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
            <div class="user-dropdown-wrap">
                <button class="user-dropdown-toggle" id="userDropdownBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="portal" class="dropdown-item">
                        <i class="fas fa-arrow-left"></i> Back to Portal
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="sratool/logout" class="dropdown-item dropdown-item-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="main-content">

    <div class="stats-mini">
        <div class="stat-mini-card">
            <div class="stat-mini-icon" style="background: linear-gradient(135deg,#6366f1,#4f46e5);">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-mini-info">
                <h4><?php echo number_format($total_count); ?></h4>
                <p>Total Log Entries</p>
            </div>
        </div>
        <div class="stat-mini-card">
            <div class="stat-mini-icon" style="background: linear-gradient(135deg,#10b981,#059669);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-mini-info">
                <h4><?php echo number_format($recent); ?></h4>
                <p>Last 24 Hours</p>
            </div>
        </div>
        <?php foreach ($stats as $skey => $cnt):
            if (!isset($system_labels[$skey])) continue;
            $label = $system_labels[$skey];
            $color = $system_colors[$skey] ?? '#64748b';
        ?>
        <div class="stat-mini-card">
            <div class="stat-mini-icon" style="background: <?php echo $color; ?>;">
                <i class="fas <?php echo $system_icons[$skey] ?? 'fa-circle'; ?>"></i>
            </div>
            <div class="stat-mini-info">
                <h4><?php echo number_format($cnt); ?></h4>
                <p><?php echo $label; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form method="GET" class="log-filters">
        <div class="filter-group">
            <label><i class="fas fa-layer-group"></i> System</label>
            <select name="system">
                <option value="">All Systems</option>
                <?php foreach ($system_labels as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $filter_system === $k ? 'selected' : ''; ?>>
                    <?php echo $v; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-user"></i> User</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($filter_user); ?>" placeholder="Search username...">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-calendar"></i> Date</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Action or description...">
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        <a href="system_logs" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
    </form>

    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-clipboard-list" style="color:var(--indigo);margin-right:8px;"></i> Activity Log</h3>
            <div style="display:flex;gap:10px;align-items:center;">
                <span class="log-count-badge"><?php echo number_format($total_count); ?> entries</span>
            </div>
        </div>

        <?php if ($logs && $logs->num_rows > 0): ?>
        <div style="overflow-x:auto; background:#fff;">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>System</th>
                        <th>Action / Description</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $row_num = $offset + 1;
                while ($log = $logs->fetch_assoc()):
                    $skey   = $log['system_key'];
                    $color  = $system_colors[$skey] ?? '#64748b';
                    $icon   = $system_icons[$skey]  ?? 'fa-circle';
                    $label  = $system_labels[$skey]  ?? $skey;
                    $initials = strtoupper(substr($log['full_name'], 0, 1) . (strpos($log['full_name'],' ') !== false ? substr(strrchr($log['full_name'],' '),1,1) : ''));
                ?>
                <tr>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo $row_num++; ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="user-cell-text">
                                <div><?php echo htmlspecialchars($log['full_name']); ?></div>
                                <small><?php echo htmlspecialchars($log['username']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="sys-pill" style="background:<?php echo $color; ?>1a;color:<?php echo $color; ?>;">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-text"><?php echo htmlspecialchars($log['action']); ?></div>
                        <?php if ($log['description']): ?>
                        <div class="desc-text"><?php echo htmlspecialchars($log['description']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="log-date">
                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                        <small><?php echo date('g:i A', strtotime($log['created_at'])); ?></small>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-wrap">
            <div class="pagination-info">
                Showing <?php echo ($offset+1); ?>–<?php echo min($offset+$per_page, $total_count); ?> of <?php echo number_format($total_count); ?> entries
            </div>
            <div class="pag-btns">
                <a href="?page=<?php echo $page-1; ?><?php echo $qstring; ?>" class="pag-btn <?php echo $page<=1?'disabled':''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
                <a href="?page=<?php echo $p; ?><?php echo $qstring; ?>" class="pag-btn <?php echo $p==$page?'active':''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $qstring; ?>" class="pag-btn <?php echo $page>=$total_pages?'disabled':''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-logs">
            <i class="fas fa-clipboard-list"></i>
            <h4 style="color:#64748b;">No logs found</h4>
            <p>No activity has been recorded yet<?php echo ($filter_system||$filter_user||$filter_date||$filter_search) ? ' for the selected filters' : ''; ?>.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dropdown.js"></script>
</body>
</html>



