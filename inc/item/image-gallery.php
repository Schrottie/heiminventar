<!-- Sektion: Bilder -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-images me-2"></i>Bilder</h6>

    <?php if (!empty($images)): ?>
        <div class="row g-2 mb-3">
            <?php foreach ($images as$image): ?>
                <?php $isMain = !empty($image['is_main']); ?>
                <div class="col-6 col-md-3">
                    <div class="card h-100 bg-white <?= $isMain ? 'border-warning shadow-sm' : 'border-0' ?>">
                        <img src="img/items/<?= htmlspecialchars($image['filename']) ?>" class="card-img-top img-fluid item-pic object-fit-cover" style="height: 140px;" alt="Bild">
                        <div class="card-body p-2 d-flex gap-1">
                           
                            <!-- Button: Als Hauptbild festlegen -->
                            <form action="api/item-update.php" method="post" class="flex-grow-1">
                                <input type="hidden" name="action_set_main_image" value="1">
                                <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit"
                                        class="btn btn-sm w-100 <?= $isMain ? 'btn-warning text-white' : 'btn-outline-warning' ?>"
                                        title="<?= $isMain ? 'Aktuelles Hauptbild' : 'Als Hauptbild festlegen' ?>">
                                    <i class="fa-solid fa-star me-1"></i><?= $isMain ? 'Hauptbild' : 'Stern' ?>
                                </button>
                            </form>

                            <!-- Button: Bild löschen (über Modal) -->
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger" 
                                    onclick="openImageDeleteModal(<?= (int)$image['id'] ?>, <?= (int)$item['id'] ?>)" 
                                    title="Bild löschen">
                                <i class="fa-solid fa-trash"></i>
                            </button>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Upload für neue Bilder -->
    <div>
        <label for="item_images" class="form-label small text-muted">Neue Bilder hinzufügen</label>
        <input type="file" name="images[]" id="item_images" class="form-control" accept="image/*" multiple>
        <div class="form-text">Du kannst mehrere Bilddateien gleichzeitig auswählen (JPG, PNG, WEBP).</div>
    </div>
</div>
