<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$unitId = (int)($_GET['unit_id'] ?? 0);
$itemId = (int)($_GET['item_id'] ?? 0);

if ($unitId <= 0 || $itemId <= 0) {
    header("Location: ../index.php");
    exit;
}

// Einheit auf 'available' zurücksetzen
$stmt = $pdo->prepare("UPDATE item_units SET status = 'available', borrower_name = NULL, lent_date = NULL, notes = NULL WHERE id = ? AND item_id = ?");
$stmt->execute([$unitId, $itemId]);

logInventoryAction($pdo, $itemId, 'Verleih', 'updated', 0, 0, "Exemplar ins Lager zurückgebracht");

header("Location: ../item.php?id=" . $itemId . "&success=1");
exit;