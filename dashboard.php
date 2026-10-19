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
<div class="row g-3 mb-4">
    <!-- Gesamtwert -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-primary-subtle bg-opacity-10 border-start border-primary border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Gesamtwert (Inventar)</div>
                <div class="fs-4 fw-bold text-primary mt-1">
                    <?= number_format($stats['total_value'] ?? 0, 2, ',', '.') ?> €
                </div>
                <div class="small text-muted mt-1">
                    <?= (int)$stats['total_items'] ?> Gegenstände (<?= (int)$stats['total_quantity'] ?> Stück gesamt)
                </div>
            </div>
        </div>
    </div>

    <!-- Nachbestellen (Mindestbestand) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-danger-subtle bg-opacity-10 border-start border-danger border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Unter Mindestbestand</div>
                <div class="fs-4 fw-bold text-danger mt-1">
                    <?= (int)$stats['total_low_stock'] ?>
                </div>
                <div class="small text-muted mt-1">
                    Artikel müssen nachbestellt werden
                </div>
            </div>
        </div>
    </div>

    <!-- Ablauf / Verfall -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-warning-subtle bg-opacity-10 border-start border-warning border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Ablauf / Verfall</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1">
                    <?= (int)$expiredCount ?>
                </div>
                <div class="small text-muted mt-1">
                    Aktuell (oder in max. 30 Tagen) abgelaufen
                </div>
            </div>
        </div>
    </div>

    <!-- Wartung fällig -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-info-subtle bg-opacity-10 border-start border-info border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Wartung fällig</div>
                <div class="fs-4 fw-bold text-info-emphasis mt-1">
                    <?= (int)$stats['total_maintenance'] ?>
                </div>
                <div class="small text-muted mt-1">
                    Aktuell (oder in max. 30 Tagen) fällig
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Widgets (Row 2 & 3) -->
<div class="row g-4 mb-4">
    <!-- Widget 1: Nachbestellen / Mindestbestand -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-cart-shopping text-danger me-2"></i>Nachbestellung erforderlich</span>
                <span class="badge bg-danger"><?= count($lowStockItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockItems)): ?>
                    <div class="p-4 text-center text-muted small">Alles bestens – kein Artikel unter Mindestbestand.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Bestand / Min</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $low): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$low['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($low['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= (int)$low['quantity'] ?> / Min: <?= (int)$low['min_quantity'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$low['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget 2: Anstehende Wartungen -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-screwdriver-wrench text-info me-2"></i>Anstehende / Fällige Wartungen</span>
                <span class="badge bg-info text-dark"><?= count($maintenanceItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($maintenanceItems)): ?>
                    <div class="p-4 text-center text-muted small">Derzeit stehen keine Wartungen an.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Intervall / Letzte Wartung</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceItems as $maint): ?>
                                    <?php
                                        $lastMaint = $maint['last_maintenance_date'] ? date('d.m.Y', strtotime($maint['last_maintenance_date'])) : 'Nie';
                                        $dueDate = $maint['last_maintenance_date'] ? date('d.m.Y', strtotime("+{$maint['maintenance_interval_months']} months", strtotime($maint['last_maintenance_date']))) : 'Sofort fällig';
                                        $isOverdue = !$maint['last_maintenance_date'] || $dueDate <= $todayStr;
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$maint['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($maint['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-info text-dark' ?>">
                                                <?= $isOverdue ? 'Überfällig (Intervall: ' . $maint['maintenance_interval_months'] . ' Mon.)' : 'Fällig am ' . $dueDate ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$maint['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Widget 3: Verliehene Gegenstände -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-handshake text-primary me-2"></i>Aktive Verleihvorgänge</span>
                <span class="badge bg-secondary"><?= count($loanedItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($loanedItems)): ?>
                    <div class="p-4 text-center text-muted small">Aktuell sind keine Gegenstände verliehen.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Verliehen an</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loanedItems as $loan): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$loan['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($loan['name']) ?>
                                            </a>
                                            <span class="badge bg-secondary ms-1">x<?= (int)$loan['quantity'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($loan['loan_to']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$loan['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget 4: Ablaufende / Abgelaufene Artikel -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Ablaufende Artikel (30 Tage)</span>
                <span class="badge bg-warning text-dark"><?= count($expiringItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($expiringItems)): ?>
                    <div class="p-4 text-center text-muted small">Keine Artikel mit anstehendem Ablaufdatum in nächster Zeit.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Ablaufdatum</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiringItems as $exp): ?>
                                    <?php
                                        $isPast = $exp['expiration_date'] < $todayStr;
                                        $badgeStyle = $isPast ? 'bg-danger text-white' : 'bg-warning text-dark';
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$exp['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($exp['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeStyle ?>">
                                                <?= htmlspecialchars($exp['expiration_date']) ?> <?= $isPast ? '(Abgelaufen)' : '' ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
