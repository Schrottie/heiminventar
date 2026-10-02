<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Standorte für das Formular und den Baum abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Alle Items abfragen und nach location_id gruppieren
$rawItems = $pdo->query("SELECT id, name, location_id FROM inventory_items ORDER BY name")->fetchAll();
$itemsByLocation = [];
foreach ($rawItems as $item) {
    $itemsByLocation[$item['location_id']][] = $item;
}

// Array mit IDs aller Standorte erstellen, die Gegenstände enthalten (für die Validierung)
$nonEmptyLocationIds = array_keys($itemsByLocation);

// Hilfsobjekt erstellen, das speichert, welche Standorte Unterlagerorte besitzen
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
        <!-- Card 1: Neuer Standort -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">Neuer Standort</div>
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

        <!-- Card 2: Umlagern (Desktop: unter 'Neuer Standort', Mobil: ganz unten durch Order 3 auf col-12) -->
        <div class="card shadow-sm mb-3 order-3 order-lg-2">
            <div class="card-header">
                <i class="fa-solid fa-boxes-packing me-1"></i>Umlagern
            </div>
            <div class="card-body">
                <form id="relocateForm" action="api/location-relocate.php" method="post">
                    <!-- Verstecktes Feld für Modalauswahl (Sub-Locations einbeziehen) -->
                    <input type="hidden" name="include_sublocations" id="includeSublocationsInput" value="0">

                    <div class="mb-3">
                        <label class="form-label">Quell-Lagerort</label>
                        <select name="source_id" id="sourceLocationSelect" class="form-select" required>
                            <option value="">Bitte auswählen...</option>
                            <?php foreach ($locations as $loc): ?>
                                <?php 
                                    $itemCount = count($itemsByLocation[$loc['id']] ?? []);
                                    $disabled = ($itemCount === 0) ? 'disabled' : '';
                                ?>
                                <option value="<?= (int)$loc['id'] ?>" <?= $disabled ?> data-has-children="<?= isset($hasChildrenMap[$loc['id']]) ? '1' : '0' ?>">
                                    <?= htmlspecialchars($loc['name']) ?> <?= $itemCount > 0 ? "({$itemCount} Items)" : '(leer)' ?>
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

    <!-- Rechte Spalte: Standortstruktur (Order 2 auf Desktop, rutscht auf Mobil vor 'Umlagern') -->
    <div class="col-12 col-lg-8 order-2">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Standortstruktur</span>
                <!-- Umschalter für die Anzeige der Inhalte -->
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

<!-- Modal: Nachfrage bei vorhanden Unterlagerorten -->
<div class="modal fade" id="relocateSublocationsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fa-solid fa-sitemap me-2"></i>Unterlagerorte vorhanden
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Der gewählte Quell-Lagerort besitzt untergeordnete Lagerorte.</p>
                <p class="fw-bold mb-0">Wie soll mit der Umlagerung verfahren werden?</p>
            </div>
            <div class="modal-footer d-flex flex-column gap-2">
                <!-- Option A: Nur Gegenstände des Quellorts -->
                <button type="button" class="btn btn-primary w-100" data-mode="items_only">
                    Nur Gegenstände aus Quellort verschieben (Standard)
                </button>
                <!-- Option B: Auch Gegenstände aus Unterlagerorten mitnehmen -->
                <button type="button" class="btn btn-outline-primary w-100" data-mode="items_with_sub">
                    Gegenstände der Unterlagerorte ebenfalls mit verschieben (Orte bleiben)
                </button>
                <!-- Option C: Kompletten Zweig (Orte + Gegenstände) verschieben -->
                <button type="button" class="btn btn-outline-warning w-100 text-dark" data-mode="move_all">
                    Unterlagerorte samt Inhalten verschieben (Struktur mitverschieben)
                </button>
                <button type="button" class="btn btn-secondary btn-sm w-100 mt-1" data-bs-dismiss="modal">
                    Abbrechen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Lagerort löschen (bereits vorhanden) -->
<div class="modal fade" id="deleteLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lagerort löschen?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="deleteLocationName"></strong></p>
                <div id="deleteLocationInfo">Wird geladen...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteLocation">Löschen</a>
            </div>
        </div>
    </div>
</div>

<script>

</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
