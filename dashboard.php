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

<!-- Widgets (Row 2 & 3) -->
<?php require_once __DIR__ . '/inc/dashboard/row2-3-dates.php'; ?>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
