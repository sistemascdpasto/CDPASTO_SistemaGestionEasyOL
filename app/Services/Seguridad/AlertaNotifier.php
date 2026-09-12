<?php

namespace App\Services\Seguridad;

use App\Enums\Role;
use App\Models\Seguridad\Alerta;
use App\Models\User;
use App\Notifications\Seguridad\AlertaGenerada;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AlertaNotifier
{
    /**
     * HU041: notifica por correo a Administrador/Seguridad cuando se genera una alerta.
     *
     * El envío nunca debe tumbar la acción que originó la alerta (registrar una
     * prueba, una condición de salud, etc.) — un fallo de correo (SMTP caído,
     * credenciales inválidas) solo se registra en el log.
     */
    public static function notificar(Alerta $alerta): void
    {
        $responsables = User::role([Role::Administrador->value, Role::Seguridad->value])
            ->whereNotNull('email')
            ->get();

        foreach ($responsables as $responsable) {
            try {
                Notification::send($responsable, new AlertaGenerada($alerta));
            } catch (Throwable $e) {
                Log::warning('No se pudo enviar la notificación de alerta de Seguridad.', [
                    'alerta_id' => $alerta->id,
                    'destinatario' => $responsable->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
