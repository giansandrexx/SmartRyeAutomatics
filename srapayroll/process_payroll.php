<?php
session_start();
date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['user_id'])) { header("Location: ../config.php"); exit(); }

require_once "../config.php";

$ps = [];
$r = $conn->query("SHOW TABLES LIKE 'payroll_settings'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("SELECT setting_key, setting_value FROM payroll_settings");
    if ($r) { while ($row = $r->fetch_assoc()) { $ps[$row['setting_key']] = (float)$row['setting_value']; } }
}
$setting_grace      = $ps['grace_period'] ?? 0;
$setting_sss        = $ps['sss']          ?? 600;
$setting_philhealth = $ps['philhealth']   ?? 300;
$setting_pagibig    = $ps['pagibig']      ?? 100;

$message      = '';
$message_type = '';

$employees = [];
$r = $conn->query("SELECT id, name, department, daily_rate FROM employees WHERE is_active = 1 ORDER BY name ASC");
if ($r) { while ($row = $r->fetch_assoc()) { $employees[] = $row; } }

$cash_advances = [];
$r = $conn->query("SHOW TABLES LIKE 'cash_advances'");
if ($r && $r->num_rows > 0) {
    $r = $conn->query("
        SELECT ca.employee_id,
               SUM(ca.amount) as total,
               GROUP_CONCAT(CONCAT('₱', FORMAT(ca.amount,2), ' (', DATE_FORMAT(ca.date_given,'%b %d'), ')') ORDER BY ca.date_given SEPARATOR ', ') as breakdown
        FROM cash_advances ca
        WHERE ca.status = 'pending'
        GROUP BY ca.employee_id
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cash_advances[$row['employee_id']] = [
                'total'     => (float)$row['total'],
                'breakdown' => $row['breakdown']
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id       = (int)$_POST['employee_id'];
    $date_from         = $conn->real_escape_string($_POST['date_from']);
    $date_to           = $conn->real_escape_string($_POST['date_to']);
    $days_worked       = (float)$_POST['days_worked'];
    $absent_days       = (float)($_POST['absent_days']       ?? 0);
    $late_minutes      = (float)($_POST['late_minutes']      ?? 0);
    $undertime_minutes = (float)($_POST['undertime_minutes'] ?? 0);
    $ot_hours          = (float)($_POST['ot_hours']          ?? 0);
    $other_deduct      = (float)($_POST['other_deductions']  ?? 0);
    $sss               = (float)($_POST['sss']               ?? 0);
    $philhealth        = (float)($_POST['philhealth']        ?? 0);
    $pagibig           = (float)($_POST['pagibig']           ?? 0);
    $remarks           = $conn->real_escape_string($_POST['remarks'] ?? '');
    $apply_gov         = isset($_POST['apply_gov']) ? 1 : 0;

    $emp_r = $conn->query("SELECT daily_rate, department FROM employees WHERE id = $employee_id");
    if ($emp_r && $emp_r->num_rows > 0) {
        $emp_data      = $emp_r->fetch_assoc();
        $daily_rate    = (float)$emp_data['daily_rate'];
        $is_field      = strtolower(trim($emp_data['department'] ?? '')) === 'field';
        $hours_per_day = $is_field ? 10 : 8;
        $hourly_rate   = $daily_rate / $hours_per_day;

        $basic_pay        = $daily_rate * $days_worked;
        $ot_pay           = $hourly_rate * 1.25 * $ot_hours;
        $late_deduct      = ($hourly_rate / 60) * max(0, $late_minutes - $setting_grace);
        $undertime_deduct = ($hourly_rate / 60) * $undertime_minutes;
        $gross_pay        = $basic_pay + $ot_pay - $late_deduct - $undertime_deduct;

        $gov_sss = $apply_gov ? $sss        : 0;
        $gov_ph  = $apply_gov ? $philhealth : 0;
        $gov_pi  = $apply_gov ? $pagibig    : 0;
        $ca      = isset($cash_advances[$employee_id]) ? (float)$cash_advances[$employee_id]['total'] : 0;

        $total_deductions = $gov_sss + $gov_ph + $gov_pi + $ca + $other_deduct;
        $net_pay          = $gross_pay - $total_deductions;

        $conn->query("CREATE TABLE IF NOT EXISTS payroll_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            days_worked DECIMAL(5,2) NOT NULL DEFAULT 0,
            absent_days DECIMAL(5,2) NOT NULL DEFAULT 0,
            late_minutes DECIMAL(8,2) NOT NULL DEFAULT 0,
            undertime_minutes DECIMAL(8,2) NOT NULL DEFAULT 0,
            ot_hours DECIMAL(5,2) NOT NULL DEFAULT 0,
            daily_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
            basic_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            ot_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            late_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
            undertime_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
            gross_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            sss DECIMAL(12,2) NOT NULL DEFAULT 0,
            philhealth DECIMAL(12,2) NOT NULL DEFAULT 0,
            pagibig DECIMAL(12,2) NOT NULL DEFAULT 0,
            cash_advance DECIMAL(12,2) NOT NULL DEFAULT 0,
            other_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
            net_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            remarks TEXT,
            created_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $conn->prepare("INSERT INTO payroll_entries (
            employee_id, date_from, date_to,
            days_worked, absent_days, late_minutes, undertime_minutes, ot_hours,
            daily_rate, basic_pay, ot_pay,
            late_deduction, undertime_deduction, gross_pay,
            sss, philhealth, pagibig, cash_advance, other_deductions, total_deductions, net_pay,
            remarks, created_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param("issddddddddddddddddddsi",
            $employee_id, $date_from, $date_to,
            $days_worked, $absent_days, $late_minutes, $undertime_minutes, $ot_hours,
            $daily_rate, $basic_pay, $ot_pay,
            $late_deduct, $undertime_deduct, $gross_pay,
            $gov_sss, $gov_ph, $gov_pi, $ca, $other_deduct, $total_deductions, $net_pay,
            $remarks, $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $message      = "Payroll saved! Net Pay: ₱" . number_format($net_pay, 2);
            $message_type = 'success';
            if ($ca > 0) {
                $conn->query("UPDATE cash_advances SET status = 'deducted' WHERE employee_id = $employee_id AND status = 'pending'");
            }
        } else {
            $message      = "Error: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message      = "Employee not found.";
        $message_type = 'error';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRA Payroll</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../sratool/img/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../srapayroll/css/process.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="page-layout">
    <div class="page-header">
        <h2>Process Payroll</h2>
        <p>Compute and save payroll entries for individual employees.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <i class="fas fa-<?= $message_type==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="payroll-grid">
        <div class="form-card">
            <div class="card-head"><i class="fas fa-money-check-alt"></i><h3>Payroll Entry Form</h3></div>
            <div class="card-body">
                <form method="POST">

                    <div class="section-title"><i class="fas fa-user"></i> Employee</div>
                    <div class="form-row single">
                        <div class="form-group">
                            <label>Employee <span class="req">*</span></label>
                            <select class="fc" name="employee_id" id="empSelect" required>
                                <option value="">— Select Employee —</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"
                                    data-rate="<?= (float)$emp['daily_rate'] ?>"
                                    data-dept="<?= htmlspecialchars($emp['department'] ?? '') ?>"
                                    data-name="<?= htmlspecialchars($emp['name']) ?>">
                                    <?= htmlspecialchars($emp['name']) ?> — ₱<?= number_format($emp['daily_rate'],0) ?>/day
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="emp-banner" id="empBanner">
                        <div class="emp-avatar"><i class="fas fa-id-badge"></i></div>
                        <div>
                            <div class="emp-name" id="bannerName">—</div>
                            <div class="emp-detail" id="bannerDept">—</div>
                        </div>
                        <div class="emp-rate-wrap">
                            <div class="rate-lbl">Daily Rate</div>
                            <div class="rate-val" id="bannerRate">₱0</div>
                        </div>
                    </div>

                    <div class="fetch-banner" id="fetchBanner" style="display:none">
                        <i class="fas fa-circle-notch fetch-icon" id="fetchIcon"></i>
                        <span id="fetchStatus">Fetching attendance…</span>
                        <button type="button" class="fetch-refetch-btn" id="fetchBtn">
                            <i class="fas fa-sync-alt"></i> Re-fetch
                        </button>
                    </div>

                    <div class="section-title"><i class="fas fa-calendar"></i> Pay Period</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date From <span class="req">*</span></label>
                            <input type="date" class="fc" name="date_from" id="dateFrom" required>
                        </div>
                        <div class="form-group">
                            <label>Date To <span class="req">*</span></label>
                            <input type="date" class="fc" name="date_to" id="dateTo" required>
                        </div>
                    </div>

                    <div class="section-title"><i class="fas fa-clipboard-check"></i> Attendance</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Days Worked <span class="req">*</span><span class="hint" id="daysHint">Max 15/period</span></label>
                            <input type="number" class="fc" name="days_worked" id="daysWorked" min="0" max="15" step="0.5" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label>Absent Days <span class="hint"></span>For record only</label>
                            <input type="number" class="fc" name="absent_days" id="absentDays" min="0" step="0.5" placeholder="0">
                        </div>
                    </div>

                    <div class="section-title"><i class="fas fa-sliders-h"></i> Adjustments</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Late <span class="hint" id="lateHint">minutes</span></label>
                            <input type="number" class="fc" name="late_minutes" id="lateMinutes" min="0" step="1" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Undertime <span class="hint" id="undertimeHint">minutes</span></label>
                            <input type="number" class="fc" name="undertime_minutes" id="undertimeMinutes" min="0" step="1" placeholder="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Overtime <span class="hint">hours · ×1.25</span></label>
                            <input type="number" class="fc" name="ot_hours" id="otHours" min="0" step="0.5" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Other Deductions (₱)</label>
                            <input type="number" class="fc" name="other_deductions" id="otherDeductions" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div class="section-title"><i class="fas fa-landmark"></i> Government Contributions</div>
                    <div class="gov-row">
                        <span class="gov-lbl">Apply government deductions?</span>
                        <label class="toggle"><input type="checkbox" name="apply_gov" id="applyGov"><span class="slider"></span></label>
                    </div>
                    <div class="gov-fields" id="govFields">
                        <div class="gf-group"><label>SSS</label><input type="number" class="fc" name="sss" id="sssInput" value="<?= $setting_sss ?>" min="0" step="0.01"></div>
                        <div class="gf-group"><label>PhilHealth</label><input type="number" class="fc" name="philhealth" id="philhealthInput" value="<?= $setting_philhealth ?>" min="0" step="0.01"></div>
                        <div class="gf-group"><label>Pag-IBIG</label><input type="number" class="fc" name="pagibig" id="pagibigInput" value="<?= $setting_pagibig ?>" min="0" step="0.01"></div>
                    </div>

                    <div class="section-title"><i class="fas fa-comment-alt"></i> Remarks</div>
                    <div class="form-row single">
                        <div class="form-group">
                            <label>Add Notes</label>
                            <textarea class="fc" name="remarks" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Process &amp; Save Payroll</button>
                </form>
            </div>
        </div>

        <div class="compute-card">
            <div class="c-head"><h3><i class="fas fa-calculator"></i> Live Computation</h3></div>
            <div>
                <div class="c-section">
                    <div class="c-stitle">Earnings</div>
                    <div class="c-row"><span class="l">Daily Rate</span><span class="v" id="cRate">₱0.00</span></div>
                    <div class="c-row"><span class="l">Hourly Rate</span><span class="v" id="cHourly">₱0.00</span></div>
                    <div class="c-row"><span class="l" id="cBasicLbl">Basic Pay (0 days)</span><span class="v pos" id="cBasic">₱0.00</span></div>
                    <div class="c-row"><span class="l" id="cOtLbl">Overtime Pay (0h × 1.25)</span><span class="v pos" id="cOt">₱0.00</span></div>
                </div>
                <div class="c-section">
                    <div class="c-stitle">Attendance Deductions</div>
                    <div class="c-row"><span class="l" id="cLateLbl">Late (0 min)</span><span class="v neg" id="cLate">– ₱0.00</span></div>
                    <div class="c-row"><span class="l" id="cUndertimeLbl">Undertime (0 min)</span><span class="v neg" id="cUndertime">– ₱0.00</span></div>
                </div>
                <div class="gross-row"><span class="l">GROSS PAY</span><span class="v" id="cGross">₱0.00</span></div>
                <div class="c-section">
                    <div class="c-stitle">Other Deductions</div>
                    <div id="govNoneTxt" class="gov-none-txt">Government deductions not applied</div>
                    <div id="govApplied" style="display:none;">
                        <div class="c-row"><span class="l">SSS</span><span class="v neg" id="cSss">₱0.00</span></div>
                        <div class="c-row"><span class="l">PhilHealth</span><span class="v neg" id="cPh">₱0.00</span></div>
                        <div class="c-row"><span class="l">Pag-IBIG</span><span class="v neg" id="cPi">₱0.00</span></div>
                    </div>
                    <div class="c-row"><span class="l">Cash Advance</span><span class="v neg" id="cCa">₱0.00</span></div>
                    <div class="c-row"><span class="l">Other</span><span class="v neg" id="cOther">₱0.00</span></div>
                    <div class="c-row" style="border-top:1px solid var(--border-light);margin-top:6px;padding-top:8px;">
                        <span class="l" style="font-weight:700;color:var(--text-primary);">Total Deductions</span>
                        <span class="v neg" style="font-size:14px;" id="cTotal">₱0.00</span>
                    </div>
                </div>
                <div class="net-section">
                    <div class="net-lbl">NET PAY</div>
                    <div class="net-val"><span class="cur">₱</span><span id="cNet">0.00</span></div>
                    <div class="net-sub" id="cSub">Select an employee to begin</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const EMP = <?php echo json_encode(array_column($employees, null, 'id')); ?>;
const CA  = <?php echo json_encode($cash_advances); ?>;
const SETTINGS = <?php echo json_encode([
    'grace'      => $setting_grace,
    'sss'        => $setting_sss,
    'philhealth' => $setting_philhealth,
    'pagibig'    => $setting_pagibig,
]); ?>;

document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

const ddBtn  = document.getElementById('userDropdownBtn');
const ddMenu = document.getElementById('userDropdownMenu');
ddBtn.addEventListener('click', () => { ddBtn.classList.toggle('open'); ddMenu.classList.toggle('open'); });
document.addEventListener('click', e => {
    if (!ddBtn.contains(e.target) && !ddMenu.contains(e.target)) {
        ddBtn.classList.remove('open'); ddMenu.classList.remove('open');
    }
});

const p = n => '₱' + parseFloat(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});

function compute() {
    const id          = document.getElementById('empSelect').value;
    const emp         = id ? EMP[id] : null;
    const isField     = emp ? (emp.department||'').toLowerCase().trim() === 'field' : false;
    const hoursPerDay = isField ? 10 : 8;
    const maxDays     = isField ? 6 : 15;
    const rate        = emp ? parseFloat(emp.daily_rate) : 0;
    const hourlyRate  = rate > 0 ? rate / hoursPerDay : 0;
    const perMin      = hourlyRate / 60;

    document.getElementById('daysWorked').max            = maxDays;
    document.getElementById('daysHint').textContent      = isField ? 'Max 6/week' : 'Max 15/period';
    document.getElementById('lateHint').textContent      = emp ? `minutes · ₱${perMin.toFixed(4)}/min` : 'minutes';
    document.getElementById('undertimeHint').textContent = emp ? `minutes · ₱${perMin.toFixed(4)}/min` : 'minutes';

    const days      = parseFloat(document.getElementById('daysWorked').value)       || 0;
    const late      = parseFloat(document.getElementById('lateMinutes').value)      || 0;
    const undertime = parseFloat(document.getElementById('undertimeMinutes').value) || 0;
    const ot        = parseFloat(document.getElementById('otHours').value)          || 0;
    const other     = parseFloat(document.getElementById('otherDeductions').value)  || 0;
    const gov       = document.getElementById('applyGov').checked;
    const sss       = gov ? (parseFloat(document.getElementById('sssInput').value)        || 0) : 0;
    const ph        = gov ? (parseFloat(document.getElementById('philhealthInput').value) || 0) : 0;
    const pi        = gov ? (parseFloat(document.getElementById('pagibigInput').value)    || 0) : 0;
    const caObj     = id ? CA[id] : null;
    const ca        = caObj ? caObj.total : 0;

    const basic        = rate * days;
    const otPay        = hourlyRate * 1.25 * ot;
    const lateDed      = perMin * Math.max(0, late - SETTINGS.grace);
    const undertimeDed = perMin * undertime;
    const gross        = basic + otPay - lateDed - undertimeDed;
    const totDed       = sss + ph + pi + ca + other;
    const net          = gross - totDed;

    document.getElementById('cRate').textContent         = p(rate);
    document.getElementById('cHourly').textContent       = p(hourlyRate);
    document.getElementById('cBasicLbl').textContent     = 'Basic Pay (' + days + ' days)';
    document.getElementById('cBasic').textContent        = p(basic);
    document.getElementById('cOtLbl').textContent        = `Overtime Pay (${ot}h × 1.25)`;
    document.getElementById('cOt').textContent           = p(otPay);
    document.getElementById('cLateLbl').textContent      = `Late (${late} min)`;
    document.getElementById('cLate').textContent         = '– ' + p(lateDed);
    document.getElementById('cUndertimeLbl').textContent = `Undertime (${undertime} min)`;
    document.getElementById('cUndertime').textContent    = '– ' + p(undertimeDed);
    document.getElementById('cGross').textContent        = p(gross);
    document.getElementById('govNoneTxt').style.display  = gov ? 'none'  : 'block';
    document.getElementById('govApplied').style.display  = gov ? 'block' : 'none';
    document.getElementById('cSss').textContent          = p(sss);
    document.getElementById('cPh').textContent           = p(ph);
    document.getElementById('cPi').textContent           = p(pi);
    const caEl = document.getElementById('cCa');
    caEl.textContent = p(ca);
    caEl.title = caObj ? (caObj.breakdown || '') : '';
    document.getElementById('cOther').textContent        = p(other);
    document.getElementById('cTotal').textContent        = p(totDed);
    document.getElementById('cNet').textContent          =
        parseFloat(net||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});

    const sub = document.getElementById('cSub');
    if (!emp) {
        sub.textContent = 'Select an employee to begin';
        sub.style.color = 'var(--text-muted)';
    } else if (net < 0) {
        sub.textContent = '⚠ Net pay is negative — check deductions';
        sub.style.color = 'var(--red-500)';
    } else {
        sub.textContent = emp.name + ' · ' + days + ' days · ' + (isField ? 'Weekly' : 'Semi-Monthly');
        sub.style.color = 'var(--text-muted)';
    }
}

async function fetchAttendanceSummary(empId) {
    const banner = document.getElementById('fetchBanner');
    const status = document.getElementById('fetchStatus');
    const icon   = document.getElementById('fetchIcon');
    const btn    = document.getElementById('fetchBtn');

    banner.style.display = 'flex';
    banner.className     = 'fetch-banner loading';
    status.textContent   = 'Fetching attendance data…';
    icon.className       = 'fas fa-circle-notch fa-spin fetch-icon';
    btn.disabled         = true;

    try {
        const res  = await fetch('get_payroll_summary.php?employee_id=' + empId);
        const data = await res.json();

        if (!data.success) {
            banner.className   = 'fetch-banner error';
            status.textContent = 'Could not load attendance: ' + (data.message || 'Unknown error');
            icon.className     = 'fas fa-exclamation-triangle fetch-icon';
            btn.disabled       = false;
            return;
        }

        document.getElementById('dateFrom').value         = data.date_from;
        document.getElementById('dateTo').value           = data.date_to;
        document.getElementById('daysWorked').value       = data.days_worked;
        document.getElementById('absentDays').value       = data.absent_days;
        document.getElementById('lateMinutes').value      = data.late_minutes;
        document.getElementById('undertimeMinutes').value = data.undertime_minutes;
        document.getElementById('otHours').value          = data.ot_hours;

        banner.className   = 'fetch-banner success';
        icon.className     = 'fas fa-check-circle fetch-icon';
        status.textContent = data.period_label + ' · ' + data.date_from + ' → ' + data.date_to + ' · Edit any field to adjust.';
        compute();
    } catch (err) {
        banner.className   = 'fetch-banner error';
        status.textContent = 'Fetch error — check connection.';
        icon.className     = 'fas fa-exclamation-triangle fetch-icon';
    }

    btn.disabled = false;
}

document.getElementById('empSelect').addEventListener('change', function () {
    const emp    = this.value ? EMP[this.value] : null;
    const banner = document.getElementById('empBanner');

    if (emp) {
        const isField = (emp.department||'').toLowerCase().trim() === 'field';
        document.getElementById('bannerName').textContent = emp.name;
        document.getElementById('bannerDept').textContent =
            (emp.department||'No dept') + (isField ? ' · Weekly' : ' · Semi-Monthly');
        document.getElementById('bannerRate').textContent = p(emp.daily_rate) + '/day';
        banner.classList.add('show');
        fetchAttendanceSummary(this.value);
    } else {
        banner.classList.remove('show');
        document.getElementById('fetchBanner').style.display = 'none';
    }
    compute();
});

document.getElementById('fetchBtn').addEventListener('click', function () {
    const id = document.getElementById('empSelect').value;
    if (id) fetchAttendanceSummary(id);
});

['daysWorked','absentDays','lateMinutes','undertimeMinutes','otHours','otherDeductions',
 'sssInput','philhealthInput','pagibigInput'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) { el.addEventListener('input', compute); el.addEventListener('change', compute); }
});

document.getElementById('applyGov').addEventListener('change', function () {
    document.getElementById('govFields').style.opacity       = this.checked ? '1'    : '0.4';
    document.getElementById('govFields').style.pointerEvents = this.checked ? 'auto' : 'none';
    compute();
});

compute();
</script>
</body>
</html>
