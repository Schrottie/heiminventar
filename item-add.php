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
<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="fa-solid fa-plus me-2"></i>Gegenstand anlegen
            </div>

            <div class="card-body p-4">
                <form action="api/item-save.php" method="post">
                   
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold">Bezeichnung</label>
                            <input type="text" name="name" class="form-control" required autofocus>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold">Hersteller</label>
                            <input type="text" name="manufacturer" class="form-control" placeholder="z.B. Bosch, Makita">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Beschreibung</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optionale Details zum Gegenstand..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-danger">Lagerort</label>
                        <select name="location_id" class="form-select">
                            <option value="">Bitte auswählen...</option>
                            <?php renderLocationOptions($locations, null, 0, 0); ?>
                        </select>
                    </div>

                    <!-- Hinweis nach dem Speichern -->
                    <div class="alert alert-info py-2 px-3 small mb-4">
                        <i class="fa-solid fa-circle-info me-1"></i> Nach dem Speichern können umfassende Angaben (Preise, Dokumente, Bilder, Wartung etc.) direkt auf der Detailseite ergänzt werden.
                    </div>

                    <!-- Schaltflächen zum Speichern -->
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="save" class="btn btn-success flex-fill">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Speichern &amp; Details öffnen
                        </button>
                        <button type="submit" name="action" value="save_and_next" class="btn btn-primary flex-fill">
                            <i class="fa-solid fa-square-plus me-2"></i>Speichern &amp; Nächster
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
