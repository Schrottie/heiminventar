            <!-- Sektion: Anbauteile -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fa-solid fa-puzzle-piece me-2"></i>Anbauteile &amp; Zubehör</span>
                    <div>
                        <?php if (!empty($itemParts)): ?>
                            <span class="badge bg-secondary me-2">Zubehör-Wert: <?= number_format($partsTotalPrice, 2, ',', '.') ?> €</span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-primary" onclick="openAddPartModal(event)">
                            <i class="fa-solid fa-plus me-1"></i> Anbauteil hinzufügen
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($itemParts)): ?>
                        <div class="p-3 text-muted text-center small">Keine Anbauteile oder Zubehör erfasst.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">Bezeichnung</th>
                                        <th class="text-center">Anzahl</th>
                                        <th class="text-end">Preis</th>
                                        <th>Rechnungsnachweis</th>
                                        <th class="text-end pe-3">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemParts as $part): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold text-break">
                                                <i class="fa-solid fa-gear text-muted me-1 small"></i><?= htmlspecialchars($part['name']) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= (int)$part['quantity'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?= !empty($part['price']) ? number_format($part['price'], 2, ',', '.') . ' €' : '—' ?>
                                            </td>
                                            <td class="small">
                                            <?php
                                            if ($part['invoice_status'] === 'parent') {
                                                echo '<span class="text-muted" title="Auf der Hauptrechnung enthalten"><i class="fa-solid fa-link me-1"></i>In Hauptrechnung</span>';
                                            } elseif ($part['invoice_status'] === 'none') {
                                                echo '<span class="text-muted" title="Keine Rechnung vorhanden"><i class="fa-solid fa-ban me-1"></i>Keine Rechnung</span>';
                                            } else {
                                                if (!empty($part['invoice_filename'])) {
                                                    echo '<a href="docs/items/' . htmlspecialchars($part['invoice_filename']) . '" target="_blank" class="text-decoration-none text-success"><i class="fa-solid fa-file-invoice me-1"></i>Eigene Rechnung</a>';
                                                } else {
                                                    echo '<span class="text-muted"><i class="fa-solid fa-file-invoice me-1"></i>Eigene Rechnung (keine Datei)</span>';
                                                }
                                            }
                                            ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="openEditPartModal(<?= htmlspecialchars(json_encode($part), ENT_QUOTES) ?>)" title="Bearbeiten">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeletePart(<?= (int)$part['id'] ?>, '<?= htmlspecialchars($part['name'], ENT_QUOTES) ?>')" title="Löschen">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if ($partsTotalPrice > 0): ?>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="2" class="ps-3 fw-bold text-end">Gesamtwert (inkl. Zubehör):</td>
                                            <td colspan="3" class="fw-bold text-primary pe-3">
                                                <?= number_format($grandTotalPrice, 2, ',', '.') ?> €
                                            </td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
