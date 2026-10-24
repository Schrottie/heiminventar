<!-- Card: Nächste Inventur / Audit planen (Usergebunden) -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white d-flex align-items-center">
        <i class="fa-solid fa-clipboard-check me-2"></i>
        <h5 class="mb-0">Geplante Inventuren (Max. 3)</h5>
    </div>
    <div class="card-body">
        <?php
        // Aktuellen User ermitteln
        $currentUserId = $_SESSION['user_id'] ?? 0;

        // Vorhandene Einträge NUR für diesen User aus audit_schedules laden
        $savedAudits = [];
        $stmt = $pdo->prepare("SELECT * FROM audit_schedules WHERE user_id = ?");
        $stmt->execute([$currentUserId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $savedAudits[$row['slot_index']] = $row;
        }
        ?>
        <form action="api/audit-schedule-save.php" method="POST">
            <p class="text-muted small mb-3">
                Lege hier deine persönlichen Inventurtermine fest. Bei Erreichen des Datums erscheint auf deiner Startseite eine Hinweismeldung mit direktem Sprungziel zum Lagerort.
            </p>

            <?php for ($i = 1; $i <= 3; $i++): ?>
                <?php 
                    $slotData = $savedAudits[$i] ?? ['audit_date' => '', 'location_id' => '', 'title' => ''];
                ?>
                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="fw-semibold small text-primary mb-2">Inventur-Eintrag #<?= $i ?></div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label extra-small text-muted">Bezeichnung / Titel</label>
                            <input type="text" class="form-control form-control-sm" name="audits[<?= $i ?>][title]" value="<?= htmlspecialchars($slotData['title'] ?: 'Inventur ' . $i) ?>" placeholder="z.B. Quartalsinventur">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label extra-small text-muted">Datum der Inventur</label>
                            <input type="date" class="form-control form-control-sm" name="audits[<?= $i ?>][date]" value="<?= htmlspecialchars($slotData['audit_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label extra-small text-muted">Lagerort (Optional)</label>
                            <select class="form-select form-select-sm" name="audits[<?= $i ?>][location_id]">
                                <option value="">-- Alle Lagerorte (Gesamt) --</option>
                                <?php if (!empty($storageLocations)): foreach ($storageLocations as $loc): ?>
                                    <option value="<?= (int)$loc['id'] ?>" <?= ($slotData['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['name']) ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Inventur-Planung speichern
                </button>
            </div>
        </form>
    </div>
</div>
