<?php
require_once __DIR__ . '/../cfg/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $action = $_POST['action'] ?? 'save';

    if (!empty($name)) {
        // 1. Gegenstand in DB anlegen
        $stmt = $pdo->prepare("INSERT INTO inventory_items (name, description, quantity, location_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $quantity, $locationId]);
        $newItemId = $pdo->lastInsertId();

        // 2. Bilder verarbeiten (falls welche hochgeladen wurden)
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . '/../img/items/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($extension, $allowedExtensions)) {
                        $filename = 'item_' . $newItemId . '_' . uniqid() . '.' . $extension;
                        $targetFile = $uploadDir . $filename;

                        if (move_uploaded_file($tmpName, $targetFile)) {
                            $stmtImg = $pdo->prepare("INSERT INTO item_images (item_id, filename) VALUES (?, ?)");
                            $stmtImg->execute([$newItemId, $filename]);
                        }
                    }
                }
            }
        }

        // 3. Weiterleitung je nach gedrücktem Button
        if ($action === 'save_and_next') {
            header("Location: ../edit-item.php?success=1");
        } else {
            header("Location: ../index.php?success=1");
        }
        exit;
    }
}
