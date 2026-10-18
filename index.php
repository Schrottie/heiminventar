<?php

// Prüfen, ob das System überhaupt installiert ist
if (!file_exists(__DIR__ . '/cfg/.env')) {
    header('Location: install.php');
    exit;
}

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Einstellungen explizit aus der DB holen
if (!isset($settings)) {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

// Einstellung 'show_images' auslesen
$rawShowImages = $settings['show_images'] ?? '1';
$showImages = ($rawShowImages === '1');

// Alle Lagerorte für den Rekursionspfad (getLocationPath) und Filterung abfragen
$allLocations = $pdo->query("SELECT id, parent_id, name FROM locations")->fetchAll();

// location_id aus URL auslesen
$filterLocationId = isset($_GET['location_id']) && is_numeric($_GET['location_id']) ? (int)$_GET['location_id'] : null;

// Falls nach Standort gefiltert wird: Alle untergeordneten Lagerort-IDs ermitteln (Rekursion)
$targetLocationIds = [];
if ($filterLocationId !== null) {
    function getSubLocationIds(array $locations, int $parentId): array {
        $ids = [$parentId];
        foreach ($locations as $loc) {
            if ((int)$loc['parent_id'] === $parentId) {
                $ids = array_merge($ids, getSubLocationIds($locations, (int)$loc['id']));
            }
        }
        return $ids;
    }
    $targetLocationIds = getSubLocationIds($allLocations, $filterLocationId);
}

// Gegenstände abfragen (inklusive is_set, Verleih-Zählung aus item_units und aller zugehörigen Tags)
$params = []; $whereClause = '';

if (!empty($targetLocationIds)) {
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
    $whereClause = " AND i.location_id IN ($placeholders) ";
    $params = $targetLocationIds;
}

$sql = "
    SELECT
        i.id,
        i.name,
        i.quantity,
        i.min_quantity,
        i.expiration_date,
        i.maintenance_interval_months,
        i.last_maintenance_date,
        i.location_id,
        i.is_set,
        l.name AS location_name,
        img.filename AS image_filename,
        GROUP_CONCAT(t.name SEPARATOR '||') AS item_tags_list,
        (SELECT COUNT(*) FROM item_units u WHERE u.item_id = i.id AND u.status = 'lent') AS lent_count,
        (SELECT GROUP_CONCAT(CONCAT(u.borrower_name, ' (Ex. #', u.unit_number, ')') SEPARATOR ', ') FROM item_units u WHERE u.item_id = i.id AND u.status = 'lent') AS lent_details
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    LEFT JOIN item_tags it ON it.item_id = i.id
    LEFT JOIN tags t ON t.id = it.tag_id
    LEFT JOIN (
        SELECT item_id,
               COALESCE(
                   MIN(CASE WHEN is_main = 1 THEN id END),
                   MIN(id)
               ) AS main_id
        FROM item_images
        GROUP BY item_id
    ) main_img ON main_img.item_id = i.id
    LEFT JOIN item_images img ON img.id = main_img.main_id
    WHERE i.is_archived = '0'
    {$whereClause}
    GROUP BY i.id
    ORDER BY i.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Hilfsfunktion zur Prüfung des Ablaufstatus
function getExpirationStatus($dateStr) {
    if (empty($dateStr)) return null;
   
    $today = new DateTime(); $expDate = new DateTime($dateStr); $interval = $today->diff($expDate);
    $daysRemaining = (int)$interval->format('%r%d');
   
    if ($daysRemaining < 0) {
        return 'danger';
    } elseif ($daysRemaining <= 21) {
        return 'warning';
    }
    return 'success';
}

$lowStockCount = 0;
$expiredCount = 0;
$maintenanceCount = 0;

foreach ($items as $chk) {
    if ((int)$chk['min_quantity'] >= 0 && (int)$chk['quantity'] < (int)$chk['min_quantity']) {
        $lowStockCount++;
    }
   
    $expStatus = getExpirationStatus($chk['expiration_date'] ?? null);
    if ($expStatus === 'danger' || $expStatus === 'warning') {
        $expiredCount++;
    }

    $maintStatus = getMaintenanceStatus($chk['maintenance_interval_months'] ?? 0, $chk['last_maintenance_date'] ?? null);
    if ($maintStatus === 'overdue' || $maintStatus === 'warning') {
        $maintenanceCount++;
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<?php if ($filterLocationId !== null): ?>
    <?php
        $activeLocName = 'Unbekannt';
        foreach ($allLocations as $loc) {
            if ((int)$loc['id'] === $filterLocationId) {
                $activeLocName = $loc['name'];
                break;
            }
        }
    ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center py-2 mb-3">
        <span>
            <i class="fa-solid fa-filter me-2"></i>Gefiltert nach Standort: <strong><?= htmlspecialchars($activeLocName) ?></strong> (inkl. Unterstandorte)
        </span>
        <a href="index.php" class="btn btn-sm btn-outline-dark ms-2">
            <i class="fa-solid fa-xmark me-1"></i>Aufheben
        </a>
    </div>
<?php endif; ?>

<!-- Meldungsbanner -->
<?php if (($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0) && (!isset($_COOKIE['hide_daily_notice']) || $_COOKIE['hide_daily_notice'] !== date('Y-m-d'))): ?>
    <div id="dailyNoticeBanner" class="border rounded-3 p-2 px-3 mb-3 bg-body-tertiary small text-body-secondary shadow-none" style="font-size: 0.875rem;">
       
        <!-- Header (Schrift links, X rechts) -->
        <div class="d-flex align-items-center justify-content-between pb-1 mb-2">
            <div class="fw-bold text-secondary">
                <i class="fa-solid fa-circle-info text-danger me-1"></i> Meldung des Tages
            </div>
            <div>
                <button type="button" class="btn-close" style="font-size: 0.65rem;" onclick="hideDailyNotice()" title="Heute nicht mehr anzeigen"></button>
            </div>
        </div>

        <!-- Inhalt als saubere Liste mit Singular/Plural -->
        <ul class="mb-1 ps-3 text-secondary">
            <?php if ($lowStockCount > 0): ?>
                <li>
                    <?php if ($lowStockCount === 1): ?>
                        Anstehende Nachbestellung: <strong>1 Gegenstand</strong>
                    <?php else: ?>
                        Anstehende Nachbestellungen: <strong><?= $lowStockCount ?> Gegenstände</strong>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($expiredCount > 0): ?>
                <li>
                    <?php if ($expiredCount === 1): ?>
                        Ablauf- oder Verfallsdatum erreicht: <strong>1 Gegenstand</strong>
                    <?php else: ?>
                        Ablauf- oder Verfallsdatum erreicht: <strong><?= $expiredCount ?> Gegenstände</strong>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($maintenanceCount > 0): ?>
                <li>
                    <?php if ($maintenanceCount === 1): ?>
                        Fällige Wartung: <strong>1 Gegenstand</strong>
                    <?php else: ?>
                        Fällige Wartungen: <strong><?= $maintenanceCount ?> Gegenstände</strong>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
        </ul>

    </div>
<?php endif; ?>

<!-- Schnellfilter / Suchleiste, QR-Button & Desktop-Warn-Buttons in einer Zeile -->
<div class="row mb-3 g-2 align-items-center">
    <!-- Suchleiste nimmt den flexiblen Platz ein -->
    <div class="col">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="quickFilter" class="form-control text-body" placeholder="Inventar durchsuchen...">
        </div>
    </div>

    <!-- QR-Button (immer als reines Icon) -->
    <div class="col-auto">
        <button type="button" class="btn btn-outline-secondary px-3" data-bs-toggle="modal" data-bs-target="#scannerModal" title="QR scannen">
            <i class="fa-solid fa-qrcode"></i>
        </button>
    </div>

    <!-- WARN-BUTTONS FÜR DESKTOP & TABLET (Ab d-md-flex: rechts neben dem QR-Button) -->
    <?php if ($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0): ?>
        <div class="col-auto d-none d-md-flex align-items-center gap-2">
            <?php if ($lowStockCount > 0): ?>
                <button type="button" id="toggleLowStock_desktop" class="btn btn-outline-danger position-relative" title="Nachbestellen">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $lowStockCount ?>
                    </span>
                </button>
            <?php endif; ?>

            <?php if ($expiredCount > 0): ?>
                <button type="button" id="toggleExpired_desktop" class="btn btn-outline-warning position-relative" title="Ablauf/Verfall">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                        <?= $expiredCount ?>
                    </span>
                </button>
            <?php endif; ?>

            <?php if ($maintenanceCount > 0): ?>
                <button type="button" id="toggleMaintenance_desktop" class="btn btn-outline-secondary border-info position-relative text-info-emphasis" title="Wartung fällig">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                        <?= $maintenanceCount ?>
                    </span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- WARN-BUTTONS FÜR SMARTPHONES (Nur auf kleinen Screens sichtbar: d-md-none, rutschen in eigene Zeile darunter) -->
<?php if ($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0): ?>
    <div class="row mb-3 g-2 d-md-none">
        <div class="col-12">
            <div class="row g-2">
                <?php if ($lowStockCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleLowStock" class="btn btn-outline-danger position-relative w-100">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Nachbestellen
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $lowStockCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($expiredCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleExpired" class="btn btn-outline-warning position-relative w-100">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Ablauf/Verfall
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                <?= $expiredCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($maintenanceCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleMaintenance" class="btn btn-outline-secondary border-info position-relative text-info-emphasis w-100">
                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Wartung fällig
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                                <?= $maintenanceCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Interaktive Tag-Filterleiste -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="text-muted small fw-bold">
                <i class="fa-solid fa-tags me-1"></i>Nach Tags filtern:
                <i class="fa-solid fa-circle-question text-muted ms-1" style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Tipp: Tippen oder Vorschlag wählen und mit Enter oder Komma bestätigen. Ab 2 Tags erscheint ein UND/ODER-Modus Schalter."></i>
            </span>
           
            <!-- UND / ODER Modus Umschalter (wird ab 2 Tags automatisch sichtbar) -->
            <div id="tagModeContainer" class="btn-group btn-group-sm" style="display: none;" role="group">
                <button type="button" id="modeOrBtn" class="btn btn-outline-primary active">ODER</button>
                <button type="button" id="modeAndBtn" class="btn btn-outline-primary">UND</button>
            </div>
        </div>
        <div class="input-group">
            <div class="position-relative flex-grow-1">
                <!-- Tag-Eingabe Container (Pills + Input) - Darkmode-optimiert mit bg-transparent -->
                <div id="filterTagContainer" class="form-control d-flex flex-wrap align-items-center gap-1 p-2 bg-transparent text-body" style="min-height: 38px; cursor: text; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                    <input type="text" id="filterTagInput" class="border-0 flex-grow-1 bg-transparent text-body p-0 shadow-none" placeholder="Tag eingeben oder suchen..." autocomplete="off" style="outline: none; min-width: 140px;">
                </div>
           
                <!-- Autocomplete-Dropdown -->
                <div id="filterTagSuggestions" class="dropdown-menu shadow-sm w-100" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050;"></div>
            </div>
            <!-- X-Button (Standardmäßig ausgeblendet) -->
            <button type="button" id="clearFilterTags" class="btn btn-outline-secondary d-none" title="Alle Filter-Tags löschen">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</div>

<!-- Inventarliste -->
<div id="inventoryList">
<?php require_once __DIR__ . '/inc/index/item-loop.php'; ?>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
