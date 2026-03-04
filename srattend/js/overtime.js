const OT_API = '../srattend/overtime_api';

let employees         = [];
let tripData          = {};
let currentWeekStart  = getMonday(new Date());
let currentDeptFilter = '';
let deleteTarget      = null;
let editTarget        = null;
let selectedEligibility = '';

document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

function getMonday(d) {
    const c = new Date(d);
    const day = c.getDay();
    c.setDate(c.getDate() - day + (day === 0 ? -6 : 1));
    c.setHours(0, 0, 0, 0);
    return c;
}

function addDays(d, n) { const r = new Date(d); r.setDate(r.getDate() + n); return r; }
function fmtDate(d)    { return d.toISOString().split('T')[0]; }
function fmtDisp(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });
}
function fmtDayName(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'long' }).toUpperCase();
}
function fmtTime(t) {
    if (!t) return null;
    const [h, m] = t.split(':').map(Number);
    const suffix = h >= 12 ? 'PM' : 'AM';
    const hh = h % 12 || 12;
    return `${hh}:${String(m).padStart(2,'0')} ${suffix}`;
}
function diffMin(t1, t2) {
    if (!t1 || !t2) return null;
    const [h1,m1] = t1.split(':').map(Number);
    const [h2,m2] = t2.split(':').map(Number);
    const diff = (h2*60+m2) - (h1*60+m1);
    return diff > 0 ? diff : null;
}
function minToHM(m) {
    if (!m || m <= 0) return null;
    const h = Math.floor(m/60), min = m%60;
    return h > 0 && min > 0 ? `${h}h ${min}m` : h > 0 ? `${h}h` : `${min}m`;
}
function initials(name) { return name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2); }

function weekLabel() {
    const dates = getWeekDates();
    const first = dates[0], last = dates[dates.length-1];
    const opt = { month: 'short', day: 'numeric' };
    return `${first.toLocaleDateString('en-PH', opt)} – ${last.toLocaleDateString('en-PH', opt)}, ${last.getFullYear()}`;
}
function getWeekDates() { return Array.from({length:6}, (_,i) => addDays(new Date(currentWeekStart), i)); }

function loading(show) {
    const el = document.getElementById('loadingBar');
    if (el) el.style.display = show ? 'block' : 'none';
}

function buildUrl(action, params={}) {
    const url = new URL(OT_API, window.location.href);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k,v]) => { if (v !== '' && v != null) url.searchParams.set(k, v); });
    return url.toString();
}
async function apiGet(action, params={}) {
    try {
        const res = await fetch(buildUrl(action, params));
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        return JSON.parse(await res.text());
    } catch(e) { console.error(e); return null; }
}
async function apiPost(action, body={}) {
    try {
        const res = await fetch(buildUrl(action), {
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body),
        });
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        return JSON.parse(await res.text());
    } catch(e) { console.error(e); return null; }
}

async function loadData() {
    loading(true);
    const ws = fmtDate(currentWeekStart);
    const params = {};
    if (currentDeptFilter) params.dept = currentDeptFilter;
    const [emps, trips] = await Promise.all([
        apiGet('employees', params),
        apiGet('trips', { week_start: ws }),
    ]);
    employees = Array.isArray(emps) ? emps : [];
    tripData  = (trips && typeof trips === 'object') ? trips : {};
    loading(false);
    renderAll(document.getElementById('searchInput').value);
}

function getEligibilityInfo(trip) {
    const e = trip.is_eligible;
    if (e === null || e === undefined || e === '') return { cls: 'pending', icon: 'fa-clock', label: 'Pending' };
    if (parseInt(e) === 1) return { cls: 'eligible', icon: 'fa-check-circle', label: 'Eligible' };
    return { cls: 'not-eligible', icon: 'fa-times-circle', label: 'Not Eligible' };
}

