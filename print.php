<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Hilfsfunktionen für Standorte und Daten
if (!function_exists('getAllSubLocationIds')) {
    function getAllSubLocationIds(array $locations, int $parentId): array {
        $ids = [$parentId];
        foreach ($locations as $loc) {
            if ((int)$loc['parent_id'] === $parentId) {
                $ids = array_merge($ids, getAllSubLocationIds($locations, (int)$loc['id']));
            }
        }
        return $ids;
    }
}

if (!function_exists('formatDateDe')) {
    function formatDateDe($dateStr) {
        if (empty($dateStr)) return '';
        $dt = DateTime::createFromFormat('Y-m-d', $dateStr);
        return $dt ? $dt->format('d.m.Y') : htmlspecialchars($dateStr);
    }
}

// Alle Lagerorte abfragen
$allLocations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

$locationsMap = [];
foreach ($allLocations as $loc) {
    $locationsMap[$loc['id']] = [
        'id' => (int)$loc['id'],
        'name' => $loc['name'],
        'parent_id' => $loc['parent_id'] ? (int)$loc['parent_id'] : null,
        'sub_ids' => getAllSubLocationIds($allLocations, (int)$loc['id'])
    ];
}

// Fall 1: ZIP-Export verarbeiten (falls aufgerufen)
require_once __DIR__ . '/inc/print/export-zip.php';

// Fall 2: Normale Seitenanzeige
$sql = "
    SELECT
        i.id, i.name, i.quantity, COALESCE(i.on_loan, 0) AS lent_quantity,
        i.price, i.purchase_date, i.purchase_location,
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

$itemDocumentsMap = []; 
$itemPartsMap = [];
if (!empty($items)) {
    $itemIds = array_column($items, 'id');
    $docPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
   
    $stmtDocs = $pdo->prepare("SELECT id, item_id, filename, title, doc_type FROM item_documents WHERE item_id IN ($docPlaceholders)");
    $stmtDocs->execute($itemIds);
    while ($doc = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
        $itemDocumentsMap[$doc['item_id']][] = $doc;
    }

    $stmtParts = $pdo->prepare("SELECT * FROM item_parts WHERE item_id IN ($docPlaceholders) ORDER BY sort_order ASC, id ASC");
    $stmtParts->execute($itemIds);
    while ($part = $stmtParts->fetch(PDO::FETCH_ASSOC)) {
        $itemPartsMap[$part['item_id']][] = $part;
    }
}

foreach ($items as &$it) {
    $it['documents'] = $itemDocumentsMap[$it['id']] ?? [];
    $it['parts'] = $itemPartsMap[$it['id']] ?? [];
   
    $it['quantity'] = (int)$it['quantity'];
    $it['lent_quantity'] = (int)$it['lent_quantity'];

    $partsSum = 0;
    foreach ($it['parts'] as $p) {
        $partsSum += (float)($p['price'] ?? 0);
    }
    $it['unit_price'] = (float)($it['price'] ?? 0) + $partsSum;
    $it['total_price_standard'] = $it['unit_price'] * $it['quantity'];

    $it['insured_quantity'] = max(0, $it['quantity'] - $it['lent_quantity']);
    $it['total_price_insured'] = $it['unit_price'] * $it['insured_quantity'];
    $it['purchase_date_formatted'] = formatDateDe($it['purchase_date']);
}
unset($it);

ob_start();
renderLocationOptions($allLocations, null, 0, null);
$locationOptionsHtml = ob_get_clean();

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/print/general.php';
?>

<!-- Formular für den ZIP-Export -->
<form id="insuranceZipForm" method="POST" action="print.php" style="display: none;">
    <input type="hidden" name="action_insurance_zip_export" value="1">
    <input type="hidden" name="all_locations" id="zip_all_locations" value="1">
    <input type="hidden" name="excluded_item_ids" id="zip_excluded_item_ids" value="[]">
    <div id="zipLocationInputsContainer"></div>
</form>

<!-- JavaScript für Echtzeit-Filterung, ZIP-Trigger & Persistence -->
<script>
const allItems = <?= json_encode($items) ?>;
const locationsMap = <?= json_encode($locationsMap) ?>;
const storedLocationOptions = `<?= $locationOptionsHtml ?>`;

