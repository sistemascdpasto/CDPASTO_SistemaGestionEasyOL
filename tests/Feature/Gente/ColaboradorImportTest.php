<?php

namespace Tests\Feature\Gente;

use App\Models\Seguridad\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ColaboradorImportTest extends TestCase
{
    use RefreshDatabase;

    private function genteUser(): User
    {
        // La carga masiva de colaboradores es exclusiva de Gente.
        $role = Role::create(['name' => 'Gente', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Arma un .xlsx real (no un fake vacío) con la hoja "BASE ACTUALIZADA "
     * (con el espacio final tal como viene el archivo real) para poder
     * probar el flujo de subida completo.
     *
     * @param  array<int, array<int, string>>  $filas  cada fila trae las 14 columnas B..O
     */
    private function construirExcel(array $filas, bool $hojaPrimero = true): UploadedFile
    {
        $spreadsheet = new Spreadsheet();

        if (! $hojaPrimero) {
            $spreadsheet->getActiveSheet()->setTitle('Otra hoja');
            $spreadsheet->createSheet();
        }

        $hoja = $spreadsheet->getSheet($hojaPrimero ? 0 : 1);
        $hoja->setTitle('BASE ACTUALIZADA ');

        $encabezados = ['', 'NÚMERO ID', 'NOMBRES Y  APELLIDOS', 'AREA', 'CARGO', 'CENTRO ', 'TELEFONO ', 'CODIGO QR SKAP',
            'CORREO ', 'DIRECCION ', 'CENTRO DE TRABAJO', 'NOMBRE DE LA EPS', 'NOMBRE DE LA AFP', 'NOMBRE DE LA ARL', 'FECHA DE INGRESO'];
        $hoja->fromArray($encabezados, null, 'A1');

        $numeroFila = 2;
        foreach ($filas as $datos) {
            $hoja->fromArray(['', ...$datos], null, 'A'.$numeroFila);
            $numeroFila++;
        }

        $ruta = sys_get_temp_dir().'/colaboradores-import-test-'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($ruta);

        return new UploadedFile($ruta, 'base.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_importa_colaboradores_normaliza_catalogos_y_provisiona_usuarios(): void
    {
        $user = $this->genteUser();

        $existente = Colaborador::create([
            'cedula' => '999000111',
            'nombres' => 'Ya',
            'apellidos' => 'Existe',
            'estado_registro' => 'completo',
        ]);

        $archivo = $this->construirExcel([
            // Fila normal: nombres en minúsculas de área, EPS con prefijo a normalizar, SKAP alfanumérico.
            ['1002003004', 'PEREZ GOMEZ JUAN CARLOS', 'ADMINISTRATIVA', 'PROGRAMADOR', 'UC', '3001112233', 'ABC123XY', 'juan.perez@example.com', 'Calle 1', 'Sura riesgo I', 'EPS-S EMSSANAR', 'PROTECCION', 'ARL SURA', '16/12/2025'],
            // Cédula ya existente en BD -> se omite, no se toca el registro existente.
            ['999000111', 'YA EXISTE OTRA VEZ', 'OPERATIVA', 'CONDUCTOR', 'UC', '3000000000', 'N/A', '', '', 'Sura riesgo I', 'SANITAS', 'PORVENIR', 'ARL SURA', '1/01/2020'],
            // Nombre vacío -> cuenta como error, no aborta el resto del archivo.
            ['1002003099', '', 'OPERATIVA', 'CONDUCTOR', 'UC', '3000000001', '', '', '', 'Sura riesgo I', 'SANITAS', 'PORVENIR', 'ARL SURA', '1/01/2020'],
        ]);

        $response = $this->actingAs($user)->post(route('gente.colaboradores.importar'), ['archivo' => $archivo]);

        $response->assertRedirect(route('gente.colaboradores.index'));
        $response->assertSessionHas('status', function ($status) {
            return $status['type'] === 'warning'
                && str_contains($status['message'], '1 creados')
                && str_contains($status['message'], '1 omitidos')
                && str_contains($status['message'], '1 con error');
        });

        $creado = Colaborador::where('cedula', '1002003004')->first();
        $this->assertNotNull($creado);
        $this->assertSame('JUAN CARLOS', $creado->nombres);
        $this->assertSame('PEREZ GOMEZ', $creado->apellidos);
        $this->assertSame('Administrativa', $creado->area);
        $this->assertSame('PROGRAMADOR', $creado->cargo);
        $this->assertSame('EMSSANAR', $creado->eps);
        $this->assertSame('PROTECCIÓN', $creado->afp);
        $this->assertSame('ABC123XY', $creado->codigo_qr_skap);
        $this->assertSame('2025-12-16', $creado->fecha_ingreso_empresa->toDateString());
        $this->assertTrue($creado->is_active);
        $this->assertSame('completo', $creado->estado_registro);
        $this->assertSame(4, $creado->wizard_step);
        $this->assertNotNull($creado->user_id);

        $cuenta = User::find($creado->user_id);
        $this->assertSame('1002003004', $cuenta->identification_number);
        $this->assertTrue(Hash::check('1002003004', $cuenta->password));
        $this->assertTrue($cuenta->hasRole('Colaborador'));

        $this->assertSame('Ya', $existente->fresh()->nombres);
        $this->assertSame(2, Colaborador::count()); // el existente + el único creado

        $this->assertNull(Colaborador::where('cedula', '1002003099')->first());
    }

    public function test_codigo_na_se_guarda_como_null_y_encuentra_la_hoja_aunque_no_sea_la_primera(): void
    {
        $user = $this->genteUser();

        $archivo = $this->construirExcel([
            ['1002003005', 'GOMEZ RUIZ ANA MARIA', 'OPERATIVA', 'CONDUCTOR', 'UC', '3001112233', 'N/A', '', '', 'Sura riesgo I', 'SANITAS', 'PORVENIR', 'ARL SURA', '4/03/2023'],
        ], hojaPrimero: false);

        $this->actingAs($user)->post(route('gente.colaboradores.importar'), ['archivo' => $archivo])
            ->assertSessionHas('status', fn ($status) => $status['type'] === 'success');

        $creado = Colaborador::where('cedula', '1002003005')->firstOrFail();
        $this->assertNull($creado->codigo_qr_skap);
        $this->assertSame('ANA MARIA', $creado->nombres);
        $this->assertSame('GOMEZ RUIZ', $creado->apellidos);
        $this->assertSame('2023-03-04', $creado->fecha_ingreso_empresa->toDateString());
    }

    public function test_cargo_y_centro_nuevos_del_catalogo_ampliado_se_guardan_tal_cual(): void
    {
        $user = $this->genteUser();

        $archivo = $this->construirExcel([
            ['1002003006', 'TORRES LEON PEDRO', 'OPERATIVA', 'CONDUCTOR MULA', 'UD', '3001112233', 'XYZ999', 'pedro@example.com', '', 'Sura riesgo I', 'ASMET SALUD', 'COLPENSIONES', 'ARL SURA', '1/06/2024'],
        ]);

        $this->actingAs($user)->post(route('gente.colaboradores.importar'), ['archivo' => $archivo]);

        $creado = Colaborador::where('cedula', '1002003006')->firstOrFail();
        $this->assertSame('CONDUCTOR MULA', $creado->cargo);
        $this->assertSame('UD', $creado->centro);
        $this->assertSame('ASMET SALUD', $creado->eps);
    }
}
