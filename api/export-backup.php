<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Prüfen ob ZipArchive verfügbar ist
if (!class_exists('ZipArchive')) {
    die('Die PHP ZipArchive-Erweiterung ist auf diesem Server nicht aktiviert.');
}

$backupFileName = 'inventar_komplett_backup_' . date('Y-m-d_H-i-s') . '.zip';

$zip = new ZipArchive();$tempFile = tempnam(sys_get_temp_dir(), 'zip');

if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die('Konnte temporäres ZIP-Archiv nicht erstellen.');
}

// A. SQL-Datenbank-Dump erzeugen
$tables = [];
$result =$pdo->query("SHOW TABLES");
while ($row =$result->fetch(PDO::FETCH_NUM)) {
    $tables[] =$row[0];
}

$sqlDump = "-- Inventar System Backup\n-- Erstellt am: " . date('Y-m-d H:i:s') . "\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {$sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
    $row =$pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $sqlDump .=$row[1] . ";\n\n";

    $rows =$pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $dataRow) {$fields = array_keys($dataRow);$values = array_values($dataRow);$escapedValues = array_map(function($val) use ($pdo) {
            if ($val === null) return "NULL";
            return $pdo->quote($val);
        }, $values);

        $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n";
    }
    $sqlDump .= "\n";
}
$sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// SQL-Dump als Datei ins ZIP legen
$zip->addFromString('database_dump.sql',$sqlDump);

// B. Medienordner hinzufügen (Bilder & Dokumente)
$foldersToAdd = [
    __DIR__ . '/../img/items/' => 'img/items/',
    __DIR__ . '/../docs/items/' => 'docs/items/'
];

foreach ($foldersToAdd as $dirPath =>$zipPath) {
    if (is_dir($dirPath)) {$files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as$file) {
            if (!$file->isDir()) {
                $filePath =$file->getRealPath();
                $relativePath =$zipPath . basename($filePath);$zip->addFile($filePath,$relativePath);
            }
        }
    }
}

$zip->close();

// Download-Header senden
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $backupFileName . '"');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);

// Temporäre Datei aufräumen
unlink($tempFile);
exit;
