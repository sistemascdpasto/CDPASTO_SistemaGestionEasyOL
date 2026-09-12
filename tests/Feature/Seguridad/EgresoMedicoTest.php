<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\CargoExamen;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\ConceptoAptitud;
use App\Models\Seguridad\Examen;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\User;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EgresoMedicoTest extends TestCase
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

    private function colaborador(array $atributos = []): Colaborador
    {
        return Colaborador::create(array_merge([
            'cedula' => '900'.random_int(100000, 999999),
            'nombres' => 'Test',
            'apellidos' => 'Colaborador',
            'estado_registro' => 'completo',
            'cargo' => 'CONDUCTOR',
            'fecha_ingreso_empresa' => now()->subYear()->toDateString(),
            'fecha_retiro_empresa' => now()->addDays(20)->toDateString(),
        ], $atributos));
    }

    public function test_crear_evaluacion_de_egreso_genera_examenes_de_matriz_si_existen(): void
    {
        $examen = Examen::create(['nombre' => 'Examen de egreso clínico', 'activo' => true]);
        CargoExamen::create([
            'cargo' => 'CONDUCTOR',
            'examen_id' => $examen->id,
            'tipo_evaluacion' => 'egreso',
            'obligatorio' => true,
            'activo' => true,
        ]);

        $colaborador = $this->colaborador(['fecha_retiro_empresa' => null]);
        $evaluacion = (new EvaluacionMedicaService())->crearEgreso($colaborador);

        $this->assertSame('egreso', $evaluacion->tipo_evaluacion);
        $this->assertSame('sin_iniciar', $evaluacion->estado);
        $this->assertSame(1, $evaluacion->examenes()->count());
    }

    public function test_crear_evaluacion_de_egreso_via_http(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador(['fecha_retiro_empresa' => null]);

        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'egreso',
        ]);

        $response->assertSessionHasNoErrors();
        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'egreso')->firstOrFail();
        $this->assertSame('egreso', $evaluacion->tipo_evaluacion);
        $response->assertRedirect(route('seguridad.examenes-medicos.show', $evaluacion->id));
    }

    public function test_show_renderiza_la_pagina_unificada_show(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador(['fecha_retiro_empresa' => null]);
        $evaluacion = (new EvaluacionMedicaService())->crearEgreso($colaborador);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.show', $evaluacion));

        $response->assertInertia(fn ($page) => $page->component('seguridad/examenes-medicos/show'));
    }

    public function test_programar_y_ejecutar_examen_de_egreso_actualiza_estados(): void
    {
        $user = $this->seguridadUser();
        $examen = Examen::create(['nombre' => 'Examen de egreso clínico', 'activo' => true]);
        CargoExamen::create([
            'cargo' => 'CONDUCTOR',
            'examen_id' => $examen->id,
            'tipo_evaluacion' => 'egreso',
            'obligatorio' => true,
            'activo' => true,
        ]);

        $colaborador = $this->colaborador(['fecha_retiro_empresa' => null]);
        $evaluacion = (new EvaluacionMedicaService())->crearEgreso($colaborador);
        $examenEvaluacion = $evaluacion->examenes()->firstOrFail();

        $this->assertSame('pendiente', $examenEvaluacion->estado);

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.examenes.programar', [$evaluacion, $examenEvaluacion]),
            ['fecha_programacion' => now()->addDays(3)->toDateString()],
        )->assertSessionHasNoErrors();

        $this->assertSame('programado', $examenEvaluacion->fresh()->estado);
        $this->assertSame('en_proceso', $evaluacion->fresh()->estado);

        $archivo = UploadedFile::fake()->create('soporte.pdf', 100, 'application/pdf');
        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.examenes.ejecutar', [$evaluacion, $examenEvaluacion]),
            ['fecha_ejecucion' => now()->toDateString(), 'soporte' => $archivo],
        )->assertSessionHasNoErrors();

        $evaluacion->refresh();
        $this->assertSame('terminada', $evaluacion->estado);
    }

    public function test_concepto_de_aptitud_se_guarda_en_evaluacion_de_egreso(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador(['fecha_retiro_empresa' => null]);
        $concepto = ConceptoAptitud::create(['nombre' => 'Satisfactorio', 'activo' => true]);
        $evaluacion = (new EvaluacionMedicaService())->crearEgreso($colaborador);

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.concepto-aptitud', $evaluacion),
            ['concepto_aptitud_id' => $concepto->id, 'emite' => 'IPS Salud', 'observacion' => 'Sin novedad.'],
        )->assertSessionHasNoErrors();

        $evaluacion->refresh();
        $this->assertSame($concepto->id, $evaluacion->concepto_aptitud_id);
        $this->assertSame('IPS Salud', $evaluacion->emite);
        $this->assertSame('Sin novedad.', $evaluacion->observacion);
    }

    public function test_crear_colaborador_con_fecha_retiro_empresa_genera_automaticamente_el_egreso(): void
    {
        $colaborador = $this->colaborador(['fecha_retiro_empresa' => now()->addDays(20)->toDateString()]);

        $egreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'egreso')->first();

        $this->assertNotNull($egreso);
        $this->assertSame('sin_iniciar', $egreso->estado);
        $this->assertSame($colaborador->fecha_retiro_empresa->toDateString(), $egreso->fecha_limite->toDateString());
        $this->assertSame(
            $colaborador->fecha_retiro_empresa->copy()->subDays(30)->toDateString(),
            $egreso->fecha_entrada_bandeja->toDateString()
        );
    }

    public function test_actualizar_la_fecha_retiro_empresa_resincroniza_el_egreso(): void
    {
        $colaborador = $this->colaborador(['fecha_retiro_empresa' => now()->addDays(20)->toDateString()]);
        $nuevaFecha = now()->addDays(45)->toDateString();

        $colaborador->update(['fecha_retiro_empresa' => $nuevaFecha]);

        $egreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'egreso')->firstOrFail();
        $this->assertSame($nuevaFecha, $egreso->fecha_limite->toDateString());
        $this->assertSame(
            now()->addDays(15)->toDateString(),
            $egreso->fecha_entrada_bandeja->toDateString()
        );
        $this->assertSame(1, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'egreso')->count());
    }

    public function test_bandeja_solo_muestra_egreso_cuando_faltan_30_dias_o_menos(): void
    {
        $user = $this->seguridadUser();

        // Colaborador 1: retiro en 45 días (fecha_entrada_bandeja en +15 días -> NO debe salir en bandeja activa)
        $colaboradorLejano = $this->colaborador(['fecha_retiro_empresa' => now()->addDays(45)->toDateString()]);

        // Colaborador 2: retiro en 20 días (fecha_entrada_bandeja en -10 días -> SÍ debe salir en bandeja activa)
        $colaboradorCercano = $this->colaborador(['fecha_retiro_empresa' => now()->addDays(20)->toDateString()]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['tipo_evaluacion' => 'egreso']));

        $response->assertInertia(function ($page) use ($colaboradorCercano, $colaboradorLejano) {
            $ids = collect($page->toArray()['props']['evaluaciones']['data'])->pluck('colaborador_id');
            \PHPUnit\Framework\Assert::assertTrue($ids->contains($colaboradorCercano->id));
            \PHPUnit\Framework\Assert::assertFalse($ids->contains($colaboradorLejano->id));

            return true;
        });
    }
}
