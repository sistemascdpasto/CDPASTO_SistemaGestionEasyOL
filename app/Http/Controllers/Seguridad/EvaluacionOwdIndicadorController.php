<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Models\Seguridad\PlanAccionOwd;
use App\Services\Seguridad\EvaluacionOwdCumplimientoService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluacionOwdIndicadorController extends Controller
{
    public function index(Request $request, EvaluacionOwdCumplimientoService $cumplimientoService): Response
    {
        $mes = $request->integer('mes') ?: (int) now()->month;
        $anio = $request->integer('anio') ?: (int) now()->year;

        $hasta = Carbon::create($anio, $mes, 1)->endOfMonth();
        $desde = $hasta->copy()->subMonths(3)->startOfDay();

        // Población: colaboradores con al menos una evaluación OWD en la
        // ventana móvil de 3 meses que termina en el período seleccionado —
        // la misma ventana que usa el cálculo de cumplimiento (HU-034), para
        // que "colaboradores evaluados" y "cumplen/no cumplen" sean
        // consistentes entre sí.
        $poblacion = Colaborador::whereHas('evaluacionesOwd', function ($query) use ($desde, $hasta) {
            $query->whereBetween('fecha_evaluacion', [$desde, $hasta]);
        })->get(['id', 'nombres', 'apellidos', 'cedula']);

        $progreso = $poblacion->map(function (Colaborador $colaborador) use ($cumplimientoService, $hasta) {
            $resultado = $cumplimientoService->calcular($colaborador, $hasta);

            return [
                'id' => $colaborador->id,
                'nombres' => $colaborador->nombres,
                'apellidos' => $colaborador->apellidos,
                'cedula' => $colaborador->cedula,
                'porcentaje' => $resultado['porcentaje'],
                'cumple' => $resultado['cumple'],
                'total_preguntas' => $resultado['total_preguntas'],
                'preguntas_no_conformes' => $resultado['preguntas_no_conformes'],
            ];
        })->sortBy('porcentaje')->values();

        $totalPoblacion = $progreso->count();
        $cumplen = $progreso->where('cumple', true)->count();
        $noCumplen = $totalPoblacion - $cumplen;
        $porcentajeCumplimiento = $totalPoblacion > 0 ? round(($cumplen / $totalPoblacion) * 100, 1) : 0.0;

        $totalPreguntas = EvaluacionOwdPregunta::whereHas(
            'evaluacionOwd',
            fn ($q) => $q->whereBetween('fecha_evaluacion', [$desde, $hasta]),
        )->count();

        $totalOk = EvaluacionOwdPregunta::where('puntuacion', 'OK')->whereHas(
            'evaluacionOwd',
            fn ($q) => $q->whereBetween('fecha_evaluacion', [$desde, $hasta]),
        )->count();

        $totalNoConformes = EvaluacionOwdPregunta::whereNotIn('puntuacion', ['OK', 'Not Applicable'])->whereHas(
            'evaluacionOwd',
            fn ($q) => $q->whereBetween('fecha_evaluacion', [$desde, $hasta]),
        )->count();

        $totalPlanesAccion = PlanAccionOwd::whereHas(
            'pregunta.evaluacionOwd',
            fn ($q) => $q->whereBetween('fecha_evaluacion', [$desde, $hasta]),
        )->count();

        $planesPendientes = PlanAccionOwd::where('estado', '!=', PlanAccionOwd::ESTADO_COMPLETADO)
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now())
            ->count();

        $ultimasEvaluaciones = EvaluacionOwd::with('colaborador:id,nombres,apellidos')
            ->whereBetween('fecha_evaluacion', [$desde, $hasta])
            ->latest('fecha_evaluacion')
            ->limit(10)
            ->get(['id', 'fecha_evaluacion', 'colaborador_id', 'pillar', 'agencia', 'total_preguntas', 'preguntas_ok', 'preguntas_no_ok']);

        return Inertia::render('seguridad/evaluaciones-owd/indicadores', [
            'filtros' => ['mes' => $mes, 'anio' => $anio],
            'resumen' => [
                'colaboradores_evaluados' => $totalPoblacion,
                'cumplen_owd' => $cumplen,
                'no_cumplen_owd' => $noCumplen,
                'porcentaje_cumplimiento' => $porcentajeCumplimiento,
                'total_preguntas' => $totalPreguntas,
                'total_preguntas_ok' => $totalOk,
                'total_preguntas_no_conformes' => $totalNoConformes,
                'total_planes_accion' => $totalPlanesAccion,
                'planes_vencidos' => $planesPendientes,
            ],
            'ultimasEvaluaciones' => $ultimasEvaluaciones,
            'colaboradoresCumplimiento' => $this->paginar($progreso, $request),
            'cumplimientoPorPillar' => $this->cumplimientoPorColumna('e.pillar', $desde, $hasta),
            'cumplimientoPorAgencia' => $this->cumplimientoPorColumna('e.agencia', $desde, $hasta),
            'cumplimientoPorProceso' => $this->cumplimientoPorColumna('p.proceso', $desde, $hasta),
            'evaluacionesPorMes' => $this->tendenciaUltimosMeses($mes, $anio),
        ]);
    }

    /**
     * Pagina en memoria la colección ya calculada de progreso por
     * colaborador (no es un `Model::paginate()` porque "porcentaje" es un
     * valor calculado, no una columna de la tabla) — mismo patrón que
     * `AciIndicadorController::paginar()`.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $coleccion
     */
    private function paginar($coleccion, Request $request): LengthAwarePaginator
    {
        $porPagina = 15;
        $paginaActual = max(1, $request->integer('page', 1));

        return new LengthAwarePaginator(
            $coleccion->slice(($paginaActual - 1) * $porPagina, $porPagina)->values(),
            $coleccion->count(),
            $porPagina,
            $paginaActual,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * @return array<int, array{grupo: string, total: int, porcentaje: float}>
     */
    private function cumplimientoPorColumna(string $columnaCalificada, Carbon $desde, Carbon $hasta): array
    {
        return DB::table('evaluacion_owd_preguntas as p')
            ->join('evaluaciones_owd as e', 'e.id', '=', 'p.evaluacion_owd_id')
            ->whereBetween('e.fecha_evaluacion', [$desde, $hasta])
            ->whereNotNull($columnaCalificada)
            ->where($columnaCalificada, '!=', '')
            ->where('p.puntuacion', '!=', 'Not Applicable')
            ->select(
                "{$columnaCalificada} as grupo",
                DB::raw('count(*) as total'),
                DB::raw("sum(case when p.puntuacion = 'OK' then 1 else 0 end) as ok"),
            )
            ->groupBy($columnaCalificada)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($fila) => [
                'grupo' => (string) $fila->grupo,
                'total' => (int) $fila->total,
                'porcentaje' => $fila->total > 0 ? round(($fila->ok / $fila->total) * 100, 1) : 0.0,
            ])
            ->all();
    }

    /**
     * Cantidad de evaluaciones OWD por mes de los últimos 6 meses,
     * agrupado en PHP (no SQL específico de motor — los tests corren sobre
     * SQLite y producción sobre MySQL, mismo criterio que ACI).
     *
     * @return array<int, array{periodo: string, cantidad: int}>
     */
    private function tendenciaUltimosMeses(int $mes, int $anio): array
    {
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
        $inicio = $fin->copy()->subMonths(5)->startOfMonth();

        $conteos = EvaluacionOwd::whereBetween('fecha_evaluacion', [$inicio, $fin])
            ->pluck('fecha_evaluacion')
            ->countBy(fn (Carbon $fecha) => $fecha->format('Y-m'));

        $meses = [];
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $clave = $cursor->format('Y-m');
            $meses[] = ['periodo' => $clave, 'cantidad' => (int) ($conteos[$clave] ?? 0)];
            $cursor->addMonth();
        }

        return $meses;
    }
}
