<?php
// /inc/index/search.php - Modus: Umfassende Suche
$currentSearchQuery = trim($_GET['q'] ?? '');
?>

<!-- Suchformular & Header für die umfassende Suche -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="index.php" class="row g-3 align-items-center">
            <input type="hidden" name="mode" value="search">
            <?php if ($filterLocationId !== null): ?>
                <input type="hidden" name="location_id" value="<?= (int)$filterLocationId ?>">
            <?php endif; ?>

            <div class="col">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Nach Namen, Beschreibung, Hersteller oder Kaufort suchen..." value="<?= htmlspecialchars($currentSearchQuery) ?>" autofocus>
                    <?php if (!empty($currentSearchQuery)): ?>
                        <a href="index.php?mode=search<?= $filterLocationId !== null ? '&location_id=' . $filterLocationId : '' ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Suche zurücksetzen">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="fa-solid fa-search me-2"></i>Suchen
                </button>
            </div>

            <!-- Modus-Umschalter (Wechsel zurück zur Liste) -->
            <div class="col-auto">
                <div class="btn-group" role="group" aria-label="Ansicht Modus">
                    <a href="index.php?mode=list" class="btn btn-lg btn-outline-primary" title="Live Filterbare Liste">
                        <i class="fa-solid fa-filter"></i>
                    </a>
                    <a href="index.php?mode=search" class="btn btn-lg btn-primary" title="Umfassende Suche">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="form-text mt-2 text-muted small">
            <i class="fa-solid fa-circle-info me-1"></i> Die umfassende Suche durchsucht den gesamten Bestand nach Stichwörtern in Namen, Beschreibungen, Herstellern oder Anschaffungsorten.
        </div>
    </div>
</div>

<!-- Suchergebnisse / Inventarliste (Item Loop) -->
<div id="inventoryList">
    <?php if (empty($currentSearchQuery) && empty($items)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-magnifying-glass fa-3x mb-3 text-secondary opacity-50"></i>
            <h5>Geben Sie einen Suchbegriff ein, um das Inventar zu durchsuchen.</h5>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="text-muted small">
                <i class="fa-solid fa-list-check me-1"></i><strong><?= count($items) ?></strong> Gegenstand/Gegenstände gefunden
            </span>
        </div>
        <?php require_once __DIR__ . '/item-loop.php'; ?>
    <?php endif; ?>
</div>
