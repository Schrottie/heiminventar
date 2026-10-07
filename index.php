<?php

// Prüfen, ob das System überhaupt installiert ist
if (!file_exists(__DIR__ . '/cfg/.env')) {
    header('Location: install.php');
    exit;
}

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Einstellungen explizit aus der DB holen (falls $settings in functions.php/db.php nicht gefüllt wird)
if (!isset($settings)) {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

// Einstellung 'show_images' auslesen: Exakte Prüfung auf '1' oder 1
$rawShowImages = $settings['show_images'] ?? '1';
$showImages = ($rawShowImages === '1' || $rawShowImages === 1);

// Gegenstände inkl. zugewiesenem Lagerort-Namen und ERSTEM Bild abfragen
$sql = "
    SELECT 
        i.id, 
        i.name, 
        i.quantity, 
        i.location_id, 
        l.name AS location_name,
        img.filename AS image_filename
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    LEFT JOIN (
        SELECT item_id, MIN(id) AS min_id
        FROM item_images
        GROUP BY item_id
    ) first_img ON first_img.item_id = i.id
    LEFT JOIN item_images img ON img.id = first_img.min_id
    ORDER BY i.name
";
$items = $pdo->query($sql)->fetchAll();

// Alle Lagerorte für den Rekursionspfad (getLocationPath) abfragen
$allLocations = $pdo->query("SELECT id, parent_id, name FROM locations")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>

<!-- Schnellfilter / Suchleiste -->
<div class="row mb-3">
    <div class="col-12">
        <div class="input-group">
            <span class="input-group-text">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="quickFilter" class="form-control" placeholder="Inventar durchsuchen...">
        </div>
    </div>
</div>

<!-- Inventarliste -->
<div id="inventoryList">
    <?php foreach ($items as $item): ?>
        <?php
        $itemId = (int)$item['id'];
        $path = getLocationPath($allLocations, (int)$item['location_id']);
        $searchTerms = mb_strtolower($item['name'] . ' ' . ($item['location_name'] ?? ''));
        // Hier greift die korrigierte Variable:
        $hasImage = $showImages && !empty($item['image_filename']);
        ?>

        <div class="card shadow-sm mb-2 inventory-item" data-search="<?= htmlspecialchars($searchTerms) ?>">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    
                    <div class="d-flex align-items-center me-3 flex-grow-1 min-w-0">
                        <!-- Miniatur-Vorschau nur wenn $showImages TRUE ist UND ein Bild existiert -->
                        <?php if ($hasImage): ?>
                            <div class="me-3 flex-shrink-0">
                                <img src="img/items/<?= htmlspecialchars($item['image_filename']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="rounded object-fit-cover" 
                                     style="width: 50px; height: 50px;">
                            </div>
                        <?php endif; ?>

                        <!-- Textinhalt -->
                        <div class="text-truncate">
                            <h6 class="mb-1 text-truncate">
                                <i class="fa-solid fa-box text-primary me-1"></i>
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if ((int)$item['quantity'] > 1): ?>
                                    <span class="badge bg-secondary ms-1">x<?= (int)$item['quantity'] ?></span>
                                <?php endif; ?>
                            </h6>

                            <!-- Lagerort-Link -->
                            <small class="d-block text-truncate">
                                <a class="text-decoration-none text-muted" role="button" data-bs-toggle="collapse" href="#locationPath<?= $itemId ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?>
                                </a>
                            </small>

                            <!-- Aufklappbarer Lagerortpfad -->
                            <div class="collapse mt-1" id="locationPath<?= $itemId ?>">
                                <div class="small text-muted">
                                    <?= htmlspecialchars($path) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details-Link -->
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
