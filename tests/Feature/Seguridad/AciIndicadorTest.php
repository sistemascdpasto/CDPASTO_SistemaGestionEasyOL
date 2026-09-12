<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AciIndicadorTest extends TestCase
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

    private function colaboradorConSkap(string $cedula, bool $activo = true): Colaborador
    {
        return Colaborador::create([
            'cedula' => $cedula, 'nombres' => 'Nombre', 'apellidos' => 'Apellido', 'estado_registro' => 'completo',
            'codigo_qr_skap' => strtoupper(substr(md5($cedula), 0, 8)), 'is_active' => $activo,
        ]);
    }

    private function aci(string $folio, Colaborador $colaborador, string $fecha, ?string $area = null, ?string $descripcion = null): Aci
    {
        return Aci::create([
            'folio' => $folio, 'colaborador_id' => $colaborador->id, 'fecha_incidente' => $fecha,
            'area' => $area, 'descripcion' => $descripcion,
        ]);
    }

    public function test_calcula_total_del_mes_y_clasifica_cumplimiento_de_meta(): void
    {
        $user = $this->seguridadUser();

        $sinReportes = $this->colaboradorConSkap('900111111');
        $conTres = $this->colaboradorConSkap('900222222');
        $conCinco = $this->colaboradorConSkap('900333333');
        // Colaborador sin código QR SKAP: no cuenta en la población del programa.
        Colaborador::create(['cedula' => '900444444', 'nombres' => 'Sin', 'apellidos' => 'Skap', 'estado_registro' => 'completo']);
        // Colaborador con SKAP pero inactivo: tampoco cuenta.
        $this->colaboradorConSkap('900555555', activo: false);

        for ($i = 1; $i <= 3; $i++) {
            $this->aci("T{$i}", $conTres, "2026-08-0{$i}");
        }
        for ($i = 1; $i <= 5; $i++) {
            $this->aci("C{$i}", $conCinco, "2026-08-0{$i}");
        }
        // Un reporte en otro mes no debe contar para agosto.
        $this->aci('FUERA', $conCinco, '2026-07-15');

        $response = $this->actingAs($user)->get(route('seguridad.acis.indicadores', ['mes' => 8, 'anio' => 2026]));

        $response->assertInertia(fn ($page) => $page
            ->component('seguridad/acis/indicadores')
            ->where('resumen.total_aci_mes', 8)
            ->where('resumen.colaboradores_en_programa', 3)
            ->where('resumen.cumplen_meta', 1)
            ->where('resumen.no_cumplen_meta', 2)
            ->where('resumen.porcentaje_cumplimiento', 33.3)
            ->where('resumen.qr_skap_activos', 3));

        $response->assertInertia(fn ($page) => $page->where('colaboradoresMeta.data', function ($fila) use ($sinReportes, $conTres, $conCinco) {
            $coleccion = collect($fila);

            return $coleccion->contains(fn ($p) => $p['id'] === $sinReportes->id && $p['cantidad'] === 0 && $p['faltantes'] === 4 && $p['cumple'] === false)
                && $coleccion->contains(fn ($p) => $p['id'] === $conTres->id && $p['cantidad'] === 3 && $p['faltantes'] === 1 && $p['cumple'] === false)
                && $coleccion->contains(fn ($p) => $p['id'] === $conCinco->id && $p['cantidad'] === 5 && $p['faltantes'] === 0 && $p['cumple'] === true);
        }));
    }

    public function test_colaboradores_meta_incluye_a_todos_paginado_y_ordenado_por_mayor_rezago(): void
    {
        $user = $this->seguridadUser();

        for ($i = 1; $i <= 17; $i++) {
            $cedula = str_pad((string) (900000000 + $i), 9, '0', STR_PAD_LEFT);
            $colaborador = $this->colaboradorConSkap($cedula);
            // Los primeros 3 sí cumplen la meta, para confirmar que también aparecen en la lista.
            if ($i <= 3) {
                for ($j = 1; $j <= 4; $j++) {
                    $this->aci("CUMPLE{$i}-{$j}", $colaborador, "2026-08-0{$j}");
                }
            }
        }

        $primeraPagina = $this->actingAs($user)->get(route('seguridad.acis.indicadores', ['mes' => 8, 'anio' => 2026]));

        $primeraPagina->assertInertia(fn ($page) => $page
            ->where('colaboradoresMeta.total', 17)
            ->where('colaboradoresMeta.data', fn ($fila) => count($fila) === 15)
            // Ordenado por cantidad ascendente: los que no reportaron nada (0) van primero.
            ->where('colaboradoresMeta.data.0.cantidad', 0));

        $segundaPagina = $this->actingAs($user)
            ->get(route('seguridad.acis.indicadores', ['mes' => 8, 'anio' => 2026, 'page' => 2]));

        $segundaPagina->assertInertia(fn ($page) => $page->where('colaboradoresMeta.data', fn ($fila) => count($fila) === 2));
    }

    public function test_problematicas_detectadas_cuenta_solo_reportes_con_descripcion(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaboradorConSkap('900111111');

        $this->aci('D1', $colaborador, '2026-08-01', descripcion: 'Con descripción');
        $this->aci('D2', $colaborador, '2026-08-02', descripcion: null);

        $response = $this->actingAs($user)->get(route('seguridad.acis.indicadores', ['mes' => 8, 'anio' => 2026]));

        $response->assertInertia(fn ($page) => $page->where('resumen.problematicas_detectadas', 1));
    }

    public function test_aci_por_area_y_tendencia_de_seis_meses_incluye_meses_sin_datos(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaboradorConSkap('900111111');

        $this->aci('A1', $colaborador, '2026-08-01', area: 'Ruta');
        $this->aci('A2', $colaborador, '2026-08-02', area: 'Ruta');
        $this->aci('A3', $colaborador, '2026-08-03', area: 'Patios');
        $this->aci('A4', $colaborador, '2026-05-01', area: 'Ruta');

        $response = $this->actingAs($user)->get(route('seguridad.acis.indicadores', ['mes' => 8, 'anio' => 2026]));

        $response->assertInertia(fn ($page) => $page
            ->where('aciPorArea', function ($porArea) {
                $keyed = collect($porArea)->keyBy('area');

                return $keyed['Ruta']['cantidad'] === 2 && $keyed['Patios']['cantidad'] === 1;
            })
            ->where('aciPorMes', function ($porMes) {
                $keyed = collect($porMes)->keyBy('periodo');

                return count($keyed) === 6
                    && $keyed['2026-08']['cantidad'] === 3
                    && $keyed['2026-05']['cantidad'] === 1
                    && $keyed['2026-06']['cantidad'] === 0;
            }));
    }
}
