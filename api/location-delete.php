<?php

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Für logInventoryAction()

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Ungültige ID');
}

/* =========================================
   1. Lagerort-Details abrufen
   ========================================= */

$stmtLoc = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
$stmtLoc->execute([$id]);
$locationName = $stmtLoc->fetchColumn();

if (!$locationName) {
    // Lagerort existiert bereits nicht mehr
    header('Location: ../locations.php');
    exit;
}

/* =========================================
   2. Betroffene Artikel ermitteln & entkoppeln
   ========================================= */

// Alle Artikel laden, die sich aktuell an diesem Lagerort befinden
$stmtItems = $pdo->prepare("
    SELECT id, name, quantity 
    FROM inventory_items 
    WHERE location_id = ?
");
$stmtItems->execute([$id]);
$affectedItems = $stmtItems->fetchAll();

if (!empty($affectedItems)) {
    // Standort bei den betroffenen Artikeln auf NULL zurücksetzen
    $stmtReset = $pdo->prepare("UPDATE inventory_items SET location_id = NULL WHERE location_id = ?");
    $stmtReset->execute([$id]);

    // Für jeden betroffenen Artikel einen Protokolleintrag schreiben
    foreach ($affectedItems as $item) {
        logInventoryAction(
            $pdo,
            (int)$item['id'],
            $item['name'],
            'updated',
            0,
            (int)$item['quantity'],
            'Lagerort "' . $locationName . '" wurde gelöscht (Standort auf unzugewiesen zurückgesetzt)'
        );
    }
}

/* =========================================
   3. Lagerort aus DB löschen
   ========================================= */

$stmt = $pdo->prepare("
    DELETE FROM locations
    WHERE id = ?
");

$stmt->execute([$id]);

/* =========================================
   4. Zurück zur Lagerort-Übersicht
   ========================================= */

header('Location: ../locations.php');
exit;
