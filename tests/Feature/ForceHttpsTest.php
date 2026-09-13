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
}
