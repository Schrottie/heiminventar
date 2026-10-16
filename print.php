<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Lagerorte für die Dropdowns & Hierarchie abfragen
$allLocations =$pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Hilfsfunktion: Alle untergeordneten IDs für einen Standort ermitteln (für JS nutzbar machen)
function getAllSubLocationIds(array $locations, int$parentId): array {
    $ids = [$parentId];
    foreach ($locations as$loc) {
        if ((int)$loc['parent_id'] ===$parentId) {
            $ids = array_merge($ids, getAllSubLocationIds($locations, (int)$loc['id']));
        }
    }
    return $ids;
}

// Map für Standort-Namen und Eltern-Beziehungen für das Frontend aufbauen
$locationsMap = [];
foreach ($allLocations as$loc) {
    $locationsMap[$loc['id']] = [
        'id' => (int)$loc['id'],
        'name' => $loc['name'],
        'parent_id' => $loc['parent_id'] ? (int)$loc['parent_id'] : null,
        'sub_ids' => getAllSubLocationIds($allLocations, (int)$loc['id'])
    ];
}

// ALLE nicht archivierten Gegenstände einmalig laden (inkl. Standort, Bilder, Versicherungs-Flag)
$sql = "
    SELECT
        i.id, i.name, i.quantity, i.price, i.purchase_date, i.purchase_location,
        i.manufacturer, i.is_insured, i.is_set, i.location_id,
        l.name AS location_name,
        img.filename AS image_filename
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    LEFT JOIN (
        SELECT item_id, COALESCE(MIN(CASE WHEN is_main = 1 THEN id END), MIN(id)) AS main_id
        FROM item_images GROUP BY item_id
    ) main_img ON main_img.item_id = i.id
    LEFT JOIN item_images img ON img.id = main_img.main_id
    WHERE i.is_archived = '0'
    ORDER BY l.name ASC, i.name ASC
