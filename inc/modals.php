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
                        Über das Suchfeld <strong>„QuickFilter“</strong> oben wird die Inventarliste in Echtzeit gefiltert. Es kann nach Name, Seriennummer, Kategorie oder Standort gesucht werden. Über die Schaltfläche <strong>„Nachbestellen“</strong> lassen sich mit einem Klick alle Artikel filtern, deren Mindestbestand unterschritten ist.
                    </p>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 2. Aktionen via FAB -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-bars-staggered me-2"></i> Schnellaktionen (Aktions-Button)
                    </h6>
                    <p class="small text-muted mb-2">
                        Unten rechts befindet sich der schwebende Aktions-Button (FAB). Ein Tippen öffnet die Schnelloptionen:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span class="small darktheme-darker"><strong>Übersicht:</strong> Ruft die Startseite auf.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-success me-2"><i class="fa-solid fa-plus"></i></span>
                            <span class="small darktheme-darker"><strong>Gegenstand anlegen:</strong> Erfasst einen neuen Gegenstand inklusive Foto und Daten.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-secondary me-2"><i class="fa-solid fa-location-dot"></i></span>
                            <span class="small darktheme-darker"><strong>Lagerorte verwalten:</strong> Verwaltet Räume, Regale und Unter-Standorte.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-danger me-2"><i class="fa-solid fa-shuffle"></i></span>
                            <span class="small darktheme-darker"><strong>Verschiebebahnhof:</strong> Verschiebt Gegenstände schnell an einen neuen Ort.</span>
                        </div>
                        <div class="d-flex align-items-center bg-light p-2 rounded">
                            <span class="badge bg-dark me-2"><i class="fa-solid fa-gear"></i></span>
                            <span class="small darktheme-darker"><strong>Einstellungen:</strong> Anpassen von Theme, Vorschaubildern, Passwortschutz und Benutzern.</span>
                        </div>
                    </div>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 3. QR-Code-Scanner & QR-Codes (NEU ERWEITERT) -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-qrcode me-2"></i> QR-Code-Scanner &amp; Etiketten
                    </h6>
                    <p class="small text-muted mb-2">
                        Das System bietet eine vollständige QR-Integration zur schnellen Erfassung und Navigation:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">
                            <strong>Kamera-Scanner nutzen:</strong> Über das Kamera-Symbol in der Kopfzeile wird der QR-Scanner geöffnet. Sobald ein QR-Code eines Gegenstands oder Lagerorts gescannt wird, springt die Anwendung direkt zum Ziel.
                        </li>
                        <li class="mb-1">
                            <strong>QR-Codes generieren &amp; drucken:</strong> Für jeden Artikel sowie jeden Lagerort wird automatisch ein individueller QR-Code erzeugt. Dieser kann auf Etiketten ausgedruckt und an Kisten, Regalen oder Geräten angebracht werden.
                        </li>
                        <li>
                            <strong>Direkter Aufruf:</strong> Das Scannen eines QR-Codes mit der regulären Smartphone-Kamera öffnet direkt die passende Detailseite im Browser.
                        </li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 4. Design & Bildeinstellungen -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-palette me-2"></i> Design &amp; Vorschaubilder
                    </h6>
                    <p class="small text-muted mb-2">
                        In den <strong>Einstellungen</strong> kannst du das Erscheinungsbild anpassen:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">
                            <strong>Dark Mode:</strong> Wechsel zwischen hellem Tag-Design und augenschonendem dunklen Design. Die Wahl wird sofort serverseitig gespeichert.
                        </li>
                        <li>
                            <strong>Vorschaubilder in der Übersicht:</strong> Legt fest, ob in der Inventarübersicht Miniaturbilder angezeigt werden. Bei Deaktivierung zeigt die Übersicht eine kompaktere Textkarten-Ansicht.
                        </li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 5. Artikel bearbeiten, Dokumente & Hauptbild (NEU ERWEITERT) -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Artikel bearbeiten, Fotos &amp; Hauptbild
                    </h6>
                    <p class="small text-muted mb-2">
                        Ein Klick auf eine Artikelkarte öffnet die Detailansicht zum Ändern aller Eigenschaften:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">
                            <strong>Hauptbild festlegen (Stern-Symbol):</strong> Bei Gegenständen mit mehreren Bildern kann durch Klick auf das Stern-Symbol ein Bild als <em>Hauptbild</em> definiert werden. Dieses erscheint als Titelbild in der Übersicht und in Kachelansichten.
                        </li>
                        <li class="mb-1">
                            <strong>Fotos per Smartphone-Kamera:</strong> Beim Hinzufügen von Bildern auf mobilen Geräten öffnet sich auf Wunsch direkt die Kamera für Schnappschüsse.
                        </li>
                        <li>
                            <strong>Dokumente &amp; Anleitungen:</strong> Neben Bildern können auch Dokumente (PDF, DOCX, TXT) hochgeladen werden, z. B. Rechnungen, Garantiescheine oder Handbücher.
                        </li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 6. Standort-Baumansicht -->
                <section class="mb-4">
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-folder-tree me-2"></i> Standortansicht (Baumstruktur)
                    </h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1">Tippe auf Pfeile/Ordner, um Unter-Standorte auszuklappen.</li>
                        <li class="mb-1">Ein Klick auf einen Standort filtert alle dort gelagerten Inventar-Artikel.</li>
                        <li><strong>Achtung beim Löschen:</strong> Das Berühren oder Überfahren des Papierkorb-Symbols hebt den betroffenen Zweig <span class="text-danger fw-semibold">rot hervor (Glow-Effekt)</span>, um versehentliches Löschen zu verhindern.</li>
                    </ul>
                </section>

                <hr class="my-3 text-black-50">

                <!-- 7. Sicherheit & Zugriffsschutz -->
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

                <!-- 8. Tastenkombinationen (Desktop) -->
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
                        <div class="col-6">
                            <kbd class="bg-dark">F1</kbd> oder <kbd class="bg-dark">?</kbd> &ndash; Diese Hilfe anzeigen
                        </div>
                    </div>
                </section>

                <hr class="my-3 text-black-50 d-none d-md-block">

                <!-- 9. Mehrbenutzer & Synchronisation -->
                <section>
                    <h6 class="text-danger d-flex align-items-center fw-bold">
                        <i class="fa-solid fa-arrows-rotate me-2"></i> Synchronisation &amp; Multi-User
                    </h6>
                    <p class="small text-muted mb-0">
                        Änderungen am Lagerbestand (z. B. Entnahmen oder Umlagerungen durch andere Benutzer) werden automatisch synchronisiert.
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

