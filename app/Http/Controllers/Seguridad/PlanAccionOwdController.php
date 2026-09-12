<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\PlanAccionOwd;
use App\Services\Seguridad\EvaluacionOwdCumplimientoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanAccionOwdController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only(['estado', 'vencimiento']);

        $planes = PlanAccionOwd::query()
            ->with(['pregunta.evaluacionOwd.colaborador:id,nombres,apellidos'])
            ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filtros['vencimiento'] ?? null, function ($q, $v) {
                match ($v) {
                    'vencido' => $q->where('estado', '!=', PlanAccionOwd::ESTADO_COMPLETADO)
                        ->whereNotNull('fecha_vencimiento')->where('fecha_vencimiento', '<', now()->toDateString()),
                    'proximo' => $q->where('estado', '!=', PlanAccionOwd::ESTADO_COMPLETADO)
                        ->whereNotNull('fecha_vencimiento')
                        ->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(7)->toDateString()]),
                    default => null,
                };
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('seguridad/planes-accion-owd/index', [
            'planes' => $planes,
            'filters' => $filtros,
        ]);
    }

    /**
     * HU-042: colaboradores que no cumplen OWD y tienen preguntas
     * incumplidas que requieren plan de acción, ordenados ascendente por
     * porcentaje de cumplimiento (peor cumplimiento primero) para
     * priorizar la gestión.
     */
    public function priorizacion(Request $request, EvaluacionOwdCumplimientoService $service): Response
    {
        $colaboradores = Colaborador::whereHas('evaluacionesOwd.preguntas', function ($query) {
            $query->where('requiere_plan_accion', true);
        })->get(['id', 'nombres', 'apellidos', 'cedula']);

        $priorizacion = $colaboradores
            ->map(function (Colaborador $colaborador) use ($service) {
                $resultado = $service->calcular($colaborador);

                return [
                    'id' => $colaborador->id,
                    'nombres' => $colaborador->nombres,
                    'apellidos' => $colaborador->apellidos,
                    'cedula' => $colaborador->cedula,
                    'porcentaje' => $resultado['porcentaje'],
                    'preguntas_no_conformes' => $resultado['preguntas_no_conformes'],
                ];
            })
            ->filter(fn ($fila) => ! ($fila['porcentaje'] >= 100.0))
            ->sortBy('porcentaje')
            ->values();

        return Inertia::render('seguridad/planes-accion-owd/priorizacion', [
            'priorizacion' => $priorizacion,
        ]);
    }

    public function update(Request $request, PlanAccionOwd $planAccionOwd): \Illuminate\Http\RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in([
                PlanAccionOwd::ESTADO_PENDIENTE, PlanAccionOwd::ESTADO_EN_PROGRESO, PlanAccionOwd::ESTADO_COMPLETADO,
            ])],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $planAccionOwd->update($datos);

        return back()->with('status', ['message' => 'Plan de acción actualizado.', 'type' => 'success']);
    }
}
