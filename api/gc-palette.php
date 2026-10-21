<?php
require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Gegenstände durchsuchen
$stmt = $pdo->prepare("SELECT ii.id, ii.name, ii.location_id FROM inventory_items ii 
LEFT JOIN item_tags it ON it.item_id = ii.id 
LEFT JOIN tags t on t.id = it.tag_id
WHERE ii.name LIKE ? OR ii.description LIKE ? OR t.name LIKE ? LIMIT 5");
$searchTerm = "%{$q}%";
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
while ($row = $stmt->fetch()) {
    $results[] = [
        'title' => $row['name'],
        'subtitle' => 'Gegenstand',
        'icon' => 'fa-box text-primary',
        'url' => 'item.php?id=' . $row['id']
    ];
}

// 2. Lagerorte durchsuchen
$stmt = $pdo->prepare("SELECT id, name FROM locations WHERE name LIKE ? LIMIT 5");
$stmt->execute([$searchTerm]);
while ($row = $stmt->fetch()) {
    $results[] = [
        'title' => $row['name'],
        'subtitle' => 'Lagerort',
        'icon' => 'fa-folder-tree text-warning',
        'url' => 'locations.php?id=' . $row['id']
    ];
}

echo json_encode($results);
