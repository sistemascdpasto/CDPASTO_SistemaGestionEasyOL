<?php

namespace App\Http\Controllers\Seguridad;

use App\Exports\Seguridad\CumplimientoOwdExport;
use App\Exports\Seguridad\EvaluacionesOwdExport;
use App\Exports\Seguridad\PlanesAccionOwdExport;
use App\Http\Controllers\Controller;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Models\Seguridad\PlanAccionOwd;
use App\Services\Seguridad\EvaluacionOwdCumplimientoService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EvaluacionOwdExportController extends Controller
{
    /**
     * HU-043: exporta las evaluaciones OWD respetando los mismos filtros
     * del listado (`EvaluacionOwdController::index`).
     */
    public function evaluaciones(Request $request)
    {
        $filtros = $request->only([
            'colaborador', 'evaluador', 'mes', 'anio', 'bu', 'pais', 'region', 'uen',
            'agencia', 'type', 'pillar', 'proceso', 'actividad', 'puntuacion', 'plan_accion',
        ]);

        $preguntas = EvaluacionOwdPregunta::query()
            ->with('evaluacionOwd.colaborador:id,nombres,apellidos')
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
            ->get();

        return Excel::download(new EvaluacionesOwdExport($preguntas), 'evaluaciones-owd-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * HU-043: exporta el cumplimiento OWD (ventana de 3 meses) de los
     * colaboradores con al menos una evaluación registrada.
     */
    public function cumplimiento(EvaluacionOwdCumplimientoService $service)
    {
        $colaboradores = Colaborador::whereHas('evaluacionesOwd')->get(['id', 'nombres', 'apellidos', 'cedula']);

        $filas = $colaboradores->map(function (Colaborador $colaborador) use ($service) {
            $resultado = $service->calcular($colaborador);

            return [
                'cedula' => $colaborador->cedula,
                'nombres' => $colaborador->nombres,
                'apellidos' => $colaborador->apellidos,
                'total_preguntas' => $resultado['total_preguntas'],
                'preguntas_no_conformes' => $resultado['preguntas_no_conformes'],
                'porcentaje' => $resultado['porcentaje'],
                'cumple' => $resultado['cumple'],
            ];
        });

        return Excel::download(new CumplimientoOwdExport($filas), 'cumplimiento-owd-'.now()->format('Y-m-d').'.xlsx');
    }

    public function planesAccion()
    {
        $planes = PlanAccionOwd::with('pregunta.evaluacionOwd.colaborador:id,nombres,apellidos')->get();

        return Excel::download(new PlanesAccionOwdExport($planes), 'planes-accion-owd-'.now()->format('Y-m-d').'.xlsx');
    }
}
