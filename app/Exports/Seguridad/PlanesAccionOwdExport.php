<?php

namespace App\Exports\Seguridad;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlanesAccionOwdExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $planes)
    {
    }

    public function collection(): Collection
    {
        return $this->planes;
    }

    public function headings(): array
    {
        return ['Colaborador', 'Tarea', 'Proceso', 'Fecha evaluación', 'Estado', 'Fecha de vencimiento', 'Observaciones'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($plan): array
    {
        $evaluacion = $plan->pregunta?->evaluacionOwd;

        return [
            $evaluacion?->colaborador ? trim("{$evaluacion->colaborador->nombres} {$evaluacion->colaborador->apellidos}") : null,
            $plan->pregunta?->tarea,
            $plan->pregunta?->proceso,
            $evaluacion?->fecha_evaluacion?->format('d/m/Y'),
            $plan->estado,
            $plan->fecha_vencimiento?->format('d/m/Y'),
            $plan->observaciones,
        ];
    }
}
