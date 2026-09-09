<?php

namespace Tests\Feature\Gente;

use App\Models\Gente\Ausentismo;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AusentismoTest extends TestCase
{
    use RefreshDatabase;

    private function genteUser(): User
    {
        $role = Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_carga_vista_ausentismo_exitosamente(): void
    {
        $user = $this->genteUser();

        Ausentismo::create([
            'apellidos' => 'Perez',
            'nombres' => 'Juan',
            'identificador' => '12345678',
            'grupo' => 'Grupo A',
            'fecha' => '2026-09-01',
            'permiso' => 'Ninguno',
            'turno' => 'Turno Mañana',
            'entro_1' => '07:00',
            'atraso_1' => '00:00',
            'salio_1' => '12:00',
            'adelanto_1' => '00:00',
            'entro_2' => '13:00',
            'atraso_2' => '00:00',
            'salio_2' => '17:00',
            'adelanto_2' => '00:00',
        ]);

        $response = $this->actingAs($user)->get(route('gente.ausentismo.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('gente/ausentismo/index')
            ->where('kpis.total', 1)
            ->where('kpis.total_colaboradores', 1)
            ->where('registros.data.0.identificador', '12345678')
            ->where('registros.data.0.apellidos', 'Perez')
        );
    }

    public function test_importar_ausentismo_desde_csv_con_columnas_duplicadas(): void
    {
        $user = $this->genteUser();

        Colaborador::create([
            'cedula' => '87654321',
            'nombres' => 'Maria',
            'apellidos' => 'Gomez',
            'codigo_qr_skap' => 'QR-87654',
            'is_active' => true,
        ]);

        $csvContent = "Apellidos,Nombres,Identificador,Grupo,Fecha,Permiso,Turno,Entró,Atraso,Salió,Adelanto,Entró,Atraso,Salió,Adelanto\n"
            ."Gomez,Maria,87654321,Grupo B,2026-09-02,Citas Medicas,Turno 1,07:15,00:15,12:00,00:00,13:00,00:00,17:00,00:00\n";

        $file = UploadedFile::fake()->createWithContent('ausentismo.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('gente.ausentismo.importar'), [
            'archivo' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ausentismos', [
            'identificador' => '87654321',
            'apellidos' => 'Gomez',
            'nombres' => 'Maria',
            'grupo' => 'Grupo B',
            'fecha' => '2026-09-02',
            'permiso' => 'Citas Medicas',
            'entro_1' => '07:15',
            'atraso_1' => '00:15',
            'salio_1' => '12:00',
            'entro_2' => '13:00',
            'salio_2' => '17:00',
        ]);
    }

    public function test_importar_ausentismo_con_encabezados_exactos_del_excel_14_columnas(): void
    {
        $user = $this->genteUser();

        $colaborador = Colaborador::create([
            'cedula' => '10203040',
            'nombres' => 'Carlos',
            'apellidos' => 'Santana',
            'is_active' => true,
        ]);

        $csvContent = "Apellidos,Nombres,Identificador,Grup,Fecha,Permiso,Turno,Entro,Atraso,Salio,Adelant,Entro,Atraso,Salio\n"
            ."Santana,Carlos,10203040,Reparto,04/09/2026,Ninguno,Turno 1,07:00,00:00,12:00,00:00,13:00,00:00,17:30\n";

        $file = UploadedFile::fake()->createWithContent('ausentismo_14cols.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('gente.ausentismo.importar'), [
            'archivo' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ausentismos', [
            'colaborador_id' => $colaborador->id,
            'identificador' => '10203040',
            'apellidos' => 'Santana',
            'nombres' => 'Carlos',
            'grupo' => 'Reparto',
            'fecha' => '2026-09-04',
            'entro_1' => '07:00',
            'salio_1' => '12:00',
            'entro_2' => '13:00',
            'salio_2' => '17:30',
        ]);
    }

    public function test_exportar_ausentismo_csv(): void
    {
        $user = $this->genteUser();

        Ausentismo::create([
            'apellidos' => 'Lopez',
            'nombres' => 'Carlos',
            'identificador' => '555555',
            'grupo' => 'Grupo C',
            'fecha' => '2026-09-03',
        ]);

        $response = $this->actingAs($user)->get(route('gente.ausentismo.exportar'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('ausentismo', $response->headers->get('content-disposition'));
    }

    public function test_limpiar_registros_ausentismo(): void
    {
        $user = $this->genteUser();

        Ausentismo::create([
            'identificador' => '999999',
            'fecha' => '2026-09-04',
        ]);

        $this->assertDatabaseCount('ausentismos', 1);

        $response = $this->actingAs($user)->post(route('gente.ausentismo.limpiar'));

        $response->assertRedirect();
        $this->assertDatabaseCount('ausentismos', 0);
    }
}
