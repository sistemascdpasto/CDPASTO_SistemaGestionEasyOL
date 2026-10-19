<?php

namespace Tests\Feature\Seguridad;

use App\Exports\Seguridad\PruebasExport;
use App\Models\Seguridad\Alcoholimetro;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\PersonaExterna;
use App\Models\Seguridad\PruebaAlcoholemia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PruebaAlcoholemiaTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        $role = Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function colaborador(): Colaborador
    {
        return Colaborador::create([
            'cedula' => '1002003004',
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'cargo' => 'Conductor',
            'turno' => 'manana',
            'estado_registro' => 'completo',
            'is_active' => true,
        ]);
    }

    private function alcoholimetro(): Alcoholimetro
    {
        return Alcoholimetro::create([
            'codigo' => 'ALC-001',
            'valor_min' => 0,
            'valor_max' => 1,
            'estado' => 'Disponible',
        ]);
    }

    public function test_create_page_exposes_cargo_for_each_colaborador(): void
    {
        $user = $this->seguridadUser();
        $this->colaborador();

        $response = $this->actingAs($user)->get(route('seguridad.pruebas.create'));

        $response->assertInertia(fn ($page) => $page
            ->where('colaboradores.0.cargo', 'Conductor')
        );
    }

    public function test_it_accepts_a_pdf_as_additional_evidence(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            'evidencias' => [UploadedFile::fake()->create('soporte.pdf', 100, 'application/pdf')],
        ]);

        $response->assertRedirect(route('seguridad.pruebas.index'));
        $prueba = PruebaAlcoholemia::where('colaborador_id', $colaborador->id)->firstOrFail();
        $this->assertCount(1, $prueba->evidencias);
    }

    public function test_it_rejects_a_non_pdf_file_as_additional_evidence(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            'evidencias' => [UploadedFile::fake()->image('foto.jpg')],
        ]);

        $response->assertSessionHasErrors('evidencias.0');
        $this->assertDatabaseMissing('pruebas_alcoholemia', ['colaborador_id' => $colaborador->id]);
    }

    public function test_it_requires_consentimiento_aceptado_when_not_scheduling(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => false,
        ]);

        $response->assertSessionHasErrors('consentimiento_aceptado');
    }

    public function test_it_can_schedule_a_prueba_for_later_without_accepting_consentimiento(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();

        // Igual que lo que realmente manda Inertia por FormData: booleans
        // como '1'/'0' (no true/false nativos de PHP). El formulario siempre
        // incluye 'consentimiento_aceptado' aunque esa sección no se muestre
        // al programar — no debe bloquear el guardado.
        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'es_programacion' => '1',
            'programada_en' => now()->addDay()->format('Y-m-d\TH:i'),
            'consentimiento_aceptado' => '0',
        ]);

        $response->assertRedirect(route('seguridad.pruebas.index'));
        $this->assertDatabaseHas('pruebas_alcoholemia', [
            'colaborador_id' => $colaborador->id,
            'estado' => 'programada',
        ]);
    }

    public function test_tipo_selector_accepts_the_renamed_values(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'salida',
            'turno' => 'C',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
        ]);

        $response->assertRedirect(route('seguridad.pruebas.index'));
        $this->assertDatabaseHas('pruebas_alcoholemia', [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'salida',
            'turno' => 'C',
        ]);
    }

    public function test_it_rejects_the_old_tipo_values(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'pre_ruta',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
        ]);

        $response->assertSessionHasErrors('tipo');
    }

    public function test_it_requires_a_valid_turno(): void
    {
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $base = [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
        ];

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $base)
            ->assertSessionHasErrors('turno');

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), [...$base, 'turno' => 'D'])
            ->assertSessionHasErrors('turno');
    }

    public function test_evidencia_principal_path_finds_the_image_regardless_of_upload_order(): void
    {
        $colaborador = $this->colaborador();
        $prueba = PruebaAlcoholemia::create([
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'consentimiento_aceptado' => true,
            'responsable_id' => $this->seguridadUser()->id,
            'fecha_hora' => now(),
            'estado' => 'realizada',
        ]);

        // A propósito en orden "adicional primero" — la posición no debe
        // importar, solo la extensión (PDF vs. imagen).
        $prueba->evidencias()->create(['path' => 'evidencias/soporte.pdf']);
        $prueba->evidencias()->create(['path' => 'evidencias/foto.jpg']);

        $this->assertSame('evidencias/foto.jpg', $prueba->evidenciaPrincipalPath());
    }

    public function test_evidencia_principal_path_is_null_when_only_pdfs_are_attached(): void
    {
        $colaborador = $this->colaborador();
        $prueba = PruebaAlcoholemia::create([
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'consentimiento_aceptado' => true,
            'responsable_id' => $this->seguridadUser()->id,
            'fecha_hora' => now(),
            'estado' => 'realizada',
        ]);

        $prueba->evidencias()->create(['path' => 'evidencias/soporte.pdf']);

        $this->assertNull($prueba->evidenciaPrincipalPath());
    }

    public function test_pdf_report_embeds_small_thumbnails_instead_of_full_photos(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $responsable = $this->seguridadUser();
        $colaborador = $this->colaborador();

        // Foto «de celular»: grande y con ruido para que el JPEG no se comprima casi nada.
        $foto = imagecreatetruecolor(2400, 1800);
        for ($i = 0; $i < 60000; $i++) {
            imagesetpixel($foto, random_int(0, 2399), random_int(0, 1799), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagejpeg($foto, null, 95);
        Storage::disk('public')->put('evidencias/foto-grande.jpg', ob_get_clean());
        $tamanoOriginal = Storage::disk('public')->size('evidencias/foto-grande.jpg');

        foreach (range(1, 30) as $i) {
            PruebaAlcoholemia::create([
                'colaborador_id' => $colaborador->id,
                'responsable_id' => $responsable->id,
                'tipo' => 'pre_ruta',
                'fecha_hora' => now()->subMinutes($i),
                'estado' => 'realizada',
                'evidencia_path' => 'evidencias/foto-grande.jpg',
                'firma_path' => 'evidencias/foto-grande.jpg',
            ]);
        }

        $html = view('seguridad.pruebas-pdf', ['pruebas' => PruebaAlcoholemia::with('evidencias')->get()])->render();

        // Antes: 60 copias de la foto completa en base64 (decenas de MB). Ahora: miniaturas.
        $this->assertSame(60, substr_count($html, 'data:image/jpeg;base64,'));
        $this->assertLessThan($tamanoOriginal, strlen($html));
        $this->assertCount(1, Storage::disk('local')->files('miniaturas-pdf'));
    }

    public function test_pdf_and_excel_export_routes_render_without_error_for_mixed_evidence(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'B',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            'evidencia' => [UploadedFile::fake()->image('foto.jpg')],
            'evidencias' => [UploadedFile::fake()->create('soporte.pdf', 100, 'application/pdf')],
        ]);

        $prueba = PruebaAlcoholemia::where('colaborador_id', $colaborador->id)->firstOrFail();
        $fotoPath = $prueba->evidencias()->where('path', 'like', '%.jpg')->firstOrFail()->path;
        $this->assertSame($fotoPath, $prueba->evidenciaPrincipalPath());

        $this->actingAs($user)->get(route('seguridad.pruebas.exportar-pdf'))->assertOk();
        $this->actingAs($user)->get(route('seguridad.pruebas.exportar-excel'))->assertOk();
    }

    public function test_excel_export_headings_and_row_no_longer_include_evidencias_adicionales(): void
    {
        Storage::fake('public');
        $colaborador = $this->colaborador();
        $prueba = PruebaAlcoholemia::create([
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'consentimiento_aceptado' => true,
            'responsable_id' => $this->seguridadUser()->id,
            'fecha_hora' => now(),
            'estado' => 'realizada',
        ]);
        // La foto se sube después del PDF a propósito, para probar que el
        // reporte no depende del orden en que se guardaron las evidencias.
        $prueba->evidencias()->create(['path' => 'evidencias/soporte.pdf']);
        $prueba->evidencias()->create(['path' => 'evidencias/foto.jpg']);

        $export = new PruebasExport(collect([$prueba]));

        $this->assertSame(
            ['Fecha', 'Tipo de persona', 'Nombre', 'Documento', 'Empresa', 'Tipo', 'Turno', 'Dispositivo', 'Resultado', 'Evaluación', 'Estado', 'Responsable', 'Firma', 'Evidencia principal'],
            $export->headings()
        );

        $fila = $export->map($prueba);
        $this->assertCount(14, $fila);
        $this->assertSame('Colaborador', $fila[1]);
        $this->assertSame('', end($fila));
    }

    public function test_it_can_create_and_update_fecha_hora_of_a_prueba_and_photo(): void
    {
        Storage::fake('public');
        $user = $this->seguridadUser();
        $colaborador = $this->colaborador();
        $dispositivo = $this->alcoholimetro();

        $fechaPersonalizada = '2026-09-18T10:30';

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            'fecha_hora' => $fechaPersonalizada,
        ]);

        $response->assertRedirect(route('seguridad.pruebas.index'));

        $prueba = PruebaAlcoholemia::where('colaborador_id', $colaborador->id)->firstOrFail();
        $this->assertSame('2026-09-18 10:30:00', $prueba->fecha_hora->format('Y-m-d H:i:s'));

        // Editar fecha y hora
        $nuevaFechaHora = '2026-09-18T14:45';

        $updateResponse = $this->actingAs($user)->put(route('seguridad.pruebas.update', $prueba), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            'fecha_hora' => $nuevaFechaHora,
        ]);

        $updateResponse->assertRedirect(route('seguridad.pruebas.index'));

        $prueba->refresh();
        $this->assertSame('2026-09-18 14:45:00', $prueba->fecha_hora->format('Y-m-d H:i:s'));
    }

    /**
     * @return array<string, mixed>
     */
    private function datosExterno(Alcoholimetro $dispositivo, array $overrides = []): array
    {
        return [
            'tipo_persona' => 'externo',
            'externo_documento' => '80 123 456',
            'externo_nombres' => 'Luis',
            'externo_apellidos' => 'Pérez',
            'externo_empresa' => 'Montajes del Sur SAS',
            'externo_tipo_vinculo' => 'contratista',
            'tipo' => 'ingreso',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
            ...$overrides,
        ];
    }

    public function test_it_registers_a_prueba_for_a_contratista_without_turno(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('seguridad.pruebas.index'));

        $persona = PersonaExterna::where('documento', '80123456')->firstOrFail();
        $this->assertSame('Montajes del Sur SAS', $persona->empresa);

        $prueba = PruebaAlcoholemia::firstOrFail();
        $this->assertNull($prueba->colaborador_id);
        $this->assertSame($persona->id, $prueba->persona_externa_id);
        $this->assertNull($prueba->turno);
        $this->assertSame('Luis Pérez', $prueba->evaluadoNombre());
        $this->assertSame('Apto', $prueba->evaluacion());
    }

    public function test_it_reuses_the_persona_externa_by_documento_and_updates_its_data(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo));
        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo, [
            'tipo' => 'salida',
            'externo_empresa' => 'Montajes del Sur S.A.S.',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, PersonaExterna::count());
        $this->assertSame('Montajes del Sur S.A.S.', PersonaExterna::first()->empresa);
        $this->assertSame(2, PruebaAlcoholemia::where('persona_externa_id', PersonaExterna::first()->id)->count());
    }

    public function test_it_requires_the_externo_data_when_tipo_persona_is_externo(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();

        $response = $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo, [
            'externo_documento' => '',
            'externo_nombres' => '',
            'externo_empresa' => '',
        ]));

        $response->assertSessionHasErrors(['externo_documento', 'externo_nombres', 'externo_empresa']);
        $response->assertSessionDoesntHaveErrors(['colaborador_id', 'turno']);
    }

    public function test_it_blocks_a_second_prueba_of_the_same_tipo_for_the_same_externo_within_the_interval(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo))->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo))
            ->assertSessionHasErrors('tipo');
    }

    public function test_index_filters_by_tipo_persona_and_searches_by_empresa(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();
        $colaborador = $this->colaborador();

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo));
        $this->actingAs($user)->post(route('seguridad.pruebas.store'), [
            'colaborador_id' => $colaborador->id,
            'tipo' => 'ingreso',
            'turno' => 'A',
            'alcoholimetro_id' => $dispositivo->id,
            'resultado' => '0.000',
            'consentimiento_aceptado' => true,
        ]);

        $this->actingAs($user)->get(route('seguridad.pruebas.index', ['tipo_persona' => 'externo']))
            ->assertInertia(fn ($page) => $page
                ->has('pruebas.data', 1)
                ->where('pruebas.data.0.persona_externa.empresa', 'Montajes del Sur SAS'));

        $this->actingAs($user)->get(route('seguridad.pruebas.index', ['tipo_persona' => 'colaborador']))
            ->assertInertia(fn ($page) => $page->has('pruebas.data', 1)->where('pruebas.data.0.colaborador_id', $colaborador->id));

        $this->actingAs($user)->get(route('seguridad.pruebas.index', ['colaborador' => 'Montajes']))
            ->assertInertia(fn ($page) => $page->has('pruebas.data', 1));
    }

    public function test_a_positive_externo_generates_an_alerta_and_exports_render(): void
    {
        $user = $this->seguridadUser();
        $dispositivo = $this->alcoholimetro();

        $this->actingAs($user)->post(route('seguridad.pruebas.store'), $this->datosExterno($dispositivo, ['resultado' => '0.500']))
            ->assertSessionHasNoErrors();

        $prueba = PruebaAlcoholemia::with('personaExterna')->firstOrFail();
        $this->assertSame('No Apto', $prueba->evaluacion());
        $this->assertDatabaseHas('alertas', ['prueba_alcoholemia_id' => $prueba->id, 'tipo' => 'prueba_positiva', 'colaborador_id' => null]);

        $fila = (new PruebasExport(collect([$prueba])))->map($prueba);
        $this->assertSame(['Externo', 'Luis Pérez', '80123456', 'Montajes del Sur SAS'], array_slice($fila, 1, 4));

        $this->actingAs($user)->get(route('seguridad.pruebas.exportar-pdf'))->assertOk();
        $this->actingAs($user)->get(route('seguridad.pruebas.show', $prueba))->assertOk();
        $this->actingAs($user)->get(route('seguridad.pruebas.edit', $prueba))
            ->assertInertia(fn ($page) => $page->where('prueba.persona_externa.documento', '80123456'));
    }
}
