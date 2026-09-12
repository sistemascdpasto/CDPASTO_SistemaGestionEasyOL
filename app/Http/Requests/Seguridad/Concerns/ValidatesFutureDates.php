<?php

namespace App\Http\Requests\Seguridad\Concerns;

use App\Models\Seguridad\Colaborador;
use Carbon\Carbon;
use Closure;

trait ValidatesFutureDates
{
    /**
     * Exige que la fecha sea hoy o futura, salvo que el valor enviado sea
     * igual al que el colaborador ya tenía guardado. Sin esto, reenviar un
     * paso del wizard sin tocar una fecha histórica (ej. un contrato o unas
     * vacaciones ya vencidas) rompía la validación al editar.
     */
    protected function futureUnlessUnchanged(?Colaborador $colaborador, string $field): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($colaborador, $field) {
            if (! $value) {
                return;
            }

            $valorActual = $colaborador?->{$field};
            $valorActual = $valorActual ? Carbon::parse($valorActual)->toDateString() : null;

            if ($valorActual === $value) {
                return;
            }

            if (Carbon::parse($value)->lt(Carbon::today())) {
                $fail('El campo :attribute debe ser hoy o una fecha futura.');
            }
        };
    }
}
