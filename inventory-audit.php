<?php
// Konfiguration und Hilfsfunktionen einbinden
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Lagerorte für das Auswahl-Dropdown abfragen
$locations =$pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name")->fetchAll();

// Ausgewählten Lagerort aus der URL holen
$selectedLocationId = isset($_GET['location_id']) && is_numeric($_GET['location_id']) ? (int)$_GET['location_id'] : null;

$items = [];
if ($selectedLocationId !== null) {
    // Untergeordnete Lagerorte per Rekursion ermitteln
    function getSubLocationIds(array $locations, int$parentId): array {
        $ids = [$parentId];
        foreach ($locations as$loc) {
            if ((int)$loc['parent_id'] ===$parentId) {
                $ids = array_merge($ids, getSubLocationIds($locations, (int)$loc['id']));
            }
        }
        return $ids;
    }
    $targetLocationIds = getSubLocationIds($locations,$selectedLocationId);
    
    $placeholders = implode(',', array_fill(0, count($targetLocationIds), '?'));
    
    $stmt =$pdo->prepare("
        SELECT i.id, i.name, i.quantity, i.min_quantity, l.name AS location_name
        FROM inventory_items i
        LEFT JOIN locations l ON l.id = i.location_id
        WHERE i.location_id IN ($placeholders) AND i.is_archived = '0'
        ORDER BY i.name
    ");
    $stmt->execute($targetLocationIds);
    $items =$stmt->fetchAll();
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fa-solid fa-clipboard-check me-2"></i>Inventur-Modus (Bestandsprüfung)
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Wähle einen Lagerort aus, um den Ist-Bestand zu prüfen. Mit dem grünen Haken kannst du gefundene Gegenstände direkt aus der Liste ausblenden.
        </p>

        <!-- Lagerort-Auswahl -->
        <form method="get" action="inventory-audit.php" class="row g-3 align-items-center">
            <div class="col-md-8">
                <select name="location_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Bitte Lagerort für die Inventur wählen...</option>
                    <?php renderLocationOptions($locations, null, 0,$selectedLocationId); ?>
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
                                <th class="text-center" style="width: 180px;">Ist-Menge (Bestand)</th>
                                <th class="text-end pe-3" style="width: 140px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as$item): ?>
                                <tr id="row-<?= (int)$item['id'] ?>" class="audit-row">
                                    <td class="ps-3 fw-semibold text-break">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= htmlspecialchars($item['location_name']) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="input-group input-group-sm justify-content-center">
                                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(<?= (int)$item['id'] ?>, -1)">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                            <input type="number" id="qty-<?= (int)$item['id'] ?>" class="form-control text-center px-1" value="<?= (int)$item['quantity'] ?>" min="0" style="max-width: 60px;" onchange="saveQty(<?= (int)$item['id'] ?>)">
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
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript für AJAX-Bestandsupdate & Verifizieren/Ausblenden -->
<script>
function updateQty(itemId, change) {
    const input = document.getElementById('qty-' + itemId);
    if (!input) return;
    
    let currentVal = parseInt(input.value) || 0;
    let newVal = currentVal + change;
    if (newVal < 0) newVal = 0;
    
    input.value = newVal;
    saveQty(itemId);
}

function saveQty(itemId) {
    const input = document.getElementById('qty-' + itemId);
    if (!input) return;
    
    const newQuantity = parseInt(input.value) || 0;

    fetch('api/audit-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: itemId, quantity: newQuantity })
    })
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById('auditStatusBadge');
        if (data.success) {
            if (badge) {
                badge.style.display = 'inline-block';
                setTimeout(() => { badge.style.display = 'none'; }, 2000);
            }
        } else {
            alert('Fehler beim Speichern: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(err => {
        console.error('Netzwerkfehler:', err);
    });
}

function verifyItem(itemId) {
    const row = document.getElementById('row-' + itemId);
    if (!row) return;

    // Zeile sanft ausblenden
    row.style.transition = 'all 0.3s ease';
    row.style.opacity = '0';
    row.style.transform = 'scale(0.98)';
    
    setTimeout(() => {
        row.style.display = 'none';
        
        // Zähler aktualisieren
        const visibleRows = document.querySelectorAll('.audit-row:not([style*="display: none"])');
        const countSpan = document.getElementById('itemCount');
        if (countSpan) {
            countSpan.innerText = visibleRows.length;
        }

        // Falls alle abgehakt wurden, einen Hinweis einblenden
        if (visibleRows.length === 0) {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-success fw-semibold"><i class="fa-solid fa-circle-check me-2"></i>Alle Gegenstände in diesem Bereich erfolgreich geprüft!</td></tr>`;
            }
        }
    }, 300);
}
</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
