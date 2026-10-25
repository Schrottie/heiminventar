<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

$todayStr = date('Y-m-d');
$thirtyDaysAhead = date('Y-m-d', strtotime('+30 days'));

// --- 1. Kennzahlen (KPIs) aus der Datenbank abfragen ---

// Gesamtzahl, Gesamtmenge, Gesamtwert, Verleih, Mindestbestand, Wartungen & Ablauf (inkl. 30 Tage)
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(id) AS total_items,
        SUM(quantity) AS total_quantity,
        SUM(price * quantity) AS total_value,
        SUM(CASE WHEN loan_to IS NOT NULL AND loan_to != '' THEN 1 ELSE 0 END) AS total_loans,
        SUM(CASE WHEN min_quantity > 0 AND quantity <= min_quantity THEN 1 ELSE 0 END) AS total_low_stock,
        SUM(CASE WHEN maintenance_interval_months > 0 AND (
            last_maintenance_date IS NULL OR
            DATE_ADD(last_maintenance_date, INTERVAL maintenance_interval_months MONTH) <= ?
        ) THEN 1 ELSE 0 END) AS total_maintenance,
        SUM(CASE WHEN expiration_date IS NOT NULL AND expiration_date <= ? THEN 1 ELSE 0 END) AS total_expired
    FROM inventory_items
    WHERE is_archived = '0'
