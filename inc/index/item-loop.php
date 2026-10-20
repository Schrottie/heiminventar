    <?php foreach ($items as $item): ?>
        <?php
        $itemId = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $minQty = (int)$item['min_quantity'];
        $isLowStock = ($minQty >= 0 && $qty < $minQty);
        $lentCount = (int)$item['lent_count'];
        $isOnLoan = ($lentCount > 0);
        $isSet = !empty($item['is_set']);
       
        $expStatus = getExpirationStatus($item['expiration_date'] ?? null);
        $isExpiredOrWarning = ($expStatus === 'danger' || $expStatus === 'warning') ? '1' : '0';

        $maintStatus = getMaintenanceStatus($item['maintenance_interval_months'] ?? 0, $item['last_maintenance_date'] ?? null);
        $isMaintenanceDue = ($maintStatus === 'overdue' || $maintStatus === 'warning') ? '1' : '0';

        $path = getLocationPath($allLocations, (int)$item['location_id']);
        $hasLocationPath = (!empty($item['location_name']) && $path !== $item['location_name']);

        $searchTerms = mb_strtolower($item['name'] . ' ' . ($item['location_name'] ?? '') . ' ' . $path . ' ' . ($item['lent_details'] ?? ''));
       
        // Tags as Array für das data-Attribute aufbereiten
        $itemTagsArray = !empty($item['item_tags_list']) ? explode('||', $item['item_tags_list']) : [];
        $itemTagsJson = htmlspecialchars(json_encode($itemTagsArray), ENT_QUOTES, 'UTF-8');

        $hasImage = $showImages && !empty($item['image_filename']);

        // Styling für Karte je nach Status
        $cardStyleClass = '';
        if ($isLowStock) {
            $cardStyleClass = 'border-danger-subtle bg-danger-subtle bg-opacity-10';
        } elseif ($isOnLoan) {
            $cardStyleClass = 'border-info-subtle bg-info-subtle bg-opacity-10';
        }
        ?>

        <div class="card shadow-sm mb-2 inventory-item <?= $cardStyleClass ?>"
             data-search="<?= htmlspecialchars($searchTerms) ?>"
             data-tags="<?= $itemTagsJson ?>"
             data-low-stock="<?= $isLowStock ? '1' : '0' ?>"
             data-expired="<?= $isExpiredOrWarning ?>"
             data-maintenance="<?= $isMaintenanceDue ?>">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                   
                    <!-- Linker Bereich: Name, Badges & Lagerort -->
                    <div class="flex-grow-1 me-3 mw-0">
                        <h6 class="mb-1 text-break">
                            <?php if ($isSet): ?>
                                <i class="fa-solid fa-layer-group text-primary me-1" title="Set"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-box text-primary me-1" title="Gegenstand"></i>
                            <?php endif; ?>
                           
                            <?= htmlspecialchars($item['name']) ?>
                           
                            <span class="d-inline-block">

                                <span class="badge <?= $isLowStock ? 'bg-danger' : 'bg-secondary' ?> ms-1">
                                    x<?= $qty ?>
                                </span>

                                <?php if (!empty($itemTagsArray)): ?>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle d-none d-md-inline-flex align-items-center gap-1" title="Tags: <?= htmlspecialchars(implode(', ', $itemTagsArray)) ?>">
                                        <i class="fa-solid fa-tag" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($itemTagsArray[0]) ?><?php if(count($itemTagsArray) > 1): ?> +<?= count($itemTagsArray) - 1 ?><?php endif; ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($isLowStock): ?>
                                    <span class="badge bg-warning text-dark ms-1" title="Mindestbestand: <?= $minQty ?>">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Min: <?= $minQty ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($isOnLoan): ?>
                                    <span class="badge bg-info text-dark ms-1" title="Verliehene Exemplare: <?= $lentCount ?> / <?= $qty ?>">
                                        <i class="fa-solid fa-handshake me-1"></i> Verliehen: <?= $lentCount ?> / <?= $qty ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($item['expiration_date'])):
                                    $badgeClass = ($expStatus === 'danger') ? 'bg-danger text-white' : (($expStatus === 'warning') ? 'bg-warning text-dark' : 'text-muted');
                                ?>
                                    <span class="badge <?= $badgeClass ?>" title="Ablaufdatum: <?= htmlspecialchars($item['expiration_date']) ?>">
                                        <i class="fa-solid fa-calendar-days me-1"></i> <?= htmlspecialchars($item['expiration_date']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($isMaintenanceDue === '1'):
                                    $maintBadgeClass = ($maintStatus === 'overdue') ? 'bg-danger text-white' : 'bg-info text-dark';
                                    $maintText = ($maintStatus === 'overdue') ? 'Wartung überfällig' : 'Wartung fällig';
                                ?>
                                    <span class="badge <?= $maintBadgeClass ?> ms-1" title="Wartungsintervall: Alle <?= (int)$item['maintenance_interval_months'] ?> Monate (Letzte: <?= htmlspecialchars($item['last_maintenance_date'] ?? 'Nie') ?>)">
                                        <i class="fa-solid fa-screwdriver-wrench me-1"></i> <?= $maintText ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </h6>

                        <small class="d-block text-truncate">
                            <a class="text-decoration-none text-muted d-inline-block" role="button"
                            onclick="toggleLocationPath(this, event)"
                            data-short="<?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?>"
                            data-full="<?= htmlspecialchars($path) ?>">
                                <i class="fa-solid <?= $hasLocationPath ? 'fa-sitemap' : 'fa-location-dot' ?> me-1" <?= $hasLocationPath ? 'title="Verschachtelter Lagerort"' : '' ?>></i>
                                <span class="location-text"><?= htmlspecialchars($item['location_name'] ?? 'Kein Lagerort') ?></span>
                            </a>
                        </small>
                    </div>

                    <!-- Rechter Bereich: Vorschaubild + Navigations-Button -->
                    <div class="flex-shrink-0 d-flex align-items-center gap-2">
                        <?php if ($hasImage): ?>
                            <img src="img/items/<?= htmlspecialchars($item['image_filename']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 class="rounded object-fit-cover img-zoomable"
                                 style="width: 50px; height: 50px;">
                        <?php endif; ?>

                        <a href="item.php?id=<?= $itemId ?>" class="btn btn-sm btn-outline-primary" title="Details anzeigen">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
