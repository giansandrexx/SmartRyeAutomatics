    let toastTimer = null;

    function showToast(title, message) {
        clearTimeout(toastTimer);
        document.getElementById('alertToastTitle').textContent = title;
        document.getElementById('alertToastMsg').innerHTML = message;
        const toast = document.getElementById('alertToast');
        const inner = document.getElementById('alertToastInner');
        inner.style.animation = 'none';
        toast.style.display = 'flex';
        void inner.offsetWidth;
        inner.style.animation = 'toastIn 0.3s cubic-bezier(.34,1.56,.64,1) both';
        toastTimer = setTimeout(() => closeToast(), 5000);
    }

    function closeToast() {
        const inner = document.getElementById('alertToastInner');
        inner.style.animation = 'toastOut 0.25s ease forwards';
        setTimeout(() => {
            document.getElementById('alertToast').style.display = 'none';
        }, 250);
    }

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Motor';
        document.getElementById('formAction').value = 'add';
        document.getElementById('itemForm').reset();
        document.getElementById('itemModal').style.display = 'block';
    }

    function openEditModal(id, name, serial, quantity) {
        document.getElementById('modalTitle').textContent = 'Edit Motor';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('itemId').value = id;
        document.getElementById('itemName').value = name;
        document.getElementById('itemSerial').value = serial;
        document.getElementById('quantity').value = quantity;
        document.getElementById('itemModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('itemModal').style.display = 'none';
    }

    let currentStockValue = 0;
    let currentSerialRange = '';
    let currentPrefix = '';

    function openAdjustModal(id, name, currentStock, serialRange) {
        currentStockValue  = currentStock;
        currentSerialRange = serialRange;

        const firstRange = serialRange.split(',')[0].trim();
        const match = firstRange.match(/^([A-Za-z]+)/);
        currentPrefix = match ? match[1] : '';

        document.getElementById('adjustId').value = id;
        document.getElementById('adjustItemName').textContent = name;
        document.getElementById('currentStock').textContent = currentStock;
        document.getElementById('adjustmentAmount').value = 0;
        document.getElementById('newStock').textContent = currentStock;
        document.getElementById('rangeStart').value = '';
        document.getElementById('rangeEnd').value = '';
        document.getElementById('rangePreview').textContent = '';
        document.getElementById('newSerialRangeHidden').value = '';

        document.getElementById('serialPrefixDisplay').textContent  = currentPrefix;
        document.getElementById('serialPrefixDisplay2').textContent = currentPrefix;

        document.getElementById('serialInfoBox').innerHTML = `
            <div style="margin-bottom:10px;">
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#5b7fa6;margin-bottom:8px;">
                    <i class="fas fa-tags" style="font-size:10px;"></i> Existing Serial Ranges
                </span>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;">
                    ${serialRange.split(',').map(r => `
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#dbeafe;border:1px solid #93c5fd;border-radius:6px;padding:4px 10px;font-family:monospace;font-size:12.5px;font-weight:700;color:#1e40af;">
                            <i class="fas fa-barcode" style="font-size:10px;opacity:0.7;"></i>
                            ${r.trim()}
                        </span>
                    `).join('')}
                </div>
            </div>
            <div style="border-top:1px solid #c3d9ff;padding-top:10px;display:flex;align-items:flex-start;gap:10px;">
                <div style="background:#1e40af;border-radius:6px;padding:6px 10px;font-family:monospace;font-size:15px;font-weight:800;color:#fff;letter-spacing:0.04em;flex-shrink:0;">
                    ${currentPrefix}
                </div>
                <div style="font-size:12.5px;color:#3b5a8a;line-height:1.6;">
                    <strong style="color:#1a3a6b;">Serial prefix for this motor.</strong>
                    Enter only the <strong>number part</strong> below.<br>
                    <span style="opacity:0.85;">e.g. last serial <code style="background:#e8f0fe;padding:1px 5px;border-radius:4px;font-size:12px;">${currentPrefix}026372</code> → start at <code style="background:#e8f0fe;padding:1px 5px;border-radius:4px;font-size:12px;">26373</code></span>
                </div>
            </div>
        `;

        onAdjustmentChange();
        document.getElementById('adjustModal').style.display = 'block';
    }

    function closeAdjustModal() {
        document.getElementById('adjustModal').style.display = 'none';
    }

    function adjustQuantity(amount) {
        let input = document.getElementById('adjustmentAmount');
        input.value = parseInt(input.value) + amount;
        onAdjustmentChange();
    }

    function onAdjustmentChange() {
        const adjustment = parseInt(document.getElementById('adjustmentAmount').value) || 0;
        document.getElementById('newStock').textContent = currentStockValue + adjustment;

        const section = document.getElementById('serialRangeSection');
        if (adjustment > 0) {
            section.classList.add('visible');
        } else {
            section.classList.remove('visible');
            document.getElementById('rangePreview').textContent = '';
        }
        updateRangePreview();
    }

    function updateRangePreview() {
        const start   = parseInt(document.getElementById('rangeStart').value);
        const end     = parseInt(document.getElementById('rangeEnd').value);
        const preview = document.getElementById('rangePreview');
        const hidden  = document.getElementById('newSerialRangeHidden');

        if (!isNaN(start) && !isNaN(end) && end >= start) {
            const rangeStr = `${currentPrefix}${start}-${currentPrefix}${end}`;
            const count    = end - start + 1;
            preview.innerHTML = `<i class="fas fa-check-circle"></i> Range: <strong>${rangeStr}</strong> &nbsp;(${count} unit${count !== 1 ? 's' : ''})`;
            hidden.value = rangeStr;
        } else {
            preview.textContent = '';
            hidden.value = '';
        }
    }

    function prepareAdjustSubmit() {
        const adjustment = parseInt(document.getElementById('adjustmentAmount').value) || 0;
        if (adjustment > 0) {
            const start = parseInt(document.getElementById('rangeStart').value);
            const end   = parseInt(document.getElementById('rangeEnd').value);
            if (isNaN(start) || isNaN(end) || end < start) {
                showToast(
                    'Invalid Serial Range',
                    'Please enter a valid <strong>start</strong> and <strong>end</strong> number for the new batch of serials.'
                );
                return false;
            }
            const count = end - start + 1;
            if (count !== adjustment) {
                showToast(
                    `Range Mismatch — ${count} serial${count !== 1 ? 's' : ''} vs ${adjustment} unit${adjustment !== 1 ? 's' : ''}`,
                    `Your range covers <strong>${count} serial${count !== 1 ? 's' : ''}</strong> but you're adding <strong>${adjustment} unit${adjustment !== 1 ? 's' : ''}</strong>. They must match exactly.<br><span style="color:#888;font-size:11.5px;">e.g. adding 10 units → range must span exactly 10 serials</span>`
                );
                return false;
            }
        }
        return true;
    }

    function deleteItem(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    window.onclick = function(event) {
        const itemModal   = document.getElementById('itemModal');
        const adjustModal = document.getElementById('adjustModal');
        if (event.target == itemModal)   closeModal();
        if (event.target == adjustModal) closeAdjustModal();
    }
