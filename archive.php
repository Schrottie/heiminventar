<?php
require_once __DIR__ . '/cfg/db.php';
require_once __DIR__ . '/inc/functions.php';

// Alle Lagerorte für das Reaktivierungs-Modal laden (nutzt idealerweise deine hierarchische Sortierung)
$locationsRaw = $pdo->query("SELECT id, parent_id, name FROM locations")->fetchAll(PDO::FETCH_ASSOC);
// Hierarchie-Sortierung wie zuvor besprochen anwenden
function sortLocationsHierarchically(array $locations, $parentId = null, int $depth = 0): array {
    $result = [];
    $children = array_filter($locations, fn($loc) => (empty($loc['parent_id']) ? null : (int)$loc['parent_id']) === (empty($parentId) ? null : (int)$parentId));
    usort($children, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    foreach ($children as $child) {
        $child['depth'] = $depth;
        $result[] = $child;
        $result = array_merge($result, sortLocationsHierarchically($locations, $child['id'], $depth + 1));
    }
    return $result;
}
$locations = sortLocationsHierarchically($locationsRaw);

// Alle archivierten Items abfragen
$archivedItems = $pdo->query("SELECT * FROM inventory_items WHERE is_archived = 1 ORDER BY archived_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/inc/header.php';
?>

<div class="container py-4 flex-grow-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-box-archive me-2"></i>Archiv / Papierkorb</h2>
            <p class="text-muted mb-0">Übersicht aller archivierten Gegenstände. Sie können jederzeit reaktiviert werden.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">Zurück zur Hauptübersicht</a>
    </div>

    <!-- Live-Suchfeld -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <input type="text" id="archiveSearchInput" class="form-control" placeholder="🔍 Nach Bezeichnung oder Beschreibung filtern...">
        </div>
    </div>

    <!-- Tabelle der archivierten Items -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="archivedTable">
                    <thead class="table-light">
                        <tr>
                            <th>Bezeichnung</th>
                            <th>Beschreibung</th>
                            <th>Menge</th>
                            <th>Archiviert am</th>
                            <th class="text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($archivedItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Das Archiv ist leer.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archivedItems as $item): ?>
                                <tr data-search-text="<?= htmlspecialchars(strtolower($item['name'] . ' ' . $item['description'])) ?>">
                                    <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="text-muted text-truncate" style="max-width: 300px;"><?= htmlspecialchars($item['description']) ?></td>
                                    <td><span class="badge bg-secondary"><?= (int)$item['quantity'] ?></span></td>
                                    <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($item['archived_at'])) ?></small></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="openRestoreModal(<?= (int)$item['id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-rotate-left me-1"></i>Reaktivieren
                                        </button>
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



<script>
// 1. Live-Suchfilter (reagiert sofort auf Tiptypen)
document.getElementById('archiveSearchInput').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#archivedTable tbody tr[data-search-text]');

    rows.forEach(row => {
        const text = row.getAttribute('data-search-text');
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// 2. Modal öffnen
function openRestoreModal(id, name) {
    document.getElementById('restoreItemId').value = id;
    document.getElementById('restoreItemName').innerText = name;
    document.getElementById('restoreLocationSelect').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}

// 3. Reaktivierung absenden
function submitRestore() {
    const id = document.getElementById('restoreItemId').value;
    const locationId = document.getElementById('restoreLocationSelect').value;

    if (!locationId) {
        alert('Bitte wähle einen Lagerort aus.');
        return;
    }

    fetch('api/item-restore.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, location_id: locationId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Seite neu laden, um die Änderung zu sehen
        } else {
            alert('Fehler: ' + data.error);
        }
    })
    .catch(err => console.error('Fehler:', err));
}
</script>

<?php require_once __DIR__ . '/inc/modals.php'; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
