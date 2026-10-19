<?php
require_once __DIR__ . '/../cfg/db.php';
require_once __DIR__ . '/../inc/functions.php';

// Nur für angemeldete Benutzer oder per Direktaufruf (je nach Auth-Setup im Projekt)
// session_start();
// if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Access denied'); }

// Dateiname für den Download generieren
$filename = 'inventar_export_' . date('Y-m-d') . '.csv';

// HTTP-Header für CSV-Download setzen
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output-Stream öffnen
$output = fopen('php://output', 'w');

// UTF-8 BOM für Excel-Kompatibilität schreiben
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV-Spaltenüberschriften definieren
fputcsv($output, [
    'Typ',
    'ID',
    'Hauptartikel-ID',
    'Name',
    'Beschreibung',
    'Hersteller',
    'Lagerort',
    'Menge',
    'Verliehen',
    'Einzelpreis (€)',
    'Gesamtpreis (€)',
    'Kaufdatum',
    'Kaufort / Händler',
    'Versichert',
    'Ist Set',
    'Ablaufdatum',
    'Tags'
], ';');

// 1. Hauptartikel abrufen (inkl. Lagerort-Namen und Tags)
$sqlItems = "
    SELECT 
        i.*,
        l.name AS location_name,
        GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') AS item_tags
    FROM inventory_items i
    LEFT JOIN locations l ON l.id = i.location_id
    LEFT JOIN item_tags itr ON itr.item_id = i.id
    LEFT JOIN tags t ON t.id = itr.tag_id
    WHERE i.is_archived = '0'
    GROUP BY i.id
    ORDER BY i.name ASC
";

$stmtItems = $pdo->query($sqlItems);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    // Gesamtwert für Hauptartikel berechnen
    $totalPrice = (float)$item['price'] * (int)$item['quantity'];

    // Hauptartikel-Zeile schreiben
    fputcsv($output, [
        'Hauptartikel',
        $item['id'],
        '',
        $item['name'],
        $item['description'],
        $item['manufacturer'],
        $item['location_name'] ?? '',
        $item['quantity'],
        $item['on_loan'] ?? 0,
        number_format((float)$item['price'], 2, ',', ''),
        number_format($totalPrice, 2, ',', ''),
        $item['purchase_date'] ?? '',
        $item['purchase_location'] ?? '',
        ($item['is_insured'] ?? 0) == 1 ? 'Ja' : 'Nein',
        ($item['is_set'] ?? 0) == 1 ? 'Ja' : 'Nein',
        $item['expiration_date'] ?? '',
        $item['item_tags'] ?? ''
    ], ';');


    // 2. Zugehörige Anbau- / Zubehörteile für diesen Artikel abrufen
    $sqlParts = "
        SELECT * 
        FROM item_parts 
        WHERE item_id = ?
        ORDER BY name ASC
    ";
    $stmtParts = $pdo->prepare($sqlParts);
    $stmtParts->execute([$item['id']]);
    $parts = $stmtParts->fetchAll(PDO::FETCH_ASSOC);

    foreach ($parts as $part) {
        $partTotal = (float)$part['price'] * (int)$part['quantity'];

        fputcsv($output, [
            'Anbau-/Zubehörteil',
            $part['id'],
            $item['id'], // Verweis auf den Hauptartikel
            '  └─ ' . $part['name'], // Visuelle Einrückung in Excel
            '',
            '',
            '',
            $part['quantity'],
            '',
            number_format((float)$part['price'], 2, ',', ''),
            number_format($partTotal, 2, ',', ''),
            '',
            '',
            '',
            '',
            '',
            ''
        ], ';');
    }
}

fclose($output);
exit;
