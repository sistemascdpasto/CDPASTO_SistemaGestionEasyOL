<?php

namespace App\Exports\Seguridad;

use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\Recomendacion;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * HU-041, listado "completo": las mismas columnas de {@see EvaluacionesMedicasExport}
 * más una columna por cada recomendación activa del catálogo (CA-041.4),
 * con Sí/No/No aplica según si la evaluación tiene esa recomendación
 * marcada y vigente (CA-041.5) — nunca se quita una columna aunque no
 * aplique a ningún colaborador del listado.
 */
class EvaluacionesMedicasCompletaExport implements FromCollection, WithHeadings, WithMapping
{
    private readonly EvaluacionesMedicasExport $basica;

    /**
     * @var Collection<int, Recomendacion>
     */
    private readonly Collection $recomendaciones;

    public function __construct(Collection $evaluaciones, EvaluacionMedicaService $service)
    {
        $this->basica = new EvaluacionesMedicasExport($evaluaciones, $service);
        $this->recomendaciones = Recomendacion::where('activo', true)->orderBy('categoria')->orderBy('nombre')->get();
    }

    public function collection(): Collection
    {
        return $this->basica->collection();
    }

    public function headings(): array
    {
        return [...$this->basica->headings(), ...$this->recomendaciones->pluck('nombre')->all()];
    }

    /**
     * @param  EvaluacionMedica  $evaluacion
     * @return array<int, mixed>
     */
    public function map($evaluacion): array
    {
        $recomendacionesActivasIds = $evaluacion->recomendaciones
            ->where('activa', true)
            ->pluck('recomendacion_id')
            ->all();

        $columnasRecomendacion = $this->recomendaciones
            ->map(fn (Recomendacion $r) => in_array($r->id, $recomendacionesActivasIds, true) ? 'Sí' : 'No aplica')
            ->all();

        return [...$this->basica->map($evaluacion), ...$columnasRecomendacion];
    }
}
