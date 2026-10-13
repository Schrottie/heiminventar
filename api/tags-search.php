<?php
require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM tags 
        WHERE name LIKE ? 
        ORDER BY name ASC 
        LIMIT 10
    ");
    $stmt->execute(['%' . $query . '%']);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tags);
} catch (Exception $e) {
    echo json_encode([]);
}