<?php
session_start();
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Für logInventoryAction

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['id'] ?? $_POST['id'] ?? null;

if (!$itemId) {
    echo json_encode(['success' => false, 'error' => 'Keine Item-ID übergeben']);
    exit;
}

try {
    // 1. Aktuelle Item-Daten für das Logging abfragen (Name, etc.)
    $stmt = $pdo->prepare("SELECT id, name, is_archived FROM inventory_items WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Gegenstand nicht gefunden']);
        exit;
    }

    // Wenn er bereits archiviert ist, müssen wir nichts tun
    if ((int)$item['is_archived'] === 1) {
        echo json_encode(['success' => true]);
        exit;
    }

    // 2. Gegenstand als archiviert markieren und location_id auf NULL setzen
    $updateStmt = $pdo->prepare("UPDATE inventory_items SET is_archived = 1, archived_at = NOW(), location_id = NULL WHERE id = ?");
    $updateStmt->execute([$itemId]);

    // 3. Benutzer ermitteln (Fallback auf 'System', falls Session leer ist)
    $userName = $_SESSION['user_name'] ?? $_SESSION['user']['username'] ?? 'System';

    // 4. Aktion in den Logs protokollieren
    logInventoryAction(
        $pdo,
        (int)$itemId,
        $item['name'],
        'item_archived',          // Aktion
        0,                  // qtyChange
        0,                  // newQty
        'Gegenstand wurde ins Archiv verschoben', // Details
        'is_archived',      // fieldChanged
        '0',                // oldValue
        '1',                // newValue
        $userName           // userName
    );

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
