<!-- Sektion: Dokumente & Anleitungen (Inklusive Rechnungen von Anbauteilen) -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3">
        <i class="fa-solid fa-paperclip me-2"></i>Dokumente &amp; Anleitungen
    </h6>

    <?php 
    // Wir sammeln reguläre Dokumente UND Rechnungen aus Anbauteilen in einem gemeinsamen Array
    $allDocuments = $documents ?? [];

    if (!empty($itemParts)) {
        foreach ($itemParts as $part) {
            if (!empty($part['invoice_filename'])) {
                // Anbauteil-Rechnung als virtuelles Dokument hinzufügen
                $allDocuments[] = [
                    'id' => 'part_' . $part['id'], // Eindeutige ID für Teile-Rechnungen
                    'title' => 'Rechnung: ' . $part['name'],
                    'filename' => $part['invoice_filename'],
                    'doc_type' => 'rechnung',
                    'is_part_invoice' => true,
                    'part_name' => $part['name']
                ];
            }
        }
    }
    ?>

    <?php if (!empty($allDocuments)): ?>
        <div class="row g-2 mb-3" id="documentGalleryContainer">
            <?php foreach ($allDocuments as $doc): ?>
                <?php
                $badgeColor = match($doc['doc_type'] ?? 'sonstiges') {
                    'rechnung' => 'bg-success',
                    'bedienungsanleitung' => 'bg-info text-dark',
                    default => 'bg-secondary'
                };
                
                $isPartDoc = !empty($doc['is_part_invoice']);
                $docTypeName = $isPartDoc ? 'Teile-Rechnung' : match($doc['doc_type']) {
                    'rechnung' => 'Rechnung',
                    'bedienungsanleitung' => 'Anleitung',
                    default => 'Sonstiges'
                };
                
                $filename = $doc['filename'] ?? '';
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $fileIcon = match($ext) {
                    'pdf' => 'fa-file-pdf text-danger',
                    'doc', 'docx' => 'fa-file-word text-primary',
                    'txt' => 'fa-file-lines text-secondary',
                    default => 'fa-file text-muted'
                };
                
                $docId = $doc['id'] ?? 0;
                $docTitle = $doc['title'] ?? 'Dokument';
                ?>
                <div class="col-6 col-md-3 document-card-wrapper" data-doc-id="<?= htmlspecialchars($docId) ?>">
                    <div class="card h-100 bg-white border-0 shadow-sm position-relative">
                        
                        <!-- Kleiner Indikator oben rechts, falls es zum Anbauteil gehört -->
                        <?php if ($isPartDoc): ?>
                            <span class="position-absolute top-0 end-0 translate-middle-y badge bg-primary mt-2 me-2 extra-small" title="Gehört zum Anbauteil: <?= htmlspecialchars($doc['part_name']) ?>">
                                <i class="fa-solid fa-puzzle-piece me-1"></i>Zubehör
                            </span>
                        <?php endif; ?>

                        <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center border-bottom">
                            <i class="fa-solid <?= $fileIcon ?> fa-3x"></i>
                        </div>
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <div class="fw-semibold small text-truncate mb-1" title="<?= htmlspecialchars($docTitle) ?>">
                                    <?= htmlspecialchars($docTitle) ?>
                                </div>
                                <span class="badge <?= $badgeColor ?> extra-small mb-2"><?= $docTypeName ?></span>
                            </div>
                            <div class="d-flex gap-1 mt-1">
                                <a href="docs/items/<?= htmlspecialchars($filename) ?>" target="_blank" class="btn btn-sm btn-outline-secondary w-100" title="Anzeigen / Herunterladen">
                                    <i class="fa-solid fa-eye me-1"></i>Anzeigen
                                </a>
                                <!-- Löschen-Button nur bei echten Artikeldokumenten (Teile-Rechnungen werden über das Anbauteil selbst verwaltet) -->
                                <?php if (!$isPartDoc): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="openDocumentDeleteModal(<?= (int)$docId ?>, <?= (int)$id ?>, '<?= htmlspecialchars($docTitle, ENT_QUOTES) ?>')"
                                            title="Löschen">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Wird im Anbauteil verwaltet">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">Bisher sind keine Dokumente, Anleitungen oder Zubehör-Rechnungen hinterlegt.</p>
    <?php endif; ?>

    <!-- Drag & Drop Uploadzone für neue Hauptdokumente -->
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
                    <div class="d-flex align-items-center justify-content-center gap-2 pointer-events-none">
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
