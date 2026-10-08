# Heiminventar (Inventory Management System)

Eine leichte, responsive Webanwendung zur Verwaltung von Lagerorten und Gegenständen im eigenen Haushalt oder Lager. Das System wurde primär für den Betrieb auf einem Raspberry Pi optimiert und bietet eine übersichtliche Visualisierung von Standortstrukturen, bequeme Drag-and-Drop-Funktionen sowie eine flexible Benutzerverwaltung.

---

## 🚀 Funktionen

### 1. Standortverwaltung (Lagerorte)
* **Hierarchische Baumanzeige:** Beliebig tief verschachtelbare Lagerorte (z. B. *Keller -> Regal 1 -> Box A*).
* **Schnelles Anlegen:** Neue Standorte und Unterlagerorte direkt über das Frontend erstellen.
* **Standortinhalte einblenden:** Per Umschalter (Toggle Switch) lassen sich die darin gelagerten Gegenstände direkt in der Baumansicht anzeigen.
* **Umlager-Assistent:**
  * Vollständiges Umlagern von Quell- zu Ziel-Lagerort per Formular.
  * **Smarte Optionen bei Unterlagerorten:** Wahlweise nur Inhalte verschieben, Inhalte inklusive aller Unterlagerorte verschieben oder die gesamte Standortstruktur mitverschieben.

### 2. Gegenstandsverwaltung (Inventory Items)
* **Detaillierte Erfassung:** Anlegen von Artikeln mit Bezeichnung, Beschreibung, Menge, Foto(s) und zugewiesem Lagerort.
* **Schneller Erfassungsmodus:** Funktion *"Speichern und nächster Gegenstand"*, um mehrere Artikel nacheinander ohne Unterbrechung anzulegen.
* **Bearbeiten & Verwalten:** Direktes Editieren und Löschen aller Gegenstände über Schnellzugriffe.

### 3. QR-Code & Scanner
* **Kamera-Scanner nutzen:** Über das Kamera-Symbol in der Kopfzeile wird der QR-Scanner geöffnet. Sobald ein QR-Code eines Gegenstands oder Lagerorts gescannt wird, springt die Anwendung direkt zum Ziel.
* **QR-Codes generieren & drucken:** Für jeden Artikel sowie jeden Lagerort wird automatisch ein individueller QR-Code erzeugt. Dieser kann auf Etiketten ausgedruckt und an Kisten, Regalen oder Geräten angebracht werden.
* **Direkter Aufruf:** Das Scannen eines QR-Codes mit der regulären Smartphone-Kamera öffnet direkt die passende Detailseite im Browser.

### 4. Artikel bearbeiten, Anleitungen, Fotos & Hauptbild
* **Hauptbild festlegen (Stern-Symbol):** Bei Gegenständen mit mehreren Bildern kann durch Klick auf das Stern-Symbol ein Bild als Hauptbild definiert werden. Dieses erscheint als Titelbild in der Übersicht und in Kachelansichten.
* **Fotos per Smartphone-Kamera:** Beim Hinzufügen von Bildern auf mobilen Geräten öffnet sich auf Wunsch direkt die Kamera für Schnappschüsse.
* **Dokumente & Anleitungen:** Neben Bildern können auch Dokumente (PDF, DOCX, TXT) hochgeladen werden, z. B. Rechnungen, Garantiescheine oder Handbücher.

### 5. Drag & Drop Modus
* **Visuelles Verschieben:** Eigenes Interface mit direkter Darstellung aller Orte und Items.
* **Greifer-Icons:** Gegenstände können bequem per Drag & Drop zwischen verschiedenen Lagerorten verschoben werden.
* **Mobil-Optimiert:** Touch-Unterstützung mit leichtem Delay zur Verhinderung ungewollten Auslösens beim Scrollen auf Smartphones und Tablets.
* **Echtzeit-Anpassung:** Sofortiges Speichern der Standortänderung im Hintergrund via AJAX.

