const API = 'employee_api.php';

let currentTab = 'employees';
let allEmployees = [];
let allUnemployed = [];
let allContracts = [];
let empPage = 1;
const EMP_PER_PAGE = 12;

function initials(name) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    if (isNaN(dt)) return d;
    return dt.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function daysUntil(dateStr) {
    if (!dateStr) return null;
    const now = new Date(); now.setHours(0,0,0,0);
    const target = new Date(dateStr);
    return Math.ceil((target - now) / 86400000);
}

function contractStatus(endDate) {
    const d = daysUntil(endDate);
    if (d === null) return '';
    if (d < 0)  return '<span class="badge badge-inactive">Expired</span>';
    if (d <= 30) return `<span class="badge badge-expiring"><i class="fas fa-exclamation-circle"></i> Expires in ${d}d</span>`;
    return `<span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>`;
}

function expiryClass(endDate) {
    const d = daysUntil(endDate);
    if (d === null) return '';
    if (d < 0)  return 'expiry-expired';
    if (d <= 30) return 'expiry-warn';
    return 'expiry-ok';
}

async function apiGet(action, params = {}) {
    try {
        const url = new URL(API, window.location.href);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k,v]) => { if (v !== '' && v != null) url.searchParams.set(k, v); });
        const res = await fetch(url);
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        return await res.json();
    } catch(e) { console.error(e); return null; }
}

async function apiPost(action, body = {}) {
    try {
        const url = new URL(API, window.location.href);
        url.searchParams.set('action', action);
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (res.status === 403) { window.location.href = '../config.php'; return null; }
        return await res.json();
    } catch(e) { console.error(e); return null; }
}

function loading(show) {
    document.getElementById('loadingBar').style.display = show ? 'block' : 'none';
}

async function loadAll() {
    loading(true);
    const [emps, unemp, contracts] = await Promise.all([
        apiGet('list_employees'),
        apiGet('list_unemployed'),
        apiGet('list_contracts'),
    ]);
    allEmployees  = Array.isArray(emps)      ? emps      : [];
    allUnemployed = Array.isArray(unemp)     ? unemp     : [];
    allContracts  = Array.isArray(contracts) ? contracts : [];
    loading(false);
    updateCounts();
    renderNBIAlerts();
    renderActive();
}

function updateCounts() {
    document.getElementById('countEmp').textContent      = allEmployees.length;
    document.getElementById('countUnemp').textContent    = allUnemployed.length;
    document.getElementById('countContract').textContent = allContracts.length;
    document.getElementById('statActive').textContent    = allEmployees.filter(e => e.is_active == 1).length;
    document.getElementById('statInactive').textContent  = allUnemployed.length;
    document.getElementById('statContract').textContent  = allContracts.length;
    document.getElementById('statExpiring').textContent  = allContracts.filter(c => { const d = daysUntil(c.end_date); return d !== null && d >= 0 && d <= 30; }).length;
}

function renderNBIAlerts() {
    const threshold = 5;
    const expiring = allEmployees.filter(e => {
        const d = daysUntil(e.nbi_validity);
        return d !== null && d >= 0 && d <= threshold;
    });
    const expired = allEmployees.filter(e => {
        const d = daysUntil(e.nbi_validity);
        return d !== null && d < 0;
    });
    const all = [...expiring, ...expired];
    const banner = document.getElementById('nbiAlertBanner');
    const list = document.getElementById('nbiAlertList');
    if (!all.length) { banner.style.display = 'none'; return; }
    list.innerHTML = all.map(e => {
        const d = daysUntil(e.nbi_validity);
        const tag = d < 0
            ? `<span class="nbi-tag expired">Expired ${Math.abs(d)}d ago</span>`
            : d === 0
                ? `<span class="nbi-tag today">Expires today</span>`
                : `<span class="nbi-tag expiring">Expiring in ${d}d</span>`;
        return `<div class="nbi-alert-row"><span class="nbi-emp-name">${e.name}</span><span class="nbi-emp-pos">${e.position||'—'}</span>${tag}</div>`;
    }).join('');
    banner.style.display = 'block';
}

function renderActive() {
    const q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    if (currentTab === 'employees')  renderEmployees(q);
    if (currentTab === 'unemployed') renderUnemployed(q);
    if (currentTab === 'contracts')  renderContracts(q);
}

