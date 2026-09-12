<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AciConsultaSkapTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_sin_seleccion_solo_devuelve_la_lista_de_colaboradores(): void
    {
        $user = $this->seguridadUser();
        Colaborador::create(['cedula' => '900111111', 'nombres' => 'Ana', 'apellidos' => 'Ruiz', 'estado_registro' => 'completo']);

        $response = $this->actingAs($user)->get(route('seguridad.acis.consultar-qr'));

        $response->assertInertia(fn ($page) => $page
            ->component('seguridad/acis/consultar-qr')
            ->where('colaboradorSeleccionado', null)
            ->where('colaboradores.0.nombres', 'Ana'));
    }

    public function test_estado_skap_activo_inactivo_y_sin_asignar(): void
    {
        $user = $this->seguridadUser();

        $activo = Colaborador::create([
            'cedula' => '900111111', 'nombres' => 'Ana', 'apellidos' => 'Ruiz', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345', 'is_active' => true,
        ]);
        $inactivo = Colaborador::create([
            'cedula' => '900222222', 'nombres' => 'Beto', 'apellidos' => 'Diaz', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'XYZ98765', 'is_active' => false,
        ]);
        $sinAsignar = Colaborador::create([
            'cedula' => '900333333', 'nombres' => 'Caro', 'apellidos' => 'Leon', 'estado_registro' => 'completo',
            'codigo_qr_skap' => null, 'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('seguridad.acis.consultar-qr', ['colaborador_id' => $activo->id]))
            ->assertInertia(fn ($page) => $page->where('colaboradorSeleccionado.estado_skap', 'Activo'));

        $this->actingAs($user)->get(route('seguridad.acis.consultar-qr', ['colaborador_id' => $inactivo->id]))
            ->assertInertia(fn ($page) => $page->where('colaboradorSeleccionado.estado_skap', 'Inactivo'));

        $this->actingAs($user)->get(route('seguridad.acis.consultar-qr', ['colaborador_id' => $sinAsignar->id]))
            ->assertInertia(fn ($page) => $page->where('colaboradorSeleccionado.estado_skap', 'Sin asignar'));
    }

    public function test_muestra_el_historial_de_aci_del_colaborador_seleccionado(): void
    {
        $user = $this->seguridadUser();

        $ana = Colaborador::create([
            'cedula' => '900111111', 'nombres' => 'Ana', 'apellidos' => 'Ruiz', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345',
        ]);
        $otro = Colaborador::create([
            'cedula' => '900222222', 'nombres' => 'Beto', 'apellidos' => 'Diaz', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'XYZ98765',
        ]);

        Aci::create(['folio' => 'H1', 'fecha_incidente' => '2026-08-01', 'colaborador_id' => $ana->id]);
        Aci::create(['folio' => 'H2', 'fecha_incidente' => '2026-08-10', 'colaborador_id' => $ana->id]);
        Aci::create(['folio' => 'H3', 'fecha_incidente' => '2026-08-10', 'colaborador_id' => $otro->id]);

        $response = $this->actingAs($user)->get(route('seguridad.acis.consultar-qr', ['colaborador_id' => $ana->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('historial.data.0.folio', 'H2')
            ->where('historial.data.1.folio', 'H1')
            ->where('historial.data', fn ($data) => count($data) === 2));
    }
}
