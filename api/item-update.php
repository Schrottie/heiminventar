<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$itemId = (int)($_POST['id'] ?? 0);

if ($itemId <= 0) {
    die('Ungültige ID');
}

// 1. Nur Hauptbild ändern (falls Stern geklickt wurde)
if (isset($_POST['action_set_main_image'])) {
    $imageId = (int)($_POST['image_id'] ?? 0);
    if ($imageId > 0) {
        $pdo->prepare("UPDATE item_images SET is_main = 0 WHERE item_id = ?")->execute([$itemId]);
        $pdo->prepare("UPDATE item_images SET is_main = 1 WHERE id = ? AND item_id = ?")->execute([$imageId, $itemId]);
    }
    header("Location: ../item.php?id=" . $itemId);
    exit;
}

// 2. Stammdaten des Gegenstands aktualisieren
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);
$locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;

$stmt = $pdo->prepare("UPDATE inventory_items SET name = ?, description = ?, quantity = ?, location_id = ? WHERE id = ?");
$stmt->execute([$name, $description, $quantity, $locationId, $itemId]);

// 3. Bilder-Upload verarbeiten
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
                    $hasMainImage = true; // Nach dem ersten Bild gibt es definitiv ein Hauptbild

                    $stmt = $pdo->prepare("INSERT INTO item_images (item_id, filename, is_main) VALUES (?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $isMain]);
                }
            }
        }
    }
}

// 4. Dokumenten-Upload verarbeiten
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
                }
            }
        }
    }
}

header("Location: ../item.php?id=" . $itemId . "&success=1");
exit;