";
$items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Dokumente für alle Gegenstände laden
$itemDocumentsMap = [];$itemPartsMap = [];
if (!empty($items)) {
    $itemIds = array_column($items, 'id');
    $docPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
    
    // Dokumente laden
    $stmtDocs =$pdo->prepare("SELECT id, item_id, filename, title, doc_type FROM item_documents WHERE item_id IN ($docPlaceholders)");
    $stmtDocs->execute($itemIds);
    while ($doc = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {$itemDocumentsMap[$doc['item_id']][] =$doc;
    }

    // Anbauteile laden
    $stmtParts =$pdo->prepare("SELECT * FROM item_parts WHERE item_id IN ($docPlaceholders) ORDER BY sort_order ASC, id ASC");
    $stmtParts->execute($itemIds);
    while ($part = $stmtParts->fetch(PDO::FETCH_ASSOC)) {$itemPartsMap[$part['item_id']][] =$part;
    }
}

// Dokumente und Anbauteile an die Items binden für das JS-Array
foreach ($items as &$it) {
    $it['documents'] =$itemDocumentsMap[$it['id']] ?? [];$it['parts'] = $itemPartsMap[$it['id']] ?? [];
}
unset($it);

// HTML-Optionen für die Lagerort-Dropdowns vorab generieren
ob_start();
renderLocationOptions($allLocations, null, 0, null);$locationOptionsHtml = ob_get_clean();

require_once __DIR__ . '/inc/header.php';
?>

<!-- Druck-Steuerung (Wird beim Drucken ausgeblendet) -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-header bg-secondary text-white">
        <i class="fa-solid fa-print me-2"></i>Druck- &amp; Berichtsgenerator
    </div>
    <div class="card-body">
        <div id="printFilterForm">
           
            <div class="row g-3 mb-3">
                <!-- Berichtstyp -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Berichtsart</label>
                    <select id="reportTypeSelect" class="form-select" onchange="onReportTypeChange()">
                        <option value="insurance" selected>Versicherungsliste (Nur versicherungsrelevante Artikel)</option>
                        <option value="standard">Standard-Inventardruck (Kompaktkarten)</option>
                    </select>
                </div>

                <!-- Alle Lagerorte Checkbox -->
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="all_locations" checked onchange="toggleLocationInputs()">
                        <label class="form-check-label fw-semibold" for="all_locations">
                            Über alle Lagerorte hinweg drucken
                        </label>
                    </div>
                </div>
            </div>

            <!-- Option für Einzelauswahl (nur bei Versicherungsliste aktivierbar) -->
            <div class="row g-3 mb-3" id="individualSelectionWrapper">
                <div class="col-12">
                    <div class="form-check border p-2 rounded bg-light">
                        <input class="form-check-input ms-1" type="checkbox" id="enable_individual_selection" onchange="toggleIndividualSelectionMode()">
                        <label class="form-check-label fw-semibold ms-2" for="enable_individual_selection">
                            <i class="fa-solid fa-list-check me-1 text-primary"></i> Einzelauswahl aktivieren (Gegenstände individuell für den Druck an- oder abwählen)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Dynamische Lagerort-Auswahl (Client-Side in Echtzeit) -->
            <div id="locationSelectorContainer" class="mb-3 d-none">
                <label class="form-label fw-bold">Lagerorte filtern</label>
                <div id="locationDropdownsWrapper" class="d-flex flex-column gap-2">
                    <div class="input-group location-row">
                        <select name="location_ids[]" class="form-select location-filter-select" onchange="renderReport()">
                            <option value="">Bitte Lagerort wählen...</option>
                            <?= $locationOptionsHtml ?>
                        </select>
                        <button type="button" class="btn btn-outline-success" onclick="addLocationDropdown()" title="Weiteren Lagerort hinzufügen">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Aktionsbuttons -->
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>Jetzt drucken
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Zurück zur Übersicht
                </a>
            </div>

        </div>
    </div>
</div>

<!-- DRUCKBEREICH -->
<div class="print-container">
    <div class="d-none d-print-block mb-4">
        <h2 id="printReportTitle">Versicherungsliste</h2>
        <p class="text-muted small">Erstellt am: <?= date('d.m.Y H:i') ?> | Gegenstände: <span id="itemCountDisplay">0</span></p>
        <hr>
    </div>

    <div id="reportOutputContainer">
        <!-- Wird dynamisch durch JavaScript in Echtzeit befüllt -->
    </div>
</div>

<!-- JavaScript für reibungslose Echtzeit-Filterung ohne Reload -->
<script>
// PHP-Daten sicher an JavaScript übergeben
const allItems = <?= json_encode($items) ?>;
const locationsMap = <?= json_encode($locationsMap) ?>;
const storedLocationOptions = `<?= $locationOptionsHtml ?>`;

// State für die Einzelauswahl (gespeicherte IDs der abgewählten Items)
let excludedItemIds = new Set();
let isIndividualModeActive = false;

function onReportTypeChange() {
    const reportType = document.getElementById('reportTypeSelect').value;
    const indSelectionWrapper = document.getElementById('individualSelectionWrapper');
    
    if (reportType === 'insurance') {
        indSelectionWrapper.style.display = 'block';
    } else {
        indSelectionWrapper.style.display = 'none';
        document.getElementById('enable_individual_selection').checked = false;
        isIndividualModeActive = false;
        excludedItemIds.clear();
    }
    renderReport();
}

function toggleIndividualSelectionMode() {
    isIndividualModeActive = document.getElementById('enable_individual_selection').checked;
    if (!isIndividualModeActive) {
        excludedItemIds.clear();
    }
    renderReport();
}

function handleItemCheckboxChange(itemId, checkbox) {
    itemId = parseInt(itemId);
    if (checkbox.checked) {
        excludedItemIds.delete(itemId);
    } else {
        excludedItemIds.add(itemId);
    }
    renderReport(); // Aktualisiert Zähler & Gesamtsummen in Echtzeit
}

function toggleLocationInputs() {
    const isChecked = document.getElementById('all_locations').checked;
    const container = document.getElementById('locationSelectorContainer');
    if (isChecked) {
        container.classList.add('d-none');
    } else {
        container.classList.remove('d-none');
    }
    renderReport();
}

function addLocationDropdown() {
    const wrapper = document.getElementById('locationDropdownsWrapper');
    const div = document.createElement('div');
    div.className = 'input-group location-row';
   
    div.innerHTML = `
        <select name="location_ids[]" class="form-select location-filter-select" onchange="renderReport()">
            <option value="">Bitte Lagerort wählen...</option>
            ${storedLocationOptions}
        </select>
        <button type="button" class="btn btn-outline-danger" onclick="removeLocationRow(this)" title="Lagerort entfernen">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    wrapper.appendChild(div);
}

function removeLocationRow(button) {
    const row = button.closest('.location-row');
    row.remove();
    renderReport();
}

// Kernfunktion: Filtert und rendert den Bericht in Echtzeit im Browser
function renderReport() {
    const reportType = document.getElementById('reportTypeSelect').value;
    const allLocationsChecked = document.getElementById('all_locations').checked;
   
    // 1. Ausgewählte Lagerort-IDs einsammeln
    let targetLocationIds = [];
    if (!allLocationsChecked) {
        const selects = document.querySelectorAll('.location-filter-select');
        let selectedIds = [];
        selects.forEach(sel => {
            if (sel.value) selectedIds.push(parseInt(sel.value));
        });

        selectedIds.forEach(locId => {
            if (locationsMap[locId]) {
                targetLocationIds = targetLocationIds.concat(locationsMap[locId].sub_ids);
            }
        });
        targetLocationIds = [...new Set(targetLocationIds)];
    }

    // 2. Gegenstände filtern (Inkl. Einzelauswahl)
    const filteredItems = allItems.filter(item => {
        // Versicherungsfilter
        if (reportType === 'insurance' && parseInt(item.is_insured) !== 1) {
            return false;
        }
        // Lagerort-Filter
        if (!allLocationsChecked) {
            if (!item.location_id) return false;
            if (!targetLocationIds.includes(parseInt(item.location_id))) {
                return false;
            }
        }
        // Wenn Einzelauswahl aktiv ist und die ID abgewählt wurde -> Abgewählte Artikel komplett ausblenden!
        if (isIndividualModeActive && excludedItemIds.has(parseInt(item.id))) {
            return false;
        }
        return true;
    });

    // Anzahl der gedruckten Items aktualisieren
    document.getElementById('itemCountDisplay').textContent = filteredItems.length;
    document.getElementById('printReportTitle').textContent = (reportType === 'insurance') ? 'Versicherungsliste' : 'Inventarbericht';

    const container = document.getElementById('reportOutputContainer');
    container.innerHTML = '';

    if (filteredItems.length === 0) {
        container.innerHTML = `<div class="alert alert-warning text-center">Keine Gegenstände für die gewählten Filterkriterien gefunden.</div>`;
        return;
    }

    // 3. HTML je nach Berichtsart aufbauen
    if (reportType === 'standard') {
        let html = `<div class="row row-cols-1 row-cols-md-2 g-3">`;
        filteredItems.forEach(item => {
            let docsHtml = '';
            if (item.documents && item.documents.length > 0) {
                docsHtml += `<div class="d-flex flex-wrap gap-1 mt-1">`;
                item.documents.forEach(doc => {
                    let badgeClass = 'bg-secondary text-white';
                    if (doc.doc_type === 'rechnung') badgeClass = 'bg-success text-white';
                    if (doc.doc_type === 'bedienungsanleitung') badgeClass = 'bg-info text-dark';
                    docsHtml += `<span class="badge ${badgeClass}" style="font-size: 0.65rem;">${escapeHtml(doc.title)} (${doc.doc_type})</span>`;
                });
                docsHtml += `</div>`;
            }

            let imageHtml = item.image_filename
                ? `<img src="img/items/${escapeHtml(item.image_filename)}" class="rounded object-fit-cover" style="width: 70px; height: 70px;" alt="Bild">`
                : `<div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 1.2rem;"><i class="fa-solid fa-box"></i></div>`;

            let setIcon = parseInt(item.is_set) === 1 ? `<i class="fa-solid fa-layer-group text-primary me-1"></i>` : '';
            let mfrText = item.manufacturer ? ` | <span>Mfr: ${escapeHtml(item.manufacturer)}</span>` : '';
            let priceText = item.price ? `Preis: ${formatMoney(item.price)} €` : '';
            let purchaseDateText = item.purchase_date ? ` (Kauf: ${escapeHtml(item.purchase_date)})` : '';

            html += `
                <div class="col print-card-wrapper">
                    <div class="card shadow-none border p-2 h-100 print-card">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-shrink-0">${imageHtml}</div>
                            <div class="flex-grow-1 mw-0">
                                <h6 class="mb-1 text-truncate fw-bold">
                                    ${setIcon}
                                    <a href="item.php?id=${item.id}" target="_blank" class="print-item-link">${escapeHtml(item.name)}</a>
                                </h6>
                                <div class="small text-muted mb-1">
                                    <i class="fa-solid fa-location-dot me-1"></i>${escapeHtml(item.location_name || 'Kein Lagerort')}
                                    | <span class="fw-semibold">Menge: ${item.quantity}</span>${mfrText}
                                </div>
                                ${(item.price || item.purchase_date) ? `<div class="small text-muted mb-1">${priceText}${purchaseDateText}</div>` : ''}
                                ${docsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += `</div>`;
        container.innerHTML = html;

        } else {
        // Versicherungsliste als Tabelle (inkl. Anbauteile & optionaler Checkboxen)
        let totalSum = 0;
        let rowsHtml = '';

                filteredItems.forEach(item => {
            const itemId = parseInt(item.id);
            const basePrice = parseFloat(item.price || 0);
            
            // Anbauteile für dieses Item summieren
            let partsSum = 0;
            let partsRowsHtml = '';
            
            if (item.parts && item.parts.length > 0) {
                item.parts.forEach(part => {
                    const partPrice = parseFloat(part.price || 0);
                    partsSum += partPrice;
                    
                    let invoiceInfo = '';
                    if (part.invoice_status === 'parent') {
                        invoiceInfo = `<span class="text-muted" title="Auf der Hauptrechnung des Gegenstands enthalten"><i class="fa-solid fa-link me-1"></i>Inkl.</span>`;
                    } else if (part.invoice_status === 'none') {
                        invoiceInfo = `<span class="text-muted" title="Keine Rechnung vorhanden"><i class="fa-solid fa-ban me-1"></i>Nein</span>`;
                    } else {
                        if (part.invoice_filename) {
                            invoiceInfo = `<span class="text-success" title="Eigene Rechnung vorhanden"><i class="fa-solid fa-file-invoice me-1"></i>Ja</span>`;
                        } else {
                            invoiceInfo = `<span class="text-muted" title="Eigene Rechnung gewählt (keine Datei hochgeladen)"><i class="fa-solid fa-file-invoice me-1"></i>n.v.</span>`;
                        }
                    }

                    partsRowsHtml += `
                        <tr class="small text-muted border-0">
                            <td class="ps-4" colspan="2">
                                <i class="fa-solid fa-puzzle-piece me-1 opacity-50"></i> ${escapeHtml(part.name)}
                                ${parseInt(part.quantity) > 1 ? `<span class="badge bg-secondary ms-1">Anz: ${part.quantity}</span>` : ''}
                            </td>
                            <td class="text-center">—</td>
                            <td class="text-end">${partPrice > 0 ? formatMoney(partPrice) + ' €' : '—'}</td>
                            <td class="text-end">—</td>
                            <td class="text-center">—</td>
                            <td><em>Zubehör</em></td>
                            <td class="text-center">${invoiceInfo}</td>
                        </tr>
                    `;
                });
            }

            const itemTotalValue = basePrice + partsSum;
            totalSum += itemTotalValue;

            // Optional Checkbox Spalte für den Einzelauswahl-Modus (no-print sorgt dafür, dass die Checkbox auf dem Papier unsichtbar ist)
            let checkboxCell = '';
            if (isIndividualModeActive) {
                checkboxCell = `
                    <td class="text-center no-print">
                        <input class="form-check-input item-print-checkbox" type="checkbox" value="${itemId}" checked onchange="handleItemCheckboxChange(${itemId}, this)">
                    </td>
                `;
            }

            let hasInvoice = item.documents && item.documents.some(d => d.doc_type === 'rechnung');
            let invoiceBadge = hasInvoice ? `<span class="badge bg-success">Ja</span>` : `<span class="badge bg-secondary">Nein</span>`;

            // Hauptartikel Zeile
            rowsHtml += `
                <tr class="fw-semibold">
                    ${checkboxCell}
                    <td>
                        <a href="item.php?id=${item.id}" target="_blank" class="print-item-link">${escapeHtml(item.name)}</a>
                    </td>
                    <td class="small text-muted">${escapeHtml(item.location_name || '-')}</td>
                    <td class="text-center">1</td>
                    <td class="text-end">${basePrice > 0 ? formatMoney(basePrice) + ' €' : '-'}</td>
                    <td class="text-end fw-semibold">${itemTotalValue > 0 ? formatMoney(itemTotalValue) + ' €' : '-'}</td>
                    <td class="text-center small">${escapeHtml(item.purchase_date || '-')}</td>
                    <td class="small">${escapeHtml(item.purchase_location || '-')}</td>
                    <td class="text-center">${invoiceBadge}</td>
                </tr>
            `;

            // Anbauteile-Zeilen direkt darunter einfügen
            rowsHtml += partsRowsHtml;
        });

        let checkboxHeader = isIndividualModeActive ? `<th class="text-center no-print" style="width: 40px;"><i class="fa-solid fa-check"></i></th>` : '';
        let colspanVal = isIndividualModeActive ? 5 : 4;

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle print-table">
                    <thead class="table-light">
                        <tr>
                            ${checkboxHeader}
                            <th>Gegenstand</th>
                            <th>Lagerort</th>
                            <th class="text-center" style="width: 60px;">Anzahl</th>
                            <th class="text-end" style="width: 100px;">Basiswert</th>
                            <th class="text-end" style="width: 100px;">Gesamtwert</th>
                            <th class="text-center" style="width: 90px;">Kaufdatum</th>
                            <th>Kaufort / Händler</th>
                            <th class="text-center" style="width: 85px;">Rechnung?</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="${colspanVal}" class="text-end">Gesamtsumme (ausgewählte Artikel inkl. Zubehör):</td>
                            <td class="text-end">${formatMoney(totalSum)} €</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }
}

// Hilfsfunktionen für JS
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatMoney(amount) {
    return parseFloat(amount).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Initialer Aufruf beim Laden der Seite
document.addEventListener('DOMContentLoaded', () => {
    onReportTypeChange();
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>