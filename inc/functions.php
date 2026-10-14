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

        // Gruppe für Aktions-Buttons 
        echo '  <div class="d-flex align-items-center gap-1 ms-auto" role="group">';
        
        // Bearbeiten-Button
        echo '      <button type="button" class="btn btn-sm btn-outline-warning" onclick="openEditLocationModal(' . $locationId . ', \'' . htmlspecialchars($location['name'], ENT_QUOTES) . '\', \'' . (!empty($location['parent_id']) ? (int)$location['parent_id'] : '') . '\')" title="Bearbeiten">';
        echo '          <i class="fa-solid fa-pen"></i>';
        echo '      </button>';

        // QR-Code Button
        echo '      <button type="button" class="btn btn-sm btn-outline-secondary location-qr-btn" ';
        echo '          data-id="' . $locationId . '" ';
        echo '          data-name="' . htmlspecialchars($location['name'], ENT_QUOTES, 'UTF-8') . '" ';
        echo '          title="QR-Code anzeigen & drucken">';
        echo '          <i class="fa-solid fa-qrcode"></i>';
        echo '      </button>';

        // Löschen-Button
        echo '      <a href="#" class="btn btn-sm btn-outline-danger location-delete-btn" ';
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

if (!function_exists('getMaintenanceStatus')) {
    function getMaintenanceStatus($monthsInterval, $lastDate) {
        if (!$monthsInterval || $monthsInterval <= 0) return 'none';
        if (!$lastDate) return 'overdue';
        
        $nextDueDate = date('Y-m-d', strtotime($lastDate . " + $monthsInterval months"));
        $today = date('Y-m-d');
        
        if ($nextDueDate < $today) {
            return 'overdue';
        } elseif (strtotime($nextDueDate) <= strtotime('+14 days')) {
            return 'warning';
        }
        return 'ok';
    }
}

/**
 * Holt alle Tags für einen bestimmten Gegenstand
 */
function getItemTags(PDO $pdo, int $itemId): array {
    $stmt = $pdo->prepare("
        SELECT t.id, t.name 
        FROM tags t
        JOIN item_tags it ON it.tag_id = t.id
        WHERE it.item_id = ?
        ORDER BY t.name
    ");
    $stmt->execute([$itemId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Speichert und verknüpft Tags für einen Gegenstand
 */
function saveItemTags(PDO $pdo, int $itemId, string $tagsString): void {
    // Zuerst alle bestehenden Verknüpfungen für diesen Artikel löschen
    $stmtDel = $pdo->prepare("DELETE FROM item_tags WHERE item_id = ?");
    $stmtDel->execute([$itemId]);

    if (empty(trim($tagsString))) return;

    // Tags anhand von Kommas oder Leerzeichen trennen (hier flexibel nach Komma)
    $tagNames = array_map('trim', explode(',', $tagsString));

    foreach ($tagNames as $name) {
        if (empty($name)) continue;

        // Prüfen, ob der Tag bereits existiert (Case-insensitive / exakt)
        $stmtCheck = $pdo->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(?)");
        $stmtCheck->execute([$name]);
        $tag = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($tag) {
            $tagId = $tag['id'];
        } else {
            // Neu anlegen
            $stmtIns = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
            $stmtIns->execute([$name]);
            $tagId = $pdo->lastInsertId();
        }

        // Verknüpfung in item_tags erstellen (mit Fehlerbehandlung für Dubletten)
        try {
            $stmtLink = $pdo->prepare("INSERT IGNORE INTO item_tags (item_id, tag_id) VALUES (?, ?)");
            $stmtLink->execute([$itemId, $tagId]);
        } catch (Exception $e) {
            // Ignorieren falls bereits verknüpft
        }
    }
}

/**
 * Synchronisiert die App-Releases aus einer JSON-Datei in die Datenbank.
 */
function syncAppReleases(PDO $pdo): void {
    $jsonFile = __DIR__ . '/../data/releases.json'; // Pfad anpassen falls nötig
    if (!file_exists($jsonFile)) return;

    $releases = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($releases)) return;

    $stmt = $pdo->prepare("
        INSERT INTO app_releases (version, title, release_date, content, is_active)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            title = VALUES(title), 
            release_date = VALUES(release_date), 
            content = VALUES(content), 
            is_active = VALUES(is_active)
    ");

    foreach ($releases as $rel) {
        $stmt->execute([
            $rel['version'],
            $rel['title'],
            $rel['release_date'] ?? null,
            $rel['content'],
            $rel['is_active'] ?? 1
        ]);
    }
}
