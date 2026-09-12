<?php

namespace App\Exports\Seguridad;

use App\Models\Seguridad\EvaluacionMedica;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * HU-041, listado "básico" — columnas exactas de CA-041.3. `$service` provee
 * los mismos indicadores (días restantes, cantidades) que ya se ven en la
 * bandeja, para que el Excel sea 1:1 con lo filtrado en pantalla (CA-041.6).
 */
class EvaluacionesMedicasExport implements FromCollection, WithHeadings, WithMapping
{
    private const TIPO_LABELS = ['ingreso' => 'Ingreso', 'periodico' => 'Periódico', 'egreso' => 'Egreso'];

    private int $indiceActual = 0;

    public function __construct(
        private readonly Collection $evaluaciones,
        private readonly EvaluacionMedicaService $service,
    ) {
    }

    public function collection(): Collection
    {
        return $this->evaluaciones;
    }

    public function headings(): array
    {
        return [
            'ITEM', 'C.C', 'NOMBRE', 'FECHA DE INGRESO', 'TIPO EXAMEN', 'ESTADO DEL FUNCIONARIO',
            'FECHA TOMA EXAMEN', 'PRÓXIMO EXAMEN', 'DÍAS PARA PRÓXIMO EXAMEN', 'ESTADO DEL EXAMEN',
            'PROGRAMACIÓN', 'CONCEPTO DE APTITUD', 'EMITE', 'SEGUIMIENTO A RECOMENDACIONES',
            'ESTADO DE SEGUIMIENTO', 'EMPRESA', 'SE LE ENTREGÓ LA CARTA DE RECOMENDACIÓN',
        ];
    }

    /**
     * @param  EvaluacionMedica  $evaluacion
     * @return array<int, mixed>
     */
    public function map($evaluacion): array
    {
        $this->indiceActual++;
        $item = $this->indiceActual;

        $indicadores = $this->service->indicadoresBandeja($evaluacion);
        $fechaTomaExamen = $evaluacion->tipo_evaluacion === 'egreso'
            ? $evaluacion->fecha_examen_ejecutado
            : $evaluacion->examenes->pluck('fecha_ejecucion')->filter()->max();

        return [
            $item,
            $evaluacion->colaborador?->cedula,
            $evaluacion->colaborador ? "{$evaluacion->colaborador->nombres} {$evaluacion->colaborador->apellidos}" : null,
            $evaluacion->colaborador?->fecha_ingreso_empresa?->format('d/m/Y'),
            self::TIPO_LABELS[$evaluacion->tipo_evaluacion] ?? $evaluacion->tipo_evaluacion,
            $evaluacion->colaborador?->is_active ? 'Activo' : 'No activo',
            $fechaTomaExamen ? Carbon::parse($fechaTomaExamen)->format('d/m/Y') : null,
            $evaluacion->proximo_examen_fecha?->format('d/m/Y') ?? $evaluacion->fecha_limite?->format('d/m/Y'),
            $indicadores['dias_restantes'],
            $evaluacion->estado,
            $evaluacion->fecha_programacion?->format('d/m/Y'),
            $evaluacion->conceptoAptitud?->nombre,
            $evaluacion->emite,
            $evaluacion->seguimiento_recomendaciones,
            $evaluacion->estado_seguimiento,
            $evaluacion->empresa,
            $evaluacion->carta_entregada === null ? null : ($evaluacion->carta_entregada ? 'Sí' : 'No'),
        ];
    }
}
