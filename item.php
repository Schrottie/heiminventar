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

        <!-- Formular für Gegenstandsdaten -->
        <form action="api/item-update.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

            <?php require_once __DIR__ . '/inc/item/main-data.php'; ?>

            <?php require_once __DIR__ . '/inc/item/price.php'; ?>

            <?php require_once __DIR__ . '/inc/item/documents.php'; ?>

            <?php require_once __DIR__ . '/inc/item/additional-parts.php'; ?>

            <?php require_once __DIR__ . '/inc/item/dates.php'; ?>

            <?php require_once __DIR__ . '/inc/item/set-management.php'; ?>

            <?php require_once __DIR__ . '/inc/item/amount-n-loans.php'; ?>

            <?php require_once __DIR__ . '/inc/item/buttons.php'; ?>
        </form>

    <?php require_once __DIR__ . '/inc/item/image-gallery.php'; ?>

    <?php require_once __DIR__ . '/inc/item/tags.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