");
$stmtStats->execute([$thirtyDaysAhead, $thirtyDaysAhead]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Variable für die KPI-Anzeige oben direkt aus dem Array übernehmen
$expiredCount = $stats['total_expired'] ?? 0;

// --- 2. Detail-Listen für Widgets abfragen ---

// Aktuell verliehene Gegenstände
$loanedItems = $pdo->query("
    SELECT i.id, i.name, i.loan_to, i.quantity, l.name AS location_name
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    WHERE i.is_archived = '0' AND i.loan_to IS NOT NULL AND i.loan_to != ''
    ORDER BY i.name
")->fetchAll(PDO::FETCH_ASSOC);

// Demnächst ablaufende / abgelaufene Gegenstände (nächste 30 Tage)
$stmtExpiring = $pdo->prepare("
    SELECT id, name, expiration_date, quantity, location_id
    FROM inventory_items
    WHERE is_archived = '0' AND expiration_date IS NOT NULL AND expiration_date <= ?
    ORDER BY expiration_date ASC
");
$stmtExpiring->execute([$thirtyDaysAhead]);
$expiringItems = $stmtExpiring->fetchAll(PDO::FETCH_ASSOC);

// Artikel unter Mindestbestand (Nachbestellen)
$lowStockItems = $pdo->query("
    SELECT id, name, quantity, min_quantity
    FROM inventory_items
    WHERE is_archived = '0' AND min_quantity > 0 AND quantity <= min_quantity
    ORDER BY (min_quantity - quantity) DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Anstehende oder überfällige Wartungen (nächste 30 Tage oder bereits fällig)
$stmtMaintenance = $pdo->prepare("
    SELECT id, name, last_maintenance_date, maintenance_interval_months
    FROM inventory_items
    WHERE is_archived = '0' 
      AND maintenance_interval_months > 0 
      AND (
          last_maintenance_date IS NULL 
          OR DATE_ADD(last_maintenance_date, INTERVAL maintenance_interval_months MONTH) <= ?
      )
    ORDER BY last_maintenance_date ASC
");
$stmtMaintenance->execute([$thirtyDaysAhead]);
$maintenanceItems = $stmtMaintenance->fetchAll(PDO::FETCH_ASSOC);

// --- 3. Termine für den 3-Monats-Kalender laden ---
$calStart = date('Y-m-01'); // Erster Tag des aktuellen Monats
$calEnd = date('Y-m-t', strtotime('+2 months')); // Letzter Tag in 2 Monaten

$calendarEvents = [];

// A) Inventur-Termine laden (aus audit_schedules)
try {
    $stmtAudit = $pdo->prepare("
        SELECT audit_date AS event_date, title AS event_title, 'audit' AS event_type, l.name AS location_name
        FROM audit_schedules s
        LEFT JOIN locations l ON l.id = s.location_id
        WHERE s.audit_date BETWEEN ? AND ?
    ");
    $stmtAudit->execute([$calStart, $calEnd]);
    foreach ($stmtAudit->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $calendarEvents[$row['event_date']][] = [
            'type' => 'audit',
            'title' => 'Inventur: ' . $row['event_title'] . (!empty($row['location_name']) ? ' (' . $row['location_name'] . ')' : '')
        ];
    }
} catch (Exception $e) {}

// B) Verfallsdaten / Ablauf laden
try {
    $stmtExp = $pdo->prepare("
        SELECT expiration_date AS event_date, name AS event_title, 'expiration' AS event_type
        FROM inventory_items
        WHERE is_archived = '0' AND expiration_date IS BETWEEN ? AND ?
    ");
    // Korrektur für direkte Abfrage ohne Syntaxfehler
    $stmtExp = $pdo->prepare("
        SELECT expiration_date AS event_date, name AS event_title, 'expiration' AS event_type
        FROM inventory_items
        WHERE is_archived = '0' AND expiration_date >= ? AND expiration_date <= ?
    ");
    $stmtExp->execute([$calStart, $calEnd]);
    foreach ($stmtExp.fetchAll(PDO::FETCH_ASSOC) as $row) { // Kleinigkeits-Korrektur weiter unten im Render-Teil
    }
} catch (Exception $e) {}

// Komplette, sichere Termin-Sammel Abfrage für das 3-Monats-Fenster:
try {
    // 1. Verfallsdaten
    $stmtExp = $pdo->prepare("SELECT expiration_date AS event_date, name AS event_title, 'Verfall' AS category FROM inventory_items WHERE is_archived='0' AND expiration_date BETWEEN ? AND ?");
    $stmtExp->execute([$calStart, $calEnd]);
    foreach ($stmtExp->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $calendarEvents[$row['event_date']][] = ['type' => 'danger', 'text' => 'Verfall: ' . $row['event_title']];
    }

    // 2. Wartungen (errechnetes Datum)
    $stmtMaint = $pdo->query("SELECT id, name, last_maintenance_date, maintenance_interval_months FROM inventory_items WHERE is_archived='0' AND maintenance_interval_months > 0");
    foreach ($stmtMaint->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $baseDate = $item['last_maintenance_date'] ?: date('Y-m-d');
        // Nächste Fälligkeiten im 3-Monats-Fenster berechnen
        $nextDate = date('Y-m-d', strtotime($baseDate . ' + ' . $item['maintenance_interval_months'] . ' months'));
        if ($nextDate >= $calStart && $nextDate <= $calEnd) {
            $calendarEvents[$nextDate][] = ['type' => 'info', 'text' => 'Wartung fällig: ' . $item['name']];
        }
    }

    // 3. Inventurtermine
    $stmtAud = $pdo->prepare("SELECT s.audit_date, s.title, l.name as loc FROM audit_schedules s LEFT JOIN locations l ON l.id = s.location_id WHERE s.audit_date BETWEEN ? AND ?");
    $stmtAud->execute([$calStart, $calEnd]);
    foreach ($stmtAud->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $calendarEvents[$row['audit_date']][] = ['type' => 'primary', 'text' => 'Inventur: ' . $row['title'] . ($row['loc'] ? ' (' . $row['loc'] . ')' : '')];
    }
} catch (Exception $e) {}


require_once __DIR__ . '/inc/header.php';
?>

<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Dashboard &amp; Auswertung</h3>
        <p class="text-muted small mb-0">Zentrale Kennzahlen und Übersicht deines gesamten Inventars.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
        </a>
    </div>
</div>

<!-- KPI-Karten (Row 1) -->
<?php require_once __DIR__ . '/inc/dashboard/row1-kpi.php'; ?>
<!-- Kalender (Row 2) -->
<?php require_once __DIR__ . '/inc/dashboard/row2-calendar.php'; ?>
<!-- Widgets (Row 3) -->
<?php require_once __DIR__ . '/inc/dashboard/row3-widgets.php'; ?>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
