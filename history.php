<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/header.php';

// Die letzten 100 Log-Einträge abfragen
$stmt = $pdo->query("
    SELECT * FROM inventory_logs 
    ORDER BY created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-clock-rotate-left me-2"></i>Aktivitätsprotokoll</h2>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
        </a>
    </div>

    <div class="card shadow-sm border-secondary-subtle">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light-subtle border-bottom">
                        <tr>
                            <th class="ps-3">Datum / Zeit</th>
                            <th>Gegenstand</th>
                            <th>Aktion</th>
                            <th class="text-center">Änderung</th>
                            <th class="text-center">Neuer Bestand</th>
                            <th>Details</th>
                            <th class="pe-3">Benutzer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Noch keine Aktivitäten protokolliert.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="ps-3 small text-nowrap text-secondary">
                                        <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['item_name']) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($log['action']) {
                                            case 'created':
                                                echo '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-plus me-1"></i>Neu</span>';
                                                break;
                                            case 'quantity_changed':
                                                echo '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="fa-solid fa-boxes-stacked me-1"></i>Bestand</span>';
                                                break;
                                            case 'updated':
                                                echo '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"><i class="fa-solid fa-pen me-1"></i>Bearbeitet</span>';
                                                break;
                                            case 'deleted':
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-trash me-1"></i>Gelöscht</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-body-tertiary text-body border">' . htmlspecialchars($log['action']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?php if ($log['qty_change'] > 0): ?>
                                            <span class="text-success">+<?= (int)$log['qty_change'] ?></span>
                                        <?php elseif ($log['qty_change'] < 0): ?>
                                            <span class="text-danger"><?= (int)$log['qty_change'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-body-tertiary text-body border"><?= (int)$log['new_qty'] ?></span>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= htmlspecialchars($log['details'] ?? '') ?>
                                    </td>
                                    <td class="pe-3 small">
                                        <span class="badge bg-body-tertiary text-body border">
                                            <i class="fa-solid fa-user me-1 text-secondary"></i>
                                            <?= htmlspecialchars($log['user_name'] ?? 'Unbekannt') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
