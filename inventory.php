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
        echo '<p class="text-muted">Keine Standorte vorhanden.</p>';
        return;
    }

    if (empty($children)) {
        return;
    }

    $ulClass = ($parentId === null) 
        ? 'list-group list-group-flush location-tree' 
        : 'list-group list-group-flush';

    echo '<ul class="' . $ulClass . '">';

    foreach ($children as $location) {
        $locationId = (int)$location['id'];
        
        echo '<li class="list-group-item border-0 px-2 py-2">';
        
        // Standort-Header
        echo '<div class="d-flex justify-content-between align-items-center mb-1">';
        echo '  <div class="fw-bold text-primary">';
        echo '    <i class="fa-solid fa-folder-tree me-2"></i>' . htmlspecialchars($location['name']);
        echo '  </div>';
        echo '</div>';

        // Dropzone & Item-Liste für diesen Lagerort
        $items = $itemsByLocation[$locationId] ?? [];
        echo '<ul class="list-group list-group-flush item-dropzone my-1 ms-3 p-1 rounded bg-light border" data-location-id="' . $locationId . '" style="min-height: 38px;">';
        
        foreach ($items as $item) {
            echo '<li class="list-group-item border-0 py-1 px-2 d-flex justify-content-between align-items-center bg-white mb-1 rounded border-sm item-drag-node" data-item-id="' . (int)$item['id'] . '">';
            echo '  <div class="d-flex align-items-center">';
            // Greifer-Icon (Grip)
            echo '    <i class="fa-solid fa-grip-vertical text-muted drag-handle me-2" style="cursor: grab;"></i>';
            echo '    <i class="fa-solid fa-box text-secondary me-2"></i>';
            echo '    <span>' . htmlspecialchars($item['name']) . '</span>';
            echo '  </div>';
            // echo '  <a href="item.php?id=' . (int)$item['id'] . '" class="btn btn-xs btn-sm btn-outline-secondary" title="Bearbeiten">';
            // echo '    <i class="fa-solid fa-pen-to-square"></i>';
            // echo '  </a>';
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

<?php require_once __DIR__ . '/inc/footer.php'; ?>