<!-- Modal für Sicherheitsabfrage beim Aktivieren des Passwortschutzes -->
<div class="modal fade" id="enableAuthModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Achtung: Passwortschutz aktivieren</h5>
                <button type="button" class="btn-close" id="cancelAuthModalBtn" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Sie aktivieren den Passwortschutz. Da aktuell <strong>keine weiteren Benutzer</strong> angelegt sind, verwenden Sie bitte das Standard-Konto zum Anmelden:</p>
                
                <div class="card bg-light border-warning mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5 fw-bold">Benutzername:</div>
                            <div class="col-7"><code class="fs-6">admin</code></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-5 fw-bold">Standard-Passwort:</div>
                            <div class="col-7"><code class="fs-6">admin123</code></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="fa-solid fa-lightbulb me-1"></i> <strong>Empfehlung:</strong> Ändern Sie nach dem Login das Passwort des Standard-Admins oder legen Sie einen eigenen Benutzer an.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="confirmCancelAuth" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-warning fw-bold" id="confirmEnableAuth">Verstanden & Aktivieren</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für QR-Code & Druck -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-qrcode me-2"></i>QR-Code Etikett</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body text-center" id="printableLabel">
                <div class="border p-3 d-inline-block rounded bg-white" style="max-width: 300px;">
                    <h6 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($item['name']) ?></h6>
                    <div id="qrcodeCanvas" class="d-flex justify-content-center my-2"></div>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">ID: #<?= (int)$item['id'] ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Etikett drucken
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Kamera-QR-Scanner -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-camera me-2"></i>QR-Code scannen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-reader-results" class="mt-2 text-muted small">Kamera wird gestartet...</div>
            </div>
        </div>
    </div>
</div>

<!-- Rollback Bestätigungs-Modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle text-warning-emphasis">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="fa-solid fa-rotate-left me-2"></i>Aktion rückgängig machen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Möchtest du folgende Änderung für <strong id="rollbackItemName"></strong> wirklich rückgängig machen?</p>
                
                <div class="card bg-body-tertiary border-0 p-3 mb-3">
                    <div id="rollbackDetailsText" class="fw-bold text-body"></div>
                </div>

                <div class="text-muted small">
                    <i class="fa-solid fa-circle-info me-1"></i> Der Zustand des Gegenstands wird auf den Wert vor dieser Änderung zurückgesetzt.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" id="btnConfirmRollback" class="btn btn-sm btn-warning">
                    <i class="fa-solid fa-check me-1"></i> Ja, Rollback ausführen
                </button>
            </div>
        </div>
    </div>
</div>
