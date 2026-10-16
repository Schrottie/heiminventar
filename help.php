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
                    <a href="#search" class="list-group-item list-group-item-action"><i class="fa-solid fa-magnifying-glass me-2"></i>Schnellsuche &amp; Filter</a>
                    <a href="#fab" class="list-group-item list-group-item-action"><i class="fa-solid fa-bars-staggered me-2"></i>Schnellaktionen (FAB)</a>
                    <a href="#qrcode" class="list-group-item list-group-item-action"><i class="fa-solid fa-qrcode me-2"></i> QR-Codes &amp; Etiketten</a>
                    <a href="#design" class="list-group-item list-group-item-action"><i class="fa-solid fa-palette me-2"></i>Design &amp; Vorschaubilder</a>
                    <a href="#items" class="list-group-item list-group-item-action"><i class="fa-solid fa-pen-to-square me-2"></i>Gegenstände bearbeiten &amp; archivieren</a>
                    <a href="#sets" class="list-group-item list-group-item-action"><i class="fa-solid fa-layer-group me-2"></i>Set-Verwaltung</a>
                    <a href="#tags" class="list-group-item list-group-item-action"><i class="fa-solid fa-tags me-2"></i>Tag-System &amp; Filterung</a>
                    <a href="#locations" class="list-group-item list-group-item-action"><i class="fa-solid fa-folder-tree me-2"></i>Lagerortverwaltung</a>
                    <a href="#shuffle" class="list-group-item list-group-item-action"><i class="fa-solid fa-shuffle me-2"></i>Verschiebebahnhof</a>
                    <a href="#dashboard" class="list-group-item list-group-item-action"><i class="fa-solid fa-chart-pie me-2"></i>Dashboard &amp; Auswertung</a>
                    <a href="#printing" class="list-group-item list-group-item-action"><i class="fa-solid fa-print me-2"></i>Druck- &amp; Berichtsgenerator</a>
                    <a href="#inventory-audit" class="list-group-item list-group-item-action"><i class="fa-solid fa-clipboard-check me-2"></i>Inventur &amp; Bestandsprüfung</a>
                    <a href="#import-export" class="list-group-item list-group-item-action"><i class="fa-solid fa-file-arrow-down me-2"></i>Daten-Import &amp; Export</a>
                    <a href="#releases" class="list-group-item list-group-item-action"><i class="fa-brands fa-square-github me-2"></i>Release-Notes &amp; Changelog</a>
                    <a href="#security" class="list-group-item list-group-item-action"><i class="fa-solid fa-shield-halved me-2"></i>Sicherheit &amp; Login</a>
                    <a href="#shortcuts" class="list-group-item list-group-item-action"><i class="fa-solid fa-keyboard me-2"></i>Tastenkombinationen</a>
                    <a href="#sync" class="list-group-item list-group-item-action"><i class="fa-solid fa-arrows-rotate me-2"></i>Synchronisation &amp; Multi-User</a>
                </div>
            </div>
        </div>

        <!-- Hauptinhalt der Hilfe -->
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <!-- Schnellsuche -->
                    <section id="search" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Schnellsuche &amp; Filter
                        </h5>
                        <p class="small text-muted mb-0">
                            Über das Suchfeld <strong>„QuickFilter“</strong> oben wird die Inventarliste in Echtzeit gefiltert. Es kann nach Name, Seriennummer, Kategorie oder Standort gesucht werden. Über die Schaltfläche <strong>„Nachbestellen“</strong> lassen sich mit einem Klick alle Gegenstände filtern, deren Mindestbestand unterschritten ist.
                        </p>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Aktionen via FAB -->
                    <section id="fab" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-bars-staggered me-2"></i> Schnellaktionen (Aktions-Button)
                        </h5>
                        <p class="small text-muted mb-3">
                            Unten rechts befindet sich ein Menübutton, der wichtige Schnellaktionen und Werkzeuge bereitstellt. Die Aktionen sind auf dem Desktop auch über die Tastenkombination <kbd class="bg-dark">Strg</kbd>+<kbd class="bg-dark">M</kbd> erreichbar. Anschließend kann mit den Pfeiltasten <kbd class="bg-dark">↑</kbd> und <kbd>↓</kbd> die gewünschte Aktion ausgewählt und mit <kbd class="bg-dark">Enter</kbd> ausgeführt werden. <kbd class="bg-dark">Esc</kbd> schließt das Menü wieder.
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-house"></i></span>
                                <span class="small"><strong>Übersicht:</strong> Ruft die Haupt-Bestandsübersicht auf.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge fab-action-primary me-2"><i class="fa-solid fa-plus"></i></span>
                                <span class="small"><strong>Gegenstand anlegen:</strong> Erfasst einen neuen Gegenstand inklusive Foto, Dokumenten und Stammdaten.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-sitemap"></i></span>
                                <span class="small"><strong>Lagerorte:</strong> Verwaltet Räume, Regale und hierarchische Unter-Standorte.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-gear"></i></span>
                                <span class="small"><strong>Einstellungen:</strong> Anpassen von Theme, Vorschaubildern, Passwortschutz und Benutzern.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-chart-pie"></i></span>
                                <span class="small"><strong>Dashboard:</strong> Grafische Auswertungen und Kennzahlen zum Inventar.</span>
                            </div>
                            <div class="d-flex align-items-center bg-light p-2 rounded">
                                <span class="badge bg-secondary me-2"><i class="fa-solid fa-wrench"></i></span>
                                <span class="small"><strong>Tools-Menü:</strong> Öffnet ein Untermenü mit erweiterten Werkzeugen:</span>
                            </div>
                            <div class="ms-4 d-flex flex-column gap-2">
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-print"></i></span>
                                    <span class="small"><strong>Druck:</strong> Öffnet den flexiblen Berichts- und Druckgenerator für Inventar- und Versicherungslisten.</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-clipboard-check"></i></span>
                                    <span class="small"><strong>Inventur:</strong> Startet die Bestandsprüfung und Compliance-Kontrolle (inkl. Set-Inhalten).</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-arrows-up-down-left-right"></i></span>
                                    <span class="small"><strong>Verschiebebahnhof:</strong> Ermöglicht schnelles, visuelles Verschieben von Lagerorten.</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-clock-rotate-left"></i></span>
                                    <span class="small"><strong>Protokoll:</strong> Zeigt die letzten Änderungen und bietet Rücksetzoptionen.</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-box-archive"></i></span>
                                    <span class="small"><strong>Archiv:</strong> Listet archivierte Gegenstände zur Reaktivierung.</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-solid fa-file-arrow-down"></i></span>
                                    <span class="small"><strong>Import / Export:</strong> Datenaustausch und Sicherung.</span>
                                </div>
                                <div class="d-flex align-items-center bg-light p-2 rounded">
                                    <span class="badge bg-secondary me-2"><i class="fa-brands fa-square-github"></i></span>
                                    <span class="small"><strong>Release-Notes:</strong> Informationen zu aktuellen Aktualisierungen.</span>
                                </div>
                            </div>
                        </div>
                    </section>


                    <hr class="my-1 text-black-50">

                    <!-- QR-Code-Scanner & QR-Codes -->
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
                                <strong>QR-Codes generieren &amp; drucken:</strong> Für jeden Gegenstand sowie jeden Lagerort wird automatisch ein individueller QR-Code erzeugt. Dieser kann auf Etiketten ausgedruckt und an Kisten, Regalen oder Geräten angebracht werden.
                            </li>
                            <li>
                                <strong>Direkter Aufruf:</strong> Das Scannen eines QR-Codes mit der regulären Smartphone-Kamera öffnet direkt die passende Detailseite im Browser.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Design & Bildeinstellungen -->
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

                    <!-- Gegenstände bearbeiten & archivieren -->
                    <section id="items" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-pen-to-square me-2"></i> Gegenstände bearbeiten &amp; archivieren
                        </h5>
                        <p class="small text-muted mb-2">
                            Ein Klick auf eine Gegenstandskarte öffnet die Detailansicht zum Ändern aller Eigenschaften:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Stammdaten &amp; Hersteller:</strong> Neben der Bezeichnung und Beschreibung kann direkt der Hersteller (z. B. für Werkzeuge oder Geräte) gepflegt werden.
                            </li>
                            <li class="mb-1">
                                <strong>Bestand &amp; Mindestbestand:</strong> Neben der aktuellen Menge lässt sich ein Mindestbestand definieren (0 = kein Mindestbestand), um Unterdeckungen im Blick zu behalten.
                            </li>
                            <li class="mb-1">
                                <strong>Hauptbild festlegen (Stern-Symbol):</strong> Bei Gegenständen mit mehreren Bildern kann durch Klick auf das Stern-Symbol ein Bild als <em>Hauptbild</em> definiert werden. Dieses erscheint als Titelbild in der Übersicht und in Kachelansichten.
                            </li>
                            <li class="mb-1">
                                <strong>Fotos per Smartphone-Kamera:</strong> Beim Hinzufügen von Bildern auf mobilen Geräten öffnet sich auf Wunsch direkt die Kamera für Schnappschüsse.
                            </li>
                            <li class="mb-1">
                                <strong>Dokumente &amp; Anleitungen (mit Typisierung):</strong> Es können Dokumente (PDF, DOCX, TXT) hochgeladen werden. Diese werden festen Typen (*Bedienungsanleitung*, *Rechnung*, *Sonstiges*) zugeordnet, was u. a. für die automatische Rechnungsprüfung in Berichten genutzt wird.
                            </li>
                            <li class="mb-1">
                                <strong>Anschaffung &amp; Versicherung:</strong> Erfassung von Anschaffungspreis, Kaufdatum, Händler/Kaufort, Garantiedauer in Monaten sowie dem wichtigen Flag <em>"Für Versicherungsliste markieren"</em>.
                            </li>
                            <li class="mb-1">
                                <strong>Verleih-Status:</strong> Gegenstände können mit Namen ("Verliehen an") und Verleihdatum versehen werden, um den Verbleib nachzuvollziehen.
                            </li>
                            <li class="mb-1">
                                <strong>Fristen &amp; Wartung:</strong> Pflege von Ablauf-/Verfallsdaten sowie Wartungsintervallen (in Monaten) inklusive des Datums der letzten Durchführung.
                            </li>
                            <li>
                                <strong>Archivieren:</strong> Ein Gegenstand kann über die Detailansicht archiviert werden. Archivierte Gegenstände werden aus der aktiven Übersicht entfernt, können aber in der Archivseite jederzeit wiederhergestellt werden.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Set-Verwaltung -->
                    <section id="sets" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-layer-group me-2"></i> Set-Verwaltung &amp; Inventur-Audit
                        </h5>
                        <p class="small text-muted mb-2">
                            Komplexe Ausrüstungsgegenstände oder Kisten können als Set organisiert werden:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Als Set markieren:</strong> Durch Aktivieren der Checkbox "Dieser Gegenstand ist ein Set" im Artikelformular wird die Set-Verwaltung freigeschaltet.
                            </li>
                            <li class="mb-1">
                                <strong>Modalbasierter Editor (1+n Elemente):</strong> Über den Button "Set-Inhalte verwalten" öffnet sich ein Editor, in dem beliebig viele Freitext-Komponenten inklusive Stückzahlen hinzugefügt werden können.
                            </li>
                            <li class="mb-1">
                                <strong>Drag &amp; Drop Sortierung:</strong> Die Inhaltsteile lassen sich im Modal per Drag &amp; Drop (unterstützt durch SortableJS) in eine logische Reihenfolge bringen.
                            </li>
                            <li>
                                <strong>Inventur-Audit-Ansicht:</strong> Set-Komponenten werden in Audit- und Bestandsprüfungsansichten direkt inline angezeigt, um die Vollständigkeit (Compliance-Prüfung) unkompliziert zu kontrollieren.
                            </li>
                        </ul>
                    </section>   

                    <hr class="my-1 text-black-50">                 

                    <!-- Tag-System & Filterung -->
                    <section id="tags" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-tags me-2"></i> Tag-System &amp; Filterung
                        </h5>
                        <p class="small text-muted mb-2">
                            Gegenstände können flexibel mit Tags (Schlagwörtern) versehen werden, um sie blitzschnell zu kategorisieren und zu filtern:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Tags hinzufügen:</strong> In der Tag-Leiste auf der Detailseite für gegenstände können bestehende Tags per Autocomplete ausgewählt oder neue Begriffe eingetippt werden. Bestätigen Sie die Eingabe einfach mit der <strong>Enter-Taste</strong> oder einem <strong>Komma</strong>. Es können beliebig viele Tags zu einem Gegenstand hinzugefügt werden. Die Tags weredn automatisch gespeichert.
                            </li>
                            <li class="mb-1">
                                <strong>Tagfilter:</strong> Analog zur Eingabe können auf der Übersichtsseite Tags als Filter verwendet werden. Sobald mehr als ein Tag eingegeben ist, erscheint ein ODER-UND-Umschalter, um die Filterlogik zu steuern. Die Filterung erfolgt in Echtzeit, sodass die Liste sofort aktualisiert wird.
                            </li>
                            <li class="mb-1">
                                <strong>ODER-Modus (Standard):</strong> Es werden alle Gegenstände angezeigt, die <em>mindestens einen</em> der ausgewählten Tags besitzen.
                            </li>
                            <li class="mb-1">
                                <strong>UND-Modus:</strong> Es werden nur Gegenstände angezeigt, die <em>alle</em> der ausgewählten Tags gleichzeitig aufweisen.
                            </li>
                            <li>
                                <strong>Filter zurücksetzen:</strong> Über das kleine Kreuz-Symbol am Ende der Eingabeleiste oder das Löschen einzelner Tag-Pills lässt sich der Filter jederzeit mit einem Klick aufheben.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">


                    <!-- Lagerortverwaltung & Struktur -->
                    <section id="locations" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-folder-tree me-2"></i> Lagerortverwaltung &amp; Struktur
                        </h5>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1"><strong>Neuen Lagerort anlegen:</strong> Gib eine Bezeichnung ein und wähle optional einen übergeordneten Lagerort (Parent), um Hierarchien (z. B. Raum → Regal) aufzubauen.</li>
                            <li class="mb-1"><strong>Baumansicht & Inhalte:</strong> Standorte werden sauber eingerichtet dargestellt. Über den Schalter „Inhalte anzeigen“ kannst du dir alle gelagerten Gegenstände direkt einblenden lassen.</li>
                            <li class="mb-1"><strong>Lagerort bearbeiten:</strong> Das Stift-Symbol öffnet ein Modal, in dem du den Namen oder den übergeordneten Ort anpassen kannst.</li>
                            <li class="mb-1"><strong>QR-Code & Löschen:</strong> Jeder Standort bietet einen Direktzugriff auf den QR-Code sowie eine Löschprüfung, ob noch Gegenstände oder Unterorte enthalten sind.</li>
                            <li><strong>Umlagern:</strong> Wähle Quell- und Ziel-Lagerort. Bei vorhandenen Unterlagerorten öffnet sich ein intelligentes Abfrage-Modal, mit dem du flexibel steuerst, ob Gegenstände, Unterorte oder ganze Zweige mitverschoben werden.</li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Verschiebebahnhof -->
                    <section id="shuffle" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-shuffle me-2"></i> Verschiebebahnhof
                        </h5>
                        <p class="small text-muted mb-2">
                            Der Verschiebebahnhof ermöglicht das schnelle Umsortieren von Lagerorten. Die Lagerorte werden in der gleichen Baumstruktur wie in der Lagerortverwaltung angezeigt. Jeder Lagerort hat dabei einen "Greifer", mit dem er innerhalb der Struktur verschoben werden kann. Die Änderungen werden sofort übernommen und optisch signalisiert. So können Lagerorte flexibel umsortiert werden, ohne dass Gegenstände oder Unterlagerorte verloren gehen.
                        </p>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Dashboard & Auswertung -->
                    <section id="dashboard" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-chart-pie me-2"></i> Dashboard &amp; Auswertung
                        </h5>
                        <p class="small text-muted mb-2">
                            Das Dashboard bietet eine zentrale Übersicht und Kennzahlen für das gesamte Inventar:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Kennzahlen (KPIs):</strong> Auf einen Blick werden der Gesamtwert des Inventars, die Anzahl verliehener Gegenstände, Gegenstände unter Mindestbestand, ablaufende Gegenstände sowie fällige Wartungen angezeigt.
                            </li>
                            <li class="mb-1">
                                <strong>Nachbestellungen:</strong> Eine separate Widget-Liste zeigt alle Gegenstände, deren Bestand den definierten Mindestbestand unterschritten hat, damit diese rechtzeitig nachbestellt werden können.
                            </li>
                            <li class="mb-1">
                                <strong>Anstehende Wartungen:</strong> Listet Gegenstände auf, deren Wartungsintervall in den nächsten 30 Tagen abläuft oder bereits überfällig ist.
                            </li>
                            <li class="mb-1">
                                <strong>Aktive Verleihvorgänge:</strong> Eine kompakte Widget-Liste zeigt sofort, welcher Gegenstand aktuell an welche Person verliehen ist und wie hoch die verliehene Menge ist.
                            </li>
                            <li>
                                <strong>Ablauf-Warnungen:</strong> Gegenstände, deren Verfallsdatum in den nächsten 30 Tagen erreicht wird oder bereits überschritten ist, werden hervorgehoben, um rechtzeitig reagieren zu können.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Druck- & Berichtsgenerator -->
                    <section id="printing" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-print me-2"></i> Druck- &amp; Berichtsgenerator
                        </h5>
                        <p class="small text-muted mb-2">
                            Über das Druck-Modul lassen sich Berichte in Echtzeit im Browser filtern und ausgeben:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Echtzeit-Client-Filterung:</strong> Alle Berichtsarten und Lagerorte schalten und filtern sofort im Browser, ganz ohne störende Seiten-Reloads.
                            </li>
                            <li class="mb-1">
                                <strong>Flexible Lagerort-Auswahl:</strong> Per Checkbox kann über alle Lagerorte hinweg gedruckt werden oder über beliebig viele kombinierbare Standort-Dropdowns (inklusive automatischer Einbeziehung aller Unterlagerorte).
                            </li>
                            <li class="mb-1">
                                <strong>Standard-Inventardruck:</strong> Kompakte Kachelkarten (optimiert für mindestens 6 Artikel pro Seite) mit Hauptbild, Stammdaten und farbigen Dokument-Badges.
                            </li>
                            <li class="mb-1">
                                <strong>Automatisierte Versicherungsliste:</strong> Kompakte Tabellenansicht aller versicherungsrelevanten Gegenstände. Das System prüft automatisch, ob eine Rechnung verknüpft ist, und berechnet den Gesamtwert aus Menge und Kaufpreis.
                            </li>
                            <li>
                                <strong>Direkte Verlinkung:</strong> Gegenstandsnamen sind direkt mit der Bearbeitungsansicht verknüpft, sodass Unstimmigkeiten sofort korrigiert werden können.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- Inventur & Bestandsprüfung -->
                    <section id="inventory-audit" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-clipboard-check me-2"></i> Inventur &amp; Bestandsprüfung
                        </h5>
                        <p class="small text-muted mb-2">
                            Das Inventur-Modul unterstützt bei der regelmäßigen Kontrolle und Bestandsaufnahme der gelagerten Güter:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Systematische Bestandsaufnahme:</strong> Die Inventur-Ansicht listet Gegenstände strukturiert auf, um Soll- und Ist-Bestände abzugleichen.
                            </li>
                            <li class="mb-1">
                                <strong>Inline-Set-Prüfung:</strong> Auch komplexe Set-Inhalte werden direkt in der Inventur- und Audit-Ansicht angezeigt, sodass Inhaltsteile und Vollständigkeiten unkompliziert mit überprüft werden können.
                            </li>
                            <li class="mb-1">
                                <strong>Schnelle Korrekturen:</strong> Abweichungen können direkt im Zuge der Prüfung eingesehen und über die Artikelbearbeitung angepasst werden.
                            </li>
                        </ul>
                    </section> 

                    <hr class="my-1 text-black-50">

                    <!-- Daten-Import & Export -->
                    <section id="import-export" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-file-arrow-down me-2"></i> Daten-Import &amp; Export
                        </h5>
                        <p class="small text-muted mb-2">
                            Über die Schnittstelle können Bestände flexibel gesichert oder massenhaft aktualisiert werden:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>CSV-Export:</strong> Das gesamte Inventar lässt sich per Mausklick als strukturierte CSV-Datei (kompatibel mit Excel und LibreOffice) im UTF-8-Format herunterladen. Die Datei enthält alle Gegenstandsattribute inklusive der Lagerort-ID und des Lagerortnamens.
                            </li>
                            <li class="mb-1">
                                <strong>Lagerort-Export:</strong> Zur Vorbereitung von umfassenden Importen ist es sinnvoll, die jeweiligen IDs der verschiedenen Lagerorte zu kennen, damit im Anschluß an den Export nicht zu viele nacharbeiten notwendig sind. Dieser Export zeigt die IDs der aktuell verfügbaren Lagerorte.
                            </li>
                            <li class="mb-1">
                                <strong>Automatisierter Import &amp; Updates:</strong> Durch Hochladen einer formatierten CSV-Datei werden neue Gegenstände angelegt oder bestehende Gegenstände anhand ihrer exakten Bezeichnung automatisch aktualisiert.
                            </li>
                            <li class="mb-1">
                                <strong>Standard-Lagerort:</strong> Für den Import kann vorab ein Standard-Lagerort im Formular ausgewählt werden, der automatisch für neue Gegenstände greift, sofern in der CSV keine individuelle Zuordnung erfolgt.
                            </li>
                            <li class="mb-1">
                                <strong>Unterstützte Spalten &amp; Format:</strong> Die CSV-Datei kann mit Semikolon (<code>;</code>) oder Komma (<code>,</code>) getrennt werden. Neben Pflichtfeldern wie der <em>Bezeichnung</em> werden optional auch Angaben zu Beschreibung, Menge, Mindestbestand, Preis, Ablaufdatum, Verleihstatus sowie <code>lagerort_id</code> oder <code>Lagerort</code> unterstützt.
                            </li>
                            <li>
                                <strong>Transaktionssicherheit:</strong> Der Import läuft abgesichert ab. Bei fehlerhaften Datumsformaten oder ungültigen Werten bricht der Vorgang vollständig ab (Rollback), sodass absolut keine Daten beschädigt werden.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 11. Release-Notes & Updates -->
                    <section id="releases" class="mb-5">
                        <h5 class="text-danger d-flex align-items-center fw-bold">
                            <i class="fa-brands fa-square-github me-2"></i> Release-Notes &amp; Changelog
                        </h5>
                        <p class="small text-muted mb-2">
                            Das System informiert automatisch über Neuerungen und Funktionserweiterungen:
                        </p>
                        <ul class="small text-muted ps-3 mb-0">
                            <li class="mb-1">
                                <strong>Automatische Benachrichtigung:</strong> Bei neuen Aktualisierungen öffnet sich beim ersten Aufruf eigenständig ein Release-Modal mit den wichtigsten Neuerungen.
                            </li>
                            <li class="mb-1">
                                <strong>Markdown-Darstellung:</strong> Release-Texte und Changelogs werden formatiert aufbereitet, um Änderungen übersichtlich darzustellen.
                            </li>
                            <li>
                                <strong>Gesehen-Status:</strong> Bereits gelesene Versionshinweise werden im Hintergrund als gesehen markiert, damit das Modal bei Folgeaufrufen nicht erneut aufspringt.
                            </li>
                        </ul>
                    </section>

                    <hr class="my-1 text-black-50">

                    <!-- 12. Sicherheit & Zugriffsschutz -->
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

                    <!-- 13. Tastenkombinationen -->
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

                    <!-- 13. Multi-User & Synchronisation -->
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
