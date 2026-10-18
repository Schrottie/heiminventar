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

// Falls das Formular abgesendet wurde (POST-Request) -> Änderungen speichern
if ($item && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $name = trim($_POST['name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = !empty($_POST['price']) ? str_replace(',', '.', $_POST['price']) : null;
    $purchaseDate = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $purchaseLocation = trim($_POST['purchase_location'] ?? '');
    $minQuantity = (int)($_POST['min_quantity'] ?? 0);
    $isInsured = isset($_POST['is_insured']) ? 1 : 0;
    $isSet = isset($_POST['is_set']) ? 1 : 0;

    // Artikeldaten aktualisieren
    $stmtUpdate = $pdo->prepare("
        UPDATE inventory_items 
        SET name = ?, quantity = ?, location_id = ?, manufacturer = ?, description = ?, 
            price = ?, purchase_date = ?, purchase_location = ?, min_quantity = ?, is_insured = ?, is_set = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([
        $name, $quantity, $locationId, $manufacturer, $description,
        $price, $purchaseDate, $purchaseLocation, $minQuantity, $isInsured, $isSet, $id
    ]);

    // WICHTIG: Einheiten (item_units) passend zur neuen quantity synchronisieren
    syncItemUnits($pdo, $id, $quantity);

    // Protokollieren
    logInventoryAction($pdo, $id, $name, 'updated', $item['quantity'], $quantity, 'Gegenstand bearbeitet');

    // Seite neu laden, um die Änderungen anzuzeigen
    header("Location: item.php?id=" . $id . "&success=1");
    exit;
}

if (!$item) {
    die('Gegenstand nicht gefunden');
}

// Alle Lagerorte für das Dropdown-Menü abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Zugehörige Bilder des Gegenstands abfragen (inkl. is_main, Hauptbild zuerst)
$stmt = $pdo->prepare("SELECT id, filename, is_main FROM item_images WHERE item_id = ? ORDER BY is_main DESC, id ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Zugehörige Dokumente des Gegenstands abfragen
$stmtDoc = $pdo->prepare("SELECT id, filename, title, doc_type FROM item_documents WHERE item_id = ? ORDER BY id DESC");
$stmtDoc->execute([$id]);
$documents = $stmtDoc->fetchAll();

// Bestehende Tags für diesen Artikel abrufen und als kommagetrennten String aufbereiten
$currentTags = getItemTags($pdo, $id);
$currentTagsString = implode(', ', array_column($currentTags, 'name'));

// Sicherstellen, dass für das Item Einheiten in item_units existieren (Migration alter Bestände)
$stmtUnitsCheck = $pdo->prepare("SELECT COUNT(*) FROM item_units WHERE item_id = ?");
$stmtUnitsCheck->execute([$id]);
$unitCount = (int)$stmtUnitsCheck->fetchColumn();

if ($unitCount === 0 && (int)$item['quantity'] > 0) {
    syncItemUnits($pdo, $id, (int)$item['quantity']);
}
syncItemUnits($pdo, $id, (int)$item['quantity']);
// Alle Einheiten für dieses Item frisch laden
$stmtUnits = $pdo->prepare("SELECT * FROM item_units WHERE item_id = ? ORDER BY unit_number ASC");
$stmtUnits->execute([$id]);
$itemUnits = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);

// Anbauteile für diesen Gegenstand laden
$stmtParts = $pdo->prepare("SELECT * FROM item_parts WHERE item_id = ? ORDER BY sort_order ASC, id ASC");
$stmtParts->execute([$id]);
$itemParts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

// Gesamtwert aller Anbauteile berechnen
$partsTotalPrice = 0;
foreach ($itemParts as $part) {
    if (!empty($part['price'])) {
        $partsTotalPrice += (float)$part['price'];
    }
}
$grandTotalPrice = (float)($item['price'] ?? 0) + $partsTotalPrice;

require_once __DIR__ . '/inc/header.php';
?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_archive_lent'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Archivierung nicht möglich:</strong> Dieser Gegenstand hat aktuell noch verliehene Exemplare. Bitte bringen Sie diese zuerst ins Lager zurück.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'no_main_invoice'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Speichern nicht möglich:</strong> Sie haben ausgewählt, dass das Anbauteil auf der Hauptrechnung enthalten ist, für diesen Hauptgegenstand wurde jedoch noch keine Rechnung bzw. kein Dokument hochgeladen. Bitte laden Sie zuerst oben unter "Dokumente" eine Hauptrechnung hoch oder wählen Sie "Eigene Rechnung".
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>
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
                <i class="fa-solid fa-qrcode me-1"></i> QR-Code &amp; Etikett
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

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Bezeichnung</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hersteller</label>
                    <input type="text" name="manufacturer" class="form-control" value="<?= htmlspecialchars($item['manufacturer'] ?? '') ?>" placeholder="z.B. Bosch, Makita">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
            </div>

            <div class="row g-3 mb-3 align-items-start">
                <div class="col-md-3">
                    <label class="form-label text-danger fw-bold">Lagerort</label>
                    <select name="location_id" class="form-select">
                        <option value="">Bitte auswählen...</option>
                        <?php renderLocationOptions($locations, null, 0, (int)$item['location_id']); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mindestbestand</label>
                    <input type="number" name="min_quantity" class="form-control" min="0" value="<?= (int)($item['min_quantity'] ?? 0) ?>">
                    <div class="form-text">0 = Kein Mindestbestand</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Aktuelle Menge</label>
                    <input type="number" name="quantity" class="form-control" min="0" value="<?= (int)$item['quantity'] ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block text-truncate">&nbsp;</label>
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="isBulkCheck" name="is_bulk" value="1" <?= !empty($item['is_bulk']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold small" for="isBulkCheck">Schüttgut / Massenartikel</label>
                    </div>
                    <div class="form-text" style="font-size: 0.75rem;">Keine Einzelverfolgung</div>
                </div>
            </div>

            <!-- Sektion: Anschaffung & Versicherung -->
            <div class="card bg-light border-0 p-3 mb-3">
                <h6 class="text-secondary fw-bold mb-3"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Anschaffung &amp; Versicherung</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="price" class="form-label">Kaufpreis (€)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?= htmlspecialchars($item['price'] ?? '') ?>">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="purchase_date" class="form-label">Kaufdatum</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= htmlspecialchars($item['purchase_date'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('purchase_date').value = '';" title="Leeren">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="purchase_location" class="form-label">Kaufort / Händler</label>
                        <input type="text" class="form-control" id="purchase_location" name="purchase_location" value="<?= htmlspecialchars($item['purchase_location'] ?? '') ?>" placeholder="z.B. Hornbach Berlin">
                    </div>
                    <div class="col-md-3">
                        <label for="warranty_months" class="form-label">Garantie (Monate)</label>
                        <input type="number" min="0" class="form-control" id="warranty_months" name="warranty_months" value="<?= (int)($item['warranty_months'] ?? 0) ?>" placeholder="z.B. 24">
                    </div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_insured" name="is_insured" <?= !empty($item['is_insured']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-warning-emphasis" for="is_insured">
                            Für Versicherungsliste markieren (versicherungsrelevant)
                        </label>
                    </div>
                </div>
            </div>

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

        <?php require_once __DIR__ . '/inc/item/amount-n-loans.php'; ?>

            <div class="row g-3 mb-3">
                <!-- Ablaufdatum -->
                <div class="col-md-4">
                    <label for="expiration_date" class="form-label">Ablaufdatum / Verfallsdatum</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?= htmlspecialchars($item['expiration_date'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expiration_date').value = '';" title="Datum leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Wartungsintervall in Monaten -->
                <div class="col-md-4">
                    <label for="maintenance_interval_months" class="form-label">Wartungsintervall (Monate)</label>
                    <div class="input-group">
                        <input type="number" min="0" class="form-control" id="maintenance_interval_months" name="maintenance_interval_months" placeholder="z.B. 12" value="<?= (int)($item['maintenance_interval_months'] ?? 0) ?>">
                        <span class="input-group-text">Monate</span>
                    </div>
                </div>

                <!-- Datum der letzten Wartung -->
                <div class="col-md-4">
                    <label for="last_maintenance_date" class="form-label">Letzte Wartung am</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="last_maintenance_date" name="last_maintenance_date" value="<?= htmlspecialchars($item['last_maintenance_date'] ?? '') ?>">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('last_maintenance_date').value = '';" title="Datum leeren">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>

            <?php require_once __DIR__ . '/inc/item/documents.php'; ?>

            <?php require_once __DIR__ . '/inc/item/imageupload.php'; ?>

            <?php require_once __DIR__ . '/inc/item/set-management.php'; ?>

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

    <?php require_once __DIR__ . '/inc/item/tags.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
