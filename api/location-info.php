<?php

require_once __DIR__ . '/../cfg/db.php';

$id = (int)($_GET['id'] ?? 0);

function getChildren(array $locations, int $parentId): array
{
    $result = [];

    foreach ($locations as $location) {

        if ((int)$location['parent_id'] === $parentId) {

            $result[] = (int)$location['id'];

            $result = array_merge(
                $result,
                getChildren($locations, (int)$location['id'])
            );
        }
    }

    return $result;
}

$stmt = $pdo->query("
    SELECT id, parent_id
    FROM locations
");

$locations = $stmt->fetchAll();

$children = getChildren($locations, $id);

$locationIds = array_merge([$id], $children);

$placeholders = implode(',', array_fill(0, count($locationIds), '?'));

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM inventory_items
    WHERE location_id IN ($placeholders)
");

$stmt->execute($locationIds);

$itemCount = (int)$stmt->fetchColumn();

echo json_encode([
    'locations' => count($children),
    'items' => $itemCount
]);