<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$unitId = (int)($_POST['unit_id'] ?? 0);
$itemId = (int)($_POST['item_id'] ?? 0);
$borrower = trim($_POST['borrower_name'] ?? '');
$lentDate = $_POST['lent_date'] ?? date('Y-m-d');
$notes = trim($_POST['notes'] ?? '');

if ($unitId <= 0 || $itemId <= 0 || empty($borrower)) {
    header("Location: ../item.php?id=" . $itemId . "&error=missing_data");
    exit;
}

// Einheit aktualisieren
$stmt = $pdo->prepare("UPDATE item_units SET status = 'lent', borrower_name = ?, lent_date = ?, notes = ? WHERE id = ? AND item_id = ?");
$stmt->execute([$borrower, $lentDate, $notes, $unitId, $itemId]);

logInventoryAction($pdo, $itemId, 'Verleih', 'updated', 0, 0, "Exemplar verliehen an: " . $borrower);

header("Location: ../item.php?id=" . $itemId . "&success=1");
exit;