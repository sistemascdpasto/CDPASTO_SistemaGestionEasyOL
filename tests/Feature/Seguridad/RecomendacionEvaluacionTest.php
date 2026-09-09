<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\Recomendacion;
use App\Models\User;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecomendacionEvaluacionTest extends TestCase
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

    private function colaborador(): Colaborador
    {
        return Colaborador::create([
            'cedula' => '900'.random_int(100000, 999999),
            'nombres' => 'Test', 'apellidos' => 'Colaborador',
            'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR',
        ]);
    }

    private function evaluacion(): EvaluacionMedica
    {
        return (new EvaluacionMedicaService())->crear($this->colaborador(), 'ingreso');
    }

    public function test_agregar_recomendacion_a_una_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.recomendaciones.store', $evaluacion),
            ['recomendacion_id' => $recomendacion->id, 'observacion' => 'Reforzar en cada turno.'],
        )->assertSessionHasNoErrors();

        $this->assertSame(1, $evaluacion->recomendaciones()->count());
        $registro = $evaluacion->recomendaciones()->first();
        $this->assertSame($recomendacion->id, $registro->recomendacion_id);
        $this->assertTrue($registro->activa);
        $this->assertSame('Reforzar en cada turno.', $registro->observacion);
    }

    public function test_no_se_puede_duplicar_la_misma_recomendacion_en_una_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);

        $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.recomendaciones.store', $evaluacion),
            ['recomendacion_id' => $recomendacion->id],
        )->assertSessionHasErrors('recomendacion_id');

        $this->assertSame(1, $evaluacion->recomendaciones()->count());
    }

    public function test_toggle_activa_no_elimina_el_registro_solo_cambia_su_vigencia(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.recomendaciones.toggle-activa', [$evaluacion, $evaluacionRecomendacion]),
        )->assertSessionHasNoErrors();

        $this->assertFalse($evaluacionRecomendacion->fresh()->activa);
        $this->assertSame(1, $evaluacion->recomendaciones()->count());

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.recomendaciones.toggle-activa', [$evaluacion, $evaluacionRecomendacion]),
        )->assertSessionHasNoErrors();

        $this->assertTrue($evaluacionRecomendacion->fresh()->activa);
    }

    public function test_registrar_seguimiento_con_responsable_automatico_del_usuario_logueado(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Control de peso', 'categoria' => 'habitos', 'activo' => true]);

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        $soporte = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.recomendaciones.seguimientos.store', [$evaluacion, $evaluacionRecomendacion]),
            [
                'fecha_seguimiento' => now()->toDateString(),
                'estado_seguimiento' => 'En seguimiento',
                'observacion' => 'Colaborador citado a control.',
                'fecha_proximo_seguimiento' => now()->addMonth()->toDateString(),
                'carta_recomendacion_entregada' => true,
                'soporte' => $soporte,
            ],
        )->assertSessionHasNoErrors();

        $this->assertSame(1, $evaluacionRecomendacion->seguimientos()->count());
        $seguimiento = $evaluacionRecomendacion->seguimientos()->first();
        $this->assertSame($user->id, $seguimiento->responsable_id);
        $this->assertSame('En seguimiento', $seguimiento->estado_seguimiento);
        $this->assertTrue($seguimiento->carta_recomendacion_entregada);
        $this->assertNotNull($seguimiento->soporte_path);
        Storage::disk('public')->assertExists($seguimiento->soporte_path);
    }

    public function test_una_recomendacion_puede_tener_multiples_seguimientos_y_se_conserva_el_historico(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Control de peso', 'categoria' => 'habitos', 'activo' => true]);

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        foreach (['Pendiente', 'En seguimiento', 'Atendida'] as $estado) {
            $this->actingAs($user)->post(
                route('seguridad.examenes-medicos.recomendaciones.seguimientos.store', [$evaluacion, $evaluacionRecomendacion]),
                ['fecha_seguimiento' => now()->toDateString(), 'estado_seguimiento' => $estado],
            )->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $evaluacionRecomendacion->seguimientos()->count());
    }

    public function test_catalogo_de_recomendaciones_crud_basico(): void
    {
        $user = $this->seguridadUser();

        $this->actingAs($user)->post(route('seguridad.examenes-medicos.recomendaciones.catalogo.store'), [
            'nombre' => 'Nueva recomendación de prueba',
            'categoria' => 'medica',
        ])->assertSessionHasNoErrors();

        $recomendacion = Recomendacion::where('nombre', 'Nueva recomendación de prueba')->firstOrFail();
        $this->assertSame('medica', $recomendacion->categoria);
        $this->assertTrue($recomendacion->activo);

        $this->actingAs($user)->patch(route('seguridad.examenes-medicos.recomendaciones.catalogo.update', $recomendacion), [
            'nombre' => 'Nueva recomendación de prueba',
            'categoria' => 'habitos',
            'activo' => false,
        ])->assertSessionHasNoErrors();

        $recomendacion->refresh();
        $this->assertSame('habitos', $recomendacion->categoria);
        $this->assertFalse($recomendacion->activo);
    }

    public function test_emite_se_guarda_junto_con_el_concepto_de_aptitud(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.concepto-aptitud', $evaluacion),
            ['emite' => 'IPS Salud Ocupacional'],
        )->assertSessionHasNoErrors();

        $this->assertSame('IPS Salud Ocupacional', $evaluacion->fresh()->emite);
    }

    public function test_eliminar_recomendacion_de_una_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Control de peso', 'categoria' => 'habitos', 'activo' => true]);

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        $this->actingAs($user)->delete(
            route('seguridad.examenes-medicos.recomendaciones.destroy', [$evaluacion, $evaluacionRecomendacion]),
        )->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('evaluacion_recomendaciones', [
            'id' => $evaluacionRecomendacion->id,
        ]);
    }

    public function test_actualizar_seguimiento_existente_conserva_historico_y_soporte(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $evaluacion = $this->evaluacion();
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $recomendacion->id,
            'activa' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        $soporteInicial = UploadedFile::fake()->create('soporte1.pdf', 100, 'application/pdf');

        $seguimiento = $evaluacionRecomendacion->seguimientos()->create([
            'fecha_seguimiento' => now()->toDateString(),
            'estado_seguimiento' => 'Pendiente',
            'observacion' => 'Primer seguimiento.',
            'responsable_id' => $user->id,
            'soporte_path' => $soporteInicial->store('recomendaciones-seguimientos', 'public'),
        ]);

        $this->actingAs($user)->put(
            route('seguridad.examenes-medicos.recomendaciones.seguimientos.update', [$evaluacion, $evaluacionRecomendacion, $seguimiento]),
            [
                'fecha_seguimiento' => now()->toDateString(),
                'estado_seguimiento' => 'Atendida',
                'observacion' => 'Seguimiento actualizado con nuevos detalles.',
            ],
        )->assertSessionHasNoErrors();

        $seguimientoActualizado = $seguimiento->fresh();
        $this->assertSame('Atendida', $seguimientoActualizado->estado_seguimiento);
        $this->assertSame('Seguimiento actualizado con nuevos detalles.', $seguimientoActualizado->observacion);
        // Soporte se mantiene si no se sube uno nuevo
        $this->assertNotNull($seguimientoActualizado->soporte_path);
        Storage::disk('public')->assertExists($seguimientoActualizado->soporte_path);
    }
}
