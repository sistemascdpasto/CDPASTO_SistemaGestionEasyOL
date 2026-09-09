<?php

namespace App\Exports\Flota;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ActaTallerExport implements FromCollection, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithMapping
{
    private const IMG_HEIGHT = 45;

    public function __construct(private readonly Collection $actas) {}

    public function collection(): Collection
    {
        return $this->actas;
    }

    public function headings(): array
    {
        return [
            'Nº Acta', 'Placa', 'Taller', 'Fecha Entrega', 'Fecha Est. Solución',
            'Fecha Cierre', 'Km Entrada', 'Combustible (%)', 'Motivo Ingreso',
            'Quien Reporta', 'Estado',
            // Novedades resumen
            'Total Novedades', 'Solucionadas', 'Pendientes',
            // Entrega
            'Nombre Entrega', 'Identificación Entrega', 'Cargo Entrega', 'Teléfono Entrega', 'Firma Entrega',
            // Recibe
            'Nombre Recibe', 'Cargo Recibe', 'Firma Recibe',
        ];
    }

    public function map($acta): array
    {
        $novedades        = $acta->novedades;
        $solucionadas     = $novedades->where('estado', 'solucionado')->count();
        $pendientes       = $novedades->where('estado', 'pendiente')->count();

        return [
            $acta->numero_acta,
            $acta->placa,
            $acta->taller ?? '—',
            $acta->fecha_entrega?->format('d/m/Y H:i') ?? '—',
            $acta->fecha_estimada_solucion?->format('d/m/Y H:i') ?? '—',
            $acta->fecha_cierre?->format('d/m/Y H:i') ?? '—',
            $acta->kilometraje_entrada ?? '—',
            $acta->combustible !== null ? "{$acta->combustible}%" : '—',
            $acta->motivo_ingreso ?? '—',
            $acta->quien_reporta ?? '—',
            $acta->estado_label ?? $acta->estado_acta,
            // Novedades
            $novedades->count(),
            $solucionadas,
            $pendientes,
            // Entrega
            $acta->nombre_entrega ?? '—',
            $acta->identificacion_entrega ?? '—',
            $acta->cargo_entrega ?? '—',
            $acta->telefono_entrega ?? '—',
            $acta->firma_entrega ? '' : '—',
            // Recibe
            $acta->nombre_recibe ?? '—',
            $acta->cargo_recibe ?? '—',
            $acta->firma_recibe ? '' : '—',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 10, 'C' => 18, 'D' => 16, 'E' => 16,
            'F' => 16, 'G' => 12, 'H' => 12, 'I' => 30, 'J' => 22,
            'K' => 12, 'L' => 8,  'M' => 10, 'N' => 10,
            'O' => 22, 'P' => 18, 'Q' => 18, 'R' => 16, 'S' => 18,
            'T' => 22, 'U' => 18, 'V' => 18,
        ];
    }

    /** @return Drawing[] */
    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->actas->values() as $index => $acta) {
            $fila = $index + 2;
            $this->addDrawing($drawings, $acta->firma_entrega, "S{$fila}", 'Firma Entrega');
            $this->addDrawing($drawings, $acta->firma_recibe,  "V{$fila}", 'Firma Recibe');
        }

        return $drawings;
    }

    /** @param Drawing[] $drawings */
    private function addDrawing(array &$drawings, ?string $path, string $cell, string $name): void
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName($name);
        $drawing->setPath(Storage::disk('public')->path($path));
        $drawing->setHeight(self::IMG_HEIGHT - 6);
        $drawing->setCoordinates($cell);
        $drawing->setOffsetX(4);
        $drawing->setOffsetY(3);

        $drawings[] = $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->actas->values() as $index => $acta) {
                    if ($acta->firma_entrega || $acta->firma_recibe) {
                        $event->sheet->getDelegate()
                            ->getRowDimension($index + 2)
                            ->setRowHeight(self::IMG_HEIGHT);
                    }
                }
            },
        ];
    }
}
