<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\EvaluacionRecomendacion;
use App\Models\Seguridad\ExamenEvaluacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamenMedicoIndicadorController extends Controller
{
    private const TIPO_EVALUACION_LABELS = ['ingreso' => 'Ingreso', 'periodico' => 'Periódico', 'egreso' => 'Egreso'];

    private const ESTADO_LABELS = [
        'sin_iniciar' => 'Sin Iniciar',
        'demorada' => 'Demorada',
        'en_proceso' => 'En Proceso',
        'terminada' => 'Terminada',
        'pendiente' => 'Pendiente',
        'programado' => 'Programado',
        'ejecutado' => 'Ejecutado',
    ];

    private const ESTADO_EXAMEN_LABELS = ['pendiente' => 'Pendiente', 'programado' => 'Programado', 'realizado' => 'Realizado'];

    public function index(): Response
    {
        return Inertia::render('seguridad/examenes-medicos/indicadores', [
            'cobertura' => $this->cobertura(),
            'porTipoEvaluacion' => $this->distribucion(EvaluacionMedica::query(), 'tipo_evaluacion', self::TIPO_EVALUACION_LABELS),
            'porConceptoAptitud' => $this->porConceptoAptitud(),
            'recomendacionesMedicas' => $this->porRecomendacion('medica'),
            'recomendacionesOcupacionales' => $this->porRecomendacion('ocupacional'),
            'habitosEstilosVida' => $this->porRecomendacion('habitos'),
            'estadoExamen' => $this->distribucion(EvaluacionMedica::query(), 'estado', self::ESTADO_LABELS),
            'programacionExamenes' => $this->distribucion(ExamenEvaluacion::query(), 'estado', self::ESTADO_EXAMEN_LABELS),
        ]);
    }

    /**
     * HU-XXX "Cobertura de ejecución": (ejecutados / programados) × 100,
     * sobre los ExamenEvaluacion de la matriz (Ingreso/Periódico) que ya
     * salieron de "pendiente" — un examen nunca programado no cuenta contra
     * la cobertura de lo que sí se gestionó.
     */
    private function cobertura(): array
    {
        $programados = ExamenEvaluacion::whereIn('estado', ['programado', 'realizado'])->count();
        $ejecutados = ExamenEvaluacion::where('estado', 'realizado')->count();

        return [
            'programados' => $programados,
            'ejecutados' => $ejecutados,
            'porcentaje' => $programados > 0 ? round(($ejecutados / $programados) * 100, 1) : 0.0,
        ];
    }

    /**
     * Distribución genérica de conteos por un campo tipo "select" del
     * modelo, con etiquetas legibles y ceros explícitos para los valores
     * que aún no tienen registros (para que el gráfico no los omita).
     *
     * @param  array<string, string>  $etiquetas
     * @return array<int, array{valor: string, etiqueta: string, cantidad: int}>
     */
    private function distribucion(Builder $query, string $columna, array $etiquetas): array
    {
        $conteos = $query->select($columna, DB::raw('count(*) as total'))->groupBy($columna)->pluck('total', $columna);

        return collect($etiquetas)
            ->map(fn ($etiqueta, $valor) => ['valor' => $valor, 'etiqueta' => $etiqueta, 'cantidad' => (int) ($conteos[$valor] ?? 0)])
            ->values()
            ->all();
    }

    /**
     * HU-XXX "Concepto aptitudinal": cantidad de exámenes por cada
     * concepto de aptitud asignado.
     */
    private function porConceptoAptitud(): array
    {
        return EvaluacionMedica::query()
            ->whereNotNull('concepto_aptitud_id')
            ->join('conceptos_aptitud', 'conceptos_aptitud.id', '=', 'evaluaciones_medicas.concepto_aptitud_id')
            ->select('conceptos_aptitud.nombre', DB::raw('count(*) as total'))
            ->groupBy('conceptos_aptitud.nombre')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => ['nombre' => $fila->nombre, 'cantidad' => (int) $fila->total])
            ->all();
    }

    /**
     * HU-XXX "Recomendaciones médicas/ocupacionales/hábitos": cantidad de
     * evaluaciones a las que se les generó cada recomendación del catálogo,
     * filtrado por categoría.
     */
    private function porRecomendacion(string $categoria): array
    {
        return EvaluacionRecomendacion::query()
            ->join('recomendaciones', 'recomendaciones.id', '=', 'evaluacion_recomendaciones.recomendacion_id')
            ->where('recomendaciones.categoria', $categoria)
            ->select('recomendaciones.nombre', DB::raw('count(*) as total'))
            ->groupBy('recomendaciones.nombre')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => ['nombre' => $fila->nombre, 'cantidad' => (int) $fila->total])
            ->all();
    }
}
