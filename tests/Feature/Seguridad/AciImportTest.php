<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AciImportTest extends TestCase
{
    use RefreshDatabase;

    private const ENCABEZADOS = [
        'Folio', 'Fecha que se Alzó', 'Hora que se Alzó', 'Fecha del Incidente', 'Hora del Incidente', 'Ubicación',
        'Dentro o fuera de ubicación', 'Zona', 'Subzona', 'Reporte de', 'Persona que lo cometió', 'Reportado por',
        'QR de quien reportó', 'Número de empleado que reportó', 'PIN del que reportó', 'Descripción', 'Posible solución',
        'Área', 'Subarea', 'Descripción de la ubicación', 'Clasificación', 'Tipo de riesgo', 'Descripción del tipo de riesgo',
        'Ciudad elegida', 'Estado elegido', 'Nombre del punto de venta', 'Descripción del punto de venta', 'Tipo de acto en ruta',
        'Fue por externo', 'SIF', 'Estatus de asignación', 'Fecha y hora de asignación', 'Asignado por', 'QR del Asignador',
        'Asignado a', 'QR del Asignado', 'Cerrado por', 'Fecha y hora de cierre', 'BU', 'Persona que lo cometió PIN',
        'Persona que lo cometió número de empleado', 'Fecha pospuesto', 'Compañia',
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
     * Convierte una fila expresada como "encabezado => valor" (solo hay que
     * indicar los campos que interesan al test) en el arreglo posicional
     * completo de 43 columnas que espera el Excel real, en el mismo orden
     * de {@see self::ENCABEZADOS}. Evita tener que contar comas a mano.
     *
     * @param  array<string, string>  $valoresPorEncabezado
     * @return array<int, string>
     */
    private function fila(array $valoresPorEncabezado): array
    {
        return array_map(fn (string $encabezado) => $valoresPorEncabezado[$encabezado] ?? '', self::ENCABEZADOS);
    }

    /**
     * @param  array<int, array<int, string>>  $filas
     * @param  array<int, string>|null  $encabezadosExtra  encabezados adicionales a los 43 conocidos, para probar datos_adicionales.
     */
    private function construirExcel(array $filas, string $nombreHoja = 'SIOs', ?array $encabezadosExtra = null): UploadedFile
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

        $ruta = sys_get_temp_dir().'/acis-import-test-'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($ruta);

        return new UploadedFile($ruta, 'acis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_importa_reportes_relaciona_colaborador_por_qr_y_normaliza_catalogos(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Luis', 'apellidos' => 'Josa', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345',
        ]);
        Colaborador::create([
            'cedula' => '900333444', 'nombres' => 'Daniela', 'apellidos' => 'Pantoja', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'XYZ98765',
        ]);

        $filaNormal = $this->fila([
            'Folio' => 'F001',
            'Fecha que se Alzó' => '01/08/2026',
            'Hora que se Alzó' => '01:55',
            'Fecha del Incidente' => '01/08/2026',
            'Hora del Incidente' => '01:53',
            'Ubicación' => 'DC Narino',
            'Dentro o fuera de ubicación' => 'Fuera de agencia',
            'Zona' => 'Sur',
            'Subzona' => 'Sur',
            'Reporte de' => 'Acto Inseguro',
            'Persona que lo cometió' => 'Mauricio Masinsoy',
            'Reportado por' => 'Luis Josa Botina',
            'QR de quien reportó' => 'ABC12345',
            'Descripción' => 'Exceso de velocidad',
            'Posible solución' => 'Retroalimentación',
            'Área' => 'Ruta',
            'Clasificación' => 'Riesgo Vial',
            'Tipo de riesgo' => 'Falta de precaución',
            'Fue por externo' => 'No',
            'SIF' => 'Sí',
            'Estatus de asignación' => 'Cerrado',
            'Fecha y hora de asignación' => '01/08/2026 06:19',
            'Asignado por' => 'Daniela Pantoja',
            'QR del Asignador' => 'XYZ98765',
            'Asignado a' => 'Daniela Pantoja',
            'QR del Asignado' => 'XYZ98765',
            'Cerrado por' => 'Daniela Pantoja',
            'Fecha y hora de cierre' => '01/08/2026 06:19',
            'BU' => 'Colombia',
            'Compañia' => 'Easy Logística S.A.S',
        ]);

        $filaSinColaborador = $this->fila([
            'Folio' => 'F002',
            'Fecha del Incidente' => '02/08/2026',
            'Ubicación' => 'DC Narino',
            'Dentro o fuera de ubicación' => 'Dentro de agencia',
            'Zona' => 'Sur',
            'Reporte de' => 'Condición Inseguro',
            'Reportado por' => 'Alguien Mas',
            'QR de quien reportó' => 'NOEXISTE1',
            'Área' => 'Patios',
            'Clasificación' => 'Riesgo Locativo',
            'Fue por externo' => 'No',
            'SIF' => 'No',
            'Estatus de asignación' => 'Pendiente',
            'BU' => 'Colombia',
            'Compañia' => 'ADENAR',
        ]);

        $archivo = $this->construirExcel([$filaNormal, $filaSinColaborador]);

        $response = $this->actingAs($user)->post(route('seguridad.acis.importar'), ['archivos' => [$archivo]]);

        $response->assertRedirect(route('seguridad.acis.index'));
        $response->assertSessionHas('status', function ($status) {
            return $status['type'] === 'warning'
                && str_contains($status['message'], '1 creados')
                && str_contains($status['message'], '1 omitidos por QR sin colaborador');
        });

        $this->assertSame(1, Aci::count());

        $aci = Aci::where('folio', 'F001')->firstOrFail();
        $this->assertSame('2026-08-01', $aci->fecha_alzado->toDateString());
        $this->assertSame('2026-08-01', $aci->fecha_incidente->toDateString());
        $this->assertSame('Acto Inseguro', $aci->reporte_de);
        $this->assertSame('Ruta', $aci->area);
        $this->assertSame('Riesgo Vial', $aci->clasificacion);
        $this->assertFalse($aci->fue_por_externo);
        $this->assertTrue($aci->sif);
        $this->assertSame('Cerrado', $aci->estatus_asignacion);
        $this->assertSame('2026-08-01 06:19:00', $aci->fecha_hora_asignacion->toDateTimeString());
        $this->assertSame('EASY LOGÍSTICA', $aci->compania);
        $this->assertNotNull($aci->colaborador_id);
        $this->assertSame('Luis', $aci->colaborador->nombres);
        $this->assertNotNull($aci->asignador_colaborador_id);
        $this->assertNotNull($aci->asignado_colaborador_id);

        $this->assertNull(Aci::where('folio', 'F002')->first());
    }

    public function test_folio_existente_se_actualiza_con_los_datos_del_nuevo_archivo(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Luis', 'apellidos' => 'Josa', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345',
        ]);

        $filaInicial = $this->fila([
            'Folio' => 'F100',
            'Fecha del Incidente' => '01/08/2026',
            'QR de quien reportó' => 'ABC12345',
            'Área' => 'Ruta',
            'Estatus de asignación' => 'Pendiente',
        ]);

        $this->actingAs($user)->post(route('seguridad.acis.importar'), ['archivos' => [$this->construirExcel([$filaInicial])]]);
        $this->assertSame(1, Aci::count());
        $this->assertSame('Pendiente', Aci::where('folio', 'F100')->firstOrFail()->estatus_asignacion);

        // Re-subir el mismo Folio con el estatus ya cerrado y fecha de cierre —
        // simula que el reporte avanzó de estado desde la primera carga.
        $filaActualizada = $this->fila([
            'Folio' => 'F100',
            'Fecha del Incidente' => '01/08/2026',
            'QR de quien reportó' => 'ABC12345',
            'Área' => 'Ruta',
            'Estatus de asignación' => 'Cerrado',
            'Cerrado por' => 'Luis Josa',
            'Fecha y hora de cierre' => '05/08/2026 10:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('seguridad.acis.importar'), ['archivos' => [$this->construirExcel([$filaActualizada])]]);

        $response->assertSessionHas('status', function ($status) {
            return $status['type'] === 'success'
                && str_contains($status['message'], '0 creados')
                && str_contains($status['message'], '1 actualizados');
        });

        $this->assertSame(1, Aci::count());
        $aci = Aci::where('folio', 'F100')->firstOrFail();
        $this->assertSame('Cerrado', $aci->estatus_asignacion);
        $this->assertSame('Luis Josa', $aci->cerrado_por);
        $this->assertSame('2026-08-05 10:00:00', $aci->fecha_hora_cierre->toDateTimeString());
    }

    public function test_columna_desconocida_cae_en_datos_adicionales_y_encuentra_la_hoja_tolerando_mayusculas(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Luis', 'apellidos' => 'Josa', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345',
        ]);

        $fila = [
            ...$this->fila([
                'Folio' => 'F200',
                'Fecha del Incidente' => '01/08/2026',
                'QR de quien reportó' => 'ABC12345',
                'Área' => 'Ruta',
                'Estatus de asignación' => 'Pendiente',
            ]),
            'Valor de columna nueva',
        ];

        $archivo = $this->construirExcel([$fila], nombreHoja: '  sios  ', encabezadosExtra: ['Columna Futura']);

        $this->actingAs($user)->post(route('seguridad.acis.importar'), ['archivos' => [$archivo]]);

        $aci = Aci::where('folio', 'F200')->firstOrFail();
        $this->assertSame(['Columna Futura' => 'Valor de columna nueva'], $aci->datos_adicionales);
    }

    public function test_importa_varios_archivos_en_una_sola_subida_y_suma_los_contadores(): void
    {
        $user = $this->seguridadUser();

        Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Luis', 'apellidos' => 'Josa', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'ABC12345',
        ]);

        $filaBase = fn (string $folio) => $this->fila([
            'Folio' => $folio,
            'Fecha del Incidente' => '01/08/2026',
            'QR de quien reportó' => 'ABC12345',
            'Área' => 'Ruta',
            'Estatus de asignación' => 'Pendiente',
        ]);

        $archivo1 = $this->construirExcel([$filaBase('M1-1'), $filaBase('M1-2')]);
        $archivo2 = $this->construirExcel([$filaBase('M2-1')]);

        $response = $this->actingAs($user)
            ->post(route('seguridad.acis.importar'), ['archivos' => [$archivo1, $archivo2]]);

        $response->assertSessionHas('status', function ($status) {
            return str_contains($status['message'], '2 archivo(s)') && str_contains($status['message'], '3 creados');
        });

        $this->assertSame(3, Aci::count());
    }
}
