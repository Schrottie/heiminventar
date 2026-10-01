<?php

// Datenbankverbindung einbinden
require_once __DIR__ . '/../cfg/db.php';

// --- Eingabedaten verarbeiten und bereinigen ---
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);

// Ort-ID verarbeiten: Ungültige/leere Eingaben sauber zu null oder (int) casten
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

// --- Validierung ---
if ($name === '') {
    die('Bezeichnung fehlt');
}

// --- Neuen Gegenstand in die Datenbank einfügen ---
$stmt = $pdo->prepare("
    INSERT INTO inventory_items (name, description, quantity, location_id)
    VALUES (:name, :description, :quantity, :location_id)
");

$stmt->execute([
    ':name'        => $name,
    ':description' => $description,
    ':quantity'    => $quantity,
    ':location_id' => $locationId
]);

$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_next') {
    // Leeres Formular erneut aufrufen (ggf. mit Erfolgsmeldung)
    header('Location: ../edit-item.php?status=success');
} else {
    // Zur Übersicht oder Detailseite weiterleiten
    header('Location: ../index.php?status=success');
}
exit;