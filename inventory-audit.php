<?php
// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

$currentUserId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0);

// --- FORMULAR-VERARBEITUNG FÜR INVENTUR-TERMINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_save_audit_schedule'])) {
        $title = trim($_POST['title'] ?? '') ?: 'Reguläre Inventur';
        $auditDate = $_POST['audit_date'] ?? '';
        $locationId = !empty($_POST['location_id']) && is_numeric($_POST['location_id']) ? (int)$_POST['location_id'] : null;

        if (!empty($auditDate)) {
            $stmt = $pdo->prepare("INSERT INTO audit_schedules (user_id, audit_date, location_id, title) VALUES (?, ?, ?, ?)");
            $stmt->execute([$currentUserId, $auditDate, $locationId, $title]);
        }
        header("Location: inventory-audit.php" . ($locationId ? "?location_id=" . $locationId : ""));
        exit;
    }

    if (isset($_POST['action_delete_audit_schedule'])) {
        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        if ($scheduleId > 0) {
            $stmt = $pdo->prepare("DELETE FROM audit_schedules WHERE id = ? AND user_id = ?");
            $stmt->execute([$scheduleId, $currentUserId]);
        }
        header("Location: inventory-audit.php");
        exit;
    }
}

// --- NÄCHSTEN ANSTEHENDEN TERMIN LADEN ---
$nextAudit = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, l.name AS location_name 
        FROM audit_schedules s 
        LEFT JOIN locations l ON l.id = s.location_id 
        WHERE s.user_id = ? AND s.audit_date >= CURDATE() 
        ORDER BY s.audit_date ASC 
        LIMIT 1
    ");
    $stmt->execute([$currentUserId]);
    $nextAudit = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Alle Termine für das Verwaltungs-Modal laden
$allSchedules = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, l.name AS location_name 
        FROM audit_schedules s 
        LEFT JOIN locations l ON l.id = s.location_id 
        WHERE s.user_id = ? 
        ORDER BY s.audit_date ASC
    ");
    $stmt->execute([$currentUserId]);
    $allSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Alle Lagerorte für das Auswahl-Dropdown abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Ausgewählten Lagerort aus der URL holen
$selectedLocationId = isset($_GET['location_id']) && is_numeric($_GET['location_id']) ? (int)$_GET['location_id'] : null;

if (!function_exists('getSubLocationIds')) {
    function getSubLocationIds(array $locations, int $parentId): array {
        $ids = [$parentId];
        foreach ($locations as $loc) {
            if ((int)$loc['parent_id'] === $parentId) {
                $ids = array_merge($ids, getSubLocationIds($locations, (int)$loc['id']));
            }
        }
        return $ids;
    }
}

