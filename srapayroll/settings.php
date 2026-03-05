<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$conn->query("CREATE TABLE IF NOT EXISTS payroll_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$defaults = [
    'sss'              => '600',
    'philhealth'       => '300',
    'pagibig'          => '100',
    'office_days'      => '15',
    'fabrication_days' => '6',
    'late_rate'        => '2.5',
    'grace_period'     => '0',
    'ot_rate'          => '150',
];

foreach ($defaults as $key => $val) {
    $conn->query("INSERT IGNORE INTO payroll_settings (setting_key, setting_value) VALUES ('$key', '$val')");
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['sss','philhealth','pagibig','office_days','fabrication_days','late_rate','grace_period','ot_rate'];
    $ok = true;
    foreach ($fields as $field) {
        $val = $conn->real_escape_string($_POST[$field] ?? '0');
        $r = $conn->query("UPDATE payroll_settings SET setting_value='$val' WHERE setting_key='$field'");
        if (!$r) { $ok = false; }
    }
    $message = $ok ? 'Settings saved successfully.' : 'Error saving settings.';
    $message_type = $ok ? 'success' : 'error';
}

$settings = [];
$r = $conn->query("SELECT setting_key, setting_value FROM payroll_settings");
if ($r) { while ($row = $r->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; } }

$conn->close();

function sv($settings, $key, $default = '0') {
    return htmlspecialchars($settings[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll — Settings</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/process.css">
    <link rel="stylesheet" href="css/settings.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">

    <div class="page-header">
        <h2>Settings</h2>
        <p>Configure payroll rules, deduction rates, and pay period categories.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <i class="fas fa-<?= $message_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="settingsForm">

        <div class="settings-grid">

            <div class="settings-card">
                <div class="settings-card-head">
                    <div class="scard-icon"><i class="fas fa-landmark"></i></div>
                    <div>
                        <div class="scard-title">Government Benefits</div>
                        <div class="scard-desc">Fixed amounts deducted on the last period of each month</div>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-label">
                            <span class="slbl">SSS</span>
                            <span class="sunit">₱ / month</span>
                        </div>
                        <div class="setting-input-wrap">
                            <span class="input-prefix">₱</span>
                            <input type="number" class="setting-input" name="sss" value="<?= sv($settings,'sss','600') ?>" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="setting-row">
                        <div class="setting-label">
                            <span class="slbl">PhilHealth</span>
                            <span class="sunit">₱ / month</span>
                        </div>
                        <div class="setting-input-wrap">
                            <span class="input-prefix">₱</span>
                            <input type="number" class="setting-input" name="philhealth" value="<?= sv($settings,'philhealth','300') ?>" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="setting-row">
                        <div class="setting-label">
                            <span class="slbl">Pag-IBIG</span>
                            <span class="sunit">₱ / month</span>
                        </div>
                        <div class="setting-input-wrap">
                            <span class="input-prefix">₱</span>
                            <input type="number" class="setting-input" name="pagibig" value="<?= sv($settings,'pagibig','100') ?>" min="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-head">
                    <div class="scard-icon green"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="scard-title">Pay Period Categories</div>
                        <div class="scard-desc">Number of working days per pay period per category</div>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="setting-row">
                        <div class="setting-label">
                            <span class="slbl">Office — Period Days</span>
                            <span class="sunit">Paid every 15 days (2 periods/month)</span>
                        </div>
                        <div class="setting-input-wrap">
                            <input type="number" class="setting-input no-prefix" name="office_days" value="<?= sv($settings,'office_days','15') ?>" min="1" max="31" step="1">
                            <span class="input-suffix">days</span>
                        </div>
                    </div>
                    <div class="setting-info-box">
                        <i class="fas fa-info-circle"></i>
                        Office employees are paid every <?= sv($settings,'office_days','15') ?> days (2 periods/month)
                    </div>
                    <div class="setting-row" style="margin-top:14px;">
                        <div class="setting-label">
                            <span class="slbl">Fabrication — Period Days</span>
                            <span class="sunit">Paid every 6 days (5 periods/month)</span>
                        </div>
                        <div class="setting-input-wrap">
                            <input type="number" class="setting-input no-prefix" name="fabrication_days" value="<?= sv($settings,'fabrication_days','6') ?>" min="1" max="31" step="1">
                            <span class="input-suffix">days</span>
                        </div>
                    </div>
                    <div class="setting-info-box">
                        <i class="fas fa-info-circle"></i>
                        Fabrication employees are paid every <?= sv($settings,'fabrication_days','6') ?> days (5 periods/month)
                    </div>
                </div>
            </div>

            <div class="settings-card full-width">
                <div class="settings-card-head">
                    <div class="scard-icon amber"><i class="fas fa-sliders-h"></i></div>
                    <div>
                        <div class="scard-title">Attendance Rules</div>
                        <div class="scard-desc">Late deduction rate, grace period, and overtime pay rate</div>
                    </div>
                </div>
                <div class="settings-card-body">
                    <div class="attendance-rules-grid">
                        <div class="setting-row">
                            <div class="setting-label">
                                <span class="slbl">Late Deduction</span>
                                <span class="sunit">₱ per minute</span>
                            </div>
                            <div class="setting-input-wrap">
                                <span class="input-prefix">₱</span>
                                <input type="number" class="setting-input" name="late_rate" value="<?= sv($settings,'late_rate','2.5') ?>" min="0" step="0.01">
                                <span class="input-suffix">/ min</span>
                            </div>
                        </div>
                        <div class="setting-row">
                            <div class="setting-label">
                                <span class="slbl">Grace Period</span>
                                <span class="sunit">minutes before late penalty applies</span>
                            </div>
                            <div class="setting-input-wrap">
                                <input type="number" class="setting-input no-prefix" name="grace_period" value="<?= sv($settings,'grace_period','0') ?>" min="0" step="1">
                                <span class="input-suffix">min</span>
                            </div>
                        </div>
                        <div class="setting-row">
                            <div class="setting-label">
                                <span class="slbl">OT Rate</span>
                                <span class="sunit">₱ per hour</span>
                            </div>
                            <div class="setting-input-wrap">
                                <span class="input-prefix">₱</span>
                                <input type="number" class="setting-input" name="ot_rate" value="<?= sv($settings,'ot_rate','150') ?>" min="0" step="0.01">
                                <span class="input-suffix">/ hr</span>
                            </div>
                        </div>
                    </div>
                    <div class="rules-note">
                        <div class="rule-item"><i class="fas fa-circle"></i> Employees late by <?= sv($settings,'grace_period','0') ?> min or less are not penalized (grace period).</div>
                        <div class="rule-item"><i class="fas fa-circle"></i> Beyond grace: ₱<?= sv($settings,'late_rate','2.5') ?>/minute is deducted.</div>
                        <div class="rule-item"><i class="fas fa-circle"></i> Overtime: ₱<?= sv($settings,'ot_rate','150') ?>/hour added to gross pay.</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="settings-footer">
            <button type="submit" class="btn-save-settings">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>

    </form>

</div>

<div id="sra-toast"><i class="fas fa-check-circle"></i><span id="toastMsg"></span></div>

<script src="../srapayroll/js/settings.js"></script>

<script>
const toast = document.getElementById('sra-toast');
document.getElementById('toastMsg').textContent = <?= json_encode($message) ?>;
toast.classList.add('show');
setTimeout(() => toast.classList.remove('show'), 3000);
</script>
</body>

</html>
