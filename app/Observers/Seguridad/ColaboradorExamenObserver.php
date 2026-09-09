<?php

namespace App\Observers\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionMedica;
use App\Services\Seguridad\EvaluacionMedicaService;

/**
 * HU-031/HU-037: la generación de exámenes ocupacionales es 100%
 * automática, sin intervención manual del usuario SST — se dispara desde
 * eventos del propio colaborador, no desde una acción explícita de "crear
 * evaluación" (esa acción manual se conserva para casos adicionales, ver
 * `EvaluacionMedicaController::store`).
 */
class ColaboradorExamenObserver
{
    public function __construct(private readonly EvaluacionMedicaService $service)
    {
    }

    public function created(Colaborador $colaborador): void
    {
        if ($colaborador->cargo) {
            $this->service->crear($colaborador, 'ingreso');
        }

        if ($colaborador->fecha_retiro_empresa) {
            $this->service->sincronizarEgresoPorRetiro($colaborador, $colaborador->fecha_retiro_empresa);
        }
    }

    public function updated(Colaborador $colaborador): void
    {
        // El wizard de colaboradores (HU01) suele crear el registro sin
        // cargo y asignarlo en un paso posterior — el examen de Ingreso no
        // debe quedar huérfano solo porque el cargo llegó tarde.
        if ($colaborador->wasChanged('cargo') && $colaborador->cargo) {
            $yaExisteIngreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)
                ->where('tipo_evaluacion', 'ingreso')
                ->exists();

            if (! $yaExisteIngreso) {
                $this->service->crear($colaborador, 'ingreso');
            }
        }

        if ($colaborador->wasChanged('fecha_retiro_empresa') && $colaborador->fecha_retiro_empresa) {
            $this->service->sincronizarEgresoPorRetiro($colaborador, $colaborador->fecha_retiro_empresa);
        }
    }
}
