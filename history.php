<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/header.php';

// Access Control: Falls Login aktiv ist, dürfen nur Admins die Seite aufrufen
if (function_exists('isLoginEnabled') && isLoginEnabled()) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

// Die letzten 100 Log-Einträge abfragen
$stmt =$pdo->query("
    SELECT l.*,
           loc_old.name AS old_loc_name,
           loc_new.name AS new_loc_name
    FROM inventory_logs l
    LEFT JOIN locations loc_old ON loc_old.id = CAST(l.old_value AS UNSIGNED) AND l.field_changed = 'location_id'
    LEFT JOIN locations loc_new ON loc_new.id = CAST(l.new_value AS UNSIGNED) AND l.field_changed = 'location_id'
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT 100
");
$logs =$stmt->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-clock-rotate-left me-2"></i>Aktivitätsprotokoll</h2>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Zur Übersicht
        </a>
    </div>

    <!-- Live-Schnellfilter -->
    <div class="card shadow-sm mb-3 border-secondary-subtle">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Sofort-Volltextsuche in der Tabelle..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="actionFilter" class="form-select form-select-sm">
                        <option value="">-- Alle Aktionen --</option>
                        <option value="created">Neu angelegt</option>
                        <option value="quantity_changed">Bestandsänderungen</option>
                        <option value="relocated">Umgelagert</option>
                        <option value="updated">Bearbeitet</option>
                        <option value="deleted">Gelöscht</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" id="btnResetFilter" class="btn btn-sm btn-outline-secondary d-none" title="Filter zurücksetzen">
                        <i class="fa-solid fa-xmark me-1"></i>Zurücksetzen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelle -->
    <div class="card shadow-sm border-secondary-subtle">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="historyTable">
                    <thead class="table-light-subtle border-bottom">
                        <tr>
                            <th class="ps-3">Datum / Zeit</th>
                            <th>Gegenstand</th>
                            <th>Aktion</th>
                            <th class="text-center">Änderung</th>
                            <th class="text-center">Bestand</th>
                            <th>Details</th>
                            <th>Benutzer</th>
                            <th class="pe-3 text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr id="noDataRow">
                                <td colspan="8" class="text-center py-4 text-muted">Noch keine Aktivitäten protokolliert.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as$log): ?>
                                <?php 
                                    $oldLocName =$log['old_loc_name'] ?? '';
                                    $newLocName =$log['new_loc_name'] ?? '';
                                ?>
                                <tr class="log-row" data-action="<?= htmlspecialchars($log['action']) ?>">
                                    <td class="ps-3 small text-nowrap text-secondary">
                                        <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="item.php?id=<?= (int)$log['item_id'] ?>" class="text-decoration-none fw-bold text-body">
                                            <i class="fa-solid fa-box me-1 text-secondary"></i><?= htmlspecialchars($log['item_name']) ?>
                                        </a>
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
                                            case 'relocated':
                                                echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-solid fa-location-dot me-1"></i>Umgelagert</span>';
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
                                    <td class="small">
                                        <span class="badge bg-body-tertiary text-body border">
                                            <i class="fa-solid fa-user me-1 text-secondary"></i>
                                            <?= htmlspecialchars($log['user_name'] ?? 'Unbekannt') ?>
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                           
                                            <!-- 1. BUTTON: Zur Detailseite des Gegenstandes (Nur Icon) -->
                                            <a href="item.php?id=<?= (int)$log['item_id'] ?>"
                                               class="btn btn-outline-primary border-0 py-0 px-2"
                                               title="Gegenstand '<?= htmlspecialchars($log['item_name']) ?>' aufrufen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>

                                            <!-- 2. BUTTON: Rollback (Nur Icon, wird nur gezeigt wenn ein Feld geändert wurde) -->
                                            <?php if (!empty($log['field_changed'])): ?>
                                            <button type="button"
                                                    class="btn btn-outline-warning border-0 py-0 px-2 btn-rollback"
                                                    title="Änderung rückgängig machen"
                                                    data-log-id="<?= (int)$log['id'] ?>"
                                                    data-item-id="<?= (int)$log['item_id'] ?>"
                                                    data-item-name="<?= htmlspecialchars($log['item_name']) ?>"
                                                    data-field="<?= htmlspecialchars($log['field_changed']) ?>"
                                                    data-old-value="<?= htmlspecialchars($log['old_value'] ?? '') ?>"
                                                    data-new-value="<?= htmlspecialchars($log['new_value'] ?? '') ?>"
                                                    data-old-location-name="<?= htmlspecialchars($oldLocName) ?>"
                                                    data-new-location-name="<?= htmlspecialchars($newLocName) ?>">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noMatchRow" class="d-none">
                                <td colspan="8" class="text-center py-4 text-muted">Keine passenden Einträge gefunden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer py-2 text-end">
            <span class="small text-muted" id="rowCountText">Zeige die letzten <?= count($logs) ?> Einträge</span>
        </div>
    </div>
</div>

<script>

</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
