<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// CSV-Header für den Download senden
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="lagerorte_export_' . date('Y-m-d') . '.csv"');

// Ausgabestream öffnen
$output = fopen('php://output', 'w');

// UTF-8 BOM für Excel-Kompatibilität schreiben
fwrite($output, "\xEF\xBB\xBF");

// Kopfzeile schreiben
fputcsv($output, [
    'lagerort_id',
    'Lagerort Name',
    'Vollständiger Pfad'
], ';');

// Alle Lagerorte abfragen
$allLocations = $pdo->query("SELECT id, parent_id, name FROM locations ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($allLocations as $loc) {
    // Hilfsfunktion getLocationPath nutzen, um den hierarchischen Pfad zu ermitteln
    $path = getLocationPath($allLocations, (int)$loc['id']);
    
    fputcsv($output, [
        $loc['id'],
        $loc['name'],
        $path
    ], ';');
}

fclose($output);
exit;
