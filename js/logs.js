/**
 * Logbuch-Filter und Rollback-Verwaltung
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const actionFilter = document.getElementById('actionFilter');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const rows = document.querySelectorAll('.log-row');
    const noMatchRow = document.getElementById('noMatchRow');
    const rowCountText = document.getElementById('rowCountText');
    const totalCount = rows.length;

    // Filtert die Protokolltabelle nach Suchbegriff und Aktion
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedAction = actionFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            const rowAction = row.dataset.action;

            const matchesText = searchTerm === '' || rowText.includes(searchTerm);
            const matchesAction = selectedAction === '' || rowAction === selectedAction;

            if (matchesText && matchesAction) {
                row.classList.remove('d-none');
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });

        if (noMatchRow) noMatchRow.classList.toggle('d-none', visibleCount > 0 || totalCount === 0);
        if (btnResetFilter) btnResetFilter.classList.toggle('d-none', searchTerm === '' && selectedAction === '');
        if (rowCountText) rowCountText.textContent = `Zeige ${visibleCount} von ${totalCount} Einträgen`;
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (actionFilter) actionFilter.addEventListener('change', filterTable);

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function() {
            searchInput.value = '';
            actionFilter.value = '';
            filterTable();
            searchInput.focus();
        });
    }

    // Rollback-Modal & Vorschau-Generierung
    const rollbackModalEl = document.getElementById('rollbackModal');
    const rollbackModal = rollbackModalEl ? new bootstrap.Modal(rollbackModalEl) : null;
    let selectedLogId = null;

    const fieldNameMap = {
        'location_id': 'Standort',
        'location': 'Standort',
        'name': 'Name',
        'description': 'Beschreibung',
        'quantity': 'Bestand',
        'min_quantity': 'Mindestbestand',
        'price': 'Preis',
        'category_id': 'Kategorie'
    };

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-rollback');
        if (!btn) return;

        selectedLogId = btn.dataset.logId;
        const itemName = btn.dataset.itemName;
        const field = btn.dataset.field;
        const oldValue = btn.dataset.oldValue;
        const newValue = btn.dataset.newValue;
        const oldLoc = btn.dataset.oldLocationName;
        const newLoc = btn.dataset.newLocationName;

        const nameEl = document.getElementById('rollbackItemName');
        if (nameEl) nameEl.textContent = itemName;

        let previewHtml = '';
        if (field === 'location_id' || field === 'location' || (oldLoc && newLoc)) {
            const currentLocDisplay = newLoc || newValue || 'Kein Standort';
            const previousLocDisplay = oldLoc || oldValue || 'Kein Standort';
            previewHtml = `Standort zurücksetzen: von <span class="badge bg-secondary">${currentLocDisplay}</span> zurück auf <span class="badge bg-primary">${previousLocDisplay}</span>`;
        } else {
            const label = fieldNameMap[field] || field;
            const displayOld = oldValue !== '' ? oldValue : '(leer)';
            const displayNew = newValue !== '' ? newValue : '(leer)';
            previewHtml = `Änderung im Feld <strong>"${label}"</strong> zurücksetzen: von <em>"${displayNew}"</em> zurück auf <em>"${displayOld}"</em>`;
        }

        const detailsEl = document.getElementById('rollbackDetailsText');
        if (detailsEl) {
            detailsEl.className = 'fw-bold text-body-emphasis';
            detailsEl.innerHTML = previewHtml;
        }

        if (rollbackModal) rollbackModal.show();
    });

    // Rollback-Bestätigung ausführen
    document.addEventListener('click', function(e) {
        const confirmBtn = e.target.closest('#btnConfirmRollback, .btn-confirm-rollback');
        if (!confirmBtn) return;

        if (!selectedLogId) {
            alert('Kein Log-Eintrag ausgewählt.');
            return;
        }

        confirmBtn.disabled = true;
        const originalHtml = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Wende an...';

        fetch('api/log-rollback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'log_id=' + encodeURIComponent(selectedLogId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler beim Rollback: ' + (data.error || data.message || 'Unbekannter Fehler'));
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalHtml;
            }
        })
        .catch(() => {
            alert('Netzwerk- oder Serverfehler.');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalHtml;
        });
    });
});
