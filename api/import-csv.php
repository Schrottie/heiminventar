<?php
// /api/import-csv.php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Nur für angemeldete Benutzer (Beispiel-Check, an euer Auth-System anpassen)
// session_start();
// if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header('Location: ../index.php?mode=import&error=invalid_request');
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];

if (!is_uploaded_file($file)) {
    header('Location: ../index.php?mode=import&error=upload_failed');
    exit;
}

$handle = fopen($file, 'r');
if ($handle === false) {
    header('Location: ../index.php?mode=import&error=file_open_failed');
    exit;
}

// UTF-8 BOM erkennen und überspringen, falls vorhanden
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Kopfzeile auslesen
$header = fgetcsv($handle, 0, ';');
if ($header === false) {
    fclose($handle);
    header('Location: ../index.php?mode=import&error=empty_file');
    exit;
}

$pdo->beginTransaction();

try {
    // Mapping von CSV-Hauptartikel-ID zu echter DB-ID
    $mainIdMapping = [];
    $importedCount = 0;
    $partsCount = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        // Sicherstellen, dass genügend Spalten vorhanden sind
        if (count($row) < 4) continue;

        $type            = trim($row[0]); // 'Hauptartikel' oder 'Anbau-/Zubehörteil'
        $csvId           = trim($row[1]); // ID aus CSV
        $csvMainId       = trim($row[2]); // Hauptartikel-ID für Zubehör
        $name            = trim(str_replace('└─', '', $row[3])); // Visuelle Zeichen entfernen
        $description     = trim($row[4] ?? '');
        $manufacturer    = trim($row[5] ?? '');
        $locationName    = trim($row[6] ?? '');
        $quantity        = max(1, (int)($row[7] ?? 1));
        $price           = (float)str_replace(',', '.', $row[9] ?? '0');
        $purchaseDate    = trim($row[11] ?? null);
        $purchaseLocation= trim($row[12] ?? '');
        $isInsured       = (stripos(trim($row[13] ?? ''), 'ja') !== false) ? 1 : 0;
        $isSet           = (stripos(trim($row[14] ?? ''), 'ja') !== false) ? 1 : 0;
        $expirationDate  = trim($row[15] ?? null);
        $tagsString      = trim($row[16] ?? '');

        if ($name === '') continue;

        // Lagerort-ID anhand des Namens ermitteln (falls angegeben)
        $locationId = null;
        if ($locationName !== '') {
            $stmtLoc = $pdo->prepare("SELECT id FROM locations WHERE name = ? LIMIT 1");
            $stmtLoc->execute([$locationName]);
            $loc = $stmtLoc->fetch(PDO::FETCH_ASSOC);
            if ($loc) {
                $locationId = (int)$loc['id'];
            }
        }

        if ($type === 'Anbau-/Zubehörteil') {
            // --- ZUBEHÖRTEIL VERARBEITEN ---
            $realParentId = null;
            
            // Versuche Parent-ID über das Mapping zu finden
            if ($csvMainId !== '' && isset($mainIdMapping[$csvMainId])) {
                $realParentId = $mainIdMapping[$csvMainId];
            }

            if ($realParentId) {
                $stmtPart = $pdo->prepare("
                    INSERT INTO item_parts (item_id, name, quantity, price, invoice_status) 
                    VALUES (?, ?, ?, ?, 'none')
                ");
                $stmtPart->execute([$realParentId, $name, $quantity, $price]);
                $partsCount++;
            }

        } else {
            // --- HAUPTARTIKEL VERARBEITEN ---
            $stmtItem = $pdo->prepare("
                INSERT INTO inventory_items (
                    name, description, manufacturer, location_id, quantity, 
                    price, purchase_date, purchase_location, is_insured, is_set, 
                    expiration_date, is_archived
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0')
            ");
            
            $stmtItem->execute([
                $name,
                $description !== '' ? $description : null,
                $manufacturer !== '' ? $manufacturer : null,
                $locationId,
                $quantity,
                $price,
                $purchaseDate !== '' ? $purchaseDate : null,
                $purchaseLocation !== '' ? $purchaseLocation : null,
                $isInsured,
                $isSet,
                $expirationDate !== '' ? $expirationDate : null
            ]);

            $newDbId = $pdo->lastInsertId();
            $importedCount++;

            // Mapping für nachfolgendes Zubehör sichern (falls CSV eine ID hatte)
            if ($csvId !== '') {
                $mainIdMapping[$csvId] = $newDbId;
            }
            // Fallback: Auch die sequenzielle Zeilennummer oder CSV-ID als direkten Bezug erlauben
            $mainIdMapping[$newDbId] = $newDbId;

            // Optional: Tags verarbeiten, falls im CSV angegeben
            if ($tagsString !== '') {
                $tags = explode(',', $tagsString);
                foreach ($tags as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName === '') continue;

                    // Tag suchen oder anlegen
                    $stmtTag = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
                    $stmtTag->execute([$tagName]);
                    $tag = $stmtTag->fetch(PDO::FETCH_ASSOC);

                    if ($tag) {
                        $tagId = $tag['id'];
                    } else {
                        $stmtNewTag = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmtNewTag->execute([$tagName]);
                        $tagId = $pdo->lastInsertId();
                    }

                    // Verknüpfung in item_tags herstellen
                    $stmtRel = $pdo->prepare("INSERT IGNORE INTO item_tags (item_id, tag_id) VALUES (?, ?)");
                    $stmtRel->execute([$newDbId, $tagId]);
                }
            }
        }
    }

    fclose($handle);
    $pdo->commit();

    header('Location: ../index.php?mode=list&import=success&items=' . $importedCount . '&parts=' . $partsCount);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    fclose($handle);
    header('Location: ../index.php?mode=import&error=' . urlencode($e->getMessage()));
    exit;
}
