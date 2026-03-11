<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

require_once "../config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'borrowed'");
$stats['borrowed'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'returned'");
$stats['returned'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'overdue' OR (status = 'borrowed' AND expected_return_date < CURDATE())");
$stats['overdue'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE DATE(created_at) = CURDATE()");
$stats['today'] = $result->fetch_assoc()['count'];

$total_active     = $stats['borrowed'] + $stats['overdue'];
$borrowed_percent = $total_active > 0 ? round(($stats['borrowed'] / $total_active) * 100) : 0;
$overdue_percent  = $total_active > 0 ? round(($stats['overdue'] / $total_active) * 100) : 0;

$inventory = [];
$r = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity),0) as stock FROM motors WHERE is_active = 1");
$inventory['motors'] = $r->fetch_assoc();
$r = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity),0) as stock FROM video_intercom");
$inventory['intercom'] = $r->fetch_assoc();
$r = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity),0) as stock FROM e_fences");
$inventory['efences'] = $r->fetch_assoc();
$r = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity),0) as stock FROM consumables");
$inventory['consumables'] = $r->fetch_assoc();



$return_reminders = $conn->query("
    SELECT * FROM borrowing_records
    WHERE status = 'borrowed'
      AND expected_return_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY expected_return_date ASC
    LIMIT 6
");

$recent_borrows = $conn->query("
    SELECT * FROM borrowing_records
    WHERE status IN ('borrowed', 'overdue')
    ORDER BY date_borrowed DESC, time_borrowed DESC
    LIMIT 5
");

$overdue_items = $conn->query("
    SELECT * FROM borrowing_records
    WHERE status = 'overdue' OR (status = 'borrowed' AND expected_return_date < CURDATE())
    ORDER BY expected_return_date ASC
    LIMIT 5
");

$chart_labels = [];
$chart_data   = [];
for ($i = 6; $i >= 0; $i--) {
    $date           = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime("-$i days"));
    $res            = $conn->query("SELECT COUNT(*) as cnt FROM borrowing_records WHERE DATE(created_at) = '$date'");
    $chart_data[]   = intval($res->fetch_assoc()['cnt']);
}

$record_tables_acq = [
    'motor_records'      => ['motor_name', 'Motors',         'fa-cog'],
    'intercom_records'   => ['item_name',  'Video Intercom', 'fa-video'],
    'efence_records'     => ['item_name',  'E-Fences',       'fa-shield-alt'],
    'consumable_records' => ['item_name',  'Consumables',    'fa-box-open'],
];
$recent_acquisitions = [];
foreach ($record_tables_acq as $rtable => [$nfield, $cat_label, $cat_icon]) {
    $check = $conn->query("SHOW TABLES LIKE '$rtable'");
    if (!$check || $check->num_rows === 0) continue;
    $res = $conn->query("SELECT *, '$cat_label' AS cat_label, '$cat_icon' AS cat_icon FROM $rtable ORDER BY created_at DESC LIMIT 5");
    if ($res) while ($r = $res->fetch_assoc()) {
        $r['_name'] = $r[$nfield] ?? '—';
        $recent_acquisitions[] = $r;
    }
}
usort($recent_acquisitions, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$recent_acquisitions = array_slice($recent_acquisitions, 0, 6);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<?php include 'navigation.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div class="page-header-left">
            <h2>Dashboard</h2>
            <p>Overview for <?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="header-time">
            <i class="fas fa-circle pulse-dot"></i>
            Live &nbsp;&middot;&nbsp; <?php echo date('h:i A'); ?>
        </div>
    </div>



    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-label">Currently Borrowed</span>
                <div class="stat-icon stat-icon-blue"><i class="fas fa-hand-holding"></i></div>
            </div>
            <div class="stat-number"><?php echo $stats['borrowed']; ?></div>
            <div class="stat-bar"><div class="stat-bar-fill stat-bar-blue" style="--w:<?php echo $borrowed_percent; ?>%;"></div></div>
            <span class="stat-sub"><?php echo $borrowed_percent; ?>% of active items</span>
        </div>

        <div class="stat-card <?php echo $stats['overdue'] > 0 ? 'stat-card-urgent' : ''; ?>">
            <div class="stat-card-top">
                <span class="stat-label">Overdue</span>
                <div class="stat-icon stat-icon-red"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-number"><?php echo $stats['overdue']; ?></div>
            <div class="stat-bar"><div class="stat-bar-fill stat-bar-red" style="--w:<?php echo $overdue_percent; ?>%;"></div></div>
            <span class="stat-sub"><?php echo $overdue_percent; ?>% of active items</span>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-label">Total Returned</span>
                <div class="stat-icon stat-icon-green"><i class="fas fa-check-double"></i></div>
            </div>
            <div class="stat-number"><?php echo $stats['returned']; ?></div>
            <div class="stat-bar"><div class="stat-bar-fill stat-bar-green" style="--w:100%;"></div></div>
            <span class="stat-sub">All-time returns</span>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-label">Today's Activity</span>
                <div class="stat-icon stat-icon-purple"><i class="fas fa-bolt"></i></div>
            </div>
            <div class="stat-number"><?php echo $stats['today']; ?></div>
            <div class="stat-bar"><div class="stat-bar-fill stat-bar-purple" style="--w:100%;"></div></div>
            <span class="stat-sub">Transactions today</span>
        </div>
    </div>

    <div class="inventory-row">
        <a href="motors.php" class="inv-card">
            <div class="inv-left">
                <div class="inv-icon inv-blue"><i class="fas fa-cog"></i></div>
                <div class="inv-body">
                    <span class="inv-label">Automation Motors</span>
                    <span class="inv-sub"><?php echo intval($inventory['motors']['total']); ?> item types · <?php echo intval($inventory['motors']['stock']); ?> units</span>
                </div>
            </div>
            <i class="fas fa-chevron-right inv-arrow"></i>
        </a>
        <a href="intercom.php" class="inv-card">
            <div class="inv-left">
                <div class="inv-icon inv-teal"><i class="fas fa-video"></i></div>
                <div class="inv-body">
                    <span class="inv-label">Video Intercom</span>
                    <span class="inv-sub"><?php echo intval($inventory['intercom']['total']); ?> item types · <?php echo intval($inventory['intercom']['stock']); ?> units</span>
                </div>
            </div>
            <i class="fas fa-chevron-right inv-arrow"></i>
        </a>
        <a href="efence.php" class="inv-card">
            <div class="inv-left">
                <div class="inv-icon inv-green"><i class="fas fa-shield-alt"></i></div>
                <div class="inv-body">
                    <span class="inv-label">E-Fences</span>
                    <span class="inv-sub"><?php echo intval($inventory['efences']['total']); ?> item types · <?php echo intval($inventory['efences']['stock']); ?> units</span>
                </div>
            </div>
            <i class="fas fa-chevron-right inv-arrow"></i>
        </a>
        <a href="consumables.php" class="inv-card">
            <div class="inv-left">
                <div class="inv-icon inv-orange"><i class="fas fa-box-open"></i></div>
                <div class="inv-body">
                    <span class="inv-label">Consumables</span>
                    <span class="inv-sub"><?php echo intval($inventory['consumables']['total']); ?> item types · <?php echo intval($inventory['consumables']['stock']); ?> units</span>
                </div>
            </div>
            <i class="fas fa-chevron-right inv-arrow"></i>
        </a>
    </div>

    <div class="main-grid">

        <div class="panel chart-panel">
            <div class="panel-head">
                <div class="panel-title"><i class="fas fa-chart-bar"></i> 7-Day Borrowing Activity</div>
                <span class="panel-hint">Transactions per day</span>
            </div>
            <div class="chart-wrap">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <?php if ($return_reminders && $return_reminders->num_rows > 0): ?>
        <div class="panel reminders-panel">
            <div class="panel-head">
                <div class="panel-title"><i class="fas fa-bell"></i> Due Soon</div>
                <span class="remind-count-badge"><?php echo $return_reminders->num_rows; ?></span>
            </div>
            <div class="panel-body">
                <?php while($row = $return_reminders->fetch_assoc()):
                    $due  = strtotime($row['expected_return_date']);
                    $diff = (int)((strtotime(date('Y-m-d')) - $due) / -86400);
                    $cls  = $diff === 0 ? 'tag-today' : ($diff === 1 ? 'tag-tomorrow' : 'tag-soon');
                    $lbl  = $diff === 0 ? 'Due today' : ($diff === 1 ? 'Tomorrow' : 'In ' . $diff . ' days');
                ?>
                    <div class="remind-row">
                        <div class="remind-avatar"><?php echo strtoupper(substr($row['borrower_name'], 0, 1)); ?></div>
                        <div class="remind-meta">
                            <span class="remind-name"><?php echo htmlspecialchars($row['borrower_name']); ?></span>
                            <span class="remind-tool"><i class="fas fa-tools"></i> <?php echo htmlspecialchars($row['tool_name']); ?></span>
                        </div>
                        <span class="due-tag <?php echo $cls; ?>"><?php echo $lbl; ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title"><i class="fas fa-clock"></i> Recent Borrowings</div>
            </div>
            <div class="panel-body">
                <?php if ($recent_borrows->num_rows > 0): ?>
                    <?php while($row = $recent_borrows->fetch_assoc()): ?>
                        <div class="borrow-row">
                            <div class="borrow-avatar"><?php echo strtoupper(substr($row['borrower_name'], 0, 1)); ?></div>
                            <div class="borrow-info">
                                <span class="borrow-name"><?php echo htmlspecialchars($row['borrower_name']); ?></span>
                                <span class="borrow-detail">
                                    <i class="fas fa-tools"></i> <?php echo htmlspecialchars($row['tool_name']); ?>
                                    &nbsp;&middot;&nbsp; Qty <?php echo $row['quantity']; ?>
                                </span>
                            </div>
                            <div class="borrow-dates">
                                <span class="borrow-date-borrowed"><?php echo date('M d', strtotime($row['date_borrowed'])); ?></span>
                                <?php if ($row['expected_return_date']): ?>
                                    <span class="borrow-date-due">Due <?php echo date('M d', strtotime($row['expected_return_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No recent borrowings</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel panel-danger">
            <div class="panel-head">
                <div class="panel-title panel-title-red"><i class="fas fa-exclamation-circle"></i> Overdue Items</div>
                <?php if ($stats['overdue'] > 0): ?>
                    <span class="overdue-count-badge"><?php echo $stats['overdue']; ?></span>
                <?php endif; ?>
            </div>
            <div class="panel-body">
                <?php if ($overdue_items->num_rows > 0): ?>
                    <?php while($row = $overdue_items->fetch_assoc()):
                        $days_late = max(0, (int)((time() - strtotime($row['expected_return_date'])) / 86400));
                    ?>
                        <div class="overdue-row">
                            <div class="overdue-avatar"><?php echo strtoupper(substr($row['borrower_name'], 0, 1)); ?></div>
                            <div class="overdue-info">
                                <span class="overdue-name"><?php echo htmlspecialchars($row['borrower_name']); ?></span>
                                <span class="overdue-detail">
                                    <i class="fas fa-tools"></i> <?php echo htmlspecialchars($row['tool_name']); ?>
                                    &nbsp;&middot;&nbsp; Qty <?php echo $row['quantity']; ?>
                                </span>
                            </div>
                            <div class="overdue-right">
                                <span class="overdue-days-badge"><?php echo $days_late; ?>d overdue</span>
                                <?php if ($row['expected_return_date']): ?>
                                    <span class="overdue-was-due">was <?php echo date('M d', strtotime($row['expected_return_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>All items returned on time</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($recent_acquisitions)): ?>
        <div class="panel acq-panel">
            <div class="panel-head">
                <div class="panel-title"><i class="fas fa-history"></i> Recent Acquisitions</div>
            </div>
            <div class="panel-body">
                <?php foreach ($recent_acquisitions as $acq): ?>
                    <div class="acq-row">
                        <div class="acq-icon-box"><i class="fas <?php echo $acq['cat_icon']; ?>"></i></div>
                        <div class="acq-info">
                            <span class="acq-name"><?php echo htmlspecialchars($acq['_name']); ?></span>
                            <span class="acq-meta">
                                <span class="acq-cat-pill"><?php echo $acq['cat_label']; ?></span>
                                <?php echo htmlspecialchars($acq['project_name']); ?>
                                &nbsp;&middot;&nbsp; Qty <?php echo $acq['quantity']; ?>
                            </span>
                        </div>
                        <span class="acq-date"><?php echo date('M d', strtotime($acq['date_acquired'])); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('activityChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($chart_data); ?>,
            backgroundColor: (context) => {
                const chart = context.chart;
                const {ctx: c, chartArea} = chart;
                if (!chartArea) return 'rgba(33,150,243,0.4)';
                const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                g.addColorStop(0, 'rgba(33,150,243,0.75)');
                g.addColorStop(1, 'rgba(33,150,243,0.06)');
                return g;
            },
            borderColor: 'rgba(33,150,243,0.9)',
            borderWidth: 2,
            borderRadius: 7,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a2236',
                titleColor: '#e2e8f0',
                bodyColor: '#90a4ae',
                borderColor: 'rgba(33,150,243,0.25)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10,
                callbacks: {
                    label: (c) => '  ' + c.parsed.y + ' transaction' + (c.parsed.y !== 1 ? 's' : '')
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: '#78909c', font: { size: 11.5, family: 'DM Sans' }, padding: 6 }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.045)' },
                border: { display: false },
                ticks: { color: '#78909c', font: { size: 11.5, family: 'DM Sans' }, precision: 0, padding: 8 }
            }
        }
    }
});
</script>
</body>
</html>