function buildTripEntry(empId, date, trip) {
    const { depart_office, arrive_site, depart_site, arrive_office, notes, location } = trip;
    const eligInfo   = getEligibilityInfo(trip);
    const travelOut  = minToHM(diffMin(depart_office, arrive_site));
    const onSite     = minToHM(diffMin(arrive_site, depart_site));
    const travelBack = minToHM(diffMin(depart_site, arrive_office));
    const otH = parseInt(trip.ot_hours)   || 0;
    const otM = parseInt(trip.ot_minutes) || 0;
    const hasOT = eligInfo.cls === 'eligible' && (otH > 0 || otM > 0);
    const otLabel = hasOT
        ? (otH > 0 && otM > 0 ? `${otH}h ${otM}m` : otH > 0 ? `${otH}h` : `${otM}m`)
        : '';

    const stop = (iconClass, iconFa, label, time) => `
        <div class="ot-tl-stop">
            <div class="ot-tl-icon ${iconClass}"><i class="fas ${iconFa}"></i></div>
            <div class="ot-tl-label">${label}</div>
            <div class="ot-tl-time${time ? '' : ' empty'}">${time ? fmtTime(time) : '—'}</div>
        </div>`;

    const arrow = (dur) => `
        <div class="ot-tl-arrow">
            <i class="fas fa-arrow-right"></i>
            ${dur ? `<div class="ot-tl-duration">${dur}</div>` : ''}
        </div>`;

    return `
    <div class="ot-trip-entry" id="trip-${empId}-${date}">
        <div class="ot-trip-head">
            <div class="ot-trip-date-info">
                <span class="ot-trip-date">${fmtDisp(date)}</span>
                <span class="ot-trip-day">${fmtDayName(date)}</span>
                ${location ? `<span class="ot-trip-location"><i class="fas fa-map-marker-alt"></i>${location}</span>` : ''}
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="ot-eligibility ${eligInfo.cls}">
                    <i class="fas ${eligInfo.icon}"></i>${eligInfo.label}
                </span>
                ${hasOT ? `<span class="ot-hours-badge"><i class="fas fa-stopwatch"></i>${otLabel} OT</span>` : ''}
                <div class="ot-trip-actions">
                    <button class="ot-action-btn" title="Edit" onclick="openEditTrip(${empId},'${date}')"><i class="fas fa-pen"></i></button>
                    <button class="ot-action-btn del" title="Delete" onclick="askDeleteTrip(${empId},'${date}')"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
        <div class="ot-timeline">
            ${stop('office-icon','fa-building','Departure from Office', depart_office)}
            ${arrow(travelOut)}
            ${stop('site-icon','fa-map-marker-alt','Arrival at Site', arrive_site)}
            ${arrow(onSite ? onSite + ' on site' : null)}
            ${stop('site-depart','fa-flag-checkered','Departure from Site', depart_site)}
            ${arrow(travelBack)}
            ${stop('office-back','fa-home','Arrival at Office', arrive_office)}
        </div>
        ${notes ? `<div class="ot-notes-row"><i class="fas fa-sticky-note"></i>${notes}</div>` : ''}
    </div>`;
}

