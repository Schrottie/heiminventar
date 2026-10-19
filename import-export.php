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
       
        if (($handle = fopen($filenaAme, 'r')) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $header = fgetcsv($handle, 1000, ';');
            $delimiter = ';';
           
            if (!$header || count($header) < 2) {
                rewind($handle);
                $header = fgetcsv($handle, 1000, ',');
                $delimiter = ',';
            }

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

            if (!isset($colMap['name'])) {
                $error = 'Das CSV-Format ist ungültig. Die Spalte "Bezeichnung" (oder "Name") wird zwingend benötigt.';
                fclose($handle);
            } else {
                try {
                    $pdo->beginTransaction();
                   
                    $importedCount = 0;
                    $updatedCount = 0;
                    $rowNumber = 1;

                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                        $rowNumber++;
                        $nameIdx = $colMap['name'];
                        if (empty(trim($data[$nameIdx] ?? ''))) continue;
                       
                        $name = trim($data[$nameIdx]);
                        $description = isset($colMap['description']) ? trim($data[$colMap['description']] ?? '') : '';
                        $quantity = isset($colMap['quantity']) && is_numeric($data[$colMap['quantity']]) ? (int)$data[$colMap['quantity']] : 0;
                        $minQuantity = isset($colMap['min_quantity']) && is_numeric($data[$colMap['min_quantity']]) ? (int)$data[$colMap['min_quantity']] : 0;
                        $price = (isset($colMap['price']) && !empty($data[$colMap['price']])) ? (float)str_replace(',', '.', $data[$colMap['price']]) : null;
                        $expirationDate = (isset($colMap['expiration_date']) && !empty($data[$colMap['expiration_date']])) ? trim($data[$colMap['expiration_date']]) : null;
                        $loanTo = (isset($colMap['loan_to']) && !empty($data[$colMap['loan_to']])) ? trim($data[$colMap['loan_to']]) : null;

                        $targetLocId = $defaultLocationId;

                        if (isset($colMap['location_id']) && !empty($data[$colMap['location_id']]) && is_numeric($data[$colMap['location_id']])) {
                            $targetLocId = (int)$data[$colMap['location_id']];
                        } elseif (isset($colMap['location_name']) && !empty($data[$colMap['location_name']])) {
                            $locNameInput = trim($data[$colMap['location_name']]);
                            $stmtLoc = $pdo->prepare("SELECT id FROM locations WHERE name = ?");
                            $stmtLoc->execute([$locNameInput]);
                            $foundLoc = $stmtLoc->fetch();
                            if ($foundLoc) {
                                $targetLocId = (int)$foundLoc['id'];
                            }
                        }

                        if ($expirationDate && !strtotime($expirationDate)) {
                            throw new PDOException("Ungültiges Datumsformat in Zeile $rowNumber: '$expirationDate'. Erwartet: YYYY-MM-DD.");
                        }

                        $stmtCheck = $pdo->prepare("SELECT id FROM inventory_items WHERE name = ? AND is_archived = '0'");
                        $stmtCheck->execute([$name]);
                        $existing = $stmtCheck->fetch();

                        if ($existing) {
                            $stmtUp = $pdo->prepare("UPDATE inventory_items SET description = ?, quantity = ?, min_quantity = ?, price = ?, expiration_date = ?, loan_to = ?, location_id = COALESCE(?, location_id) WHERE id = ?");
                            $stmtUp->execute([$description, $quantity, $minQuantity, $price, $expirationDate, $loanTo, $targetLocId, $existing['id']]);
                            $updatedCount++;
                        } else {
                            $stmtIns = $pdo->prepare("INSERT INTO inventory_items (name, description, quantity, min_quantity, price, expiration_date, loan_to, location_id, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '0')");
                            $stmtIns->execute([$name, $description, $quantity, $minQuantity, $price, $expirationDate, $loanTo, $targetLocId]);
                            $importedCount++;
                        }
                    }
                    fclose($handle);
                   
                    $pdo->commit();
                    $message = "Import erfolgreich! Neu hinzugefügt: $importedCount, Aktualisiert: $updatedCount.";

                } catch (Exception $e) {
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
        <p class="text-muted small mb-0">Sichere deine Inventardaten, erstelle komplette System-Backups oder spüle Bestände ein.</p>
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

<!-- 3 Karten in einer Reihe (auf Desktop 3 Spalten, auf Tablets 2/1 Spalte) -->
<div class="row g-4 mb-4">
    <!-- Karte 1: Export & Backup Download -->
    <div class="col-12 col-md-6 col-lg-4">
        <?php require_once __DIR__ . '/inc/import-export/export-card.php'; ?>
    </div>

    <!-- Karte 2: CSV Import -->
    <div class="col-12 col-md-6 col-lg-4">
        <?php require_once __DIR__ . '/inc/import-export/import-card.php'; ?>
    </div>

    <!-- Karte 3: Backup Restore -->
    <div class="col-12 col-md-12 col-lg-4">
        <?php require_once __DIR__ . '/inc/import-export/restore-card.php'; ?>
    </div>
</div>

<!-- Leitfaden zum CSV Format -->
<?php require_once __DIR__ . '/inc/import-export/guide.php'; ?>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
