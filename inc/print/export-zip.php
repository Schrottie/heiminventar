<?php
// inc/print/export-zip.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action_insurance_zip_export'])) {
    return;
}

$allLocationsChecked = isset($_POST['all_locations']) && $_POST['all_locations'] === '1';
$postLocationIds = $_POST['location_ids'] ?? [];
$excludedIds = isset($_POST['excluded_item_ids']) ? json_decode($_POST['excluded_item_ids'], true) : [];
if (!is_array($excludedIds)) $excludedIds = [];

$targetLocationIds = [];
if (!$allLocationsChecked && !empty($postLocationIds)) {
    foreach ($postLocationIds as $locId) {
        $locIdInt = (int)$locId;
        if (isset($locationsMap[$locIdInt])) {
            $targetLocationIds = array_merge($targetLocationIds, $locationsMap[$locIdInt]['sub_ids']);
        }
    }
    $targetLocationIds = array_unique($targetLocationIds);
}

// Nur versicherte Artikel laden
$sql = "
    SELECT 
        i.id, i.name, i.quantity, COALESCE(i.on_loan, 0) AS lent_quantity,
        i.price, i.is_insured, i.location_id
    FROM inventory_items i
    WHERE i.is_archived = '0' AND i.is_insured = '1'
";
$rawItems = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$itemDocumentsMap = [];
$itemPartsMap = [];

if (!empty($rawItems)) {
    $itemIds = array_column($rawItems, 'id');
    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        
        $stmtDocs = $pdo->prepare("SELECT id, item_id, filename, title, doc_type FROM item_documents WHERE item_id IN ($placeholders)");
        $stmtDocs->execute($itemIds);
        while ($doc = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
            $itemDocumentsMap[$doc['item_id']][] = $doc;
        }

        $stmtParts = $pdo->prepare("SELECT * FROM item_parts WHERE item_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
        $stmtParts->execute($itemIds);
        while ($part = $stmtParts->fetch(PDO::FETCH_ASSOC)) {
            $itemPartsMap[$part['item_id']][] = $part;
        }
    }
}

$exportItems = [];
foreach ($rawItems as $it) {
    $itemId = (int)$it['id'];
    
    if (in_array($itemId, $excludedIds, true)) continue;

    if (!$allLocationsChecked) {
        if (empty($it['location_id'])) continue;
        if (!in_array((int)$it['location_id'], $targetLocationIds, true)) continue;
    }

    $insuredQty = max(0, (int)$it['quantity'] - (int)$it['lent_quantity']);
    if ($insuredQty <= 0) continue;

    $it['documents'] = $itemDocumentsMap[$itemId] ?? [];
    $it['parts'] = $itemPartsMap[$itemId] ?? [];

    $exportItems[] = $it;
}

$zipFilename = sys_get_temp_dir() . '/Versicherungsbelege_' . date('Y-m-d_Hi') . '.zip';
$zip = new ZipArchive();

if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

    // Korrigierte Dateisuchfunktion mit Schwerpunkt auf /docs/items/
    $findFile = function($filename) {
        if (empty($filename)) return '';
        $baseDir = dirname(__DIR__, 2);
        
        $searchPaths = [
            $baseDir . '/docs/items/' . $filename,
            $baseDir . '/uploads/documents/' . $filename,
            $baseDir . '/uploads/invoices/' . $filename,
            $baseDir . '/uploads/' . $filename,
            $baseDir . '/img/items/' . $filename,
            $baseDir . '/' . $filename
        ];

        foreach ($searchPaths as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }
        return '';
    };

    $addedFilesCount = 0;

    foreach ($exportItems as $item) {
        $itemId = (int)$item['id'];
        $safeItemName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $item['name']);

        // A) Hauptdokumente / Rechnungen
        if (!empty($item['documents'])) {
            foreach ($item['documents'] as $doc) {
                if (empty($doc['filename'])) continue;
                
                $filePath = $findFile($doc['filename']);
                if ($filePath) {
                    $ext = pathinfo($doc['filename'], PATHINFO_EXTENSION);
                    $docTitle = !empty($doc['title']) ? preg_replace('/[^a-zA-Z0-9-_]/', '_', $doc['title']) : 'Beleg';
                    $archiveName = sprintf('Rechnungen/[ID_%03d]_%s_Beleg_%s.%s', $itemId, $safeItemName, $docTitle, $ext);
                    
                    if ($zip->addFile($filePath, $archiveName)) {
                        $addedFilesCount++;
                    }
                }
            }
        }

        // B) Zubehör / Anbauteile Rechnungen
        if (!empty($item['parts'])) {
            foreach ($item['parts'] as $part) {
                if (!empty($part['invoice_filename'])) {
                    $partFilePath = $findFile($part['invoice_filename']);
                    if ($partFilePath) {
                        $ext = pathinfo($part['invoice_filename'], PATHINFO_EXTENSION);
                        $safePartName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $part['name']);
                        $archiveName = sprintf('Rechnungen/[ID_%03d]_%s_Zubehoer_%s.%s', $itemId, $safeItemName, $safePartName, $ext);
                        
                        if ($zip->addFile($partFilePath, $archiveName)) {
                            $addedFilesCount++;
                        }
                    }
                }
            }
        }
    }

    $zip->close();

    // Falls gar keine Dateien gefunden wurden, Hinweis-Datei ins ZIP packen
    if ($addedFilesCount === 0) {
        $zipFallback = new ZipArchive();
        if ($zipFallback->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zipFallback->addFromString('Hinweis.txt', 'Es wurden keine verknüpften Rechnungen/Dokumente für die ausgewählten Artikel im System gefunden.');
            $zipFallback->close();
        }
    }

    // ZIP zum Download ausliefern
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="Versicherungsbelege_' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($zipFilename));
    readfile($zipFilename);
    @unlink($zipFilename);
    exit;
}
