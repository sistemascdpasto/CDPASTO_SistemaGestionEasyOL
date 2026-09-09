<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Models\Seguridad\PlanAccionOwd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanAccionOwdTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function plan(): PlanAccionOwd
    {
        $colaborador = Colaborador::create([
            'cedula' => '900777888', 'nombres' => 'Ana', 'apellidos' => 'Rios', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADO2',
        ]);

        $evaluacion = EvaluacionOwd::create([
            'qr_safety' => $colaborador->codigo_qr_skap,
            'colaborador_id' => $colaborador->id,
            'fecha_evaluacion' => now(),
        ]);

        $pregunta = EvaluacionOwdPregunta::create([
            'evaluacion_owd_id' => $evaluacion->id,
            'puntuacion' => 'No OK',
            'tarea' => 'Uso de EPP',
            'requiere_plan_accion' => true,
        ]);

        return PlanAccionOwd::create([
            'evaluacion_owd_pregunta_id' => $pregunta->id,
            'estado' => PlanAccionOwd::ESTADO_PENDIENTE,
        ]);
    }

    public function test_registrar_un_seguimiento_actualiza_el_estado_del_plan_y_queda_en_el_historial(): void
    {
        $user = $this->seguridadUser();
        $plan = $this->plan();

        $response = $this->actingAs($user)->post(route('seguridad.planes-accion-owd.seguimientos.store', $plan), [
            'estado' => PlanAccionOwd::ESTADO_EN_PROGRESO,
            'observacion' => 'Se reforzó la charla de EPP con el colaborador.',
            'fecha' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $plan->refresh();
        $this->assertSame(PlanAccionOwd::ESTADO_EN_PROGRESO, $plan->estado);
        $this->assertSame(1, $plan->seguimientos()->count());

        $seguimiento = $plan->seguimientos()->firstOrFail();
        $this->assertSame($user->id, $seguimiento->responsable_id);
        $this->assertSame('Se reforzó la charla de EPP con el colaborador.', $seguimiento->observacion);
    }

    public function test_usuario_sin_rol_seguridad_no_puede_acceder(): void
    {
        $role = Role::create(['name' => 'Flota', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->plan();

        $response = $this->actingAs($user)->get(route('seguridad.planes-accion-owd.index'));

        $response->assertForbidden();
    }
}