let excludedItemIds = new Set();
let isIndividualModeActive = false;

const STORAGE_KEY_PRINT = 'inventory_print_settings';

function savePrintSettings() {
    const reportType = document.getElementById('reportTypeSelect').value;
    const allLocations = document.getElementById('all_locations').checked;
   
    let locationIds = [];
    if (!allLocations) {
        document.querySelectorAll('.location-filter-select').forEach(sel => {
            if (sel.value) locationIds.push(sel.value);
        });
    }

    const settings = {
        reportType: reportType,
        allLocations: allLocations,
        locationIds: locationIds,
        enableIndividual: document.getElementById('enable_individual_selection').checked,
        excludedItemIds: Array.from(excludedItemIds)
    };

    localStorage.setItem(STORAGE_KEY_PRINT, JSON.stringify(settings));
}

function restorePrintSettings() {
    const saved = localStorage.getItem(STORAGE_KEY_PRINT);
    if (!saved) {
        addLocationDropdown();
        return;
    }

    try {
        const settings = JSON.parse(saved);

        if (settings.reportType) {
            document.getElementById('reportTypeSelect').value = settings.reportType;
        }

        if (settings.allLocations !== undefined) {
            document.getElementById('all_locations').checked = settings.allLocations;
        }

        const container = document.getElementById('locationSelectorContainer');
        const wrapper = document.getElementById('locationDropdownsWrapper');
        wrapper.innerHTML = '';

        if (settings.allLocations) {
            container.classList.add('d-none');
            addLocationDropdown();
        } else {
            container.classList.remove('d-none');
            if (settings.locationIds && settings.locationIds.length > 0) {
                settings.locationIds.forEach(locId => {
                    addLocationDropdown(locId);
                });
            } else {
                addLocationDropdown();
            }
        }

        if (settings.enableIndividual !== undefined) {
            document.getElementById('enable_individual_selection').checked = settings.enableIndividual;
            isIndividualModeActive = settings.enableIndividual;
        }

        if (settings.excludedItemIds) {
            excludedItemIds = new Set(settings.excludedItemIds.map(id => parseInt(id)));
        }

    } catch (e) {
        console.error('Fehler beim Wiederherstellen der Druckeinstellungen:', e);
        addLocationDropdown();
    }
}

function onReportTypeChange() {
    const reportType = document.getElementById('reportTypeSelect').value;
    const indSelectionWrapper = document.getElementById('individualSelectionWrapper');
    const zipBtn = document.getElementById('insuranceZipExportBtn');
   
    if (reportType === 'insurance') {
        indSelectionWrapper.style.display = 'block';
        if (zipBtn) zipBtn.style.display = 'inline-block';
    } else {
        indSelectionWrapper.style.display = 'none';
        if (zipBtn) zipBtn.style.display = 'none';
        document.getElementById('enable_individual_selection').checked = false;
        isIndividualModeActive = false;
        excludedItemIds.clear();
    }
    savePrintSettings();
    renderReport();
}

function triggerInsuranceZipExport() {
    const allLocationsChecked = document.getElementById('all_locations').checked;
    document.getElementById('zip_all_locations').value = allLocationsChecked ? '1' : '0';
    document.getElementById('zip_excluded_item_ids').value = JSON.stringify(Array.from(excludedItemIds));

    const container = document.getElementById('zipLocationInputsContainer');
    container.innerHTML = '';

    if (!allLocationsChecked) {
        document.querySelectorAll('.location-filter-select').forEach(sel => {
            if (sel.value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'location_ids[]';
                input.value = sel.value;
                container.appendChild(input);
            }
        });
    }

    document.getElementById('insuranceZipForm').submit();
}

function toggleIndividualSelectionMode() {
    isIndividualModeActive = document.getElementById('enable_individual_selection').checked;
    if (!isIndividualModeActive) {
        excludedItemIds.clear();
    }
    savePrintSettings();
    renderReport();
}

function toggleLocationInputs() {
    const isChecked = document.getElementById('all_locations').checked;
    const container = document.getElementById('locationSelectorContainer');
    if (isChecked) {
        container.classList.add('d-none');
    } else {
        container.classList.remove('d-none');
    }
    savePrintSettings();
    renderReport();
}

