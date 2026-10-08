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
    // Hilfsfunktion zum Aufsammeln aller Kind-IDs
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

// Gegenstände inkl. Lagerort-Name, Hauptbild & min_quantity abfragen
$params = [];
$whereClause = '';

if (!empty($targetLocationIds)) {
    // Dynamische Platzhalter für IN(...) erzeugen
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
    ORDER BY i.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Zählen, wie viele Artikel den Mindestbestand unterschreiten
$lowStockCount = 0;
foreach ($items as $chk) {
    if ((int)$chk['min_quantity'] > 0 && (int)$chk['quantity'] <= (int)$chk['min_quantity']) {
        $lowStockCount++;
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<?php if ($filterLocationId !== null): ?>
    <?php 
        // Name des aktiven Lagerorts ermitteln
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
<!-- Schnellfilter / Suchleiste & Low-Stock-Warnung -->
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
</div>


<!-- Inventarliste -->
<div id="inventoryList">
    <?php foreach ($items as$item): ?>
        <?php
        $itemId = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $minQty = (int)$item['min_quantity'];
        $isLowStock = ($minQty > 0 && $qty <=$minQty);

        $path = getLocationPath($allLocations, (int)$item['location_id']);$searchTerms = mb_strtolower($item['name'] . ' ' . ($item['location_name'] ?? '') . ' ' . $path);$hasImage = $showImages && !empty($item['image_filename']);
        ?>

        <div class="card shadow-sm mb-2 inventory-item <?= $isLowStock ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : '' ?>"
             data-search="<?= htmlspecialchars($searchTerms) ?>"
             data-low-stock="<?= $isLowStock ? '1' : '0' ?>">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                   
                    <div class="d-flex align-items-center me-3 flex-grow-1 min-w-0">
                        <?php if ($hasImage): ?>
                            <div class="me-3 flex-shrink-0">
                                <img src="img/items/<?= htmlspecialchars($item['image_filename']) ?>"
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     class="rounded object-fit-cover"
                                     style="width: 50px; height: 50px;">
                            </div>
                        <?php endif; ?>

                        <div class="text-truncate">
                            <h6 class="mb-1 text-truncate ">
                                <i class="fa-solid fa-box text-primary me-1"></i>
                                <?= htmlspecialchars($item['name']) ?>
                               
                                <!-- Mengen-Badges -->
                                <span class="badge <?= $isLowStock ? 'bg-danger' : 'bg-secondary' ?> ms-1">
                                    x<?= $qty ?>
                                </span>

                                <?php if ($isLowStock): ?>
                                    <span class="badge bg-warning text-dark ms-1" title="Mindestbestand: <?= $minQty ?>">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Min: <?= $minQty ?>
                                    </span>
                                <?php endif; ?>
                            </h6>

                            <small class="d-block text-truncate">
                                <a class="text-decoration-none text-muted" role="button" data-bs-toggle="collapse" href="#locationPath<?= $itemId ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?>
                                </a>
                            </small>

                            <div class="collapse mt-1" id="locationPath<?= $itemId ?>">
                                <div class="small text-muted">
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

<!-- Kombinierte Filter-Logik für Textsuche & Nachbestell-Button -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const quickFilterInput = document.getElementById('quickFilter');
    const lowStockBtn = document.getElementById('toggleLowStock');
    let lowStockActive = false;

    function applyFilters() {
        const query = quickFilterInput ? quickFilterInput.value.toLowerCase().trim() : '';
       
        document.querySelectorAll('.inventory-item').forEach(item => {
            const searchData = (item.getAttribute('data-search') || '').toLowerCase();
            const isLowStock = item.getAttribute('data-low-stock') === '1';

            const matchesSearch = query === '' || searchData.includes(query);
            const matchesLowStock = !lowStockActive || isLowStock;

            if (matchesSearch && matchesLowStock) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (quickFilterInput) {
        quickFilterInput.addEventListener('input', applyFilters);
    }

    if (lowStockBtn) {
        lowStockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            lowStockActive = !lowStockActive;
            lowStockBtn.classList.toggle('btn-danger', lowStockActive);
            lowStockBtn.classList.toggle('btn-outline-danger', !lowStockActive);
            applyFilters();
        });
    }
});
</script>
            
<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
