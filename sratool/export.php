<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once "../config.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

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

if (!empty($date_from)) {
    $where_conditions[] = "date_borrowed >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "date_borrowed <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT * FROM borrowing_records $where_sql ORDER BY date_borrowed DESC, time_borrowed DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$filename = "borrowing_records_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<?xml version="1.0"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
echo ' xmlns:o="urn:schemas-microsoft-com:office:office"';
echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
echo ' xmlns:html="http://www.w3.org/TR/REC-html40">';

echo '<Styles>';
echo '<Style ss:ID="header">';
echo '<Font ss:Bold="1" ss:Color="#FFFFFF"/>';
echo '<Interior ss:Color="#2196F3" ss:Pattern="Solid"/>';
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
echo '</Style>';
echo '<Style ss:ID="borrowed">';
echo '<Interior ss:Color="#E3F2FD" ss:Pattern="Solid"/>';
echo '</Style>';
echo '<Style ss:ID="returned">';
echo '<Interior ss:Color="#E8F5E9" ss:Pattern="Solid"/>';
echo '</Style>';
echo '<Style ss:ID="overdue">';
echo '<Interior ss:Color="#FFEBEE" ss:Pattern="Solid"/>';
echo '</Style>';
echo '</Styles>';

echo '<Worksheet ss:Name="Borrowing Records">';
echo '<Table>';

echo '<Column ss:Width="50"/>';
echo '<Column ss:Width="150"/>';
echo '<Column ss:Width="150"/>';
echo '<Column ss:Width="50"/>';
echo '<Column ss:Width="100"/>';
echo '<Column ss:Width="80"/>';
echo '<Column ss:Width="100"/>';
echo '<Column ss:Width="100"/>';
echo '<Column ss:Width="80"/>';
echo '<Column ss:Width="80"/>';
echo '<Column ss:Width="100"/>';
echo '<Column ss:Width="80"/>';
echo '<Column ss:Width="250"/>';

echo '<Row>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Borrower Name</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Tool/Item Name</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Quantity</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Date Borrowed</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Time Borrowed</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Expected Return</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Actual Return Date</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Actual Return Time</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Status</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Condition on Return</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Duration (Days)</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Notes</Data></Cell>';
echo '</Row>';

while ($row = $result->fetch_assoc()) {

    $is_overdue = ($row['status'] == 'overdue') || 
                  ($row['status'] == 'borrowed' && $row['expected_return_date'] && strtotime($row['expected_return_date']) < time());

    $duration = '';
    if ($row['status'] == 'returned' && $row['actual_return_date']) {
        $borrowed = strtotime($row['date_borrowed']);
        $returned = strtotime($row['actual_return_date']);
        $duration = floor(($returned - $borrowed) / (60 * 60 * 24));
    } elseif ($row['status'] == 'borrowed' || $is_overdue) {
        $borrowed = strtotime($row['date_borrowed']);
        $now = time();
        $duration = floor(($now - $borrowed) / (60 * 60 * 24));
    }

    $style = '';
    if ($is_overdue) {
        $style = 'overdue';
    } elseif ($row['status'] == 'returned') {
        $style = 'returned';
    } elseif ($row['status'] == 'borrowed') {
        $style = 'borrowed';
    }
    
    echo '<Row>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="Number">' . $row['transaction_id'] . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($row['borrower_name']) . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($row['tool_name']) . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="Number">' . $row['quantity'] . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . date('M d, Y', strtotime($row['date_borrowed'])) . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . date('h:i A', strtotime($row['time_borrowed'])) . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . ($row['expected_return_date'] ? date('M d, Y', strtotime($row['expected_return_date'])) : 'N/A') . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . ($row['actual_return_date'] ? date('M d, Y', strtotime($row['actual_return_date'])) : '-') . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . ($row['actual_return_time'] ? date('h:i A', strtotime($row['actual_return_time'])) : '-') . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . strtoupper($is_overdue ? 'OVERDUE' : $row['status']) . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . ($row['condition_on_return'] ? htmlspecialchars($row['condition_on_return']) : '-') . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . ($duration !== '' ? $duration . ' days' : '-') . '</Data></Cell>';
    echo '<Cell' . ($style ? ' ss:StyleID="' . $style . '"' : '') . '><Data ss:Type="String">' . htmlspecialchars($row['notes'] ?? '') . '</Data></Cell>';
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';

$conn->close();
exit();
?>