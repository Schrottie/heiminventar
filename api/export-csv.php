<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Dateiname für den Download festlegen
$filename = 'inventar_export_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM für Excel UTF-8 Kompatibilität
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header-Zeile mit allen relevanten Attributen schreiben
fputcsv($output, [
    'ID',
    'Bezeichnung',
    'Hersteller',
    'Beschreibung',
    'Menge',
    'Mindestbestand',
    'Schuettgut',
    'Preis',
    'Kaufdatum',
    'Kaufort',
    'Garantie_Monate',
    'Versichert',
    'Ablaufdatum',
    'Wartung_Intervall_Monate',
    'Letzte_Wartung',
    'Lagerort_ID',
    'Lagerort',
    'Erstellt_Am'
], ';');

// Alle Items inkl. Lagerort-Namen abrufen
$sql = "
    SELECT 
        i.*, 
        l.name AS location_name 
    FROM inventory_items i 
    LEFT JOIN locations l ON l.id = i.location_id 
    WHERE i.is_archived = '0' 
    ORDER BY i.name ASC
";
$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['manufacturer'],
        $row['description'],
        $row['quantity'],
        $row['min_quantity'],
        $row['is_bulk'] ? 'Ja' : 'Nein',
        $row['price'] !== null ? number_format($row['price'], 2, ',', '') : '',
        $row['purchase_date'],
        $row['purchase_location'],
        $row['warranty_months'],
        $row['is_insured'] ? 'Ja' : 'Nein',
        $row['expiration_date'],
        $row['maintenance_interval_months'],
        $row['last_maintenance_date'],
        $row['location_id'],
        $row['location_name'],
        $row['created_at']
    ], ';');
}

fclose($output);
exit;
