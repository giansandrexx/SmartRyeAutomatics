document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const ddBtn  = document.getElementById('userDropdownBtn');
const ddMenu = document.getElementById('userDropdownMenu');
ddBtn.addEventListener('click', () => {
    ddBtn.classList.toggle('open');
    ddMenu.classList.toggle('open');
});
document.addEventListener('click', e => {
    if (!ddBtn.contains(e.target) && !ddMenu.contains(e.target)) {
        ddBtn.classList.remove('open');
        ddMenu.classList.remove('open');
    }
});

function p(n) {
    return '₱' + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function openPrint(ps) {
    const overlay = document.getElementById('printOverlay');

    const earningsRows = `
        <div class="slip-line"><span class="l">Daily Rate</span><span class="v">${p(ps.daily_rate)}</span></div>
        <div class="slip-line"><span class="l">Days Worked</span><span class="v">${ps.days_worked} days</span></div>
        <div class="slip-line"><span class="l">Basic Pay</span><span class="v pos">${p(ps.basic_pay)}</span></div>
        ${parseFloat(ps.ot_pay) > 0 ? `<div class="slip-line"><span class="l">Overtime Pay</span><span class="v pos">${p(ps.ot_pay)}</span></div>` : ''}
        ${parseFloat(ps.absent_deduction) > 0 ? `<div class="slip-line"><span class="l">Absent Deduction</span><span class="v neg">– ${p(ps.absent_deduction)}</span></div>` : ''}
        ${parseFloat(ps.late_deduction) > 0 ? `<div class="slip-line"><span class="l">Late Deduction</span><span class="v neg">– ${p(ps.late_deduction)}</span></div>` : ''}
    `;

    const deductionRows = `
        ${parseFloat(ps.sss) > 0 ? `<div class="slip-line"><span class="l">SSS</span><span class="v neg">– ${p(ps.sss)}</span></div>` : ''}
        ${parseFloat(ps.philhealth) > 0 ? `<div class="slip-line"><span class="l">PhilHealth</span><span class="v neg">– ${p(ps.philhealth)}</span></div>` : ''}
        ${parseFloat(ps.pagibig) > 0 ? `<div class="slip-line"><span class="l">Pag-IBIG</span><span class="v neg">– ${p(ps.pagibig)}</span></div>` : ''}
        ${parseFloat(ps.cash_advance) > 0 ? `<div class="slip-line"><span class="l">Cash Advance</span><span class="v neg">– ${p(ps.cash_advance)}</span></div>` : ''}
        ${parseFloat(ps.other_deductions) > 0 ? `<div class="slip-line"><span class="l">Other Deductions</span><span class="v neg">– ${p(ps.other_deductions)}</span></div>` : ''}
        ${(parseFloat(ps.sss) === 0 && parseFloat(ps.philhealth) === 0 && parseFloat(ps.pagibig) === 0 && parseFloat(ps.cash_advance) === 0 && parseFloat(ps.other_deductions) === 0)
            ? `<div class="slip-line"><span class="l" style="color:#8fa3be;font-style:italic;">No deductions</span><span class="v">—</span></div>` : ''}
    `;

    const dateFrom = new Date(ps.date_from).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    const dateTo   = new Date(ps.date_to).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    const created  = new Date(ps.created_at).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });

    document.getElementById('printArea').innerHTML = `
        <div class="slip-wrap">
            <div class="slip-header">
                <div>
                    <div class="slip-company">Smart Rye Automatics</div>
                    <div class="slip-subtitle">S Rye Enterprises — SRA Payroll System</div>
                </div>
                <div class="slip-title-right">
                    <div class="slip-payslip-lbl">Payslip</div>
                    <div class="slip-period">${dateFrom} – ${dateTo}</div>
                </div>
            </div>
            <div class="slip-emp-bar">
                <div class="slip-emp-field">
                    <span class="lbl">Employee</span>
                    <span class="val">${ps.emp_name}</span>
                </div>
                <div class="slip-emp-field">
                    <span class="lbl">Department</span>
                    <span class="val">${ps.department || '—'}</span>
                </div>
                ${ps.position ? `<div class="slip-emp-field"><span class="lbl">Position</span><span class="val">${ps.position}</span></div>` : ''}
                <div class="slip-emp-field">
                    <span class="lbl">Pay Period</span>
                    <span class="val">${dateFrom} – ${dateTo}</span>
                </div>
            </div>
            <div class="slip-body">
                <div class="slip-cols">
                    <div>
                        <div class="slip-section-title"><i class="fas fa-arrow-up"></i> Earnings</div>
                        ${earningsRows}
                    </div>
                    <div>
                        <div class="slip-section-title"><i class="fas fa-arrow-down"></i> Deductions</div>
                        ${deductionRows}
                    </div>
                </div>
            </div>
            <div class="slip-totals">
                <div class="slip-total-item">
                    <span class="tl">Gross Pay</span>
                    <span class="tv blue">${p(ps.gross_pay)}</span>
                </div>
                <div class="slip-total-item">
                    <span class="tl">Total Deductions</span>
                    <span class="tv red">${p(ps.total_deductions)}</span>
                </div>
                <div class="slip-total-item">
                    <span class="tl">Days Worked</span>
                    <span class="tv">${ps.days_worked}</span>
                </div>
                ${parseFloat(ps.late_minutes) > 0 ? `<div class="slip-total-item"><span class="tl">Late (mins)</span><span class="tv">${ps.late_minutes}</span></div>` : ''}
            </div>
            <div class="slip-net-bar">
                <span class="nl">Net Pay</span>
                <span class="nv">${p(ps.net_pay)}</span>
            </div>
            <div class="slip-footer">
                <span class="fl">Generated: ${created} · SRA Payroll System</span>
                <div class="slip-sig">
                    <div class="slip-sig-item">
                        <div class="sig-line"></div>
                        <div class="sig-lbl">Employee Signature</div>
                    </div>
                    <div class="slip-sig-item">
                        <div class="sig-line"></div>
                        <div class="sig-lbl">Authorized By</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    overlay.classList.add('open');
}

function closePrint() {
    document.getElementById('printOverlay').classList.remove('open');
}

document.getElementById('printOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePrint();
});