$items = [];
if ($selectedLocationId !== null) {
    $targetLocationIds = getSubLocationIds($locations, $selectedLocationId);
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
   
    $stmt = $pdo->prepare("
        SELECT 
            i.id, i.name, i.quantity, i.min_quantity, i.is_set, is_bulk, 
            l.name AS location_name,
            (SELECT COUNT(*) FROM item_units u WHERE u.item_id = i.id AND u.status = 'available') AS available_count,
            (SELECT COUNT(*) FROM item_units u WHERE u.item_id = i.id AND u.status = 'lent') AS lent_count,
            (SELECT GROUP_CONCAT(CONCAT(u.borrower_name, ' (Ex. #', u.unit_number, ')') SEPARATOR ', ') FROM item_units u WHERE u.item_id = i.id AND u.status = 'lent') AS lent_details
        FROM inventory_items i
        LEFT JOIN locations l ON l.id = i.location_id
        WHERE i.location_id IN ($placeholders) AND i.is_archived = '0'
        ORDER BY i.name
    ");
    $stmt->execute($targetLocationIds);
    $items = $stmt->fetchAll();

    $setIds = array_column(array_filter($items, fn($it) => !empty($it['is_set'])), 'id');
    $setComponentsMap = [];
    if (!empty($setIds)) {
        $setPlaceholders = implode(',', array_fill(0, count($setIds), '?'));
        $stmtComp = $pdo->prepare("
            SELECT item_id, name, quantity, sort_order
            FROM item_set_items
            WHERE item_id IN ($setPlaceholders)
            ORDER BY sort_order ASC, id ASC
        ");
        $stmtComp->execute($setIds);
        while ($comp = $stmtComp->fetch(PDO::FETCH_ASSOC)) {
            $setComponentsMap[$comp['item_id']][] = $comp;
        }
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-clipboard-check me-2"></i>Inventur-Modus (Bestandsprüfung)</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#auditScheduleModal">
            <i class="fa-solid fa-calendar-days me-1"></i>Termine verwalten
        </button>
    </div>
    <div class="card-body">
        
        <!-- Nächster Inventurtermin als Infobox (falls vorhanden) -->
        <?php if ($nextAudit): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between py-2 px-3 mb-3">
                <div>
                    <i class="fa-solid fa-bell me-2 text-primary"></i>
                    <strong>Nächster Termin:</strong> <?= htmlspecialchars($nextAudit['title']) ?> am <strong><?= date('d.m.Y', strtotime($nextAudit['audit_date'])) ?></strong>
                    <?php if (!empty($nextAudit['location_name'])): ?>
                        – Ort: <a href="inventory-audit.php?location_id=<?= (int)$nextAudit['location_id'] ?>" class="alert-link"><?= htmlspecialchars($nextAudit['location_name']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <p class="text-muted small mb-3">
            Wähle einen Lagerort aus, um den Ist-Bestand zu prüfen. Mit dem grünen Haken kannst du gefundene Gegenstände direkt aus der Liste ausblenden.
        </p>

        <!-- Lagerort-Auswahl -->
        <form method="get" action="inventory-audit.php" class="row g-3 align-items-center">
            <div class="col-md-8">
                <select name="location_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Bitte Lagerort für die Inventur wählen...</option>
                    <?php renderLocationOptions($locations, null, 0, $selectedLocationId); ?>
                </select>
            </div>
            <div class="col-md-4">
                <a href="inventory-audit.php" class="btn btn-outline-secondary w-100">
                    <i class="fa-solid fa-rotate-left me-1"></i>Auswahl zurücksetzen
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Inventur-Termine verwalten & hinzufügen -->
<div class="modal fade" id="auditScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-days me-2"></i>Inventur-Termine verwalten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- Formular: Neuen Termin hinzufügen -->
                <form action="inventory-audit.php" method="POST" class="border rounded p-3 mb-4 bg-body-tertiary">
                    <input type="hidden" name="action_save_audit_schedule" value="1">
                    <h6 class="fw-semibold small text-primary mb-3"><i class="fa-solid fa-plus me-1"></i>Neuen Inventurtermin anlegen</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Bezeichnung</label>
                            <input type="text" class="form-control form-control-sm" name="title" placeholder="z.B. Quartalsinventur" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Datum</label>
                            <input type="date" class="form-control form-control-sm" name="audit_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Lagerort (Optional)</label>
                            <select class="form-select form-select-sm" name="location_id">
                                <option value="">-- Alle / Kein bestimmter --</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-success btn-sm w-100" title="Speichern">
                                <i class="fa-solid fa-floppy-disk"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Liste aller bestehenden Termine -->
                <h6 class="fw-semibold small text-muted mb-2">Alle geplanten Termine</h6>
                <?php if (empty($allSchedules)): ?>
                    <p class="text-muted small">Keine Inventurtermine hinterlegt.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Datum</th>
                                    <th>Bezeichnung</th>
                                    <th>Lagerort</th>
                                    <th class="text-end">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allSchedules as $sch): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($sch['audit_date'])) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($sch['title']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($sch['location_name'] ?? 'Gesamt') ?></td>
                                        <td class="text-end">
                                            <form action="inventory-audit.php" method="POST" class="d-inline" onsubmit="return confirm('Termin wirklich löschen?');">
                                                <input type="hidden" name="action_delete_audit_schedule" value="1">
                                                <input type="hidden" name="schedule_id" value="<?= (int)$sch['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" style="padding: 1px 6px;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedLocationId !== null): ?>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-boxes-stacked me-2"></i>Gegenstände im gewählten Bereich (<span id="itemCount"><?= count($items) ?></span> verbleibend)</span>
            <span class="badge bg-success" id="auditStatusBadge" style="display: none;">Änderungen gespeichert</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
                <div class="p-4 text-center text-muted" id="emptyMessage">
                    Keine Gegenstände in diesem Lagerort gefunden.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Gegenstand</th>
                                <th>Lagerort</th>
                                <th class="text-center" style="width: 220px;">Bestand &amp; Verleih-Info</th>
                                <th class="text-center" style="width: 180px;">Ist-Menge (Gesamt)</th>
                                <th class="text-end pe-3" style="width: 140px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $totalQty = (int)$item['quantity'];
                                $availableQty = (int)$item['available_count'];
                                $lentQty = (int)$item['lent_count'];
                            ?>
                                <tr id="row-<?= (int)$item['id'] ?>" class="audit-row">
                                    <td class="ps-3 fw-semibold text-break">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['is_set'])): ?>
                                                <i class="fa-solid fa-layer-group text-primary me-2" title="Dies ist ein Set"></i>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($item['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($item['location_name']) ?>
                                    </td>
                                    <td class="text-center small">
                                        <?php if (empty($item['is_bulk'])): ?>
                                            <span class="badge bg-secondary" title="Im Lager verfügbar">Lager: <?= $availableQty ?></span>
                                            <?php if ($lentQty > 0): ?>
                                                <span class="badge bg-warning text-dark ms-1" title="Verliehene Details: <?= htmlspecialchars($item['lent_details']) ?>">
                                                    <i class="fa-solid fa-handshake me-1"></i>Verliehen: <?= $lentQty ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border" title="Massenartikel / Schüttgut">
                                                <i class="fa-solid fa-boxes-stacked me-1"></i>Schüttgut
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm justify-content-center">
                                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(<?= (int)$item['id'] ?>, -1)">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                            <input type="number" id="qty-<?= (int)$item['id'] ?>" class="form-control text-center px-1" value="<?= $totalQty ?>" min="0" style="max-width: 60px;" onchange="saveQty(<?= (int)$item['id'] ?>)">
                                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(<?= (int)$item['id'] ?>, 1)">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="verifyItem(<?= (int)$item['id'] ?>)" title="Als vorhanden bestätigen (ausblenden)">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <a href="item.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <?php if (!empty($item['is_set']) && !empty($setComponentsMap[$item['id']])): ?>
                                    <tr class="table-light text-muted small audit-row" data-parent-row="<?= (int)$item['id'] ?>">
                                        <td colspan="5" class="ps-5 py-2">
                                            <div class="fw-semibold text-secondary mb-1"><i class="fa-solid fa-list-ul me-1"></i>Set-Inhalt (auf Vollständigkeit prüfen):</div>
                                            <ul class="mb-0 ps-3">
                                                <?php foreach ($setComponentsMap[$item['id']] as $comp): ?>
                                                    <li>
                                                        <span class="text-secondary"><?= htmlspecialchars($comp['name']) ?></span>
                                                        <span class="badge bg-secondary text-white ms-1" style="font-size: 0.75rem;">Anzahl: <?= (int)$comp['quantity'] ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

