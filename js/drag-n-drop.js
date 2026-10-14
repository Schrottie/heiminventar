/**
 * Lagerort-Steuerung, Drag & Drop sowie Baumstrukturen
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Drag & Drop mit Sortable.js für den Verschiebebahnhof
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

    // 2. Lagerort-Inhalte ein-/ausblenden (Toggle Switch)
    const toggleSwitch = document.getElementById('toggleItemsSwitch');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            document.querySelectorAll('.location-items-list').forEach(el => {
                el.classList.toggle('d-none', !this.checked);
            });
        });
    }

    // 3. Umlagern-Formular & Modal-Logik
    const relocateForm = document.getElementById('relocateForm');
    if (relocateForm) {
        const sourceSelect = document.getElementById('sourceLocationSelect');
        const targetSelect = document.getElementById('targetLocationSelect');
        const includeSubInput = document.getElementById('includeSublocationsInput');
        const modalEl = document.getElementById('relocateSublocationsModal');
        const relocateModal = (modalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(modalEl) : null;

        const executeRelocation = async () => {
            const formData = new FormData(relocateForm);
            try {
                const response = await fetch('api/location-relocate.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    relocateForm.reset();
                    includeSubInput.value = "0";
                    window.location.reload();
                } else {
                    alert('Fehler bei der Umlagerung: ' + (result.message || 'Unbekannter Fehler'));
                }
            } catch (err) {
                console.error(err);
                alert('Netzwerk- oder Serverfehler beim Umlagern.');
            }
        };

        relocateForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const sourceId = sourceSelect.value;
            const targetId = targetSelect.value;

            if (!sourceId || !targetId) {
                alert('Bitte wähle sowohl einen Quell- als auch einen Ziel-Lagerort aus.');
                return;
            }
            if (sourceId === targetId) {
                alert('Quell- und Ziel-Lagerort dürfen nicht identisch sein.');
                return;
            }

            const selectedOption = sourceSelect.options[sourceSelect.selectedIndex];
            const hasChildren = selectedOption.getAttribute('data-has-children') === '1';

            if (hasChildren && relocateModal) {
                relocateModal.show();
            } else {
                includeSubInput.value = "items_only";
                executeRelocation();
            }
        });

        modalEl?.querySelectorAll('button[data-mode]').forEach(btn => {
            btn.addEventListener('click', function() {
                includeSubInput.value = this.getAttribute('data-mode');
                relocateModal?.hide();
                executeRelocation();
            });
        });
    }

    // 4. Lagerort löschen (Sicherheitsprüfung via AJAX)
    document.querySelectorAll('.location-delete-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const id = btn.dataset.id;
            const name = btn.dataset.name || 'Dieser Lagerort';
            const infoContainer = document.getElementById('deleteLocationInfo');
            const confirmBtn = document.getElementById('confirmDeleteLocation');
            
            if (!infoContainer || !confirmBtn) return;

            document.getElementById('deleteLocationName').textContent = name;
            infoContainer.innerHTML = 'Wird geladen...';
            confirmBtn.style.display = 'inline-block';

            try {
                const response = await fetch('api/location-info.php?id=' + id);
                if (!response.ok) throw new Error('Netzwerkfehler');
                const data = await response.json();

                if (data.items > 0) {
                    infoContainer.innerHTML = `
                        <div class="alert alert-warning mb-0">
                            Dieser Lagerort enthält noch <strong>${data.items} Gegenstände</strong> (und ${data.locations} Unterlagerorte).<br>
                            Er kann nicht gelöscht werden, solange sich Gegenstände darin befinden.
                        </div>`;
                    confirmBtn.style.display = 'none';
                } else {
                    let hintText = '';
                    if (data.locations > 0) {
                        hintText = `<p class="text-danger small mt-2 mb-0"><strong>Achtung:</strong> Es werden auch ${data.locations} Unterlagerorte gelöscht.</p>`;
                    }
                    
                    infoContainer.innerHTML = `
                        <p class="mb-0">Möchtest du diesen Lagerort wirklich löschen?</p>
                        ${hintText}
                    `;
                    confirmBtn.href = 'api/location-delete.php?id=' + id;
                }

                const deleteModalEl = document.getElementById('deleteLocationModal');
                if (deleteModalEl && typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(deleteModalEl);
                    modal.show();
                }
            } catch (error) {
                console.error('Fehler:', error);
                alert('Ein Fehler ist aufgetreten. Bitte versuche es später erneut.');
            }
        });
    });

    // 5. URL-Parameter Filter für Lagerort auf index.php verarbeiten
    const urlParams = new URLSearchParams(window.location.search);
    const locationIdParam = urlParams.get('location_id');

    if (locationIdParam) {
        const locationSelect = document.getElementById('locationFilterSelect');
        if (locationSelect) {
            locationSelect.value = locationIdParam;
            locationSelect.dispatchEvent(new Event('change'));
        }
    }
});

/**
 * Öffnet das Modal zum Bearbeiten eines Lagerorts und bereitet die Formulardaten vor
 */
function openEditLocationModal(id, name, parentId) {
    document.getElementById('editLocationId').value = id;
    document.getElementById('editLocationName').value = name;
    
    const parentSelect = document.getElementById('editLocationParent');
    parentSelect.value = parentId ? parentId : "";

    // Verhindert zirkuläre Eltern-Zuweisungen (Ort kann sich nicht selbst als Parent wählen)
    for (let option of parentSelect.options) {
        option.disabled = (option.value == id);
    }

    const modal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    modal.show();
}

/**
 * Speichert Änderungen an einem Lagerort per AJAX
 */
function submitEditLocation(event) {
    event.preventDefault();

    const form = document.getElementById('editLocationForm');
    const formData = new FormData(form);

    fetch('api/location-update.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('editLocationModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            location.reload();
        } else {
            alert('Fehler beim Speichern: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(err => {
        console.error('Netzwerkfehler:', err);
        alert('Ein Netzwerkfehler ist aufgetreten.');
    });
}

// --- Ergänzung für das Bearbeiten-Modal ---

const editLocationForm = document.getElementById('editLocationForm');
if (editLocationForm) {
    editLocationForm.addEventListener('keydown', function(e) {
        // Wenn Strg + Enter gedrückt wird, direkt speichern
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            submitEditLocation(e);
            return;
        }
        
        // Wenn normales Enter in einem Input gedrückt wird, ebenfalls absenden statt falschem Tab
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
            e.preventDefault();
            submitEditLocation(e);
        }
    });
}
