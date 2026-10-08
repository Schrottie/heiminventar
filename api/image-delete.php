<?php

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Für logInventoryAction()

$imageId = (int)($_GET['id'] ?? 0);
$itemId  = (int)($_GET['item_id'] ?? 0);

if ($imageId <= 0) {
    die('Ungültige Bild-ID');
}

/* =========================================
   Bilddatensatz & Artikel-Info laden
   ========================================= */

$stmt = $pdo->prepare("
    SELECT
        img.id,
        img.filename,
        img.item_id,
        i.name AS item_name,
        i.quantity AS item_quantity
    FROM item_images img
    LEFT JOIN inventory_items i ON i.id = img.item_id
    WHERE img.id = ?
");

$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    die('Bild nicht gefunden');
}

// Falls item_id nicht über GET übergeben wurde, aus der DB-Abfrage nutzen
$targetItemId = $itemId > 0 ? $itemId : (int)$image['item_id'];
$itemName     = $image['item_name'] ?? 'Unbekannter Artikel';
$itemQty      = (int)($image['item_quantity'] ?? 0);

/* =========================================
   Datei löschen
   ========================================= */

$file = __DIR__ . '/../img/items/' . $image['filename'];

if (file_exists($file)) {
    unlink($file);
}

/* =========================================
   Datenbankeintrag löschen
   ========================================= */

$stmt = $pdo->prepare("
    DELETE FROM item_images
    WHERE id = ?
");

$stmt->execute([$imageId]);

/* =========================================
   Protokolleintrag schreiben
   ========================================= */

if ($targetItemId > 0) {
    logInventoryAction(
        $pdo,
        $targetItemId,
        $itemName,
        'updated',
        0,
        $itemQty,
        'Bild gelöscht (' . $image['filename'] . ')'
    );
}

/* =========================================
   Zurück zum Gegenstand
   ========================================= */

header('Location: ../item.php?id=' . $targetItemId);
exit;
