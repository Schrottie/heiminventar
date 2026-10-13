<?php

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// ID aus Request auslesen und validieren
$id = (int)($_GET['id'] ?? 0);

// Gegenstand-Daten abfragen
$stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ? AND is_archived = '0'");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die('Gegenstand nicht gefunden');
}

// Alle Lagerorte für das Dropdown-Menü abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Zugehörige Bilder des Gegenstands abfragen (inkl. is_main, Hauptbild zuerst)
$stmt = $pdo->prepare("SELECT id, filename, is_main FROM item_images WHERE item_id = ? ORDER BY is_main DESC, id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>

<!-- Formular-Karte: Gegenstand bearbeiten -->
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fa-solid fa-box me-2"></i>Gegenstand bearbeiten
        </div>
        <div class="d-flex gap-2">
            <!-- Button: Archivieren (öffnet das Modal aus modals.php) -->
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="openArchiveModal(<?= (int)$item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-box-archive me-1"></i>Archivieren
            </button>
            <!-- Button: QR-Code Modal -->
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#qrModal">
                <i class="fa-solid fa-qrcode me-1"></i> QR-Code & Etikett
            </button>
        </div>
    </div>

    <div class="card-body">
       
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

        <!-- Formular für Gegenstandsdaten -->
        <form action="api/item-update.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Bezeichnung</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description']) ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label">Aktuelle Menge</label>
                    <input type="number" name="quantity" class="form-control" min="0" value="<?= (int)$item['quantity'] ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Mindestbestand</label>
                    <input type="number" name="min_quantity" class="form-control" min="0" value="<?= (int)($item['min_quantity'] ?? 0) ?>">
                    <div class="form-text">0 = Kein Mindestbestand erforderlich</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Lagerort</label>
                <select name="location_id" class="form-select">
                    <option value="">Bitte auswählen...</option>
                    <?php renderLocationOptions($locations, null, 0, (int)$item['location_id']); ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="fa-solid fa-paperclip me-1"></i>Dokumente / Anleitungen hinzufügen (PDF, DOCX)</label>
                <input type="file" name="documents[]" class="form-control" accept=".pdf,.doc,.docx,.txt" multiple>
            </div>

            <div class="mb-3">
                <label class="form-label">Weitere Bilder hinzufügen</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>

            <div class="row g-3 mb-3">
                <!-- Ablaufdatum mit Lösch-Button -->
                <div class="col-md-4">
                    <label for="expiration_date" class="form-label">Ablaufdatum / Verfallsdatum</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?= htmlspecialchars($item['expiration_date'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expiration_date').value = '';" title="Datum leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Anschaffungspreis -->
                <div class="col-md-4">
                    <label for="price" class="form-label">Anschaffungspreis (€)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= htmlspecialchars($item['price'] ?? '') ?>">
                        <span class="input-group-text">€</span>
                    </div>
                </div>

                <!-- Verleih-Status & Person mit Lösch-Button -->
                <div class="col-md-4">
                    <label for="loan_to" class="form-label">Verliehen an (Name)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="loan_to" name="loan_to" placeholder="Leer, wenn im Lager" value="<?= htmlspecialchars($item['loan_to'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('loan_to').value = '';" title="Verleih leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Schaltflächen zum Speichern & Navigation -->
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="save" class="btn btn-success flex-fill">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Speichern
                </button>
                <button type="submit" name="action" value="save_and_close" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-check me-2"></i>Speichern &amp; zur Übersicht
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