function addLocationDropdown(selectedVal = '') {
    const wrapper = document.getElementById('locationDropdownsWrapper');
    const div = document.createElement('div');
    div.className = 'input-group location-row';
   
    div.innerHTML = `
        <select name="location_ids[]" class="form-select location-filter-select" onchange="onLocationChange()">
            <option value="">Bitte Lagerort wählen...</option>
            ${storedLocationOptions}
        </select>
        <button type="button" class="btn btn-outline-danger" onclick="removeLocationRow(this)" title="Lagerort entfernen">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
   
    if (selectedVal) {
        div.querySelector('select').value = selectedVal;
    }

    wrapper.appendChild(div);
}

function onLocationChange() {
    savePrintSettings();
    renderReport();
}

function removeLocationRow(button) {
    button.closest('.location-row').remove();
    savePrintSettings();
    renderReport();
}

function renderReport() {
    const reportType = document.getElementById('reportTypeSelect').value;
    const allLocationsChecked = document.getElementById('all_locations').checked;
   
    let targetLocationIds = [];
    if (!allLocationsChecked) {
        document.querySelectorAll('.location-filter-select').forEach(sel => {
            if (sel.value && locationsMap[sel.value]) {
                targetLocationIds = targetLocationIds.concat(locationsMap[sel.value].sub_ids);
            }
        });
        targetLocationIds = [...new Set(targetLocationIds)];
    }

    const filteredItems = allItems.filter(item => {
        if (reportType === 'insurance') {
            if (parseInt(item.is_insured) !== 1) return false;
            if (item.insured_quantity <= 0) return false;
        }

        if (!allLocationsChecked) {
            if (!item.location_id) return false;
            if (!targetLocationIds.includes(parseInt(item.location_id))) return false;
        }
        if (isIndividualModeActive && excludedItemIds.has(parseInt(item.id))) return false;
        return true;
    });

    document.getElementById('itemCountDisplay').textContent = filteredItems.length;
    document.getElementById('printReportTitle').textContent = (reportType === 'insurance') ? 'Versicherungsliste' : 'Inventarbericht';

    const container = document.getElementById('reportOutputContainer');
    container.innerHTML = '';

    if (filteredItems.length === 0) {
        container.innerHTML = `<div class="alert alert-warning text-center">Keine Gegenstände für die gewählten Filterkriterien gefunden.</div>`;
        return;
    }

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
                   
                    let docTitle = doc.title || '';
                    if (docTitle.length > 15) {
                        docTitle = docTitle.substring(0, 15) + '…';
                    }
                    let docTypeFormatted = doc.doc_type ? doc.doc_type.charAt(0).toUpperCase() + doc.doc_type.slice(1) : '';

                    docsHtml += `<span class="badge ${badgeClass}" style="font-size: 0.65rem;" title="${escapeHtml(doc.title)}">${escapeHtml(docTitle)} (${escapeHtml(docTypeFormatted)})</span>`;
                });
                docsHtml += `</div>`;
            }

            let imageHtml = item.image_filename
                ? `<img src="img/items/${escapeHtml(item.image_filename)}" class="rounded object-fit-cover" style="width: 70px; height: 70px;" alt="Bild">`
                : `<div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 1.2rem;"><i class="fa-solid fa-box"></i></div>`;

            let setIcon = parseInt(item.is_set) === 1 ? `<i class="fa-solid fa-layer-group text-primary me-1"></i>` : '';
            let mfrText = item.manufacturer ? ` | <span>Herst: ${escapeHtml(item.manufacturer)}</span>` : '';
           
            let totalPrice = item.total_price_standard;
            let priceText = totalPrice > 0 ? `Gesamtwert: ${formatMoney(totalPrice)} €` : '';
            if (item.quantity > 1 && item.unit_price > 0) {
                priceText += ` <span class="text-muted">(${formatMoney(item.unit_price)} €/Stk.)</span>`;
            }

            let purchaseDateText = item.purchase_date_formatted ? ` (Kauf: ${escapeHtml(item.purchase_date_formatted)})` : '';

            let quantityDisplay = `<span class="fw-semibold">Menge: ${item.quantity}</span>`;
            if (item.lent_quantity > 0) {
                if (item.quantity <= item.lent_quantity) {
                    quantityDisplay = `<span class="badge bg-warning text-dark" title="Alle Exemplare verliehen">Menge: ${item.quantity} (komplett verliehen)</span>`;
                } else {
                    quantityDisplay = `<span class="fw-semibold">Menge: ${item.quantity}</span> <span class="text-muted small">(davon ${item.lent_quantity} verliehen)</span>`;
                }
            }

            html += `
                <div class="col print-card-wrapper">
                    <div class="card shadow-none border p-2 h-100 print-card">
                        <div class="d-flex align-items-start gap-2">
                            <div class="flex-shrink-0">${imageHtml}</div>
                            <div class="flex-grow-1 mw-0">
                                <h6 class="mb-1 fw-bold text-break">
                                    ${setIcon}
                                    <a href="item.php?id=${item.id}" class="print-item-link">${escapeHtml(item.name)}</a>
                                </h6>
                                <div class="small text-muted mb-1 text-break">
                                    <i class="fa-solid fa-location-dot me-1"></i>${escapeHtml(item.location_name || 'Kein Lagerort')}
                                    | ${quantityDisplay}${mfrText}
                                </div>
                                ${(totalPrice > 0 || item.purchase_date_formatted) ? `<div class="small text-muted mb-1">${priceText}${purchaseDateText}</div>` : ''}
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
        window.currentPrintItems = filteredItems;
        let totalSum = 0;
        let rowsHtml = '';

        filteredItems.forEach(item => {
            const itemId = parseInt(item.id);
            const basePrice = parseFloat(item.price || 0);
            const insuredQty = item.insured_quantity;
            const isExcluded = excludedItemIds.has(itemId);
            const rowClassState = isExcluded ? 'table-active text-muted opacity-50 excluded-row' : 'fw-semibold';
           
            let partsSum = 0;
            let partsRowsHtml = '';
           
            if (item.parts && item.parts.length > 0) {
                item.parts.forEach(part => {
                    const partPrice = parseFloat(part.price || 0);
                    const partTotal = partPrice * insuredQty;
                    partsSum += partPrice;
                   
                    let invoiceInfo = '';
                    if (part.invoice_status === 'parent') {
                        invoiceInfo = `<span class="text-muted" title="Auf der Hauptrechnung enthalten"><i class="fa-solid fa-link me-1"></i>Inkl.</span>`;
                    } else if (part.invoice_status === 'none') {
                        invoiceInfo = `<span class="text-muted" title="Keine Rechnung"><i class="fa-solid fa-ban me-1"></i>Nein</span>`;
                    } else {
                        if (part.invoice_filename) {
                            invoiceInfo = `<span class="text-success" title="Eigene Rechnung"><i class="fa-solid fa-file-invoice me-1"></i>Ja</span>`;
                        } else {
                            invoiceInfo = `<span class="text-muted" title="Eigene Rechnung gewählt">n.v.</span>`;
                        }
                    }

                    partsRowsHtml += `
                        <tr class="small text-muted border-0 part-row-${itemId} ${isExcluded ? 'excluded-row' : ''}">
                            <td class="ps-4 text-break" colspan="2">
                                <i class="fa-solid fa-puzzle-piece me-1 opacity-50"></i> ${escapeHtml(part.name)}
                                ${parseInt(part.quantity) > 1 ? `<span class="badge bg-secondary ms-1">Anz: ${part.quantity}</span>` : ''}
                            </td>
                            <td class="text-center">${insuredQty}</td>
                            <td class="text-end">${partPrice > 0 ? formatMoney(partPrice) + ' €' : '—'}</td>
                            <td class="text-end">${partTotal > 0 ? formatMoney(partTotal) + ' €' : '—'}</td>
                            <td class="text-center">—</td>
                            <td><em>Zubehör</em></td>
                            <td class="text-center">${invoiceInfo}</td>
                        </tr>
                    `;
                });
            }

            const unitTotalValue = basePrice + partsSum;
            const itemTotalInsuredValue = item.total_price_insured;

            if (!isExcluded) {
                totalSum += itemTotalInsuredValue;
            }

            let checkboxCell = '';
            if (isIndividualModeActive) {
                let isChecked = !isExcluded ? 'checked' : '';
                checkboxCell = `
                    <td class="text-center no-print">
                        <input class="form-check-input item-print-checkbox" type="checkbox" value="${itemId}" ${isChecked} onchange="togglePrintExclusion(${itemId}, this)">
                    </td>
                `;
            }

            let hasInvoice = item.documents && item.documents.some(d => d.doc_type === 'rechnung');
            let invoiceBadge = hasInvoice ? `<span class="badge bg-success">Ja</span>` : `<span class="badge bg-secondary">Nein</span>`;

            let itemNameDisplay = escapeHtml(item.name);
            if (item.lent_quantity > 0) {
                itemNameDisplay += ` <span class="text-muted fw-normal small" title="Davon sind aktuell ${item.lent_quantity} Exemplare verliehen">(exkl. ${item.lent_quantity} verliehen)</span>`;
            }

            rowsHtml += `
                <tr class="${rowClassState} main-row-${itemId}">
                    ${checkboxCell}
                    <td class="text-break"><a href="item.php?id=${item.id}" class="print-item-link">${itemNameDisplay}</a></td>
                    <td class="small text-muted text-break">${escapeHtml(item.location_name || '-')}</td>
                    <td class="text-center">${insuredQty}</td>
                    <td class="text-end">${unitTotalValue > 0 ? formatMoney(unitTotalValue) + ' €' : '-'}</td>
                    <td class="text-end fw-semibold">${itemTotalInsuredValue > 0 ? formatMoney(itemTotalInsuredValue) + ' €' : '-'}</td>
                    <td class="text-center small">${escapeHtml(item.purchase_date_formatted || '-')}</td>
                    <td class="small text-break">${escapeHtml(item.purchase_location || '-')}</td>
                    <td class="text-center">${invoiceBadge}</td>
                </tr>
            `;
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
                            <th class="text-end" style="width: 100px;">Einzelwert</th>
                            <th class="text-end" style="width: 100px;">Gesamtwert</th>
                            <th class="text-center" style="width: 90px;">Kaufdatum</th>
                            <th>Kaufort / Händler</th>
                            <th class="text-center" style="width: 85px;">Rechnung?</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="${colspanVal}" class="text-end">Gesamtsumme (versicherte Artikel inkl. Zubehör):</td>
                            <td class="text-end" id="insuranceTotalSum">${formatMoney(totalSum)} €</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }
}

function togglePrintExclusion(itemId, checkbox) {
    const mainRow = document.querySelector(`.main-row-${itemId}`);
    const partRows = document.querySelectorAll(`.part-row-${itemId}`);

    if (checkbox.checked) {
        excludedItemIds.delete(itemId);
        if (mainRow) mainRow.classList.remove('table-active', 'text-muted', 'opacity-50', 'excluded-row');
        partRows.forEach(row => row.classList.remove('excluded-row'));
    } else {
        excludedItemIds.add(itemId);
        if (mainRow) mainRow.classList.add('table-active', 'text-muted', 'opacity-50', 'excluded-row');
        partRows.forEach(row => row.classList.add('excluded-row'));
    }

    let newTotalSum = 0;
    if (typeof window.currentPrintItems !== 'undefined') {
        window.currentPrintItems.forEach(item => {
            const id = parseInt(item.id);
            if (!excludedItemIds.has(id)) {
                newTotalSum += parseFloat(item.total_price_insured || 0);
            }
        });
    }

    const totalSumElement = document.getElementById('insuranceTotalSum');
    if (totalSumElement) {
        totalSumElement.textContent = formatMoney(newTotalSum) + ' €';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatMoney(amount) {
    return parseFloat(amount).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', () => {
    restorePrintSettings();
    const reportType = document.getElementById('reportTypeSelect').value;
    document.getElementById('individualSelectionWrapper').style.display = (reportType === 'insurance') ? 'block' : 'none';
    const zipBtn = document.getElementById('insuranceZipExportBtn');
    if (zipBtn) zipBtn.style.display = (reportType === 'insurance') ? 'inline-block' : 'none';
    renderReport();
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>