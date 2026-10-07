<!-- Modal: Hilfe & Bedienhinweise (Inventarverwaltung) -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
           
            <!-- Modal Header -->
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title d-flex align-items-center fs-6 fs-md-5">
                    <i class="fa-solid fa-circle-question me-2 fs-5"></i>
                    <span>Hilfe &amp; Bedienhinweise</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-md-4">
               
                <!-- 1. Schnellsuche -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> Schnellsuche &amp; Filter
                    </h6>
                    <p class="small text-muted mb-0">
                        Über das Suchfeld <strong>„QuickFilter“</strong> oben wird die Inventarliste in Echtzeit gefiltert. Es kann nach Name, Seriennummer, Kategorie oder Standort gesucht werden.
                    </p>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 2. Aktionen via FAB -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-bars-staggered me-2"></i> Schnellaktionen (Aktions-Button)
                    </h6>
                    <p class="small text-muted mb-2">
                        Unten rechts befindet sich der schwebende Plus-Button (FAB). Ein Tippen öffnet die Schnelloptionen:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-primary me-2"><i class="fa-solid fa-plus"></i></span>
                            <span class="small"><strong>Neuer Artikel:</strong> Erfasst einen neuen Gegenstand inklusive Foto und Daten.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-secondary me-2"><i class="fa-solid fa-sitemap"></i></span>
                            <span class="small"><strong>Standorte:</strong> Verwaltet Räume, Regale und Unter-Standorte.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-info me-2"><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span class="small"><strong>Umlagern:</strong> Verschiebt Gegenstände schnell an einen neuen Ort.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-dark me-2"><i class="fa-solid fa-gear"></i></span>
                            <span class="small"><strong>Einstellungen:</strong> Anpassen von Theme, Passwortschutz und Benutzern.</span>
                        </div>
                    </div>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 3. Design & Einstellungen (NEU) -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-palette me-2"></i> Erscheinungsbild &amp; Themes
                    </h6>
                    <p class="small text-muted mb-0">
                        In den <strong>Einstellungen</strong> kann zwischen einem hellen Tag-Design und einem augenschonenden <strong>Dark Mode</strong> umgestellt werden. Die Wahl wird serverseitig gespeichert und auf allen Geräten angewendet.
                    </p>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 4. Sicherheit & Zugriffsschutz (NEU) -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-shield-halved me-2"></i> Sicherheit &amp; Zugriffsschutz
                    </h6>
                    <p class="small text-muted mb-2">
                        Das System kann wahlweise frei zugänglich (z. B. im Heimnetzwerk auf dem Raspberry Pi) oder passwortgeschützt betrieben werden:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1"><strong>Default-Zugang:</strong> Bei der Erstinstallation lauten die Zugangsdaten <code>admin</code> / <code>admin123</code>.</li>
                        <li class="mb-1"><strong>Eigenen Benutzer anlegen:</strong> Erstelle unter <em>Einstellungen &rarr; Benutzerverwaltung</em> ein eigenes Konto mit neuem Passwort.</li>
                        <li><strong>Default-Admin entfernen:</strong> Sobald ein eigener Benutzer angelegt wurde, kann der initiale <code>admin</code>-Zugang aus Sicherheitsgründen deaktiviert werden.</li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 5. Standort-Baumansicht -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-folder-tree me-2"></i> Standortansicht (Baumstruktur)
                    </h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">Tippe auf Pfeile/Ordner, um Unter-Standorte auszuklappen.</li>
                        <li class="mb-1">Ein Klick auf einen Standort filtert alle zugewiesenen Inventar-Artikel.</li>
                        <li><strong>Achtung beim Löschen:</strong> Das Wischen oder Fahren über das Papierkorb-Symbol hebt den betroffenen Zweig <span class="text-danger fw-semibold">rot hervor (Glow-Effekt)</span>, um versehentliches Löschen zu verhindern.</li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 6. Artikel bearbeiten & Fotos -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Artikel bearbeiten &amp; Fotos
                    </h6>
                    <p class="small text-muted mb-1">
                        <strong>Karten-Details:</strong> Ein Klick auf eine Artikelkarte öffnet die Detailansicht zum Ändern von Menge, Zustand oder Bildern.
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Fotos am Smartphone:</strong> Beim Hochladen eines Bildes kann mobil direkt die Kamera genutzt werden, um Gegenstände sofort zu fotografieren.
                    </p>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 7. Tastenkombinationen (Desktop) -->
                <section class="mb-4 d-none d-md-block">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-keyboard me-2"></i> Tastenkombinationen (Desktop)
                    </h6>
                    <div class="row g-2 small text-muted">
                        <div class="col-6">
                            <kbd class="bg-dark">Strg</kbd> + <kbd class="bg-dark">Enter</kbd> &ndash; Formular speichern
                        </div>
                        <div class="col-6">
                            <kbd class="bg-dark">Esc</kbd> &ndash; Dialoge / Menüs schließen
                        </div>
                    </div>
                </section>

                <hr class="my-3 text-black-50 d-none d-md-block">

                <!-- 8. Mehrbenutzer & Synchronisation -->
                <section>
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-arrows-rotate me-2"></i> Synchronisation &amp; Multi-User
                    </h6>
                    <p class="small text-muted mb-0">
                        Änderungen am Lagerbestand (z. B. Entnahmen oder Umlagerungen durch andere Benutzer) werden automatisch abgeglichen.
                    </p>
                </section>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-danger w-100 w-sm-auto px-4" data-bs-dismiss="modal">
                    <i class="fa-solid fa-check me-1"></i> Verstanden
                </button>
            </div>

        </div>
    </div>
</div>


<!-- Modal: Benutzer bearbeiten -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-gear me-2"></i>Benutzer bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="POST" action="settings.php">
                <div class="modal-body">
                    <input type="hidden" name="action_edit_user" value="1">
                    <input type="hidden" name="edit_user_id" id="modalUserId">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Benutzername</label>
                        <input type="text" class="form-control" id="modalUsername" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rolle</label>
                        <select class="form-select" name="edit_role" id="modalRole">
                            <option value="user">User (Standard-Zugriff)</option>
                            <option value="admin">Admin (Vollzugriff & Einstellungen)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Neues Passwort vergeben</label>
                        <input type="password" class="form-control" name="edit_password" placeholder="Leer lassen für unverändertes Passwort">
                        <div class="form-text">Nur ausfüllen, wenn das Passwort dieses Benutzers geändert werden soll.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>