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

// --- STARTSEITEN-LOGIK ---
// Wenn NUR das Verzeichnis bzw. index.php ohne GET-Parameter aufgerufen wurde, 
// prüfen wir die Einstellung, ob standardmäßig das Dashboard geladen werden soll.
$isPureDirectoryCall = empty($_GET) && basename($_SERVER['SCRIPT_NAME']) === 'index.php';
$startPageSetting = $settings['start_page'] ?? 'dashboard';

if ($isPureDirectoryCall && $startPageSetting === 'dashboard') {
    header('Location: dashboard.php');
    exit;
}

// --- MODUS-ERKENNUNG (Liste vs. Umfassende Suche) ---
// Entweder per URL-Parameter (?mode=list oder ?mode=search) oder aus den Systemeinstellungen
$defaultOverviewMode = $settings['overview_mode'] ?? 'filter';
$currentMode = $_GET['mode'] ?? $defaultOverviewMode;

if (!in_array($currentMode, ['list', 'search'])) {
    $currentMode = 'filter'; // Fallback
}

// Einstellung 'show_images' auslesen
$rawShowImages = $settings['show_images'] ?? '1';
$showImages = ($rawShowImages === '1');

// Alle Lagerorte für den Rekursionspfad und Filterung abfragen
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

// Gegenstände abfragen (Basis-Query für beide Modi nutzbar)
$params = []; $whereClause = '';

if (!empty($targetLocationIds)) {
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
    $whereClause = " AND i.location_id IN ($placeholders) ";
    $params = $targetLocationIds;
}

// Falls im Such-Modus ein Suchbegriff übergeben wurde
$searchQuery = trim($_GET['q'] ?? '');
if ($currentMode === 'search' && !empty($searchQuery)) {
    $whereClause .= " AND (i.name LIKE ? OR i.description LIKE ? OR i.manufacturer LIKE ? OR i.purchase_location LIKE ?) ";
    $searchTermLike = '%' . $searchQuery . '%';
    $params[] = $searchTermLike;
    $params[] = $searchTermLike;
    $params[] = $searchTermLike;
    $params[] = $searchTermLike;
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

// --- ANSTEHENDE INVENTUR / AUDIT ABRUFEN ---
$currentUserId = $_SESSION['user_id'] ?? 0;
$todayDate = date('Y-m-d');
$auditStmt = $pdo->prepare("
    SELECT * FROM audit_schedules 
    WHERE user_id = ? AND audit_date <= ? 
    ORDER BY audit_date ASC LIMIT 1
");
$auditStmt->execute([$currentUserId, $todayDate]);
$activeAudit = $auditStmt->fetch(PDO::FETCH_ASSOC);

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
        <a href="index.php?mode=<?= $currentMode ?>" class="btn btn-sm btn-outline-dark ms-2">
            <i class="fa-solid fa-xmark me-1"></i>Aufheben
        </a>
    </div>
<?php endif; ?>

<!-- Meldungsbanner -->
<?php if (($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0 || $activeAudit !== false) && (!isset($_COOKIE['hide_daily_notice']) || $_COOKIE['hide_daily_notice'] !== date('Y-m-d'))): ?>
    <div id="dailyNoticeBanner" class="border rounded-3 p-2 px-3 mb-3 bg-body-tertiary small text-body-secondary shadow-none" style="font-size: 0.875rem;">
        <div class="d-flex align-items-center justify-content-between pb-1 mb-2">
            <div class="fw-bold text-secondary">
                <i class="fa-solid fa-circle-info text-danger me-1"></i> Meldung des Tages
            </div>
            <div>
                <button type="button" class="btn-close" style="font-size: 0.65rem;" onclick="hideDailyNotice()" title="Heute nicht mehr anzeigen"></button>
            </div>
        </div>

        <ul class="mb-1 ps-3 text-secondary">
            <?php if ($activeAudit): ?>
                <?php 
                    $auditLink = 'inventory-audit.php';
                    if (!empty($activeAudit['location_id'])) {
                        $auditLink .= '?location_id=' . (int)$activeAudit['location_id'];
                    }
                ?>
                <li>
                    Geplante Inventur fällig: <strong><?= htmlspecialchars($activeAudit['title']) ?></strong> (Datum: <?= date('d.m.Y', strtotime($activeAudit['audit_date'])) ?>) 
                    — <a href="<?= $auditLink ?>" class="fw-semibold text-decoration-underline">Zur Inventur (Lagerort öffnen)</a>
                </li>
            <?php endif; ?>
            <?php if ($lowStockCount > 0): ?>
                <li><?= $lowStockCount === 1 ? 'Anstehende Nachbestellung: <strong>1 Gegenstand</strong>' : 'Anstehende Nachbestellungen: <strong>' . $lowStockCount . ' Gegenstände</strong>' ?></li>
            <?php endif; ?>
            <?php if ($expiredCount > 0): ?>
                <li><?= $expiredCount === 1 ? 'Ablauf- oder Verfallsdatum erreicht: <strong>1 Gegenstand</strong>' : 'Ablauf- oder Verfallsdatum erreicht: <strong>' . $expiredCount . ' Gegenstände</strong>' ?></li>
            <?php endif; ?>
            <?php if ($maintenanceCount > 0): ?>
                <li><?= $maintenanceCount === 1 ? 'Fällige Wartung: <strong>1 Gegenstand</strong>' : 'Fällige Wartungen: <strong>' . $maintenanceCount . ' Gegenstände</strong>' ?></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Je nach eingestelltem Modus (Liste oder Umfassende Suche) die entsprechende Datei einbinden -->
<?php 
if ($currentMode === 'search') {
    require_once __DIR__ . '/inc/index/search.php';
} else {
    require_once __DIR__ . '/inc/index/list.php';
}
?>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
