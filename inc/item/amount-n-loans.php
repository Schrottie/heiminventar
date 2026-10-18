<?php if (!(int)$item['is_bulk']): ?>
    <!-- Sektion: Exemplare & Verleih-Status -->
    <div class="card bg-light border-0 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-secondary fw-bold mb-0">
                <i class="fa-solid fa-boxes-stacked me-2"></i>Exemplare &amp; Verleih-Status
            </h6>
            <span class="badge bg-secondary">Gesamt: <?= count($itemUnits) ?> Stk.</span>
        </div>

        <?php if (empty($itemUnits)): ?>
            <p class="text-muted small mb-0">Keine Exemplare erfasst.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($itemUnits as $unit): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark">Exemplar #<?= (int)$unit['unit_number'] ?></span>
                            <?php if ($unit['status'] === 'lent'): ?>
                                <div>
                                    <span class="badge bg-warning text-dark me-1">Verliehen</span>
                                    <span class="fw-semibold small">An: <?= htmlspecialchars($unit['borrower_name']) ?></span>
                                    <span class="text-muted small ms-1">(seit <?= htmlspecialchars($unit['lent_date']) ?>)</span>
                                    <?php if (!empty($unit['notes'])): ?>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($unit['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-success">Im Lager (Verfügbar)</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($unit['status'] === 'lent'): ?>
                                <button type="button" class="btn btn-outline-success btn-sm" 
                                        onclick="openReturnUnitModal(<?= (int)$unit['id'] ?>, <?= (int)$id ?>, <?= (int)$unit['unit_number'] ?>, '<?= htmlspecialchars($unit['borrower_name'], ENT_QUOTES) ?>')" 
                                        title="Ins Lager zurückbringen">
                                    <i class="fa-solid fa-rotate-left me-1"></i>Zurück
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                        onclick="openLendModal(<?= (int)$unit['id'] ?>, <?= (int)$id ?>, <?= (int)$unit['unit_number'] ?>)" 
                                        title="Exemplar verleihen">
                                    <i class="fa-solid fa-handshake me-1"></i>Verleihen
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Sektion: Hinweis für Schüttgut -->
    <div class="card bg-light border-0 p-3 mb-3">
        <div class="text-muted small">
            <i class="fa-solid fa-circle-info me-1"></i> <strong>Schüttgut / Massenartikel:</strong> Für diesen Gegenstand ist keine Einzelstück- oder Verleih-Verfolgung aktiv.
        </div>
    </div>
<?php endif; ?>
