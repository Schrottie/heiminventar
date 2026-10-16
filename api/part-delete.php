<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

session_start();

$partId = (int)($_POST['part_id'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);

if ($partId <= 0 || $itemId <= 0) {
    die('Ungültige ID.');
}

// Vor dem Löschen prüfen, ob eine eigene Rechnungsdatei existiert, um sie zu löschen
$stmtGet = $pdo->prepare("SELECT name, invoice_filename FROM item_parts WHERE id = ? AND item_id = ?");
$stmtGet->execute([$partId, $itemId]);
$part = $stmtGet->fetch(PDO::FETCH_ASSOC);

if ($part) {
    // Falls eine eigene Rechnungsdatei existiert, diese vom Server entfernen
    if (!empty($part['invoice_filename'])) {
        $filePath = __DIR__ . '/../docs/items/' . $part['invoice_filename'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Datensatz löschen
    $stmtDel = $pdo->prepare("DELETE FROM item_parts WHERE id = ?");
    $stmtDel->execute([$partId]);

    // Protokollieren
    $userName = $_SESSION['user_name'] ?? $_SESSION['user']['username'] ?? 'System';
    logInventoryAction(
        $pdo,
        $itemId,
        $part['name'],
        'updated',
        0,
        0,
        'Anbauteil gelöscht: ' . $part['name'],
        'parts',
        $part['name'],
        '',
        $userName
    );
}

header("Location: ../item.php?id=" . $itemId . "&success=part_deleted");
exit;
