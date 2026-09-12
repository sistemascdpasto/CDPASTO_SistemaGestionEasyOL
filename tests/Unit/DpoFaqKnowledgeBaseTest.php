<?php

namespace Tests\Unit;

use App\Support\Chatbot\DpoFaqKnowledgeBase;
use Tests\TestCase;

class DpoFaqKnowledgeBaseTest extends TestCase
{
    public function test_todas_devuelve_las_entradas_del_folleto(): void
    {
        $entradas = DpoFaqKnowledgeBase::todas();

        $this->assertNotEmpty($entradas);
        $this->assertArrayHasKey('seccion', $entradas[0]);
        $this->assertArrayHasKey('pregunta', $entradas[0]);
        $this->assertArrayHasKey('respuesta', $entradas[0]);
    }

    public function test_encuentra_la_entrada_de_los_7_pilares(): void
    {
        $relevantes = DpoFaqKnowledgeBase::buscarRelevantes('¿Cuáles son los 7 pilares del modelo DPO?');

        $this->assertNotEmpty($relevantes);
        $this->assertTrue(
            collect($relevantes)->contains(fn (array $e) => str_contains($e['pregunta'], '7 pilares')),
            'Se esperaba encontrar la entrada de los 7 pilares entre los resultados.'
        );
    }

    public function test_encuentra_la_entrada_de_limites_de_velocidad(): void
    {
        $relevantes = DpoFaqKnowledgeBase::buscarRelevantes('¿Cuál es el límite de velocidad en el CD?');

        $this->assertNotEmpty($relevantes);
        $this->assertSame('Límites de velocidad, ¿cuáles son?', $relevantes[0]['pregunta']);
    }

    public function test_respeta_el_limite_de_resultados(): void
    {
        $relevantes = DpoFaqKnowledgeBase::buscarRelevantes('seguridad', 3);

        $this->assertLessThanOrEqual(3, count($relevantes));
    }

    public function test_una_consulta_sin_palabras_relevantes_no_devuelve_nada(): void
    {
        $this->assertSame([], DpoFaqKnowledgeBase::buscarRelevantes('hola, ¿cómo estás?'));
        $this->assertSame([], DpoFaqKnowledgeBase::buscarRelevantes(''));
    }
}
