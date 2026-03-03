<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $borrower_name = trim($_POST['borrower_name']);
    $tool_name = trim($_POST['tool_name']);
    $quantity = intval($_POST['quantity']);
    $expected_return_date = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : NULL;
    $notes = trim($_POST['notes']);

    if (empty($borrower_name)) {
        $error_message = "Borrower name is required!";
    } elseif (empty($tool_name)) {
        $error_message = "Tool/Item name is required!";
    } elseif ($quantity < 1) {
        $error_message = "Quantity must be at least 1!";
    } else {

        $borrowed_at = date('Y-m-d H:i:s');
        $date_borrowed = date('Y-m-d');
        $time_borrowed = date('H:i:s');
        $stmt = $conn->prepare("INSERT INTO borrowing_records (borrower_name, tool_name, quantity, date_borrowed, time_borrowed, expected_return_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, 'borrowed', ?, ?)");
        $stmt->bind_param("ssissssi", $borrower_name, $tool_name, $quantity, $date_borrowed, $time_borrowed, $expected_return_date, $notes, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Tool borrowed successfully!";
            $_POST = array();
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$tools_suggestions = $conn->query("SELECT DISTINCT tool_name FROM borrowing_records ORDER BY tool_name ASC LIMIT 50");
$borrowers_suggestions = $conn->query("SELECT DISTINCT borrower_name FROM borrowing_records ORDER BY borrower_name ASC LIMIT 50");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Tool - Tool Room Management System</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/borrow.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    
<?php include 'navigation.php'; ?>

    <div class="main-content">

        <div class="page-header">
            <h2><i class="fas fa-hand-holding"></i> Borrow Tool</h2>
            <p>Record a new tool borrowing transaction</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3 class="form-section-title">Borrowing Information</h3>
            
            <form method="POST" action="">

                <div class="form-group">
                    <label class="form-label">
                        Borrower Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="borrower_name" 
                        class="form-control" 
                        placeholder="Enter employee/borrower name"
                        list="borrowers-list"
                        value="<?php echo isset($_POST['borrower_name']) ? htmlspecialchars($_POST['borrower_name']) : ''; ?>"
                        required
                    >
                    <datalist id="borrowers-list">
                        <?php while($row = $borrowers_suggestions->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['borrower_name']); ?>">
                        <?php endwhile; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Tool/Item Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="tool_name" 
                        class="form-control" 
                        placeholder="Enter tool or item name (e.g., Hammer, Drill, Ladder)"
                        list="tools-list"
                        value="<?php echo isset($_POST['tool_name']) ? htmlspecialchars($_POST['tool_name']) : ''; ?>"
                        required
                    >
                    <datalist id="tools-list">
                        <?php while($row = $tools_suggestions->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['tool_name']); ?>">
                        <?php endwhile; ?>
                    </datalist>
                </div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Quantity <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="quantity" 
                                class="form-control" 
                                placeholder="Enter quantity"
                                min="1"
                                value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '1'; ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Expected Return Date (Optional)
                            </label>
                            <input 
                                type="date" 
                                name="expected_return_date" 
                                class="form-control"
                                min="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo isset($_POST['expected_return_date']) ? htmlspecialchars($_POST['expected_return_date']) : ''; ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Notes / Purpose (Optional)
                    </label>
                    <textarea 
                        name="notes" 
                        class="form-control" 
                        placeholder="Enter any additional notes or purpose for borrowing..."
                    ><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Submit Borrowing
                    </button>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>