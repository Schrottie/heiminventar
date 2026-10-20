
<?php if ($page === 'settings.php'): ?>
<!-- Modal: Benutzer bearbeiten -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-gear me-2"></i>Benutzer bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="POST" action="settings.php">
                <div class="modal-body">
                    <input type="hidden" name="action_edit_user" value="1">
                    <input type="hidden" name="edit_user_id" id="modalUserId">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Benutzername</label>
                        <input type="text" class="form-control" id="modalUsername" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rolle</label>
                        <select class="form-select" name="edit_role" id="modalRole">
                            <option value="user">User (Standard-Zugriff)</option>
                            <option value="admin">Admin (Vollzugriff & Einstellungen)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Neues Passwort vergeben</label>
                        <input type="password" class="form-control" name="edit_password" placeholder="Leer lassen für unverändertes Passwort">
                        <div class="form-text">Nur ausfüllen, wenn das Passwort dieses Benutzers geändert werden soll.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal für Sicherheitsabfrage beim Aktivieren des Passwortschutzes -->
<div class="modal fade" id="enableAuthModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Achtung: Passwortschutz aktivieren</h5>
                <button type="button" class="btn-close" id="cancelAuthModalBtn" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Sie aktivieren den Passwortschutz. Da aktuell <strong>keine weiteren Benutzer</strong> angelegt sind, verwenden Sie bitte das Standard-Konto zum Anmelden:</p>
                
                <div class="card bg-light border-warning mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5 fw-bold">Benutzername:</div>
                            <div class="col-7"><code class="fs-6">admin</code></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-5 fw-bold">Standard-Passwort:</div>
                            <div class="col-7"><code class="fs-6">admin123</code></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="fa-solid fa-lightbulb me-1"></i> <strong>Empfehlung:</strong> Ändern Sie nach dem Login das Passwort des Standard-Admins oder legen Sie einen eigenen Benutzer an.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="confirmCancelAuth" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-warning fw-bold" id="confirmEnableAuth">Verstanden & Aktivieren</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal für QR-Code & Druck -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-qrcode me-2"></i>QR-Code Etikett</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body text-center" id="printableLabel">
                <div class="border p-3 d-inline-block rounded bg-white" style="max-width: 300px;">
                    <h6 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($item['name']) ?></h6>
                    <div id="qrcodeCanvas" class="d-flex justify-content-center my-2"></div>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">ID: #<?= (int)$item['id'] ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Etikett drucken
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Kamera-QR-Scanner -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-camera me-2"></i>QR-Code scannen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-reader-results" class="mt-2 text-muted small">Kamera wird gestartet...</div>
            </div>
        </div>
    </div>
</div>

