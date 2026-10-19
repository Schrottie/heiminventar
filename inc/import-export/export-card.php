<?php
// /inc/import-export/export-card.php
?>
<div class="card shadow-sm h-100">
    <div class="card-header bg-transparent">
        <i class="fa-solid fa-file-excel text-success me-2"></i>Daten exportieren &amp; Backups
    </div>
    <div class="card-body d-flex flex-column justify-content-between">
        <div>
            <p class="text-muted small">
                Lade Inventardaten als CSV, exportiere Lagerort-IDs oder sichere das gesamte System inklusive Datenbank, Bildern und Dokumenten.
            </p>
        </div>
        <div class="d-flex flex-column gap-2">
            <a href="api/export-csv.php" class="btn btn-success w-100">
                <i class="fa-solid fa-download me-2"></i>Erweiterter Inventar-Export (CSV)
            </a>
            <a href="api/export-locations-csv.php" class="btn btn-outline-success w-100">
                <i class="fa-solid fa-sitemap me-2"></i>Lagerort-IDs Exportieren
            </a>
            
            <a href="api/export-backup.php" class="btn btn-outline-primary w-100 mt-2">
                <i class="fa-solid fa-box-archive me-2"></i>Komplettes System-Backup (ZIP)
            </a>

            <!-- "Teilen"-Button: Nur auf Smartphones und Tablets sichtbar (d-md-none) -->
            <button type="button" class="btn btn-outline-info w-100 d-md-none" onclick="shareBackupZip()">
                <i class="fa-solid fa-share-nodes me-2"></i>Backup teilen / in Drive speichern
            </button>
        </div>
    </div>
</div>

<script>
async function shareBackupZip() {
    try {
        const response = await fetch('api/export-backup.php');
        const blob = await response.blob();
        const file = new File([blob], 'inventar_backup_<?= date("Y-m-d") ?>.zip', { type: 'application/zip' });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                title: 'Inventar System Backup',
                text: 'Sicherungsdatei des Inventarsystems',
                files: [file]
            });
        } else {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'inventar_backup_<?= date("Y-m-d") ?>.zip';
            a.click();
        }
    } catch (err) {
        console.error('Fehler beim Teilen:', err);
    }
}
</script>
