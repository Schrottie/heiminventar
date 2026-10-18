        <!-- Bildergalerie mit Hauptbild-Auswahl & Löschen-Funktion -->
        <?php if (!empty($images)): ?>
            <div class="row mb-3">
                <?php foreach ($images as $image): ?>
                    <?php $isMain = !empty($image['is_main']); ?>
                    <div class="col-6 col-md-4 mb-3">
                        <div class="card h-100 <?= $isMain ? 'border-warning shadow-sm' : '' ?>">
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
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                </form>

                                <!-- Button: Bild löschen -->
                                <a href="api/image-delete.php?id=<?= (int)$image['id'] ?>&item_id=<?= (int)$item['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Bild löschen?');"
                                   title="Bild löschen">
                                    <i class="fa-solid fa-trash"></i>
                                </a>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr class="mb-4">
        <?php endif; ?>