function buildEmpCard(emp) {
    const empTrips  = tripData[emp.id] || {};
    const tripCount = Object.keys(empTrips).length;
    const deptIcon  = emp.department === 'Field'
        ? '<i class="fas fa-hard-hat" style="font-size:9px;margin-right:3px"></i>'
        : '<i class="fas fa-building" style="font-size:9px;margin-right:3px"></i>';

    const empIdDisplay  = emp.employee_id ? emp.employee_id : '#' + String(emp.id).padStart(3, '0');
    const positionPart  = emp.position ? ` · ${emp.position}` : '';

    let tripsHtml = '';
    const sortedDates = Object.keys(empTrips).sort();
    sortedDates.forEach(date => { tripsHtml += buildTripEntry(emp.id, date, empTrips[date]); });
    if (!tripsHtml) tripsHtml = `<div class="ot-empty"><i class="fas fa-route"></i>No trip entries this week</div>`;

    return `
    <div class="ot-emp-card" id="ot-card-${emp.id}">
        <div class="ot-emp-header" onclick="toggleOtCard(${emp.id}, event)">
            <div class="ot-emp-avatar" style="background:linear-gradient(${emp.color})">${initials(emp.name)}</div>
            <div class="ot-emp-meta">
                <div class="ot-emp-name">
                    ${emp.name}
                    <span style="font-size:11px;font-weight:400;opacity:.65;margin-left:6px;">${empIdDisplay}</span>
                </div>
                <div class="ot-emp-dept">${deptIcon}${emp.department}${positionPart}</div>
            </div>
            <div class="ot-emp-summary">
                <span class="ot-badge ${tripCount>0?'has-trips':''}">${tripCount > 0 ? `${tripCount} trip${tripCount>1?'s':''}` : 'No trips'}</span>
                <button class="ot-action-btn" title="Add Trip"
                    style="border-color:rgba(255,255,255,0.3);background:rgba(255,255,255,0.15);color:white"
                    onclick="openAddTripFor(${emp.id}, event)"><i class="fas fa-plus"></i></button>
                <button class="ot-toggle-btn" id="ot-tbtn-${emp.id}"><i class="fas fa-chevron-down"></i></button>
            </div>
        </div>
        <div class="ot-emp-body" id="ot-body-${emp.id}">
            <div class="ot-trips-list">
                ${tripsHtml}
                <button class="ot-add-day-btn" onclick="openAddTripFor(${emp.id}, event)">
                    <i class="fas fa-plus"></i> Add Trip Entry
                </button>
            </div>
        </div>
    </div>`;
}

function renderAll(query='') {
    const q         = query.toLowerCase().trim();
    const container = document.getElementById('otContainer');
    if (!employees.length) {
        container.innerHTML = `<div class="no-results"><i class="fas fa-users-slash"></i><p>No employees found.</p></div>`;
        return;
    }
    const filtered = employees.filter(e =>
        !q ||
        e.name.toLowerCase().includes(q) ||
        (e.employee_id || '').toLowerCase().includes(q) ||
        (e.position || '').toLowerCase().includes(q)
    );
    if (!filtered.length) {
        container.innerHTML = `<div class="no-results"><i class="fas fa-search"></i><p>No employees match "<strong>${query}</strong>"</p></div>`;
        return;
    }
    const groups = {};
    filtered.forEach(e => { const d = e.department||'Other'; if (!groups[d]) groups[d]=[]; groups[d].push(e); });
    let html = '';
    for (const dept of ['Field','Office','Other']) {
        if (!groups[dept]?.length) continue;
        const icon = dept === 'Field' ? 'fa-hard-hat' : 'fa-building';
        html += `<div class="ot-dept-header"><i class="fas ${icon}"></i>${dept} <span class="dept-count">${groups[dept].length}</span></div>`;
        html += groups[dept].map(buildEmpCard).join('');
    }
    container.innerHTML = html;
}

function toggleOtCard(id, e) {
    if (e && e.target.closest('.ot-action-btn, .ot-toggle-btn')) return;
    const body = document.getElementById(`ot-body-${id}`);
    const btn  = document.getElementById(`ot-tbtn-${id}`);
    if (!body) return;
    const open = body.classList.toggle('open');
    btn.classList.toggle('open', open);
}

function setEligibility(val) {
    selectedEligibility = val;
    document.getElementById('eligPending').classList.remove('active-pending');
    document.getElementById('eligYes').classList.remove('active-yes');
    document.getElementById('eligNo').classList.remove('active-no');
    if (val === '') document.getElementById('eligPending').classList.add('active-pending');
    if (val === '1') document.getElementById('eligYes').classList.add('active-yes');
    if (val === '0') document.getElementById('eligNo').classList.add('active-no');
    const panel = document.getElementById('otHoursPanel');
    if (val === '1') { panel.classList.add('show'); }
    else { panel.classList.remove('show'); }
}

function populateEmpSelect(preselect=null) {
    const sel = document.getElementById('tEmp');
    sel.innerHTML = '';
    employees.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        const idPart  = e.employee_id ? e.employee_id : '#' + String(e.id).padStart(3, '0');
        const posPart = e.position ? ` - ${e.position}` : '';
        opt.textContent = `${e.name} (${idPart})${posPart} · ${e.department}`;
        if (preselect && e.id == preselect) opt.selected = true;
        sel.appendChild(opt);
    });
}

