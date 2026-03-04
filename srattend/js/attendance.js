const API = '../srattend/attendance.php';
const WORK_START_FIELD_MIN  = 7  * 60 + 10;
const WORK_START_OFFICE_MIN = 8  * 60 + 10;
const WORK_END_MIN          = 17 * 60;

let employees         = [];
let attData           = {};
let otData            = {};
let currentWeekStart  = getMonday(new Date());
let deleteTargetId    = null;
let editTargetId      = null;
let currentDeptFilter = '';
let weekNavigating    = false;

const COLORS = [
    '135deg,#1245a8,#42a5f5','135deg,#2e7d32,#66bb6a','135deg,#6a1b9a,#ab47bc',
    '135deg,#c62828,#ef5350','135deg,#e65100,#ffa726','135deg,#00695c,#26a69a',
    '135deg,#283593,#5c6bc0','135deg,#4a148c,#8e24aa','135deg,#880e4f,#c2185b',
    '135deg,#37474f,#607d8b',
];

function getMonday(d) {
    const c   = new Date(d);
    const day = c.getDay();
    const diff = (day === 0) ? -6 : 1 - day;
    c.setDate(c.getDate() + diff);
    c.setHours(0, 0, 0, 0);
    return c;
}

function addDays(d, n) {
    const r = new Date(d);
    r.setDate(r.getDate() + n);
    return r;
}

// FIXED: use local year/month/date instead of toISOString() which shifts to UTC
// toISOString() on a Philippine machine returns the previous day (UTC-8hrs)
function fmtDate(d) {
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

function fmtDisp(d)     { return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }); }
function toMin(t)       { if (!t) return null; const [h, m] = t.split(':').map(Number); return h * 60 + m; }
function minToHM(m) {
    if (m === null || isNaN(m) || m <= 0) return '—';
    const h = Math.floor(m / 60), min = m % 60;
    return h > 0 && min > 0 ? `${h}h ${min}m` : h > 0 ? `${h}h` : `${min}m`;
}
function getWeekDates() { return Array.from({ length: 6 }, (_, i) => addDays(new Date(currentWeekStart), i)); }

function weekLabel() {
    const d     = getWeekDates();
    const first = d[0], last = d[d.length - 1];
    const opt   = { month: 'short', day: 'numeric' };
    const yr    = last.getFullYear();
    return `${first.toLocaleDateString('en-PH', opt)} – ${last.toLocaleDateString('en-PH', opt)}, ${yr}`;
}

function calcLate(ti, dept)  { const m = toMin(ti); if (!m) return 0; const cut = dept === 'Field' ? WORK_START_FIELD_MIN : WORK_START_OFFICE_MIN; return Math.max(0, m - cut); }
function calcUnder(to)       { const m = toMin(to); if (!m) return 0; return Math.max(0, WORK_END_MIN - m); }
function calcHrs(ti, to)     { const a = toMin(ti), b = toMin(to); if (!a || !b) return 0; return Math.max(0, (b - a) / 60); }

function computeTotals(empId) {
    const emp   = employees.find(e => e.id == empId);
    const dept  = emp ? emp.department : 'Office';
    const dates = getWeekDates();
    const today = new Date(); today.setHours(0,0,0,0);
    let late = 0, under = 0, hrs = 0, present = 0, absent = 0;
    dates.forEach(d => {
        const k = fmtDate(d), r = (attData[empId] || {})[k] || {};
        if (r.in || r.out) {
            present++;
        } else if (d < today) {
            absent++;
        }
        late  += calcLate(r.in, dept);
        under += calcUnder(r.out);
        hrs   += calcHrs(r.in, r.out);
    });
    const ot      = otData[empId] || { m: 0, a: 0 };
    const otTotal = (parseFloat(ot.m) || 0) + (parseFloat(ot.a) || 0);
    return { late, under, hrs, present, absent, otTotal, otM: ot.m || '', otA: ot.a || '' };
}

function initials(name) { return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2); }

function loading(show) {
    const el = document.getElementById('loadingBar');
    if (el) el.style.display = show ? 'block' : 'none';
}

function buildUrl(action, params = {}) {
    const url = new URL(API, window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
    });
    return url.toString();
}

