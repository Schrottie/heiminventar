<!-- Widgets (Row3) -->
<div class="row g-4 mb-4">
    <!-- Widget 1: Nachbestellen / Mindestbestand -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-cart-shopping text-danger me-2"></i>Nachbestellung erforderlich</span>
                <span class="badge bg-danger"><?= count($lowStockItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockItems)): ?>
                    <div class="p-4 text-center text-muted small">Alles bestens – kein Artikel unter Mindestbestand.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Bestand / Min</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $low): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$low['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($low['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= (int)$low['quantity'] ?> / Min: <?= (int)$low['min_quantity'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$low['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget 2: Anstehende Wartungen -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-screwdriver-wrench text-info me-2"></i>Anstehende / Fällige Wartungen</span>
                <span class="badge bg-info text-dark"><?= count($maintenanceItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($maintenanceItems)): ?>
                    <div class="p-4 text-center text-muted small">Derzeit stehen keine Wartungen an.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Intervall / Letzte Wartung</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceItems as $maint): ?>
                                    <?php
                                        $lastMaint = $maint['last_maintenance_date'] ? date('d.m.Y', strtotime($maint['last_maintenance_date'])) : 'Nie';
                                        $dueDate = $maint['last_maintenance_date'] ? date('d.m.Y', strtotime("+{$maint['maintenance_interval_months']} months", strtotime($maint['last_maintenance_date']))) : 'Sofort fällig';
                                        $isOverdue = !$maint['last_maintenance_date'] || $dueDate <= $todayStr;
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$maint['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($maint['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?= $isOverdue ? 'bg-danger text-white' : 'bg-info text-dark' ?>">
                                                <?= $isOverdue ? 'Überfällig (Intervall: ' . $maint['maintenance_interval_months'] . ' Mon.)' : 'Fällig am ' . $dueDate ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$maint['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Widget 3: Verliehene Gegenstände -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-handshake text-primary me-2"></i>Aktive Verleihvorgänge</span>
                <span class="badge bg-secondary"><?= count($loanedItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($loanedItems)): ?>
                    <div class="p-4 text-center text-muted small">Aktuell sind keine Gegenstände verliehen.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Verliehen an</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loanedItems as $loan): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$loan['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($loan['name']) ?>
                                            </a>
                                            <span class="badge bg-secondary ms-1">x<?= (int)$loan['quantity'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($loan['loan_to']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$loan['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Widget 4: Ablaufende / Abgelaufene Artikel -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Ablaufende Artikel (30 Tage)</span>
                <span class="badge bg-warning text-dark"><?= count($expiringItems) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($expiringItems)): ?>
                    <div class="p-4 text-center text-muted small">Keine Artikel mit anstehendem Ablaufdatum in nächster Zeit.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gegenstand</th>
                                    <th>Ablaufdatum</th>
                                    <th class="text-end pe-3">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiringItems as $exp): ?>
                                    <?php
                                        $isPast = $exp['expiration_date'] < $todayStr;
                                        $badgeStyle = $isPast ? 'bg-danger text-white' : 'bg-warning text-dark';
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="item.php?id=<?= (int)$exp['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($exp['name']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeStyle ?>">
                                                <?= htmlspecialchars($exp['expiration_date']) ?> <?= $isPast ? '(Abgelaufen)' : '' ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="item.php?id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-primary" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