function resetModal() {
    document.getElementById('tDepartOffice').value = '';
    document.getElementById('tArriveSite').value   = '';
    document.getElementById('tDepartSite').value   = '';
    document.getElementById('tArriveOffice').value = '';
    document.getElementById('tLocation').value     = '';
    document.getElementById('tNotes').value        = '';
    document.getElementById('tOtHours').value      = '0';
    document.getElementById('tOtMinutes').value    = '0';
    document.getElementById('tDate').value         = fmtDate(new Date());
    document.getElementById('tEmp').disabled       = false;
    document.getElementById('tDate').disabled      = false;
    setEligibility('');
}

function openAddTrip() {
    editTarget = null;
    document.getElementById('tripModalTitle').innerHTML = '<i class="fas fa-route" style="margin-right:8px"></i>Add Trip Entry';
    populateEmpSelect();
    resetModal();
    document.getElementById('tripModal').classList.add('open');
}

function openAddTripFor(empId, e) {
    if (e) e.stopPropagation();
    openAddTrip();
    populateEmpSelect(empId);
    const body = document.getElementById(`ot-body-${empId}`);
    const btn  = document.getElementById(`ot-tbtn-${empId}`);
    if (body && !body.classList.contains('open')) { body.classList.add('open'); if (btn) btn.classList.add('open'); }
}

function openEditTrip(empId, date) {
    editTarget = { empId, date };
    const trip = (tripData[empId] || {})[date] || {};
    document.getElementById('tripModalTitle').innerHTML = '<i class="fas fa-pen" style="margin-right:8px"></i>Edit Trip Entry';
    populateEmpSelect(empId);
    document.getElementById('tEmp').disabled        = true;
    document.getElementById('tDate').value          = date;
    document.getElementById('tDate').disabled       = true;
    document.getElementById('tLocation').value      = trip.location      || '';
    document.getElementById('tDepartOffice').value  = trip.depart_office || '';
    document.getElementById('tArriveSite').value    = trip.arrive_site   || '';
    document.getElementById('tDepartSite').value    = trip.depart_site   || '';
    document.getElementById('tArriveOffice').value  = trip.arrive_office || '';
    document.getElementById('tNotes').value         = trip.notes         || '';
    document.getElementById('tOtHours').value       = trip.ot_hours   || 0;
    document.getElementById('tOtMinutes').value     = trip.ot_minutes || 0;
    const eligVal = trip.is_eligible !== null && trip.is_eligible !== undefined && trip.is_eligible !== ''
        ? String(trip.is_eligible) : '';
    setEligibility(eligVal);
    document.getElementById('tripModal').classList.add('open');
}

function closeModal() {
    document.getElementById('tripModal').classList.remove('open');
    editTarget = null;
}

async function saveTrip() {
    const empId         = parseInt(document.getElementById('tEmp').value);
    const date          = document.getElementById('tDate').value;
    const location      = document.getElementById('tLocation').value.trim();
    const depart_office = document.getElementById('tDepartOffice').value || null;
    const arrive_site   = document.getElementById('tArriveSite').value   || null;
    const depart_site   = document.getElementById('tDepartSite').value   || null;
    const arrive_office = document.getElementById('tArriveOffice').value || null;
    const notes         = document.getElementById('tNotes').value.trim();
    const is_eligible   = selectedEligibility;
    const ot_hours      = selectedEligibility === '1' ? parseInt(document.getElementById('tOtHours').value)   || 0 : 0;
    const ot_minutes    = selectedEligibility === '1' ? parseInt(document.getElementById('tOtMinutes').value) || 0 : 0;

    if (!empId || !date) { alert('Please select an employee and date.'); return; }

    const res = await apiPost('save_trip', {
        emp_id: empId, trip_date: date, location,
        depart_office, arrive_site, depart_site, arrive_office,
        is_eligible: is_eligible !== '' ? parseInt(is_eligible) : null,
        ot_hours, ot_minutes, notes,
    });

    if (res?.success) {
        closeModal();
        showToast(editTarget ? 'Trip updated!' : 'Trip saved!');
        await loadData();
        const body = document.getElementById(`ot-body-${empId}`);
        const btn  = document.getElementById(`ot-tbtn-${empId}`);
        if (body && !body.classList.contains('open')) { body.classList.add('open'); if (btn) btn.classList.add('open'); }
    }
}

