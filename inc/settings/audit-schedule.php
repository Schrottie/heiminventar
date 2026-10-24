<hr class="my-4">

<!-- Inventur-Planung (Usergebunden, Max. 3 Slots) -->
<div class="mb-2">
    <label class="form-label fw-bold">Persönliche Inventur-Termine (Max. 3 Slots)</label>
    <div class="form-text mb-3">
        <i class="fa-solid fa-circle-info me-1"></i> Lege hier deine Inventurtermine fest. Bei Erreichen des Datums erscheint auf deiner Startseite eine Hinweismeldung mit direktem Sprungziel zum gewählten Lagerort.
    </div>

    <?php
    // Aktuellen User ermitteln
    $currentUserId = $_SESSION['user_id'] ?? 0;

    // Lagerorte abfragen, falls noch nicht geschehen
    if (!isset($allLocations) && isset($pdo)) {
        $allLocations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();
    }

    // Vorhandene Einträge NUR für diesen User aus audit_schedules laden
    $savedAudits = [];
    if (isset($pdo) && $currentUserId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM audit_schedules WHERE user_id = ?");
            $stmt->execute([$currentUserId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $savedAudits[$row['slot_index']] = $row;
            }
        } catch (Exception $e) {
            // Tabelle eventuell noch nicht da
        }
    }
    ?>

    <form action="api/audit-schedule-save.php" method="POST">
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php 
                $slotData = $savedAudits[$i] ?? ['audit_date' => '', 'location_id' => '', 'title' => 'Reguläre Inventur'];
            ?>
            <div class="border rounded p-3 mb-3 bg-body-tertiary">
                <div class="fw-semibold small text-primary mb-2">
                    <i class="fa-solid fa-calendar-check me-1"></i> Inventur-Eintrag #<?= $i ?>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Bezeichnung / Titel</label>
                        <input type="text" class="form-control form-control-sm" name="audits[<?= $i ?>][title]" value="<?= htmlspecialchars($slotData['title'] ?: 'Inventur ' . $i) ?>" placeholder="z.B. Quartalsinventur">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Datum</label>
                        <input type="date" class="form-control form-control-sm" name="audits[<?= $i ?>][date]" value="<?= htmlspecialchars($slotData['audit_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Lagerort (Optional)</label>
                        <select class="form-select form-select-sm" name="audits[<?= $i ?>][location_id]">
                            <option value="">-- Alle Lagerorte (Gesamt) --</option>
                            <?php if (!empty($allLocations)): foreach ($allLocations as $loc): ?>
                                <option value="<?= (int)$loc['id'] ?>" <?= (isset($slotData['location_id']) && (int)$slotData['location_id'] === (int)$loc['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['name']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endfor; ?>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i>Inventur-Planung speichern
            </button>
        </div>
    </form>
</div>
