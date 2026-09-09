<?php

namespace App\Console\Commands;

use App\Models\Seguridad\Alcoholimetro;
use App\Models\Seguridad\Alerta;
use App\Services\Seguridad\AlertaNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RevisarCalibracionesAlcoholimetros extends Command
{
    protected $signature = 'seguridad:revisar-calibraciones';

    protected $description = 'Genera alertas para alcoholímetros próximos a calibración o con certificado por vencer (HU019)';

    public function handle(): int
    {
        $limite = Carbon::now()->addDays((int) config('seguridad.dias_alerta_calibracion'));
        $creadas = 0;

        Alcoholimetro::query()->where('fecha_calibracion', '<=', $limite)->each(function (Alcoholimetro $dispositivo) use (&$creadas) {
            $yaExiste = Alerta::query()
                ->where('alcoholimetro_id', $dispositivo->id)
                ->where('tipo', 'calibracion_proxima')
                ->where('atendida', false)
                ->exists();

            if (! $yaExiste) {
                $alerta = Alerta::create([
                    'tipo' => 'calibracion_proxima',
                    'alcoholimetro_id' => $dispositivo->id,
                    'mensaje' => "El alcoholímetro {$dispositivo->codigo} está próximo a su fecha de calibración ({$dispositivo->fecha_calibracion->toDateString()}).",
                ]);
                AlertaNotifier::notificar($alerta);
                $creadas++;
            }
        });

        Alcoholimetro::query()->where('fecha_vencimiento_certificado', '<=', $limite)->each(function (Alcoholimetro $dispositivo) use (&$creadas) {
            $yaExiste = Alerta::query()
                ->where('alcoholimetro_id', $dispositivo->id)
                ->where('tipo', 'certificado_vencido')
                ->where('atendida', false)
                ->exists();

            if (! $yaExiste) {
                $alerta = Alerta::create([
                    'tipo' => 'certificado_vencido',
                    'alcoholimetro_id' => $dispositivo->id,
                    'mensaje' => "El certificado del alcoholímetro {$dispositivo->codigo} vence el {$dispositivo->fecha_vencimiento_certificado->toDateString()}.",
                ]);
                AlertaNotifier::notificar($alerta);
                $creadas++;
            }
        });

        $this->info("Alertas de calibración/certificado generadas: {$creadas}");

        return self::SUCCESS;
    }
}
