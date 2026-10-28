<!-- inc/print/general.php -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i>Druck- &amp; Filter-Einstellungen</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Berichtstyp</label>
                <select id="reportTypeSelect" class="form-select" onchange="onReportTypeChange()">
                    <option value="standard">Standard-Inventarbericht</option>
                    <option value="insurance">Versicherungsliste (Nur versicherte Artikel)</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label small text-muted">Lagerort-Filter</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="all_locations" checked onchange="toggleLocationInputs()">
                    <label class="form-check-label" for="all_locations">Alle Lagerorte einbeziehen</label>
                </div>
                <div id="locationSelectorContainer" class="d-none">
                    <div id="locationDropdownsWrapper" class="d-flex flex-column gap-2 mb-2"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLocationDropdown()">
                        <i class="fa-solid fa-plus me-1"></i>Weitere Standorte hinzufügen
                    </button>
                </div>
            </div>
        </div>

        <div id="individualSelectionWrapper" class="mt-3 pt-3 border-top" style="display: none;">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="enable_individual_selection" onchange="toggleIndividualSelectionMode()">
                <label class="form-check-label fw-semibold" for="enable_individual_selection">
                    Manuelle Einzelauswahl aktivieren (Artikel per Haken ein-/ausschließen)
                </label>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
        <h5 class="mb-0 fw-bold text-secondary">
            <i class="fa-solid fa-print me-2"></i><span id="printReportTitle">Inventarbericht</span>
            <span class="badge bg-secondary ms-2 fs-6"><span id="itemCountDisplay">0</span> Gegenstände</span>
        </h5>
        <div class="d-flex align-items-center gap-2">
            <!-- ZIP-Export Button für Versicherungsliste -->
            <button type="button" id="insuranceZipExportBtn" class="btn btn-outline-success no-print" style="display: none;" onclick="triggerInsuranceZipExport()">
                <i class="fa-solid fa-file-zipper me-1"></i> Belegexport (ZIP)
            </button>
            
            <button type="button" class="btn btn-primary no-print" onclick="window.print()">
                <i class="fa-solid fa-print me-2"></i>Drucken / PDF
            </button>
        </div>
    </div>
   
    <div class="card-body p-4" id="reportOutputContainer">
        <!-- Dynamischer Inhalt per JS -->
    </div>
</div>
