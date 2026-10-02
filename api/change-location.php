<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../cfg/db.php';

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
    // Prüfen, ob Standort existiert
    $stmtLoc = $pdo->prepare("SELECT id FROM locations WHERE id = ?");
    $stmtLoc->execute([$locationId]);
    if (!$stmtLoc->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ziel-Lagerort existiert nicht']);
        exit;
    }

    // Standort des Gegenstands aktualisieren
    $stmt = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE id = ?");
    $stmt->execute([$locationId, $itemId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // Falls itemId nicht existiert oder sich die location_id gar nicht geändert hat
        echo json_encode(['success' => true, 'message' => 'Keine Änderung erforderlich']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
