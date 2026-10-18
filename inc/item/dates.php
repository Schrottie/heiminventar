            <div class="row g-3 mb-3">
                <!-- Ablaufdatum -->
                <div class="col-md-4">
                    <label for="expiration_date" class="form-label">Ablaufdatum / Verfallsdatum</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?= htmlspecialchars($item['expiration_date'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expiration_date').value = '';" title="Datum leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Wartungsintervall in Monaten -->
                <div class="col-md-4">
                    <label for="maintenance_interval_months" class="form-label">Wartungsintervall (Monate)</label>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control" id="maintenance_interval_months" name="maintenance_interval_months" placeholder="z.B. 12" value="<?= (int)($item['maintenance_interval_months'] ?? 0) ?>">
                        <span class="input-group-text">Monate</span>
                    </div>
                </div>

                <!-- Datum der letzten Wartung -->
                <div class="col-md-4">
                    <label for="last_maintenance_date" class="form-label">Letzte Wartung am</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date" value="<?= htmlspecialchars($item['last_maintenance_date'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('last_maintenance_date').value = '';" title="Datum leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
