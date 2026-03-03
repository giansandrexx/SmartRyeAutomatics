    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Item';
        document.getElementById('formAction').value = 'add';
        document.getElementById('itemForm').reset();
        document.getElementById('itemModal').style.display = 'block';
    }

    function openEditModal(id, name, quantity, unit) {
        document.getElementById('modalTitle').textContent = 'Edit Item';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('itemId').value = id;
        document.getElementById('itemName').value = name;
        document.getElementById('quantity').value = quantity;
        document.getElementById('unit').value = unit;
        document.getElementById('itemModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('itemModal').style.display = 'none';
    }

    let currentStockValue = 0;

    function openAdjustModal(id, name, currentStock) {
        currentStockValue = currentStock;
        document.getElementById('adjustId').value = id;
        document.getElementById('adjustItemName').textContent = name;
        document.getElementById('currentStock').textContent = currentStock;
        document.getElementById('adjustmentAmount').value = 0;
        document.getElementById('newStock').textContent = currentStock;
        document.getElementById('adjustModal').style.display = 'block';
    }

    function closeAdjustModal() {
        document.getElementById('adjustModal').style.display = 'none';
    }

    function adjustQuantity(amount) {
        let input = document.getElementById('adjustmentAmount');
        input.value = parseInt(input.value) + amount;
        updateNewStock();
    }

    function updateNewStock() {
        let adjustment = parseInt(document.getElementById('adjustmentAmount').value) || 0;
        document.getElementById('newStock').textContent = currentStockValue + adjustment;
    }

    document.getElementById('adjustmentAmount').addEventListener('input', updateNewStock);

    function deleteItem(id, name) {
        confirmDelete(name, function () {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }

    window.onclick = function(event) {
        const itemModal   = document.getElementById('itemModal');
        const adjustModal = document.getElementById('adjustModal');
        if (event.target == itemModal)   closeModal();
        if (event.target == adjustModal) closeAdjustModal();
    }