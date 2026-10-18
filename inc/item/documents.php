<!-- Sektion: Dokumente & Anleitungen -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3">
        <i class="fa-solid fa-paperclip me-2"></i>Dokumente &amp; Anleitungen
    </h6>

    <?php if (!empty($documents)): ?>
        <div class="list-group mb-3">
            <?php foreach ($documents as $doc): ?>
                <?php
                $badgeColor = match($doc['doc_type']) {
                    'rechnung' => 'bg-success',
                    'bedienungsanleitung' => 'bg-info text-dark',
                    default => 'bg-secondary'
                };
                $docTypeName = match($doc['doc_type']) {
                    'rechnung' => 'Rechnung',
                    'bedienungsanleitung' => 'Anleitung',
                    default => 'Sonstiges'
                };
                ?>
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-lines text-muted"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($doc['title']) ?></span>
                        <span class="badge <?= $badgeColor ?>"><?= $docTypeName ?></span>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="docs/items/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Anzeigen / Herunterladen">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="openDocumentDeleteModal(<?= (int)$doc['id'] ?>, <?= (int)$id ?>, '<?= htmlspecialchars($doc['title'], ENT_QUOTES) ?>')" 
                                title="Löschen">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">Bisher sind keine Dokumente oder Anleitungen hochgeladen.</p>
    <?php endif; ?>

    <!-- Upload für neue Dokumente -->
    <div>
        <label class="form-label small text-muted">Neue Dokumente hinzufügen</label>
        <div class="row g-2">
            <div class="col-md-8">
                <input type="file" name="documents[]" class="form-control" accept=".pdf,.doc,.docx,.txt" multiple>
            </div>
            <div class="col-md-4">
                <select name="doc_type" class="form-select">
                    <option value="sonstiges" selected>Sonstiges</option>
                    <option value="rechnung">Rechnung</option>
                    <option value="bedienungsanleitung">Bedienungsanleitung</option>
                </select>
            </div>
        </div>
        <div class="form-text">Der ausgewählte Dokumenttyp gilt für alle hier neu hochgeladenen Dateien.</div>
    </div>
</div>
