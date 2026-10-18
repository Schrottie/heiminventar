
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Bezeichnung</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hersteller</label>
                    <input type="text" name="manufacturer" class="form-control" value="<?= htmlspecialchars($item['manufacturer'] ?? '') ?>" placeholder="z.B. Bosch, Makita">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
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
                    <input type="number" name="quantity" class="form-control" min="0" value="<?= (int)$item['quantity'] ?>">
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
