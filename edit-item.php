<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Lagerorte für das Dropdown-Menü abfragen
$stmt = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name");
$locations = $stmt->fetchAll();

require_once __DIR__ . '/inc/header.php';

?>

<!-- Formular-Karte: Gegenstand anlegen -->
<div class="card shadow-sm">
    <div class="card-header">
        <i class="fa-solid fa-plus me-2"></i>Gegenstand anlegen
    </div>

    <div class="card-body">
        <form action="api/item-save.php" method="post">
            <div class="mb-3">
                <label class="form-label">Bezeichnung</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Menge</label>
                <input type="number" name="quantity" class="form-control" min="1" value="1">
            </div>

            <div class="mb-3">
                <label class="form-label">Lagerort</label>
                <select name="location_id" class="form-select">
                    <option value="">Bitte auswählen...</option>
                    <?php renderLocationOptions($locations); ?>
                </select>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
