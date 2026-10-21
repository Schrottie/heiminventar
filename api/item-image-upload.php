<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$itemId = (int)($_POST['itemId'] ?? $_POST['id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ungültige ID']);
    exit;
}

if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    echo json_encode(['success' => false, 'error' => 'Keine Datei ausgewählt']);
    exit;
}

$imgDir = __DIR__ . '/../img/items/';
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0777, true);
}

// Prüfen, ob bereits ein Hauptbild existiert
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM item_images WHERE item_id = ?");
$stmtCount->execute([$itemId]);
$totalExistingImages = (int)$stmtCount->fetchColumn();

$uploadedImages = [];

foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
    if (isset($_FILES['images']['error'][$key]) && $_FILES['images']['error'][$key] === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
        $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $newFilename = 'item_' . $itemId . '_' . time() . '_' . uniqid() . '.' . $ext;
           
            if (move_uploaded_file($tmpName, $imgDir . $newFilename)) {
                $isMain = ($totalExistingImages === 0 && count($uploadedImages) === 0) ? 1 : 0;

                try {
                    $stmt = $pdo->prepare("INSERT INTO item_images (item_id, filename, is_main) VALUES (?, ?, ?)");
                    $stmt->execute([$itemId, $newFilename, $isMain]);
                    $newImageId = $pdo->lastInsertId();
                    
                    $totalExistingImages++;
                    $uploadedImages[] = [
                        'id' => (int)$newImageId,
                        'filename' => $newFilename
                    ];
                } catch (PDOException $e) {
                    // Fallback auf is_main = 0 falls ein Constraint greift
                    $stmt = $pdo->prepare("INSERT INTO item_images (item_id, filename, is_main) VALUES (?, ?, 0)");
                    $stmt->execute([$itemId, $newFilename]);
                    $newImageId = $pdo->lastInsertId();

                    $uploadedImages[] = [
                        'id' => (int)$newImageId,
                        'filename' => $newFilename
                    ];
                }
            }
        }
    }
}

if (!empty($uploadedImages)) {
    // Optional: Logeintrag generieren
    $stmtItem = $pdo->prepare("SELECT name, quantity FROM inventory_items WHERE id = ?");
    $stmtItem->execute([$itemId]);
    $itemData = $stmtItem->fetch();
    if ($itemData && function_exists('logInventoryAction')) {
        logInventoryAction($pdo, $itemId, $itemData['name'], 'updated', 0, (int)$itemData['quantity'], count($uploadedImages) . ' neue(s) Bild(er) hochgeladen');
    }

    echo json_encode([
        'success' => true, 
        'count' => count($uploadedImages),
        'images' => $uploadedImages
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern der Bilder']);
}
exit;
