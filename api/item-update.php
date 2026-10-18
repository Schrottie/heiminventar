<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$itemId = (int)($_POST['id'] ?? $_POST['item_id'] ?? $_GET['id'] ?? $_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    die('Ungültige ID');
}

$stmtOld = $pdo->prepare("SELECT name, description, quantity, min_quantity, location_id, expiration_date, price, loan_to, loan_date, purchase_date, purchase_location, manufacturer, warranty_months, is_insured, is_set, is_bulk FROM inventory_items WHERE id = ?");
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
       
        logInventoryAction($pdo, $itemId, $oldItem['name'], 'updated', 0, (int)$oldItem['quantity'], 'Hauptbild geändert');
    }
    header("Location: ../item.php?id=" . $itemId);
    exit;
}

// 2. Stammdaten aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) && trim($_POST['name']) !== '' ? trim($_POST['name']) : $oldItem['name'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : $oldItem['description'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (int)$oldItem['quantity'];
    $minQuantity = isset($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : (int)$oldItem['min_quantity'];
    $locationId = isset($_POST['location_id']) ? (!empty($_POST['location_id']) ? (int)$_POST['location_id'] : null) : $oldItem['location_id'];

    $expirationDate = isset($_POST['expiration_date']) ? (!empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null) : $oldItem['expiration_date'];
    $price = isset($_POST['price']) ? (!empty($_POST['price']) ? (float)str_replace(',', '.', $_POST['price']) : null) : $oldItem['price'];
    $loanTo = isset($_POST['loan_to']) ? (trim($_POST['loan_to']) !== '' ? trim($_POST['loan_to']) : null) : $oldItem['loan_to'];
    $loanDate = isset($_POST['loan_date']) ? (!empty($_POST['loan_date']) ? $_POST['loan_date'] : null) : $oldItem['loan_date'];
    $onLoan = ($loanTo !== null) ? 1 : 0;

    $purchaseDate = isset($_POST['purchase_date']) ? (!empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null) : $oldItem['purchase_date'];
    $purchaseLocation = isset($_POST['purchase_location']) ? (trim($_POST['purchase_location']) !== '' ? trim($_POST['purchase_location']) : null) : $oldItem['purchase_location'];
    $manufacturer = isset($_POST['manufacturer']) ? (trim($_POST['manufacturer']) !== '' ? trim($_POST['manufacturer']) : null) : $oldItem['manufacturer'];
    $warrantyMonths = isset($_POST['warranty_months']) ? (int)$_POST['warranty_months'] : (int)$oldItem['warranty_months'];
    $isInsured = isset($_POST['is_insured']) ? 1 : 0;
    $isSet = isset($_POST['is_set']) ? 1 : 0;
    $isBulk = isset($_POST['is_bulk']) ? 1 : 0;
    $maintenanceInterval = !empty($_POST['maintenance_interval_months']) ? (int)$_POST['maintenance_interval_months'] : null;
    $lastMaintenanceDate = !empty($_POST['last_maintenance_date']) ? trim($_POST['last_maintenance_date']) : null;

    if ($name === '') {
        die('Name darf nicht leer sein');
    }

    $stmt = $pdo->prepare("UPDATE inventory_items SET
        name = ?, description = ?, quantity = ?, min_quantity = ?, location_id = ?,
        expiration_date = ?, price = ?, on_loan = ?, loan_to = ?, loan_date = ?,
        purchase_date = ?, purchase_location = ?, manufacturer = ?, warranty_months = ?, is_insured = ?, is_set = ?, is_bulk = ?,
        maintenance_interval_months = ?, last_maintenance_date = ?
        WHERE id = ?");

    $stmt->execute([
        $name, $description, $quantity, $minQuantity, $locationId,
        $expirationDate, $price, $onLoan, $loanTo, $loanDate,
        $purchaseDate, $purchaseLocation, $manufacturer, $warrantyMonths, $isInsured, $isSet, $isBulk,
        $maintenanceInterval, $lastMaintenanceDate, $itemId
    ]);

    syncItemUnits($pdo, $itemId, $quantity);

    // Protokollierung (Kurzform)
    $oldQty = (int)$oldItem['quantity'];
    if ($oldQty !== $quantity) {
        logInventoryAction($pdo, $itemId, $name, 'quantity_changed', $quantity - $oldQty, $quantity, 'Bestand angepasst', 'quantity', (string)$oldQty, (string)$quantity);
    }
}

// 3. Dokumenten-Upload verarbeiten (bleibt erhalten)
$uploadedDocsCount = 0;
if (isset($_FILES['documents']) && is_array($_FILES['documents']['tmp_name'])) {
    $docDir = __DIR__ . '/../docs/items/';
    ensureDirectoryExists($docDir);

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        if (isset($_FILES['documents']['error'][$key]) && $_FILES['documents']['error'][$key] === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
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

                    try {
                        $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$itemId, $newFilename, $docTitle, $docType]);
                        $uploadedDocsCount++;
                    } catch (PDOException $e) {}
                }
            }
        }
    }

    if ($uploadedDocsCount > 0) {
        logInventoryAction($pdo, $itemId, $name ?? $oldItem['name'], 'updated', 0, $quantity ?? (int)$oldItem['quantity'], $uploadedDocsCount . ' neue(s) Dokument(e) hochgeladen');
    }
}

// 4. Weiterleitung
$action = $_POST['action'] ?? 'save';
if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
 $tmpName) {
        if (isset($_FILES['documents']['error'][$key]) && $_FILES['documents']['error'][$key] === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
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

                    try {
                        $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$itemId, $newFilename, $docTitle, $docType]);
                        $uploadedDocsCount++;
                    } catch (PDOException $e) {
                        // Ignorieren
                    }
                }
            }
        }
    }

    if ($uploadedDocsCount > 0) {
        $currentItemName = isset($name) ? $name : $oldItem['name'];
        $currentQty = isset($quantity) ? $quantity : (int)$oldItem['quantity'];
        logInventoryAction($pdo, $itemId, $currentItemName, 'updated', 0, $currentQty, $uploadedDocsCount . ' neue(s) Dokument(e) hochgeladen');
    }
}

// 5. Weiterleitung je nach Button
$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
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

                    try {
                        $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$itemId, $newFilename, $docTitle, $docType]);
                        $uploadedDocsCount++;
                    } catch (PDOException $e) {
                        // Ignorieren / Weitermachen bei Duplikat
                    }
                }
            }
        }
    }

    if ($uploadedDocsCount > 0) {
        $currentItemName = isset($name) ? $name : $oldItem['name'];
        $currentQty = isset($quantity) ? $quantity : (int)$oldItem['quantity'];
        logInventoryAction($pdo, $itemId, $currentItemName, 'updated', 0, $currentQty, $uploadedDocsCount . ' neue(s) Dokument(e) hochgeladen');
    }
}

// 5. Weiterleitung je nach Button
$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
_id, filename, title, doc_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $docTitle, $docType]);
                   
                    $uploadedDocsCount++;
                }
            }
        }
    }

    if ($uploadedDocsCount > 0) {
        logInventoryAction($pdo, $itemId, $name, 'updated', 0, $quantity, $uploadedDocsCount . ' neue(s) Dokument(e) hochgeladen');
    }
}

$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
