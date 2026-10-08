<?php
// Output Buffering starten, um ungewollte PHP-Warnungen vor dem JSON abzufangen
ob_start();
session_start();

// Relativer Pfad zur DB-Datei
require_once __DIR__ . '/../cfg/db.php';

// Eventuelle PHP-Ausgaben vor dem Header verwerfen
ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $version = $input['version'] ?? null;
    $userId  = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;

    if ($version) {
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET last_seen_version = ? WHERE id = ?");
            $stmt->execute([$version, $userId]);
            
            // Session direkt mit aktualisieren, damit es beim selben Request synchron bleibt
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['last_seen_version'] = $version;
            }
        } else {
            setcookie('app_last_seen_version', $version, time() + (86400 * 365), '/');
        }

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Keine Version übergeben']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}