        <?php if (!(int)$item['is_bulk']): ?>
            <!-- Exemplar- & Verleih-Sektion -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fa-solid fa-boxes-stacked me-2"></i>Exemplare &amp; Verleih-Status</span>
                    <span class="badge bg-secondary">Gesamt: <?= count($itemUnits) ?> Stk.</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($itemUnits)): ?>
                            <div class="p-3 text-muted text-center small">Keine Exemplare erfasst.</div>
                        <?php else: ?>
                            <?php foreach ($itemUnits as $unit): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
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
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="openLendModal(<?= (int)$unit['id'] ?>, <?= (int)$id ?>, <?= (int)$unit['unit_number'] ?>)" title="Exemplar verleihen">
                                                <i class="fa-solid fa-handshake me-1"></i>Verleihen
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Hinweis für Schüttgut -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-muted small py-3">
                    <i class="fa-solid fa-info-circle me-1"></i> <strong>Schüttgut / Massenartikel:</strong> Für diesen Gegenstand ist keine Einzelstück- oder Verleih-Verfolgung aktiv.
                </div>
            </div>
        <?php endif; ?>
