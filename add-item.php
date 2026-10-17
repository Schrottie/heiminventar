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
            
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Bezeichnung</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hersteller</label>
                    <input type="text" name="manufacturer" class="form-control" placeholder="z.B. Bosch, Makita">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="row g-3 mb-3 align-items-start">
                <div class="col-md-3">
                    <label class="form-label text-danger fw-bold">Lagerort</label>
                    <select name="location_id" class="form-select">
                        <option value="">Bitte auswählen...</option>
                        <?php renderLocationOptions($locations, null, 0, (int)$item['location_id']); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mindestbestand</label>
                    <input type="number" name="min_quantity" class="form-control" min="0" value="<?= (int)($item['min_quantity'] ?? 0) ?>">
                    <div class="form-text">0 = Kein Mindestbestand</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Aktuelle Menge</label>
                    <input type="number" name="quantity" class="form-control" min="0" value="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block text-truncate">&nbsp;</label>
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="isBulkCheck" name="is_bulk" value="1" <?= !empty($item['is_bulk']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold small" for="isBulkCheck">Schüttgut / Massenartikel</label>
                    </div>
                    <div class="form-text" style="font-size: 0.75rem;">Keine Einzelverfolgung</div>
                </div>
            </div>

            <!-- Sektion: Anschaffung & Versicherung -->
            <div class="card bg-light border-0 p-3 mb-3">
                <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Anschaffung &amp; Versicherung</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="price" class="form-label">Anschaffungspreis (€)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="purchase_date" class="form-label">Kaufdatum</label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                    </div>
                    <div class="col-md-3">
                        <label for="purchase_location" class="form-label">Kaufort / Händler</label>
                        <input type="text" class="form-control" id="purchase_location" name="purchase_location" placeholder="z.B. Hornbach Berlin">
                    </div>
                    <div class="col-md-3">
                        <label for="warranty_months" class="form-label">Garantie (Monate)</label>
                        <input type="number" min="0" class="form-control" id="warranty_months" name="warranty_months" placeholder="z.B. 24">
                    </div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_insured" name="is_insured">
                        <label class="form-check-label fw-semibold text-warning-emphasis" for="is_insured">
                            Für Versicherungsliste markieren (versicherungsrelevant)
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Ablaufdatum -->
                <div class="col-md-4">
                    <label for="expiration_date" class="form-label">Ablaufdatum / Verfallsdatum</label>
                    <input type="date" class="form-control" id="expiration_date" name="expiration_date">
                </div>

                <!-- Wartungsintervall in Monaten -->
                <div class="col-md-4">
                    <label for="maintenance_interval_months" class="form-label">Wartungsintervall (Monate)</label>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control" id="maintenance_interval_months" name="maintenance_interval_months" placeholder="z.B. 12">
                        <span class="input-group-text">Monate</span>
                    </div>
                </div>

                <!-- Datum der letzten Wartung -->
                <div class="col-md-4">
                    <label for="last_maintenance_date" class="form-label">Letzte Wartung am</label>
                    <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fa-solid fa-paperclip me-1"></i>Dokumente / Anleitungen hinzufügen (PDF, DOCX)</label>
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="file" name="documents[]" class="form-control" accept=".pdf,.doc,.docx,.txt" multiple>
                    </div>
                    <div class="col-md-4">
                        <select name="doc_type" class="form-select">
                            <option value="sonstiges" selected>Sonstiges</option>
                            <option value="rechnung">Rechnung</option>
                            <option value="bedienungsanleitung">Bedienungsanleitung</option>
                        </select>
                    </div>
                </div>
                <div class="form-text">Der ausgewählte Dokumenttyp gilt für alle hier hochgeladenen Dateien.</div>
            </div>

            <!-- Bilder-Upload-Feld -->
            <div class="mb-3">
                <label class="form-label">Bilder hinzufügen</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <div class="form-text">
                    <i class="fa-solid fa-circle-info me-1"></i> Das erste ausgewählte Bild wird automatisch als Hauptbild für die Übersicht verwendet.
                </div>
            </div>

            <!-- Sektion: Set-Verwaltung (Neu anlegen) -->
            <div class="card bg-light border-0 p-3 mb-3">
                <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-layer-group me-2"></i>Set-Verwaltung</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="is_set" name="is_set">
                    <label class="form-check-label fw-semibold text-dark" for="is_set">
                        Dieser Gegenstand ist ein Set (enthält mehrere Inhaltsteile)
                    </label>
                </div>
                <div id="setManageButtonContainer" class="d-none">
                    <div class="form-text text-muted mt-1">
                        <i class="fa-solid fa-circle-info me-1"></i>Hinweis: Sie können die Set-Inhalte hinzufügen, sobald der Gegenstand einmalig gespeichert wurde.
                    </div>
                </div>
            </div>

            <!-- Schaltflächen zum Speichern -->
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="save" class="btn btn-success flex-fill">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Speichern
                </button>
                <button type="submit" name="action" value="save_and_next" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-square-plus me-2"></i>Speichern &amp; Nächster
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
