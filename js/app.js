/**
 * Link-Ziele für Floating Action Buttons zentral definieren
 */
const ROUTES = {
    ADD_ITEM: 'add-item.php',
    LOCATIONS: 'locations.php',
    INVENTORY: 'inventory.php',
    SETTINGS: 'settings.php',
    HISTORY: 'history.php',
    START: 'index.php'
};

// =========================================================================
// CENTRAL DOM CONTENT LOADED INITIALIZATION
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {

    // ---------------------------------------------------------------------
    // 1. Inventar Schnellfilter & Nachbestell-Filter
    // ---------------------------------------------------------------------
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    let lowStockActive = false;

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

    const bindFabRoute = (id, route) => {
        document.getElementById(id)?.addEventListener('click', () => window.location.href = route);
    };

    bindFabRoute('addFabBtn', ROUTES.ADD_ITEM);
    bindFabRoute('locationsFabBtn', ROUTES.LOCATIONS);
    bindFabRoute('changeFabBtn', ROUTES.INVENTORY);
    bindFabRoute('indexFabBtn', ROUTES.START);
    bindFabRoute('settingsFabBtn', ROUTES.SETTINGS);
    bindFabRoute('historyFabBtn', ROUTES.HISTORY);

    // ---------------------------------------------------------------------
    // 3. Drag & Drop mit Sortable.js
    // ---------------------------------------------------------------------
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
    }

    // ---------------------------------------------------------------------
    // 4. Lagerort-Pfade & Inhalte Ein-/Ausblenden
    // ---------------------------------------------------------------------
    const toggleSwitch = document.getElementById('toggleItemsSwitch');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            document.querySelectorAll('.location-items-list').forEach(el => {
                el.classList.toggle('d-none', !this.checked);
            });
        });
    }

    // Toggle-Klicks auf Lagerort-Pfade / Header (für individuelles Aufklappen)
    document.querySelectorAll('.location-toggle-btn, [data-bs-toggle="collapse"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const targetId = this.getAttribute('data-bs-target') || this.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                const targetEl = document.querySelector(targetId);
                if (targetEl && typeof bootstrap !== 'undefined') {
                    const collapse = bootstrap.Collapse.getInstance(targetEl) || new bootstrap.Collapse(targetEl);
                    collapse.toggle();
                }
            }
        });
    });

    // Umlagern-Formular & Modal
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

    // ---------------------------------------------------------------------
    // 5. Lagerort löschen (Modal & AJAX)
    // ---------------------------------------------------------------------
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

                const deleteModalEl = document.getElementById('deleteLocationModal');
                if (deleteModalEl && typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(deleteModalEl);
                    modal.show();
                }
            } catch (error) {
                console.error('Fehler:', error);
                alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
            }
        });
    });

    // ---------------------------------------------------------------------
    // 6. Tastatur-Shortcuts für Hilfemodal (F1 & ?)
    // ---------------------------------------------------------------------
    const helpModalElement = document.getElementById('helpModal');
    if (helpModalElement && typeof bootstrap !== 'undefined') {
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
    // 7. Theme Toggle (Dark / Light Mode)
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
    // 8. Benutzer bearbeiten Modal
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

    // ---------------------------------------------------------------------
    // 9. QR-Code Generator
    // ---------------------------------------------------------------------
    const qrCanvas = document.getElementById('qrcodeCanvas');
    if (qrCanvas && typeof qrcode !== 'undefined') {
        const currentUrl = window.location.href;
        const qr = qrcode(0, 'M');
        qr.addData(currentUrl);
        qr.make();
        qrCanvas.innerHTML = qr.createImgTag(5, 10);
    }

    // ---------------------------------------------------------------------
    // 10. QR-Scanner (Kamera)
    // ---------------------------------------------------------------------
    let html5QrcodeScanner = null;
    const scannerModal = document.getElementById('scannerModal');

    // Trigger-Buttons für Kamera-Scanner direkt binden
    document.querySelectorAll('[data-bs-target="#scannerModal"], #qrScannerBtn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (scannerModal && typeof bootstrap !== 'undefined') {
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(scannerModal) || new bootstrap.Modal(scannerModal);
                modal.show();
            }
        });
    });

    if (scannerModal) {
        scannerModal.addEventListener('shown.bs.modal', function () {
            if (typeof Html5QrcodeScanner === 'undefined') {
                alert('Kamera-Bibliothek (Html5QrcodeScanner) ist nicht geladen.');
                return;
            }

            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                });
            }

            html5QrcodeScanner.render((decodedText) => {
                if (decodedText.startsWith("http://") || decodedText.startsWith("https://")) {
                    window.location.href = decodedText;
                } else {
                    const resEl = document.getElementById('qr-reader-results');
                    if (resEl) resEl.innerText = "Gescannter Inhalt: " + decodedText;
                }
            }, (error) => {
                // Stille Fehlertoleranz beim Frame-Scan
            });
        });

        scannerModal.addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => console.error("Scanner Stop Error:", error));
            }
        });
    }

    // ---------------------------------------------------------------------
    // 11. QR-Code Generator an Lagerorten
    // ---------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        // Event-Listener für QR-Code Buttons an den Lagerorten (per Event Delegation)
        document.addEventListener('click', (e) => {
            const qrBtn = e.target.closest('.location-qr-btn');
            if (!qrBtn) return;

            e.preventDefault();

            const locId = qrBtn.dataset.id;
            const locName = qrBtn.dataset.name;

            // Erzeuge die Ziel-URL für die gefilterte Hauptübersicht
            const origin = window.location.origin;
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            const targetUrl = `${origin}${basePath}index.php?location_id=${locId}`;

            // Titel und Info im bestehenden QR-Modal setzen
            const modalTitle = document.getElementById('qrModalTitle') || document.getElementById('locationQrTitle');
            const modalInfo = document.getElementById('qrModalInfo') || document.getElementById('locationQrInfo');
            
            if (modalTitle) modalTitle.textContent = locName;
            if (modalInfo) modalInfo.textContent = `Lagerort-ID: ${locId}`;

            // QR-Code Container leeren und neu rendern
            const qrContainer = document.getElementById('qrModalCode') || document.getElementById('locationQrContainer');
            if (qrContainer) {
                qrContainer.innerHTML = '';

                if (typeof qrcode !== 'undefined') {
                    const qr = qrcode(0, 'M');
                    qr.addData(targetUrl);
                    qr.make();
                    qrContainer.innerHTML = qr.createImgTag(5, 10);
                } else if (typeof QRCode !== 'undefined') {
                    new QRCode(qrContainer, {
                        text: targetUrl,
                        width: 180,
                        height: 180
                    });
                } else {
                    qrContainer.textContent = 'QR-Bibliothek nicht geladen.';
                }
            }

            // Modal öffnen (unterstützt Bootstrap 5 Modal-Instanzen)
            const modalEl = document.getElementById('locationQrModal') || document.getElementById('qrModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });
    });

});

