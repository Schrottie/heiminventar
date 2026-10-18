<!-- Sektion: Anbauteile & Zubehör -->
<div class="card bg-light border-0 p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="text-secondary fw-bold mb-0">
            <i class="fa-solid fa-puzzle-piece me-2"></i>Anbauteile &amp; Zubehör
        </h6>
        <!-- Button zum Hinzufügen (nutzt deine globale Funktion ohne data-bs-toggle Konflikt) -->
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAddPartModal(event)">
            <i class="fa-solid fa-plus me-1"></i>Anbauteil hinzufügen
        </button>
    </div>

    <!-- Bestehende Anbauteile-Liste (wird ausgegeben, wenn Teile vorhanden sind) -->
    <?php if (!empty($itemParts)): ?>
        <div class="list-group mb-3">
            <?php foreach ($itemParts as $part): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-puzzle-piece text-muted"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($part['name']) ?></span>
                        <span class="badge bg-secondary">Menge: <?= (int)$part['quantity'] ?></span>
                        <?php if ($part['price'] !== null): ?>
                            <span class="badge bg-light text-dark border"><?= number_format($part['price'], 2, ',', '.') ?> €</span>
                        <?php endif; ?>
                        
                        <!-- Rechnungs-Status Badge -->
                        <?php if ($part['invoice_status'] === 'parent'): ?>
                            <span class="badge bg-secondary" title="Auf Hauptrechnung">Hauptrechnung</span>
                        <?php elseif ($part['invoice_status'] === 'none'): ?>
                            <span class="badge bg-light text-dark border" title="Keine Rechnung">Keine Rechnung</span>
                        <?php else: ?>
                            <span class="badge bg-success" title="Eigene Rechnung">Eigene Rechnung</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex gap-1">
                        <?php if (!empty($part['invoice_filename'])): ?>
                            <a href="docs/items/<?= htmlspecialchars($part['invoice_filename']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Rechnung anzeigen">
                                <i class="fa-solid fa-file-invoice"></i>
                            </a>
                        <?php endif; ?>
                        <!-- Bearbeiten-Button -->
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openEditPartModal(<?= htmlspecialchars(json_encode($part), ENT_QUOTES) ?>)" title="Bearbeiten">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <!-- Löschen-Button (ruft das schicke Bestätigungs-Modal auf) -->
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeletePart(<?= (int)$part['id'] ?>, '<?= htmlspecialchars($part['name'], ENT_QUOTES) ?>')" title="Löschen">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-0">Bisher sind keine Anbauteile oder Zubehörkomponenten hinterlegt.</p>
    <?php endif; ?>
</div>
