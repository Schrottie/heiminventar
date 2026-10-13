<?php
session_start();
require_once __DIR__ . '/../cfg/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

if (!$id || empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name oder ID fehlt']);
    exit;
}

if ($parentId === $id) {
    echo json_encode(['success' => false, 'error' => 'Ein Lagerort kann nicht übergeordnet zu sich selbst sein']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE locations SET name = ?, parent_id = ? WHERE id = ?");
    $stmt->execute([$name, $parentId, $id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
