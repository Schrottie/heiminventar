<?php $itemId = (int)($item['id'] ?? $id ?? 0); ?>

<!-- Sektion: Bilder -->
<div class="card bg-light border-0 p-3 mb-3 mt-3">
    <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-images me-2"></i>Bilder</h6>

    <?php if (!empty($images)): ?>
        <div class="row g-2 mb-3" id="imageGalleryContainer">
            <?php foreach ($images as $image): ?>
                <?php 
                $isMain = !empty($image['is_main']); 
                $imageId = (int)$image['id'];
                ?>
                <div class="col-6 col-md-3 image-card-wrapper" data-image-id="<?= $imageId ?>">
                    <div class="card h-100 bg-white border-0 shadow-sm <?= $isMain ? 'border-warning shadow main-image-card' : '' ?>">
                        <div class="ratio ratio-4x3 bg-light overflow-hidden border-bottom image-zoom-container">
                            <img src="img/items/<?= htmlspecialchars($image['filename']) ?>" class="card-img-top img-fluid object-fit-cover" alt="Bild">
                        </div>
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div class="d-flex gap-1 mt-auto">
                                <button type="button"
                                        class="btn btn-sm w-100 main-image-btn <?= $isMain ? 'btn-warning text-white' : 'btn-outline-warning' ?>"
                                        onclick="setMainImage(<?= $itemId ?>, <?= $imageId ?>)"
                                        title="<?= $isMain ? 'Aktuelles Hauptbild' : 'Als Hauptbild festlegen' ?>">
                                    <i class="fa-solid fa-star me-1"></i><span class="btn-text"><?= $isMain ? 'Hauptbild' : 'Stern' ?></span>
                                </button>
                                <!-- Korrigiert: Hier wird die Bild-ID ($imageId) statt der Artikel-ID übergeben! -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openImageDeleteModal(<?= $imageId ?>, <?= $itemId ?>)"
                                        title="Bild löschen">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Drag & Drop Uploadzone für neue Bilder -->
    <div>
        <label class="form-label small text-muted">Neue Bilder hinzufügen</label>
        <div id="imageDropzone" class="upload-dropzone p-3 text-center border border-2 border-dashed rounded bg-white" style="cursor: pointer;">
            <input type="file" id="item_images_ajax" class="d-none" accept="image/*" multiple>
            <div class="d-flex align-items-center justify-content-center gap-2 pointer-events-none">
                <i class="fa-solid fa-cloud-arrow-up text-primary fs-4"></i>
                <span class="small fw-semibold">Bilder hierher ziehen oder klicken zum Auswählen</span>
                <span class="spinner-border spinner-border-sm text-primary ms-2" id="uploadSpinner" style="display: none;" role="status"></span>
            </div>
        </div>
        <div class="form-text">Ausgewählte oder gedroppte Bilder werden sofort automatisch hochgeladen.</div>
    </div>
</div>
