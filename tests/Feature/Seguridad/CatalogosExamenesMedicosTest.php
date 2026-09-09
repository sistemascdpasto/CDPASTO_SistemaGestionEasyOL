<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\CargoExamen;
use App\Models\Seguridad\ConceptoAptitud;
use App\Models\Seguridad\Examen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogosExamenesMedicosTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Seguridad');

        return $user;
    }

    public function test_crea_y_edita_un_examen_del_catalogo(): void
    {
        $user = $this->seguridadUser();

        $this->actingAs($user)->post(route('seguridad.examenes-medicos.catalogo.store'), [
            'nombre' => 'Optometría', 'categoria' => 'Visual', 'tipo_examen' => 'Paraclínico',
        ])->assertSessionHasNoErrors();

        $examen = Examen::where('nombre', 'Optometría')->firstOrFail();
        $this->assertTrue($examen->activo);

        $this->actingAs($user)->patch(route('seguridad.examenes-medicos.catalogo.update', $examen), [
            'nombre' => 'Optometría', 'categoria' => 'Visual', 'tipo_examen' => 'Paraclínico', 'activo' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($examen->fresh()->activo);
    }

    public function test_crea_y_edita_un_concepto_de_aptitud(): void
    {
        $user = $this->seguridadUser();

        $this->actingAs($user)->post(route('seguridad.examenes-medicos.conceptos.store'), [
            'nombre' => 'Satisfactorio',
        ])->assertSessionHasNoErrors();

        $concepto = ConceptoAptitud::where('nombre', 'Satisfactorio')->firstOrFail();

        $this->actingAs($user)->patch(route('seguridad.examenes-medicos.conceptos.update', $concepto), [
            'nombre' => 'Satisfactorio', 'activo' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($concepto->fresh()->activo);
    }

    public function test_actualiza_la_matriz_cargo_examen_reemplazando_la_seleccion_anterior(): void
    {
        $user = $this->seguridadUser();

        $examenA = Examen::create(['nombre' => 'Examen A', 'activo' => true]);
        $examenB = Examen::create(['nombre' => 'Examen B', 'activo' => true]);

        // Selección inicial: solo A, obligatorio.
        $this->actingAs($user)->post(route('seguridad.examenes-medicos.matriz.update'), [
            'cargo' => 'CONDUCTOR', 'tipo_evaluacion' => 'ingreso',
            'examenes' => [$examenA->id], 'obligatorios' => [$examenA->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CargoExamen::where('cargo', 'CONDUCTOR')->where('tipo_evaluacion', 'ingreso')->count());

        // Reemplaza por B (no obligatorio) — A debe desaparecer, no acumularse.
        $this->actingAs($user)->post(route('seguridad.examenes-medicos.matriz.update'), [
            'cargo' => 'CONDUCTOR', 'tipo_evaluacion' => 'ingreso',
            'examenes' => [$examenB->id], 'obligatorios' => [],
        ])->assertSessionHasNoErrors();

        $filas = CargoExamen::where('cargo', 'CONDUCTOR')->where('tipo_evaluacion', 'ingreso')->get();
        $this->assertCount(1, $filas);
        $this->assertSame($examenB->id, $filas->first()->examen_id);
        $this->assertFalse($filas->first()->obligatorio);
    }

    public function test_matriz_index_devuelve_la_seleccion_actual_para_un_cargo(): void
    {
        $user = $this->seguridadUser();
        $examen = Examen::create(['nombre' => 'Examen A', 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.matriz.index', [
            'cargo' => 'CONDUCTOR', 'tipo_evaluacion' => 'ingreso',
        ]));

        $response->assertInertia(fn ($page) => $page->where("seleccion.{$examen->id}.obligatorio", true));
    }
}
