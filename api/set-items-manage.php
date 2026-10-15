<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$itemId = (int)($_POST['item_id'] ?? $_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Artikel-ID']);
    exit;
}

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT id, name, quantity, sort_order FROM item_set_items WHERE item_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$itemId]);
    echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Name darf nicht leer sein']);
        exit;
    }

    // Höchsten Sortierindex ermitteln
    $stmtMax = $pdo->prepare("SELECT MAX(sort_order) FROM item_set_items WHERE item_id = ?");
    $stmtMax->execute([$itemId]);
    $maxSort = (int)$stmtMax->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO item_set_items (item_id, name, quantity, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$itemId, $name, $quantity, $maxSort + 1]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($action === 'delete') {
    $setId = (int)($_POST['set_item_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM item_set_items WHERE id = ? AND item_id = ?");
    $stmt->execute([$setId, $itemId]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'reorder') {
    $order = $_POST['order'] ?? []; // Array von IDs in neuer Reihenfolge
    if (is_array($order)) {
        $stmt = $pdo->prepare("UPDATE item_set_items SET sort_order = ? WHERE id = ? AND item_id = ?");
        foreach ($order as $index => $setId) {
            $stmt->execute([$index, (int)$setId, $itemId]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
