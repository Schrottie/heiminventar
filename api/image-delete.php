<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// ID aus dem POST-Formular des Modals abfangen
$imageId = (int)($_POST['image_id'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);

if ($imageId <= 0) {
    die('Ungültige Bild-ID');
}

// 1. Bilddaten aus der Datenbank holen (für den Dateinamen)
$stmt = $pdo->prepare("SELECT * FROM item_images WHERE id = ?");
$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    die('Bild nicht gefunden.');
}

// Fallback, falls item_id nicht mitgeschickt wurde
if ($itemId <= 0) {
    $itemId = (int)$image['item_id'];
}

$filename = $image['filename'];

// 2. Datei physisch vom Server löschen (Pfad anpassen falls nötig, z.B. ../img/items/)
$filePath = __DIR__ . '/../img/items/' . $filename;
if (file_exists($filePath)) {
    @unlink($filePath);
}

// 3. Datenbankeintrag löschen
$stmtDel = $pdo->prepare("DELETE FROM item_images WHERE id = ?");
$stmtDel->execute([$imageId]);

// 4. Zurück zur Artikel-Ansicht
header('Location: ../item.php?id=' . $itemId);
exit;
