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
 * Gibt den Ort-Stammbaum als verschachtelte HTML-Liste (Bootstrap List-Group) aus.
 */
function renderLocationTree(array $locations, ?int $parentId = null): void
{
    $children = array_filter($locations, fn($loc) => (int)$loc['parent_id'] === (int)$parentId);

    if (empty($children)) {
        return;
    }

    echo '<ul class="list-group list-group-flush">';
    foreach ($children as $location) {
        echo '<li class="list-group-item">';
        echo '<i class="fa-solid fa-folder-tree text-primary me-2"></i>' . htmlspecialchars($location['name']);

        // Rekursiver Aufruf für Kinder-Elemente
        renderLocationTree($locations, (int)$location['id']);

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
