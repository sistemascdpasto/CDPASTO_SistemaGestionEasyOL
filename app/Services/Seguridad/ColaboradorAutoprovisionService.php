<?php

namespace App\Services\Seguridad;

use App\Enums\Role;
use App\Models\User;

/**
 * Al crear un colaborador (a mano o por carga masiva) se le provisiona
 * automáticamente una cuenta para el portal de autoservicio: usuario y
 * contraseña son su cédula. Si ya existe un usuario con esa cédula (p. ej.
 * provisionado antes), se reutiliza y solo se le asegura el rol Colaborador.
 */
class ColaboradorAutoprovisionService
{
    public function provisionar(string $cedula, string $nombres, string $apellidos, ?string $correo = null): User
    {
        $usuario = User::where('identification_number', $cedula)->first();

        if (! $usuario) {
            $usuario = User::create([
                'first_name' => $nombres,
                'last_name' => $apellidos,
                'identification_number' => $cedula,
                'password' => $cedula,
                'is_active' => true,
            ]);
        }

        $this->sincronizarCorreo($usuario, $correo);

        if (! $usuario->hasRole(Role::Colaborador->value)) {
            $usuario->assignRole(Role::Colaborador->value);
        }

        return $usuario;
    }

    /**
     * Mantiene sincronizado el email de la cuenta con el "correo" del
     * formulario de colaborador (contacto), tanto al crear como al editar.
     * El correo del formulario no es único (es solo un dato de contacto),
     * así que solo se aplica si no choca con el email de otro usuario.
     */
    public function sincronizarCorreo(User $usuario, ?string $correo): void
    {
        if (! $correo || $correo === $usuario->email) {
            return;
        }

        $disponible = ! User::where('email', $correo)->whereKeyNot($usuario->id)->exists();

        if ($disponible) {
            $usuario->update(['email' => $correo]);
        }
    }
}
