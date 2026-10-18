<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Erfasst 'id' sowie 'item_id' sowohl aus POST als auch GET
$itemId = (int)($_POST['id'] ?? $_POST['item_id'] ?? $_GET['id'] ?? $_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    die('Ungültige ID');
}

// 0. Vorherigen Zustand des Artikels für Vergleich und Logging abfragen
$stmtOld = $pdo->prepare("SELECT name, description, quantity, min_quantity, location_id, price, loan_to, loan_date, purchase_date, purchase_location, manufacturer, warranty_months, is_insured, is_set, is_bulk, expiration_date, maintenance_interval_months, last_maintenance_date FROM inventory_items WHERE id = ?");
$stmtOld->execute([$itemId]);
$oldItem = $stmtOld->fetch();

if (!$oldItem) {
    die('Artikel nicht gefunden');
}

// 1. Nur Hauptbild ändern (falls Stern geklickt wurde)
if (isset($_POST['action_set_main_image']) || isset($_GET['action_set_main_image'])) {
    $imageId = (int)($_REQUEST['image_id'] ?? 0);
    if ($imageId > 0) {
        $pdo->prepare("UPDATE item_images SET is_main = 0 WHERE item_id = ?")->execute([$itemId]);
        $pdo->prepare("UPDATE item_images SET is_main = 1 WHERE id = ? AND item_id = ?")->execute([$imageId, $itemId]);
       
        if (function_exists('logInventoryAction')) {
            logInventoryAction($pdo, $itemId, $oldItem['name'], 'updated', 0, (int)$oldItem['quantity'], 'Hauptbild geändert');
        }
    }
    header("Location: ../item.php?id=" . $itemId);
    exit;
}

// 2. Stammdaten & Checkboxen auswerten
$hasNameField = isset($_POST['name']);

$name = $hasNameField ? trim($_POST['name']) : $oldItem['name'];
$description = isset($_POST['description']) ? trim($_POST['description']) : $oldItem['description'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (int)$oldItem['quantity'];
$minQuantity = isset($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : (int)$oldItem['min_quantity'];
$locationId = isset($_POST['location_id']) ? (!empty($_POST['location_id']) ? (int)$_POST['location_id'] : null) : $oldItem['location_id'];

// Finanz- & Anschaffungsdaten
$price = isset($_POST['price']) ? (!empty($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : null) : $oldItem['price'];
$purchaseDate = isset($_POST['purchase_date']) ? (!empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null) : $oldItem['purchase_date'];
$purchaseLocation = isset($_POST['purchase_location']) ? (trim($_POST['purchase_location']) !== '' ? trim($_POST['purchase_location']) : null) : $oldItem['purchase_location'];
$manufacturer = isset($_POST['manufacturer']) ? (trim($_POST['manufacturer']) !== '' ? trim($_POST['manufacturer']) : null) : $oldItem['manufacturer'];
$warrantyMonths = isset($_POST['warranty_months']) ? (!empty($_POST['warranty_months']) ? (int)$_POST['warranty_months'] : null) : $oldItem['warranty_months'];

// Wichtig: Checkboxen sauber abfangen (setzen 1 wenn da, sonst 0)
$isInsured = isset($_POST['is_insured']) ? 1 : 0;
$isSet = isset($_POST['is_set']) ? 1 : 0;
$isBulk = isset($_POST['is_bulk']) ? 1 : 0;

// Wartung & Ablauf
$expirationDate = isset($_POST['expiration_date']) ? (!empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null) : $oldItem['expiration_date'];
$maintenanceInterval = isset($_POST['maintenance_interval_months']) ? (!empty($_POST['maintenance_interval_months']) ? (int)$_POST['maintenance_interval_months'] : null) : $oldItem['maintenance_interval_months'];
$lastMaintenanceDate = isset($_POST['last_maintenance_date']) ? (!empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null) : $oldItem['last_maintenance_date'];

// Verleih-Daten
$loanTo = isset($_POST['loan_to']) ? (trim($_POST['loan_to']) !== '' ? trim($_POST['loan_to']) : null) : $oldItem['loan_to'];
$loanDate = isset($_POST['loan_date']) ? (!empty($_POST['loan_date']) ? $_POST['loan_date'] : null) : $oldItem['loan_date'];
$onLoan = ($loanTo !== null) ? 1 : 0;

if ($hasNameField && $name === '') {
    die('Name darf nicht leer sein');
}

if ($hasNameField) {
    // SQL-Update mit ALLEN Flags (inkl. is_set und is_bulk)
    $stmt = $pdo->prepare("UPDATE inventory_items SET
        name = ?, description = ?, quantity = ?, min_quantity = ?, location_id = ?,
        expiration_date = ?, price = ?, on_loan = ?, loan_to = ?, loan_date = ?,
        purchase_date = ?, purchase_location = ?, manufacturer = ?, warranty_months = ?, 
        is_insured = ?, is_set = ?, is_bulk = ?,
        maintenance_interval_months = ?, last_maintenance_date = ?
        WHERE id = ?");

    $stmt->execute([
        $name, $description, $quantity, $minQuantity, $locationId,
        $expirationDate, $price, $onLoan, $loanTo, $loanDate,
        $purchaseDate, $purchaseLocation, $manufacturer, $warrantyMonths, 
        $isInsured, $isSet, $isBulk,
        $maintenanceInterval, $lastMaintenanceDate, $itemId
    ]);

    if (function_exists('syncItemUnits')) {
        syncItemUnits($pdo, $itemId, $quantity);
    }

    if (function_exists('logInventoryAction')) {
        logInventoryAction($pdo, $itemId, $name, 'updated', 0, $quantity, 'Gegenstand bearbeitet');
    }
}

// 3. Bilder-Upload verarbeiten
if (!empty($_FILES['images']['name'][0])) {
    $imgDir = __DIR__ . '/../img/items/';
    if (!is_dir($imgDir)) {
        mkdir($imgDir, 0777, true);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM item_images WHERE item_id = ? AND is_main = 1");
    $stmt->execute([$itemId]);
    $hasMainImage = $stmt->fetchColumn() > 0;

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $newFilename = 'item_' . $itemId . '_' . time() . '_' . uniqid() . '.' . $ext;
               
                if (move_uploaded_file($tmpName, $imgDir . $newFilename)) {
                    $isMain = !$hasMainImage ? 1 : 0;
                    $hasMainImage = true;

                    $stmt = $pdo->prepare("INSERT INTO item_images (item_id, filename, is_main) VALUES (?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $isMain]);
                }
            }
        }
    }
}

// 4. Dokumenten-Upload verarbeiten (Robuster gemacht)
if (!empty($_FILES['documents']['name'][0])) {
    $docDir = __DIR__ . '/../docs/items/';
    if (!is_dir($docDir)) {
        mkdir($docDir, 0777, true);
    }

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $originalName = $_FILES['documents']['name'][$key];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
           
            $allowedExts = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];
            if (in_array($ext, $allowedExts)) {
                $newFilename = 'doc_' . $itemId . '_' . time() . '_' . uniqid() . '.' . $ext;
               
                if (move_uploaded_file($tmpName, $docDir . $newFilename)) {
                    $docTitle = pathinfo($originalName, PATHINFO_FILENAME);
                    $docType = $_POST['doc_type'] ?? 'sonstiges';
                    if (!in_array($docType, ['rechnung', 'bedienungsanleitung', 'sonstiges'])) {
                        $docType = 'sonstiges';
                    }

                    $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $docTitle, $docType]);
                }
            }
        }
    }
}

// Weiterleitung je nach Button
$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