async function apiGet(action, params = {}) {
    try {
        const res  = await fetch(buildUrl(action, params));
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { console.error('Non-JSON from', action, ':', text); return null; }
    } catch (e) { console.error('Fetch error on', action, e); return null; }
}

async function apiPost(action, body = {}) {
    try {
        const res = await fetch(buildUrl(action), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { console.error('Non-JSON from', action, ':', text); return null; }
    } catch (e) { console.error('Post error on', action, e); return null; }
}

async function loadWeekData() {
    loading(true);
    const ws = fmtDate(currentWeekStart);
    try {
        const params = {};
        if (currentDeptFilter) params.dept = currentDeptFilter;

        const [emps, att] = await Promise.all([
            apiGet('employees', params),
            apiGet('week', { week_start: ws }),
        ]);

        let ot = {};
        try {
            const otRes = await apiGet('overtime', { week_start: ws });
            if (otRes && typeof otRes === 'object') ot = otRes;
        } catch (e) { ot = {}; }

        employees = Array.isArray(emps) ? emps : [];
        attData   = (att && typeof att === 'object') ? att : {};
        otData    = (ot  && typeof ot  === 'object') ? ot  : {};

        _seedSaveCache();
    } catch (e) {
        console.error('loadWeekData error:', e);
        employees = [];
    }
    loading(false);
    renderAll(document.getElementById('searchInput').value);
}

async function saveAttendance(empId, date, type, value) {
    if (!attData[empId])       attData[empId]       = {};
    if (!attData[empId][date]) attData[empId][date] = {};
    attData[empId][date][type] = value || null;

    const payload = {
        emp_id:   empId,
        att_date: date,
        time_in:  attData[empId][date].in  || null,
        time_out: attData[empId][date].out || null,
    };

    const res = await apiPost('save_attendance', payload);
    if (!res || !res.success) {
        console.error('Save failed:', empId, date, type, res);
        showToast('Save failed — check connection.');
    }
    refreshStats(empId);
}

async function saveOvertime(empId, field, value) {
    if (!otData[empId]) otData[empId] = { m: 0, a: 0 };
    otData[empId][field] = parseFloat(value) || 0;
    await apiPost('save_overtime', {
        emp_id:       empId,
        week_start:   fmtDate(currentWeekStart),
        ot_morning:   otData[empId].m || 0,
        ot_afternoon: otData[empId].a || 0,
    });
    refreshStats(empId);
}

const _saveCache  = {};
const _saveTimers = {};

function _seedSaveCache() {
    Object.keys(_saveCache).forEach(k => delete _saveCache[k]);
    const weekDates = getWeekDates();
    employees.forEach(emp => {
        weekDates.forEach(d => {
            const date = fmtDate(d);
            const r    = (attData[emp.id] || {})[date] || {};
            _saveCache[`${emp.id}_${date}_in`]  = r.in  || '';
            _saveCache[`${emp.id}_${date}_out`] = r.out || '';
        });
    });
}

function triggerSave(t) {
    if (!t.classList.contains('time-inp') || weekNavigating) return;
    const empId  = t.dataset.emp;
    const date   = t.dataset.date;
    const type   = t.dataset.t;
    const key    = `${empId}_${date}_${type}`;
    const oldVal = (key in _saveCache) ? _saveCache[key] : '';
    if (oldVal === t.value) return;
    _saveCache[key] = t.value;
    t.classList.toggle('has-val', !!t.value);
    clearTimeout(_saveTimers[key]);
    _saveTimers[key] = setTimeout(() => {
        saveAttendance(parseInt(empId), date, type, t.value);
    }, 400);
}

document.addEventListener('change', e => {
    const t = e.target;
    if (t.classList.contains('time-inp')) {
        if (weekNavigating) return;
        const empId  = t.dataset.emp;
        const date   = t.dataset.date;
        const type   = t.dataset.t;
        const key    = `${empId}_${date}_${type}`;
        const oldVal = (key in _saveCache) ? _saveCache[key] : '';
        if (oldVal === t.value) return;
        _saveCache[key] = t.value;
        t.classList.toggle('has-val', !!t.value);
        clearTimeout(_saveTimers[key]);
        saveAttendance(parseInt(empId), date, type, t.value);
    }
    if (t.classList.contains('ot-inp')) {
        saveOvertime(parseInt(t.dataset.emp), t.dataset.ot, t.value);
    }
});

document.addEventListener('input', e => {
    const t = e.target;
    if (t.classList.contains('time-inp')) triggerSave(t);
});

document.addEventListener('blur', e => {
    const t = e.target;
    if (!t.classList.contains('time-inp') || weekNavigating) return;
    const empId  = t.dataset.emp;
    const date   = t.dataset.date;
    const type   = t.dataset.t;
    const key    = `${empId}_${date}_${type}`;
    const oldVal = (key in _saveCache) ? _saveCache[key] : '';
    clearTimeout(_saveTimers[key]);
    if (oldVal === t.value) return;
    _saveCache[key] = t.value;
    t.classList.toggle('has-val', !!t.value);
    saveAttendance(parseInt(empId), date, type, t.value);
}, true);

function buildCard(emp) {
    const dates    = getWeekDates();
    const t        = computeTotals(emp.id);
    const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    const dayHeaders = dates.map((d, i) => {
        const sat = d.getDay() === 6;
        return `<th colspan="2" style="${sat ? 'background:linear-gradient(135deg,#263238,#37474f)' : ''}">${dayNames[i]}<div class="day-date">${fmtDisp(d)}</div></th>`;
    }).join('');

    const subHeaders = dates.map(d => {
        const sat = d.getDay() === 6, cls = sat ? ' class="col-sat"' : '';
        return `<th${cls}>In</th><th${cls}>Out</th>`;
    }).join('');

const tds = dates.map(d => {
    const k     = fmtDate(d);
    const r     = (attData[emp.id] || {})[k] || {};
    const sat   = d.getDay() === 6, sc = sat ? ' col-sat' : '';
    const today = new Date(); today.setHours(0,0,0,0);
    const isPastWeekday = d < today;
    const absentCls = (!r.in && !r.out && isPastWeekday) ? ' col-absent' : '';
    return `
        <td class="day-sep${sc}${absentCls}">
            <input type="time" class="time-inp${r.in  ? ' has-val' : ''}" data-emp="${emp.id}" data-date="${k}" data-t="in"  value="${r.in  || ''}">
        </td>
        <td class="${sc}${absentCls}">
            <input type="time" class="time-inp${r.out ? ' has-val' : ''}" data-emp="${emp.id}" data-date="${k}" data-t="out" value="${r.out || ''}">
        </td>`;
}).join('');

    const deptIcon     = emp.department === 'Field' ? '<i class="fas fa-hard-hat" style="font-size:9px;margin-right:3px"></i>' : '<i class="fas fa-building" style="font-size:9px;margin-right:3px"></i>';
    const empIdDisplay = emp.employee_id ? emp.employee_id : '#' + String(emp.id).padStart(3, '0');

    const strip = `
        <div class="tstrip-cell highlight-late">
            <div class="tstrip-label">Late</div>
            <div class="tstrip-val late-c" id="v-late-${emp.id}">${minToHM(t.late)}</div>
            <div class="tstrip-sub">total late</div>
        </div>
        <div class="tstrip-cell highlight-under">
            <div class="tstrip-label">Undertime</div>
            <div class="tstrip-val under-c" id="v-under-${emp.id}">${minToHM(t.under)}</div>
            <div class="tstrip-sub">left early</div>
        </div>
        <div class="tstrip-cell">
            <div class="tstrip-label">Present</div>
            <div class="tstrip-val total-c" id="v-present-${emp.id}">${t.present}d</div>
            <div class="tstrip-sub">days</div>
        </div>
        <div class="tstrip-cell">
            <div class="tstrip-label">Absent</div>
            <div class="tstrip-val" style="color:var(--red-700)" id="v-absent-${emp.id}">${t.absent}d</div>
            <div class="tstrip-sub">days</div>
        </div>
        <div class="tstrip-cell">
            <div class="tstrip-label">Total Hours</div>
            <div class="tstrip-val total-c" id="v-hrs-${emp.id}">${t.hrs.toFixed(1)}h</div>
            <div class="tstrip-sub">worked</div>
        </div>
        <div class="tstrip-cell highlight-ot">
            <div class="tstrip-label">OT Morning</div>
            <input type="number" class="ot-inp" step="0.5" min="0" max="12" data-emp="${emp.id}" data-ot="m" value="${t.otM}" placeholder="0">
        </div>
        <div class="tstrip-cell highlight-ot">
            <div class="tstrip-label">OT Afternoon</div>
            <input type="number" class="ot-inp" step="0.5" min="0" max="12" data-emp="${emp.id}" data-ot="a" value="${t.otA}" placeholder="0">
        </div>
        <div class="tstrip-cell highlight-ot" style="grid-column:span 2;border-top:2px solid var(--border);">
            <div class="tstrip-label" style="font-size:11px">Total Overtime</div>
            <div class="tstrip-val ot-c" style="font-size:20px;" id="v-ot-${emp.id}">${t.otTotal > 0 ? t.otTotal.toFixed(1) + 'h' : '—'}</div>
        </div>`;

    return `
    <div class="emp-card" id="card-${emp.id}" data-name="${emp.name.toLowerCase()}" data-dept="${(emp.department || '').toLowerCase()}">
        <div class="emp-card-header" onclick="toggleCard(${emp.id}, event)">
            <div class="emp-avatar" style="background:linear-gradient(${emp.color})">${initials(emp.name)}</div>
            <div class="emp-meta">
                <div class="emp-name-row">
                    <span class="emp-name-text">${emp.name}</span>
                    <span class="emp-id-badge">${empIdDisplay}</span>
                </div>
                <div class="emp-dept-text">${deptIcon}${emp.department}${emp.position ? ' · ' + emp.position : ''}</div>
            </div>
            <div class="emp-header-stats" id="hstats-${emp.id}">
                <div class="hstat late"><div class="hstat-val" id="hs-late-${emp.id}">${minToHM(t.late)}</div><div class="hstat-label">Late</div></div>
                <div class="hstat under"><div class="hstat-val" id="hs-under-${emp.id}">${minToHM(t.under)}</div><div class="hstat-label">Undertime</div></div>
                <div class="hstat ot"><div class="hstat-val" id="hs-ot-${emp.id}">${t.otTotal > 0 ? t.otTotal.toFixed(1) + 'h' : '—'}</div><div class="hstat-label">OT</div></div>
                <div class="hstat absent"><div class="hstat-val" id="hs-abs-${emp.id}">${t.absent}d</div><div class="hstat-label">Absent</div></div>
            </div>
            <div class="emp-header-actions">
                <button class="hdr-btn edit-btn" title="Edit"   onclick="openEdit(${emp.id}, event)"><i class="fas fa-pen"></i></button>
                <button class="hdr-btn del-btn"  title="Remove" onclick="confirmDelete(${emp.id}, event)"><i class="fas fa-trash"></i></button>
                <button class="hdr-btn toggle-btn" id="tbtn-${emp.id}"><i class="fas fa-chevron-down"></i></button>
            </div>
        </div>
        <div class="emp-card-body" id="body-${emp.id}">
            <div class="card-inner">
                <div class="daily-wrap">
                    <table class="daily-table">
                        <thead>
                            <tr class="day-row">${dayHeaders}</tr>
                            <tr class="sub-row">${subHeaders}</tr>
                        </thead>
                        <tbody><tr>${tds}</tr></tbody>
                    </table>
                </div>
                <div class="totals-strip">${strip}</div>
            </div>
        </div>
    </div>`;
}

function renderAll(query = '') {
    const q         = query.toLowerCase().trim();
    const container = document.getElementById('empContainer');
    if (!employees.length) {
        container.innerHTML = `<div class="no-results"><i class="fas fa-users-slash"></i><p>No employees found.</p></div>`;
        return;
    }
    const filtered = employees.filter(e =>
        !q ||
        e.name.toLowerCase().includes(q) ||
        (e.department || '').toLowerCase().includes(q) ||
        (e.employee_id || '').toLowerCase().includes(q) ||
        (e.position || '').toLowerCase().includes(q)
    );
    if (!filtered.length) {
        container.innerHTML = `<div class="no-results"><i class="fas fa-search"></i><p>No employees found for "<strong>${query}</strong>"</p></div>`;
        return;
    }
    const groups = {};
    filtered.forEach(e => { const d = e.department || 'Other'; if (!groups[d]) groups[d] = []; groups[d].push(e); });
    let html = '';
    for (const dept of ['Field', 'Office', 'Other']) {
        if (!groups[dept] || !groups[dept].length) continue;
        const icon = dept === 'Field' ? 'fa-hard-hat' : 'fa-building';
        html += `<div class="dept-section-header"><i class="fas ${icon}"></i>${dept} <span class="dept-count">${groups[dept].length}</span></div>`;
        html += groups[dept].map(buildCard).join('');
    }
    container.innerHTML = html;
}

function toggleCard(id, e) {
    if (e && e.target.closest('.hdr-btn')) return;
    const body = document.getElementById(`body-${id}`);
    const btn  = document.getElementById(`tbtn-${id}`);
    const open = body.classList.toggle('open');
    btn.classList.toggle('open', open);
}

function refreshStats(empId) {
    const t   = computeTotals(empId);
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set(`v-late-${empId}`,    minToHM(t.late));
    set(`v-under-${empId}`,   minToHM(t.under));
    set(`v-present-${empId}`, t.present + 'd');
    set(`v-absent-${empId}`,  t.absent + 'd');
    set(`v-hrs-${empId}`,     t.hrs.toFixed(1) + 'h');
    set(`v-ot-${empId}`,      t.otTotal > 0 ? t.otTotal.toFixed(1) + 'h' : '—');
    set(`hs-late-${empId}`,   minToHM(t.late));
    set(`hs-under-${empId}`,  minToHM(t.under));
    set(`hs-ot-${empId}`,     t.otTotal > 0 ? t.otTotal.toFixed(1) + 'h' : '—');
    set(`hs-abs-${empId}`,    t.absent + 'd');
}

function clearModal() {
    document.getElementById('fEmpId').value     = '';
    document.getElementById('fName').value      = '';
    document.getElementById('fPhone').value     = '';
    document.getElementById('fDept').value      = '';
    document.getElementById('fPosition').value  = '';
    document.getElementById('fEmpType').value   = 'Full Time';
    document.getElementById('fHireDate').value  = '';
}

function openAdd() {
    editTargetId = null;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add Employee';
    clearModal();
    document.getElementById('empModal').classList.add('open');
    setTimeout(() => document.getElementById('fEmpId').focus(), 100);
}

function openEdit(id, e) {
    e.stopPropagation();
    editTargetId = id;
    const emp = employees.find(x => x.id == id);
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Employee';
    document.getElementById('fEmpId').value     = emp.employee_id     || '';
    document.getElementById('fName').value      = emp.name            || '';
    document.getElementById('fPhone').value     = emp.phone           || '';
    document.getElementById('fDept').value      = emp.department      || '';
    document.getElementById('fPosition').value  = emp.position        || '';
    document.getElementById('fEmpType').value   = emp.employment_type || 'Full Time';
    document.getElementById('fHireDate').value  = emp.hire_date       || '';
    document.getElementById('empModal').classList.add('open');
    setTimeout(() => document.getElementById('fEmpId').focus(), 100);
}

async function saveEmployee() {
    const employee_id     = document.getElementById('fEmpId').value.trim();
    const name            = document.getElementById('fName').value.trim();
    const phone           = document.getElementById('fPhone').value.trim();
    const dept            = document.getElementById('fDept').value;
    const position        = document.getElementById('fPosition').value.trim();
    const employment_type = document.getElementById('fEmpType').value;
    const hire_date       = document.getElementById('fHireDate').value;

    if (!employee_id) { document.getElementById('fEmpId').focus();   showToast('Employee ID is required.');  return; }
    if (!name)        { document.getElementById('fName').focus();     showToast('Full name is required.');    return; }
    if (!dept)        { document.getElementById('fDept').focus();     showToast('Department is required.');   return; }
    if (!position)    { document.getElementById('fPosition').focus(); showToast('Position is required.');     return; }

    let res;
    if (editTargetId) {
        res = await apiPost('edit_employee', { id: editTargetId, employee_id, name, phone, dept, position, employment_type, hire_date });
    } else {
        const color = COLORS[Math.floor(Math.random() * COLORS.length)];
        res = await apiPost('add_employee', { employee_id, name, phone, dept, position, employment_type, hire_date, color });
    }

    if (res && !res.success) { showToast(res.message || 'Error saving employee.'); return; }
    showToast(editTargetId ? 'Employee updated!' : 'Employee added!');
    document.getElementById('empModal').classList.remove('open');
    await loadWeekData();
}

function confirmDelete(id, e) {
    e.stopPropagation();
    deleteTargetId = id;
    const emp = employees.find(x => x.id == id);
    document.getElementById('confirmMsg').textContent = `Remove "${emp.name}" from the attendance sheet? This cannot be undone.`;
    document.getElementById('confirmOverlay').classList.add('open');
}

async function doDelete() {
    await apiPost('delete_employee', { id: deleteTargetId });
    document.getElementById('confirmOverlay').classList.remove('open');
    showToast('Employee removed.');
    deleteTargetId = null;
    await loadWeekData();
}

function goWeek(dir) {
    weekNavigating = true;
    currentWeekStart = addDays(new Date(currentWeekStart), dir * 7);
    document.getElementById('weekLabel').textContent = weekLabel();
    setTimeout(() => {
        loadWeekData().then(() => { weekNavigating = false; });
    }, 50);
}

function showToast(msg) {
    const t = document.getElementById('sra-toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('weekLabel').textContent = weekLabel();
    loadWeekData();

    document.getElementById('prevWeekBtn').onclick = () => goWeek(-1);
    document.getElementById('nextWeekBtn').onclick = () => goWeek(1);
    document.getElementById('searchInput').addEventListener('input', e => renderAll(e.target.value));
    document.getElementById('addEmpBtn').onclick = openAdd;

    document.querySelectorAll('.dept-tab').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.dept-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentDeptFilter = btn.dataset.dept;
            loadWeekData();
        };
    });

    document.getElementById('exportBtn').onclick = () => {
        const ws = fmtDate(currentWeekStart);
        window.open(`export.php?week_start=${ws}&dept=${currentDeptFilter}`, '_blank');
    };

    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

    document.getElementById('saveEmpBtn').onclick    = saveEmployee;
    document.getElementById('cancelEmpBtn').onclick  = () => document.getElementById('empModal').classList.remove('open');
    document.getElementById('modalCloseBtn').onclick = () => document.getElementById('empModal').classList.remove('open');
    document.getElementById('empModal').onclick      = e => { if (e.target === document.getElementById('empModal')) document.getElementById('empModal').classList.remove('open'); };

    document.getElementById('fEmpId').addEventListener('keydown',    e => { if (e.key === 'Enter') document.getElementById('fName').focus(); });
    document.getElementById('fName').addEventListener('keydown',     e => { if (e.key === 'Enter') document.getElementById('fPhone').focus(); });
    document.getElementById('fPosition').addEventListener('keydown', e => { if (e.key === 'Enter') saveEmployee(); });

    document.getElementById('confirmOverlay').onclick   = e => { if (e.target === document.getElementById('confirmOverlay')) document.getElementById('confirmOverlay').classList.remove('open'); };
    document.getElementById('confirmDeleteBtn').onclick = doDelete;
    document.getElementById('cancelDeleteBtn').onclick  = () => document.getElementById('confirmOverlay').classList.remove('open');

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    if (dropBtn) {
        dropBtn.onclick = e => { e.stopPropagation(); const o = dropMenu.classList.toggle('open'); dropBtn.classList.toggle('open', o); };
        document.addEventListener('click', () => { dropMenu.classList.remove('open'); dropBtn.classList.remove('open'); });
    }
});

(function () {
    const btn     = document.getElementById('mobileHamburgerBtn');
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileNavOverlay');
    const close   = document.getElementById('mobileDrawerClose');
    function open() { drawer.classList.add('open'); overlay.classList.add('visible'); btn.classList.add('is-open'); }
    function shut() { drawer.classList.remove('open'); overlay.classList.remove('visible'); btn.classList.remove('is-open'); }
    if (btn)     btn.addEventListener('click', open);
    if (close)   close.addEventListener('click', shut);
    if (overlay) overlay.addEventListener('click', shut);
})();
