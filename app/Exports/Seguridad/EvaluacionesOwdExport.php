<?php

namespace App\Exports\Seguridad;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EvaluacionesOwdExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $preguntas)
    {
    }

    public function collection(): Collection
    {
        return $this->preguntas;
    }

    public function headings(): array
    {
        return [
            'Fecha evaluación', 'Evaluador', 'QR Safety evaluador', 'Evaluado', 'QR Safety',
            'BU', 'País', 'Región', 'UEN', 'Agencia', 'Type', 'Pillar',
            'Proceso', 'Actividad', 'Tarea', 'Puntuación', 'Ponderación (%)', 'Plan de acción', 'Versión',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($pregunta): array
    {
        $evaluacion = $pregunta->evaluacionOwd;

        return [
            $evaluacion?->fecha_evaluacion?->format('d/m/Y H:i'),
            $evaluacion?->evaluador,
            $evaluacion?->qr_safety_evaluador,
            $evaluacion?->evaluado,
            $evaluacion?->qr_safety,
            $evaluacion?->bu,
            $evaluacion?->pais,
            $evaluacion?->region,
            $evaluacion?->uen,
            $evaluacion?->agencia,
            $evaluacion?->type,
            $evaluacion?->pillar,
            $pregunta->proceso,
            $pregunta->actividad,
            $pregunta->tarea,
            $pregunta->puntuacion,
            $pregunta->ponderacion,
            $pregunta->requiere_plan_accion ? 'SI' : 'NO',
            $pregunta->version,
        ];
    }
}
