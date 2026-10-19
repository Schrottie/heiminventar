<?php
// /inc/index/search.php - Modus: Umfassende Suche mit Paginierung & Tag-Unterstützung
$currentSearchQuery = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$items = [];
$totalItems = 0;
$totalPages = 1;

// Nur suchen, wenn tatsächlich ein Suchbegriff eingegeben wurde!
if ($currentSearchQuery !== '') {

    // 1. Logik-Erkennung: Prüfen, ob eine explizite UND-Verknüpfung gefordert ist
    $hasAndOperator = (bool)preg_match('/(\+|\&|\bund\b|\band\b)/i', $currentSearchQuery);

    if ($hasAndOperator) {
        $logicOperator = 'AND';
        $rawTerms = preg_split('/(\+|\&|\bund\b|\band\b)/i', $currentSearchQuery);
    } else {
        $logicOperator = 'OR';
        $rawTerms = preg_split('/[,;\s]+/', $currentSearchQuery);
    }

    $searchTerms = [];
    foreach ($rawTerms as $term) {
        $cleanTerm = trim($term);
        if ($cleanTerm !== '') {
            $searchTerms[] = $cleanTerm;
        }
    }

    if (empty($searchTerms)) {
        $searchTerms = [$currentSearchQuery];
    }

    // 2. Suchbedingungen über EXISTS-Klauseln aufbauen
    $termWhereClauses = [];
    $queryParams = [];

    foreach ($searchTerms as $term) {
        $wildcard = '%' . $term . '%';
       
        $termWhereClauses[] = "(
            i.name LIKE ? OR
            i.description LIKE ? OR
            i.manufacturer LIKE ? OR
            i.purchase_location LIKE ? OR
            EXISTS (
                SELECT 1 FROM locations l_sub
                WHERE l_sub.id = i.location_id AND l_sub.name LIKE ?
            ) OR
            EXISTS (
                SELECT 1 FROM item_tags it_sub
                JOIN tags t_sub ON t_sub.id = it_sub.tag_id
                WHERE it_sub.item_id = i.id AND t_sub.name LIKE ?
            )
        )";
       
        // 4 Direktfelder + 1 Standort-EXISTS + 1 Tag-EXISTS = 6 Parameter pro Begriff
        for ($i = 0; $i < 6; $i++) {
            $queryParams[] = $wildcard;
        }
    }


    $searchWhereSql = '(' . implode(" $logicOperator ", $termWhereClauses) . ')';

    // A. Gesamtanzahl der Treffer für die Paginierung ermitteln (sauber ohne störende Joins im Count)
    $countSql = "
        SELECT COUNT(i.id)
        FROM inventory_items i
        WHERE i.is_archived = '0'
        AND $searchWhereSql
    ";
    if ($filterLocationId !== null) {
        $countSql .= " AND i.location_id = ?";
    }

    $countStmt = $pdo->prepare($countSql);
    $countParams = $queryParams;
    if ($filterLocationId !== null) {
        $countParams[] = (int)$filterLocationId;
    }
    $countStmt->execute($countParams);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalItems / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    // B. Paginierte Items abrufen
    $sql = "
        SELECT
            i.id, i.name, i.quantity, COALESCE(i.on_loan, 0) AS lent_count, i.min_quantity, i.price, i.purchase_date, i.purchase_location,
            i.manufacturer, i.is_insured, i.is_set, i.location_id, i.expiration_date,
            i.maintenance_interval_months, i.last_maintenance_date,
            l.name AS location_name,
            img.filename AS image_filename,
            GROUP_CONCAT(DISTINCT t.name SEPARATOR '||') AS item_tags_list
        FROM inventory_items i
        LEFT JOIN locations l ON l.id = i.location_id
        LEFT JOIN (
            SELECT item_id, COALESCE(MIN(CASE WHEN is_main = 1 THEN id END), MIN(id)) AS main_id
            FROM item_images GROUP BY item_id
        ) main_img ON main_img.item_id = i.id
        LEFT JOIN item_images img ON img.id = main_img.main_id
        LEFT JOIN item_tags it ON it.item_id = i.id
        LEFT JOIN tags t ON t.id = it.tag_id
        WHERE i.is_archived = '0'
        AND $searchWhereSql
    ";
    if ($filterLocationId !== null) {
        $sql .= " AND i.location_id = ?";
    }
    $sql .= " GROUP BY i.id ORDER BY i.name ASC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $execParams = $queryParams;
    if ($filterLocationId !== null) {
        $execParams[] = (int)$filterLocationId;
    }
    $execParams[] = (int)$perPage;
    $execParams[] = (int)$offset;

    $paramIndex = 1;
    foreach ($execParams as $p) {
        $type = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($paramIndex++, $p, $type);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Nach Namen, Beschreibung, Hersteller, Kaufort oder Tags suchen..." value="<?= htmlspecialchars($currentSearchQuery) ?>" autofocus>
                    <?php if (!empty($currentSearchQuery)): ?>
                        <a href="index.php?mode=search<?= $filterLocationId !== null ? '&location_id=' . $filterLocationId : '' ?>" class="btn btn-outline-secondary d-flex align-items-center" title="Suche zurücksetzen">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-search me-2"></i>Suchen
                </button>
            </div>

            <!-- Modus-Umschalter (Wechsel zur Filterliste) -->
            <div class="col-auto">
                <div class="btn-group" role="group" aria-label="Ansicht Modus">
                    <a href="index.php?mode=list" class="btn btn-outline-primary" title="Live Filterbare Liste">
                        <i class="fa-solid fa-filter"></i>
                    </a>
                    <a href="index.php?mode=search" class="btn btn-primary" title="Umfassende Suche">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="form-text mt-2 text-muted small">
            <i class="fa-solid fa-circle-info me-1"></i> Durchsucht Namen, Beschreibungen, Hersteller, Kauforte und <strong>Tags</strong>. Mehrere Begriffe mit Leerzeichen = ODER. Verwenden Sie <code>+</code>, <code>&amp;</code> oder <code>und</code> für <strong>UND</strong>.
        </div>
    </div>
</div>

<!-- Suchergebnisse / Inventarliste (Item Loop) -->
<div id="inventoryList">
    <?php if (empty($currentSearchQuery)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-magnifying-glass fa-3x mb-3 text-secondary opacity-50"></i>
            <h5>Geben Sie einen Suchbegriff ein, um das Inventar zu durchsuchen.</h5>
            <p class="small text-muted">Es werden erst nach Eingabe eines Suchbegriffs Treffer geladen.</p>
        </div>
    <?php elseif (empty($items)): ?>
        <div class="alert alert-warning text-center">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Keine Gegenstände für "<strong><?= htmlspecialchars($currentSearchQuery) ?></strong>" gefunden.
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="text-muted small">
                <i class="fa-solid fa-list-check me-1"></i><strong><?= $totalItems ?></strong> Gegenstand/Gegenstände gefunden (Seite <?= $page ?> von <?= $totalPages ?>)
            </span>
        </div>

        <?php require_once __DIR__ . '/item-loop.php'; ?>

        <!-- Paginierungs-Navigation -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Suchergebnisse Paginierung" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Zurück -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?mode=search&q=<?= urlencode($currentSearchQuery) ?><?= $filterLocationId !== null ? '&location_id=' . $filterLocationId : '' ?>&page=<?= $page - 1 ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?mode=search&q=<?= urlencode($currentSearchQuery) ?><?= $filterLocationId !== null ? '&location_id=' . $filterLocationId : '' ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Weiter -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?mode=search&q=<?= urlencode($currentSearchQuery) ?><?= $filterLocationId !== null ? '&location_id=' . $filterLocationId : '' ?>&page=<?= $page + 1 ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>
