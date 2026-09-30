<?php

require_once __DIR__ . '/../cfg/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ungültige ID');
}

$stmt = $pdo->prepare("
    DELETE FROM locations
    WHERE id = ?
");

$stmt->execute([$id]);

header('Location: ../locations.php');
exit;