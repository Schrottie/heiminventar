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

require_once __DIR__ . '/inc/header.php';
?>

<div class="row">
    <!-- Formular: Neuer Standort -->
    <div class="col-12 col-lg-4">
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
    </div>

    <!-- Übersicht: Standortstruktur (Baumansicht) -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
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

<!-- JavaScript zum Umschalten der Anzeige per CSS-Klasse -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleSwitch = document.getElementById('toggleItemsSwitch');
    
    toggleSwitch.addEventListener('change', function() {
        const itemLists = document.querySelectorAll('.location-items-list');
        itemLists.forEach(el => {
            if (this.checked) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
