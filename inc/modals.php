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

<?php if ($unseenRelease): ?>
<!-- AUTOMATISCHES RELEASE-MODAL -->
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
          Verstanden & Schließen
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

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

<!-- Marked.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sicherstellen, dass die Variable existiert, ansonsten einen leeren String verwenden
    const rawContent = <?= json_encode($unseenRelease['content'] ?? '') ?>;
    const contentContainer = document.getElementById('autoReleaseContent');

    // Wenn kein Inhalt vorhanden ist, das Modal gar nicht erst anzeigen
    if (!rawContent) return;

    // Marked options konfigurieren (behandelt \n als <br>)
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true
        });
        contentContainer.innerHTML = marked.parse(rawContent);
    } else {
        contentContainer.innerText = rawContent;
    }

    // Modal automatisch anzeigen
    const modalEl = document.getElementById('autoReleaseModal');
    if (modalEl) {
        const autoModal = new bootstrap.Modal(modalEl);
        autoModal.show();
    }
});

function markReleaseAsSeen(version) {
    fetch('api/mark-release-seen.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ version: version })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Release v' + version + ' als gesehen markiert.');
        } else {
            console.error('API-Fehler:', data.error);
        }
    })
    .catch(err => console.error('Fehler beim Aktualisieren:', err));
}
</script>

