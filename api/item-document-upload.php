<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php'; // Falls Logging gewünscht ist

header('Content-Type: application/json');

$itemId = (int)($_POST['itemId'] ?? 0);
$docType = $_POST['doc_type'] ?? 'sonstiges';

if ($itemId <= 0 || empty($_FILES['documents'])) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Parameter.']);
    exit;
}

$uploadDir = __DIR__ . '/../docs/items/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadedDocuments = [];
$files = $_FILES['documents'];

for ($i = 0; $i < count($files['name']); $i++) {
    if ($files['error'][$i] === UPLOAD_ERR_OK) {
        $tmpName = $files['tmp_name'][$i];
        $originalName = basename($files['name'][$i]);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
       
        $allowed = ['pdf', 'doc', 'docx', 'txt'];
        if (!in_array($ext, $allowed)) continue;

        $newFilename = 'doc_' . $itemId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
       
        if (move_uploaded_file($tmpName, $uploadDir . $newFilename)) {
            $stmt = $pdo->prepare("INSERT INTO item_documents (item_id, title, filename, doc_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$itemId, $originalName, $newFilename, $docType]);
            $newDocId = $pdo->lastInsertId();

            $uploadedDocuments[] = [
                'id' => (int)$newDocId,
                'filename' => $newFilename,
                'title' => $originalName,
                'doc_type' => $docType
            ];
        }
    }
}

if (!empty($uploadedDocuments)) {
    echo json_encode([
        'success' => true,
        'count' => count($uploadedDocuments),
        'documents' => $uploadedDocuments
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern der Dokumente']);
}
exit;
