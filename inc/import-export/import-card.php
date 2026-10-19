<?php
// /inc/import-export/import-card.php
?>
<div class="card shadow-sm h-100">
    <div class="card-header bg-transparent">
        <i class="fa-solid fa-file-import text-primary me-2"></i>CSV-Inventarimport
    </div>
    <div class="card-body d-flex flex-column justify-content-between">
        <div>
            <p class="text-muted small">
                Lade eine passende CSV-Datei hoch, um Gegenstände sowie deren Anbau- und Zubehörteile hinzuzufügen oder zu aktualisieren.
            </p>
        </div>
        
        <div class="d-flex flex-column gap-2">
            <form action="api/import-csv.php" method="post" enctype="multipart/form-data">
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
                    <div class="form-text small">Wird verwendet, wenn die CSV keine eigene Lagerort-Angabe enthält.</div>
                </div>

                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fa-solid fa-upload me-2"></i>Daten importieren
                </button>
            </form>
        </div>
    </div>
</div>
