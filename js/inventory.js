/**
 * Inventar-Filter und Artikel-Verwaltung
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    let lowStockActive = false;

    // Filtert die Inventarliste basierend auf Suchbegriff und Mindestbestand
    function applyFilters() {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';
        
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;

            item.style.display = (matchesSearch && matchesLowStock) ? '' : 'none';
        });
    }

    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', applyFilters);
    }

    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            lowStockActive = !lowStockActive;
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
            applyFilters();
        });
    }
});

/**
 * Führt die Archivierung eines Gegenstands per AJAX aus
 */
function archiveItem(id) {
    if (!confirm('Möchtest du diesen Gegenstand wirklich archivieren?')) return;
    
    fetch('api/item-archive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            alert('Fehler beim Archivieren: ' + data.error);
        }
    });
}

/**
 * Öffnet das Archivierungs-Modal und übergibt Gegenstands-ID und Name
 */
function openArchiveModal(id, name) {
    const nameEl = document.getElementById('archiveItemNameText');
    if (nameEl) nameEl.innerText = '"' + name + '"';
   
    const idInput = document.getElementById('archiveTargetId');
    if (idInput) idInput.value = id;

    const modalEl = document.getElementById('archiveConfirmModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Führt den API-Call zum Archivieren des Gegenstands aus
 */
function executeArchive() {
    const idInput = document.getElementById('archiveTargetId');
    const id = idInput ? idInput.value : 0;

    if (!id) return;

    fetch('api/item-archive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id, 10) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'index.php';
        } else {
            console.error('Fehler beim Archivieren:', data.error);
        }
    })
    .catch(err => console.error('Netzwerkfehler:', err));
}

/**
 * Live-Suchfilter für die Archiv-Tabelle
 */
document.addEventListener('DOMContentLoaded', () => {
    const archiveSearchInput = document.getElementById('archiveSearchInput');
    if (!archiveSearchInput) return;

    archiveSearchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#archivedTable tbody tr[data-search-text]');

        rows.forEach(row => {
            const text = row.getAttribute('data-search-text');
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

/**
 * Öffnet das Modal zum Wiederherstellen eines archivierten Gegenstands
 */
function openRestoreModal(id, name) {
    const itemIdInput = document.getElementById('restoreItemId');
    const itemNameEl = document.getElementById('restoreItemName');
    const locationSelect = document.getElementById('restoreLocationSelect');

    if (itemIdInput) itemIdInput.value = id;
    if (itemNameEl) itemNameEl.innerText = name;
    if (locationSelect) locationSelect.value = '';
   
    const modalEl = document.getElementById('restoreModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Sendet die Reaktivierung des Gegenstands an das Backend
 */
function submitRestore() {
    const id = document.getElementById('restoreItemId').value;
    const locationId = document.getElementById('restoreLocationSelect').value;

    if (!locationId) {
        alert('Bitte wähle einen Lagerort aus.');
        return;
    }

    fetch('api/item-restore.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id, 10), location_id: parseInt(locationId, 10) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + data.error);
        }
    })
    .catch(err => console.error('Fehler:', err));
}

/**
 * Inventar-Filter (Suchfeld, Nachbestellen & Ablaufdatum)
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    const expiredBtn = document.getElementById('toggleExpired');
   
    let lowStockActive = false;
    let expiredActive = false;

    function applyFilters() {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';
       
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';
            const isExpired = item.getAttribute('data-expired') === '1';

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;
            const matchesExpired = !expiredActive || isExpired;

            if (matchesSearch && matchesLowStock && matchesExpired) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', applyFilters);
    }

    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            lowStockActive = !lowStockActive;
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
            applyFilters();
        });
    }

    if (expiredBtn) {
        expiredBtn.addEventListener('click', function(e) {
            e.preventDefault();
            expiredActive = !expiredActive;
            expiredBtn.classList.toggle('btn-warning', expiredActive);
            expiredBtn.classList.toggle('btn-outline-warning', !expiredActive);
            expiredBtn.classList.toggle('text-dark', expiredActive);
            applyFilters();
        });
    }
});

/**
 * Bestandsaktualisierung und Verifizierung im Inventur-Modus
 */
function updateQty(itemId, change) {
    const input = document.getElementById('qty-' + itemId);
    if (!input) return;
   
    let currentVal = parseInt(input.value) || 0;
    let newVal = currentVal + change;
    if (newVal < 0) newVal = 0;
   
    input.value = newVal;
    saveQty(itemId);
}

function saveQty(itemId) {
    const input = document.getElementById('qty-' + itemId);
    if (!input) return;
   
    const newQuantity = parseInt(input.value) || 0;

    fetch('api/audit-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: itemId, quantity: newQuantity })
    })
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById('auditStatusBadge');
        if (data.success) {
            if (badge) {
                badge.style.display = 'inline-block';
                setTimeout(() => { badge.style.display = 'none'; }, 2000);
            }
        } else {
            alert('Fehler beim Speichern: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(err => {
        console.error('Netzwerkfehler:', err);
    });
}

function verifyItem(itemId) {
    const row = document.getElementById('row-' + itemId);
    if (!row) return;

    row.style.transition = 'all 0.3s ease';
    row.style.opacity = '0';
    row.style.transform = 'scale(0.98)';
   
    setTimeout(() => {
        row.style.display = 'none';
       
        const visibleRows = document.querySelectorAll('.audit-row:not([style*="display: none"])');
        const countSpan = document.getElementById('itemCount');
        if (countSpan) {
            countSpan.innerText = visibleRows.length;
        }

        if (visibleRows.length === 0) {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-success fw-semibold"><i class="fa-solid fa-circle-check me-2"></i>Alle Gegenstände in diesem Bereich erfolgreich geprüft!</td></tr>`;
            }
        }
    }, 300);
}
