<!-- KPI-Karten (Row 1) -->
<div class="row g-3 mb-4">
    <!-- Gesamtwert -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-primary-subtle bg-opacity-10 border-start border-primary border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Gesamtwert (Inventar)</div>
                <div class="fs-4 fw-bold text-primary mt-1">
                    <?= number_format($stats['total_value'] ?? 0, 2, ',', '.') ?> €
                </div>
                <div class="small text-muted mt-1">
                    <?= (int)$stats['total_items'] ?> Gegenstände (<?= (int)$stats['total_quantity'] ?> Stück gesamt)
                </div>
            </div>
        </div>
    </div>

    <!-- Nachbestellen (Mindestbestand) -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-danger-subtle bg-opacity-10 border-start border-danger border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Unter Mindestbestand</div>
                <div class="fs-4 fw-bold text-danger mt-1">
                    <?= (int)$stats['total_low_stock'] ?>
                </div>
                <div class="small text-muted mt-1">
                    Artikel müssen nachbestellt werden
                </div>
            </div>
        </div>
    </div>

    <!-- Ablauf / Verfall -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-warning-subtle bg-opacity-10 border-start border-warning border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Ablauf / Verfall</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1">
                    <?= (int)$expiredCount ?>
                </div>
                <div class="small text-muted mt-1">
                    Aktuell (oder in max. 30 Tagen) abgelaufen
                </div>
            </div>
        </div>
    </div>

    <!-- Wartung fällig -->
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100 bg-info-subtle bg-opacity-10 border-start border-info border-4">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Wartung fällig</div>
                <div class="fs-4 fw-bold text-info-emphasis mt-1">
                    <?= (int)$stats['total_maintenance'] ?>
                </div>
                <div class="small text-muted mt-1">
                    Aktuell (oder in max. 30 Tagen) fällig
                </div>
            </div>
        </div>
    </div>
</div>
