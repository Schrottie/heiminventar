<?php
// /inc/import-export/import-card.php
?>
<div class="card shadow-sm h-100">
    <div class="card-header bg-transparent">
        <i class="fa-solid fa-file-import text-primary me-2"></i>Inventar importieren
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Lade eine passende CSV-Datei hoch, um Gegenstände hinzuzufügen oder bestehende anhand ihrer Bezeichnung zu aktualisieren.
        </p>
        <form action="import-export.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="csv_file" class="form-label">CSV-Datei auswählen</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
           
            <div class="mb-3">
                <label for="default_location_id" class="form-label">Standard-Lagerort (für neue Gegenstände)</label>
                <select class="form-select" id="default_location_id" name="default_location_id">
                    <option value="">-- Kein Standard-Lagerort --</option>
                    <?php foreach ($allLocations as $loc): ?>
                        <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text small">Wird verwendet, wenn die CSV keine Lagerort-Angabe enthält.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fa-solid fa-upload me-2"></i>Daten importieren
            </button>
        </form>
    </div>
</div>