function askDeleteTrip(empId, date) {
    deleteTarget = { empId, date };
    document.getElementById('confirmMsg').textContent = `Delete trip on ${fmtDisp(date)} for this employee?`;
    document.getElementById('confirmOverlay').classList.add('open');
}

async function doDeleteTrip() {
    if (!deleteTarget) return;
    const res = await apiPost('delete_trip', { emp_id: deleteTarget.empId, trip_date: deleteTarget.date });
    if (res?.success) {
        document.getElementById('confirmOverlay').classList.remove('open');
        showToast('Trip deleted.');
        deleteTarget = null;
        await loadData();
    }
}

function showToast(msg) {
    const t = document.getElementById('sra-toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('weekLabel').textContent = weekLabel();
    setEligibility('');
    loadData();

    document.getElementById('prevWeekBtn').onclick = () => {
        currentWeekStart = addDays(new Date(currentWeekStart), -7);
        document.getElementById('weekLabel').textContent = weekLabel();
        loadData();
    };
    document.getElementById('nextWeekBtn').onclick = () => {
        currentWeekStart = addDays(new Date(currentWeekStart), 7);
        document.getElementById('weekLabel').textContent = weekLabel();
        loadData();
    };

    document.getElementById('searchInput').addEventListener('input', e => renderAll(e.target.value));

    document.querySelectorAll('.dept-tab').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.dept-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentDeptFilter = btn.dataset.dept;
            loadData();
        };
    });

    document.getElementById('addTripBtn').onclick     = openAddTrip;
    document.getElementById('tripSaveBtn').onclick    = saveTrip;
    document.getElementById('tripCancelBtn').onclick  = closeModal;
    document.getElementById('tripModalClose').onclick = closeModal;
    document.getElementById('tripModal').onclick = e => { if (e.target === document.getElementById('tripModal')) closeModal(); };

    document.getElementById('eligPending').onclick = () => setEligibility('');
    document.getElementById('eligYes').onclick     = () => setEligibility('1');
    document.getElementById('eligNo').onclick      = () => setEligibility('0');

    document.getElementById('exportOtBtn').onclick = () => {
        const ws = fmtDate(currentWeekStart);
        window.open(`../srattend/export_overtime.php?week_start=${ws}&dept=${currentDeptFilter}`, '_blank');
    };

    document.getElementById('confirmDeleteBtn').onclick  = doDeleteTrip;
    document.getElementById('cancelDeleteBtn').onclick   = () => { document.getElementById('confirmOverlay').classList.remove('open'); deleteTarget = null; };
    document.getElementById('confirmOverlay').onclick    = e => { if (e.target === document.getElementById('confirmOverlay')) { document.getElementById('confirmOverlay').classList.remove('open'); deleteTarget = null; } };

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    if (dropBtn) {
        dropBtn.onclick = e => { e.stopPropagation(); const o = dropMenu.classList.toggle('open'); dropBtn.classList.toggle('open', o); };
        document.addEventListener('click', () => { dropMenu.classList.remove('open'); dropBtn.classList.remove('open'); });
    }
});

(function(){
    const btn     = document.getElementById('mobileHamburgerBtn');
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileNavOverlay');
    const close   = document.getElementById('mobileDrawerClose');
    function open(){ drawer.classList.add('open'); overlay.classList.add('visible'); btn.classList.add('is-open'); }
    function shut(){ drawer.classList.remove('open'); overlay.classList.remove('visible'); btn.classList.remove('is-open'); }
    if(btn)     btn.addEventListener('click', open);
    if(close)   close.addEventListener('click', shut);
    if(overlay) overlay.addEventListener('click', shut);
})();