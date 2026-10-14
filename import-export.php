<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

$message = '';
$error = '';

// Alle Lagerorte für das Dropdown im Import-Formular laden
$allLocations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// --- IMPORT LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $defaultLocationId = isset($_POST['default_location_id']) && is_numeric($_POST['default_location_id']) ? (int)$_POST['default_location_id'] : null;
   
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $file['tmp_name'];
       
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            // Prüfen auf UTF-8 BOM und ggf. überspringen
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Erste Zeile (Header) auslesen
            $header = fgetcsv($handle, 1000, ';');
            $delimiter = ';';
           
            if (!$header || count($header) < 2) {
                // Fallback auf Komma als Trennzeichen
                rewind($handle);
                $header = fgetcsv($handle, 1000, ',');
                $delimiter = ',';
            }

            // Header-Namen für optionale Spalten analysieren (Groß-/Kleinschreibung ignorieren)
            $headerNormalized = array_map(function($h) {
                return mb_strtolower(trim(str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $h)));
            }, $header);

            $colMap = [];
            foreach ($headerNormalized as $index => $colName) {
                if (str_contains($colName, 'bezeichnung') || str_contains($colName, 'name')) $colMap['name'] = $index;
                if (str_contains($colName, 'beschreibung')) $colMap['description'] = $index;
                if (str_contains($colName, 'menge')) $colMap['quantity'] = $index;
                if (str_contains($colName, 'mindestbestand')) $colMap['min_quantity'] = $index;
                if (str_contains($colName, 'preis')) $colMap['price'] = $index;
                if (str_contains($colName, 'ablauf')) $colMap['expiration_date'] = $index;
                if (str_contains($colName, 'verliehen')) $colMap['loan_to'] = $index;
                if ($colName === 'lagerort_id') $colMap['location_id'] = $index;
                if ($colName === 'lagerort' && !isset($colMap['location_name'])) $colMap['location_name'] = $index;
            }

            // Validierung: Mindestens die Spalte "Bezeichnung" muss vorhanden sein
            if (!isset($colMap['name'])) {
                $error = 'Das CSV-Format ist ungültig. Die Spalte "Bezeichnung" (oder "Name") wird zwingend benötigt.';
                fclose($handle);
            } else {
                try {
                    // Transaktion starten für Datensicherheit
                    $pdo->beginTransaction();
                   
                    $importedCount = 0;
                    $updatedCount = 0;
                    $rowNumber = 1;

                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                        $rowNumber++;
                        $nameIdx = $colMap['name'];
                        if (empty(trim($data[$nameIdx] ?? ''))) continue; // Leere Zeilen überspringen
                       
                        $name = trim($data[$nameIdx]);
                        $description = isset($colMap['description']) ? trim($data[$colMap['description']] ?? '') : '';
                        $quantity = isset($colMap['quantity']) && is_numeric($data[$colMap['quantity']]) ? (int)$data[$colMap['quantity']] : 0;
                        $minQuantity = isset($colMap['min_quantity']) && is_numeric($data[$colMap['min_quantity']]) ? (int)$data[$colMap['min_quantity']] : 0;
                        $price = (isset($colMap['price']) && !empty($data[$colMap['price']])) ? (float)str_replace(',', '.', $data[$colMap['price']]) : null;
                        $expirationDate = (isset($colMap['expiration_date']) && !empty($data[$colMap['expiration_date']])) ? trim($data[$colMap['expiration_date']]) : null;
                        $loanTo = (isset($colMap['loan_to']) && !empty($data[$colMap['loan_to']])) ? trim($data[$colMap['loan_to']]) : null;

                        // Lagerort-Ermittlung beim Import: Priorität 1: lagerort_id in CSV -> Priorität 2: Lagerort-Name in CSV -> Priorität 3: Standard-Lagerort aus Formular
                        $targetLocId = $defaultLocationId;

                        if (isset($colMap['location_id']) && !empty($data[$colMap['location_id']]) && is_numeric($data[$colMap['location_id']])) {
                            $targetLocId = (int)$data[$colMap['location_id']];
                        } elseif (isset($colMap['location_name']) && !empty($data[$colMap['location_name']])) {
                            $locNameInput = trim($data[$colMap['location_name']]);
                            // Prüfen ob Lagerort existiert, sonst ggf. mappen
                            $stmtLoc = $pdo->prepare("SELECT id FROM locations WHERE name = ?");
                            $stmtLoc->execute([$locNameInput]);
                            $foundLoc = $stmtLoc->fetch();
                            if ($foundLoc) {
                                $targetLocId = (int)$foundLoc['id'];
                            }
                        }

                        // Datum validieren, falls gesetzt
                        if ($expirationDate && !strtotime($expirationDate)) {
                            throw new PDOException("Ungültiges Datumsformat in Zeile $rowNumber: '$expirationDate'. Erwartet: YYYY-MM-DD.");
                        }

                        // Prüfen, ob Gegenstand bereits existiert
                        $stmtCheck = $pdo->prepare("SELECT id FROM inventory_items WHERE name = ? AND is_archived = '0'");
                        $stmtCheck->execute([$name]);
                        $existing = $stmtCheck->fetch();

                        if ($existing) {
                            // Update (Lagerort nur überschreiben, wenn in CSV explizit angegeben oder Standard gewählt)
                            $stmtUp = $pdo->prepare("UPDATE inventory_items SET description = ?, quantity = ?, min_quantity = ?, price = ?, expiration_date = ?, loan_to = ?, location_id = COALESCE(?, location_id) WHERE id = ?");
                            $stmtUp->execute([$description, $quantity, $minQuantity, $price, $expirationDate, $loanTo, $targetLocId, $existing['id']]);
                            $updatedCount++;
                        } else {
                            // Neu einfügen
                            $stmtIns = $pdo->prepare("INSERT INTO inventory_items (name, description, quantity, min_quantity, price, expiration_date, loan_to, location_id, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '0')");
                            $stmtIns->execute([$name, $description, $quantity, $minQuantity, $price, $expirationDate, $loanTo, $targetLocId]);
                            $importedCount++;
                        }
                    }
                    fclose($handle);
                   
                    // Alles ok -> Transaktion bestätigen
                    $pdo->commit();
                    $message = "Import erfolgreich! Neu hinzugefügt: $importedCount, Aktualisiert: $updatedCount.";

                } catch (Exception $e) {
                    // Bei Fehler alles zurückrollen
                    $pdo->rollBack();
                    $error = 'Fehler beim Importieren: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'Die hochgeladene Datei konnte nicht geöffnet werden.';
        }
    } else {
        $error = 'Fehler beim Hochladen der Datei.';
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="fa-solid fa-file-arrow-down text-primary me-2"></i>Daten-Import &amp; Export</h3>
        <p class="text-muted small mb-0">Sichere deine Inventardaten als CSV oder spüle Bestände automatisiert ein.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Export Karte -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent">
                <i class="fa-solid fa-file-excel text-success me-2"></i>Daten exportieren
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted small">
                        Lade eine vollständige CSV-Datei deines Inventars oder eine Übersicht aller Lagerorte (inkl. IDs) herunter.
                    </p>
                </div>
                <div class="d-flex flex-column gap-2">
                    <a href="api/export-csv.php" class="btn btn-success w-100">
                        <i class="fa-solid fa-download me-2"></i>Inventar-Export herunterladen
                    </a>
                    <a href="api/export-locations-csv.php" class="btn btn-outline-success w-100">
                        <i class="fa-solid fa-sitemap me-2"></i>Lagerort-IDs Exportieren
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Karte -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent">
                <i class="fa-solid fa-file-import text-primary me-2"></i>Inventar importieren
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Lade eine passende CSV-Datei hoch, um Gegenstände hinzuzufügen oder bestehende anhand ihrer Bezeichnung zu aktualisieren.
                </p>
                <form action="import-export.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV-Datei auswählen</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="default_location_id" class="form-label">Standard-Lagerort (für neue Gegenstände)</label>
                        <select class="form-select" id="default_location_id" name="default_location_id">
                            <option value="">-- Kein Standard-Lagerort --</option>
                            <?php foreach ($allLocations as $loc): ?>
                                <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Wird verwendet, wenn die CSV keine Lagerort-Angabe enthält.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-upload me-2"></i>Daten importieren
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Leitfaden -->
<div class="card shadow-sm border-0 bg-body-tertiary">
    <div class="card-body">
        <h5 class="card-title fs-6 fw-bold mb-3">
            <i class="fa-solid fa-circle-info text-info me-2"></i>Leitfaden zum CSV-Format &amp; Funktionsweise
        </h5>
       
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm bg-white small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Spalte</th>
                        <th>Attribut</th>
                        <th>Pflicht?</th>
                        <th>Beschreibung / Format</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><code>Bezeichnung</code></td>
                        <td><span class="badge bg-danger">Ja</span></td>
                        <td>Der Name des Gegenstands (Dient als Erkennungsmerkmal für Updates).</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><code>Beschreibung</code></td>
                        <td>Nein</td>
                        <td>Freitext zur Beschreibung des Gegenstands.</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><code>Menge</code></td>
                        <td>Nein</td>
                        <td>Ganzzahl (Standard: 0).</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><code>Mindestbestand</code></td>
                        <td>Nein</td>
                        <td>Ganzzahl für Nachbestell-Warnungen (Standard: 0).</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><code>Preis</code></td>
                        <td>Nein</td>
                        <td>Preis mit Punkt oder Komma (z.B. <code>19.99</code>).</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td><code>Ablaufdatum</code></td>
                        <td>Nein</td>
                        <td>Format: <code>YYYY-MM-DD</code>.</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td><code>Verliehen an</code></td>
                        <td>Nein</td>
                        <td>Name der Person.</td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td><code>lagerort_id</code></td>
                        <td>Nein</td>
                        <td>ID des Lagerorts (überschreibt Standard-Lagerort).</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td><code>Lagerort</code></td>
                        <td>Nein</td>
                        <td>Name des Lagerorts (wird automatisch zugeordnet, falls vorhanden).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
