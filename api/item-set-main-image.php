<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$itemId = (int)($_POST['itemId'] ?? 0);
$imageId = (int)($_POST['imageId'] ?? 0);

if ($itemId <= 0 || $imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ungültige IDs']);
    exit;
}

try {
    // Alle Bilder des Items auf is_main = 0 setzen
    $pdo->prepare("UPDATE item_images SET is_main = 0 WHERE item_id = ?")->execute([$itemId]);

    // Das gewählte Bild als Hauptbild (is_main = 1) setzen
    $stmt = $pdo->prepare("UPDATE item_images SET is_main = 1 WHERE id = ? AND item_id = ?");
    $stmt->execute([$imageId, $itemId]);

    // Loggen
    $stmtItem = $pdo->prepare("SELECT name, quantity FROM inventory_items WHERE id = ?");
    $stmtItem->execute([$itemId]);
    $itemData = $stmtItem->fetch();
    if ($itemData && function_exists('logInventoryAction')) {
        logInventoryAction($pdo, $itemId, $itemData['name'], 'updated', 0, (int)$itemData['quantity'], 'Hauptbild geändert');
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
