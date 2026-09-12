<?php

namespace Tests\Feature\Flota;

use App\Models\Flota\Varada;
use App\Models\Flota\VaradaUbicacion;
use App\Models\Flota\Vehiculo;
use App\Models\User;
use App\Services\Flota\VaradaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VaradaTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function varada(array $overrides = []): Varada
    {
        return Varada::create([
            'placa' => 'ABC123',
            'fecha_reportada' => '2026-04-16 07:06:00',
            'fecha_asistencia' => '2026-04-16 08:00:00',
            'fecha_solucion' => '2026-04-16 11:00:00',
            'sistema' => 'FRENOS',
            'ruta' => 'PASTO',
            'repetitiva' => false,
            'origen' => 'manual',
            ...$overrides,
        ]);
    }

    public function test_flota_can_list_registros(): void
    {
        $user = $this->actingAsRole('Flota');
        $this->varada();
        $this->varada(['placa' => 'DEF456', 'fecha_reportada' => '2026-05-01 09:00:00']);

        $response = $this->actingAs($user)->get(route('flota.varadas.index'));

        $response->assertInertia(fn ($page) => $page->has('registros.data', 2));
    }

    public function test_administrador_can_also_access_the_module(): void
    {
        $user = $this->actingAsRole('Administrador');
        $this->varada();

        $this->actingAs($user)->get(route('flota.varadas.index'))->assertOk();
    }

    public function test_other_roles_cannot_access_the_module(): void
    {
        $user = $this->actingAsRole('Seguridad');

        $this->actingAs($user)->get(route('flota.varadas.index'))->assertForbidden();
    }

    public function test_tfs_and_gt_are_computed_from_the_three_dates_not_stored(): void
    {
        // Reportada 07:06, Asistencia 08:00, Solucion 11:00 -> TFS=234min (3.9h), GT=180min (3h).
        $varada = $this->varada();

        $this->assertEquals(234, $varada->tfs_minutos);
        $this->assertEquals(3.9, $varada->tfs_horas);
        $this->assertEquals(3.0, $varada->gt_horas);
        $this->assertFalse($varada->esta_abierta);
    }

    public function test_a_varada_without_fecha_solucion_is_open_with_null_metrics(): void
    {
        $varada = $this->varada(['fecha_solucion' => null]);

        $this->assertNull($varada->tfs_horas);
        $this->assertNull($varada->dias_fs);
        $this->assertNull($varada->gt_horas);
        $this->assertTrue($varada->esta_abierta);
    }

    public function test_flota_can_register_a_varada_manually(): void
    {
        $user = $this->actingAsRole('Flota');

        $response = $this->actingAs($user)->post(route('flota.varadas.store'), [
            'placa' => 'XYZ789',
            'fecha_reportada' => '2026-06-01 10:00:00',
            'sistema' => 'MOTOR',
            'repetitiva' => true,
        ]);

        $response->assertRedirect(route('flota.varadas.index'));
        $this->assertDatabaseHas('flota_varadas', [
            'placa' => 'XYZ789',
            'sistema' => 'MOTOR',
            'origen' => 'manual',
            'created_by' => $user->id,
        ]);
    }

    public function test_flota_can_update_and_delete_a_varada(): void
    {
        $user = $this->actingAsRole('Flota');
        $varada = $this->varada();

        $this->actingAs($user)->put(route('flota.varadas.update', $varada), [
            'placa' => $varada->placa,
            'fecha_reportada' => $varada->fecha_reportada->toDateTimeString(),
            'sistema' => 'LLANTAS',
        ])->assertRedirect(route('flota.varadas.index'));

        $this->assertSame('LLANTAS', $varada->fresh()->sistema);

        $this->actingAs($user)->delete(route('flota.varadas.destroy', $varada))
            ->assertRedirect(route('flota.varadas.index'));
        $this->assertDatabaseMissing('flota_varadas', ['id' => $varada->id]);
    }

    public function test_indicadores_group_dias_fs_by_placa_and_sistema(): void
    {
        $user = $this->actingAsRole('Flota');
        $this->varada(['placa' => 'ABC123', 'sistema' => 'FRENOS']); // 0.1625 dias
        $this->varada([
            'placa' => 'ABC123', 'sistema' => 'FRENOS',
            'fecha_reportada' => '2026-04-17 07:06:00', 'fecha_asistencia' => '2026-04-17 08:00:00', 'fecha_solucion' => '2026-04-17 11:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('flota.varadas.index'));

        $response->assertInertia(function ($page) {
            $indicadores = $page->toArray()['props']['indicadores'];
            $porPlaca = collect($indicadores['dias_fs_por_placa'])->keyBy('placa');
            $this->assertEquals(0.32, $porPlaca['ABC123']['total']);

            $porSistema = collect($indicadores['dias_fs_por_sistema'])->keyBy('sistema');
            $this->assertEquals(0.32, $porSistema['FRENOS']['total']);

            $this->assertSame(2, $indicadores['resumen']['total']);
            $this->assertSame(0, $indicadores['resumen']['abiertas']);
        });
    }

    public function test_catalogo_de_placas_solo_trae_vehiculos_activos_de_flota(): void
    {
        $user = $this->actingAsRole('Flota');
        Vehiculo::create(['placa' => 'ABC123', 'is_active' => true]);
        Vehiculo::create(['placa' => 'ZZZ999', 'is_active' => true]);
        Vehiculo::create(['placa' => 'OLD001', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('flota.varadas.index'));

        $response->assertInertia(function ($page) {
            $placas = $page->toArray()['props']['catalogos']['placas_flota'];

            $this->assertSame(['ABC123', 'ZZZ999'], array_values($placas));
        });
    }

    public function test_it_filters_by_anio_mes_placa_and_sistema(): void
    {
        $user = $this->actingAsRole('Flota');
        $this->varada(['placa' => 'ABC123', 'sistema' => 'FRENOS', 'fecha_reportada' => '2026-04-16 07:06:00']);
        $this->varada(['placa' => 'DEF456', 'sistema' => 'MOTOR', 'fecha_reportada' => '2026-05-16 07:06:00']);

        $response = $this->actingAs($user)->get(route('flota.varadas.index', ['anio' => 2026, 'mes' => 4]));
        $response->assertInertia(fn ($page) => $page->has('registros.data', 1));

        $response = $this->actingAs($user)->get(route('flota.varadas.index', ['placa' => 'DEF456']));
        $response->assertInertia(fn ($page) => $page->has('registros.data', 1));

        $response = $this->actingAs($user)->get(route('flota.varadas.index', ['sistema' => 'FRENOS']));
        $response->assertInertia(fn ($page) => $page->has('registros.data', 1));
    }

    public function test_it_filters_by_fecha_desde_and_fecha_hasta(): void
    {
        $user = $this->actingAsRole('Flota');
        $this->varada(['placa' => 'ABC123', 'fecha_reportada' => '2026-04-16 07:06:00']);
        $this->varada(['placa' => 'DEF456', 'fecha_reportada' => '2026-05-16 07:06:00']);

        $response = $this->actingAs($user)->get(route('flota.varadas.index', [
            'fecha_desde' => '2026-04-01',
            'fecha_hasta' => '2026-04-30',
        ]));

        $response->assertInertia(function ($page) {
            $page->has('registros.data', 1);
            $this->assertSame('ABC123', $page->toArray()['props']['registros']['data'][0]['placa']);
        });
    }

    /**
     * Escribe una fecha como celda de tipo fecha real de Excel (serial +
     * formato de número), igual que el archivo de origen real, para
     * ejercitar la misma ruta de código que ExcelDate::isDateTime() usa en
     * producción (no el fallback de parseo de texto).
     */
    private function celdaFecha($hoja, string $coordenada, string $fechaHora): void
    {
        $hoja->setCellValue($coordenada, Date::PHPToExcel(new \DateTime($fechaHora)));
        $hoja->getStyle($coordenada)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
    }

    private function crearExcelDePrueba(): string
    {
        $spreadsheet = new Spreadsheet;

        $varadas = $spreadsheet->getActiveSheet();
        $varadas->setTitle('Varadas');
        $varadas->fromArray([
            ['Fecha y Hora Reportada', 'Placa', 'Fecha y Hora Asistencia', 'Fecha y Hora Solucion', 'SISTEMA', 'Tipo Falla', 'Repetitiva (Sí/No)', 'Ruta', 'Gravedad'],
            [null, 'TRG442', null, null, 'FRENOS', 'Falla frenos', 'SI', 'PASTO', 3],
            [null, 'UYW793', null, null, 'TRASMISION', 'Caja bloqueada', 'NO', 'SANDONA', 3],
        ], null, 'A1');
        $this->celdaFecha($varadas, 'A2', '2026-04-16 07:06:00');
        $this->celdaFecha($varadas, 'C2', '2026-04-16 08:00:00');
        $this->celdaFecha($varadas, 'D2', '2026-04-16 11:00:00');
        $this->celdaFecha($varadas, 'A3', '2026-04-20 11:00:00');
        $this->celdaFecha($varadas, 'C3', '2026-04-20 12:00:00');
        $this->celdaFecha($varadas, 'D3', '2026-04-22 08:00:00');

        $coordenadas = $spreadsheet->createSheet();
        $coordenadas->setTitle('Coordenadas');
        $coordenadas->fromArray([
            [null, null, 'Lugar', 'Latitud', 'Longitud'],
            [null, null, 'Pasto', 12136, -772811],
            [null, null, 'Sandoná', '0.8250', -774720],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'varadas_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_import_service_parses_dates_coordinates_and_dedupes_on_reimport(): void
    {
        $path = $this->crearExcelDePrueba();
        $service = new VaradaImportService;

        $resultado = $service->importar([$path => 'prueba.xlsx']);

        $this->assertSame(2, $resultado['varadas_creadas']);
        $this->assertSame(0, $resultado['varadas_actualizadas']);
        $this->assertSame(2, $resultado['ubicaciones_cargadas']);
        $this->assertSame(0, $resultado['errores']);

        $this->assertDatabaseCount('flota_varadas', 2);

        $pasto = VaradaUbicacion::where('lugar', 'Pasto')->first();
        $this->assertEqualsWithDelta(1.2136, (float) $pasto->latitud, 0.0001);
        $this->assertEqualsWithDelta(-77.2811, (float) $pasto->longitud, 0.0001);

        $varadaTrg442 = Varada::where('placa', 'TRG442')->first();
        $this->assertEqualsWithDelta(1.2136, (float) $varadaTrg442->latitud, 0.0001);
        $this->assertEqualsWithDelta(-77.2811, (float) $varadaTrg442->longitud, 0.0001);
        $this->assertSame('excel', $varadaTrg442->origen);
        $this->assertTrue($varadaTrg442->repetitiva);
        $this->assertEquals(3.9, $varadaTrg442->tfs_horas);

        // Re-importar el mismo archivo no debe duplicar filas, sino actualizar.
        $resultado2 = $service->importar([$path => 'prueba.xlsx']);
        $this->assertSame(0, $resultado2['varadas_creadas']);
        $this->assertSame(2, $resultado2['varadas_actualizadas']);
        $this->assertDatabaseCount('flota_varadas', 2);

        unlink($path);
    }

    public function test_import_endpoint_requires_flota_or_administrador_role(): void
    {
        $user = $this->actingAsRole('Flota');
        $path = $this->crearExcelDePrueba();

        $response = $this->actingAs($user)->post(route('flota.varadas.importar'), [
            'archivos' => [new UploadedFile($path, 'prueba.xlsx', null, null, true)],
        ]);

        $response->assertRedirect(route('flota.varadas.index'));
        $this->assertDatabaseCount('flota_varadas', 2);

        unlink($path);
    }
}
