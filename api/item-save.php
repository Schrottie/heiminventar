<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$action = $_POST['action'] ?? 'save';

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);
$minQuantity = (int)($_POST['min_quantity'] ?? 0);
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

// Weitere Felder auswerten
$manufacturer = trim($_POST['manufacturer'] ?? '') !== '' ? trim($_POST['manufacturer']) : null;
$price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
$purchaseDate = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
$purchaseLocation = trim($_POST['purchase_location'] ?? '') !== '' ? trim($_POST['purchase_location']) : null;
$warrantyMonths = !empty($_POST['warranty_months']) ? (int)$_POST['warranty_months'] : null;
$isInsured = isset($_POST['is_insured']) ? 1 : 0;
$isSet = isset($_POST['is_set']) ? 1 : 0;
$loanTo = trim($_POST['loan_to'] ?? '') !== '' ? trim($_POST['loan_to']) : null;
$loanDate = !empty($_POST['loan_date']) ? $_POST['loan_date'] : null;
$onLoan = ($loanTo !== null) ? 1 : 0;

$expirationDate = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
$maintenanceInterval = !empty($_POST['maintenance_interval_months']) ? (int)$_POST['maintenance_interval_months'] : null;
$lastMaintenanceDate = !empty($_POST['last_maintenance_date']) ? trim($_POST['last_maintenance_date']) : null;

if (empty($name)) {
    die('Bezeichnung ist erforderlich');
}

// 1. Gegenstand in DB anlegen (inkl. aller neuen Spalten)
$stmt = $pdo->prepare("INSERT INTO inventory_items (
    name, description, quantity, min_quantity, location_id, 
    manufacturer, price, purchase_date, purchase_location, warranty_months, is_insured, is_set,
    loan_to, loan_date, on_loan, expiration_date, maintenance_interval_months, last_maintenance_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $name, $description, $quantity, $minQuantity, $locationId,
    $manufacturer, $price, $purchaseDate, $purchaseLocation, $warrantyMonths, $isInsured, $isSet,
    $loanTo, $loanDate, $onLoan, $expirationDate, $maintenanceInterval, $lastMaintenanceDate
]);

$newItemId = (int)$pdo->lastInsertId();

// Logging für die Erstellung ausführen
logInventoryAction(
    $pdo,
    $newItemId,
    $name,
    'created',
    $quantity,
    $quantity,
    'Gegenstand neu angelegt'
);

// 2. Bilder-Upload verarbeiten
if (!empty($_FILES['images']['name'][0])) {
    $imgDir = __DIR__ . '/../img/items/';
    ensureDirectoryExists($imgDir);

    $isFirstImage = true;
    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $newFilename = 'item_' . $newItemId . '_' . time() . '_' . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmpName, $imgDir . $newFilename)) {
                    $isMain = $isFirstImage ? 1 : 0;
                    $isFirstImage = false;

                    $stmt = $pdo->prepare("INSERT INTO item_images (item_id, filename, is_main) VALUES (?, ?, ?)");
                    $stmt->execute([$newItemId, $newFilename, $isMain]);
                }
            }
        }
    }
}

// 3. Dokumenten-Upload verarbeiten
if (!empty($_FILES['documents']['name'][0])) {
    $docDir = __DIR__ . '/../docs/items/';
    ensureDirectoryExists($docDir);

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $originalName = $_FILES['documents']['name'][$key];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowedExts = ['pdf', 'doc', 'docx', 'txt'];
            if (in_array($ext, $allowedExts)) {
                $newFilename = 'doc_' . $newItemId . '_' . time() . '_' . uniqid() . '.' . $ext;

                if (move_uploaded_file($tmpName, $docDir . $newFilename)) {
                    $docTitle = pathinfo($originalName, PATHINFO_FILENAME);
                    $docType = $_POST['doc_type'] ?? 'sonstiges'; // oder pro Dokument wählbar

                    $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$newItemId, $newFilename, $docTitle, $docType]);
                }
            }
        }
    }
}

// 4. Weiterleitung je nach gedrücktem Button
if ($action === 'save_and_next') {
    header("Location: ../add-item.php?success=1");
} else {
    // Direkt zur Detail-Bearbeitungsseite weiterleiten
    header("Location: ../item.php?id=" . $newItemId . "&success=1");
}
exit;
