<?php

namespace Tests\Feature\Api;

use App\Models\SimitConsulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SimitConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.simit.token' => 'token-de-prueba']);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'placa' => 'ABC123',
            'timestamp' => '2026-08-24T08:00:00',
            'status' => 'sin_comparendos',
            'raw_text' => 'No tienes comparendos pendientes.',
            ...$overrides,
        ];
    }

    public function test_it_rejects_requests_without_a_valid_token(): void
    {
        $this->postJson('/api/simit/consultas', $this->payload())
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer token-incorrecto')
            ->postJson('/api/simit/consultas', $this->payload())
            ->assertStatus(401);
    }

    public function test_it_stores_a_consulta_with_a_valid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer token-de-prueba')
            ->postJson('/api/simit/consultas', $this->payload())
            ->assertStatus(201);

        $this->assertDatabaseHas('simit_consultas', [
            'placa' => 'ABC123',
            'status' => 'sin_comparendos',
        ]);
    }

    public function test_it_stores_the_screenshot_as_a_blob(): void
    {
        $archivo = UploadedFile::fake()->image('captura.png');

        $this->withHeader('Authorization', 'Bearer token-de-prueba')
            ->post('/api/simit/consultas', [
                ...$this->payload(),
                'screenshot' => $archivo,
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        $consulta = SimitConsulta::firstOrFail();
        $this->assertSame('captura.png', $consulta->screenshot_nombre);
        $this->assertNotEmpty($consulta->screenshot);
    }

    public function test_it_rejects_an_invalid_status(): void
    {
        $this->withHeader('Authorization', 'Bearer token-de-prueba')
            ->postJson('/api/simit/consultas', $this->payload(['status' => 'no-existe']))
            ->assertStatus(422);
    }
}
