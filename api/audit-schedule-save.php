<?php
// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Prüfen ob der User eingeloggt ist (Fallback auf User-ID 1 falls Auth deaktiviert oder nicht gesetzt)
$currentUserId = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audits']) && is_array($_POST['audits'])) {
    
    // Vorbereitetes Statement für Upsert (Einfügen oder Aktualisieren pro Slot)
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
        
        // Nur Slots 1 bis 3 zulassen
        if ($slotIndex < 1 || $slotIndex > 3) {
            continue;
        }

        $date = trim($data['date'] ?? '');
        $locationId = !empty($data['location_id']) && is_numeric($data['location_id']) ? (int)$data['location_id'] : null;
        $title = trim($data['title'] ?? '') ?: 'Reguläre Inventur';

        // Wenn kein Datum angegeben wurde, leeren wir den Slot bzw. löschen ihn
        if (empty($date)) {
            $deleteStmt = $pdo->prepare("DELETE FROM audit_schedules WHERE user_id = ? AND slot_index = ?");
            $deleteStmt->execute([$currentUserId, $slotIndex]);
        } else {
            // Speichern / Aktualisieren
            $stmt->execute([$currentUserId, $slotIndex, $date, $locationId, $title]);
        }
    }
}

// Zurück zur Einstellungsseite leiten
header('Location: ../settings.php?tab=general&success=1');
exit;
