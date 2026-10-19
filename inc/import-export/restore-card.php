<?php
// /inc/import-export/restore-card.php
?>
<div class="card shadow-sm h-100">
    <div class="card-header bg-transparent">
        <i class="fa-solid fa-box-archive text-primary me-2"></i>Komplettes Backup wiederherstellen
    </div>
    <div class="card-body d-flex flex-column justify-content-between">
        <div>
            <p class="text-muted small mb-3">
                Spiele ein zuvor erstelltes System-Backup (ZIP-Archiv mit Datenbank-Dump, Bildern und Dokumenten) wieder ein.
            </p>

            <form action="api/restore-backup.php" method="post" enctype="multipart/form-data" id="restoreBackupForm">
                <div class="mb-3">
                    <label for="backup_zip" class="form-label small fw-bold">ZIP-Backup Datei auswählen</label>
                    <input type="file" class="form-control" id="backup_zip" name="backup_zip" accept=".zip" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Wiederherstellungs-Modus</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="restore_mode" id="modeMerge" value="merge" checked>
                        <label class="form-check-label small" for="modeMerge">
                            <strong>Zusammenführen (Empfohlen)</strong><br>
                            <span class="text-muted">Fehlende Gegenstände/Medien werden ergänzt, bestehende Daten bleiben unberührt.</span>
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="restore_mode" id="modeOverwrite" value="overwrite">
                        <label class="form-check-label small text-danger" for="modeOverwrite">
                            <strong>Vollständig überschreiben</strong><br>
                            <span class="text-muted">Setzt die Datenbank exakt auf den Stand des Backups zurück (Bestehendes wird überschrieben).</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-outline-primary w-100" onclick="return confirm('Möchtest du das Backup jetzt einspielen?');">
                    <i class="fa-solid fa-rotate-left me-2"></i>Backup wiederherstellen
                </button>
            </form>
        </div>
    </div>
</div>
