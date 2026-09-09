<?php

namespace Tests\Feature\Gente;

use App\Models\Gente\ColaboradorCalificacion;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorCalificacionTest extends TestCase
{
    use RefreshDatabase;

    private function genteUser(): User
    {
        $role = Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_carga_vista_calificaciones_exitosamente(): void
    {
        $user = $this->genteUser();

        ColaboradorCalificacion::create([
            'identificacion' => '12345678',
            'colaborador' => 'Carlos Perez',
            'cargo' => 'Auxiliar de Reparto',
            'centro_distribucion' => 'CD Bogota',
            'modulo_id_externo' => 'MOD-101',
            'modulo' => 'Módulo Seguridad Vial',
            'nota_modulo' => 95.50,
        ]);

        $response = $this->actingAs($user)->get(route('gente.calificaciones.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('gente/calificaciones/index')
            ->where('resumen.total_registros', 1)
            ->where('resumen.total_colaboradores', 1)
            ->where('resumen.total_modulos', 1)
            ->where('resumen.promedio_nota', fn ($val) => (float) $val === 95.5)
            ->where('calificaciones.data.0.colaborador', 'Carlos Perez')
            ->where('calificaciones.data.0.modulo', 'Módulo Seguridad Vial')
        );
    }

    public function test_importar_calificaciones_desde_csv(): void
    {
        $user = $this->genteUser();

        $colaborador = Colaborador::create([
            'cedula' => '87654321',
            'nombres' => 'Maria',
            'apellidos' => 'Rodriguez',
            'cargo' => 'Conductor',
            'is_active' => true,
        ]);

        $csvContent = "COLABORADOR,IDENTIFICACIÓN,MÓDULO,ID,CARGO,CENTRO DISTRIBUCIÓN,NOTA MÓDULO\n"
            ."Maria Rodriguez,87654321,Manejo Defensivo,MOD-202,Conductor,CD Medellin,88.00\n";

        $file = UploadedFile::fake()->createWithContent('calificaciones.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('gente.calificaciones.importar'), [
            'archivo' => $file,
        ]);

        $response->assertRedirect(route('gente.calificaciones.index'));

        $this->assertDatabaseHas('colaborador_calificaciones', [
            'colaborador_id' => $colaborador->id,
            'identificacion' => '87654321',
            'colaborador' => 'Maria Rodriguez',
            'modulo' => 'Manejo Defensivo',
            'modulo_id_externo' => 'MOD-202',
            'cargo' => 'Conductor',
            'centro_distribucion' => 'CD Medellin',
            'nota_modulo' => 88.00,
        ]);
    }

    public function test_exportar_calificaciones_csv(): void
    {
        $user = $this->genteUser();

        ColaboradorCalificacion::create([
            'identificacion' => '12345678',
            'colaborador' => 'Carlos Perez',
            'cargo' => 'Auxiliar',
            'centro_distribucion' => 'CD Cali',
            'modulo_id_externo' => 'MOD-303',
            'modulo' => 'Modulo Primeros Auxilios',
            'nota_modulo' => 90.00,
        ]);

        $response = $this->actingAs($user)->get(route('gente.calificaciones.exportar'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('calificaciones_modulos.csv', $response->headers->get('content-disposition'));
    }
}
