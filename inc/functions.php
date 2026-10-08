<?php

/**
 * Generiert rekursiv <option>-Tags für ein <select>-Menü mit Einrückungen.
 */
function renderLocationOptions(array $locations, ?int $parentId = null, int $level = 0, ?int $selectedId = null): void
{
    foreach ($locations as $location) {
        if ((int)$location['parent_id'] !== (int)$parentId) {
            continue;
        }

        $selected = ((int)$location['id'] === (int)$selectedId) ? ' selected' : '';
        $indent = str_repeat('— ', $level);
        $name = htmlspecialchars($location['name']);

        echo "<option value=\"{$location['id']}\"{$selected}>{$indent}{$name}</option>";

        // Rekursiver Aufruf für Unterkategorien
        renderLocationOptions($locations, (int)$location['id'], $level + 1, $selectedId);
    }
}

/**
 * Gibt den Ort-Stammbaum als verschachtelte HTML-Liste aus.
 */
function renderLocationTree(array $locations, ?int $parentId = null, array $itemsByLocation = []): void
{
    $children = array_filter(
        $locations,
        fn($loc) => (int)$loc['parent_id'] === (int)$parentId
    );

    if (empty($children)) {
        return;
    }

    $ulClass = ($parentId === null) 
        ? 'list-group list-group-flush location-tree' 
        : 'list-group list-group-flush';

    echo '<ul class="' . $ulClass . '">';

    foreach ($children as $location) {
        $locationId = (int)$location['id'];
        
        echo '<li class="list-group-item">';
        
        // Standort-Zeile
        echo '<div class="d-flex justify-content-between align-items-center py-1">';
        echo '  <div class="text-truncate me-2">';
        echo '      <i class="fa-solid fa-folder-tree text-primary me-2"></i>';
        echo        htmlspecialchars($location['name']);
        echo '  </div>';

        // Gruppe für Aktions-Buttons (rechtsbündig durch ms-auto)
        echo '  <div class="btn-group btn-group-sm ms-auto" role="group">';
        echo '      <button type="button" class="btn btn-outline-secondary location-qr-btn" ';
        echo '          data-id="' . (int)$location['id'] . '" ';
        echo '          data-name="' . htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') . '" ';
        echo '          title="QR-Code anzeigen & drucken">';
        echo '          <i class="fa-solid fa-qrcode"></i>';
        echo '      </button>';
        echo '      <a href="#" class="btn btn-outline-danger location-delete-btn" ';
        echo '          data-id="' . $locationId . '" ';
        echo '          data-name="' . htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') . '" ';
        echo '          title="Lagerort löschen">';
        echo '          <i class="fa-solid fa-trash"></i>';
        echo '      </a>';
        echo '  </div>';
        echo '</div>';

        // Anzeige der Items an diesem Standort (standardmäßig versteckt via 'd-none')
        if (!empty($itemsByLocation[$locationId])) {
            echo '<ul class="list-group list-group-flush ms-4 my-1 location-items-list d-none">';
            foreach ($itemsByLocation[$locationId] as $item) {
                echo '<li class="list-group-item bg-light py-1 d-flex justify-content-between align-items-center small">';
                echo '<div>';
                echo '<i class="fa-solid fa-box text-secondary me-2"></i>';
                echo htmlspecialchars($item['name']);
                echo '</div>';
                echo '<a href="item.php?id=' . (int)$item['id'] . '" class="btn btn-xs btn-sm btn-outline-secondary" title="Item bearbeiten">';
                echo '<i class="fa-solid fa-pen-to-square"></i>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }

        // Rekursiver Aufruf für Unterordner
        renderLocationTree($locations, $locationId, $itemsByLocation);

        echo '</li>';
    }

    echo '</ul>';
}

/**
 * Ermittelt den vollständigen Pfad eines Orts (z. B. "Hauptlager → Regal 1 → Fach A").
 */
function getLocationPath(array $locations, int $locationId): string
{
    // Schnell-Lookup via ID-Keying für O(1) Zugriff statt geschachtelter Loop
    $locationsById = array_column($locations, null, 'id');
    $path = [];

    while ($locationId && isset($locationsById[$locationId])) {
        $current = $locationsById[$locationId];
        array_unshift($path, $current['name']);
        $locationId = (int)$current['parent_id'];
    }

    return implode(' → ', $path);
}

/**
 * Stellt sicher, dass ein Verzeichnis existiert und Schreibrechte besitzt.
 */
function ensureDirectoryExists(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}


/**
 * Protokolliert Aktionen und Bestandsänderungen im Inventar inkl. Benutzer
 */
function logInventoryAction(
    PDO $pdo,
    int $itemId,
    string $itemName,
    string $action,
    int $qtyChange = 0,
    int $newQty = 0,
    ?string $details = null,
    ?string $fieldChanged = null,
    ?string $oldValue = null,
    ?string $newValue = null,
    ?string $userName = 'System'
): bool {
    $stmt = $pdo->prepare("
        INSERT INTO inventory_logs 
        (item_id, item_name, action, field_changed, old_value, new_value, qty_change, new_qty, details, user_name, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    return $stmt->execute([
        $itemId,
        $itemName,
        $action,
        $fieldChanged,
        $oldValue,
        $newValue,
        $qtyChange,
        $newQty,
        $details,
        $userName
    ]);
}
