<?php

namespace Tests\Feature\Gente;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\Entrenamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorRegistrosDuranteContratoTest extends TestCase
{
    use RefreshDatabase;

    private function genteUser(): User
    {
        Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Gente');

        return $user;
    }

    private function colaboradorCompleto(): Colaborador
    {
        return Colaborador::create([
            'cedula' => '800100200',
            'nombres' => 'Carlos',
            'apellidos' => 'Ramírez',
            'is_active' => true,
            'estado_registro' => 'completo',
            'wizard_step' => 4,
        ]);
    }

    public function test_it_stores_a_llamado_de_atencion_with_an_optional_document(): void
    {
        Storage::fake('public');
        $user = $this->genteUser();
        $colaborador = $this->colaboradorCompleto();

        $this->actingAs($user)->post(route('gente.colaboradores.llamados-atencion.store', $colaborador), [
            'observacion' => 'Llegó tarde a la jornada.',
            'documento' => UploadedFile::fake()->create('soporte.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(1, $colaborador->llamadosAtencion()->count());
        $llamado = $colaborador->llamadosAtencion()->first();
        $this->assertSame('Llegó tarde a la jornada.', $llamado->observacion);
        $this->assertSame($user->id, $llamado->registrado_por_id);
        $this->assertNotNull($llamado->path);
        Storage::disk('public')->assertExists($llamado->path);
    }

    public function test_llamado_de_atencion_requires_an_observacion(): void
    {
        $user = $this->genteUser();
        $colaborador = $this->colaboradorCompleto();

        $this->actingAs($user)->post(route('gente.colaboradores.llamados-atencion.store', $colaborador), [])
            ->assertSessionHasErrors('observacion');
    }

    public function test_it_creates_a_new_entrenamiento_catalog_entry_on_the_fly(): void
    {
        $user = $this->genteUser();
        $colaborador = $this->colaboradorCompleto();

        $this->actingAs($user)->post(route('gente.colaboradores.entrenamientos.store', $colaborador), [
            'entrenamiento_nombre' => 'Manejo defensivo',
            'fecha_registro' => '2026-02-01',
            'hora_registro' => '08:30',
        ])->assertRedirect();

        $entrenamiento = Entrenamiento::where('nombre', 'Manejo defensivo')->firstOrFail();
        $this->assertSame(1, $colaborador->entrenamientos()->count());
        $registro = $colaborador->entrenamientos()->first();
        $this->assertSame($entrenamiento->id, $registro->entrenamiento_id);
        $this->assertSame($user->id, $registro->registrado_por_id);
    }

    public function test_the_same_entrenamiento_can_be_repeated_on_a_different_date_without_overwriting_history(): void
    {
        $user = $this->genteUser();
        $colaborador = $this->colaboradorCompleto();
        $entrenamiento = Entrenamiento::create(['nombre' => 'Primeros auxilios']);

        $this->actingAs($user)->post(route('gente.colaboradores.entrenamientos.store', $colaborador), [
            'entrenamiento_id' => $entrenamiento->id,
            'fecha_registro' => '2026-01-05',
            'hora_registro' => '09:00',
        ]);

        $this->actingAs($user)->post(route('gente.colaboradores.entrenamientos.store', $colaborador), [
            'entrenamiento_id' => $entrenamiento->id,
            'fecha_registro' => '2026-02-05',
            'hora_registro' => '09:00',
        ]);

        $this->assertSame(1, Entrenamiento::where('nombre', 'Primeros auxilios')->count());
        $this->assertSame(2, $colaborador->entrenamientos()->count());
    }

    public function test_show_reports_certificado_status_based_on_uploaded_documents(): void
    {
        Storage::fake('public');
        $user = $this->genteUser();
        $colaborador = $this->colaboradorCompleto();

        $response = $this->actingAs($user)->get(route('gente.colaboradores.show', $colaborador));
        $response->assertInertia(fn ($page) => $page
            ->where('certificadosAprendizaje.escuelaPilotos', false)
            ->where('certificadosAprendizaje.brigadista', false)
        );

        $colaborador->documentos()->create([
            'campo' => 'documento_escuela_pilotos',
            'path' => 'colaboradores/documentos/escuela.pdf',
            'fecha_documento' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('gente.colaboradores.show', $colaborador));
        $response->assertInertia(fn ($page) => $page
            ->where('certificadosAprendizaje.escuelaPilotos', true)
            ->where('certificadosAprendizaje.brigadista', false)
        );
    }
}
