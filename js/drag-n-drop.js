/**
 * Drag & Drop Steuerung (Sortable.js für den Verschiebebahnhof)
 */
document.addEventListener('DOMContentLoaded', () => {
    const dropzones = document.querySelectorAll('.item-dropzone');
    const statusBadge = document.getElementById('drag-status-badge');

    if (typeof Sortable !== 'undefined' && dropzones.length > 0) {
        dropzones.forEach(zone => {
            new Sortable(zone, {
                group: 'shared-items',
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-info-subtle',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 5,

                onEnd: function (evt) {
                    const itemEl = evt.item;
                    const newLocationEl = evt.to;
                    const itemId = itemEl.getAttribute('data-item-id');
                    const newLocationId = newLocationEl.getAttribute('data-location-id');
                    const oldLocationId = evt.from.getAttribute('data-location-id');

                    if (oldLocationId === newLocationId) return;

                    if (statusBadge) {
                        statusBadge.className = 'badge bg-warning text-dark';
                        statusBadge.textContent = 'Wird gespeichert...';
                    }

                    fetch('api/change-location.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            item_id: parseInt(itemId, 10),
                            location_id: parseInt(newLocationId, 10)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (statusBadge) {
                                statusBadge.className = 'badge bg-success';
                                statusBadge.textContent = 'Erfolgreich verschoben';
                                setTimeout(() => {
                                    statusBadge.className = 'badge bg-secondary';
                                    statusBadge.textContent = 'Bereit';
                                }, 2000);
                            }
                        } else {
                            alert('Fehler beim Verschieben: ' + (data.error || 'Unbekannter Fehler'));
                            evt.from.appendChild(itemEl);
                            if (statusBadge) {
                                statusBadge.className = 'badge bg-danger';
                                statusBadge.textContent = 'Fehler';
                            }
                        }
                    })
                    .catch(() => {
                        alert('Speichern fehlgeschlagen. Bitte Verbindung prüfen.');
                        evt.from.appendChild(itemEl);
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-danger';
                            statusBadge.textContent = 'Fehler';
                        }
                    });
                }
            });
        });
    }
});

// Drag'n'drop im Modal für Sets
document.addEventListener('DOMContentLoaded', function() {
    const isSetCheckbox = document.getElementById('is_set');
    const setManageContainer = document.getElementById('setManageButtonContainer');

    if (isSetCheckbox && setManageContainer) {
        isSetCheckbox.addEventListener('change', function() {
            if (this.checked) {
                setManageContainer.classList.remove('d-none');
            } else {
                setManageContainer.classList.add('d-none');
            }
        });
    }

    const setModal = document.getElementById('setItemModal');
    if (setModal) {
        const itemIdInput = document.getElementById('modalSetItemId');
        const listContainer = document.getElementById('setItemListContainer');
        const addForm = document.getElementById('addSetItemForm');

        function loadSetItems() {
            const itemId = itemIdInput.value;
            if (!itemId || itemId <= 0) {
                listContainer.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Bitte speichern Sie den Gegenstand zuerst ab.</td></tr>';
                return;
            }

            fetch('api/set-items-manage.php?action=list&item_id=' + itemId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderSetItems(data.items);
                    }
                });
        }

        function renderSetItems(items) {
            if (items.length === 0) {
                listContainer.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Noch keine Inhaltsteile vorhanden.</td></tr>';
                return;
            }

            let html = '';
            items.forEach(item => {
                html += `
                    <tr data-id="${item.id}">
                        <td class="text-center text-muted" style="cursor: grab;"><i class="fa-solid fa-grip-vertical"></i></td>
                        <td>${escapeHtml(item.name)}</td>
                        <td class="text-center fw-bold">${item.quantity}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 delete-set-item" data-id="${item.id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            listContainer.innerHTML = html;

            listContainer.querySelectorAll('.delete-set-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    deleteSetItem(this.dataset.id);
                });
            });
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        setModal.addEventListener('shown.bs.modal', loadSetItems);

        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const itemId = itemIdInput.value;
            const name = document.getElementById('newSetItemName').value;
            const qty = document.getElementById('newSetItemQty').value;

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('item_id', itemId);
            formData.append('name', name);
            formData.append('quantity', qty);

            fetch('api/set-items-manage.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('newSetItemName').value = '';
                        document.getElementById('newSetItemQty').value = '1';
                        loadSetItems();
                    } else {
                        alert('Fehler: ' + (data.message || 'Unbekannt'));
                    }
                });
        });

        window.deleteSetItem = function(setId) {
            const itemId = itemIdInput.value;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('item_id', itemId);
            formData.append('set_item_id', setId);

            fetch('api/set-items-manage.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) loadSetItems();
                });
        };

        // SortableJS einbinden, falls geladen
        if (typeof Sortable !== 'undefined') {
            Sortable.create(listContainer, {
                handle: '.fa-grip-vertical',
                animation: 150,
                onEnd: function() {
                    const itemId = itemIdInput.value;
                    const rows = listContainer.querySelectorAll('tr[data-id]');
                    const order = Array.from(rows).map(r => r.dataset.id);

                    const formData = new FormData();
                    formData.append('action', 'reorder');
                    formData.append('item_id', itemId);
                    order.forEach(id => formData.append('order[]', id));

                    fetch('api/set-items-manage.php', { method: 'POST', body: formData });
                }
            });
        }
    }
});


