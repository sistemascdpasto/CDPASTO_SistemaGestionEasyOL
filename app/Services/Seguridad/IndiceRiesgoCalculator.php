<?php

namespace App\Services\Seguridad;

use App\Models\Seguridad\Colaborador;
use Illuminate\Support\Carbon;

class IndiceRiesgoCalculator
{
    /**
     * HU045: índice de riesgo transparente basado en el historial reciente
     * del colaborador. No hay una fórmula oficial definida por el negocio,
     * así que se pondera: cada prueba positiva pesa más que un episodio de
     * salud "Malo", ambos dentro de la ventana configurada.
     */
    public function calcular(Colaborador $colaborador): array
    {
        $desde = Carbon::now()->subDays((int) config('seguridad.dias_indice_riesgo'));

        $pruebasPositivas = $colaborador->pruebasAlcoholemia()
            ->where('fecha_hora', '>=', $desde)
            ->where('es_positivo', true)
            ->count();

        $saludMala = $colaborador->condicionesSalud()
            ->where('fecha_hora', '>=', $desde)
            ->where('estado', 'Malo')
            ->count();

        $puntaje = ($pruebasPositivas * 3) + ($saludMala * 2);

        $nivel = match (true) {
            $puntaje >= 4 => 'Alto',
            $puntaje >= 1 => 'Medio',
            default => 'Bajo',
        };

        return [
            'puntaje' => $puntaje,
            'nivel' => $nivel,
            'pruebas_positivas' => $pruebasPositivas,
            'salud_mala' => $saludMala,
            'dias_considerados' => (int) config('seguridad.dias_indice_riesgo'),
        ];
    }
}
