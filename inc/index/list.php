<?php
// /inc/index/list.php - Modus: Live Filterbare Liste
?>

<!-- Schnellfilter / Suchleiste, QR-Button & Desktop-Warn-Buttons in einer Zeile -->
<div class="row mb-3 g-2 align-items-center">
    <!-- Suchleiste nimmt den flexiblen Platz ein -->
    <div class="col">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="quickFilter" class="form-control text-body" placeholder="Inventar durchsuchen..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        </div>
    </div>

    <!-- QR-Button (immer als reines Icon) -->
    <div class="col-auto">
        <button type="button" class="btn btn-outline-secondary px-3" data-bs-toggle="modal" data-bs-target="#scannerModal" title="QR scannen">
            <i class="fa-solid fa-qrcode"></i>
        </button>
    </div>

    <!-- Modus-Umschalter (Liste vs Suche direkt im Header für schnellen Wechsel) -->
    <div class="col-auto">
        <div class="btn-group" role="group" aria-label="Ansicht Modus">
            <a href="index.php?mode=list" class="btn btn-<?= $currentMode === 'list' ? 'primary' : 'outline-primary' ?>" title="Live Filterbare Liste">
                <i class="fa-solid fa-filter"></i>
            </a>
            <a href="index.php?mode=search" class="btn btn-<?= $currentMode === 'search' ? 'primary' : 'outline-primary' ?>" title="Umfassende Suche">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </a>
        </div>
    </div>

    <!-- WARN-BUTTONS FÜR DESKTOP & TABLET -->
    <?php if ($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0): ?>
        <div class="col-auto d-none d-md-flex align-items-center gap-2">
            <?php if ($lowStockCount > 0): ?>
                <button type="button" id="toggleLowStock_desktop" class="btn btn-outline-danger position-relative" title="Nachbestellen">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $lowStockCount ?>
                    </span>
                </button>
            <?php endif; ?>

            <?php if ($expiredCount > 0): ?>
                <button type="button" id="toggleExpired_desktop" class="btn btn-outline-warning position-relative" title="Ablauf/Verfall">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                        <?= $expiredCount ?>
                    </span>
                </button>
            <?php endif; ?>

            <?php if ($maintenanceCount > 0): ?>
                <button type="button" id="toggleMaintenance_desktop" class="btn btn-outline-secondary border-info position-relative text-info-emphasis" title="Wartung fällig">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                        <?= $maintenanceCount ?>
                    </span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- WARN-BUTTONS FÜR SMARTPHONES -->
<?php if ($lowStockCount > 0 || $expiredCount > 0 || $maintenanceCount > 0): ?>
    <div class="row mb-3 g-2 d-md-none">
        <div class="col-12">
            <div class="row g-2">
                <?php if ($lowStockCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleLowStock" class="btn btn-outline-danger position-relative w-100">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Nachbestellen
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $lowStockCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($expiredCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleExpired" class="btn btn-outline-warning position-relative w-100">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Ablauf/Verfall
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                <?= $expiredCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($maintenanceCount > 0): ?>
                    <div class="col-6">
                        <button type="button" id="toggleMaintenance" class="btn btn-outline-secondary border-info position-relative text-info-emphasis w-100">
                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Wartung fällig
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark">
                                <?= $maintenanceCount ?>
                            </span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Interaktive Tag-Filterleiste -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="text-muted small fw-bold">
                <i class="fa-solid fa-tags me-1"></i>Nach Tags filtern:
                <i class="fa-solid fa-circle-question text-muted ms-1" style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Tipp: Tippen oder Vorschlag wählen und mit Enter oder Komma bestätigen. Ab 2 Tags erscheint ein UND/ODER-Modus Schalter."></i>
            </span>
           
            <!-- UND / ODER Modus Umschalter (wird ab 2 Tags automatisch sichtbar) -->
            <div id="tagModeContainer" class="btn-group btn-group-sm" style="display: none;" role="group">
                <button type="button" id="modeOrBtn" class="btn btn-outline-primary active">ODER</button>
                <button type="button" id="modeAndBtn" class="btn btn-outline-primary">UND</button>
            </div>
        </div>
        <div class="input-group">
            <div class="position-relative flex-grow-1">
                <!-- Tag-Eingabe Container (Pills + Input) -->
                <div id="filterTagContainer" class="form-control d-flex flex-wrap align-items-center gap-1 p-2 bg-transparent text-body" style="min-height: 38px; cursor: text; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                    <input type="text" id="filterTagInput" class="border-0 flex-grow-1 bg-transparent text-body p-0 shadow-none" placeholder="Tag eingeben oder suchen..." autocomplete="off" style="outline: none; min-width: 140px;">
                </div>
           
                <!-- Autocomplete-Dropdown -->
                <div id="filterTagSuggestions" class="dropdown-menu shadow-sm w-100" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050;"></div>
            </div>
            <!-- X-Button (Standardmäßig ausgeblendet) -->
            <button type="button" id="clearFilterTags" class="btn btn-outline-secondary d-none" title="Alle Filter-Tags löschen">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</div>

<!-- Inventarliste (Item Loop) -->
<div id="inventoryList">
    <?php require_once __DIR__ . '/item-loop.php'; ?>
</div>