function renderEmployees(q) {
    const grid = document.getElementById('empCardGrid');
    let data = allEmployees.filter(e =>
        !q || e.name.toLowerCase().includes(q) ||
        (e.employee_id||'').toLowerCase().includes(q) ||
        (e.position||'').toLowerCase().includes(q)
    );
    if (!data.length) {
        grid.innerHTML = `<div class="emp-no-data"><i class="fas fa-users-slash"></i><p>No employees found.</p></div>`;
        document.getElementById('empPagination').innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(data.length / EMP_PER_PAGE);
    const start = (empPage - 1) * EMP_PER_PAGE;
    const paged = data.slice(start, start + EMP_PER_PAGE);

    grid.innerHTML = paged.map(e => {
        const initStr = initials(e.name);
        const statusBadge = e.is_active == 1
            ? '<span class="badge badge-active"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Active</span>'
            : '<span class="badge badge-inactive"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Inactive</span>';
        const fi = (label, val) => `<div class="epc-field"><div class="epc-label">${label}</div><div class="epc-val">${val||'—'}</div></div>`;
        return `
        <div class="emp-card" onclick="openViewModal(${e.id})">
            <div class="emp-card-header">
                <div class="emp-card-avatar" style="background:linear-gradient(${e.color||'135deg,#1245a8,#42a5f5'})">${initStr}</div>
                <div class="emp-card-info">
                    <div class="emp-card-name">${e.name}</div>
                    <div class="emp-card-sub">${e.employee_id||'—'}</div>
                </div>
                <div>${statusBadge}</div>
            </div>
            <div class="emp-card-body">
                ${fi('Position', e.position)}
                ${fi('Department', e.department)}
                ${fi('Type', e.employment_type)}
                ${fi('Start Date', fmtDate(e.hire_date))}
                ${fi('Phone', e.phone)}
                ${fi('Sex', e.sex)}
                ${fi('Date of Birth', fmtDate(e.date_of_birth))}
                ${fi('Address', e.address)}
            </div>
            <div class="emp-card-footer" onclick="event.stopPropagation()">
                <button class="act-btn" title="Edit" onclick="openEmpModal(${e.id})"><i class="fas fa-edit"></i> Edit</button>
                <button class="act-btn del" title="Delete" onclick="confirmDelete('employee', ${e.id}, '${e.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    }).join('');

    const pag = document.getElementById('empPagination');
    if (totalPages <= 1) { pag.innerHTML = ''; return; }

    let btns = `<button class="pag-btn ${empPage === 1 ? 'disabled' : ''}" onclick="goEmpPage(${empPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
    for (let p = Math.max(1, empPage - 2); p <= Math.min(totalPages, empPage + 2); p++) {
        btns += `<button class="pag-btn ${p === empPage ? 'active' : ''}" onclick="goEmpPage(${p})">${p}</button>`;
    }
    btns += `<button class="pag-btn ${empPage === totalPages ? 'disabled' : ''}" onclick="goEmpPage(${empPage + 1})"><i class="fas fa-chevron-right"></i></button>`;

    pag.innerHTML = `
        <div class="pag-info">Showing ${start + 1}–${Math.min(start + EMP_PER_PAGE, data.length)} of ${data.length} employees</div>
        <div class="pag-btns">${btns}</div>`;
}

function goEmpPage(p) {
    empPage = p;
    renderActive();
    document.getElementById('empCardGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openViewModal(id) {
    const e = allEmployees.find(x => x.id == id);
    if (!e) return;
    const nbiDays = daysUntil(e.nbi_validity);
    const nbiClass = nbiDays === null ? '' : nbiDays < 0 ? 'expiry-expired' : nbiDays <= 30 ? 'expiry-warn' : '';
    const statusBadge = e.is_active == 1
        ? '<span class="badge badge-active"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Active</span>'
        : '<span class="badge badge-inactive"><i class="fas fa-circle" style="font-size:6px;vertical-align:middle"></i> Inactive</span>';

    const row = (label, val, cls='') =>
        `<div class="vm-field"><div class="vm-label">${label}</div><div class="vm-val ${cls}">${val||'—'}</div></div>`;

    document.getElementById('viewModalContent').innerHTML = `
        <div class="vm-header">
            <div class="vm-avatar" style="background:linear-gradient(${e.color||'135deg,#1245a8,#42a5f5'})">${initials(e.name)}</div>
            <div class="vm-header-info">
                <div class="vm-name">${e.name}</div>
                <div class="vm-position">${e.position||'—'} &bull; ${e.department||'—'}</div>
                <div class="vm-id">${e.employee_id||'—'} &bull; ${e.employment_type||'—'}</div>
            </div>
            <div>${statusBadge}</div>
        </div>
        <div class="vm-section-title"><i class="fas fa-user"></i> Basic Information</div>
        <div class="vm-grid">
            ${row('Phone', e.phone)}
            ${row('Sex', e.sex)}
            ${row('Date of Birth', fmtDate(e.date_of_birth))}
            ${row('Start Date', fmtDate(e.hire_date))}
            ${row('Address', e.address, 'full')}
        </div>
        <div class="vm-section-title"><i class="fas fa-id-badge"></i> Government IDs</div>
        <div class="vm-grid">
            ${row('SSS', e.sss)}
            ${row('PhilHealth', e.philhealth)}
            ${row('HDMF / Pag-ibig', e.hdmf)}
            ${row('TIN', e.tin)}
            ${row("Driver's License", e.driver_license)}
            ${row('NBI Validity', `<span class="${nbiClass}">${fmtDate(e.nbi_validity)}</span>`)}
        </div>
        <div class="vm-section-title"><i class="fas fa-phone-alt"></i> Emergency Contact</div>
        <div class="vm-grid">
            ${row('Name', e.contact_person_name)}
            ${row('Number', e.contact_person_number)}
            ${row('Address', e.contact_person_address, 'full')}
        </div>
    `;

    document.getElementById('viewModalEditBtn').onclick = () => { closeViewModal(); openEmpModal(id); };
    document.getElementById('viewModal').classList.add('open');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('open');
}

function renderUnemployed(q) {
    const tbody = document.getElementById('unempTbody');
    let data = allUnemployed.filter(e =>
        !q || e.name.toLowerCase().includes(q) ||
        (e.employee_id||'').toLowerCase().includes(q) ||
        (e.position||'').toLowerCase().includes(q)
    );
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="no-data"><i class="fas fa-user-slash"></i><p>No records found.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = data.map(e => `<tr>
        <td>
            <div class="emp-name-cell">
                <div class="emp-avatar-sm" style="background:linear-gradient(135deg,#37474f,#607d8b)">${initials(e.name)}</div>
                <div>
                    <div class="emp-name-main">${e.name}</div>
                    <div class="emp-id-sm">${e.employee_id||'—'}</div>
                </div>
            </div>
        </td>
        <td>${e.position||'—'}</td>
        <td class="muted">${e.phone||'—'}</td>
        <td class="muted">${fmtDate(e.start_date)}</td>
        <td class="muted">${fmtDate(e.end_date)}</td>
        <td><span class="badge badge-resigned">${e.status||'Resigned'}</span></td>
        <td>
            <div class="action-cell">
                <button class="act-btn" title="Edit" onclick="openUnempModal(${e.id})"><i class="fas fa-edit"></i></button>
                <button class="act-btn del" title="Delete" onclick="confirmDelete('unemployed', ${e.id}, '${e.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    </tr>`).join('');
}

function renderContracts(q) {
    const tbody = document.getElementById('contractTbody');
    let data = allContracts.filter(c =>
        !q || c.name.toLowerCase().includes(q) ||
        (c.position||'').toLowerCase().includes(q)
    );
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="no-data"><i class="fas fa-file-contract"></i><p>No contracts found.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = data.map(c => {
        const ec = expiryClass(c.end_date);
        return `<tr>
            <td>
                <div class="emp-name-cell">
                    <div class="emp-avatar-sm" style="background:linear-gradient(135deg,#1b5e20,#2e7d32)">${initials(c.name)}</div>
                    <div class="emp-name-main">${c.name}</div>
                </div>
            </td>
            <td>${c.position||'—'}</td>
            <td class="muted">${fmtDate(c.start_date)}</td>
            <td class="${ec}">${fmtDate(c.end_date)}</td>
            <td>${contractStatus(c.end_date)}</td>
            <td>
                <div class="action-cell">
                    <button class="act-btn" title="Edit" onclick="openContractModal(${c.id})"><i class="fas fa-edit"></i></button>
                    <button class="act-btn del" title="Delete" onclick="confirmDelete('contract', ${c.id}, '${c.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = p.dataset.panel === tab ? 'block' : 'none');
    document.getElementById('addBtn').style.display         = tab === 'employees'  ? 'flex' : 'none';
    document.getElementById('addUnempBtn').style.display    = tab === 'unemployed' ? 'flex' : 'none';
    document.getElementById('addContractBtn').style.display = tab === 'contracts'  ? 'flex' : 'none';
    renderActive();
}

function openEmpModal(id) {
    const emp = id ? allEmployees.find(e => e.id == id) : null;
    document.getElementById('empModalTitle').textContent = id ? 'Edit Employee' : 'Add Employee';
    document.getElementById('empId').value           = id || '';
    document.getElementById('fEmpNo').value          = emp?.employee_id || '';
    document.getElementById('fName').value           = emp?.name || '';
    document.getElementById('fHireDate').value       = emp?.hire_date || '';
    document.getElementById('fPosition').value       = emp?.position || '';
    document.getElementById('fDept').value           = emp?.department || 'Field';
    document.getElementById('fEmpType').value        = emp?.employment_type || 'Full Time';
    document.getElementById('fPhone').value          = emp?.phone || '';
    document.getElementById('fAddress').value        = emp?.address || '';
    document.getElementById('fDob').value            = emp?.date_of_birth || '';
    document.getElementById('fStatus').value         = emp?.status || 'Active';
    document.getElementById('fSex').value            = emp?.sex || '';
    document.getElementById('fDriverLicense').value  = emp?.driver_license || '';
    document.getElementById('fSSS').value            = emp?.sss || '';
    document.getElementById('fPhilhealth').value     = emp?.philhealth || '';
    document.getElementById('fHDMF').value           = emp?.hdmf || '';
    document.getElementById('fTIN').value            = emp?.tin || '';
    document.getElementById('fNBI').value            = emp?.nbi_validity || '';
    document.getElementById('fCPName').value         = emp?.contact_person_name || '';
    document.getElementById('fCPNumber').value       = emp?.contact_person_number || '';
    document.getElementById('fCPAddress').value      = emp?.contact_person_address || '';
    document.getElementById('empModal').classList.add('open');
}

function openUnempModal(id) {
    const e = id ? allUnemployed.find(u => u.id == id) : null;
    document.getElementById('unempModalTitle').textContent = id ? 'Edit Record' : 'Add Unemployed';
    document.getElementById('unempId').value       = id || '';
    document.getElementById('uEmpNo').value        = e?.employee_id || '';
    document.getElementById('uName').value         = e?.name || '';
    document.getElementById('uPosition').value     = e?.position || '';
    document.getElementById('uPhone').value        = e?.phone || '';
    document.getElementById('uAddress').value      = e?.address || '';
    document.getElementById('uDob').value          = e?.date_of_birth || '';
    document.getElementById('uStartDate').value    = e?.start_date || '';
    document.getElementById('uEndDate').value      = e?.end_date || '';
    document.getElementById('uStatus').value       = e?.status || 'Resigned';
    document.getElementById('unempModal').classList.add('open');
}

function openContractModal(id) {
    const c = id ? allContracts.find(x => x.id == id) : null;
    document.getElementById('contractModalTitle').textContent = id ? 'Edit Contract' : 'Add Contract';
    document.getElementById('contractId').value    = id || '';
    document.getElementById('cName').value         = c?.name || '';
    document.getElementById('cPosition').value     = c?.position || '';
    document.getElementById('cStartDate').value    = c?.start_date || '';
    document.getElementById('cEndDate').value      = c?.end_date || '';
    document.getElementById('contractModal').classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

async function saveEmployee() {
    const id = document.getElementById('empId').value;
    const payload = {
        id:                     id || null,
        employee_id:            document.getElementById('fEmpNo').value,
        name:                   document.getElementById('fName').value,
        hire_date:              document.getElementById('fHireDate').value,
        position:               document.getElementById('fPosition').value,
        department:             document.getElementById('fDept').value,
        employment_type:        document.getElementById('fEmpType').value,
        phone:                  document.getElementById('fPhone').value,
        address:                document.getElementById('fAddress').value,
        date_of_birth:          document.getElementById('fDob').value,
        status:                 document.getElementById('fStatus').value,
        sex:                    document.getElementById('fSex').value,
        driver_license:         document.getElementById('fDriverLicense').value,
        sss:                    document.getElementById('fSSS').value,
        philhealth:             document.getElementById('fPhilhealth').value,
        hdmf:                   document.getElementById('fHDMF').value,
        tin:                    document.getElementById('fTIN').value,
        nbi_validity:           document.getElementById('fNBI').value,
        contact_person_name:    document.getElementById('fCPName').value,
        contact_person_number:  document.getElementById('fCPNumber').value,
        contact_person_address: document.getElementById('fCPAddress').value,
    };
    if (!payload.name) { showToast('Name is required.', true); return; }
    const res = await apiPost(id ? 'update_employee' : 'add_employee', payload);
    if (res?.success) { showToast(id ? 'Employee updated.' : 'Employee added.'); closeModal('empModal'); loadAll(); }
    else showToast(res?.message || 'Save failed.', true);
}

async function saveUnemployed() {
    const id = document.getElementById('unempId').value;
    const payload = {
        id:            id || null,
        employee_id:   document.getElementById('uEmpNo').value,
        name:          document.getElementById('uName').value,
        position:      document.getElementById('uPosition').value,
        phone:         document.getElementById('uPhone').value,
        address:       document.getElementById('uAddress').value,
        date_of_birth: document.getElementById('uDob').value,
        start_date:    document.getElementById('uStartDate').value,
        end_date:      document.getElementById('uEndDate').value,
        status:        document.getElementById('uStatus').value,
    };
    if (!payload.name) { showToast('Name is required.', true); return; }
    const res = await apiPost(id ? 'update_unemployed' : 'add_unemployed', payload);
    if (res?.success) { showToast('Record saved.'); closeModal('unempModal'); loadAll(); }
    else showToast(res?.message || 'Save failed.', true);
}

async function saveContract() {
    const id = document.getElementById('contractId').value;
    const payload = {
        id:         id || null,
        name:       document.getElementById('cName').value,
        position:   document.getElementById('cPosition').value,
        start_date: document.getElementById('cStartDate').value,
        end_date:   document.getElementById('cEndDate').value,
    };
    if (!payload.name) { showToast('Name is required.', true); return; }
    const res = await apiPost(id ? 'update_contract' : 'add_contract', payload);
    if (res?.success) { showToast('Contract saved.'); closeModal('contractModal'); loadAll(); }
    else showToast(res?.message || 'Save failed.', true);
}

let _deleteTarget = null;
function confirmDelete(type, id, name) {
    _deleteTarget = { type, id };
    document.getElementById('confirmMsg').textContent = `Remove "${name}" from records? This cannot be undone.`;
    document.getElementById('confirmOverlay').classList.add('open');
}

async function doDelete() {
    if (!_deleteTarget) return;
    const { type, id } = _deleteTarget;
    const actionMap = { employee: 'delete_employee', unemployed: 'delete_unemployed', contract: 'delete_contract' };
    const res = await apiPost(actionMap[type], { id });
    document.getElementById('confirmOverlay').classList.remove('open');
    _deleteTarget = null;
    if (res?.success) { showToast('Record deleted.'); loadAll(); }
    else showToast('Delete failed.', true);
}

function showToast(msg, isError = false) {
    const t = document.getElementById('sra-toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.toggle('error', isError);
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    loadAll();

    document.getElementById('searchInput').addEventListener('input', () => {
        empPage = 1;
        renderActive();
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.onclick = () => switchTab(btn.dataset.tab);
    });

    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    if (dropBtn) {
        dropBtn.onclick = e => { e.stopPropagation(); const o = dropMenu.classList.toggle('open'); dropBtn.classList.toggle('open', o); };
        document.addEventListener('click', () => { dropMenu.classList.remove('open'); dropBtn.classList.remove('open'); });
    }

    document.getElementById('confirmDeleteBtn').onclick  = doDelete;
    document.getElementById('cancelDeleteBtn').onclick   = () => document.getElementById('confirmOverlay').classList.remove('open');
    document.getElementById('saveEmpBtn').onclick        = saveEmployee;
    document.getElementById('saveUnempBtn').onclick      = saveUnemployed;
    document.getElementById('saveContractBtn').onclick   = saveContract;

    document.getElementById('viewModal').addEventListener('click', e => {
        if (e.target === document.getElementById('viewModal')) closeViewModal();
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.onclick = () => btn.closest('.modal-overlay').classList.remove('open');
    });
    document.querySelectorAll('.modal-overlay').forEach(ov => {
        ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
    });

    const mBtn    = document.getElementById('mobileHamburgerBtn');
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileNavOverlay');
    const mClose  = document.getElementById('mobileDrawerClose');
    const openD  = () => { drawer.classList.add('open'); overlay.classList.add('visible'); };
    const closeD = () => { drawer.classList.remove('open'); overlay.classList.remove('visible'); };
    if (mBtn)    mBtn.addEventListener('click', openD);
    if (mClose)  mClose.addEventListener('click', closeD);
    if (overlay) overlay.addEventListener('click', closeD);
});
