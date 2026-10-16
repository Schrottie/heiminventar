<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

session_start();

$itemId = (int)($_POST['item_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$price = !empty($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : null;
$invoiceType = $_POST['invoice_type'] ?? 'own'; // 'own' oder 'parent'

if ($itemId <= 0 || empty($name)) {
    die('Ungültige Daten übergeben.');
}

$hasOwnInvoice = ($invoiceType === 'own') ? 1 : 0;

// NEU: Wenn "Auf der Hauptrechnung enthalten" gewählt wurde, prüfen ob der Hauptgegenstand Dokumente hat
if ($hasOwnInvoice === 0) {
    $stmtDocCheck = $pdo->prepare("SELECT COUNT(*) FROM item_documents WHERE item_id = ?");
    $stmtDocCheck->execute([$itemId]);
    if ($stmtDocCheck->fetchColumn() == 0) {
        // Keine Hauptrechnung/Dokumente vorhanden -> Abbrechen und zurück zur Detailseite mit Fehler
        header("Location: ../item.php?id=" . $itemId . "&error=no_main_invoice");
        exit;
    }
}

$invoiceFilename = null;

// Rechnungs-Upload verarbeiten, falls eigene Rechnung gewählt wurde
if ($hasOwnInvoice === 1 && !empty($_FILES['invoice_file']['name'])) {
    if ($_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
        $docDir = __DIR__ . '/../docs/items/';
        ensureDirectoryExists($docDir);

        $ext = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowedExts)) {
            $invoiceFilename = 'part_inv_' . $itemId . '_' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['invoice_file']['tmp_name'], $docDir . $invoiceFilename);
        }
    }
}

// In Datenbank speichern
$stmt = $pdo->prepare("
    INSERT INTO item_parts (item_id, name, quantity, price, has_own_invoice, invoice_filename) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$itemId, $name, $quantity, $price, $hasOwnInvoice, $invoiceFilename]);

// Protokollieren
$userName = $_SESSION['user_name'] ?? $_SESSION['user']['username'] ?? 'System';
logInventoryAction(
    $pdo,
    $itemId,
    $name,
    'updated',
    0,
    0,
    'Anbauteil hinzugefügt: ' . $name . ' (' . number_format($price ?? 0, 2, ',', '.') . ' €)',
    'parts',
    '',
    $name,
    $userName
);

header("Location: ../item.php?id=" . $itemId . "&success=part_added");
exit;
