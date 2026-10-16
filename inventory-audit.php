<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Lagerorte für das Auswahl-Dropdown abfragen
$locations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Ausgewählten Lagerort aus der URL holen
$selectedLocationId = isset($_GET['location_id']) && is_numeric($_GET['location_id']) ? (int)$_GET['location_id'] : null;

$items = [];
if ($selectedLocationId !== null) {
    // Untergeordnete Lagerorte per Rekursion ermitteln
    function getSubLocationIds(array $locations, int $parentId): array {
        $ids = [$parentId];
        foreach ($locations as $loc) {
            if ((int)$loc['parent_id'] === $parentId) {
                $ids = array_merge($ids, getSubLocationIds($locations, (int)$loc['id']));
            }
        }
        return $ids;
    }
    $targetLocationIds = getSubLocationIds($locations, $selectedLocationId);
   
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
   
    // SQL erweitert um verfügbare vs. verliehene Einheiten aus item_units
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

    // Set-Inhalte für gefundene Sets vorab laden, um unnötige Queries in der Schleife zu vermeiden
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
    <div class="card-header">
        <i class="fa-solid fa-clipboard-check me-2"></i>Inventur-Modus (Bestandsprüfung)
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Wähle einen Lagerort aus, um den Ist-Bestand zu prüfen. Mit dem grünen Haken kannst du gefundene Gegenstände direkt aus der Liste ausblenden. Bei Sets werden die Inhaltsteile zur Kontrolle unterhalb angezeigt. Aktuell verliehene Exemplare werden separat ausgewiesen.
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
                                            <!-- Haken-Button: Als vorhanden bestätigen & ausblenden -->
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="verifyItem(<?= (int)$item['id'] ?>)" title="Als vorhanden bestätigen (ausblenden)">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <!-- Detail-Link -->
                                            <a href="item.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Details öffnen">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Falls es ein Set ist und Inhaltsteile existieren: Unterhalb einrücken -->
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
