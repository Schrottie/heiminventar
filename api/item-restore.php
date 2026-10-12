<?php
session_start();
require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['id'] ?? null;
$locationId = $input['location_id'] ?? null;

if (!$itemId || !$locationId) {
    echo json_encode(['success' => false, 'error' => 'Item-ID oder Lagerort fehlt']);
    exit;
}

try {
    // Aus dem Archiv holen und neuen Lagerort zuweisen
    $stmt = $pdo->prepare("UPDATE inventory_items SET is_archived = 0, archived_at = NULL, location_id = ? WHERE id = ?");
    $stmt->execute([$locationId, $itemId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
