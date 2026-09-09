<?php

namespace App\Observers\Seguridad;

use App\Models\Seguridad\Alerta;
use App\Models\Seguridad\PruebaAlcoholemia;
use App\Services\Seguridad\AlertaNotifier;

class PruebaAlcoholemiaObserver
{
    public function created(PruebaAlcoholemia $prueba): void
    {
        if ($prueba->es_positivo) {
            $alerta = Alerta::create([
                'tipo' => 'prueba_positiva',
                'colaborador_id' => $prueba->colaborador_id,
                'alcoholimetro_id' => $prueba->alcoholimetro_id,
                'prueba_alcoholemia_id' => $prueba->id,
                'mensaje' => "{$prueba->colaborador->nombre_completo} registró un resultado positivo ({$prueba->resultado}) en la prueba de {$prueba->tipo}.",
            ]);

            AlertaNotifier::notificar($alerta);

            return;
        }

        if ($prueba->evaluacion() === 'No Apto') {
            $alerta = Alerta::create([
                'tipo' => 'no_apto',
                'colaborador_id' => $prueba->colaborador_id,
                'alcoholimetro_id' => $prueba->alcoholimetro_id,
                'prueba_alcoholemia_id' => $prueba->id,
                'mensaje' => "{$prueba->colaborador->nombre_completo} fue evaluado como No Apto para laborar (prueba de {$prueba->tipo}).",
            ]);

            AlertaNotifier::notificar($alerta);
        }
    }
}
