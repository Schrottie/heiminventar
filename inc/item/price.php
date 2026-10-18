<!-- Sektion: Anschaffung & Versicherung -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3">
        <i class="fa-solid fa-file-invoice-dollar me-2"></i>Anschaffung &amp; Versicherung
    </h6>
    
    <div class="row g-3">
        <!-- Kaufpreis -->
        <div class="col-md-3">
            <label for="price" class="form-label small text-muted">Kaufpreis (€)</label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= htmlspecialchars($item['price'] ?? '') ?>" placeholder="0,00">
                <span class="input-group-text">€</span>
            </div>
        </div>

        <!-- Kaufdatum -->
        <div class="col-md-3">
            <label for="purchase_date" class="form-label small text-muted">Kaufdatum</label>
            <div class="input-group">
                <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= htmlspecialchars($item['purchase_date'] ?? '') ?>">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('purchase_date').value = '';" title="Datum leeren">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Kaufort / Händler -->
        <div class="col-md-3">
            <label for="purchase_location" class="form-label small text-muted">Kaufort / Händler</label>
            <input type="text" class="form-control" id="purchase_location" name="purchase_location" value="<?= htmlspecialchars($item['purchase_location'] ?? '') ?>" placeholder="z.B. Hornbach Berlin">
        </div>

        <!-- Garantie -->
        <div class="col-md-3">
            <label for="warranty_months" class="form-label small text-muted">Garantie (Monate)</label>
            <input type="number" min="0" class="form-control" id="warranty_months" name="warranty_months" value="<?= !empty($item['warranty_months']) ? (int)$item['warranty_months'] : '' ?>" placeholder="z.B. 24">
        </div>
    </div>

    <!-- Versicherungseinstellung -->
    <div class="mt-3 pt-2 border-top border-secondary-subtle">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="is_insured" name="is_insured" <?= !empty($item['is_insured']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold text-warning-emphasis" for="is_insured">
                Für Versicherungsliste markieren (versicherungsrelevant)
            </label>
        </div>
    </div>
</div>
