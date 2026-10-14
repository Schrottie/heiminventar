<?php
require_once __DIR__ . '/../cfg/db.php';

// CSV-Header für den Download senden (verhindert das .php-Problem)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventar_export_' . date('Y-m-d') . '.csv"');

// Ausgabestream öffnen
$output = fopen('php://output', 'w');

// UTF-8 BOM für Excel-Kompatibilität schreiben
fwrite($output, "\xEF\xBB\xBF");

// Kopfzeile schreiben (inkl. lagerort_id und Lagerort)
fputcsv($output, [
    'Bezeichnung',
    'Beschreibung',
    'Menge',
    'Mindestbestand',
    'Preis',
    'Ablaufdatum',
    'Verliehen an',
    'lagerort_id',
    'Lagerort'
], ';');

// Alle aktiven Gegenstände aus der Datenbank abfragen (inkl. Lagerort-Name)
$stmt = $pdo->query("
    SELECT 
        i.name, 
        i.description, 
        i.quantity, 
        i.min_quantity, 
        i.price, 
        i.expiration_date, 
        i.loan_to, 
        i.location_id,
        l.name AS location_name
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    WHERE i.is_archived = '0'
    ORDER BY i.name ASC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['name'],
        $row['description'],
        $row['quantity'],
        $row['min_quantity'],
        $row['price'],
        $row['expiration_date'],
        $row['loan_to'],
        $row['location_id'],
        $row['location_name']
    ], ';');
}

fclose($output);
exit;
