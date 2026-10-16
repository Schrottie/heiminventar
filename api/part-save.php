<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

session_start();

$itemId = (int)($_POST['item_id'] ?? 0);
$partId = !empty($_POST['part_id']) ? (int)$_POST['part_id'] : 0;
$name = trim($_POST['name'] ?? '');
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$price = !empty($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : null;
$invoiceStatus = $_POST['invoice_status'] ?? 'own'; // 'own', 'parent', 'none'

if ($itemId <= 0 || empty($name)) {
    die('Ungültige Daten übergeben.');
}

// Prüfung: Wenn "Auf der Hauptrechnung enthalten" gewählt wurde, prüfen ob der Hauptgegenstand Dokumente hat
if ($invoiceStatus === 'parent') {
    $stmtDocCheck = $pdo->prepare("SELECT COUNT(*) FROM item_documents WHERE item_id = ?");
    $stmtDocCheck->execute([$itemId]);
    if ($stmtDocCheck->fetchColumn() == 0) {
        header("Location: ../item.php?id=" . $itemId . "&error=no_main_invoice");
        exit;
    }
}

$invoiceFilename = null;

// Rechnungs-Upload verarbeiten, falls eigene Rechnung gewählt und Datei hochgeladen wurde
if ($invoiceStatus === 'own' && !empty($_FILES['invoice_file']['name'])) {
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

$userName = $_SESSION['user_name'] ?? $_SESSION['user']['username'] ?? 'System';

if ($partId > 0) {
    // --- UPDATE BESTEHENDES ANBAUTEIL ---
    // Wenn keine neue Datei hochgeladen wurde, die alte beibehalten
    if ($invoiceStatus === 'own' && !$invoiceFilename) {
        $stmtOldFile = $pdo->prepare("SELECT invoice_filename FROM item_parts WHERE id = ?");
        $stmtOldFile->execute([$partId]);
        $invoiceFilename = $stmtOldFile->fetchColumn();
    } elseif ($invoiceStatus !== 'own') {
        $invoiceFilename = null; // Wenn nicht "eigene Rechnung", alte Datei verwerfen
    }

    $stmt = $pdo->prepare("
        UPDATE item_parts 
        SET name = ?, quantity = ?, price = ?, invoice_status = ?, invoice_filename = ?
        WHERE id = ? AND item_id = ?
    ");
    $stmt->execute([$name, $quantity, $price, $invoiceStatus, $invoiceFilename, $partId, $itemId]);

    logInventoryAction($pdo, $itemId, $name, 'updated', 0, 0, 'Anbauteil bearbeitet: ' . $name, 'parts', '', $name, $userName);
    $successCode = 'part_updated';
} else {
    // --- NEU ERSTELLEN ---
    $stmt = $pdo->prepare("
        INSERT INTO item_parts (item_id, name, quantity, price, invoice_status, invoice_filename) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$itemId, $name, $quantity, $price, $invoiceStatus, $invoiceFilename]);

    logInventoryAction($pdo, $itemId, $name, 'updated', 0, 0, 'Anbauteil hinzugefügt: ' . $name, 'parts', '', $name, $userName);
    $successCode = 'part_added';
}

header("Location: ../item.php?id=" . $itemId . "&success=" . $successCode);
exit;