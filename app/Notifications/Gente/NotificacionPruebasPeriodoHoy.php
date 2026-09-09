<?php

namespace App\Notifications\Gente;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificacionPruebasPeriodoHoy extends Notification
{

    /**
     * @param array<int, array{
     *     colaborador: string,
     *     cedula: string,
     *     cargo: string,
     *     etapa_key: string,
     *     etapa_label: string,
     *     fecha_programada: string
     * }> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly string $fechaFormateada
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asunto = "Pruebas programadas para hoy – {$this->fechaFormateada}";
        $urlModulo = url('/modules/gente/plan-padrinos');

        return (new MailMessage)
            ->subject($asunto)
            ->view('emails.pruebas_periodo_hoy', [
                'items' => $this->items,
                'fechaFormateada' => $this->fechaFormateada,
                'urlModulo' => $urlModulo,
            ]);
    }
}
