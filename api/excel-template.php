<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

// Verifica autenticazione
Auth::requireLogin();

// Verifica autorizzazioni - Solo admin e responsabili possono scaricare il template
if (!Auth::isAdmin() && !Auth::isResponsabile()) {
    http_response_code(403);
    die('Non hai i permessi per accedere a questa funzionalità');
}

// Imposta headers per download Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template_importazione_scontrini.xlsx"');
header('Cache-Control: max-age=0');

// Dati di esempio per il template
$headers = [
    'Numero D\'Ordine',
    'Nome Scontrino', 
    'Data Scontrino',
    'Codice Articolo',
    'Descrizione Materiale',
    'Quantità',
    'Prezzo Unitario (senza IVA)',
    'Prezzo Totale (senza IVA)'
];

$example_data = [
    ['ORD001', 'Materiali Ufficio Gennaio', '15/01/2024', 'PEN001', 'Penne Biro Blu confezione 10pz', 2, 5.50, 11.00],
    ['ORD001', 'Materiali Ufficio Gennaio', '15/01/2024', 'QUAD01', 'Quaderni A4 righe 80 fogli', 5, 3.20, 16.00],
    ['ORD001', 'Materiali Ufficio Gennaio', '15/01/2024', '', 'Evidenziatori colori assortiti', 3, 4.75, 14.25],
    ['', '', '', '', '', '', '', ''],
    ['ORD002', 'Acquisto Computer Portatile', '20/01/2024', 'LAPTOP01', 'Laptop Dell Inspiron 15 i5 8GB RAM', 1, 650.00, 650.00],
    ['ORD002', 'Acquisto Computer Portatile', '20/01/2024', 'MOUSE01', 'Mouse wireless ergonomico', 1, 25.90, 25.90],
    ['', '', '', '', '', '', '', ''],
    ['ORD003', 'Materiali Pulizia Ufficio', '25/01/2024', '', 'Detergente multiuso 1L', 4, 2.80, 11.20],
    ['ORD003', 'Materiali Pulizia Ufficio', '25/01/2024', 'CART001', 'Carta igienica 12 rotoli', 2, 8.50, 17.00],
    ['ORD003', 'Materiali Pulizia Ufficio', '25/01/2024', '', 'Sapone mani antibatterico 500ml', 3, 4.20, 12.60]
];

// Crea CSV (più semplice di Excel per questo esempio)
// In produzione potresti usare PhpSpreadsheet per vero Excel
$output = fopen('php://output', 'w');

// Scrivi headers
fputcsv($output, $headers, ';');

// Scrivi dati esempio
foreach ($example_data as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);

// Nota: Per un vero file Excel (.xlsx), considera l'uso di PhpSpreadsheet:
/*
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Imposta headers
$sheet->fromArray($headers, null, 'A1');

// Imposta dati esempio
$sheet->fromArray($example_data, null, 'A2');

// Formattazione
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$sheet->getStyle('A1:H1')->getFill()->getStartColor()->setARGB('FFCCCCCC');

// Auto-size colonne
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
*/
?>