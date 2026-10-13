<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// 1. Alle Lagerorte ungeordnet abfragen
$locationsRaw = $pdo->query("SELECT id, parent_id, name FROM locations")->fetchAll(PDO::FETCH_ASSOC);

// Hilfsfunktion: Lagerorte hierarchisch sortieren und Tiefe (depth) berechnen
function sortLocationsHierarchically(array $locations, $parentId = null, int $depth = 0): array {
    $result = [];
    $children = array_filter($locations, function($loc) use ($parentId) {
        $locParent = empty($loc['parent_id']) ? null : (int)$loc['parent_id'];
        $targetParent = empty($parentId) ? null : (int)$parentId;
        return $locParent === $targetParent;
    });

    usort($children, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    foreach ($children as $child) {
        $child['depth'] = $depth;
        $result[] = $child;
        $result = array_merge($result, sortLocationsHierarchically($locations, $child['id'], $depth + 1));
    }

    return $result;
}

$locations = sortLocationsHierarchically($locationsRaw);

// Alle Items abfragen und nach location_id gruppieren
$rawItems = $pdo->query("SELECT id, name, location_id FROM inventory_items WHERE is_archived = '0' ORDER BY name")->fetchAll();
$itemsByLocation = [];
foreach ($rawItems as $item) {
    $itemsByLocation[$item['location_id']][] = $item;
}

$nonEmptyLocationIds = array_keys($itemsByLocation);

$hasChildrenMap = [];
foreach ($locations as $loc) {
    if (!empty($loc['parent_id'])) {
        $hasChildrenMap[$loc['parent_id']] = true;
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="row">
    <!-- Linke Spalte auf Desktop (Order 1) -->
    <div class="col-12 col-lg-4 order-1">
        <!-- Card 1: Neuer Lagerort -->
        <div class="card shadow-sm mb-3">
            <div class="card-header"><i class="fa-solid fa-circle-plus me-1"></i>Neuer Lagerort</div>
            <div class="card-body">
                <form action="api/location-save.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Bezeichnung</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Übergeordnet</label>
                        <select name="parent_id" class="form-select">
                            <option value="">- Oberste Ebene -</option>
                            <?php renderLocationOptions($locations); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-plus me-1"></i>Anlegen
                    </button>
                </form>
            </div>
        </div>

        <!-- Card 2: Umlagern -->
        <div class="card shadow-sm mb-3 order-3 order-lg-2">
            <div class="card-header">
                <i class="fa-solid fa-boxes-packing me-1"></i>Umlagern
            </div>
            <div class="card-body">
                <form id="relocateForm" action="api/location-relocate.php" method="post">
                    <input type="hidden" name="include_sublocations" id="includeSublocationsInput" value="0">

                    <div class="mb-3">
                        <label class="form-label">Quell-Lagerort</label>
                        <select name="source_id" id="sourceLocationSelect" class="form-select" required>
                            <option value="">Bitte auswählen...</option>
                            <?php foreach ($locations as $loc): ?>
                                <?php
                                    $itemCount = count($itemsByLocation[$loc['id']] ?? []);
                                    $disabled = ($itemCount === 0) ? 'disabled' : '';
                                    $indent = $loc['depth'] > 0 ? str_repeat('— ', $loc['depth']) . ' ' : '';
                                ?>
                                <option value="<?= (int)$loc['id'] ?>" <?= $disabled ?> data-has-children="<?= isset($hasChildrenMap[$loc['id']]) ? '1' : '0' ?>">
                                    <?= $indent ?><?= htmlspecialchars($loc['name']) ?> <?= $itemCount > 0 ? "({$itemCount} Items)" : '(leer)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Leere Lagerorte sind deaktiviert.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ziel-Lagerort</label>
                        <select name="target_id" id="targetLocationSelect" class="form-select" required>
                            <option value="">Bitte auswählen...</option>
                            <?php renderLocationOptions($locations); ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fa-solid fa-truck-ramp-box me-1"></i>Jetzt umlagern
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte: Lagerortstruktur -->
    <div class="col-12 col-lg-8 order-2 order-lg-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-folder-tree me-1"></i>Lagerortstruktur</span>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleItemsSwitch" style="cursor: pointer;">
                    <label class="form-check-label small text-muted" for="toggleItemsSwitch" style="cursor: pointer;">Inhalte anzeigen</label>
                </div>
            </div>
            <div class="card-body">
                <?php renderLocationTree($locations, null, $itemsByLocation); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
