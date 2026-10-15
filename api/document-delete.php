<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$documentId = (int)($_GET['id'] ?? 0);
$itemId = (int)($_GET['item_id'] ?? 0);

if ($documentId <= 0 || $itemId <= 0) {
    die('Ungültige Parameter');
}

// 1. Dokumenten-Informationen aus der Datenbank abfragen, um den Dateinamen zu erhalten
$stmt = $pdo->prepare("SELECT filename, title FROM item_documents WHERE id = ? AND item_id = ?");
$stmt->execute([$documentId, $itemId]);
$doc = $stmt->fetch();

if (!$doc) {
    die('Dokument nicht gefunden');
}

// 2. Physische Datei löschen, falls sie existiert
$filePath = __DIR__ . '/../docs/items/' . $doc['filename'];
if (file_exists($filePath)) {
    @unlink($filePath);
}

// 3. Aus der Datenbank löschen
$stmtDelete = $pdo->prepare("DELETE FROM item_documents WHERE id = ?");
$stmtDelete->execute([$documentId]);

// 4. Optional: Aktion protokollieren
logInventoryAction(
    $pdo,
    $itemId,
    'Dokument',
    'updated',
    0,
    0,
    'Dokument gelöscht: ' . $doc['title']
);

// 5. Zurück zur Detailseite des Gegenstands
header("Location: ../item.php?id=" . $itemId . "&success=1");
exit;
