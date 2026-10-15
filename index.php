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

// Gegenstände abfragen (inklusive is_set und aller zugehörigen Tags)
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
        i.loan_to,
        i.maintenance_interval_months,
        i.last_maintenance_date,
        i.location_id,
        i.is_set,
        l.name AS location_name,
        img.filename AS image_filename,
        GROUP_CONCAT(t.name SEPARATOR '||') AS item_tags_list
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

<!-- Schnellfilter / Suchleiste & Warn-Buttons -->
<div class="row mb-3 g-2 align-items-center">
    <div class="col">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="quickFilter" class="form-control text-body" placeholder="Inventar durchsuchen...">
        </div>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#scannerModal">
            <i class="fa-solid fa-camera me-1"></i> QR Scannen
        </button>
    </div>

    <?php if ($lowStockCount > 0): ?>
        <div class="col-auto">
            <button type="button" id="toggleLowStock" class="btn btn-outline-danger position-relative">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Nachbestellen
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $lowStockCount ?>
                </span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($expiredCount > 0): ?>
        <div class="col-auto">
            <button type="button" id="toggleExpired" class="btn btn-outline-warning position-relative">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Ablauf/Verfall
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                    <?= $expiredCount ?>
                </span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($maintenanceCount > 0): ?>
        <div class="col-auto">
            <button type="button" id="toggleMaintenance" class="btn btn-outline-secondary border-info position-relative text-info-emphasis">
                <i class="fa-solid fa-screwdriver-wrench me-1"></i> Wartung fällig
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                    <?= $maintenanceCount ?>
                </span>
            </button>
        </div>
    <?php endif; ?>
</div>

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
    <?php foreach ($items as $item): ?>
        <?php
        $itemId = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $minQty = (int)$item['min_quantity'];
        $isLowStock = ($minQty >= 0 && $qty < $minQty);
        $isOnLoan = !empty($item['loan_to']);
        $isSet = !empty($item['is_set']);
       
        $expStatus = getExpirationStatus($item['expiration_date'] ?? null);
        $isExpiredOrWarning = ($expStatus === 'danger' || $expStatus === 'warning') ? '1' : '0';

        $maintStatus = getMaintenanceStatus($item['maintenance_interval_months'] ?? 0, $item['last_maintenance_date'] ?? null);
        $isMaintenanceDue = ($maintStatus === 'overdue' || $maintStatus === 'warning') ? '1' : '0';

        $path = getLocationPath($allLocations, (int)$item['location_id']);
        $searchTerms = mb_strtolower($item['name'] . ' ' . ($item['location_name'] ?? '') . ' ' . $path . ' ' . ($item['loan_to'] ?? ''));
       
        // Tags als Array für das data-Attribute aufbereiten
        $itemTagsArray = !empty($item['item_tags_list']) ? explode('||', $item['item_tags_list']) : [];
        $itemTagsJson = htmlspecialchars(json_encode($itemTagsArray), ENT_QUOTES, 'UTF-8');

        $hasImage = $showImages && !empty($item['image_filename']);

        // Styling für Karte je nach Status
        $cardStyleClass = '';
        if ($isLowStock) {
            $cardStyleClass = 'border-danger-subtle bg-danger-subtle bg-opacity-10';
        } elseif ($isOnLoan) {
            $cardStyleClass = 'border-info-subtle bg-info-subtle bg-opacity-10';
        }
        ?>

        <div class="card shadow-sm mb-2 inventory-item <?= $cardStyleClass ?>"
             data-search="<?= htmlspecialchars($searchTerms) ?>"
             data-tags="<?= $itemTagsJson ?>"
             data-low-stock="<?= $isLowStock ? '1' : '0' ?>"
             data-expired="<?= $isExpiredOrWarning ?>"
             data-maintenance="<?= $isMaintenanceDue ?>">
            <div class="card-body p-3">
                <div class="d-flex align-items-start justify-content-between">
                   
                    <div class="d-flex align-items-start me-3 flex-grow-1 mw-0">
                        <?php if ($hasImage): ?>
                            <div class="me-3 flex-shrink-0">
                                <img src="img/items/<?= htmlspecialchars($item['image_filename']) ?>"
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     class="rounded object-fit-cover"
                                     style="width: 50px; height: 50px;">
                            </div>
                        <?php endif; ?>

                        <div class="flex-grow-1 mw-0">
                            <h6 class="mb-1 text-break">
                                <?php if ($isSet): ?>
                                    <i class="fa-solid fa-layer-group text-primary me-1" title="Set"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-box text-primary me-1" title="Gegenstand"></i>
                                <?php endif; ?>
                               
                                <?= htmlspecialchars($item['name']) ?>
                               
                                <span class="d-inline-block">

                                    <span class="badge <?= $isLowStock ? 'bg-danger' : 'bg-secondary' ?> ms-1">
                                        x<?= $qty ?>
                                    </span>

                                    <?php if (!empty($itemTagsArray)): ?>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle d-none d-md-inline-flex align-items-center gap-1" title="Tags: <?= htmlspecialchars(implode(', ', $itemTagsArray)) ?>">
                                            <i class="fa-solid fa-tag" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($itemTagsArray[0]) ?><?php if(count($itemTagsArray) > 1): ?> +<?= count($itemTagsArray) - 1 ?><?php endif; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($isLowStock): ?>
                                        <span class="badge bg-warning text-dark ms-1" title="Mindestbestand: <?= $minQty ?>">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Min: <?= $minQty ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($isOnLoan): ?>
                                        <span class="badge bg-info text-dark ms-1" title="Verliehen an: <?= htmlspecialchars($item['loan_to']) ?>">
                                            <i class="fa-solid fa-handshake me-1"></i> Verliehen: <?= htmlspecialchars($item['loan_to']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($item['expiration_date'])):
                                        $badgeClass = ($expStatus === 'danger') ? 'bg-danger text-white' : (($expStatus === 'warning') ? 'bg-warning text-dark' : 'text-muted');
                                    ?>
                                        <span class="badge <?= $badgeClass ?>" title="Ablaufdatum: <?= htmlspecialchars($item['expiration_date']) ?>">
                                            <i class="fa-solid fa-calendar-days me-1"></i> <?= htmlspecialchars($item['expiration_date']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($isMaintenanceDue === '1'):
                                        $maintBadgeClass = ($maintStatus === 'overdue') ? 'bg-danger text-white' : 'bg-info text-dark';
                                        $maintText = ($maintStatus === 'overdue') ? 'Wartung überfällig' : 'Wartung fällig';
                                    ?>
                                        <span class="badge <?= $maintBadgeClass ?> ms-1" title="Wartungsintervall: Alle <?= (int)$item['maintenance_interval_months'] ?> Monate (Letzte: <?= htmlspecialchars($item['last_maintenance_date'] ?? 'Nie') ?>)">
                                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> <?= $maintText ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </h6>

                            <small class="d-block text-truncate">
                                <a class="text-decoration-none text-muted d-inline-block" role="button"
                                onclick="toggleLocationPath(this, event)"
                                data-short="<?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?>"
                                data-full="<?= htmlspecialchars($path) ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <span class="location-text"><?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?></span>
                                </a>
                            </small>
                        </div>
                    </div>

                    <div class="flex-shrink-0 ms-2">
                        <a href="item.php?id=<?= $itemId ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
