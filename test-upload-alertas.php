<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Flota\Vehiculo;

// Obtener algunas placas reales de la BD
$placasReales = Vehiculo::limit(5)->pluck('placa')->toArray();

echo "Placas en Flota: " . implode(', ', $placasReales) . "\n\n";

// Crear archivo Excel de prueba
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Eventos');

// Encabezados
$sheet->setCellValue('A1', 'Fecha');
$sheet->setCellValue('B1', 'Placa');
$sheet->setCellValue('C1', 'Cantidad de Eventos');

// Datos de prueba - usar placas reales
$datos = [];
if (count($placasReales) > 0) {
    $datos = [
        ['2026-08-26', $placasReales[0], 2],
        ['2026-08-26', $placasReales[0], 3],
        ['2026-08-26', $placasReales[1] ?? 'INVALID', 5],
        ['2026-08-25', $placasReales[2] ?? 'INVALID', 1],
        ['2026-08-25', 'PLACA_FAKE', 4],  // Placa inválida
    ];
} else {
    echo "❌ No hay placas en la BD. Agrega vehículos primero.\n";
    exit(1);
}

foreach ($datos as $row => $data) {
    foreach ($data as $col => $value) {
        $sheet->setCellValueByColumnAndRow($col + 1, $row + 2, $value);
    }
}

// Auto-fit columns
foreach (['A', 'B', 'C'] as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Aplicar estilos a encabezados
$headerStyle = [
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FF6600'],
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
];
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

$writer = new Xlsx($spreadsheet);
$writer->save('storage/app/test-alertas-velocidad.xlsx');
echo "✓ Archivo de prueba creado: storage/app/test-alertas-velocidad.xlsx\n";
echo "✓ Contiene placas reales de la BD para testear\n";
