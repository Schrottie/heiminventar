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
        <!-- WICHTIG: enctype="multipart/form-data" für Datei-Uploads -->
        <form action="api/item-save.php" method="post" enctype="multipart/form-data">
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

            <div class="mb-3">
                <label class="form-label"><i class="fa-solid fa-paperclip me-1"></i>Dokumente / Anleitungen hinzufügen (PDF, DOCX)</label>
                <input type="file" name="documents[]" class="form-control" accept=".pdf,.doc,.docx,.txt" multiple>
            </div>

            <!-- Bilder-Upload-Feld -->
            <div class="mb-3">
                <label class="form-label">Bilder hinzufügen</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <div class="form-text">
                    <i class="fa-solid fa-circle-info me-1"></i> Das erste ausgewählte Bild wird automatisch als Hauptbild für die Übersicht verwendet.
                </div>
            </div>

            <!-- Schaltflächen zum Speichern -->
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="save" class="btn btn-success flex-fill">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Speichern
                </button>
                <button type="submit" name="action" value="save_and_next" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-square-plus me-2"></i>Speichern und nächster Gegenstand
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
