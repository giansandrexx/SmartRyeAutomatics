(function () {

    const MONTHS     = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DAYS_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const DAYS_FULL  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const API        = window.SCHEDULING_API || 'events_api.php';

    const STATUS_LABELS = { pending:'Pending', confirmed:'Confirmed', completed:'Completed', cancelled:'Cancelled' };
    const STATUS_COLORS = { pending:'#f59e0b', confirmed:'#3b82f6', completed:'#10b981', cancelled:'#ef4444' };
    const CAT_COLORS    = { meeting:'#1a6ed8', maintenance:'#c62828', training:'#2e7d32', inspection:'#e65100', other:'#6a1b9a' };

    let currentDate    = new Date();
    let viewMonth      = currentDate.getMonth();
    let viewYear       = currentDate.getFullYear();
    let currentView    = 'month';
    let editingEventId = null;
    let selectedDate   = null;
    let eventsCache    = [];
    let searchResults  = null;
    let searchTimeout  = null;
    const fetchedMonths = new Set();
    let pendingDeleteId = null;

    function fmtDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    function fmt12(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        let hr = parseInt(h, 10);
        const ap = hr >= 12 ? 'PM' : 'AM';
        hr = hr % 12 || 12;
        return `${hr}:${m} ${ap}`;
    }

    function esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function norm(ev) {
        return {
            id:       ev.id,
            title:    ev.title    || '',
            date:     ev.date     || '',
            start:    ev.start    ?? (ev.start_time ? ev.start_time.slice(0,5) : ''),
            end:      ev.end      ?? (ev.end_time   ? ev.end_time.slice(0,5)   : ''),
            category: ev.category || 'other',
            status:   ev.status   || 'pending',
            assignee: ev.assignee ?? '',
            notes:    ev.notes    ?? '',
        };
    }

    function badge(status) {
        const c = STATUS_COLORS[status] || '#888';
        const l = STATUS_LABELS[status] || status;
        return `<span style="background:${c}22;color:${c};border:1px solid ${c}88;border-radius:12px;padding:2px 10px;font-size:11px;font-weight:600;">${l}</span>`;
    }

    function toast(msg, type = 'success') {
        let t = document.getElementById('sra-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'sra-toast';
            t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:500;color:#fff;box-shadow:0 4px 20px rgba(0,0,0,.25);transition:opacity .3s;pointer-events:none;';
            document.body.appendChild(t);
        }
        t.style.background = type === 'error' ? '#ef4444' : '#10b981';
        t.textContent = msg;
        t.style.opacity = '1';
        clearTimeout(t._to);
        t._to = setTimeout(() => t.style.opacity = '0', 3000);
    }

    async function api(url, opts = {}) {
        const res = await fetch(url, opts);
        let json;
        try { json = await res.json(); } catch (_) {
            throw new Error(`HTTP ${res.status}: invalid JSON from server`);
        }
        if (!json.success) throw new Error(json.message || 'Request failed');
        return json;
    }

    async function fetchMonth(year, month, force = false) {
        const key = `${year}-${month}`;
        if (!force && fetchedMonths.has(key)) return;
        fetchedMonths.add(key);
        const json   = await api(`${API}?action=get_by_month&year=${year}&month=${month + 1}`);
        const prefix = `${year}-${String(month + 1).padStart(2,'0')}`;
        eventsCache  = eventsCache.filter(e => !e.date.startsWith(prefix));
        eventsCache.push(...json.data.map(norm));
    }

    function prefetchMonth(year, month) {
        const key = `${year}-${month}`;
        if (fetchedMonths.has(key)) return;
        fetchMonth(year, month).then(() => {
            if (currentView === 'month' && viewYear === year && viewMonth === month) renderMonth();
            else if (currentView === 'year') renderYear();
        }).catch(() => {});
    }

    async function refreshMonth(year, month) {
        fetchedMonths.delete(`${year}-${month}`);
        await fetchMonth(year, month, true);
        buildUpcomingWidget();
    }

    function eventsOn(dateStr) {
        const src = searchResults ?? eventsCache;
        return src.filter(e => e.date === dateStr);
    }

    function eventsInMonth(year, month) {
        const prefix = `${year}-${String(month + 1).padStart(2,'0')}`;
        const src    = searchResults ?? eventsCache;
        return src.filter(e => e.date.startsWith(prefix));
    }

    function renderMonth() {
        document.getElementById('calMonth').textContent = MONTHS[viewMonth];
        document.getElementById('calYear').textContent  = viewYear;

        const wrap      = document.getElementById('calendarDays');
        wrap.innerHTML  = '';
        const firstDay  = new Date(viewYear, viewMonth, 1).getDay();
        const daysInMo  = new Date(viewYear, viewMonth + 1, 0).getDate();
        const daysInPrv = new Date(viewYear, viewMonth, 0).getDate();
        const todayStr  = fmtDate(currentDate);

        let cells = [];
        for (let i = firstDay - 1; i >= 0; i--)
            cells.push({ day: daysInPrv - i, str: fmtDate(new Date(viewYear, viewMonth - 1, daysInPrv - i)), cur: false });
        for (let d = 1; d <= daysInMo; d++)
            cells.push({ day: d, str: fmtDate(new Date(viewYear, viewMonth, d)), cur: true });
        const rem = 42 - cells.length;
        for (let d = 1; d <= rem; d++)
            cells.push({ day: d, str: fmtDate(new Date(viewYear, viewMonth + 1, d)), cur: false });

        cells.forEach((cell, idx) => {
            const div = document.createElement('div');
            div.className = 'day-cell';
            if (!cell.cur)             div.classList.add('other-month');
            if (cell.str === todayStr) div.classList.add('today');
            if (idx % 7 === 0)         div.classList.add('sunday');

            const numDiv = document.createElement('div');
            numDiv.className   = 'day-num';
            numDiv.textContent = cell.day;
            div.appendChild(numDiv);

            const evts  = eventsOn(cell.str);
            const evDiv = document.createElement('div');
            evDiv.className = 'day-events';
            evts.slice(0, 3).forEach(ev => {
                const chip = document.createElement('div');
                chip.className = `event-chip ${ev.category}`;
                const dot = ev.status !== 'pending'
                    ? `<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:${STATUS_COLORS[ev.status]};margin-right:4px;vertical-align:middle;"></span>` : '';
                chip.innerHTML = dot + (ev.start ? fmt12(ev.start) + ' ' : '') + esc(ev.title);
                chip.addEventListener('click', e => { e.stopPropagation(); openDay(cell.str); });
                evDiv.appendChild(chip);
            });
            if (evts.length > 3) {
                const more = document.createElement('div');
                more.className   = 'more-events';
                more.textContent = `+${evts.length - 3} more`;
                evDiv.appendChild(more);
            }
            div.appendChild(evDiv);
            div.addEventListener('click', () => openDay(cell.str));
            wrap.appendChild(div);
        });
    }

    function renderYear() {
        document.getElementById('calMonth').textContent = '';
        document.getElementById('calYear').textContent  = viewYear;
        const grid     = document.getElementById('yearGrid');
        grid.innerHTML = '';
        const todayStr = fmtDate(currentDate);

        for (let m = 0; m < 12; m++) {
            const wrap   = document.createElement('div');
            wrap.className = 'mini-month';
            const hdr    = document.createElement('div');
            hdr.className   = 'mini-month-header';
            hdr.textContent = MONTHS[m];
            wrap.appendChild(hdr);
            const mgrid = document.createElement('div');
            mgrid.className = 'mini-month-grid';
            DAYS_SHORT.forEach(d => {
                const dh = document.createElement('div');
                dh.className = 'mini-day-head'; dh.textContent = d[0];
                mgrid.appendChild(dh);
            });
            const firstDay  = new Date(viewYear, m, 1).getDay();
            const daysInMo  = new Date(viewYear, m + 1, 0).getDate();
            const daysInPrv = new Date(viewYear, m, 0).getDate();
            const evDates   = new Set(eventsInMonth(viewYear, m).map(e => e.date));
            for (let i = firstDay - 1; i >= 0; i--) {
                const d = document.createElement('div');
                d.className = 'mini-day other'; d.textContent = daysInPrv - i;
                mgrid.appendChild(d);
            }
            for (let day = 1; day <= daysInMo; day++) {
                const ds = fmtDate(new Date(viewYear, m, day));
                const d  = document.createElement('div');
                d.className = 'mini-day'; d.textContent = day;
                if (ds === todayStr)   d.classList.add('today');
                if (evDates.has(ds))   d.classList.add('has-event');
                d.addEventListener('click', e => { e.stopPropagation(); openDay(ds); });
                mgrid.appendChild(d);
            }
            const filled = firstDay + daysInMo;
            const trail  = filled % 7 === 0 ? 0 : 7 - (filled % 7);
            for (let d = 1; d <= trail; d++) {
                const el = document.createElement('div');
                el.className = 'mini-day other'; el.textContent = d;
                mgrid.appendChild(el);
            }
            wrap.appendChild(mgrid);
            wrap.addEventListener('click', () => goMonth(m));
            grid.appendChild(wrap);
        }
    }

    function goMonth(m) {
        viewMonth = m; currentView = 'month';
        document.getElementById('monthView').style.display = '';
        document.getElementById('yearView').style.display  = 'none';
        document.getElementById('monthViewBtn').classList.add('active');
        document.getElementById('yearViewBtn').classList.remove('active');
        renderMonth();
        prefetchMonth(viewYear, viewMonth);
    }

    function openDay(dateStr) {
        selectedDate = dateStr;
        const d      = new Date(dateStr + 'T00:00:00');
        document.getElementById('modalDate').textContent = `${MONTHS[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
        document.getElementById('modalDay').textContent  = DAYS_FULL[d.getDay()];
        document.getElementById('eventDate').value       = dateStr;
        renderDayList(dateStr);
        document.getElementById('dayModal').classList.add('open');
    }

    function renderDayList(dateStr) {
        const list   = document.getElementById('eventsList');
        const events = eventsOn(dateStr);
        list.innerHTML = '';
        if (!events.length) {
            list.innerHTML = `<div class="empty-events"><i class="fas fa-calendar-times"></i><p>No events scheduled.</p></div>`;
            return;
        }
        events.forEach(ev => {
            const item = document.createElement('div');
            item.className    = `event-item ${ev.category}`;
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="event-item-info">
                    <div class="event-item-title">${esc(ev.title)}</div>
                    <div class="event-item-meta">
                        ${ev.start ? `<span><i class="fas fa-clock"></i>${fmt12(ev.start)}${ev.end ? ' – '+fmt12(ev.end) : ''}</span>` : ''}
                        ${ev.assignee ? `<span><i class="fas fa-user"></i>${esc(ev.assignee)}</span>` : ''}
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                    <span class="event-category-tag ${ev.category}">${ev.category}</span>
                    ${badge(ev.status)}
                    <div class="event-item-actions">
                        <button class="btn-icon edit-btn" data-id="${ev.id}" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon delete delete-btn" data-id="${ev.id}" data-title="${esc(ev.title)}" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            item.addEventListener('click', e => { if (!e.target.closest('.event-item-actions')) openDetail(ev.id); });
            list.appendChild(item);
        });
        list.querySelectorAll('.edit-btn').forEach(b =>
            b.addEventListener('click', e => { e.stopPropagation(); openEditForm(+b.dataset.id); }));
        list.querySelectorAll('.delete-btn').forEach(b =>
            b.addEventListener('click', e => { e.stopPropagation(); openDeleteConfirm(+b.dataset.id, b.dataset.title); }));
    }

    function openDetail(id) {
        const ev = eventsCache.find(e => e.id == id);
        if (!ev) return;
        const cats = {
            meeting:['#0d2f6e','#1a6ed8'], maintenance:['#7f0000','#c62828'],
            training:['#1b5e20','#2e7d32'], inspection:['#bf360c','#e65100'], other:['#4a148c','#6a1b9a']
        };
        const [c1, c2] = cats[ev.category] || cats.meeting;
        document.getElementById('detailHeader').style.background = `linear-gradient(135deg,${c1},${c2})`;
        document.getElementById('detailTitle').textContent    = ev.title;
        document.getElementById('detailCategory').textContent = ev.category.charAt(0).toUpperCase() + ev.category.slice(1);
        document.getElementById('detailCategory').className   = `event-category-tag ${ev.category}`;
        const d = new Date(ev.date + 'T00:00:00');
        document.getElementById('detailDate').textContent = `${DAYS_FULL[d.getDay()]}, ${MONTHS[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
        document.getElementById('detailTimeRow').style.display     = ev.start    ? 'flex' : 'none';
        if (ev.start) document.getElementById('detailTime').textContent = fmt12(ev.start) + (ev.end ? ' – ' + fmt12(ev.end) : '');
        document.getElementById('detailAssigneeRow').style.display = ev.assignee ? 'flex' : 'none';
        document.getElementById('detailAssignee').textContent      = ev.assignee || '';
        document.getElementById('detailNotesRow').style.display    = ev.notes    ? 'flex' : 'none';
        document.getElementById('detailNotes').textContent         = ev.notes    || '';
        const sel = document.getElementById('detailStatusSelect');
        if (sel) { sel.value = ev.status; sel.dataset.id = ev.id; }
        document.getElementById('detailEditBtn').dataset.id   = ev.id;
        document.getElementById('detailDeleteBtn').dataset.id = ev.id;
        document.getElementById('detailDeleteBtn').dataset.title = ev.title;
        loadLogs(ev.id);
        document.getElementById('dayModal').classList.remove('open');
        document.getElementById('eventDetailModal').classList.add('open');
    }

    async function loadLogs(eventId) {
        const box = document.getElementById('activityLog');
        if (!box) return;
        box.innerHTML = '<div style="color:#8fa3be;font-size:13px;padding:8px 0;">Loading…</div>';
        try {
            const json = await api(`${API}?action=get_logs&event_id=${eventId}`);
            renderLogs(json.data);
        } catch (_) {
            box.innerHTML = '<div style="color:#8fa3be;font-size:13px;padding:4px 0;">No activity yet.</div>';
        }
    }

    function renderLogs(logs) {
        const box = document.getElementById('activityLog');
        if (!box) return;
        if (!logs || !logs.length) {
            box.innerHTML = '<div style="color:#8fa3be;font-size:13px;padding:4px 0;">No activity yet.</div>';
            return;
        }
        const icons = { comment:'fa-comment', status_change:'fa-exchange-alt', edit:'fa-edit', created:'fa-plus-circle', deleted:'fa-trash' };
        box.innerHTML = logs.map(log => {
            const ic    = icons[log.type] || 'fa-info-circle';
            const color = log.type === 'comment' ? '#1a6ed8' : '#8fa3be';
            const dt    = new Date(log.created_at.replace(' ', 'T'));
            const time  = dt.toLocaleDateString('en-PH',{month:'short',day:'numeric'}) + ' ' +
                          dt.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
            return `<div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;">
                <div style="width:26px;height:26px;border-radius:50%;background:${color}18;border:1px solid ${color}44;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                    <i class="fas ${ic}" style="font-size:11px;color:${color};"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;color:#8fa3be;">${esc(log.user_name)} · ${time}</div>
                    <div style="font-size:13px;color:#4a607d;margin-top:2px;">${esc(log.content)}</div>
                </div>
            </div>`;
        }).join('');
        box.scrollTop = box.scrollHeight;
    }

    function openAddForm(dateStr) {
        editingEventId = null;
        document.getElementById('eventModalTitle').textContent = 'Add Event';
        document.getElementById('eventTitle').value    = '';
        document.getElementById('eventDate').value     = dateStr || fmtDate(currentDate);
        document.getElementById('eventStart').value    = '';
        document.getElementById('eventEnd').value      = '';
        document.getElementById('eventCategory').value = 'meeting';
        document.getElementById('eventStatus').value   = 'pending';
        document.getElementById('eventAssignee').value = '';
        document.getElementById('eventNotes').value    = '';
        document.getElementById('eventModal').classList.add('open');
    }

    function openEditForm(id) {
        const ev = eventsCache.find(e => e.id == id);
        if (!ev) return;
        editingEventId = id;
        document.getElementById('eventModalTitle').textContent = 'Edit Event';
        document.getElementById('eventTitle').value    = ev.title;
        document.getElementById('eventDate').value     = ev.date;
        document.getElementById('eventStart').value    = ev.start    || '';
        document.getElementById('eventEnd').value      = ev.end      || '';
        document.getElementById('eventCategory').value = ev.category;
        document.getElementById('eventStatus').value   = ev.status   || 'pending';
        document.getElementById('eventAssignee').value = ev.assignee || '';
        document.getElementById('eventNotes').value    = ev.notes    || '';
        document.getElementById('dayModal').classList.remove('open');
        document.getElementById('eventDetailModal').classList.remove('open');
        document.getElementById('eventModal').classList.add('open');
    }

    function openDeleteConfirm(id, title) {
        pendingDeleteId = id;
        const nameEl = document.getElementById('deleteEventName');
        if (nameEl) nameEl.textContent = title || 'This event';
        document.getElementById('deleteConfirmModal').classList.add('open');
    }

    function closeDeleteConfirm() {
        pendingDeleteId = null;
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) modal.classList.remove('open');
    }

    async function saveEvent() {
        const title    = document.getElementById('eventTitle').value.trim();
        const date     = document.getElementById('eventDate').value;
        const start    = document.getElementById('eventStart').value    || null;
        const end      = document.getElementById('eventEnd').value      || null;
        const category = document.getElementById('eventCategory').value;
        const status   = document.getElementById('eventStatus').value;
        const assignee = document.getElementById('eventAssignee').value.trim() || null;
        const notes    = document.getElementById('eventNotes').value.trim()    || null;

        if (!title || !date) { toast('Title and date are required.', 'error'); return; }

        const btn = document.getElementById('saveEvent');
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

        try {
            const yr = new Date(date + 'T00:00:00').getFullYear();
            const mo = new Date(date + 'T00:00:00').getMonth();

            if (editingEventId !== null) {
                await api(API, {
                    method:'PUT', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({id:editingEventId,title,date,start,end,category,status,assignee,notes})
                });
                toast('Event updated');
            } else {
                await api(API, {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({title,date,start,end,category,status,assignee,notes})
                });
                toast('Event added');
            }

            await refreshMonth(yr, mo);
            document.getElementById('eventModal').classList.remove('open');
            if (currentView === 'month') renderMonth(); else renderYear();
            selectedDate = date;
            renderDayList(date);
            document.getElementById('dayModal').classList.add('open');

        } catch (e) {
            toast(e.message || 'Failed to save.', 'error');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save';
        }
    }

    async function deleteEvent(id) {
        const ev = eventsCache.find(e => e.id == id);
        if (!ev) return;

        const confirmBtn = document.getElementById('deleteConfirmBtn');
        if (confirmBtn) {
            confirmBtn.classList.add('loading');
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
        }

        try {
            await api(`${API}?action=delete&id=${id}`, {method:'DELETE'});
            const yr = new Date(ev.date + 'T00:00:00').getFullYear();
            const mo = new Date(ev.date + 'T00:00:00').getMonth();
            await refreshMonth(yr, mo);
            toast('Event deleted');
            closeDeleteConfirm();
            document.getElementById('eventDetailModal').classList.remove('open');
            if (currentView === 'month') renderMonth(); else renderYear();
            if (selectedDate) { renderDayList(selectedDate); document.getElementById('dayModal').classList.add('open'); }
        } catch (e) {
            toast(e.message || 'Delete failed.', 'error');
        } finally {
            if (confirmBtn) {
                confirmBtn.classList.remove('loading');
                confirmBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            }
        }
    }

    async function changeStatus(id, newStatus) {
        const sel = document.getElementById('detailStatusSelect');
        if (sel) sel.value = newStatus;
        try {
            const json    = await api(`${API}?action=update_status`, {
                method:'PUT', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id, status: newStatus})
            });
            const updated = norm(json.data);
            eventsCache   = eventsCache.map(e => e.id == id ? updated : e);
            if (currentView === 'month') renderMonth(); else renderYear();
            if (selectedDate) renderDayList(selectedDate);
            loadLogs(id);
            toast('Status updated');
        } catch (e) {
            const orig = eventsCache.find(ev => ev.id == id);
            if (sel && orig) sel.value = orig.status;
            toast(e.message || 'Status update failed.', 'error');
        }
    }

    function buildSearchBar() {
        const bar = document.getElementById('searchFilterBar');
        if (!bar) return;
        bar.innerHTML = `
            <div class="search-bar-inner">
                <div class="search-input-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search by title, assignee, notes…" class="search-input">
                    <button id="clearSearch" class="clear-search" style="display:none;"><i class="fas fa-times"></i></button>
                </div>
                <select id="filterCategory" class="filter-select">
                    <option value="">All Categories</option>
                    <option value="meeting">Meeting</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="training">Training</option>
                    <option value="inspection">Inspection</option>
                    <option value="other">Other</option>
                </select>
                <select id="filterStatus" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button id="exportBtn" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
            </div>
            <div id="searchResultsBar" style="display:none;" class="search-results-bar">
                <span id="searchResultCount" style="font-weight:600;color:#1a6ed8;"></span>
                <button id="clearSearchResults" class="btn-clear-results"><i class="fas fa-times"></i> Clear search</button>
            </div>`;

        document.getElementById('searchInput').addEventListener('input', doSearch);
        document.getElementById('filterCategory').addEventListener('change', doSearch);
        document.getElementById('filterStatus').addEventListener('change', doSearch);
        document.getElementById('clearSearch').addEventListener('click', clearSearch);
        document.getElementById('clearSearchResults').addEventListener('click', clearSearch);
        document.getElementById('exportBtn').addEventListener('click', exportCSV);
    }

    async function doSearch() {
        clearTimeout(searchTimeout);
        const q        = document.getElementById('searchInput').value.trim();
        const category = document.getElementById('filterCategory').value;
        const status   = document.getElementById('filterStatus').value;
        const clearBtn = document.getElementById('clearSearch');

        clearBtn.style.display = (q || category || status) ? '' : 'none';
        if (!q && !category && !status) { clearSearch(); return; }

        searchTimeout = setTimeout(async () => {
            try {
                const p    = new URLSearchParams({ action:'search', q, category, status });
                const json = await api(`${API}?${p}`);
                searchResults = json.data.map(norm);

                const bar   = document.getElementById('searchResultsBar');
                const count = document.getElementById('searchResultCount');
                bar.style.display = '';
                count.textContent = `${searchResults.length} result${searchResults.length !== 1 ? 's' : ''} found`;

                if (currentView === 'month') renderMonth(); else renderYear();
            } catch (_) { toast('Search failed', 'error'); }
        }, 300);
    }

    function clearSearch() {
        searchResults = null;
        ['searchInput','filterCategory','filterStatus'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const cb = document.getElementById('clearSearch');
        if (cb) cb.style.display = 'none';
        const rb = document.getElementById('searchResultsBar');
        if (rb) rb.style.display = 'none';
        if (currentView === 'month') renderMonth(); else renderYear();
    }

    async function exportCSV() {
        const eventsThisMonth = eventsInMonth(viewYear, viewMonth);
        if (!eventsThisMonth.length) {
            toast('No events to export for ' + MONTHS[viewMonth] + ' ' + viewYear, 'error');
            return;
        }
        const params = new URLSearchParams({ year: viewYear, month: viewMonth + 1 });
        const cat = document.getElementById('filterCategory')?.value || '';
        const st  = document.getElementById('filterStatus')?.value   || '';
        if (cat) params.set('category', cat);
        if (st)  params.set('status',   st);
        window.location.href = 'export_events.php?' + params.toString();
        toast('Downloading ' + MONTHS[viewMonth] + ' ' + viewYear + ' events…');
    }

    function buildUpcomingWidget() {
        const wrap = document.getElementById('upcomingWidget');
        if (!wrap) return;
        wrap.innerHTML = '';

        const today    = new Date();
        today.setHours(0, 0, 0, 0);
        const todayStr = fmtDate(today);

        const upcoming = eventsCache
            .filter(e => e.date >= todayStr && e.status !== 'cancelled')
            .sort((a, b) => a.date.localeCompare(b.date) || (a.start || '').localeCompare(b.start || ''));

        const todayCnt   = upcoming.filter(e => e.date === todayStr).length;
        const badgeClass = todayCnt > 0 ? 'uw-badge-urgent' : (upcoming.length > 0 ? 'uw-badge-normal' : 'uw-badge-empty');
        const badgeLabel = todayCnt > 0 ? `${todayCnt} today!` : (upcoming.length > 0 ? `${upcoming.length} events` : 'None');

        let html = `
            <div class="uw-header">
                <span class="uw-title"><i class="fas fa-bell"></i> Upcoming Events</span>
                <span class="uw-badge ${badgeClass}">${badgeLabel}</span>
            </div>
            <div class="uw-date-strip">
                As of ${today.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}
            </div>`;

        if (!upcoming.length) {
            html += `
                <div class="uw-empty">
                    <i class="fas fa-calendar-check"></i>
                    <p>No upcoming events scheduled.</p>
                </div>`;
            wrap.innerHTML = html;
            return;
        }

        const groups = {};
        upcoming.forEach(ev => {
            const key = ev.date.slice(0, 7);
            if (!groups[key]) groups[key] = [];
            groups[key].push(ev);
        });

        const monthKeys = Object.keys(groups).sort();
        html += `<div class="uw-accordion">`;

        monthKeys.forEach((key, idx) => {
            const events     = groups[key];
            const [yr, mo]   = key.split('-');
            const monthLabel = new Date(parseInt(yr), parseInt(mo) - 1, 1)
                .toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
            const hasToday   = events.some(e => e.date === todayStr);
            const isOpen     = idx === 0;

            html += `
                <div class="uw-month-group">
                    <div class="uw-month-toggle ${isOpen ? 'open' : ''}" onclick="window.uwToggleMonth(this)">
                        <div class="uw-month-toggle-left">
                            <span class="uw-month-dot ${hasToday ? 'has-today' : ''}"></span>
                            <span class="uw-month-name">${monthLabel}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="uw-month-count">${events.length}</span>
                            <i class="fas fa-chevron-down uw-month-chevron"></i>
                        </div>
                    </div>
                    <div class="uw-month-body ${isOpen ? 'open' : ''}">`;

            events.forEach(ev => {
                const evDate   = new Date(ev.date + 'T00:00:00');
                const diffDay  = Math.round((evDate - today) / 86400000);
                const dayName  = DAYS_FULL[evDate.getDay()];
                const dateLabel = `${dayName}, ${MONTHS[evDate.getMonth()]} ${evDate.getDate()}`;

                let whenClass, whenLabel;
                if (diffDay === 0)      { whenClass = 'uw-when-today'; whenLabel = 'Today'; }
                else if (diffDay === 1) { whenClass = 'uw-when-soon';  whenLabel = 'Tomorrow'; }
                else if (diffDay <= 7)  { whenClass = 'uw-when-soon';  whenLabel = `In ${diffDay}d`; }
                else                    { whenClass = 'uw-when-later'; whenLabel = `In ${diffDay}d`; }

                const catColor    = CAT_COLORS[ev.category] || CAT_COLORS.other;
                const statusColor = STATUS_COLORS[ev.status] || '#888';
                const statusLabel = STATUS_LABELS[ev.status] || ev.status;
                const timeText    = ev.start ? fmt12(ev.start) + (ev.end ? ' – ' + fmt12(ev.end) : '') : 'All day';

                html += `
                    <div class="uw-item" data-id="${ev.id}" style="--cat-color:${catColor};">
                        <div class="uw-item-accent"></div>
                        <div class="uw-item-body">
                            <div class="uw-item-top">
                                <span class="uw-item-title">${esc(ev.title)}</span>
                                <span class="uw-when ${whenClass}">${whenLabel}</span>
                            </div>
                            <div class="uw-item-date-row">
                                <i class="fas fa-calendar-alt"></i>
                                <span>${dateLabel}</span>
                            </div>
                            <div class="uw-item-meta">
                                <div class="uw-meta-pill">
                                    <i class="fas fa-clock"></i>${timeText}
                                </div>
                                ${ev.assignee ? `<div class="uw-meta-pill"><i class="fas fa-user"></i>${esc(ev.assignee)}</div>` : ''}
                            </div>
                            <div class="uw-item-footer">
                                <span class="uw-cat-tag uw-cat-${ev.category}">${ev.category}</span>
                                <span class="uw-status-pill" style="background:${statusColor}18;color:${statusColor};border:1px solid ${statusColor}44;">
                                    <span class="uw-status-dot" style="background:${statusColor};"></span>
                                    ${statusLabel}
                                </span>
                            </div>
                        </div>
                    </div>`;
            });

            html += `</div></div>`;
        });

        html += `</div>`;
        wrap.innerHTML = html;

        wrap.querySelectorAll('.uw-item').forEach(item => {
            item.addEventListener('click', () => {
                const ev = eventsCache.find(e => e.id == item.dataset.id);
                if (ev) openDay(ev.date);
            });
        });
    }

    window.uwToggleMonth = function(toggleEl) {
        const isOpen = toggleEl.classList.contains('open');
        toggleEl.classList.toggle('open', !isOpen);
        toggleEl.nextElementSibling.classList.toggle('open', !isOpen);
    };

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentView === 'month') {
            viewMonth--; if (viewMonth < 0) { viewMonth = 11; viewYear--; }
            renderMonth(); prefetchMonth(viewYear, viewMonth);
        } else {
            viewYear--; renderYear();
            for (let m = 0; m < 12; m++) prefetchMonth(viewYear, m);
        }
    });

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentView === 'month') {
            viewMonth++; if (viewMonth > 11) { viewMonth = 0; viewYear++; }
            renderMonth(); prefetchMonth(viewYear, viewMonth);
        } else {
            viewYear++; renderYear();
            for (let m = 0; m < 12; m++) prefetchMonth(viewYear, m);
        }
    });

    document.getElementById('todayBtn').addEventListener('click', () => {
        viewMonth = currentDate.getMonth(); viewYear = currentDate.getFullYear();
        if (currentView === 'year') goMonth(viewMonth); else renderMonth();
        prefetchMonth(viewYear, viewMonth);
    });

    document.getElementById('monthViewBtn').addEventListener('click', () => {
        currentView = 'month';
        document.getElementById('monthView').style.display = '';
        document.getElementById('yearView').style.display  = 'none';
        document.getElementById('monthViewBtn').classList.add('active');
        document.getElementById('yearViewBtn').classList.remove('active');
        renderMonth(); prefetchMonth(viewYear, viewMonth);
    });

    document.getElementById('yearViewBtn').addEventListener('click', () => {
        currentView = 'year';
        document.getElementById('monthView').style.display = 'none';
        document.getElementById('yearView').style.display  = '';
        document.getElementById('monthViewBtn').classList.remove('active');
        document.getElementById('yearViewBtn').classList.add('active');
        renderYear();
        for (let m = 0; m < 12; m++) prefetchMonth(viewYear, m);
    });

    const addBtn = document.getElementById('addEventBtn');
    if (addBtn) addBtn.addEventListener('click', () => openAddForm(fmtDate(currentDate)));

    const addFromMo = document.getElementById('addFromModal');
    if (addFromMo) addFromMo.addEventListener('click', () => {
        document.getElementById('dayModal').classList.remove('open');
        openAddForm(selectedDate);
    });

    document.getElementById('modalClose').addEventListener('click', () =>
        document.getElementById('dayModal').classList.remove('open'));
    document.getElementById('dayModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open'); });

    document.getElementById('eventModalClose').addEventListener('click', () =>
        document.getElementById('eventModal').classList.remove('open'));
    document.getElementById('cancelEvent').addEventListener('click', () =>
        document.getElementById('eventModal').classList.remove('open'));
    document.getElementById('saveEvent').addEventListener('click', saveEvent);
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open'); });

    document.getElementById('detailModalClose').addEventListener('click', () => {
        document.getElementById('eventDetailModal').classList.remove('open');
        document.getElementById('dayModal').classList.add('open');
    });
    document.getElementById('eventDetailModal').addEventListener('click', function(e) {
        if (e.target === this) { this.classList.remove('open'); document.getElementById('dayModal').classList.add('open'); }
    });
    document.getElementById('detailEditBtn').addEventListener('click', () =>
        openEditForm(+document.getElementById('detailEditBtn').dataset.id));
    document.getElementById('detailDeleteBtn').addEventListener('click', () => {
        const btn = document.getElementById('detailDeleteBtn');
        openDeleteConfirm(+btn.dataset.id, btn.dataset.title);
    });

    const statusSel = document.getElementById('detailStatusSelect');
    if (statusSel) statusSel.addEventListener('change', function () {
        changeStatus(+this.dataset.id, this.value); });

    const commentBtn = document.getElementById('submitComment');
    if (commentBtn) {
        commentBtn.addEventListener('click', async () => {
            const inp     = document.getElementById('commentInput');
            const content = inp.value.trim();
            const id      = +document.getElementById('detailEditBtn').dataset.id;
            if (!content) return;
            try {
                const json = await api(`${API}?action=add_comment`, {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({event_id:id, content})
                });
                inp.value = '';
                renderLogs(json.data);
                toast('Comment added');
            } catch (e) { toast(e.message, 'error'); }
        });
    }

    const deleteCancelBtn  = document.getElementById('deleteCancelBtn');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteModal      = document.getElementById('deleteConfirmModal');

    if (deleteCancelBtn)  deleteCancelBtn.addEventListener('click', closeDeleteConfirm);
    if (deleteModal)      deleteModal.addEventListener('click', e => { if (e.target === deleteModal) closeDeleteConfirm(); });
    if (deleteConfirmBtn) deleteConfirmBtn.addEventListener('click', () => {
        if (pendingDeleteId !== null) deleteEvent(pendingDeleteId);
    });

    buildSearchBar();
    renderMonth();
    fetchMonth(viewYear, viewMonth).then(() => {
        renderMonth();
        buildUpcomingWidget();
    }).catch(() => {});
    prefetchMonth(viewYear, viewMonth + 1 > 11 ? 0 : viewMonth + 1);

})();