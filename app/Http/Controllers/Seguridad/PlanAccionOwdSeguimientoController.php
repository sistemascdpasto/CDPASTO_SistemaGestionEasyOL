<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\PlanAccionOwd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanAccionOwdSeguimientoController extends Controller
{
    public function store(Request $request, PlanAccionOwd $planAccionOwd): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in([
                PlanAccionOwd::ESTADO_PENDIENTE, PlanAccionOwd::ESTADO_EN_PROGRESO, PlanAccionOwd::ESTADO_COMPLETADO,
            ])],
            'observacion' => ['nullable', 'string'],
            'fecha' => ['required', 'date'],
        ]);

        $planAccionOwd->seguimientos()->create([
            ...$datos,
            'responsable_id' => $request->user()->id,
        ]);

        // El seguimiento registra el estado vigente del plan en ese
        // momento — se refleja también en el registro padre para que las
        // vistas de listado no tengan que ir a buscar el último seguimiento.
        $planAccionOwd->update(['estado' => $datos['estado']]);

        return back()->with('status', ['message' => 'Avance registrado.', 'type' => 'success']);
    }
}
