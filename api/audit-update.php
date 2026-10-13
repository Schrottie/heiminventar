<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// JSON-Input einlesen
$input = json_decode(file_get_contents('php://input'), true);

$itemId = (int)($input['id'] ?? 0);
$newQuantity = isset($input['quantity']) ? (int)$input['quantity'] : null;

if ($itemId <= 0 || $newQuantity === null || $newQuantity < 0) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Daten']);
    exit;
}

// Alten Bestand und Namen für das Log ermitteln
$stmtOld = $pdo->prepare("SELECT name, quantity FROM inventory_items WHERE id = ?");
$stmtOld->execute([$itemId]);
$oldItem = $stmtOld->fetch();

if (!$oldItem) {
    echo json_encode(['success' => false, 'error' => 'Gegenstand nicht gefunden']);
    exit;
}

$oldQty = (int)$oldItem['quantity'];
$qtyChange = $newQuantity - $oldQty;

// Datenbank aktualisieren
$stmtUpdate = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ?");
$stmtUpdate->execute([$newQuantity, $itemId]);

// Protokollieren, falls sich die Menge geändert hat
if ($qtyChange !== 0) {
    logInventoryAction(
        $pdo,
        $itemId,
        $oldItem['name'],
        'quantity_changed',
        $qtyChange,
        $newQuantity,
        'Bestand im Inventur-Modus angepasst',
        'quantity',
        (string)$oldQty,
        (string)$newQuantity
    );
}

echo json_encode(['success' => true]);
