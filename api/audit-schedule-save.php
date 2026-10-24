<?php
session_start();
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// User-ID aus Session ermitteln (Sicherheitsprüfung)
$currentUserId = $_SESSION['user_id'] ?? 0;
if ($currentUserId <= 0 && function_exists('isLoggedIn') && !isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audits'])) {
    // Nur die Slots DIESES Users in audit_schedules löschen
    $stmt = $pdo->prepare("DELETE FROM audit_schedules WHERE user_id = ?");
    $stmt->execute([$currentUserId]);

    $insertStmt = $pdo->prepare("INSERT INTO audit_schedules (user_id, slot_index, audit_date, location_id, title) VALUES (?, ?, ?, ?, ?)");

    foreach ($_POST['audits'] as $slotIndex => $data) {
        $date = trim($data['date'] ?? '');
        if (empty($date)) continue; // Leere Slots überspringen

        $locationId = !empty($data['location_id']) ? (int)$data['location_id'] : null;
        if ($locationId <= 0) $locationId = null;

        $title = trim($data['title'] ?? 'Inventur');
        if (empty($title)) $title = 'Inventur';

        $insertStmt->execute([$currentUserId, (int)$slotIndex, $date, $locationId, $title]);
    }
}

header('Location: ../settings.php?saved=1');
exit;
