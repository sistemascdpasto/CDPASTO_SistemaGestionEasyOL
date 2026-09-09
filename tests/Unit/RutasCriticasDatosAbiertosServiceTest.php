<?php

namespace Tests\Unit;

use App\Services\Seguridad\RutasCriticasDatosAbiertosService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class RutasCriticasDatosAbiertosServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Evita depender de la base de datos configurada como cache store por defecto.
        config(['cache.default' => 'array']);

        // El backoff entre reintentos (retry()) duerme de verdad salvo que se
        // falsee — si no, el test de la respuesta 503 tarda varios segundos.
        Sleep::fake();
    }

    public function test_no_guarda_una_respuesta_fallida_para_que_la_siguiente_carga_pueda_reintentarse(): void
    {
        Http::fake([
            'https://www.datos.gov.co/resource/7i66-rps2.json*' => Http::response([], 503),
        ]);

        $service = app(RutasCriticasDatosAbiertosService::class);

        $this->assertSame([], $service->obtenerAfectacionesVia());
        $this->assertFalse(Cache::has('seguridad.rutas-criticas.afectaciones-vias.v2'));
    }

    public function test_guarda_las_respuestas_validas_de_vias_afectadas(): void
    {
        $respuesta = [['municipio' => 'Pasto', 'departamento' => 'Nariño']];

        Http::fake([
            'https://www.datos.gov.co/resource/7i66-rps2.json*' => Http::response($respuesta),
        ]);

        $service = app(RutasCriticasDatosAbiertosService::class);

        $this->assertSame($respuesta, $service->obtenerAfectacionesVia());
        $this->assertSame($respuesta, Cache::get('seguridad.rutas-criticas.afectaciones-vias.v2'));
    }
}
