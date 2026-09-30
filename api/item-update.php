<?php

// Datenbankverbindung einbinden
require_once __DIR__ . '/../cfg/db.php';

// --- Eingabedaten verarbeiten und bereinigen ---
$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

// --- Validierung ---
if ($id <= 0) {
    die('Ungültige ID');
}
if ($name === '') {
    die('Bezeichnung fehlt');
}

// --- Datensatz in der Datenbank aktualisieren ---
$stmt = $pdo->prepare("
    UPDATE inventory_items
    SET name = :name, description = :description, quantity = :quantity, location_id = :location_id
    WHERE id = :id
");

$stmt->execute([
    ':name' => $name,
    ':description' => $description,
    ':quantity' => $quantity,
    ':location_id' => $locationId,
    ':id' => $id
]);

// --- Bilder-Upload verarbeiten ---
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $uploadDir = __DIR__ . '/../img/items/';

    // Zielverzeichnis erstellen, falls es nicht existiert
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Prepared Statement für den Bildupload im Voraus vorbereiten
    $stmtImage = $pdo->prepare("
        INSERT INTO item_images (item_id, filename)
        VALUES (:item_id, :filename)
    ");

    foreach ($_FILES['images']['name'] as $index => $originalName) {
        // Fehlerhafte Uploads überspringen
        if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        // Dateiendung prüfen
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        // Eindeutigen Dateinamen generieren und Datei verschieben
        $filename = uniqid('item_', true) . '.' . $extension;
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['images']['tmp_name'][$index], $targetFile)) {
            // Bild in der Datenbank speichern
            $stmtImage->execute([
                ':item_id' => $id,
                ':filename' => $filename
            ]);
        }
    }
}

// --- Weiterleitung zum bearbeiteten Gegenstand ---
header('Location: ../item.php?id=' . $id);
exit;
