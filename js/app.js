/**
 * Link-Ziele für Floating Actions Buttons zentral definieren
 */
const ROUTES = {
    ADD_ITEM: 'edit-item.php',
    LOCATIONS: 'locations.php',
    INVENTORY: 'inventory.php',
    START: 'index.php'
};


document.addEventListener('DOMContentLoaded', () => {

    const filter = document.getElementById('quickFilter');

    if (!filter) return;

    filter.addEventListener('input', () => {

        const search = filter.value.toLowerCase().trim();

        document
            .querySelectorAll('.inventory-item')
            .forEach(item => {

                const content = item.dataset.search;

                item.style.display =
                    content.includes(search)
                        ? ''
                        : 'none';

            });

    });

});

document.addEventListener('DOMContentLoaded', () => {

    const fabContainer = document.getElementById('fabContainer');
    const fabMainBtn = document.getElementById('fabMainBtn');

    if (fabMainBtn) {

        fabMainBtn.addEventListener('click', () => {

            fabContainer.classList.toggle('open');

            const icon = fabMainBtn.querySelector('i');

            if (fabContainer.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }

        });

    }

    document.getElementById('addFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.ADD_ITEM;
        });

    document.getElementById('locationsFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.LOCATIONS;
        });

    document.getElementById('changeFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.INVENTORY;
        });

    document.getElementById('indexFabBtn')
        ?.addEventListener('click', () => {
            window.location.href = ROUTES.START;
        });

});

document.querySelectorAll('.location-delete-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        const id = btn.dataset.id;
        const name = btn.dataset.name || 'Dieser Lagerort';
        
        const infoContainer = document.getElementById('deleteLocationInfo');
        const confirmBtn = document.getElementById('confirmDeleteLocation');
        
        // Reset/Lade-Zustand setzen
        document.getElementById('deleteLocationName').textContent = name;
        infoContainer.innerHTML = 'Wird geladen...';
        confirmBtn.style.display = 'inline-block'; // Standardmäßig anzeigen

        try {
            const response = await fetch('api/location-info.php?id=' + id);
            if (!response.ok) throw new Error('Netzwerkfehler');
            const data = await response.json();

            // Prüfen, ob Items vorhanden sind
            if (data.items > 0) {
                // FALL 1: Mindestens 1 Item vorhanden -> Löschen NICHT erlauben
                const cntitemstring = data.items === 1 ? 'Inventargegenstand' : 'Inventargegenstände';
                const cntlocstring = data.locations === 1 ? 'Unterlagerort' : 'Unterlagerorte';
                infoContainer.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        Dieser Lagerort enthält noch <strong>${data.items} ${cntitemstring}</strong> (und ${data.locations} ${cntlocstring}).<br>
                        Er kann nicht gelöscht werden, solange sich Gegenstände darin befinden.
                    </div>`;
                
                // Löschen-Button ausblenden
                confirmBtn.style.display = 'none';
            } else {
                // FALL 2: Keine Items -> Löschen mit Hinweis auf Sublocations erlauben
                let hintText = '';
                if (data.locations > 0) {
                    const cntlocstring = data.locations === 1 ? 'Unterlagerort' : 'Unterlagerorte';
                    const cntdostring = data.locations === 1 ? 'wird' : 'werden';
                    hintText = `<p class="text-danger small mt-2 mb-0"><strong>Achtung:</strong> Es ${cntdostring} auch ${data.locations} ${cntlocstring} gelöscht.</p>`;
                }
                
                infoContainer.innerHTML = `
                    <p class="mb-0">Möchten Sie diesen Lagerort wirklich löschen?</p>
                    ${hintText}
                `;

                // Link für das Löschen setzen
                confirmBtn.href = 'api/location-delete.php?id=' + id;
            }

            // Modal manuell öffnen
            const modal = new bootstrap.Modal(document.getElementById('deleteLocationModal'));
            modal.show();

        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const dropzones = document.querySelectorAll('.item-dropzone');
    const statusBadge = document.getElementById('drag-status-badge');

    dropzones.forEach(function (zone) {
        new Sortable(zone, {
            group: 'shared-items',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'bg-info-subtle',

            // --- WICHTIG FÜR MOBILGERÄTE / TOUCH ---
            delay: 150,               // 150ms gedrückt halten, um Drag auf Touch zu starten
            delayOnTouchOnly: true,   // Am PC weiterhin ohne Verzögerung sofort reagieren
            touchStartThreshold: 5,   // Erst ab 5px Bewegung als Drag werten (verhindert Fehlauslösungen beim Scrollen)
            // ----------------------------------------

            onEnd: function (evt) {
                const itemEl = evt.item;
                const newLocationEl = evt.to;
                
                const itemId = itemEl.getAttribute('data-item-id');
                const newLocationId = newLocationEl.getAttribute('data-location-id');
                const oldLocationId = evt.from.getAttribute('data-location-id');

                if (oldLocationId === newLocationId) {
                    return;
                }

                if (statusBadge) {
                    statusBadge.className = 'badge bg-warning text-dark';
                    statusBadge.textContent = 'Wird gespeichert...';
                }

                fetch('api/change-location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
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
                .catch(err => {
                    console.error('Netzwerk- oder Serverfehler:', err);
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
});

document.addEventListener('DOMContentLoaded', function() {
    // Switch für Inhalte anzeigen/verstecken
    const toggleSwitch = document.getElementById('toggleItemsSwitch');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            document.querySelectorAll('.location-items-list').forEach(el => {
                el.classList.toggle('d-none', !this.checked);
            });
        });
    }

    const relocateForm = document.getElementById('relocateForm');
    const sourceSelect = document.getElementById('sourceLocationSelect');
    const targetSelect = document.getElementById('targetLocationSelect');
    const includeSubInput = document.getElementById('includeSublocationsInput');
    
    const modalEl = document.getElementById('relocateSublocationsModal');
    let relocateModal = null;
    if (modalEl) {
        relocateModal = new bootstrap.Modal(modalEl);
    }

    // Funktion zum Ausführen des AJAX-Requests
    async function executeRelocation() {
        const formData = new FormData(relocateForm);

        try {
            const response = await fetch('api/location-relocate.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Formular zurücksetzen
                relocateForm.reset();
                includeSubInput.value = "0";

                // Seite sanft neu laden, um Baum & Selects mit frischen DB-Daten neu aufzubauen
                window.location.reload();
            } else {
                alert('Fehler bei der Umlagerung: ' + (result.message || 'Unbekannter Fehler'));
            }
        } catch (err) {
            console.error(err);
            alert('Netzwerk- oder Serverfehler beim Umlagern.');
        }
    }

    // Formular-Submit abfangen
    relocateForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Verhindert Neuladen/Weiterleitung

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

        // Wenn Unterlagerorte vorhanden sind -> Modal zeigen
        if (hasChildren && relocateModal) {
            relocateModal.show();
        } else {
            // Keine Unterlagerorte -> direkt verarbeiten
            includeSubInput.value = "items_only";
            executeRelocation();
        }
    });

    // Event-Listener für die Modal-Buttons
    modalEl.querySelectorAll('button[data-mode]').forEach(btn => {
        btn.addEventListener('click', function() {
            const mode = this.getAttribute('data-mode');
            includeSubInput.value = mode;
            
            if (relocateModal) {
                relocateModal.hide();
            }
            
            executeRelocation();
        });
    });
});
