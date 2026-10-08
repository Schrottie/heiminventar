<?php

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Für logInventoryAction()

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

    // 1. Namen des Quell- und Zielstandorts für die Protokollierung abfragen
    $stmtLoc = $pdo->prepare("SELECT id, name FROM locations WHERE id IN (?, ?)");
    $stmtLoc->execute([$sourceId, $targetId]);
    $locations = $stmtLoc->fetchAll(PDO::FETCH_KEY_PAIR);

    $sourceName = $locations[$sourceId] ?? 'Unbekannter Quellort';
    $targetName = $locations[$targetId] ?? 'Unbekannter Zielort';

    // 2. Zu verschiebende Standort-IDs je nach Modus ermitteln
    $locationIdsToProcess = [];
    if ($mode === 'items_with_sub') {
        $subIds = getSubtreeLocationIds($pdo, $sourceId);
        $locationIdsToProcess = array_merge([$sourceId], $subIds);
    } else {
        $locationIdsToProcess = [$sourceId];
    }

    // 3. Alle betroffenen Gegenstände samt aktuellem Standortnamen vorab laden
    $inClause = implode(',', array_fill(0, count($locationIdsToProcess), '?'));
    $stmtItemsToMove = $pdo->prepare("
        SELECT i.id, i.name, i.quantity, i.location_id, l.name AS current_location_name
        FROM inventory_items i
        LEFT JOIN locations l ON l.id = i.location_id
        WHERE i.location_id IN ($inClause)
    ");
    $stmtItemsToMove->execute($locationIdsToProcess);
    $affectedItems = $stmtItemsToMove->fetchAll();

    // 4. Verschiebung ausführen
    if ($mode === 'move_all') {
        // Option 1: Gegenstände des Quellorts verschieben UND Unterlagerorte samt Inhalt unter Zielort hängen
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id = ?");
        $stmtItems->execute([$targetId, $sourceId]);

        $stmtSub = $pdo->prepare("UPDATE locations SET parent_id = ? WHERE parent_id = ?");
        $stmtSub->execute([$targetId, $sourceId]);

    } elseif ($mode === 'items_with_sub') {
        // Option 2: Gegenstände des Quellorts UND aller Unterlagerorte in den Zielort verschieben
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id IN ($inClause)");
        $params = array_merge([$targetId], $locationIdsToProcess);
        $stmtItems->execute($params);

    } else {
        // Option 3 (Standard / items_only): Nur Gegenstände direkt im Quellort verschieben
        $stmtItems = $pdo->prepare("UPDATE inventory_items SET location_id = ? WHERE location_id = ?");
        $stmtItems->execute([$targetId, $sourceId]);
    }

    // 5. Protokolleinträge für jeden verschobenen Gegenstand schreiben
    foreach ($affectedItems as $item) {
        $fromLoc = $item['current_location_name'] ?? $sourceName;
        $detailText = "Umgelagert von \"{$fromLoc}\" nach \"{$targetName}\" (Massenumlagerung)";

        logInventoryAction(
            $pdo,
            (int)$item['id'],
            $item['name'],
            'updated',
            0,
            (int)$item['quantity'],
            $detailText
        );
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
