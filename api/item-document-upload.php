<?php
// Absolute Fehlerausgabe im Output unterdrücken, damit niemals HTML-Fehler das JSON zerstören
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

try {
    // Prüfen ob die DB-Datei existiert
    $dbPath = __DIR__ . '/../cfg/db.php';
    if (!file_exists($dbPath)) {
        throw new Exception("Datenbank-Konfigurationsdatei nicht gefunden: " . $dbPath);
    }
    require_once $dbPath;

    $funcPath = __DIR__ . '/../inc/functions.php';
    if (file_exists($funcPath)) {
        require_once $funcPath;
    }

    $itemId = (int)($_POST['itemId'] ?? $_POST['id'] ?? 0);
    $docType = $_POST['doc_type'] ?? 'sonstiges';

    if ($itemId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Ungültige Artikel-ID übergeben: ' . $itemId]);
        exit;
    }

    if (!isset($_FILES['documents']) || empty($_FILES['documents']['name'][0])) {
        echo json_encode(['success' => false, 'error' => 'Keine Dokumente im Request gefunden.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../docs/items/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Upload-Ordner konnte nicht erstellt werden: " . $uploadDir);
        }
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
                // Prüfen, ob die Tabelle existiert und das Statement fehlerfrei durchläuft
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
        echo json_encode(['success' => false, 'error' => 'Keine gültigen Dateien hochgeladen (Erlaubt: PDF, DOC, DOCX, TXT).']);
    }

} catch (Throwable $e) {
    // Fängt absolut jeden PHP-Fehler oder PDO-Exception ab und sendet sauberes JSON
    echo json_encode([
        'success' => false, 
        'error' => 'Server-Crash abgefangen: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
    ]);
}
exit;
