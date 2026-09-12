<?php

namespace App\Observers\Seguridad;

use App\Models\Seguridad\Alerta;
use App\Models\Seguridad\CondicionSalud;
use App\Services\Seguridad\AlertaNotifier;

class CondicionSaludObserver
{
    public function created(CondicionSalud $condicion): void
    {
        if ($condicion->estado !== 'Malo') {
            return;
        }

        $alerta = Alerta::create([
            'tipo' => 'salud_mala',
            'colaborador_id' => $condicion->colaborador_id,
            'mensaje' => "{$condicion->colaborador->nombre_completo} fue registrado con estado de salud Malo al momento de {$condicion->momento}.",
        ]);

        AlertaNotifier::notificar($alerta);
    }
}
