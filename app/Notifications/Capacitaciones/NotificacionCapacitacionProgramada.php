<?php

namespace App\Notifications\Capacitaciones;

use App\Models\Capacitaciones\CapacitacionMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NotificacionCapacitacionProgramada extends Notification
{
    use Queueable;

    public function __construct(private readonly CapacitacionMaterial $material)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fecha = $this->material->fecha_programada ? Carbon::parse($this->material->fecha_programada) : null;
        $mesNombre = $fecha
            ? mb_strtoupper($fecha->locale('es')->monthName) . ' de ' . $fecha->year
            : 'próximo mes';

        return (new MailMessage)
            ->subject("EASY LOGÍSTICA Capacitaciones: Programación de {$this->material->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("La capacitación \"{$this->material->titulo}\" está programada para {$mesNombre}.")
            ->action('Ver mi centro de capacitaciones', route('portal.capacitaciones.index'))
            ->line('Este es un mensaje automático del Sistema Integral de Gestión EASY LOGÍSTICA.');
    }
}
