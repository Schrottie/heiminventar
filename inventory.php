<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Standorte für den Baum abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Alle Items abfragen und nach location_id gruppieren
$rawItems = $pdo->query("SELECT id, name, location_id FROM inventory_items ORDER BY name")->fetchAll();
$itemsByLocation = [];
foreach ($rawItems as $item) {
    $itemsByLocation[$item['location_id']][] = $item;
}

require_once __DIR__ . '/inc/header.php';

/**
 * Rekursive Ausgabe der Standortstruktur mit Drag-Container für Items
 */
function renderDragLocationTree(array $locations, ?int $parentId = null, array $itemsByLocation = []): void
{
    $children = array_filter(
        $locations,
        fn($loc) => (int)$loc['parent_id'] === (int)$parentId
    );

    if (empty($children) && $parentId === null) {
        echo '<p class="text-muted p-3 mb-0">Keine Standorte vorhanden.</p>';
        return;
    }

    if (empty($children)) {
        return;
    }

    // Unterscheidung: Root-Liste oder Verschachtelung
    $ulClass = ($parentId === null)
        ? 'location-tree'
        : 'location-children';

    echo '<ul class="' . $ulClass . '">';

    foreach ($children as $location) {
        $locationId = (int)$location['id'];
       
        echo '<li class="location-node py-1">';
       
        // Standort-Header
        echo '<div class="d-flex justify-content-between align-items-center py-1 px-2 rounded bg-white border-sm shadow-xs">';
        echo '  <div class="fw-bold text-primary">';
        echo '    <i class="fa-solid fa-folder-tree me-2 darkred"></i>' . htmlspecialchars($location['name']);
        echo '  </div>';
        echo '</div>';

        // Dropzone & Item-Liste für diesen Lagerort (Isoliert von der Baumstruktur)
        $items = $itemsByLocation[$locationId] ?? [];
        echo '<ul class="item-dropzone list-unstyled my-2 ms-3 p-2 rounded" data-location-id="' . $locationId . '" style="min-height: 42px;">';
       
        foreach ($items as $item) {
            echo '<li class="item-drag-node border-0 py-1 px-2 d-flex justify-content-between align-items-center bg-white mb-1 rounded shadow-sm" data-item-id="' . (int)$item['id'] . '">';
            echo '  <div class="d-flex align-items-center">';
            echo '    <i class="fa-solid fa-grip-vertical text-muted drag-handle me-2" style="cursor: grab;"></i>';
            echo '    <i class="fa-solid fa-box text-secondary me-2"></i>';
            echo '    <span>' . htmlspecialchars($item['name']) . '</span>';
            echo '  </div>';
            echo '</li>';
        }
       
        echo '</ul>';

        // Rekursiver Aufruf für untergeordnete Standorte
        renderDragLocationTree($locations, $locationId, $itemsByLocation);

        echo '</li>';
    }

    echo '</ul>';
}

?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-up-down-left-right me-2"></i>Inhalte per Drag & Drop verschieben</span>
                <span id="drag-status-badge" class="badge bg-secondary">Bereit</span>
            </div>
            <div class="card-body">
                <?php renderDragLocationTree($locations, null, $itemsByLocation); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
