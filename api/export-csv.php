<?php
require_once __DIR__ . '/../cfg/db.php';

// Header für CSV-Download setzen
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment-filename=inventar_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// UTF-8 BOM für Excel-Kompatibilität mitschicken
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV-Spaltenüberschriften
fputcsv($output, ['Bezeichnung', 'Beschreibung', 'Menge', 'Mindestbestand', 'Preis', 'Ablaufdatum', 'Verliehen an', 'Lagerort'], ';');

// Daten aus der Datenbank abfragen
$stmt = $pdo->query("
    SELECT i.name, i.description, i.quantity, i.min_quantity, i.price, i.expiration_date, i.loan_to, l.name AS location_name
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    WHERE i.is_archived = '0'
    ORDER BY i.name
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
        $row['location_name']
    ], ';');
}

fclose($output);
exit;
