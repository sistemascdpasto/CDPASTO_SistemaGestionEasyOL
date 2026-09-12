<?php

namespace Tests\Feature\Gente;

use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorToggleActivoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_toggles_is_active_on_each_call(): void
    {
        Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Gente');

        $colaborador = Colaborador::create([
            'cedula' => '600100200',
            'nombres' => 'Diego',
            'apellidos' => 'Salas',
            'is_active' => true,
            'estado_registro' => 'completo',
            'wizard_step' => 4,
        ]);

        $this->actingAs($user)->patch(route('gente.colaboradores.toggle-activo', $colaborador))->assertRedirect();
        $this->assertFalse($colaborador->fresh()->is_active);

        $this->actingAs($user)->patch(route('gente.colaboradores.toggle-activo', $colaborador))->assertRedirect();
        $this->assertTrue($colaborador->fresh()->is_active);
    }

    public function test_it_does_not_fail_when_the_colaborador_has_no_linked_user(): void
    {
        Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Gente');

        $colaborador = Colaborador::create([
            'cedula' => '600100201',
            'nombres' => 'Sin',
            'apellidos' => 'Usuario',
            'is_active' => true,
            'estado_registro' => 'completo',
            'wizard_step' => 4,
        ]);

        $this->actingAs($user)->patch(route('gente.colaboradores.toggle-activo', $colaborador))->assertRedirect();
        $this->assertFalse($colaborador->fresh()->is_active);
    }

    public function test_it_syncs_is_active_on_the_linked_user_in_both_directions(): void
    {
        Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Gente');

        $cuenta = User::factory()->create(['is_active' => true]);

        $colaborador = Colaborador::create([
            'cedula' => '600100202',
            'nombres' => 'Con',
            'apellidos' => 'Usuario',
            'is_active' => true,
            'estado_registro' => 'completo',
            'wizard_step' => 4,
            'user_id' => $cuenta->id,
        ]);

        $this->actingAs($user)->patch(route('gente.colaboradores.toggle-activo', $colaborador))->assertRedirect();
        $this->assertFalse($colaborador->fresh()->is_active);
        $this->assertFalse($cuenta->fresh()->is_active);

        $this->actingAs($user)->patch(route('gente.colaboradores.toggle-activo', $colaborador))->assertRedirect();
        $this->assertTrue($colaborador->fresh()->is_active);
        $this->assertTrue($cuenta->fresh()->is_active);
    }
}
