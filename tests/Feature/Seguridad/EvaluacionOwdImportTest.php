<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdImportacion;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Models\Seguridad\PlanAccionOwd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluacionOwdImportTest extends TestCase
{
    use RefreshDatabase;

    private const ENCABEZADOS = [
        'BU', 'País', 'Región', 'UEN', 'ID Agencia', 'Agencia', 'Evaluador', 'Posición Evaluador',
        'QR Safety evaluador', 'SHARP evaluador', 'Evaluado', 'Posición', 'QR Safety', 'SHARP',
        'Fecha evaluación', 'Type', 'Pillar', 'Proceso', 'Actividad', 'Tarea', 'Descripción',
        'Puntuación', 'Ponderación (%)', 'Plan de acción', 'Versión',
    ];

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, string>  $valoresPorEncabezado
     * @return array<int, string>
     */
    private function fila(array $valoresPorEncabezado): array
    {
        return array_map(fn (string $encabezado) => $valoresPorEncabezado[$encabezado] ?? '', self::ENCABEZADOS);
    }

    /**
     * @param  array<int, array<int, string>>  $filas
     * @param  array<int, string>|null  $encabezadosExtra
     */
    private function construirExcel(array $filas, string $nombreHoja = 'Data OWD', ?array $encabezadosExtra = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle($nombreHoja);

        $encabezados = self::ENCABEZADOS;
        if ($encabezadosExtra) {
            $encabezados = [...$encabezados, ...$encabezadosExtra];
        }

        $hoja->fromArray($encabezados, null, 'A1');

        $numeroFila = 2;
        foreach ($filas as $datos) {
            $hoja->fromArray($datos, null, 'A'.$numeroFila);
            $numeroFila++;
        }

        $ruta = sys_get_temp_dir().'/owd-import-test-'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($ruta);

        return new UploadedFile($ruta, 'reporte-owd.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_importa_evaluaciones_y_preguntas_relacionando_colaboradores_por_qr(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Luis', 'apellidos' => 'Josa', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADOR1',
        ]);
        Colaborador::create([
            'cedula' => '900333444', 'nombres' => 'Victor', 'apellidos' => 'Martinez', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADO1',
        ]);

        $filaBase = fn (string $tarea, string $puntuacion = 'OK') => $this->fila([
            'BU' => 'Colombia',
            'País' => 'CO',
            'Agencia' => 'DC Narino',
            'Evaluador' => 'Luis Josa',
            'QR Safety evaluador' => 'EVALUADOR1',
            'Evaluado' => 'Victor Martinez',
            'QR Safety' => 'EVALUADO1',
            'Fecha evaluación' => '2026-08-03 14:22:40',
            'Type' => 'Preventiva',
            'Pillar' => 'WAREHOUSE',
            'Proceso' => 'Abastecimiento GLP',
            'Actividad' => 'Abastecimiento GLP',
            'Tarea' => $tarea,
            'Puntuación' => $puntuacion,
            'Ponderación (%)' => '14.29',
            'Plan de acción' => 'NO',
            'Versión' => '4',
        ]);

        $filaSinQr = $this->fila([
            'QR Safety evaluador' => 'EVALUADOR1',
            'Evaluado' => 'Alguien sin registrar',
            'QR Safety' => 'NOEXISTE',
            'Fecha evaluación' => '2026-08-03 14:22:40',
            'Tarea' => 'Tarea de alguien sin colaborador',
            'Puntuación' => 'OK',
            'Plan de acción' => 'NO',
            'Versión' => '4',
        ]);

        $archivo = $this->construirExcel([$filaBase('Uso de EPP'), $filaBase('Orden y aseo'), $filaSinQr]);

        $response = $this->actingAs($user)->post(route('seguridad.evaluaciones-owd.importar'), ['archivos' => [$archivo]]);

        $response->assertRedirect(route('seguridad.evaluaciones-owd.index'));
        $response->assertSessionHas('status', function ($status) {
            return $status['type'] === 'warning'
                && str_contains($status['message'], '2 evaluaciones')
                && str_contains($status['message'], '3 preguntas nuevas')
                && str_contains($status['message'], '1 sin coincidencia de QR');
        });

        $this->assertSame(2, EvaluacionOwd::count());
        $this->assertSame(3, EvaluacionOwdPregunta::count());

        $evaluacion = EvaluacionOwd::where('qr_safety', 'EVALUADO1')->firstOrFail();
        $this->assertNotNull($evaluacion->colaborador_id);
        $this->assertSame('Victor', $evaluacion->colaborador->nombres);
        $this->assertNotNull($evaluacion->evaluador_colaborador_id);
        $this->assertSame(2, $evaluacion->preguntas()->count());
        $this->assertSame(2, $evaluacion->total_preguntas);
        $this->assertSame(2, $evaluacion->preguntas_ok);

        $evaluacionSinColaborador = EvaluacionOwd::where('qr_safety', 'NOEXISTE')->firstOrFail();
        $this->assertNull($evaluacionSinColaborador->colaborador_id);
    }

    public function test_filas_duplicadas_no_se_reinsertan_dentro_del_mismo_archivo_ni_al_reimportar(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900333444', 'nombres' => 'Victor', 'apellidos' => 'Martinez', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADO1',
        ]);

        $fila = $this->fila([
            'QR Safety evaluador' => 'EVALUADOR1',
            'Evaluado' => 'Victor Martinez',
            'QR Safety' => 'EVALUADO1',
            'Fecha evaluación' => '2026-08-03 14:22:40',
            'Proceso' => 'Abastecimiento GLP',
            'Actividad' => 'Abastecimiento GLP',
            'Tarea' => 'Uso de EPP',
            'Puntuación' => 'OK',
            'Plan de acción' => 'NO',
            'Versión' => '4',
        ]);

        // La misma fila repetida dos veces en el mismo archivo: solo debe
        // quedar una pregunta nueva, la otra se cuenta como duplicada.
        $archivo = $this->construirExcel([$fila, $fila]);

        $this->actingAs($user)->post(route('seguridad.evaluaciones-owd.importar'), ['archivos' => [$archivo]]);

        $this->assertSame(1, EvaluacionOwdPregunta::count());

        // Reimportar el mismo archivo no debe duplicar tampoco.
        $response = $this->actingAs($user)
            ->post(route('seguridad.evaluaciones-owd.importar'), ['archivos' => [$this->construirExcel([$fila, $fila])]]);

        $response->assertSessionHas('status', function ($status) {
            return str_contains($status['message'], '0 preguntas nuevas') && str_contains($status['message'], '2 duplicadas');
        });

        $this->assertSame(1, EvaluacionOwdPregunta::count());
    }

    public function test_columna_desconocida_cae_en_datos_adicionales_y_se_registra_en_el_historial(): void
    {
        $user = $this->seguridadUser();

        $fila = [
            ...$this->fila([
                'QR Safety evaluador' => 'EVALUADOR1',
                'Evaluado' => 'Victor Martinez',
                'QR Safety' => 'EVALUADO1',
                'Fecha evaluación' => '2026-08-03 14:22:40',
                'Tarea' => 'Uso de EPP',
                'Puntuación' => 'OK',
                'Plan de acción' => 'NO',
                'Versión' => '4',
            ]),
            'Valor de columna nueva',
        ];

        $archivo = $this->construirExcel([$fila], nombreHoja: '  data owd  ', encabezadosExtra: ['Columna Futura']);

        $this->actingAs($user)->post(route('seguridad.evaluaciones-owd.importar'), ['archivos' => [$archivo]]);

        $pregunta = EvaluacionOwdPregunta::firstOrFail();
        $this->assertSame(['Columna Futura' => 'Valor de columna nueva'], $pregunta->datos_adicionales);

        $importacion = EvaluacionOwdImportacion::firstOrFail();
        $this->assertSame(['Columna Futura'], $importacion->columnas_nuevas_detectadas);
        $this->assertSame(1, $importacion->registros_nuevos);
    }

    public function test_plan_de_accion_si_crea_un_plan_de_accion_pendiente(): void
    {
        $user = $this->seguridadUser();

        $fila = $this->fila([
            'QR Safety evaluador' => 'EVALUADOR1',
            'Evaluado' => 'Victor Martinez',
            'QR Safety' => 'EVALUADO1',
            'Fecha evaluación' => '2026-08-03 14:22:40',
            'Tarea' => 'Uso de EPP',
            'Puntuación' => 'No OK',
            'Plan de acción' => 'SI',
            'Versión' => '4',
        ]);

        $this->actingAs($user)->post(route('seguridad.evaluaciones-owd.importar'), ['archivos' => [$this->construirExcel([$fila])]]);

        $pregunta = EvaluacionOwdPregunta::firstOrFail();
        $this->assertTrue($pregunta->requiere_plan_accion);

        $plan = PlanAccionOwd::where('evaluacion_owd_pregunta_id', $pregunta->id)->firstOrFail();
        $this->assertSame(PlanAccionOwd::ESTADO_PENDIENTE, $plan->estado);
    }
}
