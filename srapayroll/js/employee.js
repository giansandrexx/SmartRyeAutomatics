    document.getElementById('headerDate').textContent =
        new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

    const dropBtn  = document.getElementById('userDropdownBtn');
    const dropMenu = document.getElementById('userDropdownMenu');
    dropBtn.addEventListener('click', () => { dropBtn.classList.toggle('open'); dropMenu.classList.toggle('open'); });
    document.addEventListener('click', e => {
        if (!dropBtn.contains(e.target) && !dropMenu.contains(e.target)) { dropBtn.classList.remove('open'); dropMenu.classList.remove('open'); }
    });

    const empModal   = document.getElementById('empModal');
    const confirmOvl = document.getElementById('confirmOverlay');
    const grid       = document.getElementById('empGrid');

    function showToast(msg, isError = false) {
        const t  = document.getElementById('sra-toast');
        const ic = t.querySelector('i');
        document.getElementById('toastMsg').textContent = msg;
        t.className = isError ? 'error' : '';
        ic.className = isError ? 'fas fa-times-circle' : 'fas fa-check-circle';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function updateCount() {
        const visible = grid.querySelectorAll('.emp-card:not([style*="display: none"])').length;
        const total   = grid.querySelectorAll('.emp-card').length;
        document.getElementById('empCount').innerHTML = `Showing <strong>${visible}</strong> of <strong>${total}</strong> employees`;
    }
    updateCount();

    document.getElementById('searchInput').addEventListener('input', filterGrid);
    document.getElementById('deptFilter').addEventListener('change', filterGrid);

    function filterGrid() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const d = document.getElementById('deptFilter').value.toLowerCase();
        grid.querySelectorAll('.emp-card').forEach(card => {
            const nameMatch = card.dataset.name.includes(q);
            const deptMatch = !d || card.dataset.dept.toLowerCase() === d;
            card.style.display = (nameMatch && deptMatch) ? '' : 'none';
        });
        updateCount();
    }

    document.getElementById('fRate').addEventListener('input', function() {
        const rate   = parseFloat(this.value) || 0;
        const annual = rate * 313;
        document.getElementById('annualHint').textContent = rate > 0 ? `Annual ≈ ₱${annual.toLocaleString()}` : '';
    });

    function clearModal() {
        document.getElementById('fId').value       = '';
        document.getElementById('fEmpId').value    = '';
        document.getElementById('fName').value     = '';
        document.getElementById('fPhone').value    = '';
        document.getElementById('fDept').value     = '';
        document.getElementById('fPosition').value = '';
        document.getElementById('fEmpType').value  = 'Full Time';
        document.getElementById('fRate').value     = '';
        document.getElementById('fHire').value     = '';
        document.getElementById('annualHint').textContent = '';
    }

    function openModal(mode, data = {}) {
        clearModal();
        document.getElementById('fId').value       = data.id       || '';
        document.getElementById('fEmpId').value    = data.empid    || '';
        document.getElementById('fName').value     = data.name     || '';
        document.getElementById('fPhone').value    = data.phone    || '';
        document.getElementById('fDept').value     = data.dept     || '';
        document.getElementById('fPosition').value = data.position || '';
        document.getElementById('fEmpType').value  = data.emptype  || 'Full Time';
        document.getElementById('fRate').value     = data.rate     || '';
        document.getElementById('fHire').value     = data.hire     || '';
        if (data.rate > 0) {
            document.getElementById('annualHint').textContent = `Annual ≈ ₱${(parseFloat(data.rate) * 313).toLocaleString()}`;
        }
        const isEdit = mode === 'edit';
        document.getElementById('modalTitle').innerHTML = isEdit
            ? '<i class="fas fa-user-edit"></i> Edit Employee'
            : '<i class="fas fa-user-plus"></i> Add Employee';
        document.getElementById('saveBtnText').textContent = isEdit ? 'Update' : 'Save';
        empModal.classList.add('open');
        setTimeout(() => document.getElementById('fEmpId').focus(), 100);
    }

    function closeModal() { empModal.classList.remove('open'); }

    document.getElementById('addEmpBtn').addEventListener('click', () => openModal('add'));
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('cancelEmpBtn').addEventListener('click', closeModal);
    empModal.addEventListener('click', e => { if (e.target === empModal) closeModal(); });

    grid.addEventListener('click', e => {
        const editBtn = e.target.closest('.act-btn.edit');
        const delBtn  = e.target.closest('.act-btn.del');
        if (editBtn) {
            openModal('edit', {
                id:       editBtn.dataset.id,
                empid:    editBtn.dataset.empid,
                name:     editBtn.dataset.name,
                phone:    editBtn.dataset.phone,
                dept:     editBtn.dataset.dept,
                position: editBtn.dataset.position,
                emptype:  editBtn.dataset.emptype,
                rate:     editBtn.dataset.rate,
                hire:     editBtn.dataset.hire,
            });
        }
        if (delBtn) {
            document.getElementById('confirmMsg').textContent = `Remove ${delBtn.dataset.name} from the payroll system?`;
            document.getElementById('confirmDeleteBtn').dataset.id   = delBtn.dataset.id;
            document.getElementById('confirmDeleteBtn').dataset.name = delBtn.dataset.name;
            confirmOvl.classList.add('open');
        }
    });

    document.getElementById('cancelDeleteBtn').addEventListener('click', () => confirmOvl.classList.remove('open'));
    confirmOvl.addEventListener('click', e => { if (e.target === confirmOvl) confirmOvl.classList.remove('open'); });

    document.getElementById('saveEmpBtn').addEventListener('click', async () => {
        const id       = document.getElementById('fId').value;
        const employee_id = document.getElementById('fEmpId').value.trim();
        const name     = document.getElementById('fName').value.trim();
        const phone    = document.getElementById('fPhone').value.trim();
        const dept     = document.getElementById('fDept').value;
        const position = document.getElementById('fPosition').value.trim();
        const emptype  = document.getElementById('fEmpType').value;
        const rate     = document.getElementById('fRate').value;
        const hire     = document.getElementById('fHire').value;

        if (!employee_id) { showToast('Employee ID is required.', true); document.getElementById('fEmpId').focus(); return; }
        if (!name)        { showToast('Full name is required.', true);   document.getElementById('fName').focus();  return; }
        if (!dept)        { showToast('Department is required.', true);  document.getElementById('fDept').focus();  return; }
        if (!position)    { showToast('Position is required.', true);    document.getElementById('fPosition').focus(); return; }

        const action = id ? 'edit_employee' : 'add_employee';
        const res = await fetch(`employees_api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, employee_id, name, phone, department: dept, position, employment_type: emptype, daily_rate: rate, hire_date: hire })
        });
        const data = await res.json();
        if (data.success) {
            showToast(id ? 'Employee updated successfully.' : 'Employee added successfully.');
            closeModal();
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Something went wrong.', true);
        }
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        const id = this.dataset.id;
        const res = await fetch(`employees_api.php?action=delete_employee`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Employee removed successfully.');
            confirmOvl.classList.remove('open');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Something went wrong.', true);
        }
    });

    document.getElementById('fEmpId').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('fName').focus(); });
    document.getElementById('fName').addEventListener('keydown',  e => { if (e.key === 'Enter') document.getElementById('fPhone').focus(); });