### 6. Authentifizierung & Benutzerverwaltung 🔒
* **Schaltbarer Passwortschutz:** Kann in den Systemeinstellungen mit einem Klick global aktiviert oder deaktiviert werden.
* **Rollenbasiertes Rechtesystem:**
  * **Admin:** Vollzugriff inklusive Systemeinstellungen, Migrationsverwaltung und Benutzersteuerung.
  * **User:** Zugriff auf Inventar, Standorte und Umbuchungen.
* **Benutzerverwaltung:** Anlegen neuer Accounts, Rollenzuweisung (`admin` / `user`), Kennwort-Resets und temporäres Deaktivieren von Konten.
* **Sichere Passwort-Hashes:** Automatische Erzeugung und Verifikation mittels BCRYPT (`password_hash` / `password_verify`).
* **Modernes Login-Interface:** Besserer Komfort mit Passwort-Sichtbarkeits-Toggle ("Auge"-Icon) und integriertem Dark-Mode-Support.

### 7. Systemeinstellungen & Theme-Wahl 🎨
* **Erscheinungsbild (Light/Dark Mode):** Visuelle Auswahl zwischen hellem und dunklem Design per modernem UI-Button-System.
* **Vorschaubilder:** In der Übersicht lassen sich auf Wunsch, sofern vorhanden, Vorschaubilder zu den gelisteten Gegenständen anzeigen. Dazu wird jeweils das erste zum Gegenstand abgelegte Bild verwendet.
* **Automatisches Datenbank-Migrationssystem:** Schema-Updates der Datenbank werden über ein zentrales Skript (`migrations.php`) vollautomatisch im Hintergrund ausgeführt.

### 8. Komfortables Installations-Script
* **Geführte Installation:** Automatische Prüfung aller Systemanforderungen (PDO MySQL, GD-Bibliothek, Dateirechte).
* **Geführtes DB-Setup:** Automatische Erstellung der Datenbank, der Tabellenstruktur und des Datenbank-Benutzers im geführten Dialog (inklusive Support für frische MariaDB-Installationen auf Raspberry Pi OS).

---

## 🛠️ Technologien

* **Backend:** PHP 8+
* **Datenbank:** MariaDB / MySQL (Verbindung via PDO)
* **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, FontAwesome 6
* **Drag & Drop:** [SortableJS](https://sortablejs.github.io/Sortable/)

---

## 📦 Installation & Setup

### Voraussetzungen
* Webserver (Apache2 oder Nginx)
* PHP 8.0 oder höher mit den Erweiterungen `pdo_mysql` und `gd`
* MariaDB / MySQL Datenbankserver

### 1. Repository klonen / herunterladen
```bash
cd /var/www/html
git clone [https://github.com/Schrottie/heiminventar.git](https://github.com/Schrottie/heiminventar.git) inventory
```

### 2. Verzeichnisrechte setzen
```bash
sudo chown -R www-data:$USER /var/www/html/inventory
sudo chmod -R 775 /var/www/html/inventory
```

### 3. Setup im Browser ausführen
1. Öffne `http://<deine-raspi-ip>/inventory/install.php` im Browser.
2. Gib die gewünschten Datenbank-Zugangsdaten ein.
3. Falls die Datenbank oder der Benutzer noch nicht existiert, fordert das Installations-Script die Admin-Zugangsdaten an und legt Datenbank, Benutzer und Tabellen automatisch an.
4. Nach erfolgreicher Installation wird eine `.env`-Datei im Ordner `cfg/` angelegt.

---

## 🔄 Automatische Updates via GitHub auf dem Raspberry Pi

Um das Verzeichnis `/var/www/html/inventory` automatisch mit diesem Repository synchron zu halten:

1. **Cronjob für automatischen Abgleich (alle 5 Minuten) anlegen:**
   ```bash
   crontab -e
   ```
2. **Folgende Zeile einfügen:**
   ```bash
   */5 * * * * cd /var/www/html/inventory && git pull origin main > /dev/null 2>&1
   ```

---

## 📄 Lizenz

Dieses Projekt ist unter der **MIT-Lizenz** veröffentlich – freie Nutzung, Anpassung und Weitergabe für private und kommerzielle Zwecke.