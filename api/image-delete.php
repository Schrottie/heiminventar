<?php

require_once __DIR__ . '/../cfg/db.php';

$imageId = (int)($_GET['id'] ?? 0);
$itemId  = (int)($_GET['item_id'] ?? 0);

if ($imageId <= 0) {
    die('Ungültige Bild-ID');
}

/* =========================================
   Bilddatensatz laden
   ========================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        filename
    FROM item_images
    WHERE id = ?
");

$stmt->execute([$imageId]);

$image = $stmt->fetch();

if (!$image) {
    die('Bild nicht gefunden');
}

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
   Zurück zum Gegenstand
   ========================================= */

header('Location: ../item.php?id=' . $itemId);
exit;