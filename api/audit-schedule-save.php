<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

$currentUserId = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audits']) && is_array($_POST['audits'])) {
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_schedules (user_id, slot_index, audit_date, location_id, title)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            audit_date = VALUES(audit_date),
            location_id = VALUES(location_id),
            title = VALUES(title),
            updated_at = CURRENT_TIMESTAMP
    ");

    foreach ($_POST['audits'] as $slotIndex => $data) {
        $slotIndex = (int)$slotIndex;
        if ($slotIndex < 1 || $slotIndex > 3) continue;

        $date = trim($data['date'] ?? '');
        $locationId = !empty($data['location_id']) && is_numeric($data['location_id']) ? (int)$data['location_id'] : null;
        $title = trim($data['title'] ?? '') ?: 'Reguläre Inventur';

        if (empty($date)) {
            // Slot löschen, wenn kein Datum angegeben ist
            $delStmt = $pdo->prepare("DELETE FROM audit_schedules WHERE user_id = ? AND slot_index = ?");
            $delStmt->execute([$currentUserId, $slotIndex]);
        } else {
            // Speichern / Aktualisieren
            $stmt->execute([$currentUserId, $slotIndex, $date, $locationId, $title]);
        }
    }

    // Unterstützung für optionale AJAX-Requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

header('Location: ../settings.php?success=1');
exit;
