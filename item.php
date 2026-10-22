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

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Holt die Artikel-ID sicher aus der URL oder der PHP-Variable
    const urlParams = new URLSearchParams(window.location.search);
    const currentItemId = parseInt(urlParams.get('id')) || <?= (int)($item['id'] ?? $id ?? 0) ?>;

    // Dokumente Dropzone & Upload initialisieren
    setupAjaxDropzone({
        dropzoneId: 'documentDropzone',
        inputId: 'item_documents_ajax',
        endpoint: 'api/item-document-upload.php',
        fileKey: 'documents[]',
        extraData: () => ({ 
            itemId: currentItemId, 
            doc_type: document.getElementById('doc_type_ajax')?.value || 'sonstiges' 
        }),
        onSuccess: (data) => {
            if (data.documents && Array.isArray(data.documents)) {
                appendNewDocumentsToGrid(data.documents, currentItemId);
            }
        }
    });

    function setupAjaxDropzone(config) {
        const dropzone = document.getElementById(config.dropzoneId);
        const fileInput = document.getElementById(config.inputId);
        if (!dropzone || !fileInput) return;

        dropzone.addEventListener('click', (e) => {
            if (e.target !== fileInput) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                uploadFiles(this.files, config);
                this.value = ''; 
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dropzone-active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dropzone-active');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dropzone-active');

            const dt = e.dataTransfer;
            if (dt.files && dt.files.length > 0) {
                fileInput.files = dt.files;
                uploadFiles(dt.files, config);
            }
        });
    }

    let isUploadingDocs = false;
    function uploadFiles(files, config) {
        if (isUploadingDocs) return;

        // Maximale Dateigröße (35 MB als sichere Grenze vor dem 40 MB PHP-Limit)
        const MAX_FILE_SIZE_MB = 35;
        const maxBytes = MAX_FILE_SIZE_MB * 1024 * 1024;

        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxBytes) {
                const fileSizeFormatted = (files[i].size / (1024 * 1024)).toFixed(1);
                showFileSizeModal(files[i].name, fileSizeFormatted, MAX_FILE_SIZE_MB);
                return; // Bricht ab und zeigt das Modal
            }
        }

        isUploadingDocs = true;
        const formData = new FormData();

        if (config.extraData) {
            const extra = config.extraData();
            for (const key in extra) {
                formData.append(key, extra[key]);
            }
        }

        for (let i = 0; i < files.length; i++) {
            formData.append(config.fileKey, files[i]);
        }

        const spinner = document.getElementById('docUploadSpinner');
        if (spinner) spinner.style.display = 'inline-block';

        fetch(config.endpoint, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';
            isUploadingDocs = false;
            
            if (data.success) {
                config.onSuccess(data);
            } else {
                alert('Fehler beim Dokumenten-Upload: ' + (data.error || 'Unbekannter Fehler'));
            }
        })
        .catch(err => {
            if (spinner) spinner.style.display = 'none';
            isUploadingDocs = false;
            console.error('GENAUER FETCH-FEHLER:', err);
            alert('Ein Netzwerkfehler ist beim Hochladen aufgetreten.');
        });
    }

    // Hilfsfunktion: Zeigt das Bootstrap-Modal bei zu großen Dateien
    function showFileSizeModal(fileName, fileSize, maxLimit) {
        const modalText = document.getElementById('fileSizeModalText');
        if (modalText) {
            modalText.innerHTML = `Die Datei <strong>"${escapeHtml(fileName)}"</strong> ist <strong>${fileSize} MB</strong> groß.<br>Erlaubt sind maximal ${maxLimit} MB.`;
        }
        const modalEl = document.getElementById('fileSizeModal');
        if (modalEl && window.bootstrap) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            alert(`Die Datei "${fileName}" ist zu groß (${fileSize} MB). Erlaubt sind max. ${maxLimit} MB.`);
        }
    }

    function appendNewDocumentsToGrid(docs, itemId) {
        let container = document.getElementById('documentGalleryContainer');
        if (!container) {
            const dropzoneCard = document.querySelector('#documentDropzone').closest('.card');
            container = document.createElement('div');
            container.className = 'row g-2 mb-3';
            container.id = 'documentGalleryContainer';
            dropzoneCard.parentNode.insertBefore(container, dropzoneCard);
        }

        docs.forEach(doc => {
            let badgeColor = 'bg-secondary', docTypeName = 'Sonstiges';
            if (doc.doc_type === 'rechnung') { badgeColor = 'bg-success'; docTypeName = 'Rechnung'; }
            if (doc.doc_type === 'bedienungsanleitung') { badgeColor = 'bg-info text-dark'; docTypeName = 'Anleitung'; }

            const ext = doc.filename ? doc.filename.split('.').pop().toLowerCase() : '';
            let fileIcon = 'fa-file text-muted';
            if (ext === 'pdf') fileIcon = 'fa-file-pdf text-danger';
            else if (['doc', 'docx'].includes(ext)) fileIcon = 'fa-file-word text-primary';
            else if (ext === 'txt') fileIcon = 'fa-file-lines text-secondary';

            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 document-card-wrapper';
            col.setAttribute('data-doc-id', doc.id);
            
            // Wichtig: itemId wird hier sauber als reiner Integer an die globale Löschfunktion übergeben
            col.innerHTML = `
                <div class="card h-100 bg-white border-0 shadow-sm">
                    <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center border-bottom">
                        <i class="fa-solid ${fileIcon} fa-3x"></i>
                    </div>
                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div>
                            <div class="fw-semibold small text-truncate mb-1" title="${escapeHtml(doc.title)}">${escapeHtml(doc.title)}</div>
                            <span class="badge ${badgeColor} extra-small mb-2">${docTypeName}</span>
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <a href="docs/items/${escapeHtml(doc.filename)}" target="_blank" class="btn btn-sm btn-outline-secondary w-100" title="Anzeigen / Herunterladen">
                                <i class="fa-solid fa-eye me-1"></i>Anzeigen
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDocumentDeleteModal(${doc.id}, ${currentItemId}, '${escapeHtml(doc.title)}')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    }

    function escapeHtml(str) {
        return str ? String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") : '';
    }
});




function setMainImage(itemId, imageId) {
    //console.log("SetMainImage aufgerufen mit ItemID:", itemId, "ImageID:", imageId);

    const formData = new FormData();
    formData.append('itemId', itemId);
    formData.append('imageId', imageId);

    fetch('api/item-set-main-image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        //console.log("Antwort vom Server:", data);
        if (data.success) {
            // 1. Alle Karten zurücksetzen
            document.querySelectorAll('#imageGalleryContainer .image-card-wrapper').forEach(wrapper => {
                const card = wrapper.querySelector('.card');
                const btn = wrapper.querySelector('.main-image-btn');
                const btnText = btn.querySelector('.btn-text');

                card.classList.remove('border-warning', 'shadow', 'main-image-card');
                btn.classList.remove('btn-warning', 'text-white');
                btn.classList.add('btn-outline-warning');
                btnText.textContent = 'Stern';
                btn.title = 'Als Hauptbild festlegen';
            });

            // 2. Das ausgewählte Bild als Hauptbild markieren
            const selectedWrapper = document.querySelector(`[data-image-id="${imageId}"]`);
            if (selectedWrapper) {
                const card = selectedWrapper.querySelector('.card');
                const btn = selectedWrapper.querySelector('.main-image-btn');
                const btnText = btn.querySelector('.btn-text');

                card.classList.add('border-warning', 'shadow', 'main-image-card');
                btn.classList.remove('btn-outline-warning');
                btn.classList.add('btn-warning', 'text-white');
                btnText.textContent = 'Hauptbild';
                btn.title = 'Aktuelles Hauptbild';
            }
        } else {
            alert('Fehler beim Ändern des Hauptbildes: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Fetch-Error:', error);
        alert('Ein Netzwerkfehler ist aufgetreten.');
    });
}

</script>