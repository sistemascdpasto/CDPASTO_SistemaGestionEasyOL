<?php

namespace App\Console\Commands;

use App\Models\Seguridad\PruebaAlcoholemia;
use App\Notifications\Seguridad\RecordatorioPruebaProgramada;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RecordatoriosPruebasProgramadas extends Command
{
    protected $signature = 'seguridad:recordatorios-pruebas';

    protected $description = 'Envía recordatorios de pruebas programadas próximas o vencidas (HU042)';

    public function handle(): int
    {
        $pruebas = PruebaAlcoholemia::query()
            ->with(['responsable', 'colaborador'])
            ->where('estado', 'programada')
            ->whereNull('recordatorio_enviado_at')
            ->where('programada_en', '<=', Carbon::now()->addHour())
            ->get();

        foreach ($pruebas as $prueba) {
            if ($prueba->responsable) {
                try {
                    Notification::send($prueba->responsable, new RecordatorioPruebaProgramada($prueba));
                } catch (Throwable $e) {
                    Log::warning('No se pudo enviar el recordatorio de prueba programada.', [
                        'prueba_id' => $prueba->id,
                        'destinatario' => $prueba->responsable->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $prueba->update(['recordatorio_enviado_at' => Carbon::now()]);
        }

        $this->info("Recordatorios enviados: {$pruebas->count()}");

        return self::SUCCESS;
    }
}
