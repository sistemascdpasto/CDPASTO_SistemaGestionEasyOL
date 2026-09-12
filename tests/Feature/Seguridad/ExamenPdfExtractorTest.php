<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\EvaluacionRecomendacion;
use App\Models\Seguridad\Recomendacion;
use App\Services\Seguridad\ExamenPdfExtractorService;
use App\Services\Seguridad\RecomendacionPdfMatcherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamenPdfExtractorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Genera un PDF real (vía dompdf, ya es dependencia del proyecto) con
     * el HTML dado, para probar el extractor contra un archivo válido sin
     * depender de un PDF de muestra que no existe.
     */
    private function pdfConTexto(string $html): string
    {
        $ruta = sys_get_temp_dir().'/examen-pdf-test-'.uniqid().'.pdf';
        file_put_contents($ruta, Pdf::loadHTML($html)->output());

        return $ruta;
    }

    public function test_extrae_fecha_y_hora_de_ingreso_cuando_estan_presentes(): void
    {
        $ruta = $this->pdfConTexto('<p>Fecha de ingreso: 15/03/2026</p><p>Hora de ingreso: 08:30</p>');

        $extractor = new ExamenPdfExtractorService();
        $campos = $extractor->extraerCamposIngreso($extractor->extraerTexto($ruta));

        $this->assertSame('2026-03-15', $campos['fecha_ingreso_pdf']);
        $this->assertSame('08:30', $campos['hora_ingreso_pdf']);
    }

    public function test_no_falla_y_devuelve_null_cuando_no_hay_etiquetas(): void
    {
        $ruta = $this->pdfConTexto('<p>Documento sin las etiquetas esperadas.</p>');

        $extractor = new ExamenPdfExtractorService();
        $campos = $extractor->extraerCamposIngreso($extractor->extraerTexto($ruta));

        $this->assertNull($campos['fecha_ingreso_pdf']);
        $this->assertNull($campos['hora_ingreso_pdf']);
    }

    public function test_no_falla_con_un_archivo_que_no_es_pdf_valido(): void
    {
        $ruta = sys_get_temp_dir().'/no-es-un-pdf-'.uniqid().'.pdf';
        file_put_contents($ruta, 'esto no es un PDF');

        $extractor = new ExamenPdfExtractorService();

        $this->assertSame('', $extractor->extraerTexto($ruta));
    }

    public function test_marca_recomendaciones_que_coinciden_en_el_texto_del_pdf_sin_duplicar(): void
    {
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de corrección óptica', 'categoria' => 'medica', 'activo' => true]);
        Recomendacion::create(['nombre' => 'Actividad física aeróbica', 'categoria' => 'habitos', 'activo' => true]);

        $colaborador = Colaborador::create([
            'cedula' => '900'.random_int(100000, 999999), 'nombres' => 'Test', 'apellidos' => 'Colaborador', 'estado_registro' => 'completo',
        ]);
        $evaluacion = EvaluacionMedica::create([
            'colaborador_id' => $colaborador->id,
            'tipo_evaluacion' => 'ingreso',
            'fecha_evaluacion' => now(),
            'estado' => 'sin_iniciar',
        ]);

        $matcher = new RecomendacionPdfMatcherService();
        $texto = 'El paciente requiere Uso de corrección óptica según la valoración realizada.';

        $marcadas = $matcher->marcarCoincidencias($evaluacion, $texto);

        $this->assertSame(1, $marcadas);
        $this->assertSame(
            1,
            EvaluacionRecomendacion::where('evaluacion_medica_id', $evaluacion->id)
                ->where('recomendacion_id', $recomendacion->id)
                ->where('origen', 'PDF')
                ->count(),
        );

        // Repetir la coincidencia (p. ej. al re-ejecutar el mismo examen) no duplica la fila.
        $matcher->marcarCoincidencias($evaluacion, $texto);
        $this->assertSame(1, EvaluacionRecomendacion::where('evaluacion_medica_id', $evaluacion->id)->count());
    }
}
