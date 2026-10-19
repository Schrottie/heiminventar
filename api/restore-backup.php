<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['backup_zip'])) {
    header('Location: ../import-export.php?error=Keine Datei hochgeladen.');
    exit;
}

$file = $_FILES['backup_zip'];
$mode = $_POST['restore_mode'] ?? 'merge'; // 'merge' (Zusammenführen) oder 'overwrite' (Vollständig)

if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../import-export.php?error=Fehler beim Datei-Upload.');
    exit;
}

if (!class_exists('ZipArchive')) {
    header('Location: ../import-export.php?error=PHP ZipArchive Erweiterung fehlt.');
    exit;
}

$zip = new ZipArchive();
if ($zip->open($file['tmp_name']) !== TRUE) {
    header('Location: ../import-export.php?error=Ungültiges ZIP-Archiv.');
    exit;
}

// Temporären Ordner zum Entpacken anlegen
$tempDir = sys_get_temp_dir() . '/restore_' . uniqid();
mkdir($tempDir, 0777, true);
$zip->extractTo($tempDir);
$zip->close();

try {
    $pdo->beginTransaction();

    // 1. SQL-Dump verarbeiten
    $sqlFile = $tempDir . '/database_dump.sql';
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);

        if ($mode === 'overwrite') {
            // Vollständiger Reset & Import
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec($sqlContent);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        } else {
            // Smart Merge Mode: INSERT statements anpassen zu INSERT ... ON DUPLICATE KEY UPDATE
            // Oder SQL zeilenweise abarbeiten
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            foreach ($statements as $stmt) {
                if (empty($stmt) || str_starts_with($stmt, '--') || str_starts_with($stmt, 'DROP TABLE') || str_starts_with($stmt, 'CREATE TABLE') || str_starts_with($stmt, 'SET ')) {
                    continue;
                }

                // In 'ON DUPLICATE KEY UPDATE' umwandeln, wenn es ein INSERT ist
                if (str_starts_with(strtoupper($stmt), 'INSERT INTO')) {
                    // Falls doppelte Schlüssel existieren, Werte aus dem Backup aktualisieren
                    // Alternativ: INSERT IGNORE INTO nutzen, um bestehende zu behalten
                    $mergeStmt = preg_replace('/^INSERT INTO/i', 'INSERT IGNORE INTO', $stmt);
                    $pdo->exec($mergeStmt);
                }
            }
        }
    }

    // 2. Medien-Dateien wiederherstellen (Bilder & Dokumente)
    $mediaMappings = [
        $tempDir . '/img/items' => __DIR__ . '/../img/items',
        $tempDir . '/docs/items' => __DIR__ . '/../docs/items'
    ];

    foreach ($mediaMappings as $sourceDir => $targetDir) {
        if (is_dir($sourceDir)) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $f) {
                if ($f->isFile()) {
                    $targetFile = $targetDir . '/' . $f->getFilename();
                    // Kopieren: bestehende Bilder/Docs werden aktualisiert, nicht vorhandene hinzugefügt
                    copy($f->getRealPath(), $targetFile);
                }
            }
        }
    }

    $pdo->commit();

    // Aufräumen
    array_map('unlink', glob("$tempDir/*.*"));
    @rmdir($tempDir);

    header('Location: ../import-export.php?success=System-Backup erfolgreich eingespielt.');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../import-export.php?error=Fehler beim Einspielen: ' . urlencode($e->getMessage()));
    exit;
}
