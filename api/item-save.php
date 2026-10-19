<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    die('Bezeichnung darf nicht leer sein.');
}

$manufacturer = trim($_POST['manufacturer'] ?? '') !== '' ? trim($_POST['manufacturer']) : null;
$description = trim($_POST['description'] ?? '') !== '' ? trim($_POST['description']) : null;
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

// Standardwerte für neue Gegenstände
$quantity = 1;
$minQuantity = 0;
$isBulk = 0;

try {
    // In die Datenbank einfügen
    $stmt = $pdo->prepare("INSERT INTO inventory_items 
        (name, manufacturer, description, location_id, quantity, min_quantity, is_bulk, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $name,
        $manufacturer,
        $description,
        $locationId,
        $quantity,
        $minQuantity,
        $isBulk
    ]);

    $newItemId = (int)$pdo->lastInsertId();

    // Einheiten synchronisieren, falls die Funktion existiert
    if (function_exists('syncItemUnits')) {
        syncItemUnits($pdo, $newItemId, $quantity);
    }

    // Protokollieren
    if (function_exists('logInventoryAction')) {
        logInventoryAction($pdo, $newItemId, $name, 'created', $quantity, $quantity, 'Gegenstand angelegt');
    }

    // Weiterleitung basierend auf dem Button
    $action = $_POST['action'] ?? 'save';
    if ($action === 'save_and_next') {
        header("Location: ../item-add.php?success=1");
    } else {
        // Direkt zur Detailseite des neuen Artikels springen, um dort weitere Angaben zu machen
        header("Location: ../item.php?id=" . $newItemId . "&success=1");
    }
    exit;

} catch (PDOException $e) {
    die('Fehler beim Speichern des Gegenstands: ' . htmlspecialchars($e->getMessage()));
}
