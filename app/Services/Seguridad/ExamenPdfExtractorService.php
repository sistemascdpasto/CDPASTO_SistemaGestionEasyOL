<?php

namespace App\Services\Seguridad;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extracción "best effort" de campos informativos desde el PDF de un
 * examen ocupacional (HU-033). No hay librería de OCR ni un PDF de
 * muestra para calibrar el formato exacto — se buscan las etiquetas
 * literales del documento ("Fecha de ingreso", "Hora de ingreso") con
 * regex tolerante a mayúsculas/espacios/saltos de línea. Si el PDF es una
 * imagen escaneada (sin texto) o no trae la etiqueta, el campo queda vacío
 * sin bloquear la carga — nunca reemplaza la fecha contractual del
 * colaborador (CA-033.10).
 */
class ExamenPdfExtractorService
{
    public function extraerTexto(string $rutaAbsoluta): string
    {
        try {
            return (new Parser())->parseFile($rutaAbsoluta)->getText();
        } catch (Throwable $e) {
            Log::warning("No se pudo extraer texto del PDF de examen ({$rutaAbsoluta}): {$e->getMessage()}");

            return '';
        }
    }

    /**
     * @return array{fecha_ingreso_pdf: ?string, hora_ingreso_pdf: ?string}
     */
    public function extraerCamposIngreso(string $texto): array
    {
        return [
            'fecha_ingreso_pdf' => $this->buscarFecha($texto, 'fecha de ingreso'),
            'hora_ingreso_pdf' => $this->buscarHora($texto, 'hora de ingreso'),
        ];
    }

    private function buscarFecha(string $texto, string $etiqueta): ?string
    {
        $patron = '/'.preg_quote($etiqueta, '/').'\s*[:\-]?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/iu';

        if (! preg_match($patron, $this->normalizarEspacios($texto), $coincidencias)) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'd/m/y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $coincidencias[1])->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function buscarHora(string $texto, string $etiqueta): ?string
    {
        $patron = '/'.preg_quote($etiqueta, '/').'\s*[:\-]?\s*(\d{1,2}:\d{2}(?::\d{2})?\s*(?:[ap]\.?\s?m\.?)?)/iu';

        if (! preg_match($patron, $this->normalizarEspacios($texto), $coincidencias)) {
            return null;
        }

        return trim($coincidencias[1]);
    }

    private function normalizarEspacios(string $texto): string
    {
        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }
}
