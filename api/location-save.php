<?php

// Datenbankverbindung einbinden
require_once __DIR__ . '/../cfg/db.php';

// --- Eingabedaten verarbeiten ---
$name = trim($_POST['name'] ?? '');

// Vater-ID verarbeiten: Leere Werte (z.B. selektiertes "Keine") auf null setzen
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

// --- Validierung ---
if ($name === '') {
    die('Name fehlt');
}

// --- Neuen Ort/Kategorie in die Datenbank einfügen ---
$stmt = $pdo->prepare("
    INSERT INTO locations (parent_id, name)
    VALUES (:parent_id, :name)
");

$stmt->execute([
    ':parent_id' => $parentId,
    ':name'      => $name
]);

// --- Weiterleitung zur Übersicht ---
header('Location: ../locations.php');
exit;
