<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluacionOwdController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only([
            'colaborador', 'evaluador', 'mes', 'anio', 'bu', 'pais', 'region', 'uen',
            'agencia', 'type', 'pillar', 'proceso', 'actividad', 'puntuacion', 'plan_accion',
        ]);

        $preguntas = EvaluacionOwdPregunta::query()
            ->with([
                'evaluacionOwd.colaborador:id,nombres,apellidos,cedula',
                'evaluacionOwd.evaluadorColaborador:id,nombres,apellidos',
            ])
            ->when($filtros['puntuacion'] ?? null, fn ($q, $v) => $q->where('puntuacion', $v))
            ->when($filtros['proceso'] ?? null, fn ($q, $v) => $q->where('proceso', 'like', "%{$v}%"))
            ->when($filtros['actividad'] ?? null, fn ($q, $v) => $q->where('actividad', 'like', "%{$v}%"))
            ->when(
                isset($filtros['plan_accion']) && $filtros['plan_accion'] !== '',
                fn ($q) => $q->where('requiere_plan_accion', filter_var($filtros['plan_accion'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->whereHas('evaluacionOwd', function ($query) use ($filtros) {
                $query
                    ->when($filtros['mes'] ?? null, fn ($q, $v) => $q->whereMonth('fecha_evaluacion', $v))
                    ->when($filtros['anio'] ?? null, fn ($q, $v) => $q->whereYear('fecha_evaluacion', $v))
                    ->when($filtros['bu'] ?? null, fn ($q, $v) => $q->where('bu', $v))
                    ->when($filtros['pais'] ?? null, fn ($q, $v) => $q->where('pais', $v))
                    ->when($filtros['region'] ?? null, fn ($q, $v) => $q->where('region', $v))
                    ->when($filtros['uen'] ?? null, fn ($q, $v) => $q->where('uen', $v))
                    ->when($filtros['agencia'] ?? null, fn ($q, $v) => $q->where('agencia', $v))
                    ->when($filtros['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
                    ->when($filtros['pillar'] ?? null, fn ($q, $v) => $q->where('pillar', $v))
                    ->when($filtros['colaborador'] ?? null, function ($q, $v) {
                        $q->where(fn ($qq) => $qq->where('evaluado', 'like', "%{$v}%")->orWhere('qr_safety', 'like', "%{$v}%"));
                    })
                    ->when($filtros['evaluador'] ?? null, function ($q, $v) {
                        $q->where(fn ($qq) => $qq->where('evaluador', 'like', "%{$v}%")->orWhere('qr_safety_evaluador', 'like', "%{$v}%"));
                    });
            })
            ->orderByDesc(EvaluacionOwd::select('fecha_evaluacion')->whereColumn('id', 'evaluacion_owd_preguntas.evaluacion_owd_id'))
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('seguridad/evaluaciones-owd/index', [
            'preguntas' => $preguntas,
            'filters' => $filtros,
            'catalogos' => $this->catalogosFiltro(),
        ]);
    }

    public function show(EvaluacionOwd $evaluacionOwd): Response
    {
        $evaluacionOwd->load(['colaborador', 'evaluadorColaborador', 'preguntas.planAccion']);

        return Inertia::render('seguridad/evaluaciones-owd/show', [
            'evaluacionOwd' => $evaluacionOwd,
        ]);
    }

    public function incumplimientos(Request $request): Response
    {
        $filtros = $request->only(['mes', 'anio', 'pillar', 'agencia', 'proceso']);

        $base = EvaluacionOwdPregunta::query()
            ->whereNotIn('puntuacion', ['OK', 'Not Applicable'])
            ->whereHas('evaluacionOwd', function ($query) use ($filtros) {
                $query
                    ->when($filtros['mes'] ?? null, fn ($q, $v) => $q->whereMonth('fecha_evaluacion', $v))
                    ->when($filtros['anio'] ?? null, fn ($q, $v) => $q->whereYear('fecha_evaluacion', $v))
                    ->when($filtros['pillar'] ?? null, fn ($q, $v) => $q->where('pillar', $v))
                    ->when($filtros['agencia'] ?? null, fn ($q, $v) => $q->where('agencia', $v))
                    ->when($filtros['proceso'] ?? null, fn ($q, $v) => $q->where('proceso', 'like', "%{$v}%"));
            });

        $porTarea = (clone $base)
            ->select('tarea', DB::raw('count(*) as total'))
            ->groupBy('tarea')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $detalle = (clone $base)
            ->with(['evaluacionOwd.colaborador:id,nombres,apellidos'])
            ->orderByDesc(EvaluacionOwd::select('fecha_evaluacion')->whereColumn('id', 'evaluacion_owd_preguntas.evaluacion_owd_id'))
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('seguridad/evaluaciones-owd/incumplimientos', [
            'filtros' => $filtros,
            'porTarea' => $porTarea,
            'detalle' => $detalle,
            'catalogos' => $this->catalogosFiltro(),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function catalogosFiltro(): array
    {
        return collect(['bu', 'pais', 'region', 'uen', 'agencia', 'type', 'pillar'])
            ->mapWithKeys(fn ($campo) => [
                $campo => EvaluacionOwd::query()->whereNotNull($campo)->where($campo, '!=', '')
                    ->distinct()->orderBy($campo)->pluck($campo)->values()->all(),
            ])->all();
    }
}
