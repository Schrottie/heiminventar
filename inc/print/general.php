<?php
// /inc/print/general.php - Druck- und Berichtssteuerung
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        
        <!-- Header & Berichtsart-Auswahl -->
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-5">
                        <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-file-lines me-1"></i>Berichtsart</label>
                        <select id="reportTypeSelect" class="form-select form-select" onchange="onReportTypeChange()">
                            <option value="standard">Standard-Inventarbericht</option>
                            <option value="insurance">Versicherungsliste (mit Werten)</option>
                        </select>
                    </div>

                    <!-- Optionale Einzelauswahl für Versicherungsliste -->
                    <div class="col-12 col-md-7" id="individualSelectionWrapper" style="display: none;">
                        <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-list-check me-1"></i>Einzelauswahl steuern</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="enable_individual_selection" onchange="toggleIndividualSelectionMode()">
                            <label class="form-check-label small fw-semibold" for="enable_individual_selection">
                                Bestimmte Artikel für den Druck abwählen
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Lagerort-Filter Sektion -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small text-muted mb-0"><i class="fa-solid fa-location-dot me-1"></i>Lagerort-Filter</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="all_locations" checked onchange="toggleLocationInputs()">
                            <label class="form-check-label small fw-semibold" for="all_locations">Alle Lagerorte einschließen</label>
                        </div>
                    </div>

                    <!-- Dynamische Lagerort-Auswahl -->
                    <div id="locationSelectorContainer" class="d-none">
                        <div id="locationDropdownsWrapper" class="d-flex flex-column gap-2 mb-2">
                            <!-- Wird dynamisch oder via Saved State befüllt -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLocationDropdown()">
                            <i class="fa-solid fa-plus me-1"></i>Weiteren Lagerort hinzufügen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Die eigentliche Druckansicht / Ausgabe -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                <h5 class="mb-0 fw-bold text-secondary">
                    <i class="fa-solid fa-print me-2"></i><span id="printReportTitle">Inventarbericht</span>
                    <span class="badge bg-secondary ms-2 fs-6"><span id="itemCountDisplay">0</span> Gegenstände</span>
                </h5>
                <button type="button" class="btn btn-primary no-print" onclick="window.print()">
                    <i class="fa-solid fa-print me-2"></i>Drucken / PDF
                </button>
            </div>
            
            <div class="card-body p-4" id="reportOutputContainer">
                <!-- Dynamischer Inhalt per JavaScript -->
            </div>
        </div>

    </div>
</div>
