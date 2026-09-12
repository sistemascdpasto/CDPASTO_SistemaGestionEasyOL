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
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluacionMedicaTest extends TestCase
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

    private function colaborador(string $cargo = 'CONDUCTOR'): Colaborador
    {
        return Colaborador::create([
            'cedula' => '900'.random_int(100000, 999999),
            'nombres' => 'Test', 'apellidos' => 'Colaborador',
            'estado_registro' => 'completo', 'cargo' => $cargo,
        ]);
    }

    private function examen(string $nombre): Examen
    {
        return Examen::create(['nombre' => $nombre, 'activo' => true]);
    }

    public function test_crear_evaluacion_genera_los_examenes_de_la_matriz_segun_cargo_y_tipo(): void
    {
        $colaborador = $this->colaborador('CONDUCTOR');
        $examenGeneral = $this->examen('Examen médico ocupacional');
        $examenVisual = $this->examen('Optometría');
        $examenOtroTipo = $this->examen('Examen solo para periódico');

        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examenGeneral->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examenVisual->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => false, 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examenOtroTipo->id, 'tipo_evaluacion' => 'periodico', 'obligatorio' => true, 'activo' => true]);

        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $this->assertSame(2, $evaluacion->examenes()->count());
        $this->assertTrue($evaluacion->examenes()->where('examen_id', $examenGeneral->id)->where('obligatorio', true)->exists());
        $this->assertTrue($evaluacion->examenes()->where('examen_id', $examenVisual->id)->where('obligatorio', false)->exists());
        $this->assertFalse($evaluacion->examenes()->where('examen_id', $examenOtroTipo->id)->exists());
        $this->assertSame('sin_iniciar', $evaluacion->estado);
    }

    public function test_crear_evaluacion_sin_matriz_definida_no_genera_examenes_ni_falla(): void
    {
        $colaborador = $this->colaborador('CARGO SIN MATRIZ');

        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $this->assertSame(0, $evaluacion->examenes()->count());
        $this->assertSame('sin_iniciar', $evaluacion->estado);
    }

    public function test_flujo_completo_programar_y_ejecutar_examen_via_http(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $examen = $this->examen('Examen médico ocupacional');
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');
        $examenEvaluacion = $evaluacion->examenes()->first();

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.examenes.programar', [$evaluacion, $examenEvaluacion]),
            ['fecha_programacion' => now()->addDays(3)->toDateString()],
        )->assertSessionHasNoErrors();

        $examenEvaluacion->refresh();
        $this->assertSame('programado', $examenEvaluacion->estado);
        $this->assertSame('en_proceso', $evaluacion->fresh()->estado);

        $archivo = UploadedFile::fake()->create('soporte.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.examenes.ejecutar', [$evaluacion, $examenEvaluacion]),
            ['fecha_ejecucion' => now()->toDateString(), 'soporte' => $archivo],
        )->assertSessionHasNoErrors();

        $examenEvaluacion->refresh();
        $this->assertSame('realizado', $examenEvaluacion->estado);
        $this->assertNotNull($examenEvaluacion->soporte_path);
        $this->assertSame('terminada', $evaluacion->fresh()->estado);
    }

    public function test_concepto_de_aptitud_se_guarda(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $concepto = ConceptoAptitud::create(['nombre' => 'Satisfactorio', 'activo' => true]);
        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $this->actingAs($user)->patch(
            route('seguridad.examenes-medicos.concepto-aptitud', $evaluacion),
            ['concepto_aptitud_id' => $concepto->id],
        )->assertSessionHasNoErrors();

        $this->assertSame($concepto->id, $evaluacion->fresh()->concepto_aptitud_id);
    }

    public function test_estado_en_proceso_terminada_y_calculo_de_proximo_examen_periodico(): void
    {
        $colaborador = $this->colaborador();
        $examen = $this->examen('Examen médico ocupacional');
        CargoExamen::create(['cargo' => $colaborador->cargo, 'examen_id' => $examen->id, 'tipo_evaluacion' => 'periodico', 'obligatorio' => true, 'activo' => true]);

        $service = new EvaluacionMedicaService();
        $evaluacion = $service->crear($colaborador, 'periodico');
        $examenEvaluacion = $evaluacion->examenes()->first();

        // Sin tocar nada: Sin Iniciar.
        $service->recalcularEstado($evaluacion);
        $this->assertSame('sin_iniciar', $evaluacion->fresh()->estado);

        // Programado (no realizado todavía): En Proceso.
        $examenEvaluacion->update(['estado' => 'programado', 'fecha_programacion' => now()->addDay()]);
        $service->recalcularEstado($evaluacion);
        $this->assertSame('en_proceso', $evaluacion->fresh()->estado);

        // Realizado el 2026-08-01: Terminada, y próximo examen = +12 meses, bandeja = -1 mes.
        $examenEvaluacion->update(['estado' => 'realizado', 'fecha_ejecucion' => '2026-08-01']);
        $service->recalcularEstado($evaluacion);

        $evaluacion->refresh();
        $this->assertSame('terminada', $evaluacion->estado);
        $this->assertSame('2027-08-01', $evaluacion->proximo_examen_fecha->toDateString());
        $this->assertSame('2027-07-02', $evaluacion->fecha_entrada_bandeja->toDateString());
    }

    public function test_evaluacion_periodica_queda_demorada_si_paso_la_fecha_limite_de_la_anterior(): void
    {
        $colaborador = $this->colaborador();
        $examen = $this->examen('Examen médico ocupacional');
        CargoExamen::create(['cargo' => $colaborador->cargo, 'examen_id' => $examen->id, 'tipo_evaluacion' => 'periodico', 'obligatorio' => true, 'activo' => true]);

        // Evaluación anterior ya Terminada, con próximo examen vencido (ayer).
        EvaluacionMedica::create([
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'periodico',
            'fecha_evaluacion' => now()->subYear(),
            'proximo_examen_fecha' => Carbon::yesterday(),
            'estado' => 'terminada',
        ]);

        $service = new EvaluacionMedicaService();
        $nueva = $service->crear($colaborador, 'periodico');
        $service->recalcularEstado($nueva);

        $this->assertSame('demorada', $nueva->fresh()->estado);
    }

    public function test_examen_adicional_no_es_obligatorio_y_no_bloquea_terminar(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $examenObligatorio = $this->examen('Examen médico ocupacional');
        $examenAdicional = $this->examen('Examen extra');
        CargoExamen::create(['cargo' => $colaborador->cargo, 'examen_id' => $examenObligatorio->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.examenes.adicional', $evaluacion),
            ['examen_id' => $examenAdicional->id],
        )->assertSessionHasNoErrors();

        $this->assertSame(2, $evaluacion->examenes()->count());
        $adicional = $evaluacion->examenes()->where('examen_id', $examenAdicional->id)->first();
        $this->assertSame('adicional', $adicional->origen);
        $this->assertFalse($adicional->obligatorio);

        // Solo se completa el obligatorio (el adicional se queda pendiente) y aun así Termina.
        $obligatorio = $evaluacion->examenes()->where('examen_id', $examenObligatorio->id)->first();
        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.examenes.ejecutar', [$evaluacion, $obligatorio]),
            ['fecha_ejecucion' => now()->toDateString()],
        );

        $this->assertSame('terminada', $evaluacion->fresh()->estado);
    }

    public function test_agregar_examen_ya_presente_falla_validacion(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $examen = $this->examen('Examen médico ocupacional');
        CargoExamen::create(['cargo' => $colaborador->cargo, 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $this->actingAs($user)->post(
            route('seguridad.examenes-medicos.examenes.adicional', $evaluacion),
            ['examen_id' => $examen->id],
        )->assertSessionHasErrors('examen_id');

        $this->assertSame(1, $evaluacion->examenes()->count());
    }

    public function test_bandeja_excluye_terminadas_salvo_que_se_pida_el_historial(): void
    {
        $user = $this->seguridadUser();
        // Sin cargo para que el observer de HU-031 no genere un Ingreso
        // automático de más — este test solo verifica el filtro de historial.
        $colaborador = $this->colaborador('');

        EvaluacionMedica::create(['colaborador_id' => $colaborador->id, 'tipo_evaluacion' => 'ingreso', 'fecha_evaluacion' => now(), 'estado' => 'sin_iniciar']);
        EvaluacionMedica::create(['colaborador_id' => $colaborador->id, 'tipo_evaluacion' => 'periodico', 'fecha_evaluacion' => now(), 'estado' => 'terminada']);

        $activas = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index'));
        $activas->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 1));

        $conHistorial = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['ver_historial' => '1']));
        $conHistorial->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 2));
    }

    public function test_crear_evaluacion_desde_la_bandeja_via_http(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $examen = $this->examen('Examen médico ocupacional');
        // La matriz se define DESPUÉS del colaborador: el Ingreso que HU-031
        // generó automáticamente al crearlo no tiene exámenes; el que crea
        // este test vía HTTP sí, porque ya existe la matriz — por eso se
        // busca explícitamente el más reciente (`latest('id')`) más abajo.
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'ingreso',
        ]);

        $response->assertSessionHasNoErrors();
        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('seguridad.examenes-medicos.show', $evaluacion));
        $this->assertSame(1, $evaluacion->examenes()->count());
    }

    public function test_crear_un_colaborador_con_cargo_genera_automaticamente_su_ingreso(): void
    {
        // HU-031: la generación es 100% automática al crear el colaborador,
        // sin acción manual de SST.
        $colaborador = $this->colaborador('CONDUCTOR');

        $ingreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->first();

        $this->assertNotNull($ingreso);
        $this->assertSame('sin_iniciar', $ingreso->estado);
    }

    public function test_asignar_cargo_despues_de_crear_el_colaborador_tambien_genera_el_ingreso(): void
    {
        $colaborador = Colaborador::create([
            'cedula' => '900'.random_int(100000, 999999), 'nombres' => 'Test', 'apellidos' => 'Colaborador', 'estado_registro' => 'completo',
        ]);

        $this->assertSame(0, EvaluacionMedica::where('colaborador_id', $colaborador->id)->count());

        $colaborador->update(['cargo' => 'CONDUCTOR']);

        $this->assertSame(1, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->count());

        // Un segundo cambio de cargo no genera un segundo Ingreso.
        $colaborador->update(['cargo' => 'AUXILIAR DE FLOTA']);
        $this->assertSame(1, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->count());
    }

    public function test_terminar_el_ingreso_genera_automaticamente_el_periodico_1_y_terminarlo_genera_el_2(): void
    {
        $colaborador = $this->colaborador('CONDUCTOR');
        $examen = $this->examen('Examen médico ocupacional');
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'periodico', 'obligatorio' => true, 'activo' => true]);

        $service = new EvaluacionMedicaService();
        $ingreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->firstOrFail();

        // El Ingreso automático se creó antes de que existiera la matriz —
        // se agrega el examen a mano para simular que sí aplicaba.
        $ingreso->examenes()->create(['examen_id' => $examen->id, 'obligatorio' => true, 'origen' => 'matriz', 'estado' => 'realizado', 'fecha_ejecucion' => '2026-08-01']);
        $service->recalcularEstado($ingreso);

        $periodico1 = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'periodico')->where('numero_periodo', 1)->first();
        $this->assertNotNull($periodico1);
        $this->assertSame('2027-08-01', $periodico1->fecha_limite->toDateString());
        $this->assertSame(1, $periodico1->examenes()->count());

        // Recalcular otra vez (p. ej. tras editar el concepto de aptitud) no duplica el periódico.
        $service->recalcularEstado($ingreso);
        $this->assertSame(1, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'periodico')->count());

        // Terminar el periódico 1 genera el periódico 2.
        $periodico1->examenes()->first()->update(['estado' => 'realizado', 'fecha_ejecucion' => '2027-08-01']);
        $service->recalcularEstado($periodico1);

        $periodico2 = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'periodico')->where('numero_periodo', 2)->first();
        $this->assertNotNull($periodico2);
        $this->assertSame('2028-08-01', $periodico2->fecha_limite->toDateString());
    }

    public function test_bandeja_filtra_por_colaborador_identificacion_cargo_y_centro(): void
    {
        $user = $this->seguridadUser();

        $buscado = Colaborador::create([
            'cedula' => '9001112223', 'nombres' => 'Marta', 'apellidos' => 'Rios',
            'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR', 'centro' => 'UC',
        ]);
        $otro = Colaborador::create([
            'cedula' => '9009998887', 'nombres' => 'Pedro', 'apellidos' => 'Diaz',
            'estado_registro' => 'completo', 'cargo' => 'AUXILIAR DE FLOTA', 'centro' => 'UD',
        ]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['colaborador' => 'Marta']));
        $response->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 1
            && $data[0]['colaborador']['id'] === $buscado->id));

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['identificacion' => '9009998887']));
        $response->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 1
            && $data[0]['colaborador']['id'] === $otro->id));

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['cargo' => 'CONDUCTOR']));
        $response->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 1
            && $data[0]['colaborador']['id'] === $buscado->id));

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['centro' => 'UD']));
        $response->assertInertia(fn ($page) => $page->where('evaluaciones.data', fn ($data) => count($data) === 1
            && $data[0]['colaborador']['id'] === $otro->id));
    }

    public function test_bandeja_calcula_dias_restantes_y_alerta_de_vencimiento(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');

        $evaluacion = EvaluacionMedica::create([
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'periodico',
            'numero_periodo' => 1,
            'fecha_evaluacion' => now(),
            'fecha_limite' => now()->addDays(10)->toDateString(),
            'estado' => 'sin_iniciar',
        ]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['tipo_evaluacion' => 'periodico']));

        $response->assertInertia(function ($page) use ($evaluacion) {
            $fila = collect($page->toArray()['props']['evaluaciones']['data'])->firstWhere('id', $evaluacion->id);
            \PHPUnit\Framework\Assert::assertSame(10, $fila['dias_restantes']);
            \PHPUnit\Framework\Assert::assertSame('proximo_a_vencer', $fila['alerta']);

            return true;
        });

        $evaluacion->update(['fecha_limite' => now()->subDays(5)->toDateString()]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.index', ['tipo_evaluacion' => 'periodico']));
        $response->assertInertia(function ($page) use ($evaluacion) {
            $fila = collect($page->toArray()['props']['evaluaciones']['data'])->firstWhere('id', $evaluacion->id);
            \PHPUnit\Framework\Assert::assertSame(-5, $fila['dias_restantes']);
            \PHPUnit\Framework\Assert::assertSame('vencido', $fila['alerta']);

            return true;
        });
    }

    public function test_iniciar_evaluacion_genera_examenes_de_la_matriz_sin_duplicar(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $examen = $this->examen('Audiometría');

        // Se crea matriz
        CargoExamen::create([
            'cargo' => 'CONDUCTOR',
            'examen_id' => $examen->id,
            'tipo_evaluacion' => 'ingreso',
            'obligatorio' => true,
            'activo' => true,
        ]);

        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->latest('id')->firstOrFail();
        // Eliminar exámenes si los tuviera para probar el botón iniciar
        $evaluacion->examenes()->delete();
        $evaluacion->update(['estado' => 'sin_iniciar']);
        $this->assertSame(0, $evaluacion->examenes()->count());

        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.iniciar', $evaluacion));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSame(1, $evaluacion->fresh()->examenes()->count());
    }

    public function test_regla_ciclos_ingreso_no_duplica_si_no_hay_egreso(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');

        $primerIngreso = EvaluacionMedica::where('colaborador_id', $colaborador->id)
            ->where('tipo_evaluacion', 'ingreso')
            ->firstOrFail();

        // Intento de crear otro ingreso sin egreso previo
        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'ingreso',
        ]);

        // Debe redirigir al ingreso existente y no crear uno nuevo
        $response->assertRedirect(route('seguridad.examenes-medicos.show', $primerIngreso->id));
        $this->assertSame(1, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->count());

        // Ahora creamos un Egreso para cerrar el ciclo
        $service = app(EvaluacionMedicaService::class);
        $service->crearEgreso($colaborador);

        // Nuevo intento de crear Ingreso: ahora sí debe permitirlo porque el ciclo anterior cerró
        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'ingreso',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->count());
    }

    public function test_recomendaciones_activas_se_heredan_en_nueva_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $evaluacion1 = EvaluacionMedica::where('colaborador_id', $colaborador->id)->firstOrFail();

        $rec = \App\Models\Seguridad\Recomendacion::create([
            'nombre' => 'Uso obligatorio de EPP auditivo',
            'categoria' => 'elementos_proteccion',
            'activo' => true,
        ]);

        $evaluacion1->recomendaciones()->create([
            'recomendacion_id' => $rec->id,
            'observacion' => 'Control auditivo',
            'activa' => true,
            'origen' => 'manual',
            'fecha_registro' => now()->toDateString(),
        ]);

        $service = app(EvaluacionMedicaService::class);
        $periodico = $service->crear($colaborador, 'periodico');

        $this->assertTrue($periodico->recomendaciones()->where('recomendacion_id', $rec->id)->exists());
        $this->assertSame('heredada', $periodico->recomendaciones()->first()->origen);
    }

    public function test_historial_medico_asigna_ciclos_correctamente(): void
    {
        $colaborador = $this->colaborador('CONDUCTOR');
        $service = app(EvaluacionMedicaService::class);

        // Ciclo 1: Ingreso (creado por observer)
        $ingreso1 = EvaluacionMedica::where('colaborador_id', $colaborador->id)->where('tipo_evaluacion', 'ingreso')->firstOrFail();
        $ingreso1->update(['estado' => 'terminada', 'proximo_examen_fecha' => now()->addYear()]);

        // Ciclo 1: Periódico 1
        $periodico1 = $service->crear($colaborador, 'periodico');

        // Ciclo 1: Egreso
        $egreso1 = $service->crearEgreso($colaborador);

        // Ciclo 2: Nuevo Ingreso
        $ingreso2 = $service->crear($colaborador, 'ingreso');

        $historial = $service->obtenerHistorialConCiclos($colaborador->id);

        $this->assertCount(4, $historial);
        $this->assertSame(2, $historial->firstWhere('id', $ingreso2->id)->ciclo_numero);
        $this->assertSame(1, $historial->firstWhere('id', $egreso1->id)->ciclo_numero);
        $this->assertSame(1, $historial->firstWhere('id', $periodico1->id)->ciclo_numero);
        $this->assertSame(1, $historial->firstWhere('id', $ingreso1->id)->ciclo_numero);
    }

    public function test_fechas_de_evaluacion_se_serializan_en_formato_ano_mes_dia(): void
    {
        $colaborador = $this->colaborador('CONDUCTOR');
        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->firstOrFail();

        $jsonArray = $evaluacion->toArray();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $jsonArray['fecha_evaluacion']);
    }

    public function test_programar_todos_los_examenes_de_una_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->firstOrFail();
        $examen = $this->examen('Optometría');

        $evaluacion->examenes()->create([
            'examen_id' => $examen->id,
            'obligatorio' => true,
            'origen' => 'matriz',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($user)->patch(route('seguridad.examenes-medicos.examenes.programar-todos', $evaluacion->id), [
            'fecha_programacion' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $evaluacion->refresh();
        $this->assertTrue($evaluacion->examenes->every(fn ($e) => $e->estado === 'programado'));
    }

    public function test_ejecutar_todos_los_examenes_de_una_evaluacion(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador('CONDUCTOR');
        $evaluacion = EvaluacionMedica::where('colaborador_id', $colaborador->id)->firstOrFail();
        $examen = $this->examen('Audiometría');

        $evaluacion->examenes()->create([
            'examen_id' => $examen->id,
            'obligatorio' => true,
            'origen' => 'matriz',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($user)->post(route('seguridad.examenes-medicos.examenes.ejecutar-todos', $evaluacion->id), [
            'fecha_ejecucion' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $evaluacion->refresh();
        $this->assertTrue($evaluacion->examenes->every(fn ($e) => $e->estado === 'realizado'));
    }

    public function test_periodico_siguiente_calcula_proximo_objetivo_desde_fecha_real_de_ejecucion(): void
    {
        $service = new EvaluacionMedicaService();
        $colaborador = $this->colaborador('CONDUCTOR');
        $examen = $this->examen('Optometría');

        // 1. Periódico #1 con fecha objetivo 2026-07-30
        $periodico1 = $service->crear($colaborador, 'periodico', Carbon::parse('2026-07-30'), Carbon::parse('2026-06-30'));
        $periodico1Examen = $periodico1->examenes()->create([
            'examen_id' => $examen->id,
            'obligatorio' => true,
            'origen' => 'matriz',
            'estado' => 'pendiente',
        ]);

        // Se ejecuta en FECHA REAL: 2026-08-15
        $periodico1Examen->update([
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-08-15',
        ]);
        $service->recalcularEstado($periodico1);
        $periodico1->refresh();

        // Próximo objetivo de Periódico #1: 2026-08-15 + 1 año = 2027-08-15
        $this->assertSame('terminada', $periodico1->estado);
        $this->assertSame('2027-08-15', $periodico1->proximo_examen_fecha->toDateString());

        // Periódico #2 se generó con objetivo 2027-08-15 y entrada a bandeja 30 días antes (2027-07-16)
        $periodico2 = EvaluacionMedica::where('colaborador_id', $colaborador->id)
            ->where('tipo_evaluacion', 'periodico')
            ->where('numero_periodo', 2)
            ->firstOrFail();

        $this->assertSame('2027-08-15', $periodico2->fecha_limite->toDateString());
        $this->assertSame('2027-07-16', $periodico2->fecha_entrada_bandeja->toDateString());

        // 2. Periódico #2 se ejecuta en FECHA REAL: 2027-08-20
        $periodico2Examen = $periodico2->examenes()->create([
            'examen_id' => $examen->id,
            'obligatorio' => true,
            'origen' => 'matriz',
            'estado' => 'pendiente',
        ]);
        $periodico2Examen->update([
            'estado' => 'realizado',
            'fecha_ejecucion' => '2027-08-20',
        ]);
        $service->recalcularEstado($periodico2);
        $periodico2->refresh();

        // Próximo objetivo de Periódico #2: 2027-08-20 + 1 año = 2028-08-20
        $this->assertSame('terminada', $periodico2->estado);
        $this->assertSame('2028-08-20', $periodico2->proximo_examen_fecha->toDateString());

        // Periódico #3 generado con objetivo 2028-08-20 y entrada a bandeja 30 días antes (2028-07-21)
        $periodico3 = EvaluacionMedica::where('colaborador_id', $colaborador->id)
            ->where('tipo_evaluacion', 'periodico')
            ->where('numero_periodo', 3)
            ->firstOrFail();

        $this->assertSame('2028-08-20', $periodico3->fecha_limite->toDateString());
        $this->assertSame('2028-07-21', $periodico3->fecha_entrada_bandeja->toDateString());
    }
}

