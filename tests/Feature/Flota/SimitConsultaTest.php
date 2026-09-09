<?php

namespace Tests\Feature\Flota;

use App\Models\SimitConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SimitConsultaTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsFlota(): User
    {
        $role = Role::create(['name' => 'Flota', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function consulta(array $overrides = []): SimitConsulta
    {
        return SimitConsulta::create([
            'placa' => 'ABC123',
            'fecha_hora' => now(),
            'status' => 'sin_comparendos',
            'raw_text' => 'No tienes comparendos pendientes.',
            ...$overrides,
        ]);
    }

    public function test_flota_can_list_consultas(): void
    {
        $user = $this->actingAsFlota();
        $this->consulta();
        $this->consulta(['placa' => 'XYZ789', 'status' => 'ok']);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index'));

        $response->assertInertia(fn ($page) => $page->has('consultas.data', 2));
    }

    public function test_indicadores_are_computed_correctly(): void
    {
        $user = $this->actingAsFlota();
        $this->consulta(['placa' => 'ABC123', 'status' => 'sin_comparendos', 'fecha_hora' => now()]);
        $this->consulta(['placa' => 'XYZ789', 'status' => 'ok', 'fecha_hora' => now()]);
        $this->consulta(['placa' => 'DEF456', 'status' => 'captcha', 'fecha_hora' => now()]);
        // Un segundo hit de "ok" para XYZ789 en un dia distinto: debe sumar
        // al top de placas con comparendos aunque ya no sea su estado actual.
        $this->consulta(['placa' => 'XYZ789', 'status' => 'ok', 'fecha_hora' => now()->subDay()]);
        $this->consulta(['placa' => 'XYZ789', 'status' => 'sin_comparendos', 'fecha_hora' => now()->subDays(2)]);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index'));

        $response->assertInertia(function ($page) {
            $indicadores = $page->toArray()['props']['indicadores'];

            $this->assertSame(3, $indicadores['resumen']['total_placas']);
            $this->assertSame(1, $indicadores['resumen']['con_comparendos']);
            $this->assertSame(1, $indicadores['resumen']['sin_comparendos']);
            $this->assertSame(1, $indicadores['resumen']['requieren_atencion']);

            $this->assertCount(30, $indicadores['tendencia_diaria']);
            $hoy = collect($indicadores['tendencia_diaria'])->last();
            $this->assertSame(3, $hoy['sin_comparendos'] + $hoy['ok'] + $hoy['captcha'] + $hoy['error']);

            $topPlacas = collect($indicadores['top_placas_comparendos'])->keyBy('placa');
            $this->assertSame(2, $topPlacas['XYZ789']['total']);
        });
    }

    public function test_it_filters_by_placa_and_status(): void
    {
        $user = $this->actingAsFlota();
        $this->consulta(['placa' => 'ABC123', 'status' => 'sin_comparendos']);
        $this->consulta(['placa' => 'XYZ789', 'status' => 'ok']);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index', ['search' => 'XYZ']));
        $response->assertInertia(fn ($page) => $page->has('consultas.data', 1));

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index', ['status' => 'ok']));
        $response->assertInertia(fn ($page) => $page->has('consultas.data', 1));
    }

    public function test_estado_actual_shows_only_the_latest_consulta_per_placa(): void
    {
        $user = $this->actingAsFlota();
        $this->consulta(['placa' => 'ABC123', 'status' => 'sin_comparendos', 'fecha_hora' => now()->subDay()]);
        $this->consulta(['placa' => 'ABC123', 'status' => 'ok', 'fecha_hora' => now()]);
        $this->consulta(['placa' => 'XYZ789', 'status' => 'captcha', 'fecha_hora' => now()]);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index'));

        $response->assertInertia(function ($page) {
            $actuales = collect($page->toArray()['props']['actuales'])->keyBy('placa');

            $this->assertCount(2, $actuales);
            $this->assertSame('ok', $actuales['ABC123']['status']);
            $this->assertSame('captcha', $actuales['XYZ789']['status']);
        });
    }

    public function test_the_screenshot_blob_is_never_serialized_in_the_list(): void
    {
        $user = $this->actingAsFlota();
        $this->consulta(['screenshot' => 'contenido-binario-falso', 'screenshot_nombre' => 'captura.png']);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.index'));

        $response->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            $fila = $props['consultas']['data'][0];
            $this->assertArrayNotHasKey('screenshot', $fila);
            $this->assertSame('captura.png', $fila['screenshot_nombre']);

            $actual = $props['actuales'][0];
            $this->assertArrayNotHasKey('screenshot', $actual);
        });
    }

    public function test_it_serves_the_screenshot_binary_with_an_image_content_type(): void
    {
        $user = $this->actingAsFlota();
        $consulta = $this->consulta(['screenshot' => 'contenido-binario-falso', 'screenshot_nombre' => 'captura.png']);

        $response = $this->actingAs($user)->get(route('flota.simit-consultas.screenshot', $consulta));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame('contenido-binario-falso', $response->getContent());
    }

    public function test_it_returns_404_when_there_is_no_screenshot(): void
    {
        $user = $this->actingAsFlota();
        $consulta = $this->consulta();

        $this->actingAs($user)->get(route('flota.simit-consultas.screenshot', $consulta))
            ->assertNotFound();
    }

    public function test_other_roles_cannot_access_the_module(): void
    {
        Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Seguridad');

        $this->actingAs($user)->get(route('flota.simit-consultas.index'))
            ->assertForbidden();
    }
}
