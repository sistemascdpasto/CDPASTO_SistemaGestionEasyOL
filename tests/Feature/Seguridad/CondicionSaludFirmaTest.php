<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\CondicionSalud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CondicionSaludFirmaTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_it_rejects_signing_an_ingreso_record(): void
    {
        $user = $this->seguridadUser();

        $colaborador = Colaborador::create([
            'cedula' => '1002003004',
            'nombres' => 'Laura',
            'apellidos' => 'Portal',
            'is_active' => true,
        ]);

        $ingreso = CondicionSalud::create([
            'colaborador_id' => $colaborador->id,
            'momento' => 'ingreso',
            'estado' => 'Bueno',
            'responsable_id' => $user->id,
            'fecha_hora' => now(),
            'consentimiento_aceptado' => true,
        ]);

        Storage::fake('public');

        $this->actingAs($user)->post(route('seguridad.condiciones-salud.firmar', $ingreso), [
            'firma' => UploadedFile::fake()->image('firma.png'),
        ])->assertForbidden();
    }

    public function test_it_rejects_signing_when_the_day_is_incomplete(): void
    {
        $user = $this->seguridadUser();

        $colaborador = Colaborador::create([
            'cedula' => '1002003004',
            'nombres' => 'Laura',
            'apellidos' => 'Portal',
            'is_active' => true,
        ]);

        // Salida sin su ingreso correspondiente el mismo día.
        $salida = CondicionSalud::create([
            'colaborador_id' => $colaborador->id,
            'momento' => 'salida',
            'estado' => 'Bueno',
            'responsable_id' => $user->id,
            'fecha_hora' => now(),
            'consentimiento_aceptado' => true,
        ]);

        Storage::fake('public');

        $this->actingAs($user)->post(route('seguridad.condiciones-salud.firmar', $salida), [
            'firma' => UploadedFile::fake()->image('firma.png'),
        ])->assertStatus(422);
    }

    public function test_it_signs_the_salida_record_when_the_day_is_complete(): void
    {
        $user = $this->seguridadUser();

        $colaborador = Colaborador::create([
            'cedula' => '1002003004',
            'nombres' => 'Laura',
            'apellidos' => 'Portal',
            'is_active' => true,
        ]);

        CondicionSalud::create([
            'colaborador_id' => $colaborador->id,
            'momento' => 'ingreso',
            'estado' => 'Bueno',
            'responsable_id' => $user->id,
            'fecha_hora' => now()->setTime(7, 0),
            'consentimiento_aceptado' => true,
        ]);

        $salida = CondicionSalud::create([
            'colaborador_id' => $colaborador->id,
            'momento' => 'salida',
            'estado' => 'Bueno',
            'responsable_id' => $user->id,
            'fecha_hora' => now()->setTime(16, 0),
            'consentimiento_aceptado' => true,
        ]);

        Storage::fake('public');

        $this->actingAs($user)->post(route('seguridad.condiciones-salud.firmar', $salida), [
            'firma' => UploadedFile::fake()->image('firma.png'),
        ])->assertRedirect();

        $salida->refresh();

        $this->assertNotNull($salida->firma_supervisor_path);
        $this->assertSame($user->id, $salida->firmado_por_id);
        $this->assertNotNull($salida->firmado_en);
        Storage::disk('public')->assertExists($salida->firma_supervisor_path);
    }
}
