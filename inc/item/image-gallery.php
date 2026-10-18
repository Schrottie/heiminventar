<!-- Sektion: Bilder -->
<div class="card bg-light border-0 p-3 mb-3">
    <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-images me-2"></i>Bilder</h6>

    <?php if (!empty($images)): ?>
        <div class="row g-2 mb-3" id="imageGalleryContainer">
            <?php foreach ($images as $image): ?>
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

    <!-- Upload für neue Bilder (AJAX) -->
    <div>
        <label for="item_images_ajax" class="form-label small text-muted">Neue Bilder hinzufügen</label>
        <div class="input-group">
            <input type="file" id="item_images_ajax" class="form-control" accept="image/*" multiple onchange="uploadItemImages(<?= (int)$item['id'] ?>)">
            <span class="input-group-text" id="uploadSpinner" style="display: none;">
                <i class="fa-solid fa-spinner fa-spin text-primary"></i>
            </span>
        </div>
        <div class="form-text">Wähle Bilder aus – sie werden sofort automatisch hochgeladen.</div>
    </div>
</div>

<script>
function uploadItemImages(itemId) {
    const fileInput = document.getElementById('item_images_ajax');
    if (fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('itemId', itemId);
    for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('images[]', fileInput.files[i]);
    }

    document.getElementById('uploadSpinner').style.display = 'flex';

    fetch('api/item-image-upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('uploadSpinner').style.display = 'none';
        if (data.success) {
            // Seite sanft aktualisieren, um die neuen Bilder direkt anzuzeigen
            window.location.reload();
        } else {
            alert('Fehler beim Upload: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        document.getElementById('uploadSpinner').style.display = 'none';
        console.error('Error:', error);
        alert('Ein Netzwerkfehler ist aufgetreten.');
    });
}
</script>