<?php if ($page === 'history.php'): ?>
<!-- Rollback Bestätigungs-Modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle text-warning-emphasis">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="fa-solid fa-rotate-left me-2"></i>Aktion rückgängig machen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Möchtest du folgende Änderung für <strong id="rollbackItemName"></strong> wirklich rückgängig machen?</p>
                
                <div class="card bg-body-tertiary border-0 p-3 mb-3">
                    <div id="rollbackDetailsText" class="fw-bold text-body"></div>
                </div>

                <div class="text-muted small">
                    <i class="fa-solid fa-circle-info me-1"></i> Der Zustand des Gegenstands wird auf den Wert vor dieser Änderung zurückgesetzt.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" id="btnConfirmRollback" class="btn btn-sm btn-warning">
                    <i class="fa-solid fa-check me-1"></i> Ja, Rollback ausführen
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($unseenRelease): ?>
<!-- Releasemodal -->
<div class="modal fade" id="autoReleaseModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">✨ Was ist neu in v<?= htmlspecialchars($unseenRelease['version']) ?>?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="markReleaseAsSeen('<?= htmlspecialchars($unseenRelease['version']) ?>')"></button>
      </div>
      <div class="modal-body">
        <h4 class="fw-bold text-primary"><?= htmlspecialchars($unseenRelease['title']) ?></h4>
        <hr>
        <div id="autoReleaseContent" class="markdown-body"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="markReleaseAsSeen('<?= htmlspecialchars($unseenRelease['version']) ?>')">
          <i class="fa-solid fa-check me-1"></i>Wow, toll! 
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($page === 'archive.php'): ?>
<!-- Reaktivieren archivierter Items mit Ziel-Lagerort-Auswahl -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa-solid fa-rotate-left me-2"></i>Gegenstand reaktivieren</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Wähle einen Lagerort aus, in den <strong><span id="restoreItemName"></span></strong> zurückgelegt werden soll:</p>
                <input type="hidden" id="restoreItemId">
                
                <div class="mb-3">
                    <label class="form-label">Ziel-Lagerort</label>
                    <select id="restoreLocationSelect" class="form-select" required>
                        <option value="">Bitte auswählen...</option>
                        <?php foreach ($locations as $loc): ?>
                            <?php $indent = $loc['depth'] > 0 ? str_repeat('— ', $loc['depth']) . ' ' : ''; ?>
                            <option value="<?= (int)$loc['id'] ?>"><?= $indent ?><?= htmlspecialchars($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-success" onclick="submitRestore()">Reaktivieren bestätigen</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($page === 'locations.php'): ?>
<!-- Modal: Lagerort bearbeiten -->
<div class="modal fade" id="editLocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editLocationForm" onsubmit="submitEditLocation(event)">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Lagerort bearbeiten</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editLocationId">

                    <div class="mb-3">
                        <label class="form-label">Bezeichnung</label>
                        <input type="text" name="name" id="editLocationName" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Übergeordnet</label>
                        <select name="parent_id" id="editLocationParent" class="form-select">
                            <option value="">- Oberste Ebene -</option>
                            <?php 
                            if (isset($pdo)) {
                                $modalLocationsRaw = $pdo->query("SELECT id, parent_id, name FROM locations")->fetchAll(PDO::FETCH_ASSOC);
                                
                                function sortModalLocations(array $locations, $parentId = null, int $depth = 0): array {
                                    $result = [];
                                    $children = array_filter($locations, fn($loc) => (empty($loc['parent_id']) ? null : (int)$loc['parent_id']) === (empty($parentId) ? null : (int)$parentId));
                                    usort($children, fn($a, $b) => strcasecmp($a['name'], $b['name']));
                                    foreach ($children as $child) {
                                        $child['depth'] = $depth;
                                        $result[] = $child;
                                        $result = array_merge($result, sortModalLocations($locations, $child['id'], $depth + 1));
                                    }
                                    return $result;
                                }
                                
                                foreach (sortModalLocations($modalLocationsRaw) as $loc) {
                                    $indent = $loc['depth'] > 0 ? str_repeat('— ', $loc['depth']) . ' ' : '';
                                    echo '<option value="' . (int)$loc['id'] . '">' . $indent . htmlspecialchars($loc['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <div class="form-text">Ein Lagerort kann sich nicht selbst als Elternteil haben.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Lagerort löschen -->
<div class="modal fade" id="deleteLocationModal" tabindex="-1" aria-labelledby="deleteLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteLocationModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Lagerort löschen: <span id="deleteLocationName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Dynamischer Inhalt aus der AJAX-Abfrage -->
                <div id="deleteLocationInfo">
                    Wird geladen...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" id="confirmDeleteLocation" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-trash me-1"></i>Endgültig löschen
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($page === 'item.php'): ?>
<!-- Sicherheitsabfrage zum Archivieren von Items -->
<div class="modal fade" id="archiveConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-box-archive me-2"></i>Gegenstand archivieren</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Möchtest du den Gegenstand <strong id="archiveItemNameText"></strong> wirklich archivieren?</p>
                <p class="text-muted small mb-0">Er wird aus dem aktiven Bestand entfernt, kann aber über das Archiv jederzeit wiederhergestellt werden.</p>
                <input type="hidden" id="archiveTargetId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" onclick="executeArchive()">Ja, archivieren</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Set-Inhalte verwalten -->
<div class="modal fade" id="setItemModal" tabindex="-1" aria-labelledby="setItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="setItemModalLabel"><i class="fa-solid fa-layer-group me-2"></i>Set-Inhalte verwalten</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Formular zum Hinzufügen eines Inhaltsteils -->
                <form id="addSetItemForm" class="row g-2 mb-3 align-items-end">
                    <input type="hidden" id="modalSetItemId" name="item_id" value="<?= (int)($id ?? 0) ?>">
                    <div class="col-md-7">
                        <label class="form-label small">Inhaltsteil / Bezeichnung</label>
                        <input type="text" id="newSetItemName" name="name" class="form-control form-control-sm" placeholder="z.B. Inbusschlüssel 4mm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Anzahl</label>
                        <input type="number" id="newSetItemQty" name="quantity" class="form-control form-control-sm" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="fa-solid fa-plus me-1"></i>Hinzufügen
                        </button>
                    </div>
                </form>

                <hr>

                <!-- Liste der Set-Inhalte (Sortierbar via SortableJS) -->
                <p class="text-muted small mb-2"><i class="fa-solid fa-arrows-up-down-left-right me-1"></i>Einträge per Drag &amp; Drop umsortieren:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center"><i class="fa-solid fa-grip-vertical"></i></th>
                                <th>Bezeichnung</th>
                                <th class="text-center" style="width: 100px;">Anzahl</th>
                                <th class="text-end" style="width: 80px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody id="setItemListContainer">
                            <!-- Per AJAX geladen -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fertig</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Dokument löschen bestätigen -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDocumentModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Dokument löschen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                Möchten Sie das Dokument <strong id="deleteDocTitlePlaceholder"></strong> wirklich unwiderruflich löschen?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <a href="#" id="confirmDeleteDocumentBtn" class="btn btn-danger">Ja, Dokument löschen</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Exemplar verleihen -->
<div class="modal fade" id="lendUnitModal" tabindex="-1" aria-labelledby="lendUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="api/unit-lend.php" method="post" class="modal-content">
            <input type="hidden" name="unit_id" id="lendUnitId">
            <input type="hidden" name="item_id" value="<?= (int)$id ?>">
            
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="lendUnitModalLabel"><i class="fa-solid fa-handshake me-2"></i>Exemplar <span id="lendUnitNumberDisplay"></span> verleihen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Verliehen an (Name / Person)*</label>
                    <input type="text" name="borrower_name" class="form-control" required placeholder="z. B. Max Mustermann">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Verleihdatum</label>
                    <input type="date" name="lent_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notiz (optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Z. B. Telefonnummer oder Rücksprache..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-warning text-dark fw-semibold">Verleih speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Exemplar zurückbringen -->
<div class="modal fade" id="returnUnitModal" tabindex="-1" aria-labelledby="returnUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="returnUnitModalLabel"><i class="fa-solid fa-rotate-left me-2"></i>Exemplar ins Lager zurückbringen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                Soll das Exemplar <strong id="returnUnitNumberDisplay"></strong> (verliehen an <span id="returnBorrowerDisplay" class="fw-semibold"></span>) wieder als <strong>im Lager verfügbar</strong> markiert werden?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <a href="#" id="confirmReturnUnitBtn" class="btn btn-success">Ja, ins Lager zurück</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Anbauteil / Zubehör hinzufügen oder bearbeiten -->
<div class="modal fade" id="partModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="api/part-save.php" method="POST" enctype="multipart/form-data" id="partForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="partModalTitle"><i class="fa-solid fa-puzzle-piece me-2"></i>Anbauteil hinzufügen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="item_id" value="<?= (int)$id ?>">
                    <input type="hidden" name="part_id" id="partIdInput" value="">

                    <div class="mb-3">
                        <label class="form-label">Bezeichnung des Anbauteils *</label>
                        <input type="text" name="name" id="partNameInput" class="form-control" required placeholder="z. B. Spezial-Sattel, Fahrradcomputer">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Anzahl</label>
                            <input type="number" name="quantity" id="partQuantityInput" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Preis in €</label>
                            <input type="text" name="price" id="partPriceInput" class="form-control" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rechnungsnachweis</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="invoice_status" id="invStatusOwn" value="own" checked onchange="togglePartInvoiceFields()">
                            <label class="form-check-label" for="invStatusOwn">
                                Eigene Rechnung hochladen
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="invoice_status" id="invStatusParent" value="parent" onchange="togglePartInvoiceFields()">
                            <label class="form-check-label" for="invStatusParent">
                                Auf der Hauptrechnung des Gegenstands enthalten
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="invoice_status" id="invStatusNone" value="none" onchange="togglePartInvoiceFields()">
                            <label class="form-check-label" for="invStatusNone">
                                Keine Rechnung vorhanden
                            </label>
                        </div>
                    </div>

                    <!-- Eigener Rechnungs-Upload -->
                    <div class="mb-3" id="partInvoiceFileContainer">
                        <label class="form-label small text-muted">Rechnungsdatei (PDF / Bild)</label>
                        <div id="existingPartInvoiceInfo" class="mb-2 small text-success d-none">
                            <i class="fa-solid fa-file-invoice me-1"></i> Aktuelle Datei: <a href="#" id="existingPartInvoiceLink" target="_blank">Anzeigen</a>
                            <span class="text-muted ms-2">(Neue Datei wählen zum Ersetzen)</span>
                        </div>
                        <input type="file" name="invoice_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary" id="partSubmitBtn">Anbauteil speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Anbauteil löschen bestätigen -->
<div class="modal fade" id="deletePartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Anbauteil löschen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Möchtest du das Anbauteil <strong id="deletePartNameDisplay"></strong> wirklich unwiderruflich löschen?</p>
            </div>
            <div class="modal-footer">
                <form action="api/part-delete.php" method="POST">
                    <input type="hidden" name="part_id" id="deletePartIdInput">
                    <input type="hidden" name="item_id" value="<?= (int)$id ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Unwiderruflich löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal zum Löschen von Bildern -->
<div class="modal fade" id="imageDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="api/image-delete.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Bild löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p>Mist du sicher, dass du dieses Bild unwiderruflich löschen möchtest?</p>
                    <input type="hidden" name="image_id" id="modal_image_id">
                    <input type="hidden" name="item_id" id="modal_item_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Universal Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark bg-opacity-75 border-0 shadow-lg">
            <div class="modal-body text-center position-relative p-4">
                <!-- Schließen-Button als btn-primary mit X -->
                <button type="button" class="btn btn-primary position-absolute top-0 end-0 m-3 z-3 rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 40px; height: 40px;" data-bs-dismiss="modal" aria-label="Schließen">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img src="" id="modalImagePreview" class="img-fluid rounded shadow" alt="Vollbild" style="max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>