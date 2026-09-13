<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_are_not_redirected_outside_production(): void
    {
        // En entorno de test/local no se fuerza HTTPS (evita romper el flujo
        // de desarrollo local, que corre en http://localhost).
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_hsts_header_is_only_set_in_production_over_https(): void
    {
        $response = $this->get(route('home'));

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    /**
     * Regresión del incidente del 2026-09-14 (mismo bug en el hermano
     * ADENAR): ForceHttps registrado con prepend() GLOBAL corría antes que
     * TrustProxies, así que nunca veía el X-Forwarded-Proto que manda
     * Railway (TLS terminado en su borde) y redirigía a https en bucle
     * infinito. Este test simula esa cabecera para detectar si el orden
     * vuelve a romperse.
     */
    public function test_no_redirige_en_bucle_cuando_el_proxy_ya_termino_https(): void
    {
        $this->app['env'] = 'production';

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get(route('home'), ['REMOTE_ADDR' => '10.0.0.5']);

        $response->assertOk();
    }

    public function test_redirige_una_sola_vez_a_https_sin_el_proxy_header(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get(route('home'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }
}
