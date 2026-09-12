<?php

namespace App\Services\Colaborador;

use App\Models\Seguridad\EncuestaMorbilidadPregunta;

class MorbilidadCatalogoService
{
    /**
     * Devuelve la estructura completa de secciones y preguntas,
     * obtenidas dinámicamente de la base de datos (o de config como fallback).
     */
    public function secciones(): array
    {
        try {
            $preguntasDB = EncuestaMorbilidadPregunta::where('activo', true)
                ->orderBy('seccion_numero')
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            if ($preguntasDB->isNotEmpty()) {
                $seccionesConfig = config('morbilidad.secciones', []);
                $secciones = [];

                foreach ($preguntasDB as $p) {
                    $secNum = $p->seccion_numero;
                    if (! isset($secciones[$secNum])) {
                        $meta = $seccionesConfig[$secNum] ?? [];
                        $secciones[$secNum] = [
                            'titulo'      => $p->seccion_titulo ?: ($meta['titulo'] ?? "Sección {$secNum}"),
                            'descripcion' => $meta['descripcion'] ?? null,
                            'sensible'    => $meta['sensible'] ?? false,
                            'preguntas'   => [],
                        ];
                    }

                    $item = [
                        'id'          => $p->id,
                        'texto'       => $p->texto,
                        'tipo'        => $p->tipo,
                        'obligatorio' => $p->obligatorio,
                    ];
                    if ($p->opciones) {
                        $item['opciones'] = $p->opciones;
                    }
                    if ($p->con_otro) {
                        $item['conOtro'] = true;
                    }
                    if ($p->segmento) {
                        $item['segmento'] = $p->segmento;
                    }

                    $secciones[$secNum]['preguntas'][$p->numero_pregunta] = $item;
                }

                return $secciones;
            }
        } catch (\Throwable $e) {
            // Fallback si ocurre algún error en la BD
        }

        return config('morbilidad.secciones', []);
    }

    /**
     * Devuelve todas las preguntas del catálogo en una sola lista plana.
     *
     * @return array<int, array{
     *     seccion: int,
     *     texto: string,
     *     tipo: string,
     *     obligatorio: bool
     * }>
     */
    public function preguntasPlanas(): array
    {
        $planas = [];
        $secciones = $this->secciones();

        foreach ($secciones as $numeroSeccion => $seccion) {
            foreach ($seccion['preguntas'] as $numeroPregunta => $pregunta) {
                $planas[$numeroPregunta] = [
                    'seccion'     => $numeroSeccion,
                    'texto'       => $pregunta['texto'],
                    'tipo'        => $pregunta['tipo'],
                    'obligatorio' => $pregunta['obligatorio'] ?? ($pregunta['tipo'] !== 'texto_libre'),
                ];
            }
        }

        return $planas;
    }

    /**
     * Devuelve los valores permitidos para un tipo de pregunta.
     *
     * @return array<int, string>
     */
    public function valoresPermitidosPorTipo(string $tipo): array
    {
        return match ($tipo) {
            'si_no', 'si_no_detalle' => ['Si', 'No'],
            'aplica_detalle'         => ['Aplica', 'No aplica'],
            'mano_dominante'         => ['Derecha', 'Izquierda'],
            // numero, checkbox_multiple, actividades_salud y segmento_corporal
            // no tienen lista cerrada - se valida que valor no sea null o vacio.
            default                  => [],
        };
    }
}
