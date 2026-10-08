<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Für logInventoryAction()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Ungültige Anforderungsmethode']);
    exit;
}

// JSON-Body auslesen
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$itemId = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$locationId = isset($data['location_id']) ? (int)$data['location_id'] : 0;

if ($itemId <= 0 || $locationId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige IDs übergeben']);
    exit;
}

try {
    // 1. Prüfen, ob Ziel-Standort existiert & Name holen
    $stmtLoc = $pdo->prepare("SELECT id, name FROM locations WHERE id = ?");
    $stmtLoc->execute([$locationId]);
    $newLoc = $stmtLoc->fetch();

    if (!$newLoc) {
        echo json_encode(['success' => false, 'error' => 'Ziel-Lagerort existiert nicht']);
        exit;
    }

    // 2. Gegenstand inkl. altem Standortnamen und aktuellem Bestand abfragen
    $stmtItem = $pdo->prepare("
        SELECT i.id, i.name, i.quantity, i.location_id, l.name AS old_location_name
        FROM inventory_items i
        LEFT JOIN locations l ON l.id = i.location_id
        WHERE i.id = ?
    ");
    $stmtItem->execute([$itemId]);
    $item = $stmtItem->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Gegenstand nicht gefunden']);
        exit;
    }

    // Prüfen, ob sich der Standort überhaupt geändert hat
    if ((int)$item['location_id'] === $locationId) {
        echo json_encode(['success' => true, 'message' => 'Keine Änderung erforderlich']);
        exit;
    }

    $oldLocationId = $item['location_id'] !== null ? (string)$item['location_id'] : null;
    $newLocationIdStr = (string)$locationId;

    // 3. Standort des Gegenstands aktualisieren
    $stmt = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE id = ?");
    $stmt->execute([$locationId, $itemId]);

    if ($stmt->rowCount() > 0) {
        // 4. Strukturierter Protokolleintrag (An neue Signatur angepasst)
        $oldLocName = $item['old_location_name'] ?? 'Kein Standort';
        $newLocName = $newLoc['name'];
        $detailText = "Umgelagert von \"{$oldLocName}\" nach \"{$newLocName}\"";

        logInventoryAction(
            $pdo,
            $itemId,
            $item['name'],
            'relocated',
            0,
            (int)$item['quantity'],
            $detailText,
            'location_id',       // 8. Param: field_changed
            $oldLocationId,      // 9. Param: old_value (Alte Standort-ID als String/Null)
            $newLocationIdStr    // 10. Param: new_value (Neue Standort-ID als String)
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Keine Änderung erforderlich']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
