<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EncuestaMorbilidad;
use App\Models\Seguridad\EncuestaMorbilidadRespuesta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EncuestaMorbilidadIndexTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function encuestaCompletada(): EncuestaMorbilidad
    {
        $colaborador = Colaborador::create([
            'cedula' => '900555000', 'nombres' => 'Carlos', 'apellidos' => 'Reyes', 'estado_registro' => 'completo',
        ]);

        $encuesta = EncuestaMorbilidad::create([
            'colaborador_id' => $colaborador->id,
            'estado' => EncuestaMorbilidad::ESTADO_COMPLETADA,
            'fecha_hora' => now(),
            'enviado_en' => now(),
        ]);

        EncuestaMorbilidadRespuesta::create([
            'encuesta_morbilidad_id' => $encuesta->id,
            'numero_pregunta' => 97,
            'valor' => 'Si',
        ]);

        return $encuesta;
    }

    public function test_usuario_con_rol_seguridad_ve_el_listado_y_el_detalle_completo(): void
    {
        $user = $this->seguridadUser();
        $encuesta = $this->encuestaCompletada();

        $this->actingAs($user)->get(route('seguridad.encuestas-morbilidad.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seguridad/encuestas-morbilidad/index')
                ->has('encuestas.data', 1)
            );

        $this->actingAs($user)->get(route('seguridad.encuestas-morbilidad.show', $encuesta))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seguridad/encuestas-morbilidad/show')
                ->where('respuestas.97.valor', 'Si')
            );
    }

    public function test_usuario_con_otro_rol_no_puede_acceder(): void
    {
        $role = Role::create(['name' => 'Flota', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $encuesta = $this->encuestaCompletada();

        $this->actingAs($user)->get(route('seguridad.encuestas-morbilidad.index'))->assertForbidden();
        $this->actingAs($user)->get(route('seguridad.encuestas-morbilidad.show', $encuesta))->assertForbidden();
    }
}
