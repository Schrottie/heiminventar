<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$action = $_POST['action'] ?? 'save';

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);
$minQuantity = (int)($_POST['min_quantity'] ?? 0);
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

if (empty($name)) {
    die('Bezeichnung ist erforderlich');
}

// 1. Gegenstand in DB anlegen
$stmt = $pdo->prepare("INSERT INTO inventory_items (name, description, quantity, min_quantity, location_id) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$name, $description, $quantity, $minQuantity, $locationId]);

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

                    $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, filename, title) VALUES (?, ?, ?)");
                    $stmt->execute([$newItemId, $newFilename, $docTitle]);
                }
            }
        }
    }
}

// 4. Weiterleitung je nach gedrücktem Button
if ($action === 'save_and_next') {
    header("Location: ../edit-item.php?success=1");
} else {
    // Direkt zur Detail-Bearbeitungsseite weiterleiten
    header("Location: ../item.php?id=" . $newItemId . "&success=1");
}
exit;
