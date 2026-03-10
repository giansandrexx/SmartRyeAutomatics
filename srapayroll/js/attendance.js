const API               = 'attendance_api.php';
const WORK_START_FIELD  = 7  * 60;
const WORK_START_OFFICE = 8  * 60;
const WORK_END          = 17 * 60;

let employees     = [];
let attData       = {};
let currentYear   = new Date().getFullYear();
let currentMonth  = new Date().getMonth() + 1;
let deptFilter    = '';
let searchQuery   = '';

function toMin(t) {
    if (!t) return null;
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

function to12hr(t) {
    if (!t) return '—';
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12  = h % 12 || 12;
    return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
}

function monthName(y, m) {
    return new Date(y, m - 1, 1).toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
}
function daysInMonth(y, m) {
    return new Date(y, m, 0).getDate();
}
function dayOfWeek(y, m, d) {
    return new Date(y, m - 1, d).getDay();
}
const DAY_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

function isSunday(dow)   { return dow === 0; }
function isSaturday(dow) { return dow === 6; }

function isToday(y, m, d) {
    const now = new Date();
    return now.getFullYear() === y && (now.getMonth()+1) === m && now.getDate() === d;
}
function initials(name) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

function dayStatus(empId, dateStr, dept) {
    const rec = (attData[empId] || {})[dateStr] || {};
    const ti  = rec.in, to = rec.out;
    if (!ti && !to) return 'absent';
    const tiMin  = toMin(ti);
    const toMin_ = toMin(to);
    const cut    = dept === 'Field' ? WORK_START_FIELD : WORK_START_OFFICE;
    if (tiMin  && tiMin  > cut)      return 'late';
    if (toMin_ && toMin_ < WORK_END) return 'undertime';
    return 'present';
}

async function fetchJSON(url) {
    const res  = await fetch(url);
    if (res.status === 403) { window.location.href = '../config.php'; return null; }
    const text = await res.text();
    try { return JSON.parse(text); }
    catch(e) { console.error('Non-JSON:', text); return null; }
}

async function loadData() {
    loading(true);
    const empUrl = `${API}?action=employees${deptFilter ? '&dept=' + encodeURIComponent(deptFilter) : ''}`;
    const attUrl = `${API}?action=month&year=${currentYear}&month=${currentMonth}`;
    const [emps, att] = await Promise.all([fetchJSON(empUrl), fetchJSON(attUrl)]);
    employees = Array.isArray(emps) ? emps : [];
    attData   = (att && typeof att === 'object') ? att : {};
    loading(false);
    render();
}

function render() {
    const q        = searchQuery.toLowerCase().trim();
    const filtered = employees.filter(e =>
        !q ||
        e.name.toLowerCase().includes(q) ||
        (e.department || '').toLowerCase().includes(q) ||
        (e.employee_id || '').toLowerCase().includes(q) ||
        (e.position || '').toLowerCase().includes(q)
    );

    const tbody = document.getElementById('attBody');
    const thead = document.getElementById('attHead');

    if (!filtered.length) {
        thead.innerHTML = '';
        tbody.innerHTML = `<tr><td colspan="33" class="av-no-results"><i class="fas fa-users-slash"></i>No employees found.</td></tr>`;
        return;
    }

    const days = daysInMonth(currentYear, currentMonth);

    let monthCols = `<th class="av-emp-col">Employee</th>`;
    for (let d = 1; d <= days; d++) {
        const dow     = dayOfWeek(currentYear, currentMonth, d);
        const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        let cls = '';
        if (isSunday(dow))        cls = 'av-sunday-col';
        else if (isSaturday(dow)) cls = 'av-sat-col';
        else if (isToday(currentYear, currentMonth, d)) cls = 'av-today-col';
        monthCols += `<th class="${cls}" data-date="${dateStr}">
            <span class="av-day-num">${d}</span>
            <span class="av-day-name">${DAY_NAMES[dow]}</span>
        </th>`;
    }
    thead.innerHTML = `<tr class="av-day-row">${monthCols}</tr>`;

    const groups = {};
    filtered.forEach(e => {
        const d = e.department || 'Other';
        if (!groups[d]) groups[d] = [];
        groups[d].push(e);
    });

    let html = '';
    for (const dept of ['Field', 'Office', 'Other']) {
        if (!groups[dept]?.length) continue;
        const icon = dept === 'Field' ? 'fa-hard-hat' : 'fa-building';
        html += `<tr class="av-dept-row">
            <td colspan="${days + 1}">
                <i class="fas ${icon}" style="font-size:10px;margin-right:6px;opacity:.7"></i>${dept}
                <span class="dept-count-badge">${groups[dept].length}</span>
            </td>
        </tr>`;

        for (const emp of groups[dept]) {
            const empIdDisplay = emp.employee_id || '#' + String(emp.id).padStart(3,'0');
            html += `<tr>
                <td class="av-emp-sticky">
                    <div class="av-emp-name">
                        <div class="av-emp-avatar" style="background:linear-gradient(${emp.color || '135deg,#1245a8,#42a5f5'})">${initials(emp.name)}</div>
                        <div>
                            <div>${emp.name}</div>
                            <div style="font-size:10px;color:var(--muted);font-family:'JetBrains Mono',monospace;">${empIdDisplay}</div>
                        </div>
                    </div>
                    <div class="av-emp-sub">${emp.department}${emp.position ? ' · ' + emp.position : ''}</div>
                </td>`;

            for (let d = 1; d <= days; d++) {
                const dow     = dayOfWeek(currentYear, currentMonth, d);
                const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const rec     = (attData[emp.id] || {})[dateStr] || {};

                let dotCls, status, cellCls;

                if (isSunday(dow)) {
                    dotCls  = 'sunday';
                    status  = 'sunday';
                    cellCls = 'av-sunday';
                } else if (isSaturday(dow)) {
    cellCls = 'av-saturday';
    if (rec.in || rec.out) {
        status = dayStatus(emp.id, dateStr, emp.department);
        dotCls = status;
    } else {
        const cellDate = new Date(dateStr + 'T00:00:00');
        const today    = new Date(); today.setHours(0, 0, 0, 0);
        if (cellDate < today) {
            dotCls = 'absent';
            status = 'absent';
        } else {
            dotCls = 'empty';
            status = 'none';
        }
    }
} else {
    cellCls = isToday(currentYear, currentMonth, d) ? 'av-today-cell' : '';
    if (rec.in || rec.out) {
        status = dayStatus(emp.id, dateStr, emp.department);
        dotCls = status;
    } else {
        const cellDate = new Date(dateStr + 'T00:00:00');
        const today    = new Date(); today.setHours(0,0,0,0);
        if (cellDate < today) {
            dotCls = 'absent';
            status = 'absent';
        } else {
            dotCls = 'empty';
            status = 'none';
        }
    }
}

                html += `<td class="av-day-cell ${cellCls}"
                    data-emp-id="${emp.id}"
                    data-emp-name="${emp.name}"
                    data-date="${dateStr}"
                    data-in="${rec.in || ''}"
                    data-out="${rec.out || ''}"
                    data-status="${status}"
                    data-dept="${emp.department}"
                ><span class="av-dot ${dotCls}"></span></td>`;
            }
            html += `</tr>`;
        }
    }
    tbody.innerHTML = html;
}

const popover = document.getElementById('avPopover');
let popHideTimer = null;

function showPopover(cell) {
    clearTimeout(popHideTimer);
    const status = cell.dataset.status;
    if (status === 'sunday') { hidePopover(); return; }

    const name    = cell.dataset.empName;
    const date    = cell.dataset.date;
    const timeIn  = cell.dataset.in;
    const timeOut = cell.dataset.out;
    const dept    = cell.dataset.dept;

    const dateLabel = new Date(date + 'T00:00:00').toLocaleDateString('en-PH', {
        weekday:'long', month:'long', day:'numeric', year:'numeric'
    });

    let rowsHtml = '';
    if (status === 'none') {
        rowsHtml = `<div class="av-pop-row"><span class="av-pop-label">Status</span><span class="av-pop-val red">No Record</span></div>`;
    } else {
        const tiMin    = toMin(timeIn);
        const toMin_   = toMin(timeOut);
        const cut      = dept === 'Field' ? WORK_START_FIELD : WORK_START_OFFICE;
        const lateMin  = tiMin  ? Math.max(0, tiMin  - cut)      : 0;
        const underMin = toMin_ ? Math.max(0, WORK_END - toMin_) : 0;
        const workedH  = (tiMin && toMin_) ? ((toMin_ - tiMin) / 60).toFixed(1) + 'h' : '—';

        const statusLabel = { present:'Present', late:'Late', undertime:'Undertime' }[status] || status;
        const statusColor = { present:'green',   late:'amber', undertime:'orange'   }[status] || '';

        rowsHtml = `
            <div class="av-pop-row"><span class="av-pop-label">Status</span><span class="av-pop-val ${statusColor}">${statusLabel}</span></div>
            <div class="av-pop-row"><span class="av-pop-label">Time In</span><span class="av-pop-val">${to12hr(timeIn)}</span></div>
            <div class="av-pop-row"><span class="av-pop-label">Time Out</span><span class="av-pop-val">${to12hr(timeOut)}</span></div>
            <div class="av-pop-row"><span class="av-pop-label">Hours</span><span class="av-pop-val">${workedH}</span></div>
            ${lateMin  > 0 ? `<div class="av-pop-row"><span class="av-pop-label">Late</span><span class="av-pop-val amber">${lateMin}m</span></div>`      : ''}
            ${underMin > 0 ? `<div class="av-pop-row"><span class="av-pop-label">Undertime</span><span class="av-pop-val orange">${underMin}m</span></div>` : ''}
        `;
    }

    document.getElementById('popName').textContent = name;
    document.getElementById('popDate').textContent = dateLabel;
    document.getElementById('popRows').innerHTML   = rowsHtml;

    const rect = cell.getBoundingClientRect();
    let left = rect.left + rect.width / 2 - 90;
    let top  = rect.bottom + 8;
    if (left + 190 > window.innerWidth)  left = window.innerWidth - 200;
    if (left < 8)                        left = 8;
    if (top  + 180 > window.innerHeight) top  = rect.top - 190;

    popover.style.left = left + 'px';
    popover.style.top  = top  + 'px';
    popover.classList.add('show');
}

function hidePopover() {
    popHideTimer = setTimeout(() => popover.classList.remove('show'), 120);
}

function loading(show) {
    const el = document.getElementById('loadingBar');
    if (el) el.style.display = show ? 'block' : 'none';
}

function showToast(msg) {
    const t = document.getElementById('sra-toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function changeMonth(dir) {
    currentMonth += dir;
    if (currentMonth > 12) { currentMonth = 1;  currentYear++; }
    if (currentMonth < 1)  { currentMonth = 12; currentYear--; }
    document.getElementById('monthLabel').textContent = monthName(currentYear, currentMonth);
    loadData();
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

    document.getElementById('monthLabel').textContent = monthName(currentYear, currentMonth);

    document.getElementById('prevMonthBtn').onclick = () => changeMonth(-1);
    document.getElementById('nextMonthBtn').onclick = () => changeMonth(1);

    document.getElementById('searchInput').addEventListener('input', e => {
        searchQuery = e.target.value;
        render();
    });

    document.querySelectorAll('.av-dept-tab').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.av-dept-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            deptFilter = btn.dataset.dept;
            loadData();
        };
    });

    document.addEventListener('mouseover', e => {
        const cell = e.target.closest('.av-day-cell');
        if (cell) showPopover(cell);
    });
    document.addEventListener('mouseout', e => {
        const cell = e.target.closest('.av-day-cell');
        if (cell) hidePopover();
    });

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    if (dropBtn) {
        dropBtn.onclick = e => {
            e.stopPropagation();
            const o = dropMenu.classList.toggle('open');
            dropBtn.classList.toggle('open', o);
        };
        document.addEventListener('click', () => {
            dropMenu.classList.remove('open');
            dropBtn.classList.remove('open');
        });
    }

    const btn     = document.getElementById('mobileHamburgerBtn');
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileNavOverlay');
    const close   = document.getElementById('mobileDrawerClose');
    function openDrawer()  { drawer.classList.add('open'); overlay.classList.add('visible'); btn?.classList.add('is-open'); }
    function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('visible'); btn?.classList.remove('is-open'); }
    if (btn)     btn.addEventListener('click', openDrawer);
    if (close)   close.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    loadData();
});
