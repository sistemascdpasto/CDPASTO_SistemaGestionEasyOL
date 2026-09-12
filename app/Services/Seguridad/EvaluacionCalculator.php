<?php

namespace App\Services\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\CondicionSalud;
use App\Models\Seguridad\PruebaAlcoholemia;
use Illuminate\Support\Carbon;

class EvaluacionCalculator
{
    /**
     * HU047: estado del colaborador hoy (Apto / Apto con Observaciones / No Apto),
     * o null si todavía no tiene ningún registro el día de hoy.
     */
    public function paraColaboradorHoy(Colaborador $colaborador): ?string
    {
        $hoy = Carbon::today();

        $huboPositiva = PruebaAlcoholemia::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('fecha_hora', $hoy)
            ->where('es_positivo', true)
            ->exists();

        if ($huboPositiva) {
            return 'No Apto';
        }

        $condicion = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('fecha_hora', $hoy)
            ->latest('fecha_hora')
            ->first();

        $huboPrueba = PruebaAlcoholemia::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('fecha_hora', $hoy)
            ->where('estado', 'realizada')
            ->exists();

        if (! $condicion && ! $huboPrueba) {
            return null;
        }

        return match ($condicion?->estado) {
            'Malo' => 'No Apto',
            'Regular' => 'Apto con Observaciones',
            default => 'Apto',
        };
    }

    /**
     * Bloquea la creación de un NUEVO ingreso: cuando el último registro
     * histórico del colaborador es un ingreso sin su salida correspondiente.
     * Si hoy ya existe un registro de ingreso, esta comprobación no aplica
     * (el registro de hoy ya es inmutable por sí mismo, ver `store()`) —
     * por eso es distinta de {@see faltaRegistrarSalida()}.
     */
    public function jornadaAbierta(Colaborador $colaborador): bool
    {
        $existeIngresoHoy = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->where('momento', 'ingreso')
            ->whereDate('fecha_hora', Carbon::today())
            ->exists();

        if ($existeIngresoHoy) {
            return false;
        }

        return $this->ultimoMomentoEsIngreso($colaborador);
    }

    /**
     * Para avisos informativos (dashboard, historial): si el último registro
     * es un ingreso sin salida, sin importar si es de hoy o de un día
     * anterior — a diferencia de {@see jornadaAbierta()}, que exceptúa el
     * ingreso de hoy para permitir editarlo sin bloquear el formulario.
     */
    public function faltaRegistrarSalida(Colaborador $colaborador): bool
    {
        return $this->ultimoMomentoEsIngreso($colaborador);
    }

    private function ultimoMomentoEsIngreso(Colaborador $colaborador): bool
    {
        $ultimoRegistro = CondicionSalud::query()
            ->where('colaborador_id', $colaborador->id)
            ->latest('fecha_hora')
            ->first();

        return $ultimoRegistro?->momento === 'ingreso';
    }
}
