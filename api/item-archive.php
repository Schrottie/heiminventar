<?php
session_start();
require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['id'] ?? $_POST['id'] ?? null;

if (!$itemId) {
    echo json_encode(['success' => false, 'error' => 'Keine Item-ID übergeben']);
    exit;
}

try {
    // Gegenstand als archiviert markieren und optional location_id auf NULL setzen
    $stmt = $pdo->prepare("UPDATE inventory_items SET is_archived = 1, archived_at = NOW(), location_id = NULL WHERE id = ?");
    $stmt->execute([$itemId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}