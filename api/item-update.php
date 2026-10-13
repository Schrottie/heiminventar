<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Erfasst 'id' sowie 'item_id' sowohl aus POST als auch GET
$itemId = (int)($_POST['id'] ?? $_POST['item_id'] ?? $_GET['id'] ?? $_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    die('Ungültige ID');
}

// 0. Vorherigen Zustand des Artikels für Vergleich und Logging abfragen 
$stmtOld = $pdo->prepare("SELECT name, description, quantity, min_quantity, location_id, expiration_date, price, loan_to FROM inventory_items WHERE id = ?");
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
       
        logInventoryAction(
            $pdo,
            $itemId,
            $oldItem['name'],
            'updated',
            0,
            (int)$oldItem['quantity'],
            'Hauptbild geändert'
        );
    }
    header("Location: ../item.php?id=" . $itemId);
    exit;
}

// 2. Stammdaten nur aktualisieren, wenn sie im Formular übermittelt wurden
$hasNameField = isset($_POST['name']);

// Fallbacks auf Altwerte sicherstellen
$name = $hasNameField ? trim($_POST['name']) : $oldItem['name'];
$description = isset($_POST['description']) ? trim($_POST['description']) : $oldItem['description'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (int)$oldItem['quantity'];
$minQuantity = isset($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : (int)$oldItem['min_quantity'];
$locationId = isset($_POST['location_id']) ? (!empty($_POST['location_id']) ? (int)$_POST['location_id'] : null) : $oldItem['location_id'];

// (Ablaufdatum, Preis, Verleih etc.) auswerten
$expirationDate = isset($_POST['expiration_date']) ? (!empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null) : $oldItem['expiration_date'];
$price = isset($_POST['price']) ? (!empty($_POST['price']) ? (float)$_POST['price'] : null) : $oldItem['price'];
$loanTo = isset($_POST['loan_to']) ? (trim($_POST['loan_to']) !== '' ? trim($_POST['loan_to']) : null) : $oldItem['loan_to'];
$onLoan = ($loanTo !== null) ? 1 : 0; // Automatisch Flag setzen, wenn ein Verleih-Name eingetragen ist
$maintenanceInterval = !empty($_POST['maintenance_interval_months']) ? (int)$_POST['maintenance_interval_months'] : null;
$lastMaintenanceDate = !empty($_POST['last_maintenance_date']) ? trim($_POST['last_maintenance_date']) : null;

// Validierung: Name darf beim Ändern der Stammdaten nicht leer sein
if ($hasNameField && $name === '') {
    die('Name darf nicht leer sein');
}

if ($hasNameField) {
    $stmt = $pdo->prepare("UPDATE inventory_items SET name = ?, description = ?, quantity = ?, min_quantity = ?, location_id = ?, expiration_date = ?, price = ?, on_loan = ?, loan_to = ? ,maintenance_interval_months = ?, last_maintenance_date = ? WHERE id = ?");
    $stmt->execute([$name, $description, $quantity, $minQuantity, $locationId, $expirationDate, $price, $onLoan, $loanTo, $maintenanceInterval, $lastMaintenanceDate, $itemId]);

    // --- PROTOKOLLIERUNG DER STAMMDATEN-ÄNDERUNGEN (EINZELN UND ENTKOPPELT) ---

    // A) Bestandsänderung
    $oldQty = (int)$oldItem['quantity'];
    if ($oldQty !== $quantity) {
        $qtyChange = $quantity - $oldQty;
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'quantity_changed',
            $qtyChange,
            $quantity,
            'Bestand über Formular angepasst',
            'quantity',
            (string)$oldQty,
            (string)$quantity
        );
    }

    // B) Namensänderung
    if ($oldItem['name'] !== $name) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            'Name geändert',
            'name',
            $oldItem['name'],
            $name
        );
    }

    // C) Beschreibungsänderung
    if ($oldItem['description'] !== $description) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            'Beschreibung angepasst',
            'description',
            $oldItem['description'],
            $description
        );
    }

    // D) Mindestbestand
    if ((int)$oldItem['min_quantity'] !== $minQuantity) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            'Mindestbestand angepasst',
            'min_quantity',
            (string)$oldItem['min_quantity'],
            (string)$minQuantity
        );
    }

    // E) Haupt-Lagerort / Standort geändert
    if ((int)$oldItem['location_id'] !== (int)$locationId) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'relocated',
            0,
            $quantity,
            'Hauptstandort geändert',
            'location_id',
            $oldItem['location_id'] !== null ? (string)$oldItem['location_id'] : null,
            $locationId !== null ? (string)$locationId : null
        );
    }

    // F) Ablaufdatum geändert
    if ($oldItem['expiration_date'] !== $expirationDate) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            'Ablaufdatum angepasst',
            'expiration_date',
            $oldItem['expiration_date'],
            $expirationDate
        );
    }

    // G) Preis geändert
    if ($oldItem['price'] !== $price) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            'Anschaffungspreis angepasst',
            'price',
            $oldItem['price'] !== null ? (string)$oldItem['price'] : null,
            $price !== null ? (string)$price : null
        );
    }

    // H) Verleihstatus / Verleih-Name geändert
    if ($oldItem['loan_to'] !== $loanTo) {
        $logActionText = empty($loanTo) ? 'Gegenstand zurückgegeben' : 'Gegenstand verliehen an: ' . $loanTo;
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            $logActionText,
            'loan_to',
            $oldItem['loan_to'],
            $loanTo
        );
    }
}

// 3. Bilder-Upload verarbeiten
$uploadedImagesCount = 0;
if (!empty($_FILES['images']['name'][0])) {
    $imgDir = __DIR__ . '/../img/items/';
    ensureDirectoryExists($imgDir);

    // Prüfen, ob bereits ein Hauptbild existiert
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
                    $uploadedImagesCount++;
                }
            }
        }
    }

    if ($uploadedImagesCount > 0) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            $uploadedImagesCount . ' neue(s) Bild(er) hochgeladen'
        );
    }
}

// 4. Dokumenten-Upload verarbeiten
$uploadedDocsCount = 0;
if (!empty($_FILES['documents']['name'][0])) {
    $docDir = __DIR__ . '/../docs/items/';
    ensureDirectoryExists($docDir);

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $originalName = $_FILES['documents']['name'][$key];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
           
            $allowedExts = ['pdf', 'doc', 'docx', 'txt'];
            if (in_array($ext, $allowedExts)) {
                $newFilename = 'doc_' . $itemId . '_' . time() . '_' . uniqid() . '.' . $ext;
               
                if (move_uploaded_file($tmpName, $docDir . $newFilename)) {
                    $docTitle = pathinfo($originalName, PATHINFO_FILENAME);

                    $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title) VALUES (?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $docTitle]);
                    $uploadedDocsCount++;
                }
            }
        }
    }

    if ($uploadedDocsCount > 0) {
        logInventoryAction(
            $pdo,
            $itemId,
            $name,
            'updated',
            0,
            $quantity,
            $uploadedDocsCount . ' neue(s) Dokument(e) hochgeladen'
        );
    }
}

$action = $_POST['action'] ?? 'save';

if ($action === 'save_and_close') {
    header("Location: ../index.php?success=1");
} else {
    header("Location: ../item.php?id=" . $itemId . "&success=1");
}
exit;
