<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Colaborador;
use App\Services\Seguridad\EvaluacionOwdCumplimientoService;
use Inertia\Inertia;
use Inertia\Response;

class EvaluacionOwdCumplimientoController extends Controller
{
    public function show(Colaborador $colaborador, EvaluacionOwdCumplimientoService $service): Response
    {
        $resultado = $service->calcular($colaborador);
        $resultado['preguntas_incumplidas'] = $resultado['preguntas_incumplidas']->load('evaluacionOwd:id,fecha_evaluacion,pillar');

        return Inertia::render('seguridad/evaluaciones-owd/cumplimiento-colaborador', [
            'colaborador' => $colaborador->only(['id', 'nombres', 'apellidos', 'cedula', 'codigo_qr_skap']),
            'cumplimiento' => $resultado,
        ]);
    }

    public function historial(Colaborador $colaborador, EvaluacionOwdCumplimientoService $service): Response
    {
        return Inertia::render('seguridad/evaluaciones-owd/cumplimiento-colaborador', [
            'colaborador' => $colaborador->only(['id', 'nombres', 'apellidos', 'cedula', 'codigo_qr_skap']),
            'cumplimiento' => $service->calcular($colaborador),
            'evolucion' => $service->evolucion($colaborador),
        ]);
    }
}
