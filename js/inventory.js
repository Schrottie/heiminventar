/**
 * Inventar-Filter (Suchfeld, Nachbestellen, Ablaufdatum & Wartung fällig)
 */
document.addEventListener('DOMContentLoaded', () => {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    const expiredBtn = document.getElementById('toggleExpired');
    const maintenanceBtn = document.getElementById('toggleMaintenance');

    function applyFilters(targetFilter = null) {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';

        // Prüfen, welcher Button aktuell aktiv ist
        let lowStockActive = lowStockBtn ? lowStockBtn.classList.contains('active-filter') : false;
        let expiredActive = expiredBtn ? expiredBtn.classList.contains('active-filter') : false;
        let maintenanceActive = maintenanceBtn ? maintenanceBtn.classList.contains('active-filter') : false;

        // Wenn ein spezifischer Filter geklickt wurde
        if (targetFilter !== null) {
            // Wenn derselbe Filter bereits aktiv war -> ausschalten (Toggle)
            if (targetFilter === 'lowStock') lowStockActive = !lowStockActive;
            if (targetFilter === 'expired') expiredActive = !expiredActive;
            if (targetFilter === 'maintenance') maintenanceActive = !maintenanceActive;

            // Wenn es ein neuer Filter ist, alle anderen exklusiv ausschalten
            if (targetFilter === 'lowStock' && lowStockActive) {
                expiredActive = false;
                maintenanceActive = false;
            }
            if (targetFilter === 'expired' && expiredActive) {
                lowStockActive = false;
                maintenanceActive = false;
            }
            if (targetFilter === 'maintenance' && maintenanceActive) {
                lowStockActive = false;
                expiredActive = false;
            }

            if (quickFilterInput) quickFilterInput.value = ''; // Suchfeld bei Button-Klick leeren
        }

        // Optischen Zustand der Buttons aktualisieren
        if (lowStockBtn) {
            lowStockBtn.classList.toggle('active-filter', lowStockActive);
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
        }
        if (expiredBtn) {
            expiredBtn.classList.toggle('active-filter', expiredActive);
            expiredBtn.classList.toggle('btn-warning', expiredActive);
            expiredBtn.classList.toggle('text-dark', expiredActive);
            expiredBtn.classList.toggle('btn-outline-warning', !expiredActive);
        }
        if (maintenanceBtn) {
            maintenanceBtn.classList.toggle('active-filter', maintenanceActive);
            maintenanceBtn.classList.toggle('btn-info', maintenanceActive);
            maintenanceBtn.classList.toggle('text-dark', maintenanceActive);
            maintenanceBtn.classList.toggle('btn-outline-secondary', !maintenanceActive);
            maintenanceBtn.classList.toggle('border-info', !maintenanceActive);
            maintenanceBtn.classList.toggle('text-info-emphasis', !maintenanceActive);
        }

        // Inventar-Elemente durchgehen und filtern
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';
            const isExpired = item.getAttribute('data-expired') === '1';
            const isMaintenance = item.getAttribute('data-maintenance') === '1';

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;
            const matchesExpired = !expiredActive || isExpired;
            const matchesMaintenance = !maintenanceActive || isMaintenance;

            if (matchesSearch && matchesLowStock && matchesExpired && matchesMaintenance) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Event-Listener für das Suchfeld
    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', () => applyFilters(null));
    }

    // Event-Listener: Nachbestellen
    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyFilters('lowStock');
        });
    }

    // Event-Listener: Ablauf / Verfall
    if (expiredBtn) {
        expiredBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyFilters('expired');
        });
    }

    // Event-Listener: Wartung fällig
    if (maintenanceBtn) {
        maintenanceBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyFilters('maintenance');
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
