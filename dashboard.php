<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// --- 1. Kennzahlen (KPIs) aus der Datenbank abfragen ---

// Gesamtzahl eindeutiger Artikel & Gesamtmenge (Summe quantity) sowie Gesamtwert (price * quantity)
$stmtStats = $pdo->query("
    SELECT 
        COUNT(id) AS total_items,
        SUM(quantity) AS total_quantity,
        SUM(price * quantity) AS total_value,
        SUM(CASE WHEN loan_to IS NOT NULL AND loan_to != '' THEN 1 ELSE 0 END) AS total_loans,
        SUM(CASE WHEN min_quantity > 0 AND quantity <= min_quantity THEN 1 ELSE 0 END) AS total_low_stock
    FROM inventory_items 
    WHERE is_archived = '0'
");
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Anzahl abgelaufener Artikel berechnen
$todayStr = date('Y-m-d');
$stmtExpired = $pdo->prepare("
    SELECT COUNT(*) FROM inventory_items 
    WHERE is_archived = '0' AND expiration_date IS NOT NULL AND expiration_date <= ?
");
$stmtExpired->execute([$todayStr]);
$expiredCount = $stmtExpired->fetchColumn();

// --- 2. Detail-Listen für Widgets abfragen ---

// Aktuell verliehene Gegenstände
$stmtLoans = $pdo->query("
    SELECT i.id, i.name, i.loan_to, i.quantity, l.name AS location_name
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    WHERE i.is_archived = '0' AND i.loan_to IS NOT NULL AND i.loan_to != ''
    ORDER BY i.name
");

$loanedItems = $stmtLoans->fetchAll(PDO::FETCH_ASSOC);

// Demnächst ablaufende / abgelaufene Gegenstände (nächste 30 Tage)
$thirtyDaysAhead = date('Y-m-d', strtotime('+30 days'));
$stmtExpiring = $pdo->prepare("
    SELECT id, name, expiration_date, quantity, location_id
    FROM inventory_items
    WHERE is_archived = '0' AND expiration_date IS NOT NULL AND expiration_date <= ?
    ORDER BY expiration_date ASC
");
$stmtExpiring->execute([$thirtyDaysAhead]);
$expiringItems = $stmtExpiring->fetchAll(PDO::FETCH_ASSOC);

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
            <i class="fa-solid fa-arrow-left me-1"></i>Zurück zur Übersicht
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
                    <?= (int)$stats['total_items'] ?> Artikeltypen (<?= (int)$stats['total_quantity'] ?> Stück gesamt)
                </div>
            </div>
        </div>
    </div>

    <!-- Verleih-Status -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-info-subtle bg-opacity-10 border-start border-info border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Verliehene Gegenstände</div>
                <div class="fs-4 fw-bold text-info mt-1">
                    <?= (int)$stats['total_loans'] ?>
                </div>
                <div class="small text-muted mt-1">
                    Artikel außer Haus / verliehen
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
                    Abgelaufen oder in den nächsten 30 Tagen
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Widgets (Row 2: Verleih & Ablauf) -->
<div class="row g-4">
    <!-- Widget 1: Verliehene Gegenstände -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-handshake text-info me-2"></i>Aktive Verleihvorgänge</span>
                <span class="badge bg-info text-dark"><?= count($loanedItems) ?></span>
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

    <!-- Widget 2: Ablaufende / Abgelaufene Artikel -->
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
