<?php

namespace Tests\Feature\Gente;

use App\Models\Gente\DpoAcademy;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DpoAcademyTest extends TestCase
{
    use RefreshDatabase;

    private function genteUser(): User
    {
        $role = Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_carga_vista_dpo_academy_exitosamente(): void
    {
        $user = $this->genteUser();

        DpoAcademy::create([
            'region' => 'Norte',
            'centro' => 'CD Barranquilla',
            'negocio' => 'Bebidas',
            'qr_safety' => 'QR-100',
            'nombre' => 'Juan Gomez',
            'cargo' => 'Operador',
            'coronita' => 'Oro',
            'calificacion' => 100.00,
            'status' => 'Completado',
        ]);

        $response = $this->actingAs($user)->get(route('gente.dpo-academy.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('gente/dpo-academy/index')
            ->where('kpis.total', 1)
            ->where('kpis.promedio_calificacion', fn ($val) => (float) $val === 100.0)
            ->where('kpis.total_coronitas', 1)
            ->where('kpis.total_completados', 1)
            ->where('registros.data.0.nombre', 'Juan Gomez')
            ->where('registros.data.0.region', 'Norte')
        );
    }

    public function test_importar_dpo_academy_desde_csv(): void
    {
        $user = $this->genteUser();

        Colaborador::create([
            'cedula' => '99887766',
            'nombres' => 'Pedro',
            'apellidos' => 'Picapiedra',
            'cargo' => 'Conductor',
            'is_active' => true,
        ]);

        $csvContent = "Region,Centro,Negocio,QR Safety,Nombre,Cargo,Coronita,Calificación,Status\n"
            ."Sur,CD Cali,Logistica,QR-500,Pedro Picapiedra,Conductor,Plata,95.5%,Completado\n";

        $file = UploadedFile::fake()->createWithContent('dpo_academy.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('gente.dpo-academy.importar'), [
            'archivo' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dpo_academy', [
            'region' => 'Sur',
            'centro' => 'CD Cali',
            'negocio' => 'Logistica',
            'qr_safety' => 'QR-500',
            'nombre' => 'Pedro Picapiedra',
            'cargo' => 'Conductor',
            'coronita' => 'Plata',
            'calificacion' => 95.50,
            'status' => 'Completado',
        ]);
    }

    public function test_importar_dpo_academy_asocia_colaborador_por_codigo_qr_skap(): void
    {
        $user = $this->genteUser();

        $colaborador = Colaborador::create([
            'cedula' => '11223344',
            'nombres' => 'Mario',
            'apellidos' => 'Bros',
            'cargo' => 'Mecanico',
            'codigo_qr_skap' => 'QR-SKAP-999',
            'is_active' => true,
        ]);

        $csvContent = "Region,Centro,Negocio,QR Safety,Nombre,Cargo,Coronita,Calificación,Status\n"
            ."Occidente,CD Medellin,Mantenimiento,QR-SKAP-999,Mario Bros,Mecanico,Oro,100%,Completado\n";

        $file = UploadedFile::fake()->createWithContent('dpo_qr.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('gente.dpo-academy.importar'), [
            'archivo' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('dpo_academy', [
            'colaborador_id' => $colaborador->id,
            'qr_safety' => 'QR-SKAP-999',
            'nombre' => 'Mario Bros',
        ]);
    }

    public function test_exportar_dpo_academy_csv(): void
    {
        $user = $this->genteUser();

        DpoAcademy::create([
            'region' => 'Centro',
            'centro' => 'CD Bogota',
            'negocio' => 'Ventas',
            'qr_safety' => 'QR-300',
            'nombre' => 'Ana Lopez',
            'cargo' => 'Supervisora',
            'coronita' => 'Bronce',
            'calificacion' => 88.00,
            'status' => 'Aprobado',
        ]);

        $response = $this->actingAs($user)->get(route('gente.dpo-academy.exportar'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('dpo_academy', $response->headers->get('content-disposition'));
    }

    public function test_limpiar_registros_dpo_academy(): void
    {
        $user = $this->genteUser();

        DpoAcademy::create([
            'nombre' => 'Carlos Perez',
            'calificacion' => 70.00,
        ]);

        $this->assertDatabaseCount('dpo_academy', 1);

        $response = $this->actingAs($user)->post(route('gente.dpo-academy.limpiar'));

        $response->assertRedirect();
        $this->assertDatabaseCount('dpo_academy', 0);
    }
}
