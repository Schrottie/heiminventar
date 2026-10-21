<!-- Sektion: Dokumente & Anleitungen -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3">
        <i class="fa-solid fa-paperclip me-2"></i>Dokumente &amp; Anleitungen
    </h6>

    <?php if (!empty($documents)): ?>
        <div class="row g-2 mb-3" id="documentGalleryContainer">
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
                
                $ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                $fileIcon = match($ext) {
                    'pdf' => 'fa-file-pdf text-danger',
                    'doc', 'docx' => 'fa-file-word text-primary',
                    'txt' => 'fa-file-lines text-secondary',
                    default => 'fa-file text-muted'
                };
                ?>
                <div class="col-6 col-md-3 document-card-wrapper" data-doc-id="<?= (int)$doc['id'] ?>">
                    <div class="card h-100 bg-white border-0 shadow-sm">
                        <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center border-bottom">
                            <i class="fa-solid <?= $fileIcon ?> fa-3x"></i>
                        </div>
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <div class="fw-semibold small text-truncate mb-1" title="<?= htmlspecialchars($doc['title']) ?>">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </div>
                                <span class="badge <?= $badgeColor ?> extra-small mb-2"><?= $docTypeName ?></span>
                            </div>
                            <div class="d-flex gap-1 mt-1">
                                <a href="docs/items/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary w-100" title="Anzeigen / Herunterladen">
                                    <i class="fa-solid fa-eye me-1"></i>Anzeigen
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="openDocumentDeleteModal(<?= (int)$doc['id'] ?>, <?= (int)$item['id'] ?>, '<?= htmlspecialchars($doc['title'], ENT_QUOTES) ?>')"
                                        title="Löschen">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">Bisher sind keine Dokumente oder Anleitungen hochgeladen.</p>
    <?php endif; ?>

    <!-- Drag & Drop Uploadzone für neue Dokumente (AJAX-Sofortupload) -->
    <div>
        <label class="form-label small text-muted">Neue Dokumente hinzufügen</label>
        <div class="row g-2 mb-2">
            <div class="col-12 col-md-4 order-md-2">
                <select id="doc_type_ajax" class="form-select form-select-sm h-100">
                    <option value="sonstiges" selected>Typ: Sonstiges</option>
                    <option value="rechnung">Typ: Rechnung</option>
                    <option value="bedienungsanleitung">Typ: Bedienungsanleitung</option>
                </select>
            </div>
            <div class="col-12 col-md-8 order-md-1">
                <div id="documentDropzone" class="upload-dropzone p-3 text-center border border-2 border-dashed rounded bg-white" style="cursor: pointer;">
                    <input type="file" id="item_documents_ajax" class="d-none" accept=".pdf,.doc,.docx,.txt" multiple onchange="uploadItemDocuments(<?= (int)$item['id'] ?>)">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-primary fs-4"></i>
                        <span class="small fw-semibold">Dokumente hierher ziehen oder klicken</span>
                        <span class="spinner-border spinner-border-sm text-primary ms-2" id="docUploadSpinner" style="display: none;" role="status"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-text">Der oben gewählte Typ gilt für alle zeitgleich hochgeladenen Dokumente.</div>
    </div>
</div>

