<?php

require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anforderung']);
    exit;
}

$sourceId = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
$targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$mode = $_POST['include_sublocations'] ?? 'items_only';

if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
    echo json_encode(['success' => false, 'message' => 'Quell- und Zielort müssen unterschiedlich und gültig sein.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Hilfsfunktion: Rekursiv alle untergeordneten Standort-IDs ermitteln
    function getSubtreeLocationIds(PDO $pdo, int $parentId): array {
        $ids = [];
        $stmt = $pdo->prepare("SELECT id FROM locations WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($children as $childId) {
            $ids[] = (int)$childId;
            $ids = array_merge($ids, getSubtreeLocationIds($pdo, (int)$childId));
        }
        return $ids;
    }

    if ($mode === 'move_all') {
        // Option 1: Gegenstände des Quellorts verschieben UND Unterlagerorte samt Inhalt unter Zielort hängen
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id = ?");
        $stmtItems->execute([$targetId, $sourceId]);

        $stmtSub = $pdo->prepare("UPDATE locations SET parent_id = ? WHERE parent_id = ?");
        $stmtSub->execute([$targetId, $sourceId]);

    } elseif ($mode === 'items_with_sub') {
        // Option 2: Gegenstände des Quellorts UND aller Unterlagerorte in den Zielort verschieben (Standortstruktur bleibt erhalten)
        $subIds = getSubtreeLocationIds($pdo, $sourceId);
        $allLocationIds = array_merge([$sourceId], $subIds);

        $inClause = implode(',', array_fill(0, count($allLocationIds), '?'));
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id IN ($inClause)");
        
        $params = array_merge([$targetId], $allLocationIds);
        $stmtItems->execute($params);

    } else {
        // Option 3 (Standard / items_only): Nur Gegenstände direkt im Quellort verschieben
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id = ?");
        $stmtItems->execute([$targetId, $sourceId]);
    }

    $pdo->commit();

    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
