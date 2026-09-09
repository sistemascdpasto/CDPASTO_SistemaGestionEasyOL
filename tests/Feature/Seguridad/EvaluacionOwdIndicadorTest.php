<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluacionOwdIndicadorTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Regresión: el listado de indicadores carga el cumplimiento de cada
     * colaborador con `EvaluacionOwdCumplimientoService::calcular()`, que
     * hace un eager-load restringido de columnas sobre `evaluacionOwd`
     * (`->with('evaluacionOwd:id,colaborador_id,fecha_evaluacion,pillar')`)
     * — si esa lista de columnas incluyera alguna que solo existe en
     * `evaluacion_owd_preguntas` (como pasó con "proceso"), la consulta
     * revienta en MySQL en cuanto hay al menos una evaluación que cargar.
     */
    public function test_indicadores_carga_sin_error_con_evaluaciones_registradas(): void
    {
        $user = $this->seguridadUser();

        $colaborador = Colaborador::create([
            'cedula' => '900999888', 'nombres' => 'Marta', 'apellidos' => 'Lopez', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADO9',
        ]);

        $evaluacion = EvaluacionOwd::create([
            'qr_safety' => 'EVALUADO9',
            'colaborador_id' => $colaborador->id,
            'fecha_evaluacion' => now(),
            'pillar' => 'WAREHOUSE',
        ]);

        EvaluacionOwdPregunta::create([
            'evaluacion_owd_id' => $evaluacion->id,
            'proceso' => 'Abastecimiento',
            'tarea' => 'Uso de EPP',
            'puntuacion' => 'OK',
        ]);

        $this->actingAs($user)->get(route('seguridad.evaluaciones-owd.indicadores'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seguridad/evaluaciones-owd/indicadores')
                ->has('colaboradoresCumplimiento.data', 1)
            );
    }
}
