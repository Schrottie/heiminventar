<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Ungültige Anforderungsmethode']);
    exit;
}

$logId = (int)($_POST['log_id'] ?? 0);

if ($logId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Log-ID']);
    exit;
}

try {
    // Log-Eintrag laden
    $stmt = $pdo->prepare("SELECT * FROM inventory_logs WHERE id = ?");
    $stmt->execute([$logId]);
    $log = $stmt->fetch();

    if (!$log) {
        echo json_encode(['success' => false, 'error' => 'Log-Eintrag nicht gefunden']);
        exit;
    }

    $itemId = (int)$log['item_id'];

    // Prüfen, ob Artikel noch existiert
    $stmtItem = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
    $stmtItem->execute([$itemId]);
    $item = $stmtItem->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Der zugehörige Artikel existiert nicht mehr.']);
        exit;
    }

    // Rollback je nach Aktionstyp durchführen
    if ($log['action'] === 'relocated') {
        $oldLocId = !empty($log['old_value']) ? (int)$log['old_value'] : null;
        
        $upd = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE id = ?");
        $upd->execute([$oldLocId, $itemId]);

        logInventoryAction($pdo, $itemId, $item['name'], 'relocated', 0, (int)$item['quantity'], 'Rollback: Standort zurückgesetzt', 'location_id', $log['new_value'], $log['old_value']);

    } elseif ($log['action'] === 'quantity_changed') {
        $qtyDiff = (int)$log['qty_change'];
        $newQty = max(0, (int)$item['quantity'] - $qtyDiff);

        $upd = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ?");
        $upd->execute([$newQty, $itemId]);

        logInventoryAction($pdo, $itemId, $item['name'], 'quantity_changed', -$qtyDiff, $newQty, 'Rollback: Bestand zurückgesetzt', 'quantity', (string)$item['quantity'], (string)$newQty);

    } elseif ($log['action'] === 'updated' && !empty($log['field_changed'])) {
        $field = $log['field_changed'];
        $allowedFields = ['name', 'description', 'min_quantity'];

        if (in_array($field, $allowedFields)) {
            $upd = $pdo->prepare("UPDATE inventory_items SET {$field} = ? WHERE id = ?");
            $upd->execute([$log['old_value'], $itemId]);

            logInventoryAction($pdo, $itemId, $item['name'], 'updated', 0, (int)$item['quantity'], "Rollback: {$field} zurückgesetzt", $field, $log['new_value'], $log['old_value']);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
