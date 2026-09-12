<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AciIndexTest extends TestCase
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

    private function crearColaborador(string $cedula, string $nombres, string $centro): Colaborador
    {
        return Colaborador::create([
            'cedula' => $cedula, 'nombres' => $nombres, 'apellidos' => 'Apellido', 'estado_registro' => 'completo',
            'centro' => $centro, 'codigo_qr_skap' => strtoupper(substr(md5($cedula), 0, 8)),
        ]);
    }

    public function test_filtra_por_folio_mes_anio_colaborador_area_y_centro(): void
    {
        $user = $this->seguridadUser();

        $ana = $this->crearColaborador('900111111', 'Ana', 'UC');
        $beto = $this->crearColaborador('900222222', 'Beto', 'UD');

        Aci::create(['folio' => 'A1', 'fecha_incidente' => '2026-08-05', 'colaborador_id' => $ana->id, 'area' => 'Ruta']);
        Aci::create(['folio' => 'A2', 'fecha_incidente' => '2026-08-10', 'colaborador_id' => $beto->id, 'area' => 'Patios']);
        Aci::create(['folio' => 'A3', 'fecha_incidente' => '2026-05-10', 'colaborador_id' => $ana->id, 'area' => 'Ruta']);

        $porFolio = $this->actingAs($user)->get(route('seguridad.acis.index', ['folio' => 'A1']));
        $porFolio->assertInertia(fn ($page) => $page->where('acis.data.0.folio', 'A1')->where('acis.data', fn ($data) => count($data) === 1));

        $porMes = $this->actingAs($user)->get(route('seguridad.acis.index', ['mes' => '8', 'anio' => '2026']));
        $porMes->assertInertia(fn ($page) => $page->where('acis.data', fn ($data) => count($data) === 2));

        $porColaborador = $this->actingAs($user)->get(route('seguridad.acis.index', ['colaborador' => 'Beto']));
        $porColaborador->assertInertia(fn ($page) => $page->where('acis.data.0.folio', 'A2'));

        $porArea = $this->actingAs($user)->get(route('seguridad.acis.index', ['area' => 'Patios']));
        $porArea->assertInertia(fn ($page) => $page->where('acis.data.0.folio', 'A2'));

        $porCentro = $this->actingAs($user)->get(route('seguridad.acis.index', ['centro' => 'UD']));
        $porCentro->assertInertia(fn ($page) => $page->where('acis.data.0.folio', 'A2'));
    }

    public function test_show_devuelve_el_detalle_completo_del_reporte(): void
    {
        $user = $this->seguridadUser();
        $ana = $this->crearColaborador('900111111', 'Ana', 'UC');

        $aci = Aci::create([
            'folio' => 'D1', 'fecha_incidente' => '2026-08-05', 'colaborador_id' => $ana->id,
            'descripcion' => 'Descripción de prueba', 'area' => 'Ruta',
        ]);

        $response = $this->actingAs($user)->get(route('seguridad.acis.show', $aci));

        $response->assertInertia(fn ($page) => $page
            ->component('seguridad/acis/show')
            ->where('aci.folio', 'D1')
            ->where('aci.descripcion', 'Descripción de prueba')
            ->where('aci.colaborador.nombres', 'Ana'));
    }
}
