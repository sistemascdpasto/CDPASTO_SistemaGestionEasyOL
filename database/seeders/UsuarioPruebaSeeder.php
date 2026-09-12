<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $identificacion = '1233191710';

        $user = User::updateOrCreate(
            ['identification_number' => $identificacion],
            [
                'first_name'            => 'Usuario',
                'last_name'             => 'Prueba',
                'identification_number' => $identificacion,
                'email'                 => 'prueba@easy.test',
                'password'              => Hash::make($identificacion),
                'is_active'             => true,
            ]
        );

        // Asegurar que tenga el rol Administrador (RoleSeeder debe haber corrido primero)
        if (! $user->hasRole(RoleEnum::Administrador->value)) {
            $user->assignRole(RoleEnum::Administrador->value);
        }

        $this->command->info("Usuario de prueba listo — cédula: {$identificacion} / contraseña: {$identificacion}");
    }
}
