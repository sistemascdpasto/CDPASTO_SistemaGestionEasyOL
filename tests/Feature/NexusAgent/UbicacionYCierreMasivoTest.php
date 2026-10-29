<?php

namespace Tests\Feature\NexusAgent;

use App\Models\User;
use Cdpasto\NexusAgent\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Agente Nexus 1.4.0: ubicación obligatoria y cierre masivo de sesiones.
 */
class UbicacionYCierreMasivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Location::flushPolicy();
        Http::fake(); // geocodificación y envíos a Nexus
        config(['nexus_agent.location.required' => true, 'nexus_agent.key' => 'clave-de-prueba']);
    }

    public function test_sin_permiso_de_ubicacion_se_muestra_el_aviso()
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Activa tu ubicación para continuar');
    }

    public function test_las_visitas_inertia_se_fuerzan_a_recargar()
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard', ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location');
    }

    public function test_no_se_frenan_los_formularios_ni_los_invitados()
    {
        $this->get('/login')->assertDontSee('Activa tu ubicación para continuar');
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect();
    }

    public function test_con_coordenadas_el_usuario_entra()
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/nexus/ubicacion', ['latitude' => 1.2136, 'longitude' => -77.2811, 'accuracy' => 20])
            ->assertOk()
            ->assertJson(['blocked' => false]);

        $this->get('/dashboard')->assertDontSee('Activa tu ubicación para continuar');
    }

    public function test_sin_senal_gps_se_permite_pero_si_niega_el_permiso_se_bloquea()
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/nexus/ubicacion', ['error' => 'timeout'])->assertJson(['blocked' => false]);
        $this->get('/dashboard')->assertDontSee('Activa tu ubicación para continuar');

        // Quita el permiso a mitad de sesión: el siguiente cambio de página vuelve al aviso.
        $this->postJson('/nexus/ubicacion', ['error' => 'denied'])->assertJson(['blocked' => true]);
        $this->get('/dashboard')->assertSee('Activa tu ubicación para continuar');
    }

    public function test_se_puede_desactivar_por_configuracion()
    {
        config(['nexus_agent.location.required' => false]);

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertDontSee('Activa tu ubicación para continuar');
    }

    public function test_nexus_puede_desactivar_y_reactivar_la_ubicacion_obligatoria()
    {
        $user = User::factory()->create();

        $this->comandoFirmado(['type' => 'location_off'])->assertOk()->assertJson(['ok' => true]);
        $this->actingAs($user)->get('/dashboard')->assertDontSee('Activa tu ubicación para continuar');

        $this->comandoFirmado(['type' => 'location_on'])->assertOk();
        $this->get('/dashboard')->assertSee('Activa tu ubicación para continuar');
    }

    public function test_la_politica_llega_en_la_respuesta_de_la_ingesta()
    {
        \Cdpasto\NexusAgent\Location::storePolicy(false);
        $this->assertFalse(\Cdpasto\NexusAgent\Location::required());

        // Valores que no son booleanos se ignoran (versiones de Nexus sin el módulo).
        \Cdpasto\NexusAgent\Location::storePolicy(null);
        $this->assertFalse(\Cdpasto\NexusAgent\Location::required());

        // La variable local manda aunque Nexus la exija.
        \Cdpasto\NexusAgent\Location::storePolicy(true);
        config(['nexus_agent.location.required' => false]);
        $this->assertFalse(\Cdpasto\NexusAgent\Location::required());
    }

    private function comandoFirmado(array $data): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($data);
        $timestamp = (string) time();

        return $this->call('POST', '/nexus/commands', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_NEXUS_TIMESTAMP' => $timestamp,
            'HTTP_X_NEXUS_SIGNATURE' => hash_hmac('sha256', $timestamp."\n".$body, 'clave-de-prueba'),
        ], $body);
    }

    public function test_cierre_masivo_borra_todas_las_sesiones_y_los_recordarme()
    {
        config(['session.driver' => 'database']);
        $users = User::factory()->count(3)->create();
        foreach ($users as $i => $user) {
            DB::table('sessions')->insert(['id' => "s{$i}", 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        }
        DB::table('sessions')->insert(['id' => 'invitado', 'user_id' => null, 'payload' => '', 'last_activity' => time()]);

        $body = json_encode(['type' => 'logout_all']);
        $timestamp = (string) time();

        $this->call('POST', '/nexus/commands', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_NEXUS_TIMESTAMP' => $timestamp,
            'HTTP_X_NEXUS_SIGNATURE' => hash_hmac('sha256', $timestamp."\n".$body, 'clave-de-prueba'),
        ], $body)->assertOk()->assertJson(['ok' => true, 'message' => 'Se cerraron 3 sesiones.']);

        $this->assertSame(['invitado'], DB::table('sessions')->pluck('id')->all());
        $this->assertSame(0, User::whereNotNull('remember_token')->count());
    }
}
