<?php

namespace App\Http\Requests\Gente\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

trait ValidatesColaboradorDocumentos
{
    /**
     * Formatos aceptados para los documentos del colaborador: PDF, Word e
     * imágenes. Se comparte con StoreLlamadoAtencionRequest para que todos
     * los documentos del colaborador acepten exactamente lo mismo.
     */
    public const MIMES = 'pdf,doc,docx,jpg,jpeg,png';

    /**
     * Reglas repetidas para un grupo de campos de documentos multi-archivo
     * (`nullable array` + cada archivo como pdf/word/imagen hasta 5MB).
     *
     * @param  array<int, string>  $campos
     * @return array<string, mixed>
     */
    protected function documentoRules(array $campos): array
    {
        $rules = [];

        foreach ($campos as $campo) {
            $rules[$campo] = ['nullable', 'array'];
            $rules["{$campo}.*"] = ['file', 'mimes:'.self::MIMES, 'max:5120'];
        }

        return $rules;
    }

    /**
     * Diagnóstico: un usuario reportó que un PDF genuino era rechazado por
     * "tipo de archivo no permitido" en el wizard, pero un PDF real probado
     * de punta a punta (subida real por navegador) sí pasó en este mismo
     * entorno — así que el rechazo no parece ser un bug de la regla en sí,
     * sino algo puntual del archivo/navegador/máquina del usuario (ej. un
     * archivo aún no descargado de OneDrive, un archivo realmente corrupto,
     * antivirus interceptando la subida, etc.). En vez de seguir adivinando,
     * esto deja evidencia real en el log — mime y extensión que el propio
     * servidor detectó — la próxima vez que un documento se rechace, para
     * poder diagnosticarlo con datos en vez de reproducir a ciegas.
     *
     * @param  array<int, string>  $campos
     */
    protected function logArchivosDocumentoRechazados(Validator $validator, array $campos): void
    {
        foreach ($validator->errors()->keys() as $atributo) {
            if (! preg_match('/^('.implode('|', array_map('preg_quote', $campos)).')\.(\d+)$/', $atributo)) {
                continue;
            }

            $archivo = $this->file($atributo);

            if (! $archivo) {
                continue;
            }

            Log::warning('Documento de colaborador rechazado en validación', [
                'usuario_id' => $this->user()?->id,
                'campo' => $atributo,
                'nombre_original' => $archivo->getClientOriginalName(),
                'mime_detectado' => $archivo->getMimeType(),
                'extension_detectada' => $archivo->guessExtension(),
                'extension_cliente' => $archivo->getClientOriginalExtension(),
                'tamano_bytes' => $archivo->getSize(),
                'es_valido' => $archivo->isValid(),
                'error_subida' => $archivo->getError(),
                'errores_validacion' => $validator->errors()->get($atributo),
            ]);
        }
    }
}
