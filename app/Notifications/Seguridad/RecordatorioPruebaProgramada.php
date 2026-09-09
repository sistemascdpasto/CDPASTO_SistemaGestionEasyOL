<?php

namespace App\Notifications\Seguridad;

use App\Models\Seguridad\PruebaAlcoholemia;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordatorioPruebaProgramada extends Notification
{
    public function __construct(private readonly PruebaAlcoholemia $prueba) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $colaborador = $this->prueba->colaborador;

        return (new MailMessage)
            ->subject('EASY LOGÍSTICA Seguridad: recordatorio de prueba programada')
            ->greeting('Tienes una prueba de alcoholemia programada')
            ->line("Colaborador: {$colaborador?->nombre_completo}")
            ->line("Tipo: {$this->prueba->tipoLabel()}")
            ->line('Programada para: '.$this->prueba->programada_en?->format('d/m/Y H:i'))
            ->action('Ver pruebas', route('seguridad.pruebas.index'))
            ->line('Este es un mensaje automático del Sistema Integral de Gestión EASY LOGÍSTICA.');
    }
}
