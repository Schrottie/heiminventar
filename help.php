<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/header.php';
?>

<style>
/* CSS-Effekt: Hebt die angesprungene Sektion (Ziel des Hash-Ankers) kurz hervor */
section:target {
    animation: highlightSection 2s ease-out;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

@keyframes highlightSection {
    0% { background-color: rgba(220, 53, 69, 0.2); } /* Sanftes Rot passend zum Design */
    100% { background-color: transparent; }
}

/* Verhindert, dass fixierte Header den Anker verdecken beim Hinspringen */
section {
    scroll-margin-top: 80px;
}
</style>

<div class="container py-4">
    <!-- Seiten-Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-circle-question text-danger me-2"></i>Hilfe &amp; Bedienhinweise</h2>
            <p class="text-muted mb-0">Ausführliche Dokumentation und Anleitungen zur Inventar- und Lagerortverwaltung.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Zurück zur Übersicht
        </a>
    </div>

    <div class="row">
        <!-- Schnellauswahl / Inhaltsverzeichnis (Desktop: Sticky Sidebar) -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header bg-light fw-bold">
                    <i class="fa-solid fa-list me-2"></i>Inhalt
                </div>
                <div class="list-group list-group-flush small">
                    <a href="#search" class="list-group-item list-group-item-action">Schnellsuche &amp; Filter</a>
                    <a href="#fab" class="list-group-item list-group-item-action">Schnellaktionen (FAB)</a>
                    <a href="#qrcode" class="list-group-item list-group-item-action">QR-Codes &amp; Etiketten</a>
                    <a href="#design" class="list-group-item list-group-item-action">Design &amp; Vorschaubilder</a>
                    <a href="#items" class="list-group-item list-group-item-action">Gegenstände &amp; Archiv</a>
                    <a href="#locations" class="list-group-item list-group-item-action">Lagerortverwaltung</a>
                    <a href="#security" class="list-group-item list-group-item-action">Sicherheit &amp; Login</a>
                    <a href="#shortcuts" class="list-group-item list-group-item-action">Tastenkombinationen</a>
                    <a href="#sync" class="list-group-item list-group-item-action">Multi-User &amp; Sync</a>
                </div>
            </div>
        </div>

        <!-- Hauptinhalt der Hilfe -->
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <!-- 1. Schnellsuche -->
                    <section id="search" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Schnellsuche &amp; Filter
                        </h5>
                        <p class="small text-muted mb-0">
                            Über das Suchfeld <strong>„QuickFilter“</strong> oben wird die Inventarliste in Echtzeit gefiltert. Es kann nach Name, Seriennummer, Kategorie oder Standort gesucht werden. Über die Schaltfläche <strong>„Nachbestellen“</strong> lassen sich mit einem Klick alle Artikel filtern, deren Mindestbestand unterschritten ist.
                        </p>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 2. Aktionen via FAB -->
                    <section id="fab" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-bars-staggered me-2"></i> Schnellaktionen (Aktions-Button)
                        </h5>
                        <p class="small text-muted mb-3">
                            Unten rechts befinden sich ein Menübutton, der folgende Schnellaktionen bereitstellt. Die Aktionen sind auf dem Desktop auch über die Tastenkombination <kbd class="bg-dark">Strg</kbd>+<kbd class="bg-dark">M</kbd> erreichbar. Anschließend kann mit den Pfeiltasten <kbd class="bg-dark">↑</kbd> und <kbd>↓</kbd> die gewünschte Aktion ausgewählt und mit <kbd class="bg-dark">Enter</kbd> ausgeführt werden. <kbd class="bg-dark">Esc</kbd> schließt das Menü wieder.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-boxes-stacked"></i></span>
                                <span class="small"><strong>Übersicht:</strong> Ruft die Startseite auf.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-success me-2"><i class="fa-solid fa-plus"></i></span>
                                <span class="small"><strong>Gegenstand anlegen:</strong> Erfasst einen neuen Gegenstand inklusive Foto und Daten.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-location-dot"></i></span>
                                <span class="small"><strong>Lagerorte verwalten:</strong> Verwaltet Räume, Regale und Unter-Standorte.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-danger me-2"><i class="fa-solid fa-shuffle"></i></span>
                                <span class="small"><strong>Verschiebebahnhof:</strong> Verschiebt Gegenstände schnell an einen neuen Ort.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-light me-2"><i class="fa-solid fa-gear"></i></span>
                                <span class="small"><strong>Einstellungen:</strong> Anpassen von Theme, Vorschaubildern, Passwortschutz und Benutzern.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-info me-2"><i class="fa-solid fa-clock-rotate-left"></i></span>
                                <span class="small"><strong>Protokoll:</strong> Übersicht über die letzten Änderungen an Gegenständen, inklusive Rücksetzoption.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-dark me-2"><i class="fa-solid fa-trash"></i></span>
                                <span class="small"><strong>Archiv:</strong> Übersicht über archivierte Gegenstände inklusive Reaktivierungsoption.</span>
                            </div>
                        </div>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 3. QR-Code-Scanner & QR-Codes -->
                    <section id="qrcode" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-qrcode me-2"></i> QR-Code-Scanner &amp; Etiketten
                        </h5>
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

                    <hr class="my-1 text-black-50">

                    <!-- 4. Design & Bildeinstellungen -->
                    <section id="design" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-palette me-2"></i> Design &amp; Vorschaubilder
                        </h5>
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

                    <hr class="my-1 text-black-50">

                    <!-- 5. Gegenstände bearbeiten & archivieren -->
                    <section id="items" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2"></i> Gegenstände bearbeiten &amp; archivieren
                        </h5>
                        <p class="small text-muted mb-2">
                            Ein Klick auf eine Gegenstandskarte öffnet die Detailansicht zum Ändern aller Eigenschaften:
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
                            <li>
                                <strong>Archivieren:</strong> Ein Gegenstand kann über die Detailansicht archiviert werden. Archivierte Gegenstände werden aus der aktiven Übersicht entfernt, können aber in der Archivseite jederzeit wiederhergestellt werden.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 6. Lagerortverwaltung & Struktur -->
                    <section id="locations" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-folder-tree me-2"></i> Lagerortverwaltung &amp; Struktur
                        </h5>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1"><strong>Neuen Lagerort anlegen:</strong> Gib eine Bezeichnung ein und wähle optional einen übergeordneten Lagerort (Parent), um Hierarchien (z. B. Raum → Regal) aufzubauen.</li>
                            <li class="mb-1"><strong>Baumansicht & Inhalte:</strong> Standorte werden sauber eingerichtet dargestellt. Über den Schalter „Inhalte anzeigen“ kannst du dir alle gelagerten Artikel direkt einblenden lassen.</li>
                            <li class="mb-1"><strong>Lagerort bearbeiten:</strong> Das Stift-Symbol öffnet ein Modal, in dem du den Namen oder den übergeordneten Ort anpassen kannst.</li>
                            <li class="mb-1"><strong>QR-Code & Löschen:</strong> Jeder Standort bietet einen Direktzugriff auf den QR-Code sowie eine Löschprüfung, ob noch Gegenstände oder Unterorte enthalten sind.</li>
                            <li><strong>Umlagern:</strong> Wähle Quell- und Ziel-Lagerort. Bei vorhandenen Unterlagerorten öffnet sich ein intelligentes Abfrage-Modal, mit dem du flexibel steuerst, ob Artikel, Unterorte oder ganze Zweige mitverschoben werden.</li>
                            <li><strong>Umlagern:</strong> Hier können gegenstände aus einem in einen anderen Lagerort umgelagert werden. Befinden sich noch Unterlagerorte mit oder ohne eigenen gegenständen innerhalb des Quelllagerortes, können entweder nur die enthaltenen Gegenstände oder gleich alle Unterlagerorte (ein Schrank wechselt den raum o.ä.) inklusive gegenstände umgelagert werden werden.</li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 7. Sicherheit & Zugriffsschutz -->
                    <section id="security" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-shield-halved me-2"></i> Sicherheit &amp; Zugriffsschutz
                        </h5>
                        <p class="small text-muted mb-2">
                            Das System kann wahlweise frei zugänglich (z. B. im Heimnetzwerk auf dem Raspberry Pi) oder passwortgeschützt betrieben werden:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1"><strong>Default-Zugang:</strong> Bei der Erstinstallation lauten die Zugangsdaten <code>admin</code> / <code>admin123</code>.</li>
                            <li class="mb-1"><strong>Eigenen Benutzer anlegen:</strong> Erstelle unter <em>Einstellungen &rarr; Benutzerverwaltung</em> ein eigenes Konto mit neuem Passwort.</li>
                            <li><strong>Default-Admin entfernen:</strong> Sobald ein eigener Benutzer angelegt wurde, kann der initiale <code>admin</code>-Zugang aus Sicherheitsgründen deaktiviert werden.</li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 8. Tastenkombinationen -->
                    <section id="shortcuts" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-keyboard me-2"></i> Tastenkombinationen auf dem Desktop
                        </h5>
                        <ul class="small text-muted ps-3 mb-0">
                            <li>In Dialogen/Menüs: <kbd class="bg-dark">Strg</kbd> + <kbd class="bg-dark">Enter</kbd> &ndash; Formular speichern</li>
                            <li><kbd class="bg-dark">Esc</kbd> &ndash; Dialoge/Menüs schließen</li>
                            <li><kbd class="bg-dark">Strg</kbd> + <kbd class="bg-dark">M</kbd> &ndash; Schnellaktionen (FAB) öffnen</li>
                            <li><kbd class="bg-dark">↑</kbd> und <kbd class="bg-dark">↓</kbd> &ndash; durch das geöffnete FAB-Menü navigieren → <kbd class="bg-dark">Enter</kbd> öffnet den aktiven Eintrag</li>
                            <li><kbd class="bg-dark">F1</kbd>  &ndash; Diese Hilfe anzeigen</li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 9. Multi-User & Synchronisation -->
                    <section id="sync">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-arrows-rotate me-2"></i> Synchronisation &amp; Multi-User
                        </h5>
                    <p class="small text-muted mb-0">
                        Änderungen am Lagerbestand (z. B. Entnahmen oder Umlagerungen durch andere Benutzer) werden automatisch synchronisiert. Ist der Zugriffsschutz aktiviert, können mehrere Benutzer gleichzeitig auf das System zugreifen. Alle Änderungen werden in Echtzeit übernommen, sodass jeder Benutzer stets die aktuelle Inventarliste sieht. Änderungen werden dabei namentlich protokolliert, sodass nachvollziehbar ist, wer welche Anpassungen vorgenommen hat.
                    </p>
                    </section>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