// =========================================================================
// HEADER SCROLL EFFECT
// =========================================================================
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


document.addEventListener('DOMContentLoaded', () => {

    // ---------------------------------------------------------------
    // 1. QR-Code für Lagerorte anzeigen (per Event-Delegation)
    // ---------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const qrBtn = e.target.closest('.location-qr-btn');
        if (!qrBtn) return;

        e.preventDefault();

        const locId = qrBtn.dataset.id;
        const locName = qrBtn.dataset.name;

        // Erzeuge die Ziel-URL für die gefilterte Hauptübersicht (index.php)
        const origin = window.location.origin;
        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const targetUrl = `${origin}${basePath}index.php?location_id=${locId}`;

        // Elemente im QR-Modal befüllen (prüft verschiedene Standard-IDs)
        const modalTitle = document.getElementById('locationQrTitle') || document.getElementById('qrModalTitle');
        const modalInfo = document.getElementById('locationQrInfo') || document.getElementById('qrModalInfo');
        
        if (modalTitle) modalTitle.textContent = locName;
        if (modalInfo) modalInfo.textContent = `Lagerort-ID: ${locId}`;

        // QR-Code Container leeren und neu rendern
        const qrContainer = document.getElementById('locationQrContainer') || document.getElementById('qrModalCode');
        if (qrContainer) {
            qrContainer.innerHTML = '';

            if (typeof qrcode !== 'undefined') {
                const qr = qrcode(0, 'M');
                qr.addData(targetUrl);
                qr.make();
                qrContainer.innerHTML = qr.createImgTag(5, 10);
            } else if (typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text: targetUrl,
                    width: 180,
                    height: 180
                });
            } else {
                qrContainer.textContent = 'QR-Bibliothek nicht geladen.';
            }
        }

        // Modal öffnen
        const modalEl = document.getElementById('locationQrModal') || document.getElementById('qrModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    // ---------------------------------------------------------------
    // 2. Automated Filter auf index.php ausführen, wenn location_id in URL
    // ---------------------------------------------------------------
    const urlParams = new URLSearchParams(window.location.search);
    const locationIdParam = urlParams.get('location_id');

    if (locationIdParam) {
        // Falls du ein Filter-Dropdown für Lagerorte hast (z.B. #locationFilterSelect):
        const locationSelect = document.getElementById('locationFilterSelect');
        if (locationSelect) {
            locationSelect.value = locationIdParam;
            locationSelect.dispatchEvent(new Event('change'));
        }

        // Falls du den QuickFilter / Such-Input nutzt (z.B. #quickFilter):
        // Oder eine eigene JS-Funktion zum Filtern hast, kannst du sie hier aufrufen.
    }

});

document.addEventListener('DOMContentLoaded', function() {
    // Filter-Logik
    const searchInput = document.getElementById('searchInput');
    const actionFilter = document.getElementById('actionFilter');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const rows = document.querySelectorAll('.log-row');
    const noMatchRow = document.getElementById('noMatchRow');
    const rowCountText = document.getElementById('rowCountText');
    const totalCount = rows.length;

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

    // Modal & Rollback Logik
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
            // Logik-Korrektur: Der 'aktuelle' (neue) Standort wird zurückgesetzt auf den 'ursprünglichen' (alten) Standort.
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

        if (rollbackModal) {
            rollbackModal.show();
        }
    });

    // Bestätigung-Button im Modal flexibel abfangen (unterstützt #btnConfirmRollback oder Buttons mit class .btn-confirm-rollback)
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