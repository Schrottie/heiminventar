            <!-- Sektion: Set-Verwaltung -->
            <div class="card bg-light border-0 p-3 mb-3">
                <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-layer-group me-2"></i>Set-Verwaltung</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="is_set" name="is_set" <?= !empty($item['is_set']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold text-warning-emphasis" for="is_set">
                        Dieser Gegenstand ist ein Set (enthält mehrere Inhaltsteile)
                    </label>
                </div>
                <div id="setManageButtonContainer" class="<?= !empty($item['is_set']) ? '' : 'd-none' ?>">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#setItemModal">
                        <i class="fa-solid fa-list-check me-1"></i>Set-Inhalte verwalten
                    </button>
                    <?php if (empty($id) || $id <= 0): ?>
                        <div class="form-text text-muted mt-1">Hinweis: Sie können Inhalte hinzufügen, sobald der Gegenstand einmalig gespeichert wurde.</div>
                    <?php endif; ?>
                </div>
            </div>
