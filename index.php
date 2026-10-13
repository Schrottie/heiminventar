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

// Gegenstände inkl. Wartungsfeldern abfragen
$params = [];
$whereClause = '';

if (!empty($targetLocationIds)) {
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
    $whereClause = " WHERE i.location_id IN ($placeholders) ";
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
        l.name AS location_name,
        img.filename AS image_filename
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
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
    {$whereClause}
    WHERE i.is_archived = '0'
    ORDER BY i.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Hilfsfunktion zur Prüfung des Ablaufstatus
function getExpirationStatus($dateStr) {
    if (empty($dateStr)) return null;
   
    $today = new DateTime();
    $expDate = new DateTime($dateStr);
    $interval = $today->diff($expDate);
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
    if ((int)$chk['min_quantity'] > 0 && (int)$chk['quantity'] <= (int)$chk['min_quantity']) {
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

<!-- Schnellfilter / Suchleiste & Warn-Buttons -->
<div class="row mb-3 g-2 align-items-center">
    <div class="col">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="quickFilter" class="form-control" placeholder="Inventar durchsuchen...">
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

<!-- Inventarliste -->
<div id="inventoryList">
    <?php foreach ($items as $item): ?>
        <?php
        $itemId = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $minQty = (int)$item['min_quantity'];
        $isLowStock = ($minQty > 0 && $qty <= $minQty);
        $isOnLoan = !empty($item['loan_to']);
       
        $expStatus = getExpirationStatus($item['expiration_date'] ?? null);
        $isExpiredOrWarning = ($expStatus === 'danger' || $expStatus === 'warning') ? '1' : '0';

        $maintStatus = getMaintenanceStatus($item['maintenance_interval_months'] ?? 0, $item['last_maintenance_date'] ?? null);
        $isMaintenanceDue = ($maintStatus === 'overdue' || $maintStatus === 'warning') ? '1' : '0';

        $path = getLocationPath($allLocations, (int)$item['location_id']);
        $searchTerms = mb_strtolower($item['name'] . ' ' . ($item['location_name'] ?? '') . ' ' . $path . ' ' . ($item['loan_to'] ?? ''));
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
                                <i class="fa-solid fa-box text-primary me-1"></i>
                                <?= htmlspecialchars($item['name']) ?>
                               
                                <span class="d-inline-block">
                                    <span class="badge <?= $isLowStock ? 'bg-danger' : 'bg-secondary' ?> ms-1">
                                        x<?= $qty ?>
                                    </span>

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
                                <a class="text-decoration-none text-muted" role="button" data-bs-toggle="collapse" href="#locationPath<?= $itemId ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?>
                                </a>
                            </small>

                            <div class="collapse mt-1" id="locationPath<?= $itemId ?>">
                                <div class="small text-muted text-break">
                                    <?= htmlspecialchars($path) ?>
                                </div>
                            </div>
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
