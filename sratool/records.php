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

$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

$records_per_page = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : '';

$where_conditions = [];
$params = [];
$types = "";

if ($status_filter != 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $where_conditions[] = "(borrower_name LIKE ? OR tool_name LIKE ?)";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "date_borrowed >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "date_borrowed <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$where_sql = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

$count_sql = "SELECT COUNT(*) as total FROM borrowing_records $where_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

$sql = "SELECT * FROM borrowing_records $where_sql ORDER BY date_borrowed DESC, time_borrowed DESC LIMIT ? OFFSET ?";
$limit_params = $params;
$limit_params[] = $records_per_page;
$limit_params[] = $offset;
$limit_types = $types . "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($limit_types, ...$limit_params);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$records = $stmt->get_result();

$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM borrowing_records")->fetch_assoc()['count'];
$stats['borrowed'] = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'borrowed'")->fetch_assoc()['count'];
$stats['returned'] = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'returned'")->fetch_assoc()['count'];
$stats['overdue'] = $conn->query("SELECT COUNT(*) as count FROM borrowing_records WHERE status = 'overdue' OR (status = 'borrowed' AND expected_return_date < CURDATE())")->fetch_assoc()['count'];

$overdue_items = $conn->query("
    SELECT * FROM borrowing_records 
    WHERE status = 'overdue' OR (status = 'borrowed' AND expected_return_date < CURDATE())
    ORDER BY expected_return_date ASC
    LIMIT 5
");

$most_borrowed_tools = $conn->query("
    SELECT tool_name, COUNT(*) as borrow_count, SUM(quantity) as total_qty
    FROM borrowing_records 
    WHERE date_borrowed BETWEEN '$date_from' AND '$date_to'
    GROUP BY tool_name 
    ORDER BY borrow_count DESC 
    LIMIT 5
");

$frequent_borrowers = $conn->query("
    SELECT borrower_name, COUNT(*) as borrow_count 
    FROM borrowing_records 
    WHERE date_borrowed BETWEEN '$date_from' AND '$date_to'
    GROUP BY borrower_name 
    ORDER BY borrow_count DESC 
    LIMIT 5
");

$daily_activity = $conn->query("
    SELECT DATE(date_borrowed) as date, COUNT(*) as count 
    FROM borrowing_records 
    WHERE date_borrowed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date_borrowed) 
    ORDER BY date ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records & Reports - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="css/records.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

<?php include 'navigation.php'; ?>

    <div class="main-content">

        <div class="page-header">
            <h2><i class="fas fa-list"></i> Records & Reports</h2>
            <p>View all borrowing transactions with analytics and insights</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Records</h3>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                </div>
                <i class="fas fa-database stat-icon blue"></i>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Currently Borrowed</h3>
                    <div class="stat-number"><?php echo $stats['borrowed']; ?></div>
                </div>
                <i class="fas fa-hand-holding stat-icon purple"></i>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Returned</h3>
                    <div class="stat-number"><?php echo $stats['returned']; ?></div>
                </div>
                <i class="fas fa-check-circle stat-icon green"></i>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Overdue</h3>
                    <div class="stat-number"><?php echo $stats['overdue']; ?></div>
                </div>
                <i class="fas fa-exclamation-triangle stat-icon orange"></i>
            </div>
        </div>

        <button class="toggle-analytics" onclick="toggleAnalytics()">
            <i class="fas fa-chart-bar"></i> <span id="toggleText">Show Analytics & Charts</span>
        </button>

        <div class="analytics-content hidden" id="analyticsContent">
            <div class="charts-grid">

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Activity (Last 7 Days)</h3>
                    </div>
                    <div class="chart-body">
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-tools"></i> Most Borrowed Tools (Top 5)</h3>
                    </div>
                    <div class="chart-body">
                        <?php if ($most_borrowed_tools && $most_borrowed_tools->num_rows > 0): 
                            $tools_array = [];
                            while($row = $most_borrowed_tools->fetch_assoc()) {
                                $tools_array[] = $row;
                            }
                            $max_value = $tools_array[0]['borrow_count'];
                        ?>
                            <table class="ranking-table">
                                <?php 
                                $rank = 1;
                                foreach($tools_array as $row): 
                                    $percentage = ($row['borrow_count'] / $max_value) * 100;
                                ?>
                                    <tr>
                                        <td>
                                            <div><?php echo $rank; ?>. <?php echo htmlspecialchars($row['tool_name']); ?></div>
                                            <div class="progress-bar-custom">
                                                <div class="progress-fill-custom" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                        <td><?php echo $row['borrow_count']; ?>x</td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endforeach; 
                                ?>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-users"></i> Frequent Borrowers (Top 5)</h3>
                    </div>
                    <div class="chart-body">
                        <?php if ($frequent_borrowers && $frequent_borrowers->num_rows > 0): 
                            $borrowers_array = [];
                            while($row = $frequent_borrowers->fetch_assoc()) {
                                $borrowers_array[] = $row;
                            }
                            $max_value = $borrowers_array[0]['borrow_count'];
                        ?>
                            <table class="ranking-table">
                                <?php 
                                $rank = 1;
                                foreach($borrowers_array as $row): 
                                    $percentage = ($row['borrow_count'] / $max_value) * 100;
                                ?>
                                    <tr>
                                        <td>
                                            <div><?php echo $rank; ?>. <?php echo htmlspecialchars($row['borrower_name']); ?></div>
                                            <div class="progress-bar-custom">
                                                <div class="progress-fill-custom" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                        <td><?php echo $row['borrow_count']; ?>x</td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endforeach; 
                                ?>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filter Records</h3>
                <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn-export">
                    <i class="fas fa-download"></i> Export to Excel
                </a>
            </div>

            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="borrowed" <?php echo $status_filter == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                        <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                        <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Search</label>
                    <input 
                        type="text" 
                        name="search" 
                        class="filter-input" 
                        placeholder="Borrower or tool name..."
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date From</label>
                    <input 
                        type="date" 
                        name="date_from" 
                        class="filter-input"
                        value="<?php echo htmlspecialchars($filter_date_from); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date To</label>
                    <input 
                        type="date" 
                        name="date_to" 
                        class="filter-input"
                        value="<?php echo htmlspecialchars($filter_date_to); ?>"
                    >
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>

                <div class="filter-group">
                    <a href="records.php" class="btn-clear">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="table-section">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Borrower Name</th>
                            <th>Tool/Item</th>
                            <th>Qty</th>
                            <th>Date Borrowed</th>
                            <th>Time</th>
                            <th>Expected Return</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                            <th>Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records->num_rows > 0): ?>
                            <?php while($row = $records->fetch_assoc()): 
                                $is_overdue = ($row['status'] == 'overdue') || 
                                             ($row['status'] == 'borrowed' && $row['expected_return_date'] && strtotime($row['expected_return_date']) < time());
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $row['transaction_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['tool_name']); ?></strong></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['date_borrowed'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['time_borrowed'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($row['expected_return_date']) {
                                            echo date('M d, Y', strtotime($row['expected_return_date']));
                                        } else {
                                            echo '<span style="color: #999;">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($row['actual_return_date']) {
                                            echo date('M d, Y', strtotime($row['actual_return_date']));
                                            if ($row['actual_return_time']) {
                                                echo '<br><small style="color: #999;">' . date('h:i A', strtotime($row['actual_return_time'])) . '</small>';
                                            }
                                        } else {
                                            echo '<span style="color: #999;">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $is_overdue ? 'overdue' : $row['status']; ?>">
                                            <?php echo $is_overdue ? 'OVERDUE' : strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($row['condition_on_return']) {
                                            echo htmlspecialchars($row['condition_on_return']);
                                        } else {
                                            echo '<span style="color: #999;">-</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h4>No Records Found</h4>
                                        <p>No borrowing records match your filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records
                    </div>

                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                <i class="fas fa-chevron-left"></i> Previous
                            </span>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                Next <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>

        function toggleAnalytics() {
            const content = document.getElementById('analyticsContent');
            const toggleText = document.getElementById('toggleText');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                toggleText.textContent = 'Hide Analytics & Charts';
            } else {
                content.classList.add('hidden');
                toggleText.textContent = 'Show Analytics & Charts';
            }
        }

        const activityCtx = document.getElementById('activityChart');
        if (activityCtx) {
            const activityChart = new Chart(activityCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        if ($daily_activity) {
                            $dates = [];
                            $counts = [];
                            while($row = $daily_activity->fetch_assoc()) {
                                $dates[] = "'" . date('M d', strtotime($row['date'])) . "'";
                                $counts[] = $row['count'];
                            }
                            echo implode(',', $dates);
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Borrowing Transactions',
                        data: [
                            <?php 
                            if (isset($counts) && count($counts) > 0) {
                                echo implode(',', $counts);
                            }
                            ?>
                        ],
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>