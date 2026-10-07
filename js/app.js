/**
 * Link-Ziele für Floating Action Buttons zentral definieren
 */
const ROUTES = {
    ADD_ITEM: 'edit-item.php',
    LOCATIONS: 'locations.php',
    INVENTORY: 'inventory.php',
    SETTINGS: 'settings.php',
    START: 'index.php'
};

// =========================================================================
// CENTRAL DOM CONTENT LOADED INITIALIZATION
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {

    // ---------------------------------------------------------------------
    // 1. Inventar Schnellfilter
    // ---------------------------------------------------------------------
    const filter = document.getElementById('quickFilter');
    if (filter) {
        filter.addEventListener('input', () => {
            const search = filter.value.toLowerCase().trim();
            document.querySelectorAll('.inventory-item').forEach(item => {
                const content = item.dataset.search || '';
                item.style.display = content.includes(search) ? '' : 'none';
            });
        });
    }

    // ---------------------------------------------------------------------
    // 2. Floating Action Buttons (FAB) Navigation
    // ---------------------------------------------------------------------
    const fabContainer = document.getElementById('fabContainer');
    const fabMainBtn = document.getElementById('fabMainBtn');

    if (fabMainBtn && fabContainer) {
        fabMainBtn.addEventListener('click', () => {
            fabContainer.classList.toggle('open');
            const icon = fabMainBtn.querySelector('i');
            if (icon) {
                const isOpen = fabContainer.classList.contains('open');
                icon.classList.toggle('fa-bars', !isOpen);
                icon.classList.toggle('fa-xmark', isOpen);
            }
        });
    }

    // FAB Route Binding Helper
    const bindFabRoute = (id, route) => {
        document.getElementById(id)?.addEventListener('click', () => window.location.href = route);
    };

    bindFabRoute('addFabBtn', ROUTES.ADD_ITEM);
    bindFabRoute('locationsFabBtn', ROUTES.LOCATIONS);
    bindFabRoute('changeFabBtn', ROUTES.INVENTORY);
    bindFabRoute('indexFabBtn', ROUTES.START);
    bindFabRoute('settingsFabBtn', ROUTES.SETTINGS);

    // ---------------------------------------------------------------------
    // 3. Drag & Drop mit Sortable.js
    // ---------------------------------------------------------------------
    const dropzones = document.querySelectorAll('.item-dropzone');
    const statusBadge = document.getElementById('drag-status-badge');

    dropzones.forEach(zone => {
        new Sortable(zone, {
            group: 'shared-items',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'bg-info-subtle',
            delay: 150,               // Gedrückt halten für Touch-Geräte
            delayOnTouchOnly: true,   // PC ohne Verzögerung
            touchStartThreshold: 5,   // Verhindert ungewolltes Draggen beim Scrollen

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

    // ---------------------------------------------------------------------
    // 4. Lagerort Inhalte Ein-/Ausblenden & Umlagern-Modal
    // ---------------------------------------------------------------------
    const toggleSwitch = document.getElementById('toggleItemsSwitch');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            document.querySelectorAll('.location-items-list').forEach(el => {
                el.classList.toggle('d-none', !this.checked);
            });
        });
    }

    const relocateForm = document.getElementById('relocateForm');
    if (relocateForm) {
        const sourceSelect = document.getElementById('sourceLocationSelect');
        const targetSelect = document.getElementById('targetLocationSelect');
        const includeSubInput = document.getElementById('includeSublocationsInput');
        const modalEl = document.getElementById('relocateSublocationsModal');
        const relocateModal = modalEl ? new bootstrap.Modal(modalEl) : null;

        // AJAX Request zum Ausführen der Umlagerung
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

        // Formular-Aktion abfangen
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

        // Event-Listener für Buttons im Modal
        modalEl?.querySelectorAll('button[data-mode]').forEach(btn => {
            btn.addEventListener('click', function() {
                includeSubInput.value = this.getAttribute('data-mode');
                relocateModal?.hide();
                executeRelocation();
            });
        });
    }

    // ---------------------------------------------------------------------
    // 5. Tastatur-Shortcuts für Hilfemodal (F1 & ?)
    // ---------------------------------------------------------------------
    const helpModalElement = document.getElementById('helpModal');
    if (helpModalElement) {
        const helpModal = new bootstrap.Modal(helpModalElement);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'F1') {
                event.preventDefault();
                helpModal.toggle();
            }

            const isInputField = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) 
                                 || document.activeElement.isContentEditable;

            if (event.key === '?' && !isInputField) {
                event.preventDefault();
                helpModal.toggle();
            }
        });
    }

    // ---------------------------------------------------------------------
    // 6. Theme Toggle (Dark / Light Mode)
    // ---------------------------------------------------------------------
    const themeBtn = document.getElementById('themeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const body = document.body;
            const icon = themeBtn.querySelector('i');
            
            body.classList.toggle('theme-dark');
            const isDark = body.classList.contains('theme-dark');

            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }

            document.cookie = "theme=" + (isDark ? "dark" : "light") + ";path=/;max-age=31536000";
        });
    }

    // ---------------------------------------------------------------------
    // 7. Benutzer bearbeiten Modal
    // ---------------------------------------------------------------------
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            document.getElementById('modalUserId').value = button.getAttribute('data-userid');
            document.getElementById('modalUsername').value = button.getAttribute('data-username');
            document.getElementById('modalRole').value = button.getAttribute('data-role');
        });
    }
});

// =========================================================================
// STANDALONE / DYNAMISCHE EVENT HANDLER
// =========================================================================

/**
 * Lagerort löschen (Sicherheitsprüfung via AJAX vor Modal-Anzeige)
 */
document.querySelectorAll('.location-delete-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();

        const id = btn.dataset.id;
        const name = btn.dataset.name || 'Dieser Lagerort';
        const infoContainer = document.getElementById('deleteLocationInfo');
        const confirmBtn = document.getElementById('confirmDeleteLocation');
       
        document.getElementById('deleteLocationName').textContent = name;
        infoContainer.innerHTML = 'Wird geladen...';
        confirmBtn.style.display = 'inline-block';

        try {
            const response = await fetch('api/location-info.php?id=' + id);
            if (!response.ok) throw new Error('Netzwerkfehler');
            const data = await response.json();

            if (data.items > 0) {
                const cntitemstring = data.items === 1 ? 'Inventargegenstand' : 'Inventargegenstände';
                const cntlocstring = data.locations === 1 ? 'Unterlagerort' : 'Unterlagerorte';
                infoContainer.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        Dieser Lagerort enthält noch <strong>${data.items} ${cntitemstring}</strong> (und ${data.locations} ${cntlocstring}).<br>
                        Er kann nicht gelöscht werden, solange sich Gegenstände darin befinden.
                    </div>`;
                confirmBtn.style.display = 'none';
            } else {
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
                confirmBtn.href = 'api/location-delete.php?id=' + id;
            }

            const modal = new bootstrap.Modal(document.getElementById('deleteLocationModal'));
            modal.show();
        } catch (error) {
            console.error('Fehler:', error);
            alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        }
    });
});

/**
 * Header-Schrumpf-Effekt beim Scrollen (Hysterese)
 */
const hero = document.getElementById("heroHeader");
let isCollapsed = false;

window.addEventListener("scroll", () => {
    if (!hero) return;
    const scrollY = window.scrollY;

    if (!isCollapsed && scrollY > 80) {
        hero.classList.add("hero-small");
        isCollapsed = true;
    } else if (isCollapsed && scrollY < 40) {
        hero.classList.remove("hero-small");
        isCollapsed = false;
    }
});
