<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

$message = '';
$error = '';

// --- IMPORT LOGIK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
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

            // Validierung: Mindestens die Spalte "Bezeichnung" muss vorhanden sein
            if (!$header || !isset($header[0]) || stripos($header[0], 'Bezeichnung') === false) {
                $error = 'Das CSV-Format ist ungültig. Die erste Spalte muss "Bezeichnung" heißen und das Trennzeichen muss Semikolon (;) oder Komma (,) sein.';
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
                        if (empty(trim($data[0] ?? ''))) continue; // Leere Zeilen überspringen
                        
                        $name = trim($data[0]);
                        $description = trim($data[1] ?? '');
                        $quantity = isset($data[2]) && is_numeric($data[2]) ? (int)$data[2] : 0;
                        $minQuantity = isset($data[3]) && is_numeric($data[3]) ? (int)$data[3] : 0;
                        $price = !empty($data[4]) ? (float)str_replace(',', '.', $data[4]) : null;
                        $expirationDate = !empty($data[5]) ? trim($data[5]) : null;
                        $loanTo = !empty($data[6]) ? trim($data[6]) : null;

                        // Datum validieren, falls gesetzt
                        if ($expirationDate && !strtotime($expirationDate)) {
                            throw new PDOException("Ungültiges Datumsformat in Zeile $rowNumber: '$expirationDate'. Erwartet: YYYY-MM-DD.");
                        }

                        // Prüfen, ob Artikel bereits existiert
                        $stmtCheck = $pdo->prepare("SELECT id FROM inventory_items WHERE name = ? AND is_archived = '0'");
                        $stmtCheck->execute([$name]);
                        $existing = $stmtCheck->fetch();

                        if ($existing) {
                            // Update
                            $stmtUp = $pdo->prepare("UPDATE inventory_items SET description = ?, quantity = ?, min_quantity = ?, price = ?, expiration_date = ?, loan_to = ? WHERE id = ?");
                            $stmtUp->execute([$description, $quantity, $minQuantity, $price, $expirationDate, $loanTo, $existing['id']]);
                            $updatedCount++;
                        } else {
                            // Neu einfügen
                            $stmtIns = $pdo->prepare("INSERT INTO inventory_items (name, description, quantity, min_quantity, price, expiration_date, loan_to, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, '0')");
                            $stmtIns->execute([$name, $description, $quantity, $minQuantity, $price, $expirationDate, $loanTo]);
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
                <i class="fa-solid fa-file-excel text-success me-2"></i>Inventar exportieren
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted small">
                        Lade eine vollständige CSV-Datei deines aktuellen Inventars herunter. Die Datei enthält alle wichtigen Attribute.
                    </p>
                </div>
                <div>
                    <a href="api/export-csv.php" class="btn btn-success w-100">
                        <i class="fa-solid fa-download me-2"></i>CSV-Export herunterladen
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
                    Lade eine passende CSV-Datei hoch, um Artikel hinzuzufügen oder bestehende anhand ihrer Bezeichnung zu aktualisieren.
                </p>
                <form action="import-export.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV-Datei auswählen</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-upload me-2"></i>Daten importieren
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- NEU: Ausführliche Erklärungs-Sektion über die volle Breite -->
<div class="card shadow-sm border-0 bg-body-tertiary">
    <div class="card-body">
        <h5 class="card-title fs-6 fw-bold mb-3">
            <i class="fa-solid fa-circle-info text-info me-2"></i>Leitfaden zum CSV-Format &amp; Funktionsweise
        </h5>
        
        <p class="small text-muted mb-2">
            Damit der Import fehlerfrei funktioniert, muss die CSV-Datei folgenden Aufbau besitzen:
        </p>

        <ul class="small text-muted mb-3 ps-3">
            <li><strong>Trennzeichen:</strong> Semikolon (<code>;</code>) oder Komma (<code>,</code>).</li>
            <li><strong>Zeichensatz:</strong> UTF-8 (wird bei Excel-Exporten automatisch unterstützt).</li>
            <li><strong>Kopfzeile (Header):</strong> Die erste Zeile muss zwingend die Spaltenbezeichnungen enthalten.</li>
        </ul>

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
                        <td>Der Name des Artikels (Dient als Erkennungsmerkmal für Updates).</td>
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
                        <td>Anschaffungspreis mit Punkt oder Komma als Dezimaltrenner (z.B. <code>19.99</code>).</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td><code>Ablaufdatum</code></td>
                        <td>Nein</td>
                        <td>Format: <code>YYYY-MM-DD</code> (z.B. <code>2026-12-31</code>).</td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td><code>Verliehen an</code></td>
                        <td>Nein</td>
                        <td>Name der Person (leer lassen, wenn im Lager).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning mb-0 small">
            <i class="fa-solid fa-shield-halved me-1"></i> <strong>Sicherheits-Hinweis:</strong> 
            Existiert ein Artikel mit exakt demselben Namen bereits im System, werden seine Werte mit den Daten aus der CSV aktualisiert. Existiert er noch nicht, wird er neu angelegt. Bei Fehlern (z.B. falsches Datumsformat) wird der gesamte Import automatisch abgebrochen, sodass keine Daten beschädigt werden.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
